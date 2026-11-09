<?php

namespace App\Layanan;

use App\Enums\BookingStatus;
use App\Enums\StatusPesananKustom;
use App\Models\Perjalanan;
use App\Models\Pesanan;
use App\Models\PesananKustom;
use App\Models\Ulasan;
use Illuminate\Support\Collection;

/**
 * Angka widget Dashboard dari data nyata. Tren mingguan dihitung dari created_at terhadap status saat ini
 * (tidak ada riwayat waktu perubahan status).
 */
class StatistikDashboardLayanan
{
    public function __construct(private readonly KapasitasTripLayanan $kapasitas) {}

    /**
     * @return array{jumlah: int, tren: string, positif: bool}
     */
    public function pesananBaru(): array
    {
        return $this->kartuMingguan(
            fn ($awal, $akhir) => Pesanan::query()->where('status', BookingStatus::Dihubungi)->whereBetween('created_at', [$awal, $akhir])->count(),
        );
    }

    /**
     * @return array{jumlah: int, tren: string, positif: bool}
     */
    public function sudahPayment(): array
    {
        return $this->kartuMingguan(
            fn ($awal, $akhir) => Pesanan::query()->whereIn('status', [BookingStatus::Payment, BookingStatus::Completed])->whereBetween('created_at', [$awal, $akhir])->count(),
        );
    }

    /**
     * @return array{jumlah: int, tren: string, positif: bool}
     */
    public function pesananKustomBaru(): array
    {
        return $this->kartuMingguan(
            fn ($awal, $akhir) => PesananKustom::query()->where('status', StatusPesananKustom::Baru)->whereBetween('created_at', [$awal, $akhir])->count(),
        );
    }

    /**
     * @return array{jumlah: int, tren: string, positif: bool, sparkline: array<int, int>}
     */
    public function pendapatanBulanIni(): array
    {
        // Pendapatan = uang yang benar-benar sudah diterima (nominal_dibayar) dari pesanan
        // Pembayaran & Selesai; pesanan Dihubungi belum dihitung. Beda dari kuota.
        $statusBayar = [BookingStatus::Payment->value, BookingStatus::Completed->value];
        $bulanIni = (int) Pesanan::query()->whereIn('status', $statusBayar)->whereBetween('tanggal_trip', [now()->startOfMonth(), now()->endOfMonth()])->sum('nominal_dibayar');
        $bulanLalu = (int) Pesanan::query()->whereIn('status', $statusBayar)->whereBetween('tanggal_trip', [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()])->sum('nominal_dibayar');

        return [
            'jumlah' => $bulanIni,
            'sparkline' => $this->sparklineHarian(
                fn ($awal, $akhir) => (int) Pesanan::query()->whereIn('status', $statusBayar)->whereBetween('created_at', [$awal, $akhir])->sum('nominal_dibayar'),
            ),
            ...$this->hitungTren($bulanIni, $bulanLalu),
        ];
    }

    /**
     * @return array{jumlah: int, tren: string, positif: bool, sparkline: array<int, int>}
     */
    private function kartuMingguan(\Closure $hitung): array
    {
        $iniMinggu = $hitung(now()->subDays(7), now());
        $minggLalu = $hitung(now()->subDays(14), now()->subDays(7));

        return [
            'jumlah' => $iniMinggu,
            'sparkline' => $this->sparklineHarian($hitung),
            ...$this->hitungTren($iniMinggu, $minggLalu),
        ];
    }

    /**
     * 7 nilai harian (H-6 s.d. hari ini) dari closure yang sama dipakai kartuMingguan —
     * dipakai utk sparkline kartu statistik dashboard. Data nyata (bukan dekoratif), sejalan
     * dengan metodologi "tren" yang sudah dipakai di kelas ini.
     *
     * @return array<int, int>
     */
    private function sparklineHarian(\Closure $hitung): array
    {
        return collect(range(6, 0))
            ->map(fn (int $i) => $hitung(now()->subDays($i)->startOfDay(), now()->subDays($i)->endOfDay()))
            ->values()
            ->all();
    }

    /**
     * @return array{tren: string, positif: bool}
     */
    private function hitungTren(int $sekarang, int $sebelumnya): array
    {
        if ($sebelumnya === 0) {
            return ['tren' => $sekarang > 0 ? 'Baru' : 'Stabil', 'positif' => true];
        }

        $persen = (int) round((($sekarang - $sebelumnya) / $sebelumnya) * 100);
        $tampil = abs($persen) > 999 ? '999%+' : abs($persen).'%';

        return [
            'tren' => ($persen > 0 ? '+' : ($persen < 0 ? '-' : '')).$tampil,
            'positif' => $persen >= 0,
        ];
    }

    /**
     * @return Collection<int, array{label: string, nilai: int, nilaiKedua: int}>
     */
    public function pesananPerBulan(int $tahun): Collection
    {
        $namaBulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];
        $awalTahun = now()->setDate($tahun, 1, 1)->startOfDay();

        $perBulan = Pesanan::query()
            ->whereBetween('created_at', [$awalTahun, $awalTahun->copy()->endOfYear()])
            ->get(['created_at', 'status'])
            ->groupBy(fn (Pesanan $pesanan): int => $pesanan->created_at->month);

