<?php

namespace App\Layanan;

use App\Enums\BookingStatus;
use App\Models\Pengaturan;
use App\Models\Pesanan;

/**
 * Aturan ganti jadwal: Dihubungi/Jadwal Ditahan gratis kapan saja; Pembayaran hanya bila > H-7 dan kena charge
 * pada ganti jadwal pertama saja; Selesai/Dibatalkan tidak boleh.
 */
class BiayaJadwalLayanan
{
    public function bolehGantiJadwal(Pesanan $pesanan): bool
    {
        return match ($pesanan->status) {
            // Dihubungi / Jadwal Ditahan selalu boleh, bebas batas H-7
            // mengunci H-7 hanya untuk booking yang sudah dibayar (Pembayaran).
            BookingStatus::Dihubungi, BookingStatus::HoldSchedule => true,
            BookingStatus::Payment => $pesanan->sisaHari() > 7,
            default => false,
        };
    }

    public function adalahGantiPertama(Pesanan $pesanan): bool
    {
        return $pesanan->penggantian()->count() === 0;
    }

    public function hitungCharge(Pesanan $pesanan): int
    {
        if ($pesanan->status !== BookingStatus::Payment) {
            return 0;
        }

        if (! $this->adalahGantiPertama($pesanan)) {
            return 0;
        }

        return (int) round(($pesanan->harga ?? 0) * $this->persen() / 100);
    }

    public function persen(): int
    {
        return Pengaturan::current()->persen_charge_ganti_jadwal;
    }
}
