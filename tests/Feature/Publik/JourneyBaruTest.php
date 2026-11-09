<?php

namespace Tests\Feature\Publik;

use App\Models\Hotel;
use App\Models\Perjalanan;
use Database\Seeders\HotelSeeder;
use Tests\TestCase;

class JourneyBaruTest extends TestCase
{
    public function test_journey_baru_tersedia_lengkap_dengan_harga_patokan_dan_jadwal(): void
    {
        // Harga patokan = biaya pokok (resources/data/biaya-perjalanan.php) + keuntungan ±10%, maksimal Rp500.000.
        foreach (['LEMBANG & TANGKUBAN PARAHU JOURNEY' => 4_850_000, 'BOROBUDUR & MERAPI SUNRISE JOURNEY' => 5_050_000] as $nama => $harga) {
            $journey = Perjalanan::query()->where('nama', $nama)->firstOrFail();

            $this->assertSame($harga, $journey->harga);
            $this->assertSame(2, $journey->durasi_hari);
            $this->assertNotEmpty($journey->highlights);
            $this->assertNotEmpty($journey->itinerary);
            $this->assertNotEmpty($journey->praktis['vehicle']);
            $this->assertTrue($journey->jadwal()->exists());
        }
    }

    public function test_setiap_journey_punya_mobil_utama_untuk_admin_tetapi_tidak_menampilkannya_ke_tamu(): void
    {
        $this->seed(HotelSeeder::class);

        foreach (Perjalanan::query()->journey()->whereNotNull('harga')->where('template_pesanan_kustom', false)->where('tone', '<>', 'custom')->get() as $journey) {
            $this->assertNotNull($journey->kendaraan, "{$journey->nama} belum punya mobil utama");

            $this->get(route('journeys.show', $journey->slug))
                ->assertOk()
                ->assertDontSee('Mobil untuk perjalanan ini')
                ->assertDontSee($journey->kendaraan->namaUntukTamu())
                ->assertDontSee('Rekomendasi Kendaraan')
                ->assertDontSee('Taft');
        }
    }

    public function test_hotel_journey_baru_minimal_bintang_empat_lengkap_dengan_peta_dan_fasilitas(): void
    {
        $this->seed(HotelSeeder::class);

        $lembang = Perjalanan::query()->where('nama', 'LEMBANG & TANGKUBAN PARAHU JOURNEY')->firstOrFail();
        $borobudur = Perjalanan::query()->where('nama', 'BOROBUDUR & MERAPI SUNRISE JOURNEY')->firstOrFail();

        $this->assertSame(['The Lodge Maribaya'], $lembang->hotel()->pluck('nama')->all());
        $this->assertSame(['Atria Hotel Magelang'], $borobudur->hotel()->pluck('nama')->all());

        $this->get(route('journeys.show', $borobudur->slug))
            ->assertOk()
            ->assertSee('Atria Hotel Magelang')
            ->assertSee('google.com/maps/search', false)
            ->assertSee('Fasilitas yang didapat');

        Hotel::query()->each(function (Hotel $hotel): void {
            $this->assertContains($hotel->bintang, Hotel::BINTANG_DIIZINKAN);
            $this->assertNotEmpty($hotel->labelFasilitas());
        });
    }

    public function test_rekomendasi_penginapan_lama_yang_belum_terverifikasi_sudah_dibersihkan(): void
    {
        $teks = Perjalanan::query()->whereNotNull('rekomendasi_penginapan')->pluck('rekomendasi_penginapan')->implode(' ');

        $this->assertStringNotContainsString('Bromo Permai', $teks);
        $this->assertStringNotContainsString('Zaada', $teks);
    }
}
