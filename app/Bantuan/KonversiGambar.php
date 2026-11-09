<?php

namespace App\Bantuan;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Crop-cover + konversi WebP pakai ekstensi GD bawaan PHP (tanpa composer package baru).
 * Dipakai untuk gambar sampul Artikel: rasio 1200×630.
 */
class KonversiGambar
{
    public static function sampul(UploadedFile $berkas, string $direktori, string $disk = 'public', int $lebar = 1200, int $tinggi = 630, int $kualitas = 82): string
    {
        static::sediakanMemoriDekode($berkas);

        $sumber = imagecreatefromstring((string) file_get_contents($berkas->getRealPath()));

        if ($sumber === false) {
            // Format tidak dikenali GD (jarang terjadi karena sudah divalidasi mimes di Request) — simpan apa adanya.
            return $berkas->store($direktori, $disk);
        }

        $lebarAsli = imagesx($sumber);
        $tinggiAsli = imagesy($sumber);
        $rasioTarget = $lebar / $tinggi;
        $rasioAsli = $lebarAsli / $tinggiAsli;

        if ($rasioAsli > $rasioTarget) {
            $tinggiCrop = $tinggiAsli;
            $lebarCrop = (int) round($tinggiAsli * $rasioTarget);
            $x = (int) round(($lebarAsli - $lebarCrop) / 2);
            $y = 0;
        } else {
            $lebarCrop = $lebarAsli;
            $tinggiCrop = (int) round($lebarAsli / $rasioTarget);
            $x = 0;
            $y = (int) round(($tinggiAsli - $tinggiCrop) / 2);
        }

        $hasil = imagecreatetruecolor($lebar, $tinggi);
        imagecopyresampled($hasil, $sumber, 0, 0, $x, $y, $lebar, $tinggi, $lebarCrop, $tinggiCrop);

        $nama = Str::random(20).'.webp';
        $path = trim($direktori, '/').'/'.$nama;
        $penuh = Storage::disk($disk)->path($path);

        if (! is_dir(dirname($penuh))) {
            mkdir(dirname($penuh), 0755, true);
        }

        imagewebp($hasil, $penuh, $kualitas);

        imagedestroy($sumber);
        imagedestroy($hasil);

        return $path;
    }

    /**
     * GD memuat gambar sebagai bitmap penuh (sekitar 4,5 byte per piksel). Foto kamera 24 MP butuh ±100 MB,
     * sehingga batas memori 128 MB bawaan PHP bisa mematikan proses. Batas dinaikkan seperlunya saja.
     */
    private static function sediakanMemoriDekode(UploadedFile $berkas): void
    {
        $ukuran = @getimagesize($berkas->getRealPath());
        $batas = static::batasMemoriByte();

        if ($ukuran === false || $batas === -1) {
            return;
        }

        $dibutuhkan = (int) ($ukuran[0] * $ukuran[1] * 4.5) + 64 * 1048576;

        if ($dibutuhkan > $batas) {
            ini_set('memory_limit', (string) $dibutuhkan);
        }
    }

    private static function batasMemoriByte(): int
    {
        $nilai = trim((string) ini_get('memory_limit'));

        if ($nilai === '-1') {
            return -1;
        }

        $angka = (int) $nilai;

        return match (strtolower(substr($nilai, -1))) {
            'g' => $angka * 1073741824,
            'm' => $angka * 1048576,
            'k' => $angka * 1024,
            default => $angka,
        };
    }
}
