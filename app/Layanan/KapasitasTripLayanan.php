<?php

namespace App\Layanan;

use App\Enums\BookingStatus;
use App\Models\JadwalKeberangkatan;
use App\Models\Pengaturan;
use App\Models\Perjalanan;
use App\Models\PersonelOperasional;
use App\Models\Pesanan;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Satu sumber slot untuk semua formulir pemesanan. Sisa slot = nilai terkecil dari kuota journey di tanggal itu
 * dan guide siap yang masih bebas di seluruh hari trip (1 guide memegang 1 trip sampai selesai).
 * Trip kustom tidak memakai Tanggal Tersedia/kuota, tetapi tetap dibatasi guide.
 */
class KapasitasTripLayanan
{
    /** Pesanan yang masih memegang guide: trip belum selesai dan belum dibatalkan. */
    private const STATUS_MEMEGANG_GUIDE = [BookingStatus::Dihubungi, BookingStatus::Payment, BookingStatus::HoldSchedule];

    /** @var Collection<int, string>|null disimpan selama satu permintaan (kalender memanggilnya untuk setiap sel tanggal) */
    private ?Collection $simGuideSiap = null;

    /**
     * Tanggal yang bisa dipilih untuk journey ini dalam rentang pemesanan (H+7 sampai setahun sesudahnya) beserta
     * sisa slotnya. Journey biasa: hanya Tanggal Tersedia. Journey trip kustom: setiap hari, hanya dibatasi guide.
     *
     * @return Collection<int, array{tanggal: string, tersedia: bool, sisa: int, status: string, alasan: ?string, label: string}>
     */
    public function daftarTanggal(Perjalanan $perjalanan, ?int $kecualiPesananId = null, ?int $durasiHari = null): Collection
    {
        [$awal, $akhir] = $this->rentangPemesanan();

        if ($perjalanan->template_pesanan_kustom) {
            $tanggal = collect(CarbonPeriod::create($awal, $akhir))->map(fn (CarbonInterface $hari): string => $hari->toDateString());

            return $this->hitung($perjalanan, $tanggal, collect(), $durasiHari, $kecualiPesananId, false);
        }

        $jadwal = $perjalanan->jadwal()
            ->whereDate('tanggal', '>=', $awal->toDateString())
            ->whereDate('tanggal', '<=', $akhir->toDateString())
            ->orderBy('tanggal')
            ->get()
            ->keyBy(fn (JadwalKeberangkatan $item): string => $item->tanggal->toDateString());

        return $this->hitung($perjalanan, $jadwal->keys(), $jadwal, $durasiHari, $kecualiPesananId, true);
    }

    /**
     * Ketersediaan satu tanggal (tanpa mengunci), untuk pemeriksaan formulir.
     *
     * @return array{tanggal: string, tersedia: bool, sisa: int, status: string, alasan: ?string, label: string}
     */
    public function cek(Perjalanan $perjalanan, string $tanggal, ?int $durasiHari = null, ?int $kecualiPesananId = null): array
    {
        $tanggal = Carbon::parse($tanggal)->toDateString();
        $pakaiTanggalTersedia = ! $perjalanan->template_pesanan_kustom;
        $jadwal = $pakaiTanggalTersedia
            ? $perjalanan->jadwal()->whereDate('tanggal', $tanggal)->get()->keyBy(fn (JadwalKeberangkatan $item): string => $item->tanggal->toDateString())
            : collect();

        return $this->hitung($perjalanan, collect([$tanggal]), $jadwal, $durasiHari, $kecualiPesananId, $pakaiTanggalTersedia)->first();
    }

    /**
     * Dipanggil di dalam transaksi tepat sebelum pesanan dibuat atau jadwalnya diganti: mengunci satu baris bersama
     * (Pengaturan) supaya dua admin tidak memakai slot terakhir yang sama, lalu menolak bila tanggal tidak tersedia.
     */
    public function pastikanBisaDipesan(Perjalanan $perjalanan, string $tanggal, ?int $durasiHari = null, ?int $kecualiPesananId = null, string $kolom = 'tanggal_trip'): void
    {
        Pengaturan::query()->whereKey(Pengaturan::current()->id)->lockForUpdate()->first();

        $hasil = $this->cek($perjalanan, $tanggal, $durasiHari, $kecualiPesananId);

        if (! $hasil['tersedia']) {
            throw ValidationException::withMessages([$kolom => $hasil['alasan']]);
        }
    }

