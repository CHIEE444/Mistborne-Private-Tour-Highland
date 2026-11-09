<?php

namespace Tests\Feature\Admin\Pesanan;

use App\Enums\StatusPesananKustom;
use App\Models\Perjalanan;
use App\Models\PermintaanPesanan;
use App\Models\PersonelOperasional;
use App\Models\Pesanan;
use App\Models\PesananKustom;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\Support\DataGuide;
use Tests\TestCase;

/**
 * Semua formulir pemesanan hanya memakai Tanggal Tersedia journey, dan slot dibatasi kuota journey serta guide
 * yang masih bebas (1 guide memegang 1 trip sampai selesai).
 */
class SlotPesananTest extends TestCase
{
    private const ALASAN_BELUM_DIBUKA = 'Tanggal ini belum dibuka di Tanggal Tersedia journey ini. Pilih tanggal yang tersedia.';

    private const ALASAN_GUIDE_PENUH = 'Semua guide sudah bertugas di tanggal itu. Pilih tanggal lain.';

    private function journey(): Perjalanan
    {
        return Perjalanan::factory()->create(['harga' => 2_000_000, 'maks_orang' => 6, 'durasi_hari' => 1, 'template_pesanan_kustom' => false]);
    }

    private function hari(int $hariKe): string
    {
        return today()->addDays($hariKe)->toDateString();
    }

    /** Hanya satu guide siap, lalu guide itu dipakai trip lain di tanggal tersebut. */
    private function satuGuideSudahBertugas(string $tanggal): void
    {
        $this->tanpaGuideCadangan();
        PersonelOperasional::query()->create([
            ...DataGuide::sim(),
            'nama' => 'Guide Satu-satunya',
            'peran' => PersonelOperasional::PERAN,
            'email' => 'guide.satu@example.com',
            'status_operasional' => 'siap',
            'aktif' => true,
        ]);
        Pesanan::factory()->payment()->create(['tanggal_trip' => $tanggal, 'tanggal_selesai' => $tanggal]);
    }

    /**
     * @param  array<string, mixed>  $timpa
     * @return array<string, mixed>
     */
    private function dataPesananAdmin(Perjalanan $journey, string $tanggal, array $timpa = []): array
    {
        return [
            'perjalanan_id' => $journey->id,
            'tanggal_trip' => $tanggal,
            'nama_lengkap' => 'Tamu Slot',
            'whatsapp' => '081234567890',
            'status_penginapan' => 'sudah',
            'nama_hotel' => 'Hotel Slot',
            'jumlah_wisatawan' => 2,
            'lokasi_pickup' => 'Hotel Slot',
            ...$timpa,
        ];
    }

    public function test_halaman_booking_hanya_menampilkan_tanggal_tersedia_dan_menonaktifkan_tanggal_penuh(): void
    {
        $journey = $this->journey();
        $this->bukaTanggal($journey, $this->hari(20), kuota: 1);
        $this->bukaTanggal($journey, $this->hari(25));
        Pesanan::factory()->create(['perjalanan_id' => $journey->id, 'tanggal_trip' => $this->hari(20)]);

        $html = $this->get(route('booking.buat', ['destination' => $journey->nama]))->assertOk()->getContent();

        $this->assertSame(2, substr_count($html, 'name="tanggal_trip"'));
        $this->assertMatchesRegularExpression('/value="'.$this->hari(20).'"[^>]*disabled/', $html);
        $this->assertDoesNotMatchRegularExpression('/value="'.$this->hari(25).'"[^>]*disabled/', $html);
        $this->assertStringContainsString('Sisa 5 slot', $html);
    }

    public function test_halaman_booking_tanpa_tanggal_tersedia_mengarahkan_ke_whatsapp_atau_trip_kustom(): void
    {
        $journey = $this->journey();

        $this->get(route('booking.buat', ['destination' => $journey->nama]))
            ->assertOk()
            ->assertSee('Belum ada tanggal yang bisa dipesan untuk perjalanan ini.')
            ->assertSee(route('rancang-trip-kustom'), false)
            ->assertDontSee('name="tanggal_trip"', false);
    }

    public function test_booking_publik_menolak_tanggal_yang_belum_dibuka_tanpa_membuat_permintaan(): void
    {
        $journey = $this->journey();

        $this->post(route('booking.simpan'), [
            'token_booking' => (string) Str::uuid(),
            'destination' => $journey->nama,
            'pax' => 2,
            'nama_lengkap' => 'Tamu Tanggal Tutup',
            'whatsapp' => '081234567890',
            'tanggal_trip' => $this->hari(20),
            'lokasi_pickup' => 'Stasiun Malang',
        ])->assertSessionHasErrors(['tanggal_trip' => self::ALASAN_BELUM_DIBUKA]);

        $this->assertDatabaseCount('permintaan_pesanan', 0);
    }

