<?php

namespace App\Bantuan;

class FormatRupiah
{
    /** Format integer rupiah (tanpa desimal) menjadi "Rp 2.400.000". */
    public static function dari(?int $nominal): ?string
    {
        if ($nominal === null) {
            return null;
        }

        return 'Rp '.number_format($nominal, 0, ',', '.');
    }
}
