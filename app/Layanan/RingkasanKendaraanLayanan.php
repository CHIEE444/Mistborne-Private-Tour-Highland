<?php

namespace App\Layanan;

use App\Enums\BookingStatus;
use App\Models\Kendaraan;
use App\Models\Perjalanan;
use App\Models\Pesanan;
use App\Models\ServisKendaraan;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Statistik halaman detail kendaraan dari data nyata (pesanan, servis, dokumen). Skor kesiapan = jumlah bobot pemeriksaan.
 * Keterpakaian = hari kalender yang dipakai trip dalam 30 hari terakhir.
 */
class RingkasanKendaraanLayanan
{
    public function __construct(private readonly KetersediaanKendaraanLayanan $ketersediaan = new KetersediaanKendaraanLayanan) {}

    /** Jumlah minggu pada grafik trip. */
    public const MINGGU_GRAFIK = 8;

    /** Jumlah trip pada tabel kinerja. */
    public const BARIS_TRIP = 8;

    /** Dokumen dianggap segera berakhir bila sisa harinya sebanyak ini atau kurang. */
    public const BATAS_DOKUMEN_SEGERA_HARI = 30;

    /** Bobot tiap pemeriksaan kesiapan; jumlahnya 100. */
    private const BOBOT = ['status' => 30, 'stnk' => 20, 'pajak' => 20, 'servis' => 20, 'jadwal' => 10];

    /**
     * @return array{
     *     statistik: array{total_trip: int, trip_mendatang: int, keterpakaian: int, nilai_trip: int},
     *     grafik: list<array{label: string, jumlah: int}>,
     *     perjalanan: Collection<int, Pesanan>,
     *     bentrok: list<array{kode: string, lawan: string}>,
     *     penugasanBermasalah: list<array{kode: string, alasan: string}>,
     *     destinasi: Collection<int, Perjalanan>,
     *     dokumen: array{stnk: array{tingkat: string, keterangan: string}, pajak: array{tingkat: string, keterangan: string}},
     *     servisTerjadwal: Collection<int, ServisKendaraan>,
     *     riwayatServis: Collection<int, ServisKendaraan>,
     *     kesiapan: array{skor: int, label: string, tingkat: string, pemeriksaan: list<array{label: string, keterangan: string, tingkat: string}>}
     * }
     */
    public function ringkas(Kendaraan $kendaraan): array
    {
        $servisTerjadwal = $kendaraan->servis()->terjadwal()->orderByRaw('jatuh_tempo_tanggal asc nulls last')->orderBy('id')->get();
        $riwayatServis = $kendaraan->servis()->selesai()->orderByDesc('tanggal_servis')->orderByDesc('id')->limit(10)->get();
        $bentrok = $this->bentrok($kendaraan);
        $penugasanBermasalah = $this->ketersediaan->penugasanBermasalah($kendaraan)
            ->map(fn (array $butir): array => ['kode' => (string) $butir['pesanan']->kode, 'alasan' => $butir['alasan']])
            ->all();
        $dokumen = [
            'stnk' => $this->statusDokumen($kendaraan->stnk_berlaku_sampai, 'STNK'),
            'pajak' => $this->statusDokumen($kendaraan->pajak_berlaku_sampai, 'Pajak'),
        ];

        return [
            'statistik' => $this->statistik($kendaraan),
            'grafik' => $this->grafikMingguan($kendaraan),
            'perjalanan' => $kendaraan->pesanan()->orderByDesc('tanggal_trip')->orderByDesc('id')->limit(self::BARIS_TRIP)->get(),
            'bentrok' => $bentrok,
            'penugasanBermasalah' => $penugasanBermasalah,
            'destinasi' => $kendaraan->perjalanan()->orderBy('nama')->get(['id', 'nama', 'jenis', 'alasan_kendaraan']),
            'dokumen' => $dokumen,
            'servisTerjadwal' => $servisTerjadwal,
            'riwayatServis' => $riwayatServis,
            'kesiapan' => $this->kesiapan($kendaraan, $dokumen, $servisTerjadwal, $riwayatServis->isNotEmpty(), $bentrok, $penugasanBermasalah),
        ];
    }

