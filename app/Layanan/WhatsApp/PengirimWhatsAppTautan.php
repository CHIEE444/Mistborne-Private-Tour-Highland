<?php

namespace App\Layanan\WhatsApp;

use App\Kontrak\PengirimWhatsApp;

class PengirimWhatsAppTautan implements PengirimWhatsApp
{
    public function buatTautan(string $nomor, string $pesan): string
    {
        return 'https://wa.me/'.$this->normalisasiNomor($nomor).'?text='.rawurlencode($pesan);
    }

    /** Pastikan format 62xxxxxxxxxx (tanpa +, tanpa 0 depan). */
    private function normalisasiNomor(string $nomor): string
    {
        $digit = preg_replace('/\D/', '', $nomor) ?? '';

        if (str_starts_with($digit, '0')) {
            $digit = '62'.substr($digit, 1);
        }

        if (! str_starts_with($digit, '62')) {
            $digit = '62'.$digit;
        }

        return $digit;
    }
}
