<?php

namespace Tests\Feature\Landing;

use App\Models\Perjalanan;
use Tests\TestCase;

/**
 * Beranda publik: badge durasi kartu journey, section "Tentang Kami" berbasis data,
 * deretan logo mitra, dan footer berlangganan.
 */
class BerandaTentangKamiTest extends TestCase
{
    public function test_badge_durasi_tiap_kartu_journey_sesuai_data_journey_itu(): void
    {
        $html = $this->get('/journey')->assertOk()->getContent();

        $journeys = Perjalanan::query()->journey()->where('template_pesanan_kustom', false)->get();
        $this->assertNotEmpty($journeys);

        foreach ($journeys as $journey) {
            $tautan = 'href="'.route('journeys.show', ['slug' => $journey->slug]).'"';
            $awalKartu = strpos($html, $tautan);
            $this->assertNotFalse($awalKartu, "Kartu {$journey->nama} tidak ditemukan");
            $kartu = substr($html, $awalKartu, strpos($html, '</article>', $awalKartu) - $awalKartu);

            $this->assertStringContainsString('data-durasi-journey', $kartu, "Badge durasi hilang di {$journey->nama}");
            $this->assertStringContainsString('<bdi>'.e($journey->labelDurasi()).'</bdi>', $kartu, "Durasi {$journey->nama} tidak sesuai data");
        }
    }

    public function test_tentang_kami_menampilkan_manifesto_janji_dan_kontak_tanpa_deret_angka(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('id="tentang-kami"', $html);
        $this->assertStringNotContainsString('Standar layanan MISTBORNE', $html);
        $this->assertStringContainsString('Jadwal mengikuti ritme Anda', $html);
        $this->assertStringContainsString('href="mailto:travelling@mistborne.id"', $html);
        $this->assertStringNotContainsString('sd-fakta__angka', $html);
        $this->assertStringNotContainsString('Armada siap jalan', $html);
    }

    public function test_bagian_mitra_sudah_dihapus_dari_beranda(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('Mitra MISTBORNE')
            ->assertDontSee('Bekerja sama dengan mitra lokal terpercaya')
            ->assertDontSee('images/mitra/', false);

        $this->assertDirectoryDoesNotExist(public_path('images/mitra'));
    }

    public function test_form_berlangganan_footer_dikirim_ke_email_mistborne(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('action="mailto:travelling@mistborne.id"', false)
            ->assertSee('placeholder="travelling@mistborne.id"', false);
    }
}
