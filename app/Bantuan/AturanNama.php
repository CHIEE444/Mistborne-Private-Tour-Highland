<?php

namespace App\Bantuan;

/**
 * Satu aturan nama orang untuk semua formulir (tamu, admin, personel): minimal 3 karakter, memuat huruf,
 * dan hanya huruf, angka, spasi, serta tanda . , ' - (menolak simbol, emoji, dan karakter < >).
 */
class AturanNama
{
    /**
     * @return array<int, string>
     */
    public static function aturan(int $maks = 255): array
    {
        return ['bail', 'required', 'string', 'min:3', "max:{$maks}", 'not_regex:/[<>]/', 'regex:/^[\p{L}\p{M}\p{N}\s.,\'’\-]+$/u', 'regex:/\p{L}/u'];
    }

    /**
     * @return array<string, string>
     */
    public static function pesan(string $kolom = 'nama_lengkap', string $label = 'Nama lengkap'): array
    {
        return [
            "{$kolom}.required" => "{$label} wajib diisi.",
            "{$kolom}.string" => "{$label} harus berupa teks.",
            "{$kolom}.min" => "{$label} minimal 3 karakter.",
            "{$kolom}.max" => "{$label} terlalu panjang.",
            "{$kolom}.not_regex" => "{$label} tidak boleh memuat karakter < atau >.",
            "{$kolom}.regex" => "{$label} hanya boleh berisi huruf, angka, spasi, dan tanda . , ' -",
        ];
    }

    /** Spasi ganda dan spasi di tepi dirapikan sebelum divalidasi. */
    public static function rapikan(mixed $nama): ?string
    {
        if (! is_string($nama)) {
            return null;
        }

        $bersih = preg_replace('/\s+/u', ' ', trim($nama));

        return $bersih === '' ? null : $bersih;
    }
}
