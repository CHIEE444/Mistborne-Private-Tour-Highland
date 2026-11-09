<?php

namespace App\Bantuan;

class NomorWhatsApp
{
    /**
     * Merapikan nomor ke format 08xxxxxxxxxx (buang spasi/strip/titik/kurung/+, 62 → 0). Nomor berhuruf dibiarkan agar ditolak validasi.
     */
    public static function lokal(?string $nomor): string
    {
        $nomor = trim((string) $nomor);

        if ($nomor === '' || preg_match('/[^\d\s\-.()+]/', $nomor) === 1) {
            return $nomor;
        }

        $digit = preg_replace('/\D+/', '', $nomor) ?? '';

        return str_starts_with($digit, '62') ? '0'.substr($digit, 2) : $digit;
    }
}
