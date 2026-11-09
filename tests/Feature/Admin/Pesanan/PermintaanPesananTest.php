<?php

namespace Tests\Feature\Admin\Pesanan;

use App\Enums\BookingStatus;
use App\Models\JadwalKeberangkatan;
use App\Models\Perjalanan;
use App\Models\PermintaanPesanan;
use App\Models\Pesanan;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class PermintaanPesananTest extends TestCase
{
    private function buatPermintaanMasuk(): PermintaanPesanan
    {
        $journey = Perjalanan::factory()->create(['harga' => 2_000_000]);
        $jadwal = JadwalKeberangkatan::factory()->create(['perjalanan_id' => $journey->id]);

        return PermintaanPesanan::query()->create([
            'token' => Str::random(64),
            'whatsapp' => '081234567890',
            'status' => 'menunggu_tinjauan',
            'perjalanan_id' => $journey->id,
            'nama_lengkap' => 'Sari Wulandari',
            'tanggal_trip' => $jadwal->tanggal,
            'jumlah_wisatawan' => 3,
            'status_penginapan' => 'sudah',
            'nama_hotel' => 'Hotel Tugu',
            'lokasi_pickup' => 'Lobi Hotel Tugu',
            'token_berlaku_sampai' => now()->addDays(7),
        ]);
    }

    public function test_halaman_permintaan_menampilkan_pesan_konfirmasi_whatsapp_yang_bisa_disalin(): void
    {
        $permintaan = $this->buatPermintaanMasuk();

        $response = $this->actingAs(User::factory()->create())->get(route('admin.permintaan-pesanan.indeks'));

        $response->assertOk()
            ->assertSee('Konfirmasi via WhatsApp')
            ->assertSee('Salin Pesan')
            ->assertSee('https://wa.me/6281234567890', false)
            ->assertSee(rawurlencode('Hotel Tugu'), false);
        $this->assertStringContainsString($permintaan->token, urldecode((string) $response->getContent()));
    }

    public function test_admin_membuat_pesanan_dari_permintaan_yang_sudah_dikonfirmasi(): void
    {
        $permintaan = $this->buatPermintaanMasuk();
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.permintaan-pesanan.follow-up', $permintaan), ['catatan_admin' => 'Sudah chat tamu'])
            ->assertSessionHasNoErrors();
        $this->assertSame('ditindaklanjuti', $permintaan->fresh()->status);

        $this->actingAs($admin)->post(route('admin.permintaan-pesanan.buat-pesanan', $permintaan))
            ->assertSessionHasNoErrors();

        $permintaan->refresh();
        $this->assertSame('disetujui', $permintaan->status);
        $pesanan = Pesanan::query()->findOrFail($permintaan->pesanan_id);
        $this->assertSame(BookingStatus::Dihubungi, $pesanan->status);
        $this->assertSame('6281234567890', $pesanan->whatsapp);
    }

    public function test_nomor_dengan_kode_negara_dinormalisasi_saat_pesanan_manual_dibuat(): void
    {
        $journey = Perjalanan::factory()->create(['harga' => 1_000_000]);
        $jadwal = JadwalKeberangkatan::factory()->create(['perjalanan_id' => $journey->id]);

        $this->actingAs(User::factory()->create())->post(route('admin.pesanan.simpan'), [
            'perjalanan_id' => $journey->id,
            'tanggal_trip' => $jadwal->tanggal->toDateString(),
            'nama_lengkap' => 'Dewi Lestari',
            'whatsapp' => '+62 812-3456-7890',
            'status_penginapan' => 'sudah',
            'nama_hotel' => 'Hotel A',
            'jumlah_wisatawan' => 2,
            'lokasi_pickup' => 'Hotel A',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('pesanan', ['nama_lengkap' => 'Dewi Lestari', 'whatsapp' => '6281234567890']);
    }

    public function test_layout_admin_memuat_satu_modal_salin(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('admin.pesanan.indeks'));

        $this->assertSame(1, substr_count((string) $response->getContent(), 'id="judul-modal-salin"'));
    }

    public function test_halaman_permintaan_menampilkan_ketersediaan_slot_dan_tanggal_penuh(): void
    {
        $permintaan = $this->buatPermintaanMasuk();
        $admin = User::factory()->create();

        $this->actingAs($admin)->get(route('admin.permintaan-pesanan.indeks'))
            ->assertOk()
            ->assertSee('Tersedia · sisa 5 slot')
            ->assertSee('Deal · Buat Pesanan');

        JadwalKeberangkatan::query()
            ->where('perjalanan_id', $permintaan->perjalanan_id)
            ->whereDate('tanggal', $permintaan->tanggal_trip)
            ->update(['kuota' => 1]);
        Pesanan::factory()->create(['perjalanan_id' => $permintaan->perjalanan_id, 'tanggal_trip' => $permintaan->tanggal_trip]);

        $this->actingAs($admin)->get(route('admin.permintaan-pesanan.indeks'))
            ->assertOk()
            ->assertSee('Kuota journey di tanggal ini sudah penuh. Pilih tanggal lain.')
            ->assertDontSee('Deal · Buat Pesanan');
    }
}
