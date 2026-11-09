<?php

namespace App\Layanan;

/**
 * Harga all-inclusive per booking = biaya pokok (mobil, BBM, guide, tol, hotel, tiket, biaya grup) + margin ±10%
 * (maks. Rp500.000 di atas biaya pokok), dibulatkan per Rp50.000.
 */
class HargaPerjalananLayanan
{
    /**
     * Perhitungan dasar dengan tarif penginapan standar dari config.
     *
     * @param  int  $tiketPerOrang  total tiket masuk semua objek wisata dalam itinerary untuk satu orang (Rp)
     * @param  bool  $denganPenginapan  hitung penginapan tamu (all-inclusive); false untuk perhitungan layanan saja
     * @return array{sewa_mobil: int, sewa_guide: int, tol_parkir: int, makan_inap_guide: int, bbm: int, penginapan_tamu: int, tiket_masuk: int, biaya_grup: int, biaya_pokok: int, margin: int, harga: int}
     */
    public function hitung(int $durasiHari, int $perkiraanKilometer, int $tiketPerOrang = 0, bool $denganPenginapan = false): array
    {
        $biaya = config('mistborne.biaya_perjalanan');
        $malam = max(0, $durasiHari - 1);
        $kamar = $this->jumlahKamar();
        $penginapan = $denganPenginapan ? array_fill(0, $malam, $kamar * (int) $biaya['penginapan_per_kamar_per_malam']) : [];

        return $this->susun($durasiHari, $perkiraanKilometer, $penginapan, $tiketPerOrang, 0);
    }

    /**
     * Perhitungan dari lembar biaya resources/data/biaya-perjalanan.php (hotel mitra, tiket resmi, biaya grup).
     *
     * @param  array{km: int, penginapan: list<array{0: string, 1: int}>, tiket: list<array{0: string, 1: int}>, grup?: list<array{0: string, 1: int}>}  $rincian
     * @return array{sewa_mobil: int, sewa_guide: int, tol_parkir: int, makan_inap_guide: int, bbm: int, penginapan_tamu: int, tiket_masuk: int, biaya_grup: int, biaya_pokok: int, margin: int, harga: int}
     */
    public function hitungRincian(int $durasiHari, array $rincian): array
    {
        $kamar = $this->jumlahKamar();
        $penginapan = array_map(fn (array $malam): int => $kamar * (int) $malam[1], $rincian['penginapan']);
        $tiketPerOrang = array_sum(array_map(fn (array $tiket): int => (int) $tiket[1], $rincian['tiket']));
        $biayaGrup = array_sum(array_map(fn (array $grup): int => (int) $grup[1], $rincian['grup'] ?? []));

        return $this->susun($durasiHari, (int) $rincian['km'], $penginapan, $tiketPerOrang, $biayaGrup);
    }

    /**
     * Harga untuk journey/paket bernama $nama, atau null bila belum ada di lembar biaya.
     *
     * @return array{sewa_mobil: int, sewa_guide: int, tol_parkir: int, makan_inap_guide: int, bbm: int, penginapan_tamu: int, tiket_masuk: int, biaya_grup: int, biaya_pokok: int, margin: int, harga: int}|null
     */
    public function untuk(string $nama, int $durasiHari): ?array
    {
        $rincian = self::lembarBiaya()[$nama] ?? null;

        return $rincian ? $this->hitungRincian($durasiHari, $rincian) : null;
    }

    /**
     * @return array<string, array{km: int, penginapan: list<array{0: string, 1: int}>, tiket: list<array{0: string, 1: int}>, grup?: list<array{0: string, 1: int}>}>
     */
    public static function lembarBiaya(): array
    {
        return require resource_path('data/biaya-perjalanan.php');
    }

    /**
     * @param  list<int>  $penginapanPerMalam
     * @return array{sewa_mobil: int, sewa_guide: int, tol_parkir: int, makan_inap_guide: int, bbm: int, penginapan_tamu: int, tiket_masuk: int, biaya_grup: int, biaya_pokok: int, margin: int, harga: int}
     */
    private function susun(int $durasiHari, int $kilometer, array $penginapanPerMalam, int $tiketPerOrang, int $biayaGrup): array
    {
        $biaya = config('mistborne.biaya_perjalanan');
        $malam = max(0, $durasiHari - 1);
        $tamu = max(1, (int) $biaya['tamu_standar']);

        $sewaMobil = $durasiHari * (int) $biaya['sewa_mobil_per_hari'];
        $sewaGuide = $durasiHari * (int) $biaya['sewa_guide_per_hari'];
        $tolParkir = $durasiHari * (int) $biaya['tol_parkir_per_hari'];
        $makanInapGuide = $malam * (int) $biaya['makan_inap_guide_per_malam'];
        $bbm = $kilometer * (int) $biaya['bbm_per_km'];
        $penginapanTamu = array_sum($penginapanPerMalam);
        $tiketMasuk = $tiketPerOrang * $tamu;
        $biayaPokok = $sewaMobil + $sewaGuide + $tolParkir + $makanInapGuide + $bbm + $penginapanTamu + $tiketMasuk + $biayaGrup;

        return [
            'sewa_mobil' => $sewaMobil,
            'sewa_guide' => $sewaGuide,
            'tol_parkir' => $tolParkir,
            'makan_inap_guide' => $makanInapGuide,
            'bbm' => $bbm,
            'penginapan_tamu' => $penginapanTamu,
            'tiket_masuk' => $tiketMasuk,
            'biaya_grup' => $biayaGrup,
            'biaya_pokok' => $biayaPokok,
            'margin' => ($harga = $this->hargaJual($biayaPokok)) - $biayaPokok,
            'harga' => $harga,
        ];
    }

    /** Biaya pokok + keuntungan ±10%, dibulatkan ke Rp50.000 terdekat, dan selalu kurang dari biaya pokok + Rp500.000. */
    private function hargaJual(int $biayaPokok): int
    {
        $biaya = config('mistborne.biaya_perjalanan');
        $pembulatan = max(1, (int) $biaya['pembulatan']);
        $denganMargin = (int) round($biayaPokok * (100 + (int) $biaya['margin_persen']) / 100 / $pembulatan) * $pembulatan;
        $batasAtas = (int) floor(($biayaPokok + (int) $biaya['margin_maks']) / $pembulatan) * $pembulatan;

        return max($biayaPokok, min($denganMargin, $batasAtas));
    }

    private function jumlahKamar(): int
    {
        $biaya = config('mistborne.biaya_perjalanan');

        return (int) ceil(max(1, (int) $biaya['tamu_standar']) / max(1, (int) $biaya['tamu_per_kamar']));
    }
}
