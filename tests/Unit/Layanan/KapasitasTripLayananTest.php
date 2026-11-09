<?php

namespace Tests\Unit\Layanan;

use App\Enums\BookingStatus;
use App\Layanan\KapasitasTripLayanan;
use App\Models\Perjalanan;
use App\Models\PersonelOperasional;
use App\Models\Pesanan;
use Illuminate\Validation\ValidationException;
use Tests\Support\DataGuide;
use Tests\TestCase;

class KapasitasTripLayananTest extends TestCase
{
    private KapasitasTripLayanan $layanan;

    protected function setUp(): void
    {
        parent::setUp();

        // Jumlah guide diatur sendiri oleh tiap tes supaya angka sisa slot mudah dibaca.
        $this->tanpaGuideCadangan();
        $this->layanan = new KapasitasTripLayanan;
    }

    private function guide(int $jumlah = 1, array $timpa = []): void
    {
        foreach (range(1, $jumlah) as $nomor) {
            PersonelOperasional::query()->create([
                ...DataGuide::sim(),
                'nama' => 'Guide Kapasitas '.uniqid(),
                'peran' => PersonelOperasional::PERAN,
                'email' => 'guide.kapasitas'.uniqid().'@example.com',
                'status_operasional' => 'siap',
                'aktif' => true,
                ...$timpa,
            ]);
        }
    }

    private function journey(int $durasiHari = 1): Perjalanan
    {
        return Perjalanan::factory()->create(['durasi_hari' => $durasiHari, 'template_pesanan_kustom' => false]);
    }

    private function hari(int $hariKe): string
    {
        return today()->addDays($hariKe)->toDateString();
    }

    public function test_journey_biasa_hanya_menawarkan_tanggal_yang_dibuka_dalam_rentang_h7_sampai_setahun(): void
    {
        $this->guide();
        $journey = $this->journey();
        $this->bukaTanggal($journey, $this->hari(6));
        $this->bukaTanggal($journey, $this->hari(7));
        $this->bukaTanggal($journey, $this->hari(20));
        $this->bukaTanggal($journey, today()->addDays(7)->addYear()->addDay());

        $tanggal = $this->layanan->daftarTanggal($journey)->pluck('tanggal')->all();

        $this->assertSame([$this->hari(7), $this->hari(20)], $tanggal);
    }

    public function test_tanggal_yang_belum_dibuka_ditolak_dengan_alasan_yang_jelas(): void
    {
        $this->guide();

        $hasil = $this->layanan->cek($this->journey(), $this->hari(20));

        $this->assertFalse($hasil['tersedia']);
        $this->assertSame('belum_dibuka', $hasil['status']);
        $this->assertSame('Tanggal ini belum dibuka di Tanggal Tersedia journey ini. Pilih tanggal yang tersedia.', $hasil['alasan']);
    }

    public function test_tanggal_kurang_dari_h7_atau_lebih_dari_setahun_di_luar_batas(): void
    {
        $this->guide();
        $journey = $this->journey();
        $this->bukaTanggal($journey, $this->hari(6));
        $terlaluJauh = today()->addDays(7)->addYear()->addDay()->toDateString();
        $this->bukaTanggal($journey, $terlaluJauh);

        $this->assertSame('Tanggal trip harus minimal tujuh hari dari hari ini (H-7).', $this->layanan->cek($journey, $this->hari(6))['alasan']);
        $this->assertSame('Tanggal trip hanya bisa dipilih sampai satu tahun ke depan.', $this->layanan->cek($journey, $terlaluJauh)['alasan']);
    }

    public function test_tanggal_diblokir_berstatus_ditutup(): void
    {
        $this->guide();
        $journey = $this->journey();
        $this->bukaTanggal($journey, $this->hari(20))->update(['diblok' => true]);

        $hasil = $this->layanan->cek($journey, $this->hari(20));

        $this->assertSame('ditutup', $hasil['status']);
        $this->assertSame('Ditutup', $hasil['label']);
    }

