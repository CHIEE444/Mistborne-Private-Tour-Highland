<?php

namespace Tests\Feature\Publik;

use App\Models\Hotel;
use App\Models\Perjalanan;
use Tests\TestCase;

class HalamanDetailHotelTest extends TestCase
{
    public function test_halaman_hotel_menampilkan_galeri_fasilitas_kamar_yang_termasuk_tanpa_faq_dan_journey_terkait(): void
    {
        $journey = Perjalanan::factory()->create(['nama' => 'JOURNEY DETAIL HOTEL']);
        $hotel = Hotel::factory()->create([
            'nama' => 'Hotel Detail Uji', 'area' => 'Ngadisari, Bromo', 'deskripsi' => 'Hotel nyaman di lereng gunung.', 'jarak_destinasi' => '± 1 jam berkendara ke Bromo',
            'foto' => '/storage/hotel/utama.webp', 'fasilitas' => ['wifi', 'kolam_renang', 'spa'], 'tipe_kamar' => [['nama' => 'Deluxe Uji', 'kapasitas' => 2]], 'tersedia_extra_bed' => true,
            'galeri' => [['foto' => '/storage/hotel/galeri/kamar-uji.webp', 'label' => 'kamar'], ['foto' => '/storage/hotel/galeri/kolam-uji.webp', 'label' => 'kolam_renang']],
        ]);
        $hotel->perjalanan()->attach($journey->id);

        $this->assertSame('hotel-detail-uji', $hotel->slug);

        $this->get(route('hotel.show', $hotel->slug))
            ->assertOk()
            ->assertSee('Hotel Detail Uji')
            ->assertSee('Hotel nyaman di lereng gunung.')
            ->assertSee('/storage/hotel/utama.webp', false)
            ->assertSee('/storage/hotel/galeri/kamar-uji.webp', false)
            ->assertSee('Fasilitas terbaik kami')
            ->assertSee('Kolam renang')
            ->assertSee('Deluxe Uji')
            ->assertSee('Yang sudah termasuk dalam paket')
            ->assertSee('Extra bed tersedia (+1 orang per kamar)')
            ->assertDontSee('Apakah bisa menambah extra bed?')
            ->assertDontSee('id="faq"', false)
            ->assertSee('1 jam berkendara ke Bromo')
            ->assertDontSee('± 1 jam berkendara ke Bromo')
            ->assertSee('JOURNEY DETAIL HOTEL')
            ->assertSee(route('booking.buat', ['destination' => 'JOURNEY DETAIL HOTEL']), false);
    }

    public function test_hotel_nonaktif_atau_tidak_ada_mengembalikan_404(): void
    {
        $nonaktif = Hotel::factory()->nonaktif()->create(['nama' => 'Hotel Sembunyi']);

        $this->get(route('hotel.show', $nonaktif->slug))->assertNotFound();
        $this->get('/hotel/tidak-ada')->assertNotFound();
    }

    public function test_slug_hotel_unik_dan_mengikuti_nama_saat_diubah(): void
    {
        $pertama = Hotel::factory()->create(['nama' => 'Hotel Kembar']);
        $kedua = Hotel::factory()->create(['nama' => 'Hotel Kembar ']);

        $this->assertSame('hotel-kembar', $pertama->slug);
        $this->assertSame('hotel-kembar-2', $kedua->slug);

        $pertama->update(['nama' => 'Hotel Baru Nama']);
        $this->assertSame('hotel-baru-nama', $pertama->fresh()->slug);
    }

    public function test_halaman_hotel_menampilkan_rating_google_dan_tautan_ulasan(): void
    {
        $hotel = Hotel::factory()->create(['nama' => 'Hotel Google Uji', 'google_rating' => 4.6, 'google_jumlah_ulasan' => 1250, 'google_place_id' => 'ChIJuji123']);

        $this->get(route('hotel.show', $hotel->slug))
            ->assertOk()
            ->assertSee('Ulasan dan rating')
            ->assertSee('4.6')
            ->assertSee('Berdasarkan 1.250 ulasan di Google')
            ->assertSee('https://www.google.com/maps/place/?q=place_id:ChIJuji123', false)
            ->assertDontSee('Rating Google hotel ini belum tersedia');
    }

    public function test_hotel_tanpa_rating_google_tidak_menampilkan_angka_karangan(): void
    {
        $hotel = Hotel::factory()->create(['nama' => 'Hotel Tanpa Rating', 'google_rating' => null]);

        $this->get(route('hotel.show', $hotel->slug))
            ->assertOk()
            ->assertSee('Rating Google hotel ini belum tersedia')
            ->assertSee('Lihat ulasan di Google Maps')
            ->assertDontSee('Berdasarkan');
    }

    public function test_kartu_hotel_di_halaman_journey_menaut_ke_halaman_hotel(): void
    {
        $journey = Perjalanan::factory()->create();
        $hotel = Hotel::factory()->create(['nama' => 'Hotel Tautan Uji']);
        $journey->hotel()->attach($hotel->id);

        $this->get(route('journeys.show', $journey->slug))
            ->assertOk()
            ->assertSee(route('hotel.show', $hotel->slug), false)
            ->assertSee('Lihat detail hotel');
    }
}
