<?php

namespace Tests\Feature\Admin\Pesanan;

use App\Aksi\Pesanan\BuatPesananManual;
use App\Layanan\PesanWhatsAppLayanan;
use App\Models\Kendaraan;
use App\Models\Perjalanan;
use App\Models\Pesanan;
use App\Models\User;
use Tests\TestCase;

class PenugasanMobilOtomatisTest extends TestCase
{
    private function bromo(): Perjalanan
    {
        return Perjalanan::query()->where('nama', 'BROMO SUNRISE JOURNEY')->firstOrFail();
    }

    private function buatPesanan(Perjalanan $journey, int $tamu = 4, int $hariKe = 15): Pesanan
    {
        $this->bukaTanggal($journey, now()->addDays($hariKe));

        return app(BuatPesananManual::class)->handle($journey, [
            'tanggal_trip' => now()->addDays($hariKe)->toDateString(),
            'nama_lengkap' => 'Tamu Otomatis',
            'whatsapp' => '6281234567890',
            'jumlah_wisatawan' => $tamu,
            'status_penginapan' => 'belum',
            'lokasi_pickup' => 'Stasiun',
        ]);
    }

    public function test_pesanan_baru_memakai_mobil_utama_journey_bila_kosong(): void
    {
        $bromo = $this->bromo();

        $pesanan = $this->buatPesanan($bromo);

        $this->assertSame($bromo->kendaraan_id, $pesanan->kendaraan_id);
    }

    public function test_pesanan_kedua_di_tanggal_sama_memakai_mobil_lain_yang_kosong_dan_muat(): void
    {
        $bromo = $this->bromo();

        $pertama = $this->buatPesanan($bromo);
        $kedua = $this->buatPesanan($bromo);

        $this->assertSame($bromo->kendaraan_id, $pertama->kendaraan_id);
        $this->assertNotNull($kedua->kendaraan_id);
        $this->assertNotSame($pertama->kendaraan_id, $kedua->kendaraan_id);

        $pengganti = Kendaraan::query()->findOrFail($kedua->kendaraan_id);
        $this->assertSame('siap', $pengganti->status);
        $this->assertGreaterThanOrEqual(5, $pengganti->kapasitas);
    }

    public function test_pengganti_tidak_memakai_mobil_nonaktif_atau_yang_kursinya_kurang(): void
    {
        Kendaraan::query()->delete();
        $utama = Kendaraan::factory()->create(['nama' => 'Utama', 'kapasitas' => 7]);
        Kendaraan::factory()->nonaktif()->create(['nama' => 'Mati', 'kapasitas' => 7]);
        Kendaraan::factory()->create(['nama' => 'Kecil', 'kapasitas' => 4]);
        $cadangan = Kendaraan::factory()->create(['nama' => 'Cadangan', 'kapasitas' => 7]);
        $journey = Perjalanan::factory()->create(['harga' => 4_000_000, 'durasi_hari' => 2, 'maks_orang' => 4, 'kendaraan_id' => $utama->id]);

        $pertama = $this->buatPesanan($journey);
        $kedua = $this->buatPesanan($journey);

        $this->assertSame($utama->id, $pertama->kendaraan_id);
        $this->assertSame($cadangan->id, $kedua->kendaraan_id);
    }

    public function test_pesanan_tetap_dibuat_tanpa_mobil_bila_semua_unit_terpakai(): void
    {
        Kendaraan::query()->delete();
        $satu = Kendaraan::factory()->create(['kapasitas' => 7]);
        $journey = Perjalanan::factory()->create(['harga' => 4_000_000, 'durasi_hari' => 2, 'maks_orang' => 4, 'kendaraan_id' => $satu->id]);

        $this->buatPesanan($journey);
        $habis = $this->buatPesanan($journey);

        $this->assertNull($habis->kendaraan_id);
        $this->assertDatabaseHas('pesanan', ['id' => $habis->id]);

        $this->actingAs(User::factory()->create())->get(route('admin.pesanan.detail', $habis))
            ->assertOk()
            ->assertSee('Belum ada mobil yang tersedia di tanggal ini');
    }

