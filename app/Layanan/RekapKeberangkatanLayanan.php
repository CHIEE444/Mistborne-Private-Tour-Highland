<?php

namespace App\Layanan;

use App\Models\Perjalanan;
use App\Models\Pesanan;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Data rekap PDF bulanan Kalender Trip (pengganti manifest harian): keberangkatan yang pasti berangkat
 * (Pembayaran/Selesai) dalam satu bulan kalender, dikelompokkan per tanggal trip dan urut jam jemput.
 */
class RekapKeberangkatanLayanan
{
    /**
     * @return array{
     *     bulan: CarbonInterface,
     *     journeyTerpilih: ?Perjalanan,
     *     perTanggal: Collection<string, Collection<int, Pesanan>>,
     *     perJourney: Collection<string, int>,
     *     jumlahPesanan: int,
     *     jumlahTamu: int,
     *     jumlahHari: int
     * }
     */
    public function bulanan(CarbonInterface $bulan, ?int $idJourney = null): array
    {
        $pesanan = Pesanan::query()
            ->pastiBerangkat()
            ->with(['kendaraan'])
            ->whereDate('tanggal_trip', '>=', $bulan->copy()->startOfMonth()->toDateString())
            ->whereDate('tanggal_trip', '<=', $bulan->copy()->endOfMonth()->toDateString())
            ->when($idJourney, fn ($query, int $id) => $query->where('perjalanan_id', $id))
            ->orderBy('tanggal_trip')
            ->orderBy('waktu_pickup')
            ->orderBy('nama_perjalanan')
            ->orderBy('nama_lengkap')
            ->get();

        $perTanggal = $pesanan->groupBy(fn (Pesanan $item): string => $item->tanggal_trip->toDateString());

        return [
            'bulan' => $bulan->copy()->startOfMonth(),
            'journeyTerpilih' => $idJourney ? Perjalanan::query()->find($idJourney) : null,
            'perTanggal' => $perTanggal,
            'perJourney' => $pesanan->countBy('nama_perjalanan')->sortDesc(),
            'jumlahPesanan' => $pesanan->count(),
            'jumlahTamu' => (int) $pesanan->sum('jumlah_wisatawan'),
            'jumlahHari' => $perTanggal->count(),
        ];
    }
}
