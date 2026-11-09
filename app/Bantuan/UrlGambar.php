<?php

namespace App\Bantuan;

/**
 * Mengubah nilai kolom gambar (path statis, path disk, atau URL penuh) menjadi path "/storage/..." yang aman untuk <img>.
 */
class UrlGambar
{
    public static function dari(?string $nilai): ?string
    {
        if (blank($nilai)) {
            return null;
        }

        if (preg_match('#^https?://[^/]+(/storage/.+)$#i', $nilai, $cocok) === 1) {
            return $cocok[1];
        }

        if (preg_match('#^https?://#i', $nilai) === 1 || str_starts_with($nilai, '/')) {
            return $nilai;
        }

        return asset('storage/'.ltrim($nilai, '/'));
    }
}
