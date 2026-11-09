<?php

namespace App\Layanan;

use App\Models\Hotel;
use Illuminate\Support\Facades\Http;

/**
 * Menarik rating dan jumlah ulasan Google Maps untuk satu hotel lewat Places API (New), kunci yang sama dengan widget dashboard.
 * Place ID dicari dari nama dan alamat hotel bila belum diisi, lalu disimpan supaya sinkron berikutnya langsung memakainya.
 */
class RatingGoogleHotelLayanan
{
    public function tersedia(): bool
    {
        return filled(config('services.google_places.api_key'));
    }

    /** True bila rating berhasil diperbarui; false bila API belum dikonfigurasi, hotel tidak ditemukan, atau API gagal. */
    public function sinkron(Hotel $hotel): bool
    {
        if (! $this->tersedia()) {
            return false;
        }

        $placeId = $hotel->google_place_id ?: $this->cariPlaceId($hotel);

        if (blank($placeId)) {
            return false;
        }

        $respons = Http::timeout(8)
            ->withHeaders(['X-Goog-Api-Key' => config('services.google_places.api_key'), 'X-Goog-FieldMask' => 'rating,userRatingCount'])
            ->get(RatingGoogleLayanan::ALAMAT_DETAIL.rawurlencode($placeId));

        $hasil = $respons->successful() ? (array) $respons->json() : [];

        if (! isset($hasil['rating'])) {
            return false;
        }

        $hotel->update([
            'google_place_id' => $placeId,
            'google_rating' => round((float) $hasil['rating'], 1),
            'google_jumlah_ulasan' => (int) ($hasil['userRatingCount'] ?? 0),
            'google_diperbarui' => now(),
        ]);

        return true;
    }

    private function cariPlaceId(Hotel $hotel): ?string
    {
        $respons = Http::timeout(8)
            ->withHeaders(['X-Goog-Api-Key' => config('services.google_places.api_key'), 'X-Goog-FieldMask' => 'places.id'])
            ->post('https://places.googleapis.com/v1/places:searchText', ['textQuery' => trim($hotel->nama.' '.$hotel->area)]);

        return $respons->successful() ? $respons->json('places.0.id') : null;
    }
}