    /**
     * @return array{total_trip: int, trip_mendatang: int, keterpakaian: int, nilai_trip: int}
     */
    private function statistik(Kendaraan $kendaraan): array
    {
        $selesai = $kendaraan->pesanan()->where('status', BookingStatus::Completed);

        return [
            'total_trip' => (clone $selesai)->count(),
            'trip_mendatang' => $kendaraan->pesanan()->menahanKendaraan()->count(),
            'keterpakaian' => $this->persenKeterpakaian($kendaraan),
            'nilai_trip' => (int) (clone $selesai)->sum('harga'),
        ];
    }

    /** Persentase hari terpakai pada 30 hari terakhir (hari ini termasuk). */
    private function persenKeterpakaian(Kendaraan $kendaraan): int
    {
        $awal = today()->subDays(29);

        $hariTerpakai = $kendaraan->pesanan()
            ->where('status', '<>', BookingStatus::Dibatalkan)
            ->whereDate('tanggal_trip', '<=', today())
            ->where(fn ($query) => $query->whereDate('tanggal_selesai', '>=', $awal)->orWhere(fn ($sub) => $sub->whereNull('tanggal_selesai')->whereDate('tanggal_trip', '>=', $awal)))
            ->get(['tanggal_trip', 'tanggal_selesai'])
            ->flatMap(function (Pesanan $pesanan) use ($awal): array {
                $mulai = $pesanan->tanggal_trip->copy()->max($awal);
                $akhir = ($pesanan->tanggal_selesai ?? $pesanan->tanggal_trip)->copy()->min(today());
                $hari = [];

                for ($tanggal = $mulai; $tanggal->lte($akhir); $tanggal = $tanggal->copy()->addDay()) {
                    $hari[] = $tanggal->toDateString();
                }

                return $hari;
            })
            ->unique()
            ->count();

        return (int) round($hariTerpakai / 30 * 100);
    }

    /**
     * Jumlah trip (tidak dibatalkan) per minggu pada MINGGU_GRAFIK minggu terakhir, minggu berjalan terakhir.
     *
     * @return list<array{label: string, jumlah: int}>
     */
    private function grafikMingguan(Kendaraan $kendaraan): array
    {
        $awal = today()->startOfWeek()->subWeeks(self::MINGGU_GRAFIK - 1);

        $perMinggu = $kendaraan->pesanan()
            ->where('status', '<>', BookingStatus::Dibatalkan)
            ->whereBetween('tanggal_trip', [$awal, today()->endOfWeek()])
            ->pluck('tanggal_trip')
            ->countBy(fn (CarbonInterface $tanggal): int => intdiv((int) $awal->diffInDays($tanggal->copy()->startOfDay()), 7));

        return collect(range(0, self::MINGGU_GRAFIK - 1))
            ->map(fn (int $i): array => [
                'label' => $awal->copy()->addWeeks($i)->translatedFormat('d M'),
                'jumlah' => (int) $perMinggu->get($i, 0),
            ])
            ->all();
    }

    /**
     * Pesanan aktif (berjalan atau mendatang) yang tanggalnya saling tumpang tindih pada kendaraan ini.
     *
     * @return list<array{kode: string, lawan: string}>
     */
    private function bentrok(Kendaraan $kendaraan): array
    {
        $mendatang = $kendaraan->pesanan()
            ->menahanKendaraan()
            ->orderBy('tanggal_trip')
            ->orderBy('id')
            ->get(['id', 'kode', 'tanggal_trip', 'tanggal_selesai']);

        $hasil = [];

        foreach ($mendatang as $i => $pesanan) {
            $berikutnya = $mendatang->get($i + 1);

            if ($berikutnya !== null && $berikutnya->tanggal_trip->lte($pesanan->tanggal_selesai ?? $pesanan->tanggal_trip)) {
                $hasil[] = ['kode' => (string) $pesanan->kode, 'lawan' => (string) $berikutnya->kode];
            }
        }

        return $hasil;
    }

    /**
     * @return array{tingkat: string, keterangan: string}
     */
    private function statusDokumen(?CarbonInterface $berlakuSampai, string $nama): array
    {
        if ($berlakuSampai === null) {
            return ['tingkat' => 'perhatian', 'keterangan' => "Masa berlaku {$nama} belum diisi."];
        }

        $sisaHari = (int) today()->diffInDays($berlakuSampai->copy()->startOfDay(), false);

        return match (true) {
            $sisaHari < 0 => ['tingkat' => 'bahaya', 'keterangan' => "{$nama} kedaluwarsa ".abs($sisaHari).' hari lalu ('.$berlakuSampai->translatedFormat('d M Y').').'],
            $sisaHari <= self::BATAS_DOKUMEN_SEGERA_HARI => ['tingkat' => 'perhatian', 'keterangan' => "{$nama} berakhir {$sisaHari} hari lagi (".$berlakuSampai->translatedFormat('d M Y').').'],
            default => ['tingkat' => 'ok', 'keterangan' => "{$nama} berlaku sampai ".$berlakuSampai->translatedFormat('d M Y').'.'],
        };
    }

