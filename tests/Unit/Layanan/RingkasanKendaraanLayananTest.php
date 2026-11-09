<?php

namespace Tests\Unit\Layanan;

use App\Layanan\RingkasanKendaraanLayanan;
use App\Models\Kendaraan;
use App\Models\Pesanan;
use App\Models\ServisKendaraan;
use Tests\TestCase;

class RingkasanKendaraanLayananTest extends TestCase
{
    private function ringkas(Kendaraan $kendaraan): array
    {
        return (new RingkasanKendaraanLayanan)->ringkas($kendaraan);
    }

    /** Kendaraan siap dengan dokumen berlaku dan satu riwayat servis: semua pemeriksaan lolos. */
    private function kendaraanPrima(): Kendaraan
    {
        $kendaraan = Kendaraan::factory()->terawat()->create();
        ServisKendaraan::factory()->selesai()->for($kendaraan)->create();

        return $kendaraan;
    }

    private function pesananUntuk(Kendaraan $kendaraan, string $mulai, ?string $selesai = null, string $keadaan = 'create'): Pesanan
    {
        $pesanan = Pesanan::factory()->for($kendaraan, 'kendaraan');

        return match ($keadaan) {
            'selesai' => $pesanan->completed()->create(['tanggal_trip' => $mulai, 'tanggal_selesai' => $selesai]),
            'batal' => $pesanan->dibatalkan()->create(['tanggal_trip' => $mulai, 'tanggal_selesai' => $selesai]),
            default => $pesanan->create(['tanggal_trip' => $mulai, 'tanggal_selesai' => $selesai]),
        };
    }

    /**
     * @return array<string, array{tingkat: string}>
     */
    private function pemeriksaan(array $ringkasan): array
    {
        return collect($ringkasan['kesiapan']['pemeriksaan'])->keyBy('label')->all();
    }

    public function test_statistik_menghitung_trip_selesai_mendatang_dan_nilai_tanpa_pesanan_dibatalkan(): void
    {
        $kendaraan = Kendaraan::factory()->create();
        Pesanan::factory()->for($kendaraan, 'kendaraan')->completed()->create(['tanggal_trip' => today()->subDays(20), 'harga' => 2_000_000]);
        Pesanan::factory()->for($kendaraan, 'kendaraan')->completed()->create(['tanggal_trip' => today()->subDays(40), 'harga' => 3_000_000]);
        Pesanan::factory()->for($kendaraan, 'kendaraan')->payment()->create(['tanggal_trip' => today()->addDays(5)]);
        Pesanan::factory()->for($kendaraan, 'kendaraan')->dibatalkan()->create(['tanggal_trip' => today()->addDays(9), 'harga' => 9_000_000]);
        Pesanan::factory()->completed()->create(['tanggal_trip' => today()->subDays(3), 'harga' => 1_000_000]);

        $statistik = $this->ringkas($kendaraan)['statistik'];

        $this->assertSame(2, $statistik['total_trip']);
        $this->assertSame(1, $statistik['trip_mendatang']);
        $this->assertSame(5_000_000, $statistik['nilai_trip']);
    }

    public function test_keterpakaian_menghitung_hari_unik_dalam_30_hari_terakhir(): void
    {
        $kendaraan = Kendaraan::factory()->create();
        $this->pesananUntuk($kendaraan, today()->subDays(2)->toDateString(), today()->toDateString(), 'selesai');
        $this->pesananUntuk($kendaraan, today()->subDays(1)->toDateString(), today()->toDateString(), 'selesai');
        $this->pesananUntuk($kendaraan, today()->subDays(60)->toDateString(), today()->subDays(58)->toDateString(), 'selesai');
        $this->pesananUntuk($kendaraan, today()->subDays(10)->toDateString(), today()->subDays(8)->toDateString(), 'batal');

        $this->assertSame(10, $this->ringkas($kendaraan)['statistik']['keterpakaian']);
    }

