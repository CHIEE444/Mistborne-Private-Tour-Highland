<?php

namespace Tests\Feature\Publik;

use App\Models\JadwalKeberangkatan;
use App\Models\Perjalanan;
use Tests\TestCase;

class DetailJourneyTest extends TestCase
{
    public function test_halaman_detail_menampilkan_semua_data_dari_admin(): void
    {
        $journey = Perjalanan::factory()->create([
            'highlights' => ['Sunrise Golden Hour'],
            'itinerary' => [['t' => 'Hari 1 · 03.30', 'x' => 'Jemput di hotel']],
            'praktis' => ['pickup' => 'Hotel Wonosobo', 'start' => 'Jam 03.30', 'capacity' => 'Maks. 6', 'tips' => 'Bawa jaket'],
        ]);
        $tanggal = JadwalKeberangkatan::factory()->create(['perjalanan_id' => $journey->id, 'tanggal' => now()->addDays(10)]);

        $this->get(route('journeys.show', $journey->slug))
            ->assertOk()
            ->assertSee($journey->nama)
            ->assertSee($journey->deskripsi)
            ->assertSee('Sunrise Golden Hour')
            ->assertSee('Jemput di hotel')
            ->assertSee('Hotel Wonosobo')
            ->assertSee('Bawa jaket')
            ->assertSee($tanggal->tanggal->translatedFormat('d M Y'))
            ->assertSee(route('booking.buat', ['destination' => $journey->nama]), false);
    }

    public function test_slug_tidak_dikenal_menghasilkan_404(): void
    {
        $this->get(route('journeys.show', 'tidak-ada'))->assertNotFound();
    }

    public function test_halaman_journey_menampilkan_tab_region_dari_database_dan_menaut_ke_detail(): void
    {
        $journey = Perjalanan::factory()->create(['region' => 'jawa-barat', 'tampil_beranda' => false]);

        $this->get(route('journeys.index'))
            ->assertOk()
            ->assertSee('Jawa Barat')
            ->assertSee(route('journeys.show', $journey->slug), false);
    }

    public function test_journey_kustom_tanpa_tanggal_tetap_dan_menuju_form_kustom(): void
    {
        $kustom = Perjalanan::factory()->create(['tone' => 'custom', 'nama' => 'CUSTOM TEST JOURNEY']);
        JadwalKeberangkatan::factory()->create(['perjalanan_id' => $kustom->id, 'tanggal' => now()->addDays(20)]);

        $this->get(route('journeys.show', $kustom->slug))
            ->assertOk()
            ->assertSee('Jadwal fleksibel')
            ->assertSee('Sesuai rancangan Anda')
            ->assertDontSee('Mulai dari')
            ->assertDontSee('Tanggal tersedia')
            ->assertSee(route('rancang-trip-kustom'), false)
            ->assertSee('Rancang Trip Kustom');
    }

    public function test_booking_journey_kustom_dialihkan_ke_form_kustom(): void
    {
        $kustom = Perjalanan::factory()->create(['tone' => 'custom', 'nama' => 'CUSTOM TEST JOURNEY']);

        $this->get(route('booking.buat', ['destination' => $kustom->nama]))
            ->assertRedirect(route('rancang-trip-kustom'));
    }

    public function test_detail_journey_menampilkan_rincian_termasuk_dan_tidak_termasuk(): void
    {
        $journey = Perjalanan::factory()->create();

        $this->get(route('journeys.show', $journey->slug))
            ->assertOk()
            ->assertSee('Termasuk dalam harga')
            ->assertSee('Pemandu lokal dari MISTBORNE')
            ->assertSee('Tidak termasuk (dibayar tamu)')
            ->assertSee('Tiket masuk objek wisata')
            ->assertSee('Penginapan minimal bintang 4 (maks. 4 orang per kamar)')
            ->assertSee('Makan dan minum')
            ->assertSee('Oleh-oleh')
            ->assertSee('Aktivitas opsional di luar itinerary')
            ->assertSee('Pengeluaran pribadi')
            ->assertDontSee('Penginapan (akomodasi)')
            ->assertSee('Tiket masuk dan penginapan sudah termasuk');
    }

    public function test_info_praktis_selalu_memuat_keterangan_kendaraan(): void
    {
        $journey = Perjalanan::factory()->create(['praktis' => []]);

        $this->get(route('journeys.show', $journey->slug))
            ->assertOk()
            ->assertSee('Info Praktis')
            ->assertSee('Jeep adventure dan SUV privat');
    }

    public function test_ikon_highlight_mengikuti_topik_teks_aslinya(): void
    {
        $this->assertSame('matahari', Perjalanan::ikonHighlight('Golden sunrise di Bukit Sikunir'));
        $this->assertSame('kawah', Perjalanan::ikonHighlight('Kawah Sikidang yang masih beruap'));
        $this->assertSame('air', Perjalanan::ikonHighlight('Telaga Warna & Telaga Pengilon'));
        $this->assertSame('candi', Perjalanan::ikonHighlight('Candi Arjuna berusia ribuan tahun'));
        $this->assertSame('teh', Perjalanan::ikonHighlight('Kebun teh Tambi berhawa sejuk'));
        $this->assertSame('air-terjun', Perjalanan::ikonHighlight('Air Terjun Madakaripura di tebing melingkar'));
        $this->assertSame('kilau', Perjalanan::ikonHighlight('Pengalaman tak terlupakan'));
    }
}
