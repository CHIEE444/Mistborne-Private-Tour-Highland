<?php

namespace App\Enums;

/** Perkembangan reservasi hotel yang dipesankan MISTBORNE untuk tamu. */
enum StatusReservasiHotel: string
{
    case Diminta = 'diminta';
    case Terkonfirmasi = 'terkonfirmasi';
    case Dibatalkan = 'dibatalkan';

    public function label(): string
    {
        return match ($this) {
            self::Diminta => 'Menunggu konfirmasi hotel',
            self::Terkonfirmasi => 'Terkonfirmasi hotel',
            self::Dibatalkan => 'Dibatalkan',
        };
    }
}