    public function test_kuota_dihitung_per_journey_sehingga_pesanan_journey_lain_tidak_memakai_kuota(): void
    {
        $this->guide(3);
        $journey = $this->journey();
        $lain = $this->journey();
        $this->bukaTanggal($journey, $this->hari(20), kuota: 1);
        Pesanan::factory()->create(['perjalanan_id' => $lain->id, 'tanggal_trip' => $this->hari(20)]);

        $this->assertSame(1, $this->layanan->cek($journey, $this->hari(20))['sisa']);

        Pesanan::factory()->create(['perjalanan_id' => $journey->id, 'tanggal_trip' => $this->hari(20)]);

        $hasil = $this->layanan->cek($journey, $this->hari(20));
        $this->assertSame('penuh', $hasil['status']);
        $this->assertSame('Kuota journey di tanggal ini sudah penuh. Pilih tanggal lain.', $hasil['alasan']);
    }

    public function test_sisa_slot_mengikuti_yang_paling_kecil_antara_kuota_dan_guide_bebas(): void
    {
        $this->guide(2);
        $journey = $this->journey();
        $this->bukaTanggal($journey, $this->hari(20), kuota: 5);

        $hasil = $this->layanan->cek($journey, $this->hari(20));

        $this->assertSame(2, $hasil['sisa']);
        $this->assertSame('Sisa 2 slot', $hasil['label']);
    }

    public function test_satu_guide_memegang_satu_trip_sepanjang_hari_trip_itu(): void
    {
        $this->guide();
        $journey = $this->journey();
        foreach ([20, 22, 23] as $hariKe) {
            $this->bukaTanggal($journey, $this->hari($hariKe));
        }
        // Trip journey lain 3 hari (hari ke-20 sampai 22) memakai satu-satunya guide.
        Pesanan::factory()->payment()->create(['tanggal_trip' => $this->hari(20), 'tanggal_selesai' => $this->hari(22)]);

        $this->assertSame('guide_penuh', $this->layanan->cek($journey, $this->hari(20))['status']);
        $this->assertSame('Semua guide sudah bertugas di tanggal itu. Pilih tanggal lain.', $this->layanan->cek($journey, $this->hari(22))['alasan']);
        $this->assertTrue($this->layanan->cek($journey, $this->hari(23))['tersedia']);
    }

    public function test_trip_beberapa_hari_butuh_guide_bebas_di_semua_harinya(): void
    {
        $this->guide();
        $journey = $this->journey(3);
        $this->bukaTanggal($journey, $this->hari(20));
        // Hari ke-22 adalah hari ketiga trip yang akan dipesan dan guide sudah dipakai trip lain.
        Pesanan::factory()->create(['tanggal_trip' => $this->hari(22), 'tanggal_selesai' => $this->hari(22)]);

        $this->assertSame('guide_penuh', $this->layanan->cek($journey, $this->hari(20))['status']);
        $this->assertTrue($this->layanan->cek($journey, $this->hari(20), durasiHari: 2)['tersedia']);
    }

    public function test_guide_dilepas_setelah_trip_selesai_atau_pesanan_dibatalkan(): void
    {
        $this->guide();
        $journey = $this->journey();
        $this->bukaTanggal($journey, $this->hari(20));
        $pesanan = Pesanan::factory()->payment()->create(['tanggal_trip' => $this->hari(20), 'tanggal_selesai' => $this->hari(20)]);

        $this->assertSame('guide_penuh', $this->layanan->cek($journey, $this->hari(20))['status']);

        $pesanan->update(['guide_trip_selesai_at' => now()]);
        $this->assertTrue((new KapasitasTripLayanan)->cek($journey, $this->hari(20))['tersedia']);

        $pesanan->update(['guide_trip_selesai_at' => null, 'status' => BookingStatus::Dibatalkan]);
        $this->assertTrue((new KapasitasTripLayanan)->cek($journey, $this->hari(20))['tersedia']);
    }

    public function test_hanya_guide_aktif_siap_dengan_sim_berlaku_sampai_hari_terakhir_trip_yang_dihitung(): void
    {
        $journey = $this->journey(2);
        $this->bukaTanggal($journey, $this->hari(20));
        $this->guide(1, ['aktif' => false]);
        $this->guide(1, ['status_operasional' => 'cuti']);
        // SIM habis di hari pertama, padahal trip berjalan dua hari.
        $this->guide(1, ['masa_berlaku_sim' => $this->hari(20)]);

        $this->assertSame('guide_penuh', $this->layanan->cek($journey, $this->hari(20))['status']);

        $this->guide(1, ['masa_berlaku_sim' => $this->hari(21)]);
        $this->assertSame(1, (new KapasitasTripLayanan)->cek($journey, $this->hari(20))['sisa']);
    }

