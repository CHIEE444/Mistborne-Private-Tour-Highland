<?php

namespace Tests\Unit\Layanan;

use App\Layanan\RatingGoogleHotelLayanan;
use App\Models\Hotel;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RatingGoogleHotelLayananTest extends TestCase
{
    public function test_tidak_menarik_rating_bila_api_key_kosong(): void
    {
        config(['services.google_places.api_key' => null]);
        Http::fake();

        $hotel = Hotel::factory()->create();

        $this->assertFalse((new RatingGoogleHotelLayanan)->sinkron($hotel));
        Http::assertNothingSent();
    }

    public function test_mencari_place_id_lalu_menyimpan_rating_dan_jumlah_ulasan(): void
    {
        config(['services.google_places.api_key' => 'kunci-uji']);
        Http::fake([
            'places.googleapis.com/v1/places:searchText' => Http::response(['places' => [['id' => 'ChIJhotel']]]),
            'places.googleapis.com/v1/places/ChIJhotel' => Http::response(['rating' => 4.55, 'userRatingCount' => 321]),
        ]);

        $hotel = Hotel::factory()->create(['google_place_id' => null]);

        $this->assertTrue((new RatingGoogleHotelLayanan)->sinkron($hotel));

        $hotel->refresh();
        $this->assertSame('ChIJhotel', $hotel->google_place_id);
        $this->assertSame('4.6', $hotel->google_rating);
        $this->assertSame(321, $hotel->google_jumlah_ulasan);
        $this->assertNotNull($hotel->google_diperbarui);
        Http::assertSent(fn ($permintaan) => $permintaan->url() === 'https://places.googleapis.com/v1/places:searchText'
            && $permintaan->hasHeader('X-Goog-Api-Key', 'kunci-uji')
            && $permintaan['textQuery'] === trim($hotel->nama.' '.$hotel->area));
    }

    public function test_gagal_bila_hotel_tidak_ditemukan_di_google(): void
    {
        config(['services.google_places.api_key' => 'kunci-uji']);
        Http::fake(['places.googleapis.com/*' => Http::response(['places' => []])]);

        $hotel = Hotel::factory()->create(['google_place_id' => null]);

        $this->assertFalse((new RatingGoogleHotelLayanan)->sinkron($hotel));
        $this->assertNull($hotel->fresh()->google_rating);
    }
}
