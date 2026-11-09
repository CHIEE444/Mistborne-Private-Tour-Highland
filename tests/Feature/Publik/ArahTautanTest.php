<?php

namespace Tests\Feature\Publik;

use App\Models\Artikel;
use App\Models\Hotel;
use App\Models\Perjalanan;
use App\Models\Ulasan;
use App\Models\User;
use Tests\TestCase;

/**
 * Kebenaran ARAH: tombol dan tautan harus membawa pengunjung ke tujuan yang sesuai konteks halamannya,
 * bukan sekadar menghasilkan status 200.
 */
class ArahTautanTest extends TestCase
{
    /** @return array{0: Hotel, 1: Perjalanan, 2: Perjalanan} hotel yang melayani paket (urut nama paling awal) dan journey */
    private function hotelDenganPaketDanJourney(): array
    {
        $paket = Perjalanan::factory()->create(['jenis' => 'paket', 'nama' => 'AAA PAKET SIKUNIR UJI', 'slug' => 'aaa-paket-sikunir-uji']);
        $journey = Perjalanan::factory()->create(['jenis' => 'journey', 'nama' => 'ZZZ DIENG UJI', 'slug' => 'zzz-dieng-uji']);
        $hotel = Hotel::factory()->create(['nama' => 'Hotel Arah Uji']);
        $hotel->perjalanan()->attach([$paket->id, $journey->id]);

        return [$hotel, $paket, $journey];
    }

    public function test_tombol_kembali_ke_beranda_ada_di_semua_halaman_publik_kecuali_beranda(): void
    {
        $journey = Perjalanan::factory()->create(['jenis' => 'journey', 'nama' => 'JOURNEY TOMBOL KACA', 'slug' => 'journey-tombol-kaca']);
        $artikel = Artikel::factory()->create(['judul' => 'Artikel Navigasi Uji']);
        $tombolBeranda = fn (string $beranda, string $teks = 'Kembali ke Beranda'): string => '#<a\s+href="'.preg_quote($beranda, '#').'"[^>]*>.*?'.preg_quote($teks, '#').'\s*</a>#s';

        foreach ([route('journeys.index'), route('gallery.index'), route('articles.index'), route('articles.show', $artikel->slug), route('journeys.show', $journey->slug), route('rancang-trip-kustom')] as $alamat) {
            $this->assertMatchesRegularExpression($tombolBeranda(route('home')), $this->get($alamat)->assertOk()->getContent(), $alamat);
        }
        $this->assertMatchesRegularExpression($tombolBeranda(route('home.localized', ['locale' => 'en']), 'Back to homepage'), $this->get('/en/journey')->assertOk()->getContent());

        $this->get(route('home'))->assertOk()->assertDontSee('Kembali ke Beranda');
    }

    public function test_halaman_detail_hanya_punya_tombol_kembali_ke_beranda_tanpa_tombol_mundur(): void
    {
        $journey = Perjalanan::factory()->create(['jenis' => 'journey', 'nama' => 'NAVIGASI JOURNEY UJI', 'slug' => 'navigasi-journey-uji']);
        $artikel = Artikel::factory()->create(['judul' => 'Artikel Navigasi Uji']);
        $tombolMundur = fn (string $tujuan, string $teks): string => '#<a href="'.preg_quote($tujuan, '#').'" class="tombol-kaca[^"]*">\s*<span[^>]*>←</span>\s*<span>'.preg_quote($teks, '#').'</span>#';

        $detailJourney = $this->get(route('journeys.show', $journey->slug))->assertOk()->assertSee('Kembali ke Beranda')->getContent();
        $detailArtikel = $this->get(route('articles.show', $artikel->slug))->assertOk()->assertSee('Kembali ke Beranda')->getContent();

        $this->assertDoesNotMatchRegularExpression($tombolMundur(route('journeys.index'), 'Semua Perjalanan'), $detailJourney);
        $this->assertDoesNotMatchRegularExpression($tombolMundur(route('articles.index'), 'Semua Artikel'), $detailArtikel);
    }

    public function test_hotel_yang_dibuka_dari_journey_mengembalikan_dan_membooking_journey_itu_bukan_paket_lain(): void
    {
        [$hotel, $paket, $journey] = $this->hotelDenganPaketDanJourney();

        $this->get(route('hotel.show', ['hotel' => $hotel->slug, 'dari' => $journey->slug]))
            ->assertOk()
            ->assertSee('href="'.route('journeys.show', $journey->slug).'"', false)
            ->assertDontSee('href="'.route('journeys.show', $paket->slug).'"', false)
            ->assertSee('href="'.route('booking.buat', ['destination' => $journey->nama]).'"', false);
    }

