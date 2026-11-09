<?php

namespace App\Bantuan;

use App\Models\Kendaraan;
use Illuminate\Validation\Rule;

/**
 * Pilihan armada yang bisa dipilih tamu di formulir booking dan formulir permintaan. Daftarnya dibaca langsung dari
 * data Kendaraan di admin panel (hanya unit berstatus siap), jadi tamu dan admin selalu melihat armada yang sama.
 * Nilai tersimpan di pesanan berbentuk "Kategori — Nama unit" agar terbaca apa adanya di invoice.
 */
class PilihanKendaraan
{
    /**
     * @return array<string, list<array{nilai: string, nama: string, maks_tamu: int}>> kategori => unit
     */
    public static function daftar(): array
    {
        $kursiStaf = (int) config('mistborne.kursi_staf', 1);
        $hasil = [];

        Kendaraan::query()->siap()->orderBy('nama')->orderBy('tahun')->get(['nama', 'jenis', 'tahun', 'kapasitas'])
            ->groupBy(fn (Kendaraan $unit): string => $unit->namaUntukTamu())
            ->each(function ($kelompok, string $nama) use (&$hasil, $kursiStaf): void {
                $kategori = $kelompok->first()->kategoriTamu();
                $hasil[$kategori][] = [
                    'nilai' => self::nilai($kategori, $nama),
                    'nama' => $nama,
                    'maks_tamu' => max(1, (int) $kelompok->max('kapasitas') - $kursiStaf),
                ];
            });

        return $hasil;
    }

    /** @return list<string> semua nilai yang sah */
    public static function nilaiSah(): array
    {
        return collect(self::daftar())->flatten(1)->pluck('nilai')->all();
    }

    /** Batas tamu unit terpilih, atau null bila tamu tidak memilih / nilai tak dikenal. */
    public static function maksTamu(?string $nilai): ?int
    {
        if (blank($nilai)) {
            return null;
        }

        $unit = collect(self::daftar())->flatten(1)->firstWhere('nilai', $nilai);

        return $unit['maks_tamu'] ?? null;
    }

    /**
     * Pilihan untuk dropdown admin (nilai => label). Nilai lama yang unitnya sudah tidak ada tetap muncul agar pesanan lama bisa disimpan ulang.
     *
     * @return array<string, string>
     */
    public static function pilihanAdmin(?string $nilaiSaatIni = null): array
    {
        $pilihan = collect(self::daftar())->flatten(1)->mapWithKeys(fn (array $unit): array => [$unit['nilai'] => self::labelUnit($unit)])->all();

        if (filled($nilaiSaatIni) && ! isset($pilihan[$nilaiSaatIni])) {
            $pilihan[$nilaiSaatIni] = $nilaiSaatIni.' (tidak lagi tersedia)';
        }

        return $pilihan;
    }

    /** Label singkat satu unit untuk tamu, mis. "Toyota Fortuner 2020 · maks. 6 tamu +1 guide". */
    public static function labelUnit(array $unit): string
    {
        return $unit['nama'].' · maks. '.$unit['maks_tamu'].' tamu +'.(int) config('mistborne.kursi_staf', 1).' guide';
    }

    /**
     * @param  string|null  $nilaiLama  nilai yang sudah tersimpan di pesanan; tetap sah walau unitnya sudah dihapus dari armada
     * @return array<string, array<int, mixed>>
     */
    public static function aturan(string $kolom = 'kendaraan_pilihan', bool $wajib = false, ?string $nilaiLama = null): array
    {
        $sah = self::nilaiSah();

        if (filled($nilaiLama)) {
            $sah[] = $nilaiLama;
        }

        return [$kolom => [$wajib ? 'required' : 'nullable', 'string', 'max:150', Rule::in($sah)]];
    }

    /**
     * @return array<string, string>
     */
    public static function pesan(string $kolom = 'kendaraan_pilihan'): array
    {
        return [
            "{$kolom}.required" => __('Pilih kendaraan terlebih dahulu.'),
            "{$kolom}.in" => __('Kendaraan yang dipilih tidak tersedia. Pilih dari daftar.'),
        ];
    }

    public static function nilai(string $kategori, string $nama): string
    {
        return $kategori.' — '.$nama;
    }
}
