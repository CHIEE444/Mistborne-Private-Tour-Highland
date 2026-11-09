<?php

namespace App\Kontrak;

/**
 * Abstraksi pengiriman pesan WhatsApp. Implementasi saat ini hanya membuat tautan wa.me
 * (admin menekan tombol & mengirim sendiri) — bisa diganti ke WhatsApp Business API nanti
 * tanpa mengubah controller/Aksi yang memakainya.
 */
interface PengirimWhatsApp
{
    /** Buat tautan untuk membuka percakapan WhatsApp berisi $pesan ke $nomor (format 62...). */
    public function buatTautan(string $nomor, string $pesan): string;
}