    public function test_hotel_yang_dibuka_dari_paket_mengembalikan_ke_paket_itu(): void
    {
        [$hotel, $paket, $journey] = $this->hotelDenganPaketDanJourney();

        $this->get(route('hotel.show', ['hotel' => $hotel->slug, 'dari' => $paket->slug]))
            ->assertOk()
            ->assertSee('href="'.route('journeys.show', $paket->slug).'"', false)
            ->assertSee('href="'.route('booking.buat', ['destination' => $paket->nama]).'"', false);
    }

    public function test_hotel_tanpa_konteks_mendahulukan_journey_utama_dari_paket_meski_nama_paket_lebih_awal(): void
    {
        [$hotel, $paket, $journey] = $this->hotelDenganPaketDanJourney();

        $this->get(route('hotel.show', $hotel->slug))
            ->assertOk()
            ->assertSee('href="'.route('journeys.show', $journey->slug).'"', false)
            ->assertSee('href="'.route('booking.buat', ['destination' => $journey->nama]).'"', false);
    }

    public function test_konteks_dari_yang_bukan_milik_hotel_diabaikan(): void
    {
        [$hotel, , $journey] = $this->hotelDenganPaketDanJourney();
        $asing = Perjalanan::factory()->create(['jenis' => 'journey', 'slug' => 'journey-asing-uji']);

        $this->get(route('hotel.show', ['hotel' => $hotel->slug, 'dari' => $asing->slug]))
            ->assertOk()
            ->assertSee('href="'.route('journeys.show', $journey->slug).'"', false)
            ->assertDontSee('href="'.route('journeys.show', $asing->slug).'"', false);
    }

    public function test_panel_booking_hotel_dari_journey_tertentu_hanya_menawarkan_journey_itu(): void
    {
        [$hotel, $paket, $journey] = $this->hotelDenganPaketDanJourney();

        $this->get(route('hotel.show', ['hotel' => $hotel->slug, 'dari' => $paket->slug]))
            ->assertOk()
            ->assertSee('AAA PAKET SIKUNIR UJI')
            ->assertDontSee('ZZZ DIENG UJI');
        $this->get(route('hotel.show', ['hotel' => $hotel->slug, 'dari' => $journey->slug]))
            ->assertOk()
            ->assertSee('ZZZ DIENG UJI')
            ->assertDontSee('AAA PAKET SIKUNIR UJI');
        $this->get(route('hotel.show', $hotel->slug))
            ->assertOk()
            ->assertSeeInOrder(['ZZZ DIENG UJI', 'AAA PAKET SIKUNIR UJI']);
    }

    public function test_kartu_hotel_di_halaman_journey_membawa_konteks_journey_itu(): void
    {
        [$hotel, , $journey] = $this->hotelDenganPaketDanJourney();

        $this->get(route('journeys.show', $journey->slug))
            ->assertOk()
            ->assertSee('href="'.route('hotel.show', ['hotel' => $hotel->slug, 'dari' => $journey->slug]).'"', false);
    }

    public function test_admin_yang_sudah_login_dari_halaman_masuk_dikembalikan_ke_dashboard_admin_bukan_beranda_publik(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.autentikasi.masuk'))->assertRedirect(route('admin.dashboard.indeks'));
        $this->get(route('admin.autentikasi.lupa-sandi'))->assertRedirect(route('admin.dashboard.indeks'));
        $this->get(route('admin.autentikasi.atur-ulang-sandi', 'token-apa-saja'))->assertRedirect(route('admin.dashboard.indeks'));
    }

    public function test_guide_dan_tamu_yang_sudah_login_dari_halaman_masuk_admin_dikembalikan_ke_berandanya_masing_masing(): void
    {
        $this->actingAs(User::factory()->create(['jabatan' => 'guide']));
        $this->get(route('admin.autentikasi.masuk'))->assertRedirect(route('personel.dashboard'));

        $this->actingAs(User::factory()->tamu()->create());
        $this->get(route('admin.autentikasi.masuk'))->assertRedirect(route('home'));
    }

    public function test_token_rating_tidak_dikenal_berstatus_404_dengan_penjelasan_ramah(): void
    {
        $this->get(route('rating.formulir', 'token-ngawur'))
            ->assertNotFound()
            ->assertSee('Tautan ulasan tidak valid');
    }

