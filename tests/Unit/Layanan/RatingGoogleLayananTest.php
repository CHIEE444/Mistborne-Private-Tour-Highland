<?php

namespace Tests\Unit\Layanan;

use App\Layanan\RatingGoogleLayanan;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RatingGoogleLayananTest extends TestCase
{
    public function test_tidak_tersedia_dan_menyebut_kunci_yang_belum_diisi(): void
    {
        Http::fake();

        config(['services.google_places.api_key' => null, 'services.google_places.place_id' => 'tempat-uji']);
        $tanpaKunci = (new RatingGoogleLayanan)->ambil();

        config(['services.google_places.api_key' => 'kunci-uji', 'services.google_places.place_id' => null]);
        $tanpaPlaceId = (new RatingGoogleLayanan)->ambil();

        $this->assertFalse($tanpaKunci['tersedia']);
        $this->assertNull($tanpaKunci['rating']);
        $this->assertSame('GOOGLE_PLACES_API_KEY belum diisi di .env.', $tanpaKunci['alasan']);
        $this->assertSame('GOOGLE_PLACES_PLACE_ID belum diisi di .env.', $tanpaPlaceId['alasan']);
        Http::assertNothingSent();
    }

    public function test_memanggil_places_api_baru_dan_memetakan_rating(): void
    {
        config(['services.google_places.api_key' => 'kunci-uji', 'services.google_places.place_id' => 'tempat-uji']);
        Http::fake([
            'places.googleapis.com/v1/places/tempat-uji' => Http::response([
                'rating' => 4.84,
                'userRatingCount' => 123,
                'googleMapsUri' => 'https://maps.google.com/?cid=1',
            ]),
        ]);

        $hasil = (new RatingGoogleLayanan)->ambil();

        $this->assertSame(['tersedia' => true, 'rating' => 4.8, 'jumlah_ulasan' => 123, 'alasan' => null, 'tautan' => 'https://maps.google.com/?cid=1'], $hasil);
        Http::assertSent(fn (Request $permintaan): bool => $permintaan->hasHeader('X-Goog-Api-Key', 'kunci-uji')
            && $permintaan->hasHeader('X-Goog-FieldMask', 'rating,userRatingCount,googleMapsUri'));
    }

    public function test_pesan_galat_google_diteruskan_dan_tidak_disimpan_lama(): void
    {
        config(['services.google_places.api_key' => 'kunci-salah', 'services.google_places.place_id' => 'tempat-uji']);
        Http::fakeSequence('places.googleapis.com/*')
            ->push(['error' => ['message' => 'API key not valid. Please pass a valid API key.']], 400)
            ->push(['rating' => 4.5, 'userRatingCount' => 10]);

        $hasil = (new RatingGoogleLayanan)->ambil();

        $this->assertFalse($hasil['tersedia']);
        $this->assertSame('Google menolak permintaan: API key not valid. Please pass a valid API key.', $hasil['alasan']);

        $this->travel(11)->minutes();
        $this->assertTrue((new RatingGoogleLayanan)->ambil()['tersedia']);
    }

    public function test_tempat_tanpa_rating_dijelaskan(): void
    {
        config(['services.google_places.api_key' => 'kunci-uji', 'services.google_places.place_id' => 'tempat-uji']);
        Http::fake(['places.googleapis.com/*' => Http::response(['userRatingCount' => 0])]);

        $this->assertSame('Tempat ini belum punya rating di Google Maps.', (new RatingGoogleLayanan)->ambil()['alasan']);
    }
}
