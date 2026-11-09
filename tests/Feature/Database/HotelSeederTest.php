<?php

namespace Tests\Feature\Database;

use App\Models\Hotel;
use App\Models\Perjalanan;
use Database\Seeders\HotelSeeder;
use Tests\TestCase;

class HotelSeederTest extends TestCase
{
    public function test_seeder_membuat_hotel_mitra_dan_menautkannya_ke_journey(): void
    {
        $this->seed(HotelSeeder::class);

        $hotel = Hotel::where('nama', 'Artotel Wanasaba')->firstOrFail();
        $this->assertSame(4, $hotel->bintang);
        $this->assertTrue($hotel->aktif);
        $this->assertTrue($hotel->perjalanan()->where('nama', 'DIENG HIGHLAND ESCAPE')->exists());
        $this->assertGreaterThanOrEqual(7, Hotel::count());
    }

    public function test_seeder_ulang_tidak_menimpa_data_yang_sudah_diubah_admin(): void
    {
        $this->seed(HotelSeeder::class);
        $hotel = Hotel::where('nama', 'Artotel Wanasaba')->firstOrFail();
        $hotel->update(['whatsapp' => '081211112222', 'tarif_mulai' => 650000, 'aktif' => false, 'tipe_kamar' => [['nama' => 'Suite Baru', 'kapasitas' => 3]]]);

        $this->seed(HotelSeeder::class);

        $hotel->refresh();
        $this->assertSame('081211112222', $hotel->whatsapp);
        $this->assertSame(650000, $hotel->tarif_mulai);
        $this->assertFalse($hotel->aktif);
        $this->assertSame('Suite Baru', $hotel->tipe_kamar[0]['nama']);
    }

    public function test_foto_atria_dipasang_dari_public_images_dan_tidak_menimpa_unggahan_admin(): void
    {
        $this->seed(HotelSeeder::class);
        $this->assertSame('/images/Atria Hotel Magelang.jpg', Hotel::where('nama', 'Atria Hotel Magelang')->value('foto'));
        $this->assertFileExists(public_path('images/Atria Hotel Magelang.jpg'));

        Hotel::where('nama', 'Atria Hotel Magelang')->update(['foto' => '/storage/hotel/unggahan-admin.webp']);
        $this->seed(HotelSeeder::class);
        $this->assertSame('/storage/hotel/unggahan-admin.webp', Hotel::where('nama', 'Atria Hotel Magelang')->value('foto'));

        Hotel::where('nama', 'Atria Hotel Magelang')->update(['foto' => null]);
        $this->seed(HotelSeeder::class);
        $this->assertSame('/images/Atria Hotel Magelang.jpg', Hotel::where('nama', 'Atria Hotel Magelang')->value('foto'));
    }

    public function test_seeder_ulang_tidak_menghidupkan_hotel_yang_sengaja_dihapus_atau_membuat_ganda(): void
    {
        $this->seed(HotelSeeder::class);
        $jumlah = Hotel::count();
        Hotel::where('nama', 'Forest Hills Ciwidey')->firstOrFail()->delete();

        $this->seed(HotelSeeder::class);

        $this->assertSame($jumlah - 1, Hotel::count());
        $this->assertTrue(Hotel::withTrashed()->where('nama', 'Forest Hills Ciwidey')->sole()->trashed());
    }

    public function test_seeder_ulang_tidak_menautkan_lagi_journey_yang_sengaja_dilepas_admin(): void
    {
        $this->seed(HotelSeeder::class);
        $hotel = Hotel::where('nama', 'Artotel Wanasaba')->firstOrFail();
        $journey = Perjalanan::where('nama', 'DIENG HIGHLAND ESCAPE')->firstOrFail();
        $hotel->perjalanan()->detach($journey->id);

        $this->seed(HotelSeeder::class);

        $this->assertFalse($hotel->perjalanan()->whereKey($journey->id)->exists());
    }

    public function test_journey_bromo_memakai_the_singhasari_resort_bintang_lima_dengan_foto(): void
    {
        $bromo = Perjalanan::query()->where('nama', 'BROMO SUNRISE JOURNEY')->firstOrFail();
        $lama = Hotel::factory()->create(['nama' => 'Plataran Bromo']);
        $lama->perjalanan()->sync([$bromo->id]);

        $this->seed(HotelSeeder::class);

        $hotel = Hotel::query()->where('nama', 'The Singhasari Resort')->firstOrFail();

        $this->assertSame(5, $hotel->bintang);
        $this->assertSame('/images/hotel/singhasari-resort-utama.webp', $hotel->foto);
        $this->assertCount(6, $hotel->galeri);
        $this->assertFileExists(public_path('images/hotel/singhasari-resort-utama.webp'));
        $this->assertSame(['The Singhasari Resort'], $bromo->hotel()->pluck('nama')->all());
        $this->assertNotNull($lama->fresh());
    }

    public function test_setiap_hotel_aktif_punya_foto_utama_dan_galeri_hd_yang_filenya_ada(): void
    {
        $this->seed(HotelSeeder::class);

        $hotel = Hotel::query()->aktif()->get();
        $this->assertNotEmpty($hotel);

        foreach ($hotel as $item) {
            $this->assertNotEmpty($item->foto, "{$item->nama} belum punya foto utama");
            $this->assertGreaterThanOrEqual(4, count($item->galeri ?? []), "{$item->nama} galerinya kurang lengkap");

            foreach ([$item->foto, ...collect($item->galeri)->pluck('foto')->all()] as $path) {
                if (str_starts_with($path, '/images/')) {
                    $this->assertFileExists(public_path(ltrim(rawurldecode($path), '/')), "File {$path} milik {$item->nama} tidak ada");
                }
            }
        }
    }

    public function test_galeri_unggahan_admin_tidak_dihapus_dan_foto_hd_ditambahkan_di_belakangnya(): void
    {
        $this->seed(HotelSeeder::class);
        $hotel = Hotel::query()->where('nama', 'Atria Hotel Magelang')->firstOrFail();
        $hotel->update(['galeri' => [['foto' => '/storage/hotel/galeri/admin.webp', 'label' => 'kamar']]]);

        $this->seed(HotelSeeder::class);

        $galeri = $hotel->fresh()->galeri;
        $this->assertSame('/storage/hotel/galeri/admin.webp', $galeri[0]['foto']);
        $this->assertGreaterThan(1, count($galeri));
        $this->assertLessThanOrEqual(Hotel::MAKS_GALERI, count($galeri));
    }
}
