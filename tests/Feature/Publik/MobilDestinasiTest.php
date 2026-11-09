<?php

namespace Tests\Feature\Publik;

use App\Models\Perjalanan;
use App\Models\User;
use Tests\TestCase;

class MobilDestinasiTest extends TestCase
{
    private function journey(string $nama): Perjalanan
    {
        return Perjalanan::query()->where('nama', $nama)->firstOrFail();
    }

    public function test_kartu_journey_dan_paket_di_beranda_tidak_menampilkan_mobil(): void
    {
        foreach ([route('journeys.index'), '/'] as $halaman) {
            $this->get($halaman)
                ->assertOk()
                ->assertDontSee('data-kartu-mobil', false)
                ->assertDontSee('Toyota Land Cruiser FJ40')
                ->assertDontSee('Toyota Hilux Double Cabin 2020')
                ->assertDontSee('Toyota Rush TRD 2020');
        }
    }

    public function test_detail_journey_tidak_menyebut_mobil_tertentu_dan_memakai_keterangan_kendaraan_umum(): void
    {
        $papandayan = $this->journey('PAPANDAYAN CRATER JOURNEY');

        $this->get(route('journeys.show', $papandayan->slug))
            ->assertOk()
            ->assertDontSee('data-mobil-destinasi', false)
            ->assertDontSee('Mobil untuk perjalanan ini')
            ->assertDontSee('Toyota Hilux Double Cabin 2020')
            ->assertDontSee('Double cabin 4x4 yang kuat untuk jalan berbatu dan menanjak menuju area parkir kawah.')
            ->assertSee('Jeep adventure dan SUV privat');
    }

    public function test_detail_journey_bahasa_inggris_tidak_menyebut_mobil_tertentu(): void
    {
        $ciwidey = $this->journey('CIWIDEY & RANCABALI ESCAPE');

        $this->get('/en/journey/'.$ciwidey->slug)
            ->assertOk()
            ->assertDontSee('Vehicle for this trip')
            ->assertDontSee('Toyota Kijang Innova Venturer');
    }

    public function test_formulir_booking_tidak_menampilkan_mobil_dan_batas_tamu_mengikuti_journey(): void
    {
        $bromo = $this->journey('BROMO SUNRISE JOURNEY');

        $this->actingAs(User::factory()->tamu()->create())
            ->get(route('booking.buat', ['destination' => $bromo->nama]))
            ->assertOk()
            ->assertDontSee('data-mobil-destinasi', false)
            ->assertDontSee('Toyota Land Cruiser FJ40')
            ->assertSee('batas: '.$bromo->maks_orang.',', false);
    }

    public function test_mobil_utama_tetap_tersimpan_untuk_admin_walau_tidak_tampil_ke_tamu(): void
    {
        $bromo = $this->journey('BROMO SUNRISE JOURNEY');

        $this->assertSame('Toyota Land Cruiser FJ40', $bromo->kendaraan->nama);
        $this->assertNotEmpty($bromo->alasan_kendaraan);
    }
}
