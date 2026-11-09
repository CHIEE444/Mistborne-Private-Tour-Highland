<?php

namespace Tests\Feature\Admin\Pesanan;

use App\Aksi\Pesanan\SinkronkanTahapPesanan;
use App\Enums\BookingStatus;
use App\Models\Kendaraan;
use App\Models\PersonelOperasional;
use App\Models\Pesanan;
use App\Models\User;
use Tests\Support\DataGuide;
use Tests\TestCase;

/** Alur tahap pesanan: Dihubungi → Menunggu Konfirmasi Pembayaran → Sudah Bayar → Lunas (Menunggu Trip) → Trip Berlangsung → Trip Selesai. */
class TahapPesananTest extends TestCase
{
    /** Pesanan berstatus Pembayaran yang semua syarat tripnya beres (lunas, guide menerima, kendaraan ada, penginapan milik tamu). */
    private function pesananSiapTrip(array $timpa = []): Pesanan
    {
        $guide = PersonelOperasional::query()->create([...DataGuide::sim(), 'nama' => 'Guide Tahap', 'peran' => 'guide', 'status_operasional' => 'siap', 'aktif' => true]);

        return Pesanan::factory()->payment()->create([
            'harga' => 3_000_000,
            'nominal_dibayar' => 3_000_000,
            'guide_personel_id' => $guide->id,
            'nama_guide' => $guide->nama,
            'status_konfirmasi_guide' => 'diterima',
            'kendaraan_id' => Kendaraan::factory()->create()->id,
            ...$timpa,
        ]);
    }

    public function test_tahap_mengikuti_konfirmasi_pembayaran_dan_tanggal_trip(): void
    {
        $this->assertSame('dihubungi', Pesanan::factory()->create()->tahap());
        $this->assertSame('menunggu_pembayaran', Pesanan::factory()->create(['dikonfirmasi_at' => now()])->tahap());
        $this->assertSame('sudah_bayar', Pesanan::factory()->payment()->create(['harga' => 3_000_000, 'nominal_dibayar' => 1_000_000])->tahap());
        $this->assertSame('lunas', $this->pesananSiapTrip(['tanggal_trip' => today()->addDays(10)])->tahap());
        $this->assertSame('berlangsung', $this->pesananSiapTrip(['tanggal_trip' => today()->subDay(), 'tanggal_selesai' => today()->addDay()])->tahap());
        $this->assertSame('selesai', $this->pesananSiapTrip(['tanggal_trip' => today()->subDays(3), 'tanggal_selesai' => today()->subDay()])->tahap());
        $this->assertSame('Lunas (Menunggu Trip)', $this->pesananSiapTrip(['tanggal_trip' => today()->addDays(10)])->labelTahap());
    }

    public function test_belum_lunas_atau_syarat_belum_beres_membuat_trip_tidak_bisa_dilaksanakan(): void
    {
        $belumLunas = $this->pesananSiapTrip(['tanggal_trip' => today(), 'nominal_dibayar' => 1_000_000]);
        $tanpaKendaraan = $this->pesananSiapTrip(['tanggal_trip' => today(), 'kendaraan_id' => null]);

        $this->assertSame('sudah_bayar', $belumLunas->tahap());
        $this->assertTrue($belumLunas->tripTidakBisaDilaksanakan());
        $this->assertSame(['Pembayaran belum lunas'], $belumLunas->syaratTripBelumTerpenuhi());
        $this->assertSame('lunas', $tanpaKendaraan->tahap());
        $this->assertSame(['Kendaraan belum ditetapkan'], $tanpaKendaraan->syaratTripBelumTerpenuhi());

        $this->actingAs(User::factory()->create())->get(route('admin.pesanan.detail', $belumLunas))
            ->assertOk()
            ->assertSee('Trip tidak bisa dilaksanakan: Pembayaran belum lunas.');
    }

    public function test_sinkronisasi_menyimpan_trip_selesai_hanya_bila_semua_syarat_beres(): void
    {
        $usai = $this->pesananSiapTrip(['tanggal_trip' => today()->subDays(3), 'tanggal_selesai' => today()->subDay()]);
        $usaiBelumLunas = $this->pesananSiapTrip(['tanggal_trip' => today()->subDays(3), 'tanggal_selesai' => today()->subDay(), 'nominal_dibayar' => 1_000_000]);

        $hasil = app(SinkronkanTahapPesanan::class)->handle();

        $this->assertSame(1, $hasil['selesai']);
        $this->assertSame(BookingStatus::Completed, $usai->fresh()->status);
        $this->assertSame(BookingStatus::Payment, $usaiBelumLunas->fresh()->status);
    }

    public function test_tab_daftar_pesanan_menyaring_per_tahap(): void
    {
        $this->pesananSiapTrip(['nama_lengkap' => 'Tamu Lunas Tahap', 'tanggal_trip' => today()->addDays(10)]);
        Pesanan::factory()->payment()->create(['nama_lengkap' => 'Tamu Nyicil Tahap', 'harga' => 3_000_000, 'nominal_dibayar' => 1_000_000]);
        Pesanan::factory()->create(['nama_lengkap' => 'Tamu Deal Tahap', 'dikonfirmasi_at' => now()]);
        $admin = User::factory()->create();

        $this->actingAs($admin)->get(route('admin.pesanan.indeks'))
            ->assertOk()
            ->assertSeeInOrder(['Dihubungi', 'Menunggu Konfirmasi Pembayaran', 'Sudah Bayar', 'Lunas (Menunggu Trip)', 'Trip Berlangsung', 'Trip Selesai']);

        $this->actingAs($admin)->get(route('admin.pesanan.indeks', ['tahap' => 'lunas']))
            ->assertOk()->assertSee('Tamu Lunas Tahap')->assertDontSee('Tamu Nyicil Tahap')->assertDontSee('Tamu Deal Tahap');
        $this->actingAs($admin)->get(route('admin.pesanan.indeks', ['tahap' => 'sudah_bayar']))
            ->assertOk()->assertSee('Tamu Nyicil Tahap')->assertDontSee('Tamu Lunas Tahap');
        $this->actingAs($admin)->get(route('admin.pesanan.indeks', ['tahap' => 'menunggu_pembayaran']))
            ->assertOk()->assertSee('Tamu Deal Tahap')->assertDontSee('Tamu Nyicil Tahap');
        $this->assertSame(1, Pesanan::jumlahPerTahap()['lunas']);
    }

    public function test_dashboard_memperingatkan_tamu_lewat_batas_bayar_dan_trip_yang_belum_siap(): void
    {
        Pesanan::factory()->create(['nama_lengkap' => 'Tamu Telat Bayar', 'dikonfirmasi_at' => now()->subDays(4), 'nominal_dibayar' => null]);
        $this->pesananSiapTrip(['nama_lengkap' => 'Tamu Belum Siap', 'tanggal_trip' => today()->addDays(2), 'nominal_dibayar' => 1_000_000]);

        $this->actingAs(User::factory()->create())->get(route('admin.dashboard.indeks'))
            ->assertOk()
            ->assertSee('1 tamu lewat batas bayar')
            ->assertSee('Tamu Telat Bayar')
            ->assertSee('Batal otomatis '.now()->subDays(4)->addDays(5)->translatedFormat('d M'))
            ->assertSee('1 trip belum bisa dilaksanakan')
            ->assertSeeInOrder(['Tamu Belum Siap', 'Pembayaran belum lunas']);
    }
}