    public function test_formulir_tautan_whatsapp_menolak_tanggal_yang_semua_guidenya_bertugas(): void
    {
        $journey = $this->journey();
        $this->bukaTanggal($journey, $this->hari(20));
        $this->satuGuideSudahBertugas($this->hari(20));
        $permintaan = PermintaanPesanan::query()->create([
            'token' => Str::random(64),
            'whatsapp' => '081234567890',
            'status' => 'menunggu_tamu',
            'token_berlaku_sampai' => PermintaanPesanan::batasBerlakuBaru(),
        ]);

        $this->post(route('form-pesanan.simpan', $permintaan->token), [
            'nama_lengkap' => 'Tamu Tautan',
            'whatsapp' => '081234567890',
            'perjalanan_id' => $journey->id,
            'tanggal_trip' => $this->hari(20),
            'jumlah_wisatawan' => 2,
            'lokasi_pickup' => 'Stasiun Malang',
        ])->assertSessionHasErrors(['tanggal_trip' => self::ALASAN_GUIDE_PENUH]);

        $this->assertSame('menunggu_tamu', $permintaan->fresh()->status);
    }

    public function test_admin_tidak_bisa_membuat_pesanan_di_tanggal_yang_belum_dibuka(): void
    {
        $journey = $this->journey();

        $this->actingAs(User::factory()->create())
            ->post(route('admin.pesanan.simpan'), $this->dataPesananAdmin($journey, $this->hari(20)))
            ->assertSessionHasErrors(['tanggal_trip' => self::ALASAN_BELUM_DIBUKA]);

        $this->assertDatabaseMissing('pesanan', ['nama_lengkap' => 'Tamu Slot']);
    }

    public function test_admin_tidak_bisa_membuat_pesanan_saat_semua_guide_bertugas_walau_kuota_journey_masih_ada(): void
    {
        $journey = $this->journey();
        $this->bukaTanggal($journey, $this->hari(20), kuota: 5);
        $this->satuGuideSudahBertugas($this->hari(20));

        $this->actingAs(User::factory()->create())
            ->post(route('admin.pesanan.simpan'), $this->dataPesananAdmin($journey, $this->hari(20)))
            ->assertSessionHasErrors(['tanggal_trip' => self::ALASAN_GUIDE_PENUH]);

        $this->assertDatabaseMissing('pesanan', ['nama_lengkap' => 'Tamu Slot']);
    }

    public function test_pesanan_baru_admin_langsung_memegang_slot_tanggal_itu(): void
    {
        $journey = $this->journey();
        $this->bukaTanggal($journey, $this->hari(20), kuota: 1);
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.pesanan.simpan'), $this->dataPesananAdmin($journey, $this->hari(20)))->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->post(route('admin.pesanan.simpan'), $this->dataPesananAdmin($journey, $this->hari(20), ['nama_lengkap' => 'Tamu Kedua']))
            ->assertSessionHasErrors(['tanggal_trip' => 'Kuota journey di tanggal ini sudah penuh. Pilih tanggal lain.']);
    }

    public function test_trip_kustom_tidak_bisa_diresmikan_saat_semua_guide_bertugas(): void
    {
        $admin = User::factory()->create();
        $tanggal = $this->hari(20);
        $this->satuGuideSudahBertugas($tanggal);
        $kustom = PesananKustom::factory()->create([
            'nama' => 'Tamu Kustom Slot',
            'jumlah_orang' => 2,
            'harga_final' => 1_000_000,
            'status' => StatusPesananKustom::Diproses,
            'rencana_perjalanan' => 'Hari 1 Dieng',
            'tanggal_fleksibel' => false,
            'tanggal_diinginkan' => $tanggal,
        ]);
        $this->actingAs($admin)->post(route('admin.pesanan-kustom.penawaran-terkirim', $kustom))->assertSessionHasNoErrors();

        $this->actingAs($admin)->post(route('admin.pesanan-kustom.jadikan-resmi', $kustom), [
            'tamu_menyetujui' => '1',
            'tanggal_trip' => $tanggal,
            'lokasi_pickup' => 'Hotel Malang',
            'status_penginapan' => 'belum',
        ])->assertSessionHasErrors('tanggal_trip');

        $this->assertDatabaseMissing('pesanan', ['nama_lengkap' => 'Tamu Kustom Slot']);
        $this->assertNull($kustom->fresh()->pesanan_id);
    }
}
