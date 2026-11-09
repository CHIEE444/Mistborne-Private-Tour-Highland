<?php

namespace App\Bantuan;

use App\Models\Pesanan;

/** Membandingkan data tamu yang diedit admin dengan data tersimpan, untuk konfirmasi ulang ke tamu. */
class PerubahanDataTamu
{
    /** Kolom yang memengaruhi perjalanan/pembayaran tamu → label tampilan. */
    public const KOLOM = [
        'nama_lengkap' => 'Nama lengkap',
        'whatsapp' => 'Nomor WhatsApp',
        'jumlah_wisatawan' => 'Jumlah peserta',
        'nama_hotel' => 'Nama hotel',
        'alamat_hotel' => 'Alamat hotel',
        'kendaraan_pilihan' => 'Kendaraan',
        'lokasi_pickup' => 'Lokasi jemput',
        'waktu_pickup' => 'Jam jemput',
        'paket_opsional' => 'Catatan tambahan',
        'harga' => 'Harga',
        'permintaan_khusus' => 'Permintaan khusus',
    ];

    /**
     * @param  array<string, mixed>  $dataBaru  data yang akan disimpan (sudah dinormalisasi)
     * @return list<array{kolom: string, label: string, lama: string, baru: string}>
     */
    public static function bandingkan(Pesanan $pesanan, array $dataBaru): array
    {
        $perubahan = [];

        foreach (self::KOLOM as $kolom => $label) {
            if (! array_key_exists($kolom, $dataBaru)) {
                continue;
            }

            // Harga kosong di database berarti "ikut harga journey": bukan perubahan bila admin tidak mengisinya.
            $lama = self::teks($kolom, $kolom === 'harga' ? ($pesanan->harga ?? $dataBaru['harga']) : $pesanan->{$kolom});
            $baru = self::teks($kolom, $dataBaru[$kolom]);

            if ($lama !== $baru) {
                $perubahan[] = ['kolom' => $kolom, 'label' => $label, 'lama' => $lama ?: '—', 'baru' => $baru ?: '—'];
            }
        }

        return $perubahan;
    }

    private static function teks(string $kolom, mixed $nilai): string
    {
        if ($nilai === null || $nilai === '') {
            return '';
        }

        return match ($kolom) {
            'waktu_pickup' => substr((string) $nilai, 0, 5),
            'harga' => FormatRupiah::dari((int) $nilai),
            default => trim((string) $nilai),
        };
    }
}
