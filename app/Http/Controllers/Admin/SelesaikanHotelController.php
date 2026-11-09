<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Layanan\AlurReservasiHotel;
use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Halaman "Lihat semua" dari kartu Selesaikan Hotel di dashboard: semua reservasi hotel yang belum tuntas,
 * dikelompokkan supaya admin langsung tahu mana yang belum diurus sama sekali dan mana yang tinggal beberapa tahap.
 */
class SelesaikanHotelController extends Controller
{
    /** Kelompok yang bisa dipilih di tab, berurutan seperti tampil. */
    public const KELOMPOK = [
        'semua' => 'Semua',
        'belum' => 'Belum diurus',
        'sedang' => 'Sedang diurus',
        'dibatalkan' => 'Dibatalkan hotel',
        'mendesak' => 'Mendesak (≤ 7 hari)',
    ];

    public function __invoke(Request $request, AlurReservasiHotel $alur): View
    {
        $filter = $request->validate([
            'kelompok' => ['nullable', Rule::in(array_keys(self::KELOMPOK))],
            'cari' => ['nullable', 'string', 'max:100'],
        ]);
        $kelompokAktif = $filter['kelompok'] ?? 'semua';

        $semua = $alur->antrean()
            ->reject(fn (Pesanan $pesanan): bool => $alur->sudahDiurus($pesanan))
            ->map(fn (Pesanan $pesanan): array => $this->baris($pesanan, $alur))
            ->values();

        $jumlah = collect(self::KELOMPOK)->map(fn (string $label, string $kunci): int => $semua->filter(fn (array $baris): bool => $this->masukKelompok($baris, $kunci))->count());

        $cari = Str::lower(trim($filter['cari'] ?? ''));
        $daftar = $semua
            ->filter(fn (array $baris): bool => $this->masukKelompok($baris, $kelompokAktif))
            ->when($cari !== '', fn ($koleksi) => $koleksi->filter(fn (array $baris): bool => Str::contains(
                Str::lower(implode(' ', [$baris['pesanan']->kode, $baris['pesanan']->nama_lengkap, $baris['pesanan']->nama_perjalanan, $baris['pesanan']->hotel?->nama])),
                $cari,
            )))
            ->values();

        return view('admin.selesaikan-hotel.indeks', [
            'daftar' => $daftar,
            'jumlah' => $jumlah,
            'kelompokAktif' => $kelompokAktif,
        ]);
    }

    /**
     * @return array{pesanan: Pesanan, ringkasan: array{selesai: int, total: int}, berikutnya: array<string, mixed>|null, kelompok: string, mendesak: bool}
     */
    private function baris(Pesanan $pesanan, AlurReservasiHotel $alur): array
    {
        $ringkasan = $alur->ringkasan($pesanan);

        return [
            'pesanan' => $pesanan,
            'ringkasan' => $ringkasan,
            'berikutnya' => $alur->langkahBerikutnya($pesanan),
            'kelompok' => match (true) {
                $alur->dibatalkan($pesanan) => 'dibatalkan',
                ! $alur->adaReservasi($pesanan) || $ringkasan['selesai'] === 0 => 'belum',
                default => 'sedang',
            },
            'mendesak' => $pesanan->sisaHari() <= 7,
        ];
    }

    /** @param  array{kelompok: string, mendesak: bool}  $baris */
    private function masukKelompok(array $baris, string $kelompok): bool
    {
        return match ($kelompok) {
            'semua' => true,
            'mendesak' => $baris['mendesak'],
            default => $baris['kelompok'] === $kelompok,
        };
    }
}
