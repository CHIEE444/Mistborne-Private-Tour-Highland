<?php

namespace Tests\Feature\Admin;

use App\Bantuan\KonversiGambar;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BatasResolusiFotoTest extends TestCase
{
    public function test_foto_kendaraan_beresolusi_terlalu_besar_ditolak_sebelum_didekode(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create())->post(route('admin.kendaraan.simpan'), [
            'nama' => 'Hiace Besar', 'jenis' => 'minibus', 'plat_nomor' => 'B 1 AB', 'kapasitas' => 12,
            'tahun' => 2022, 'warna' => 'Putih', 'status' => 'siap',
            'foto' => UploadedFile::fake()->image('besar.jpg', 8001, 100),
        ])->assertSessionHasErrors('foto');
    }

    public function test_foto_hotel_dan_galeri_beresolusi_terlalu_besar_ditolak(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create())->post(route('admin.hotel.simpan'), [
            'nama' => 'Hotel Besar', 'bintang' => 4, 'area' => 'Dieng', 'jarak_destinasi' => '± 1 jam ke Dieng', 'aktif' => '1',
            'kapasitas_kamar' => 2, 'jam_check_in' => '14:00', 'jam_check_out' => '12:00',
            'foto' => UploadedFile::fake()->image('besar.jpg', 100, 8001),
            'galeri_baru' => [['berkas' => UploadedFile::fake()->image('g.jpg', 8001, 100), 'label' => 'kamar']],
        ])->assertSessionHasErrors(['foto', 'galeri_baru.0.berkas']);
    }

    public function test_konversi_menaikkan_batas_memori_sesuai_resolusi_lalu_menghasilkan_webp(): void
    {
        Storage::fake('public');
        $batasAwal = ini_get('memory_limit');
        ini_set('memory_limit', '256M');

        try {
            $path = KonversiGambar::sampul(UploadedFile::fake()->image('foto.jpg', 4000, 3000), 'uji');
        } finally {
            ini_set('memory_limit', $batasAwal);
        }

        $this->assertStringEndsWith('.webp', $path);
        Storage::disk('public')->assertExists($path);
        $this->assertSame([1200, 630], array_slice(getimagesize(Storage::disk('public')->path($path)), 0, 2));
    }
}
