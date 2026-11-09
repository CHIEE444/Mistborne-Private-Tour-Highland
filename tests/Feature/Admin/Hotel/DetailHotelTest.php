<?php

namespace Tests\Feature\Admin\Hotel;

use App\Models\Hotel;
use App\Models\Perjalanan;
use App\Models\User;
use Tests\TestCase;

class DetailHotelTest extends TestCase
{
    public function test_tamu_diarahkan_ke_halaman_masuk(): void
    {
        $hotel = Hotel::factory()->create();

        $this->get(route('admin.hotel.detail', $hotel))->assertRedirect(route('admin.autentikasi.masuk'));
    }

    public function test_halaman_menampilkan_detail_hotel(): void
    {
        $hotel = Hotel::factory()->create([
            'nama' => 'Plataran Bromo',
            'area' => 'Tosari, Pasuruan',
            'alamat' => 'Jl. Tosari Raya No. 1',
            'deskripsi' => 'Resor eksklusif dataran tinggi Bromo.',
            'nama_pic' => 'Bu Rina',
            'tipe_kamar' => [['nama' => 'Private Pool Villa', 'kapasitas' => 4]],
            'fasilitas' => ['spa', 'kolam_renang'],
            'kebijakan_pembatalan' => 'Gratis batal sampai H-3.',
        ]);
        $hotel->perjalanan()->attach(Perjalanan::factory()->create(['nama' => 'UJI HIDDEN VALLEY JOURNEY']));

        $this->actingAs(User::factory()->create())
            ->get(route('admin.hotel.detail', $hotel))
            ->assertOk()
            ->assertSee('Plataran Bromo')
            ->assertSee('Tosari, Pasuruan')
            ->assertSee('Jl. Tosari Raya No. 1')
            ->assertSee('Resor eksklusif dataran tinggi Bromo.')
            ->assertSee('Bu Rina')
            ->assertSee('Private Pool Villa')
            ->assertSee('Spa')
            ->assertSee('Gratis batal sampai H-3.')
            ->assertSee('Uji Hidden Valley Journey')
            ->assertDontSee('Skor kesiapan');
    }

    public function test_data_kosong_tampil_sebagai_keadaan_kosong(): void
    {
        $hotel = Hotel::factory()->tanpaWhatsapp()->create(['fasilitas' => [], 'tarif_mulai' => null, 'tarif_sampai' => null]);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.hotel.detail', $hotel))
            ->assertOk()
            ->assertSee('Belum diisi')
            ->assertSee('Fasilitas belum diisi.')
            ->assertSee('Belum ditautkan ke journey');
    }
}