    public function test_ganti_jadwal_tidak_menghitung_pesanan_itu_sendiri(): void
    {
        $this->guide();
        $journey = $this->journey();
        $this->bukaTanggal($journey, $this->hari(20), kuota: 1);
        $pesanan = Pesanan::factory()->create(['perjalanan_id' => $journey->id, 'tanggal_trip' => $this->hari(20)]);

        $this->assertFalse($this->layanan->cek($journey, $this->hari(20))['tersedia']);
        $this->assertTrue($this->layanan->cek($journey, $this->hari(20), kecualiPesananId: $pesanan->id)['tersedia']);
    }

    public function test_trip_kustom_bebas_memilih_hari_tanpa_tanggal_tersedia_tetapi_tetap_dibatasi_guide(): void
    {
        $this->guide();
        $kustom = Perjalanan::factory()->create(['template_pesanan_kustom' => true, 'durasi_hari' => 1]);

        $this->assertTrue($this->layanan->cek($kustom, $this->hari(20))['tersedia']);
        $this->assertSame($this->hari(7), $this->layanan->daftarTanggal($kustom)->first()['tanggal']);

        Pesanan::factory()->create(['tanggal_trip' => $this->hari(20), 'tanggal_selesai' => $this->hari(20)]);

        $this->assertSame('guide_penuh', $this->layanan->cek($kustom, $this->hari(20))['status']);
    }

    public function test_pastikan_bisa_dipesan_melempar_galat_validasi_dengan_alasan(): void
    {
        $this->guide();
        $journey = $this->journey();

        try {
            $this->layanan->pastikanBisaDipesan($journey, $this->hari(20));
            $this->fail('Tanggal yang belum dibuka seharusnya ditolak.');
        } catch (ValidationException $galat) {
            $this->assertSame(['tanggal_trip' => ['Tanggal ini belum dibuka di Tanggal Tersedia journey ini. Pilih tanggal yang tersedia.']], $galat->errors());
        }
    }

    public function test_rentang_kelebihan_guide_menandai_hari_yang_tripnya_lebih_banyak_dari_guide(): void
    {
        $this->guide();
        $bentrok = Pesanan::factory()->create(['tanggal_trip' => $this->hari(20), 'tanggal_selesai' => $this->hari(21)]);
        Pesanan::factory()->create(['tanggal_trip' => $this->hari(21), 'tanggal_selesai' => $this->hari(21)]);
        $aman = Pesanan::factory()->create(['tanggal_trip' => $this->hari(30), 'tanggal_selesai' => $this->hari(30)]);

        $rentang = $this->layanan->rentangKelebihanGuide();

        $this->assertCount(1, $rentang);
        $this->assertSame($this->hari(21), $rentang->first()['mulai']->toDateString());
        $this->assertSame($this->hari(21), $rentang->first()['akhir']->toDateString());
        $this->assertSame(['trip' => 2, 'guide' => 1], ['trip' => $rentang->first()['trip'], 'guide' => $rentang->first()['guide']]);
        $this->assertTrue($this->layanan->pesananKelebihanGuide($bentrok, $rentang));
        $this->assertFalse($this->layanan->pesananKelebihanGuide($aman, $rentang));
    }

    public function test_jumlah_trip_per_hari_memuat_hari_lanjutan_trip(): void
    {
        Pesanan::factory()->create(['tanggal_trip' => $this->hari(20), 'tanggal_selesai' => $this->hari(22)]);
        Pesanan::factory()->completed()->create(['tanggal_trip' => $this->hari(21), 'tanggal_selesai' => $this->hari(21)]);

        $this->assertSame(
            [$this->hari(20) => 1, $this->hari(21) => 1, $this->hari(22) => 1],
            $this->layanan->tripPerHari($this->hari(19), $this->hari(23)),
        );
    }
}
