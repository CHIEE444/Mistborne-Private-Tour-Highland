<?php

namespace App\Enums;

/**
 * Status penginapan tamu selama trip. Sistem baku adalah all-inclusive (Diurus): MISTBORNE memesankan hotel dan biayanya
 * sudah masuk harga. Status lain dipertahankan untuk pesanan lama dan kasus khusus yang diinput admin.
 */
enum StatusPenginapan: string
{
    case Sudah = 'sudah';
    case Belum = 'belum';
    /** Tamu minta dicarikan, lalu setuju pada satu hotel: MISTBORNE yang memesankan, tamu membayar langsung ke hotel. */
    case Dipesankan = 'dipesankan';
    /** All-inclusive (standar semua journey): penginapan dipesankan MISTBORNE dan sudah masuk harga. */
    case Diurus = 'diurus';

    public function label(): string
    {
        return match ($this) {
            self::Sudah => __('Sudah punya penginapan'),
            self::Belum => __('Belum punya, butuh rekomendasi'),
            self::Dipesankan => __('Dipesankan MISTBORNE (bayar ke hotel)'),
            self::Diurus => __('Diurus MISTBORNE (All-inclusive)'),
        };
    }

    /** Status di mana MISTBORNE yang memesankan hotel (perlu reservasi hotel). */
    public function dipesankanMistborne(): bool
    {
        return in_array($this, [self::Dipesankan, self::Diurus], true);
    }
}