    public function test_tanggal_berbeda_tidak_bentrok_dan_kembali_memakai_mobil_utama(): void
    {
        $bromo = $this->bromo();

        $this->buatPesanan($bromo, 4, 15);
        $lain = $this->buatPesanan($bromo, 4, 40);

        $this->assertSame($bromo->kendaraan_id, $lain->kendaraan_id);
    }

    public function test_halaman_admin_memberi_tahu_bila_mobil_utama_diganti_otomatis(): void
    {
        $bromo = $this->bromo();
        $this->buatPesanan($bromo);
        $kedua = $this->buatPesanan($bromo);

        $this->actingAs(User::factory()->create())->get(route('admin.pesanan.detail', $kedua))
            ->assertOk()
            ->assertSee('tidak tersedia di tanggal ini, jadi sistem menugaskan');
    }

    public function test_pesan_pesanan_disetujui_menyebut_mobil_yang_ditugaskan(): void
    {
        $pesanan = $this->buatPesanan($this->bromo());

        $teks = app(PesanWhatsAppLayanan::class)->pesananDisetujui($pesanan->fresh());

        $this->assertStringContainsString('Kendaraan: '.$pesanan->kendaraan->namaUntukTamu(), $teks);
        $this->assertStringContainsString('Unit kendaraan dapat berubah menyesuaikan ketersediaan', $teks);
    }

    public function test_pesan_pesanan_disetujui_tanpa_mobil_menyebut_akan_dikonfirmasi_admin(): void
    {
        $pesanan = Pesanan::factory()->create(['kendaraan_id' => null]);

        $this->assertStringContainsString('Kendaraan: Akan dikonfirmasi oleh admin', app(PesanWhatsAppLayanan::class)->pesananDisetujui($pesanan));
    }

    public function test_admin_mengganti_unit_dan_pesan_kendaraan_siap_dikirim_ke_tamu_tanpa_menyebut_unit_lama(): void
    {
        $admin = User::factory()->create();
        $pesanan = $this->buatPesanan($this->bromo());
        $unitLama = $pesanan->kendaraan->namaUntukTamu();
        $pengganti = Kendaraan::query()->where('status', 'siap')->where('kapasitas', '>=', 5)->where('id', '<>', $pesanan->kendaraan_id)->firstOrFail();

        $this->actingAs($admin)->put(route('admin.pesanan.operasional', $pesanan), ['kendaraan_id' => $pengganti->id])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('perubahan_unit', ['baru' => $pengganti->namaUntukTamu()]);

        $this->actingAs($admin)->withSession(['perubahan_unit' => ['baru' => $pengganti->namaUntukTamu()]])
            ->get(route('admin.pesanan.detail', $pesanan))
            ->assertOk()
            ->assertSee('Kendaraan Siap (Unit untuk Tamu)')
            ->assertSee('Unit: '.$pengganti->namaUntukTamu())
            ->assertSee('menjemput Kakak dan dipakai selama perjalanan tour')
            ->assertDontSee('Unit sebelumnya');

        $this->assertStringNotContainsString($unitLama, app(PesanWhatsAppLayanan::class)->perubahanUnit($pesanan->fresh(), $pengganti->namaUntukTamu()));
    }

    public function test_menyimpan_operasional_tanpa_mengganti_unit_tidak_memunculkan_pesan_perubahan_unit(): void
    {
        $pesanan = $this->buatPesanan($this->bromo());

        $this->actingAs(User::factory()->create())->put(route('admin.pesanan.operasional', $pesanan), ['kendaraan_id' => $pesanan->kendaraan_id, 'catatan_admin' => 'Catatan saja'])
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('perubahan_unit');
    }

    public function test_pesan_kendaraan_siap_memuat_unit_untuk_jemput_dan_perjalanan_tour(): void
    {
        $pesanan = Pesanan::factory()->create(['kendaraan_id' => null]);

        $teks = app(PesanWhatsAppLayanan::class)->perubahanUnit($pesanan, 'Toyota Fortuner 2020');

        $this->assertStringContainsString('Kendaraan untuk perjalanan Kakak sudah siap digunakan', $teks);
        $this->assertStringContainsString('Unit: Toyota Fortuner 2020', $teks);
        $this->assertStringContainsString('menjemput Kakak dan dipakai selama perjalanan tour', $teks);
        $this->assertStringNotContainsString('sebelumnya', $teks);
    }
}
