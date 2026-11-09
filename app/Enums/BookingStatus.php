<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Dihubungi = 'dihubungi';
    case Payment = 'payment';
    case HoldSchedule = 'hold_schedule';
    case Completed = 'completed';
    case Dibatalkan = 'dibatalkan';

    public function label(): string
    {
        return match ($this) {
            self::Dihubungi => 'Dihubungi',
            self::Payment => 'Pembayaran',
            self::HoldSchedule => 'Jadwal Ditahan',
            self::Completed => 'Trip Selesai',
            self::Dibatalkan => 'Dibatalkan',
        };
    }

    /** Pesanan aktif (Dihubungi, Pembayaran, Jadwal Ditahan, Selesai) menahan kuota. */
    public function menghitungKuota(): bool
    {
        return match ($this) {
            self::Dihubungi, self::Payment, self::HoldSchedule, self::Completed => true,
            self::Dibatalkan => false,
        };
    }
}
