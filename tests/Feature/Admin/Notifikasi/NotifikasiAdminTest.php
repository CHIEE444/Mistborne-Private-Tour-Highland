<?php

namespace Tests\Feature\Admin\Notifikasi;

use App\Models\Perjalanan;
use App\Models\PermintaanPesanan;
use App\Models\PesananKustom;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotifikasiAdminTest extends TestCase
{
    private function kirimBookingDariWebsite(string $nama): void
    {
        $journey = Perjalanan::query()->where('template_pesanan_kustom', false)->whereNotNull('harga')->where('tone', '<>', 'custom')->orderBy('urutan')->firstOrFail();
        $this->bukaTanggal($journey, Carbon::today()->addDays(12));

        $this->actingAs(User::factory()->create(['jabatan' => 'tamu']))->post(route('booking.simpan'), [
            'token_booking' => (string) Str::uuid(),
            'destination' => $journey->nama,
            'pax' => 2,
            'nama_lengkap' => $nama,
            'whatsapp' => '081234567890',
            'tanggal_trip' => Carbon::today()->addDays(12)->toDateString(),
            'status_penginapan' => 'sudah',
            'nama_hotel' => 'Hotel Contoh',
            'alamat_hotel' => 'Jl. Uji Alamat No. 1, Kota Uji',
            'lokasi_pickup' => 'Jl. Contoh No. 10',
            'kendaraan_pilihan' => 'Jeep klasik (petualangan dataran tinggi) — Toyota Land Cruiser FJ40',
        ])->assertStatus(302);
    }

    public function test_booking_dari_website_menambah_angka_notifikasi_admin(): void
    {
        $admin = User::factory()->create(['jabatan' => 'admin']);

        $this->actingAs($admin)->get(route('admin.notifikasi.ringkasan'))
            ->assertOk()
            ->assertJsonPath('jumlah_belum_dibaca', 0);

        $this->kirimBookingDariWebsite('Tamu Notifikasi Satu');
        $this->kirimBookingDariWebsite('Tamu Notifikasi Dua');

        $respons = $this->actingAs($admin)->get(route('admin.notifikasi.ringkasan'));

        $respons->assertOk()
            ->assertJsonPath('jumlah_belum_dibaca', 2)
            ->assertJsonCount(2, 'daftar')
            ->assertJsonPath('daftar.0.jenis', 'permintaan')
            ->assertJsonPath('daftar.0.dibaca', false);
        $this->assertStringContainsString('booking dari website', $respons->json('daftar.0.baris.0'));
    }

    public function test_header_menampilkan_angka_notifikasi_belum_dibaca(): void
    {
        $admin = User::factory()->create(['jabatan' => 'admin']);
        $this->kirimBookingDariWebsite('Tamu Header');
        $this->kirimBookingDariWebsite('Tamu Header Dua');
        $this->kirimBookingDariWebsite('Tamu Header Tiga');

        $this->actingAs($admin)->get(route('admin.dashboard.indeks'))
            ->assertOk()
            ->assertSee('Tandai semua dibaca')
            ->assertSee('jumlah_belum_dibaca\\u0022:3', false);
    }

    public function test_membuka_notifikasi_menandai_dibaca_dan_mengarah_ke_permintaan_form(): void
    {
        $admin = User::factory()->create(['jabatan' => 'admin']);
        $this->kirimBookingDariWebsite('Tamu Buka Notifikasi');
        $notifikasi = $admin->notifications()->firstOrFail();

        $this->actingAs($admin)->post(route('admin.notifikasi.baca', $notifikasi->id))
            ->assertRedirect(route('admin.permintaan-pesanan.indeks'));

        $this->assertNotNull($notifikasi->fresh()->read_at);
        $this->actingAs($admin)->get(route('admin.notifikasi.ringkasan'))->assertJsonPath('jumlah_belum_dibaca', 0);
    }

    public function test_tandai_semua_dibaca_hanya_memengaruhi_akun_sendiri(): void
    {
        $admin = User::factory()->create(['jabatan' => 'admin']);
        $adminLain = User::factory()->create(['jabatan' => 'admin']);
        $this->kirimBookingDariWebsite('Tamu Baca Semua');

        $this->actingAs($admin)->postJson(route('admin.notifikasi.baca-semua'))
            ->assertOk()
            ->assertJsonPath('jumlah_belum_dibaca', 0)
            ->assertJsonPath('daftar.0.dibaca', true);

        $this->assertSame(1, $adminLain->unreadNotifications()->count());
    }

    public function test_notifikasi_tidak_dapat_diakses_tanpa_login_admin(): void
    {
        $this->getJson(route('admin.notifikasi.ringkasan'))->assertUnauthorized();
        $this->actingAs(User::factory()->create(['jabatan' => 'tamu']))
            ->getJson(route('admin.notifikasi.ringkasan'))
            ->assertRedirect();
    }

    public function test_angka_permintaan_baru_mengikuti_permintaan_yang_perlu_ditinjau(): void
    {
        $admin = User::factory()->create(['jabatan' => 'admin']);
        $this->kirimBookingDariWebsite('Tamu Angka Satu');
        $this->kirimBookingDariWebsite('Tamu Angka Dua');

        $this->actingAs($admin)->get(route('admin.notifikasi.ringkasan'))
            ->assertJsonPath('jumlah_permintaan_baru', 2);
        $this->actingAs($admin)->get(route('admin.pesanan.indeks'))
            ->assertOk()
            ->assertSee('badgeAdminAwal = { permintaan: 2, pertanyaan: 0, kustom: 0 }', false);

        $permintaan = PermintaanPesanan::query()->where('nama_lengkap', 'Tamu Angka Satu')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.permintaan-pesanan.tidak-jadi', $permintaan))->assertSessionHasNoErrors();

        $this->actingAs($admin)->get(route('admin.notifikasi.ringkasan'))
            ->assertJsonPath('jumlah_permintaan_baru', 1);
    }

    public function test_pesanan_kustom_dari_website_memberi_notifikasi_badge_dan_tautan_ke_detail(): void
    {
        $admin = User::factory()->create(['jabatan' => 'admin']);

        $this->post(route('rancang-trip-kustom.simpan'), [
            'nama' => 'Rudi Kustom',
            'whatsapp' => '+62 813-1111-2222',
            'destinasi_diinginkan' => 'Dieng - Bromo',
            'tanggal_diinginkan' => now()->addDays(30)->toDateString(),
            'durasi_hari' => 3,
            'lokasi_penjemputan' => 'Malang',
            'status_penginapan' => 'sudah',
            'nama_hotel' => 'Hotel Uji Penginapan',
            'alamat_hotel' => 'Jl. Uji Alamat No. 1, Kota Uji',
            'jumlah_orang' => 4,
        ])->assertRedirect(route('rancang-trip-kustom'));

        $kustom = PesananKustom::query()->firstOrFail();
        $this->assertSame('6281311112222', $kustom->whatsapp);

        $respons = $this->actingAs($admin)->get(route('admin.notifikasi.ringkasan'));
        $respons->assertJsonPath('jumlah_kustom_baru', 1)
            ->assertJsonPath('jumlah_belum_dibaca', 1)
            ->assertJsonPath('daftar.0.jenis', 'kustom')
            ->assertJsonPath('daftar.0.label', 'Kustom');
        $this->assertStringContainsString('Rudi Kustom', $respons->json('daftar.0.baris.0'));

        $this->actingAs($admin)->post($respons->json('daftar.0.url_baca'))
            ->assertRedirect(route('admin.pesanan-kustom.detail', $kustom->id));

        $this->actingAs($admin)->get(route('admin.pesanan.indeks'))
            ->assertSee('kustom: 1', false);
    }
}
