<?php

namespace Tests\Feature\Publik;

use App\Models\Hotel;
use App\Models\Perjalanan;
use App\Models\PermintaanPesanan;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class HotelDiDetailJourneyTest extends TestCase
{
    public function test_detail_journey_menampilkan_hotel_aktif_yang_diurus_mistborne(): void
    {
        $journey = Perjalanan::factory()->create();
        $aktif = Hotel::factory()->create(['nama' => 'Hotel Aktif Uji', 'area' => 'Ngadisari, Bromo', 'bintang' => 4, 'tautan_peta' => 'https://maps.google.com/?q=uji', 'fasilitas' => ['sarapan', 'air_panas'], 'foto' => '/storage/hotel/uji.webp']);
        $nonaktif = Hotel::factory()->nonaktif()->create(['nama' => 'Hotel Nonaktif Uji']);
        $lain = Hotel::factory()->create(['nama' => 'Hotel Journey Lain']);
        $journey->hotel()->attach([$aktif->id, $nonaktif->id]);

        $this->get(route('journeys.show', $journey->slug))
            ->assertOk()
            ->assertSee('Akomodasi Selama Perjalanan')
            ->assertSee('Hotel Aktif Uji')
            ->assertSee('Ngadisari, Bromo')
            ->assertSee('★★★★')
            ->assertSee('Sarapan')
            ->assertSee('Air panas')
            ->assertSee('Lihat lokasi di Google Maps')
            ->assertSee('Fasilitas yang didapat')
            ->assertSee('Jumlah kamar menyesuaikan jumlah tamu')
            ->assertSee('/storage/hotel/uji.webp', false)
            ->assertDontSee('Hotel Nonaktif Uji')
            ->assertDontSee('Hotel Journey Lain');
    }

    public function test_hotel_tanpa_foto_memakai_penampung_dan_journey_tanpa_hotel_menyembunyikan_bagian(): void
    {
        $dengan = Perjalanan::factory()->create();
        $tanpa = Perjalanan::factory()->create();
        $dengan->hotel()->attach(Hotel::factory()->create(['nama' => 'Hotel Tanpa Foto', 'foto' => null])->id);

        $this->get(route('journeys.show', $dengan->slug))->assertOk()->assertSee('Hotel Tanpa Foto');
        $this->get(route('journeys.show', $tanpa->slug))->assertOk()->assertDontSee('Akomodasi Selama Perjalanan');
    }

    public function test_admin_mengunggah_foto_hotel_dan_foto_lama_diganti(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();
        $data = [
            'nama' => 'Hotel Foto Uji', 'bintang' => 4, 'area' => 'Dieng, Jawa Tengah', 'jarak_destinasi' => '± 1 jam berkendara ke Dieng', 'aktif' => '1',
            'kapasitas_kamar' => 2, 'jam_check_in' => '14:00', 'jam_check_out' => '12:00',
            'foto' => UploadedFile::fake()->image('hotel.jpg', 1400, 900),
        ];

        $this->actingAs($admin)->post(route('admin.hotel.simpan'), $data)->assertSessionHasNoErrors();
        $hotel = Hotel::query()->where('nama', 'Hotel Foto Uji')->firstOrFail();
        $this->assertStringStartsWith('/storage/hotel/', (string) $hotel->foto);
        Storage::disk('public')->assertExists(ltrim(str_replace('/storage/', '', $hotel->foto), '/'));
        $fotoLama = $hotel->foto;

        $this->actingAs($admin)->put(route('admin.hotel.perbarui', $hotel), [...$data, 'foto' => UploadedFile::fake()->image('baru.jpg', 1400, 900)])->assertSessionHasNoErrors();
        $hotel->refresh();
        $this->assertNotSame($fotoLama, $hotel->foto);
        Storage::disk('public')->assertMissing(ltrim(str_replace('/storage/', '', $fotoLama), '/'));
    }

    public function test_foto_hotel_harus_gambar(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.hotel.simpan'), [
            'nama' => 'Hotel Foto Salah', 'bintang' => 4, 'area' => 'Dieng, Jawa Tengah', 'aktif' => '1',
            'foto' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('foto');
    }

    public function test_hotel_tanpa_tautan_peta_memakai_pencarian_google_maps(): void
    {
        $hotel = Hotel::factory()->create(['nama' => 'Hotel Peta Uji', 'area' => 'Licin, Banyuwangi', 'tautan_peta' => null]);

        $this->assertSame('https://www.google.com/maps/search/?api=1&query=Hotel%20Peta%20Uji%20Licin%2C%20Banyuwangi', $hotel->petaUrl());
    }

    public function test_hotel_mitra_minimal_bintang_empat_dan_boleh_bintang_lima(): void
    {
        $admin = User::factory()->create();
        $dasar = ['nama' => 'Hotel Bintang Tiga', 'area' => 'Dieng, Jawa Tengah', 'jarak_destinasi' => '± 1 jam berkendara ke Dieng', 'kapasitas_kamar' => 2, 'jam_check_in' => '14:00', 'jam_check_out' => '12:00', 'aktif' => '1'];

        $this->actingAs($admin)->post(route('admin.hotel.simpan'), [...$dasar, 'bintang' => 3])->assertSessionHasErrors('bintang');
        $this->actingAs($admin)->post(route('admin.hotel.simpan'), [...$dasar, 'bintang' => 4])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('admin.hotel.simpan'), [...$dasar, 'nama' => 'Hotel Bintang Lima', 'bintang' => 5])->assertSessionHasNoErrors();
        $this->assertSame(5, Hotel::query()->where('nama', 'Hotel Bintang Lima')->value('bintang'));
        $this->actingAs($admin)->post(route('admin.hotel.simpan'), [...$dasar, 'nama' => 'Hotel Bintang Enam', 'bintang' => 6])->assertSessionHasErrors('bintang');
    }

    public function test_form_booking_dan_form_pesanan_menampilkan_hotel_dengan_jumlah_kamar_mengikuti_tamu(): void
    {
        $journey = Perjalanan::factory()->create();
        $journey->hotel()->attach(Hotel::factory()->create(['nama' => 'Hotel Form Uji', 'fasilitas' => ['sarapan', 'wifi']])->id);
        $permintaan = PermintaanPesanan::query()->create([
            'token' => Str::random(64),
            'whatsapp' => '081234567890',
            'status' => 'menunggu_tamu',
            'token_berlaku_sampai' => now()->addDay(),
        ]);

        $this->actingAs(User::factory()->create(['jabatan' => 'tamu']))->get(route('booking.buat', ['destination' => $journey->nama]))
            ->assertOk()
            ->assertSee('Akomodasi Selama Perjalanan')
            ->assertSee('Hotel Form Uji')
            ->assertSee('Fasilitas yang didapat')
            ->assertSee('Lihat lokasi di Google Maps')
            ->assertSee('Untuk :orang tamu, Anda mendapat :kamar kamar.');

        $this->get(route('form-pesanan.tampil', $permintaan->token))
            ->assertOk()
            ->assertSee('Akomodasi selama perjalanan')
            ->assertSee('Hotel Form Uji')
            ->assertSee('Jumlah kamar menyesuaikan jumlah tamu (4 orang per kamar).');
    }

    public function test_hanya_satu_hotel_disorot_per_journey_dan_hotel_bertanda_sorotan_didahulukan(): void
    {
        $journey = Perjalanan::factory()->create();
        $tertua = Hotel::factory()->create(['nama' => 'Hotel Tertua Uji']);
        $disorot = Hotel::factory()->create(['nama' => 'Hotel Disorot Uji', 'sorotan' => true]);
        $journey->hotel()->attach([$tertua->id, $disorot->id]);

        $this->assertSame(['Hotel Disorot Uji'], array_column($journey->hotelPublik(), 'nama'));

        $disorot->update(['sorotan' => false]);
        $this->assertSame(['Hotel Tertua Uji'], array_column($journey->hotelPublik(), 'nama'));

        $this->get(route('journeys.show', $journey->slug))->assertOk()->assertSee('Hotel Tertua Uji')->assertDontSee('Hotel Disorot Uji');
    }

    public function test_perintah_impor_foto_memasang_foto_berdasarkan_id_atau_nama_hotel(): void
    {
        Storage::fake('public');
        $folder = sys_get_temp_dir().'/foto-hotel-uji-'.uniqid();
        mkdir($folder);
        $hotelA = Hotel::factory()->create(['nama' => 'Bromo Permai 1']);
        $hotelB = Hotel::factory()->create(['nama' => 'Hotel Nomor Dua']);
        UploadedFile::fake()->image('a.jpg', 1400, 900)->move($folder, 'bromo-permai-1.jpg');
        UploadedFile::fake()->image('b.jpg', 1400, 900)->move($folder, $hotelB->id.'.jpg');
        UploadedFile::fake()->image('c.jpg', 1400, 900)->move($folder, 'tidak-dikenal.jpg');

        $this->artisan('hotel:impor-foto', ['folder' => $folder])
            ->expectsOutputToContain('2 foto terpasang')
            ->expectsOutputToContain('tidak-dikenal.jpg')
            ->assertSuccessful();

        $this->assertStringStartsWith('/storage/hotel/', (string) $hotelA->fresh()->foto);
        $this->assertStringStartsWith('/storage/hotel/', (string) $hotelB->fresh()->foto);
        array_map('unlink', glob($folder.'/*'));
        rmdir($folder);
    }

    /**
     * @return array<string, mixed>
     */
    private function dataHotelGaleri(array $timpa = []): array
    {
        return [
            'nama' => 'Hotel Galeri Uji', 'bintang' => 4, 'area' => 'Dieng, Jawa Tengah', 'jarak_destinasi' => '± 1 jam berkendara ke Dieng', 'aktif' => '1',
            'kapasitas_kamar' => 2, 'jam_check_in' => '14:00', 'jam_check_out' => '12:00',
            ...$timpa,
        ];
    }

    public function test_admin_menambah_dan_menghapus_foto_galeri_hotel(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.hotel.simpan'), $this->dataHotelGaleri([
            'galeri_baru' => [
                ['berkas' => UploadedFile::fake()->image('kamar.jpg', 640, 480), 'label' => 'kamar'],
                ['berkas' => UploadedFile::fake()->image('kolam.jpg', 640, 480), 'label' => 'kolam_renang'],
            ],
        ]))->assertSessionHasNoErrors();

        $hotel = Hotel::query()->where('nama', 'Hotel Galeri Uji')->firstOrFail();
        $this->assertCount(2, $hotel->galeri);
        $this->assertSame(['kamar', 'kolam_renang'], array_column($hotel->galeri, 'label'));
        foreach ($hotel->galeri as $baris) {
            Storage::disk('public')->assertExists(ltrim(str_replace('/storage/', '', $baris['foto']), '/'));
        }

        $dihapus = $hotel->galeri[0]['foto'];
        $dipertahankan = $hotel->galeri[1]['foto'];

        $this->actingAs($admin)->put(route('admin.hotel.perbarui', $hotel), $this->dataHotelGaleri([
            'galeri_lama' => [
                ['foto' => $dihapus, 'label' => 'kamar', 'hapus' => '1'],
                ['foto' => $dipertahankan, 'label' => 'restoran'],
            ],
        ]))->assertSessionHasNoErrors();

        $hotel->refresh();
        $this->assertSame([['foto' => $dipertahankan, 'label' => 'restoran']], $hotel->galeri);
        Storage::disk('public')->assertMissing(ltrim(str_replace('/storage/', '', $dihapus), '/'));
        Storage::disk('public')->assertExists(ltrim(str_replace('/storage/', '', $dipertahankan), '/'));
    }

    public function test_galeri_hotel_ditolak_bila_melebihi_batas_atau_bukan_gambar_atau_kategori_asing(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();

        $terlalu = array_map(fn (int $n): array => ['berkas' => UploadedFile::fake()->image("f{$n}.jpg", 800, 600), 'label' => 'kamar'], range(1, Hotel::MAKS_GALERI + 1));
        $this->actingAs($admin)->post(route('admin.hotel.simpan'), $this->dataHotelGaleri(['galeri_baru' => $terlalu]))->assertSessionHasErrors('galeri_baru');

        $this->actingAs($admin)->post(route('admin.hotel.simpan'), $this->dataHotelGaleri(['galeri_baru' => [
            ['berkas' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'), 'label' => 'kamar'],
            ['berkas' => UploadedFile::fake()->image('a.jpg', 800, 600), 'label' => 'dapur-rahasia'],
        ]]))->assertSessionHasErrors(['galeri_baru.0.berkas', 'galeri_baru.1.label']);

        $this->assertSame(0, Hotel::query()->count());
    }

    public function test_detail_journey_menampilkan_galeri_foto_hotel_dengan_kategori(): void
    {
        $journey = Perjalanan::factory()->create();
        $hotel = Hotel::factory()->create([
            'nama' => 'Hotel Galeri Publik', 'foto' => '/storage/hotel/utama.webp',
            'galeri' => [['foto' => '/storage/hotel/galeri/kamar-uji.webp', 'label' => 'kamar'], ['foto' => '/storage/hotel/galeri/kolam-uji.webp', 'label' => 'kolam_renang']],
        ]);
        $journey->hotel()->attach($hotel->id);

        $this->get(route('journeys.show', $journey->slug))
            ->assertOk()
            ->assertSee('Galeri foto hotel')
            ->assertSee('/storage/hotel/utama.webp', false)
            ->assertSee('/storage/hotel/galeri/kamar-uji.webp', false)
            ->assertSee('/storage/hotel/galeri/kolam-uji.webp', false)
            ->assertSee('Kolam renang');
    }

    public function test_estimasi_perjalanan_hotel_tampil_satu_angka_tanpa_tanda_kurang_lebih(): void
    {
        $journey = Perjalanan::factory()->create();
        $journey->hotel()->attach(Hotel::factory()->create(['jarak_destinasi' => '± 30–45 menit berkendara ke Kawah Putih; ± 1 jam ke Situ Patenggang'])->id);

        $this->get(route('journeys.show', $journey->slug))
            ->assertOk()
            ->assertSee('45 menit berkendara ke Kawah Putih; 1 jam ke Situ Patenggang')
            ->assertDontSee('30–45 menit')
            ->assertDontSee('± 1 jam');
    }
}