    public function test_grafik_mengelompokkan_trip_per_minggu_dan_mengabaikan_yang_dibatalkan(): void
    {
        $kendaraan = Kendaraan::factory()->create();
        $this->pesananUntuk($kendaraan, today()->toDateString());
        $this->pesananUntuk($kendaraan, today()->toDateString());
        $this->pesananUntuk($kendaraan, today()->subWeeks(2)->toDateString(), null, 'selesai');
        $this->pesananUntuk($kendaraan, today()->subWeeks(2)->toDateString(), null, 'batal');
        $this->pesananUntuk($kendaraan, today()->subWeeks(20)->toDateString(), null, 'selesai');

        $jumlah = array_column($this->ringkas($kendaraan)['grafik'], 'jumlah');

        $this->assertCount(RingkasanKendaraanLayanan::MINGGU_GRAFIK, $jumlah);
        $this->assertSame([0, 0, 0, 0, 0, 1, 0, 2], $jumlah);
    }

    public function test_skor_kesiapan_penuh_bila_semua_pemeriksaan_lolos(): void
    {
        $kesiapan = $this->ringkas($this->kendaraanPrima())['kesiapan'];

        $this->assertSame(100, $kesiapan['skor']);
        $this->assertSame('Baik', $kesiapan['label']);
        $this->assertSame(['ok'], array_values(array_unique(array_column($kesiapan['pemeriksaan'], 'tingkat'))));
    }

    public function test_stnk_kedaluwarsa_menurunkan_skor_dan_ditandai_bahaya(): void
    {
        $kendaraan = $this->kendaraanPrima();
        $kendaraan->update(['stnk_berlaku_sampai' => today()->subDays(3)]);

        $ringkasan = $this->ringkas($kendaraan);

        $this->assertSame(80, $ringkasan['kesiapan']['skor']);
        $this->assertSame('bahaya', $this->pemeriksaan($ringkasan)['STNK']['tingkat']);
        $this->assertStringContainsString('kedaluwarsa 3 hari lalu', $ringkasan['dokumen']['stnk']['keterangan']);
    }

    public function test_pajak_segera_berakhir_dan_dokumen_kosong_ditandai_perhatian(): void
    {
        $kendaraan = $this->kendaraanPrima();
        $kendaraan->update(['pajak_berlaku_sampai' => today()->addDays(20), 'stnk_berlaku_sampai' => null]);

        $ringkasan = $this->ringkas($kendaraan);

        $this->assertSame(80, $ringkasan['kesiapan']['skor']);
        $this->assertSame('perhatian', $this->pemeriksaan($ringkasan)['Pajak kendaraan']['tingkat']);
        $this->assertSame('perhatian', $this->pemeriksaan($ringkasan)['STNK']['tingkat']);
        $this->assertStringContainsString('belum diisi', $ringkasan['dokumen']['stnk']['keterangan']);
    }

    public function test_servis_lewat_tanggal_atau_kilometer_ditandai_terlambat(): void
    {
        $lewatTanggal = $this->kendaraanPrima();
        ServisKendaraan::factory()->terlambat()->for($lewatTanggal)->create();
        $lewatKilometer = $this->kendaraanPrima();
        ServisKendaraan::factory()->for($lewatKilometer)->create(['jatuh_tempo_tanggal' => null, 'jatuh_tempo_odometer' => 39_000]);
        $belumLewat = $this->kendaraanPrima();
        ServisKendaraan::factory()->for($belumLewat)->create(['jatuh_tempo_tanggal' => null, 'jatuh_tempo_odometer' => 41_000]);

        $this->assertSame('bahaya', $this->pemeriksaan($this->ringkas($lewatTanggal))['Servis berkala']['tingkat']);
        $this->assertSame('bahaya', $this->pemeriksaan($this->ringkas($lewatKilometer))['Servis berkala']['tingkat']);
        $this->assertSame('ok', $this->pemeriksaan($this->ringkas($belumLewat))['Servis berkala']['tingkat']);
        $this->assertSame(80, $this->ringkas($lewatTanggal)['kesiapan']['skor']);
    }

    public function test_tanpa_catatan_servis_sama_sekali_ditandai_perhatian(): void
    {
        $kendaraan = Kendaraan::factory()->terawat()->create();

        $ringkasan = $this->ringkas($kendaraan);

        $this->assertSame('perhatian', $this->pemeriksaan($ringkasan)['Servis berkala']['tingkat']);
        $this->assertSame(90, $ringkasan['kesiapan']['skor']);
    }

