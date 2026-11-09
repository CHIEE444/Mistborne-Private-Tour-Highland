<?php

namespace App\Bantuan;

/**
 * Batas unggah file yang benar-benar berlaku di server ini. PHP menolak file yang melebihi
 * upload_max_filesize atau post_max_size sebelum validasi aplikasi sempat berjalan, jadi batas yang
 * diatur admin tidak boleh ditampilkan seolah-olah satu-satunya batas.
 */
class BatasUnggah
{
    /**
     * Batas server dalam MB (dibulatkan ke bawah): nilai terkecil antara upload_max_filesize dan
     * post_max_size. Nilai 0 berarti tanpa batas dan diabaikan; null bila keduanya tanpa batas.
     */
    public static function serverMb(?string $uploadMaxFilesize = null, ?string $postMaxSize = null): ?int
    {
        $byte = collect([$uploadMaxFilesize ?? ini_get('upload_max_filesize'), $postMaxSize ?? ini_get('post_max_size')])
            ->map(fn (mixed $nilai): int => ini_parse_quantity((string) $nilai))
            ->filter(fn (int $nilai): bool => $nilai > 0);

        return $byte->isEmpty() ? null : intdiv($byte->min(), 1024 * 1024);
    }

    /**
     * Batas yang benar-benar berlaku: nilai terkecil antara pengaturan admin dan batas server.
     */
    public static function efektifMb(int $batasPengaturanMb, ?string $uploadMaxFilesize = null, ?string $postMaxSize = null): int
    {
        $server = static::serverMb($uploadMaxFilesize, $postMaxSize);

        return $server === null ? $batasPengaturanMb : min($batasPengaturanMb, $server);
    }
}
