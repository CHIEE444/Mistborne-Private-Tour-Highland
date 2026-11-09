<?php

namespace Tests\Unit\Layanan;

use App\Layanan\HargaPerjalananLayanan;
use App\Models\Perjalanan;
use Database\Seeders\PaketWisataSeeder;
use Tests\TestCase;

class HargaPerjalananLayananTest extends TestCase
{
    public function test_harga_dihitung_dari_sewa_mobil_bbm_guide_dan_margin(): void
    {
        $hasil = (new HargaPerjalananLayanan)->hitung(3, 330);

        $this->assertSame(2_400_000, $hasil['sewa_mobil']);
        $this->assertSame(1_050_000, $hasil['sewa_guide']);
        $this->assertSame(429_000, $hasil['bbm']);
        $this->assertSame(400_000, $hasil['makan_inap_guide']);
        $this->assertSame(4_504_000, $hasil['biaya_pokok']);
        // 4.504.000 + 10% = 4.954.400, dibulatkan ke Rp50.000 terdekat.
        $this->assertSame(4_950_000, $hasil['harga']);
    }

    public function test_harga_naik_mengikuti_durasi_dan_jarak(): void
    {
        $layanan = new HargaPerjalananLayanan;

        $this->assertGreaterThan($layanan->hitung(2, 150)['harga'], $layanan->hitung(4, 150)['harga']);
        $this->assertGreaterThan($layanan->hitung(2, 100)['harga'], $layanan->hitung(2, 400)['harga']);
    }

    public function test_harga_all_inclusive_menghitung_tiket_masuk_dan_penginapan_tamu(): void
    {
        $hasil = (new HargaPerjalananLayanan)->hitung(4, 380, 250_000, true);

        // 4 tamu standar: tiket 4 × 250.000, penginapan 1 kamar × 3 malam × 650.000 (hotel bintang 4).
        $this->assertSame(1_000_000, $hasil['tiket_masuk']);
        $this->assertSame(1_950_000, $hasil['penginapan_tamu']);
        $this->assertSame(8_944_000, $hasil['biaya_pokok']);
        // 10% dari 8.944.000 melebihi Rp500.000, jadi keuntungan dibatasi agar harga < biaya pokok + Rp500.000.
        $this->assertSame(9_400_000, $hasil['harga']);
        $this->assertGreaterThan((new HargaPerjalananLayanan)->hitung(4, 380)['harga'], $hasil['harga']);
    }

    public function test_keuntungan_sekitar_sepuluh_persen_dan_selalu_kurang_dari_lima_ratus_ribu(): void
    {
        $layanan = new HargaPerjalananLayanan;

        foreach ([[1, 50], [2, 150], [3, 330], [4, 380], [6, 900]] as [$hari, $km]) {
            $hasil = $layanan->hitung($hari, $km, 150_000, true);

            $this->assertGreaterThan(0, $hasil['margin'], "hari {$hari}");
            $this->assertLessThan(500_000, $hasil['margin'], "hari {$hari}");
            // Sekitar 10%: pembulatan ke Rp50.000 terdekat bisa menggeser sedikit pada trip yang biaya pokoknya kecil.
            $this->assertLessThanOrEqual(0.12, $hasil['margin'] / $hasil['biaya_pokok'], "hari {$hari}");
            $this->assertSame(0, $hasil['harga'] % 50_000, "hari {$hari}");
        }
    }

    public function test_setiap_journey_dan_paket_di_lembar_biaya_untung_wajar_dan_harganya_sinkron(): void
    {
        $layanan = new HargaPerjalananLayanan;
        $durasi = collect(require resource_path('data/journeys.php'))->pluck('durasi_hari', 'name')->all();
        $hargaData = collect(require resource_path('data/journeys.php'))->mapWithKeys(fn ($j) => [$j['name'] => (int) preg_replace('/\D/', '', (string) ($j['price'] ?? ''))])->all();

        foreach (HargaPerjalananLayanan::lembarBiaya() as $nama => $rincian) {
            $hasil = $layanan->untuk($nama, (int) ($durasi[$nama] ?? 2));

            $this->assertNotNull($hasil, $nama);
            $this->assertGreaterThan(0, $hasil['penginapan_tamu'], "{$nama}: hotel mitra belum dihitung");
            $this->assertGreaterThan(0, $hasil['tiket_masuk'], "{$nama}: tiket masuk belum dihitung");
            $this->assertGreaterThan(0, $hasil['margin'], $nama);
            $this->assertLessThan(500_000, $hasil['harga'] - $hasil['biaya_pokok'], "{$nama}: selisih dari biaya pokok harus di bawah Rp500.000");

            if (isset($hargaData[$nama])) {
                $this->assertSame($hasil['harga'], $hargaData[$nama], "{$nama}: harga di resources/data/journeys.php belum sinkron");
            }
        }
    }

    public function test_seeder_menyimpan_harga_hasil_hitungan_dan_memperbarui_harga_seed_lama_tanpa_menimpa_edit_admin(): void
    {
        $layanan = new HargaPerjalananLayanan;

        foreach (Perjalanan::query()->whereIn('nama', array_keys(HargaPerjalananLayanan::lembarBiaya()))->get() as $perjalanan) {
            $this->assertSame($layanan->untuk($perjalanan->nama, (int) $perjalanan->durasi_hari)['harga'], (int) $perjalanan->harga, $perjalanan->nama);
        }

        $rengganis = Perjalanan::query()->where('nama', 'Kawah Rengganis')->firstOrFail();
        $sikunir = Perjalanan::query()->where('nama', 'Bukit Sikunir')->firstOrFail();
        $rengganis->update(['harga' => 4_400_000, 'img' => '/images/kawahrengganis.jpg']);
        $sikunir->update(['harga' => 6_000_000]);

        $this->seed(PaketWisataSeeder::class);

        $this->assertSame(4_450_000, (int) $rengganis->fresh()->harga);
        $this->assertSame('/images/kawah-rengganis.webp', $rengganis->fresh()->img);
        $this->assertSame(6_000_000, (int) $sikunir->fresh()->harga, 'harga hasil edit admin tidak boleh ditimpa');
    }
}