    public function test_status_servis_dan_nonaktif_menurunkan_skor_dengan_label_sesuai(): void
    {
        $servis = $this->kendaraanPrima();
        $servis->update(['status' => 'servis']);
        $nonaktif = $this->kendaraanPrima();
        $nonaktif->update(['status' => 'nonaktif']);

        $this->assertSame(85, $this->ringkas($servis)['kesiapan']['skor']);
        $this->assertSame('Baik', $this->ringkas($servis)['kesiapan']['label']);
        $this->assertSame(70, $this->ringkas($nonaktif)['kesiapan']['skor']);
        $this->assertSame('Perlu perhatian', $this->ringkas($nonaktif)['kesiapan']['label']);
    }

    public function test_skor_di_bawah_enam_puluh_berlabel_perlu_tindakan(): void
    {
        $kendaraan = Kendaraan::factory()->nonaktif()->create();

        $kesiapan = $this->ringkas($kendaraan)['kesiapan'];

        $this->assertSame(40, $kesiapan['skor']);
        $this->assertSame('Perlu tindakan', $kesiapan['label']);
        $this->assertSame('bahaya', $kesiapan['tingkat']);
    }

    public function test_pesanan_mendatang_yang_tumpang_tindih_dilaporkan_sebagai_bentrok(): void
    {
        $kendaraan = $this->kendaraanPrima();
        $pertama = $this->pesananUntuk($kendaraan, today()->addDays(5)->toDateString(), today()->addDays(7)->toDateString());
        $kedua = $this->pesananUntuk($kendaraan, today()->addDays(7)->toDateString(), today()->addDays(8)->toDateString());
        $this->pesananUntuk($kendaraan, today()->addDays(20)->toDateString());

        $ringkasan = $this->ringkas($kendaraan);

        $this->assertSame([['kode' => $pertama->kode, 'lawan' => $kedua->kode]], $ringkasan['bentrok']);
        $this->assertSame(90, $ringkasan['kesiapan']['skor']);
    }

    public function test_trip_yang_sedang_berjalan_dihitung_aktif_dan_ikut_diperiksa_bentroknya(): void
    {
        $kendaraan = $this->kendaraanPrima();
        $berjalan = $this->pesananUntuk($kendaraan, today()->subDay()->toDateString(), today()->addDays(2)->toDateString());
        $berikutnya = $this->pesananUntuk($kendaraan, today()->addDay()->toDateString());

        $ringkasan = $this->ringkas($kendaraan);

        $this->assertSame(2, $ringkasan['statistik']['trip_mendatang']);
        $this->assertSame([['kode' => $berjalan->kode, 'lawan' => $berikutnya->kode]], $ringkasan['bentrok']);
    }

    public function test_penugasan_dengan_kapasitas_kurang_atau_kendaraan_tidak_siap_menurunkan_skor(): void
    {
        $kendaraan = $this->kendaraanPrima();
        $kendaraan->update(['kapasitas' => 4]);
        $pesanan = Pesanan::factory()->for($kendaraan, 'kendaraan')->create(['tanggal_trip' => today()->addDays(5), 'jumlah_wisatawan' => 6]);

        $ringkasan = $this->ringkas($kendaraan);

        $this->assertSame([['kode' => $pesanan->kode, 'alasan' => 'kapasitas 4 kursi kurang dari 7 kursi (6 peserta + guide yang merangkap driver)']], $ringkasan['penugasanBermasalah']);
        $this->assertSame('bahaya', $this->pemeriksaan($ringkasan)['Jadwal penugasan']['tingkat']);
        $this->assertSame(90, $ringkasan['kesiapan']['skor']);

        $kendaraan->update(['kapasitas' => 7, 'status' => 'nonaktif']);
        $this->assertStringContainsString('status nonaktif', $this->ringkas($kendaraan)['penugasanBermasalah'][0]['alasan']);
    }

    public function test_pesanan_berurutan_tanpa_tumpang_tindih_tidak_dianggap_bentrok(): void
    {
        $kendaraan = $this->kendaraanPrima();
        $this->pesananUntuk($kendaraan, today()->addDays(5)->toDateString(), today()->addDays(6)->toDateString());
        $this->pesananUntuk($kendaraan, today()->addDays(7)->toDateString());

        $this->assertSame([], $this->ringkas($kendaraan)['bentrok']);
    }
}