    public function test_alamat_lama_berawalan_id_dialihkan_permanen_ke_alamat_tanpa_awalan(): void
    {
        $this->get('/id/journey')->assertStatus(301)->assertRedirect('/journey');
        $this->get('/id/journey/dieng-highland-escape')->assertStatus(301)->assertRedirect('/journey/dieng-highland-escape');
        $this->get('/id/galeri')->assertStatus(301)->assertRedirect('/galeri');
        $this->get('/id/artikel')->assertStatus(301)->assertRedirect('/artikel');
        $this->get('/id')->assertStatus(301)->assertRedirect('/');
    }

    public function test_alamat_berawalan_id_yang_bukan_halaman_publik_tetap_404(): void
    {
        $this->get('/id/admin')->assertNotFound();
        $this->get('/id/tidak-ada')->assertNotFound();
        $this->get('/id/journey/a/b')->assertNotFound();
    }

    public function test_menu_navbar_di_halaman_detail_menuju_bagian_di_beranda_bukan_anchor_mati(): void
    {
        $journey = Perjalanan::factory()->create(['jenis' => 'journey', 'slug' => 'detail-menu-uji']);

        $html = $this->get(route('journeys.show', $journey->slug))->assertOk()->getContent();

        foreach (['journeys', 'paket', 'galeri', 'kontak'] as $bagian) {
            $this->assertStringContainsString('href="'.url_beranda($bagian).'"', $html, "menu {$bagian} harus menuju beranda");
            $this->assertStringNotContainsString('href="#'.$bagian.'"', $html, "menu {$bagian} tidak boleh anchor lokal di halaman detail");
        }
    }

    public function test_menu_navbar_di_beranda_memakai_anchor_lokal(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        foreach (['journeys', 'paket', 'galeri', 'kontak'] as $bagian) {
            $this->assertStringContainsString('href="#'.$bagian.'"', $html);
        }
    }

    public function test_halaman_arsip_hanya_menaut_lokal_ke_bagiannya_sendiri(): void
    {
        $html = $this->get(route('journeys.index'))->assertOk()->getContent();
        $this->assertStringContainsString('href="#journeys"', $html);
        $this->assertStringContainsString('href="'.url_beranda('paket').'"', $html);
        $this->assertStringNotContainsString('href="#paket"', $html);

        $html = $this->get(route('gallery.index'))->assertOk()->getContent();
        $this->assertStringContainsString('href="#galeri"', $html);
        $this->assertStringNotContainsString('href="#journeys"', $html);
    }

    public function test_menu_artikel_dan_favorit_tersembunyi_selama_bagiannya_kosong_dan_muncul_bila_ada_isinya(): void
    {
        $kosong = $this->get(route('home'))->assertOk()->getContent();
        $this->assertStringNotContainsString('href="#artikel"', $kosong);
        $this->assertStringNotContainsString('href="#testimoni"', $kosong);

        Artikel::factory()->create(['published_at' => now()->subDay()]);
        Ulasan::factory()->create(['disetujui' => true, 'izin_tampil' => true]);

        $terisi = $this->get(route('home'))->assertOk()->getContent();
        $this->assertStringContainsString('href="#artikel"', $terisi);
        $this->assertStringContainsString('href="#testimoni"', $terisi);
        $this->assertStringContainsString('id="artikel"', $terisi);
        $this->assertStringContainsString('id="testimoni"', $terisi);
    }

    public function test_beranda_hanya_bertanda_aktif_di_beranda(): void
    {
        $this->get(route('home'))->assertOk()->assertSee('aria-current="page"', false);

        $lain = Perjalanan::factory()->create(['jenis' => 'journey', 'slug' => 'aktif-uji']);
        $html = $this->get(route('journeys.show', $lain->slug))->assertOk()->getContent();
        $menu = substr($html, strpos($html, 'layout.navbar.menu-desktop'), 2500);
        $this->assertStringNotContainsString('aria-current="page"', $menu);
        $this->assertStringNotContainsString('nav-active', $menu);
    }

    public function test_tombol_kembali_ke_atas_selalu_punya_tujuan_di_halaman_itu(): void
    {
        $journey = Perjalanan::factory()->create(['jenis' => 'journey', 'slug' => 'atas-uji']);

        $beranda = $this->get(route('home'))->assertOk()->getContent();
        $this->assertStringContainsString('href="#beranda" aria-label="Kembali ke atas"', $beranda);
        $this->assertStringContainsString('id="beranda"', $beranda);

        foreach ([route('journeys.index'), route('gallery.index'), route('articles.index'), route('journeys.show', $journey->slug)] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertStringContainsString('href="#atas-halaman" aria-label="Kembali ke atas"', $html, $url);
            $this->assertStringContainsString('id="atas-halaman"', $html, $url);
        }
    }
}