    /**
     * @param  array{stnk: array{tingkat: string, keterangan: string}, pajak: array{tingkat: string, keterangan: string}}  $dokumen
     * @param  Collection<int, ServisKendaraan>  $servisTerjadwal
     * @param  list<array{kode: string, lawan: string}>  $bentrok
     * @param  list<array{kode: string, alasan: string}>  $penugasanBermasalah
     * @return array{skor: int, label: string, tingkat: string, pemeriksaan: list<array{label: string, keterangan: string, tingkat: string}>}
     */
    private function kesiapan(Kendaraan $kendaraan, array $dokumen, Collection $servisTerjadwal, bool $punyaRiwayat, array $bentrok, array $penugasanBermasalah): array
    {
        $terlambat = $servisTerjadwal->filter(fn (ServisKendaraan $servis): bool => $servis->terlambat($kendaraan->odometer));

        $pemeriksaan = [
            'status' => match ($kendaraan->status) {
                'siap' => ['tingkat' => 'ok', 'keterangan' => 'Status kendaraan Siap jalan.'],
                'servis' => ['tingkat' => 'perhatian', 'keterangan' => 'Kendaraan sedang dalam servis dan belum bisa ditugaskan.'],
                default => ['tingkat' => 'bahaya', 'keterangan' => 'Kendaraan nonaktif dan tidak bisa ditugaskan.'],
            },
            'stnk' => $dokumen['stnk'],
            'pajak' => $dokumen['pajak'],
            'servis' => match (true) {
                $terlambat->isNotEmpty() => ['tingkat' => 'bahaya', 'keterangan' => $terlambat->count().' servis terjadwal sudah lewat jatuh tempo: '.$terlambat->pluck('jenis')->implode(', ').'.'],
                $servisTerjadwal->isEmpty() && ! $punyaRiwayat => ['tingkat' => 'perhatian', 'keterangan' => 'Belum ada catatan servis. Tambahkan jadwal atau riwayat servis.'],
                default => ['tingkat' => 'ok', 'keterangan' => 'Tidak ada servis yang terlambat.'],
            },
            'jadwal' => match (true) {
                $bentrok !== [] => ['tingkat' => 'bahaya', 'keterangan' => 'Ada pesanan yang tanggalnya bertabrakan pada kendaraan ini.'],
                $penugasanBermasalah !== [] => ['tingkat' => 'bahaya', 'keterangan' => count($penugasanBermasalah).' pesanan aktif memakai kendaraan ini padahal tidak lagi layak (lihat peringatan di Kinerja Trip).'],
                default => ['tingkat' => 'ok', 'keterangan' => 'Tidak ada jadwal yang bertabrakan dan semua penugasan masih layak.'],
            },
        ];

        $label = ['status' => 'Status operasional', 'stnk' => 'STNK', 'pajak' => 'Pajak kendaraan', 'servis' => 'Servis berkala', 'jadwal' => 'Jadwal penugasan'];

        $skor = collect($pemeriksaan)->sum(fn (array $hasil, string $kunci): float => self::BOBOT[$kunci] * match ($hasil['tingkat']) {
            'ok' => 1,
            'perhatian' => 0.5,
            default => 0,
        });
        $skor = (int) round($skor);

        return [
            'skor' => $skor,
            'label' => match (true) {
                $skor >= 80 => 'Baik',
                $skor >= 60 => 'Perlu perhatian',
                default => 'Perlu tindakan',
            },
            'tingkat' => match (true) {
                $skor >= 80 => 'ok',
                $skor >= 60 => 'perhatian',
                default => 'bahaya',
            },
            'pemeriksaan' => collect($pemeriksaan)
                ->map(fn (array $hasil, string $kunci): array => ['label' => $label[$kunci], ...$hasil])
                ->values()
                ->all(),
        ];
    }
}
