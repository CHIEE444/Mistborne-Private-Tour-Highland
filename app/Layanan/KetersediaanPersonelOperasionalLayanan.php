<?php

namespace App\Layanan;

use App\Enums\BookingStatus;
use App\Models\PersonelOperasional;
use App\Models\Pesanan;
use Illuminate\Support\Collection;

/**
 * Guide yang boleh ditugaskan ke sebuah pesanan. Guide merangkap driver, jadi selain aktif, berstatus siap, dan tidak
 * bentrok jadwal, SIM-nya harus masih berlaku sampai hari terakhir trip.
 */
class KetersediaanPersonelOperasionalLayanan
{
    private const KOLOM_PENUGASAN = 'guide_personel_id';

    /** @return Collection<int, string> id guide => label pilihan */
    public function daftarTersedia(Pesanan $pesanan): Collection
    {
        $idSaatIni = $pesanan->{self::KOLOM_PENUGASAN};
        $idBentrok = $this->idYangBentrok($pesanan);
        $akhirTrip = $pesanan->tanggal_selesai ?? $pesanan->tanggal_trip;

        $personel = PersonelOperasional::query()
            ->where('peran', 'guide')
            ->where('aktif', true)
            ->where(function ($query) use ($idSaatIni): void {
                $query->where('status_operasional', 'siap')
                    ->when($idSaatIni, fn ($query) => $query->orWhere('id', $idSaatIni));
            })
            ->where(function ($query) use ($idBentrok, $idSaatIni): void {
                $query->whereNotIn('id', $idBentrok)
                    ->when($idSaatIni, fn ($query) => $query->orWhere('id', $idSaatIni));
            })
            ->where(function ($query) use ($akhirTrip, $idSaatIni): void {
                $query->whereDate('masa_berlaku_sim', '>=', $akhirTrip->toDateString())
                    ->when($idSaatIni, fn ($query) => $query->orWhere('id', $idSaatIni));
            })
            ->orderBy('nama')
            ->get();

        return $personel->mapWithKeys(function (PersonelOperasional $item) use ($idSaatIni, $idBentrok, $akhirTrip): array {
            $label = $item->nama.($item->jenis_sim ? " · SIM {$item->jenis_sim}" : '');

            if ((int) $item->id === (int) $idSaatIni) {
                $masalah = array_filter([
                    in_array((int) $item->id, $idBentrok, true) ? 'jadwal bentrok' : null,
                    $item->status_operasional !== 'siap' ? 'sedang bertugas' : null,
                    ! $item->simBerlakuSampai($akhirTrip) ? 'SIM tidak berlaku sampai trip selesai' : null,
                ]);
                $label .= ' · Penugasan saat ini'.($masalah ? ' ('.implode(', ', $masalah).')' : '');
            }

            return [$item->id => $label];
        });
    }

    public function memilikiJadwalBentrok(int $personelId, Pesanan $pesanan): bool
    {
        return in_array($personelId, $this->idYangBentrok($pesanan), true);
    }

    /** @return list<int> */
    private function idYangBentrok(Pesanan $pesanan): array
    {
        $tanggalMulai = $pesanan->tanggal_trip->toDateString();
        $tanggalSelesai = ($pesanan->tanggal_selesai ?? $pesanan->tanggal_trip)->toDateString();

        return Pesanan::query()
            ->whereIn('status', [
                BookingStatus::Dihubungi->value,
                BookingStatus::Payment->value,
                BookingStatus::HoldSchedule->value,
            ])
            ->where('id', '<>', $pesanan->id)
            ->whereDate('tanggal_trip', '<=', $tanggalSelesai)
            ->whereRaw('COALESCE(tanggal_selesai, tanggal_trip) >= ?', [$tanggalMulai])
            ->where(function ($query): void {
                $query->whereNull('status_konfirmasi_guide')->orWhereIn('status_konfirmasi_guide', ['menunggu', 'diterima']);
            })
            ->whereNotNull(self::KOLOM_PENUGASAN)
            ->pluck(self::KOLOM_PENUGASAN)
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
