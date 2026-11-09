<?php

namespace App\Enums;

enum StatusPesananKustom: string
{
    case Baru = 'baru';
    case Diproses = 'diproses';
    case MenungguJawaban = 'menunggu_jawaban';
    case Disetujui = 'disetujui';
    case TidakJadi = 'tidak_jadi';

    public function label(): string
    {
        return match ($this) {
            self::Baru => 'Baru',
            self::Diproses => 'Diproses',
            self::MenungguJawaban => 'Menunggu jawaban',
            self::Disetujui => 'Disetujui',
            self::TidakJadi => 'Tidak jadi',
        };
    }
}