    /**
     * Rentang hari yang trip aktifnya melebihi jumlah guide siap (mulai hari ini), untuk peringatan dashboard dan
     * penanda di daftar pesanan. Terjadi bila guide dinonaktifkan, SIM-nya habis, atau ada data lama sebelum aturan ini.
     *
     * @return Collection<int, array{mulai: Carbon, akhir: Carbon, trip: int, guide: int}>
     */
    public function rentangKelebihanGuide(int $jumlahHari = 90): Collection
    {
        $mulai = today();
        $akhir = today()->addDays($jumlahHari);
        $tripPerHari = $this->tripPerHari($mulai->toDateString(), $akhir->toDateString());
        $guide = $this->masaBerlakuSimGuideSiap();
        $rentang = collect();
        $sekarang = null;

        foreach (CarbonPeriod::create($mulai, $akhir) as $hari) {
            $tanggal = $hari->toDateString();
            $trip = $tripPerHari[$tanggal] ?? 0;
            $guideHariItu = $guide->filter(fn (string $berlaku): bool => $berlaku >= $tanggal)->count();

            if ($trip > $guideHariItu) {
                if ($sekarang !== null && $sekarang['akhir']->copy()->addDay()->isSameDay($hari)) {
                    $sekarang['akhir'] = $hari->copy();
                    $sekarang['trip'] = max($sekarang['trip'], $trip);
                    $sekarang['guide'] = min($sekarang['guide'], $guideHariItu);
                } else {
                    if ($sekarang !== null) {
                        $rentang->push($sekarang);
                    }
                    $sekarang = ['mulai' => $hari->copy(), 'akhir' => $hari->copy(), 'trip' => $trip, 'guide' => $guideHariItu];
                }
            }
        }

        if ($sekarang !== null) {
            $rentang->push($sekarang);
        }

        return $rentang;
    }

    /** Pesanan ini memakai hari yang trip aktifnya melebihi jumlah guide (ada yang pasti tidak kebagian guide). */
    public function pesananKelebihanGuide(Pesanan $pesanan, ?Collection $rentangKelebihan = null): bool
    {
        if (! in_array($pesanan->status, self::STATUS_MEMEGANG_GUIDE, true) || $pesanan->tanggal_trip === null) {
            return false;
        }

        $mulai = $pesanan->tanggal_trip->toDateString();
        $akhir = ($pesanan->tanggal_selesai ?? $pesanan->tanggal_trip)->toDateString();

        return ($rentangKelebihan ?? $this->rentangKelebihanGuide())
            ->contains(fn (array $rentang): bool => $rentang['mulai']->toDateString() <= $akhir && $rentang['akhir']->toDateString() >= $mulai);
    }

    /**
     * Jumlah trip yang masih memegang guide per hari (kunci Y-m-d) dalam rentang, termasuk hari lanjutan trip beberapa hari.
     *
     * @return array<string, int>
     */
    public function tripPerHari(string $mulai, string $akhir, ?int $kecualiPesananId = null): array
    {
        $jumlah = [];

        Pesanan::query()
            ->whereIn('status', self::STATUS_MEMEGANG_GUIDE)
            ->whereNull('guide_trip_selesai_at')
            ->when($kecualiPesananId, fn ($query, int $id) => $query->whereKeyNot($id))
            ->whereDate('tanggal_trip', '<=', $akhir)
            ->whereRaw('COALESCE(tanggal_selesai, tanggal_trip) >= ?', [$mulai])
            ->get(['id', 'tanggal_trip', 'tanggal_selesai'])
            ->each(function (Pesanan $pesanan) use (&$jumlah, $mulai, $akhir): void {
                $dari = max($pesanan->tanggal_trip->toDateString(), $mulai);
                $sampai = min(($pesanan->tanggal_selesai ?? $pesanan->tanggal_trip)->toDateString(), $akhir);

                foreach (CarbonPeriod::create($dari, $sampai) as $hari) {
                    $jumlah[$hari->toDateString()] = ($jumlah[$hari->toDateString()] ?? 0) + 1;
                }
            });

        return $jumlah;
    }

    /** Jumlah guide siap yang SIM-nya berlaku sampai tanggal tersebut. */
    public function jumlahGuideSiap(string $sampaiTanggal): int
    {
        return $this->masaBerlakuSimGuideSiap()->filter(fn (string $berlaku): bool => $berlaku >= $sampaiTanggal)->count();
    }

