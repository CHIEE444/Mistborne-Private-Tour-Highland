<?php

namespace App\Layanan;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Rating Google Maps bisnis MISTBORNE untuk dashboard lewat Places API (New); kunci kosong = kartu menjelaskan cara mengisinya.
 * Hasil sukses disimpan 6 jam, kegagalan 10 menit agar perbaikan .env cepat terlihat.
 */
class RatingGoogleLayanan
{
    public const ALAMAT_DETAIL = 'https://places.googleapis.com/v1/places/';

    /**
     * @return array{tersedia: bool, rating: float|null, jumlah_ulasan: int|null, alasan: string|null, tautan: string|null}
     */
    public function ambil(): array
    {
        $apiKey = config('services.google_places.api_key');
        $placeId = config('services.google_places.place_id');

        if (blank($apiKey) || blank($placeId)) {
            return $this->gagal(blank($apiKey) ? 'GOOGLE_PLACES_API_KEY belum diisi di .env.' : 'GOOGLE_PLACES_PLACE_ID belum diisi di .env.');
        }

        $kunci = 'dashboard.rating-google.'.md5($apiKey.$placeId);
        $tersimpan = Cache::get($kunci);
        if (is_array($tersimpan)) {
            return $tersimpan;
        }

        $hasil = $this->minta((string) $apiKey, (string) $placeId);
        Cache::put($kunci, $hasil, $hasil['tersedia'] ? now()->addHours(6) : now()->addMinutes(10));

        return $hasil;
    }

    /**
     * @return array{tersedia: bool, rating: float|null, jumlah_ulasan: int|null, alasan: string|null, tautan: string|null}
     */
    private function minta(string $apiKey, string $placeId): array
    {
        try {
            $respons = Http::timeout(8)
                ->withHeaders(['X-Goog-Api-Key' => $apiKey, 'X-Goog-FieldMask' => 'rating,userRatingCount,googleMapsUri'])
                ->get(self::ALAMAT_DETAIL.rawurlencode($placeId));
        } catch (ConnectionException) {
            return $this->gagal('Server tidak bisa menghubungi Google. Periksa koneksi internet.');
        }

        if (! $respons->successful()) {
            return $this->gagal('Google menolak permintaan: '.($respons->json('error.message') ?: 'HTTP '.$respons->status()));
        }

        if ($respons->json('rating') === null) {
            return $this->gagal('Tempat ini belum punya rating di Google Maps.');
        }

        return [
            'tersedia' => true,
            'rating' => round((float) $respons->json('rating'), 1),
            'jumlah_ulasan' => (int) $respons->json('userRatingCount', 0),
            'alasan' => null,
            'tautan' => $respons->json('googleMapsUri'),
        ];
    }

    /**
     * @return array{tersedia: false, rating: null, jumlah_ulasan: null, alasan: string, tautan: null}
     */
    private function gagal(string $alasan): array
    {
        return ['tersedia' => false, 'rating' => null, 'jumlah_ulasan' => null, 'alasan' => $alasan, 'tautan' => null];
    }
}
