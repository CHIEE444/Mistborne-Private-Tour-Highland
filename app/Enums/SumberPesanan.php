<?php

namespace App\Enums;

enum SumberPesanan: string
{
    case Form = 'form';
    case Manual = 'manual';
    case Kustom = 'kustom';

    public function label(): string
    {
        return match ($this) {
            self::Form => 'Form',
            self::Manual => 'Manual',
            self::Kustom => 'Kustom',
        };
    }
}