        return collect($namaBulan)->map(fn (string $label, int $indeks): array => [
            'label' => $label,
            'nilai' => $perBulan->get($indeks + 1, collect())->count(),
            'nilaiKedua' => $perBulan->get($indeks + 1, collect())->where('status', BookingStatus::Dibatalkan)->count(),
        ]);
    }

    /**
     * @return Collection<int, array{label: string, nilai: int, warna: string}>
     */
    public function statusBulanIni(): Collection
    {
        $warna = [
            'dihubungi' => '#2563AA',
            'payment' => '#A9790A',
            'hold_schedule' => '#6B3FA0',
            'completed' => '#1F7A3C',
            'dibatalkan' => '#C0314A',
        ];

        return collect(BookingStatus::cases())->map(fn (BookingStatus $status) => [
            'label' => $status->label(),
            'nilai' => Pesanan::query()->where('status', $status)->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            'warna' => $warna[$status->value],
        ])->filter(fn (array $baris) => $baris['nilai'] > 0)->values();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Pesanan>
     */
    public function pesananTerbaru(int $jumlah = 6)
    {
        return Pesanan::query()->with('perjalanan')->latest('created_at')->limit($jumlah)->get();
    }

    /**
     * Rating dari formulir ulasan tamu MISTBORNE sendiri: tetap ada walau Rating Google belum tersambung.
     *
     * @return array{rata_rata: float|null, jumlah: int, sebaran: array<int, int>, menunggu: int, terbaru: Ulasan|null}
     */
    public function ratingTamu(): array
    {
        $perBintang = Ulasan::query()->selectRaw('rating, count(*) as jumlah')->groupBy('rating')->pluck('jumlah', 'rating');
        $jumlah = (int) $perBintang->sum();

        return [
            'rata_rata' => $jumlah > 0 ? round($perBintang->map(fn ($banyak, $bintang): int => (int) $bintang * (int) $banyak)->sum() / $jumlah, 1) : null,
            'jumlah' => $jumlah,
            'sebaran' => collect([5, 4, 3, 2, 1])->mapWithKeys(fn (int $bintang): array => [$bintang => (int) ($perBintang[$bintang] ?? 0)])->all(),
            'menunggu' => Ulasan::query()->where('disetujui', false)->count(),
            'terbaru' => Ulasan::query()->with('pesanan')->latest()->first(),
        ];
    }

    /**
     * @return Collection<int, array{journey: Perjalanan, jumlah: int, persen: float}>
     */
    public function journeyTerlaris(int $jumlah = 4): Collection
    {
        $journey = Perjalanan::query()->where('template_pesanan_kustom', false)->withCount('pesanan')->orderByDesc('pesanan_count')->limit($jumlah)->get();
        $maksimum = max(1, $journey->max('pesanan_count') ?? 1);

        return $journey->map(fn (Perjalanan $item) => [
            'journey' => $item,
            'jumlah' => $item->pesanan_count,
            'persen' => $item->pesanan_count / $maksimum * 100,
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, PesananKustom>
     */
    public function pesananKustomTerbaru(int $jumlah = 3)
    {
        return PesananKustom::query()->latest('created_at')->limit($jumlah)->get();
    }

    /**
     * @return array{batas_bayar_lewat: int, guide_tidak_cukup: int, duplikat: int, pesanan_kustom_dihubungi: int, penawaran_kedaluwarsa: int, penugasan_ditolak: int, penugasan_mendesak: int}
     */
    public function perluDitindaklanjuti(BatasBayarLayanan $batasBayar): array
    {
        $kandidatBatasBayar = Pesanan::query()->where('status', BookingStatus::Dihubungi)->whereNotNull('dikonfirmasi_at')->get();
        $batasBayarLewat = $kandidatBatasBayar->filter(fn (Pesanan $p) => $batasBayar->sudahLewat($p))->count();

        $guideTidakCukup = $this->hitungPesananKelebihanGuide();

        $duplikat = Pesanan::query()
            ->select('whatsapp', 'tanggal_trip')
            ->whereIn('status', [BookingStatus::Dihubungi, BookingStatus::Payment, BookingStatus::HoldSchedule])
            ->groupBy('whatsapp', 'tanggal_trip')
            ->havingRaw('count(*) > 1')
            ->get()
            ->count();

        return [
            'batas_bayar_lewat' => $batasBayarLewat,
            'guide_tidak_cukup' => $guideTidakCukup,
            'duplikat' => $duplikat,
            'penugasan_ditolak' => Pesanan::query()->adaPenolakanPersonel()->count(),
            'butuh_rekomendasi_penginapan' => Pesanan::query()->butuhRekomendasiPenginapan()->count(),
            'hotel_belum_terkonfirmasi' => Pesanan::query()->hotelBelumTerkonfirmasi()->count(),
            'penugasan_mendesak' => Pesanan::query()->penugasanBelumLengkap()->whereDate('tanggal_trip', '<=', today()->addDays(7)->toDateString())->count(),
            'penawaran_kedaluwarsa' => PesananKustom::query()
                ->where('status', StatusPesananKustom::MenungguJawaban)
                ->whereDate('batas_penawaran', '<', today())
                ->count(),
            'pesanan_kustom_dihubungi' => PesananKustom::query()->whereIn('status', [StatusPesananKustom::Baru, StatusPesananKustom::Diproses, StatusPesananKustom::MenungguJawaban])->count(),
        ];
    }

    /** Jumlah pesanan aktif yang harinya memakai lebih banyak trip daripada guide (ada yang pasti tidak kebagian guide). */
    private function hitungPesananKelebihanGuide(): int
    {
        $rentang = $this->kapasitas->rentangKelebihanGuide();

        if ($rentang->isEmpty()) {
            return 0;
        }

        return Pesanan::query()
            ->whereIn('status', [BookingStatus::Dihubungi, BookingStatus::Payment, BookingStatus::HoldSchedule])
            ->whereDate('tanggal_trip', '<=', $rentang->max('akhir')->toDateString())
            ->whereRaw('COALESCE(tanggal_selesai, tanggal_trip) >= ?', [today()->toDateString()])
            ->get()
            ->filter(fn (Pesanan $pesanan): bool => $this->kapasitas->pesananKelebihanGuide($pesanan, $rentang))
            ->count();
    }
}