    /**
     * @param  Collection<int, string>  $daftarTanggal
     * @param  Collection<string, JadwalKeberangkatan>  $jadwalPerTanggal
     * @return Collection<int, array{tanggal: string, tersedia: bool, sisa: int, status: string, alasan: ?string, label: string}>
     */
    private function hitung(Perjalanan $perjalanan, Collection $daftarTanggal, Collection $jadwalPerTanggal, ?int $durasiHari, ?int $kecualiPesananId, bool $pakaiTanggalTersedia): Collection
    {
        if ($daftarTanggal->isEmpty()) {
            return collect();
        }

        $durasi = max(1, (int) ($durasiHari ?? $perjalanan->durasi_hari ?? 1));
        $awal = $daftarTanggal->min();
        $akhirRentang = Carbon::parse($daftarTanggal->max())->addDays($durasi - 1)->toDateString();
        $tripPerHari = $this->tripPerHari($awal, $akhirRentang, $kecualiPesananId);
        $guide = $this->masaBerlakuSimGuideSiap();
        $terisiJourney = $pakaiTanggalTersedia
            ? Pesanan::query()
                ->terhitungKuota()
                ->where('perjalanan_id', $perjalanan->id)
                ->when($kecualiPesananId, fn ($query, int $id) => $query->whereKeyNot($id))
                ->where('tanggal_trip', '>=', $awal)
                ->where('tanggal_trip', '<', Carbon::parse($daftarTanggal->max())->addDay()->toDateString())
                ->selectRaw('DATE(tanggal_trip) as tgl, count(*) as jumlah')
                ->groupBy('tgl')
                ->pluck('jumlah', 'tgl')
            : collect();
        $kuotaBawaan = Pengaturan::current()->kuota_default;
        [$batasAwal, $batasAkhir] = array_map(fn (Carbon $tanggal): string => $tanggal->toDateString(), $this->rentangPemesanan());

        return $daftarTanggal->map(function (string $tanggal) use ($durasi, $jadwalPerTanggal, $tripPerHari, $guide, $terisiJourney, $kuotaBawaan, $batasAwal, $batasAkhir, $pakaiTanggalTersedia): array {
            $akhir = Carbon::parse($tanggal)->addDays($durasi - 1)->toDateString();
            $jadwal = $jadwalPerTanggal->get($tanggal);
            $tersibuk = collect(CarbonPeriod::create($tanggal, $akhir))->map(fn (CarbonInterface $hari): int => $tripPerHari[$hari->toDateString()] ?? 0)->max();
            $guideBebas = max(0, $guide->filter(fn (string $berlaku): bool => $berlaku >= $akhir)->count() - $tersibuk);
            $sisaKuota = $pakaiTanggalTersedia ? max(0, ($jadwal?->kuota ?? $kuotaBawaan) - (int) ($terisiJourney[$tanggal] ?? 0)) : PHP_INT_MAX;

            [$status, $alasan] = match (true) {
                $tanggal < $batasAwal => ['di_luar_batas', 'Tanggal trip harus minimal tujuh hari dari hari ini (H-7).'],
                $tanggal > $batasAkhir => ['di_luar_batas', 'Tanggal trip hanya bisa dipilih sampai satu tahun ke depan.'],
                $pakaiTanggalTersedia && $jadwal === null => ['belum_dibuka', 'Tanggal ini belum dibuka di Tanggal Tersedia journey ini. Pilih tanggal yang tersedia.'],
                (bool) $jadwal?->diblok => ['ditutup', 'Tanggal ini ditutup. Pilih tanggal lain.'],
                $sisaKuota === 0 => ['penuh', 'Kuota journey di tanggal ini sudah penuh. Pilih tanggal lain.'],
                $guideBebas === 0 => ['guide_penuh', 'Semua guide sudah bertugas di tanggal itu. Pilih tanggal lain.'],
                default => ['tersedia', null],
            };
            $sisa = $status === 'tersedia' ? min($sisaKuota, $guideBebas) : 0;

            return [
                'tanggal' => $tanggal,
                'tersedia' => $status === 'tersedia',
                'sisa' => $sisa,
                'status' => $status,
                'alasan' => $alasan,
                'label' => match ($status) {
                    'tersedia' => "Sisa {$sisa} slot",
                    'penuh', 'guide_penuh' => 'Penuh',
                    'ditutup' => 'Ditutup',
                    default => 'Tidak tersedia',
                },
            ];
        })->values();
    }

    /** @return array{0: Carbon, 1: Carbon} batas paling cepat (H+7) dan paling lambat (setahun sesudahnya) */
    private function rentangPemesanan(): array
    {
        $awal = today()->addDays(7);

        return [$awal, $awal->copy()->addYear()];
    }

    /** @return Collection<int, string> masa berlaku SIM (Y-m-d) tiap guide siap; guide tanpa SIM tidak dihitung */
    private function masaBerlakuSimGuideSiap(): Collection
    {
        return $this->simGuideSiap ??= PersonelOperasional::query()
            ->where('peran', PersonelOperasional::PERAN)
            ->where('aktif', true)
            ->where('status_operasional', 'siap')
            ->whereNotNull('masa_berlaku_sim')
            ->pluck('masa_berlaku_sim')
            ->map(fn ($tanggal): string => Carbon::parse($tanggal)->toDateString())
            ->values();
    }
}
