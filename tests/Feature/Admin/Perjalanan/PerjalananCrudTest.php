<?php

namespace Tests\Feature\Admin\Perjalanan;

use App\Models\Perjalanan;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PerjalananCrudTest extends TestCase
{
    public function test_tamu_tidak_bisa_mengakses_daftar_journey(): void
    {
        $this->get(route('admin.perjalanan.indeks'))->assertRedirect(route('admin.autentikasi.masuk'));
    }

    public function test_admin_bisa_melihat_daftar_journey(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.perjalanan.indeks'))
            ->assertOk()
            ->assertSee('DIENG HIGHLAND ESCAPE');
    }

    public function test_admin_bisa_mencari_journey(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.perjalanan.indeks', ['cari' => 'Bromo']));

        $response->assertOk()
            ->assertSee('BROMO SUNRISE JOURNEY')
            ->assertDontSee('DIENG HIGHLAND ESCAPE');
    }

    public function test_admin_bisa_membuka_form_tambah_journey(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.perjalanan.buat'))->assertOk();
    }

    public function test_admin_bisa_membuka_form_ubah_journey(): void
    {
        $user = User::factory()->create();
        $journey = Perjalanan::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.perjalanan.ubah', $journey))
            ->assertOk()
            ->assertSee($journey->nama);
    }

    /**
     * @return array<string, mixed>
     */
    private function dataLengkap(array $timpa = []): array
    {
        return [
            'nama' => 'SEMERU SUNRISE JOURNEY',
            'region' => 'jawa-timur',
            'tempat' => 'Semeru, Jawa Timur',
            'tagline' => 'Sunrise di atas Ranu Kumbolo',
            'deskripsi' => 'Perjalanan privat menuju kaki Semeru.',
            'harga' => 2500000,
            'durasi_hari' => 2,
            'maks_orang' => 6,
            'urutan' => 0,
            'tampil_beranda' => '1',
            'img' => UploadedFile::fake()->image('sampul.jpg', 800, 1000),
            'tanggal_pertama' => now()->addDays(20)->toDateString(),
            'highlights' => ['Sunrise di Ranu Kumbolo'],
            'itinerary' => [['t' => 'Hari 1 · 03.30', 'x' => 'Jemput di hotel']],
            'praktis' => [
                'pickup' => 'Malang',
                'start' => 'Jam 03.30 WIB',
                'capacity' => 'Maks. 6 penumpang',
                'tips' => 'Bawa jaket tebal.',
            ],
            ...$timpa,
        ];
    }

    public function test_durasi_minimal_dua_hari_itinerary_tidak_melebihi_durasi_dan_kapasitas_maksimal_sepuluh(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('admin.perjalanan.simpan'), $this->dataLengkap(['durasi_hari' => 1]))->assertSessionHasErrors('durasi_hari');
        $this->actingAs($user)->post(route('admin.perjalanan.simpan'), $this->dataLengkap(['maks_orang' => 11]))->assertSessionHasErrors('maks_orang');
        $this->actingAs($user)
            ->post(route('admin.perjalanan.simpan'), $this->dataLengkap(['durasi_hari' => 2, 'itinerary' => [['t' => 'Hari 1 · 07.00', 'x' => 'Jemput'], ['t' => 'Hari 3 · 08.00', 'x' => 'Pulang']]]))
            ->assertSessionHasErrors('itinerary.1.t');
        $this->actingAs($user)
            ->post(route('admin.perjalanan.simpan'), $this->dataLengkap(['deskripsi' => 'Halo <script>alert(1)</script>']))
            ->assertSessionHasErrors('deskripsi');
        $this->actingAs($user)
            ->post(route('admin.perjalanan.simpan'), $this->dataLengkap(['durasi_hari' => 3, 'itinerary' => [['t' => 'Hari 3 · 08.00', 'x' => 'Pulang']]]))
            ->assertSessionHasNoErrors();
    }

    public function test_kolom_uang_dan_kolom_lebar_dirender_utuh_di_formulir_admin(): void
    {
        $html = $this->actingAs(User::factory()->create())->get(route('admin.perjalanan.buat'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<input[^>]*name="harga"/', $html);
        $this->assertStringNotContainsString('<x-admin.formulir', $html);
        $this->assertStringNotContainsString('old($name', $html);
        $this->assertMatchesRegularExpression('/<div class="[^"]*sm:col-span-2[^"]*">\s*<label[^>]*for="nama"/', $html);
    }

    public function test_admin_bisa_membuat_journey_baru_dengan_semua_field_dan_sampul(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.perjalanan.simpan'), $this->dataLengkap())
            ->assertRedirect(route('admin.perjalanan.indeks'));

        $journey = Perjalanan::where('nama', 'SEMERU SUNRISE JOURNEY')->firstOrFail();
        $this->assertSame('semeru-sunrise-journey', $journey->slug);
        $this->assertSame(1, $journey->jadwal()->count());
        $this->assertSame(['Sunrise di Ranu Kumbolo'], $journey->highlights);
        $this->assertStringStartsWith('/storage/perjalanan/sampul/', $journey->img);
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $journey->img));
    }

    public function test_kuota_tanggal_pertama_opsional_tersimpan_di_tanggal_tersedia(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('admin.perjalanan.simpan'), $this->dataLengkap(['kuota_pertama' => 0]))->assertSessionHasErrors('kuota_pertama');

        $this->actingAs($user)->post(route('admin.perjalanan.simpan'), $this->dataLengkap(['kuota_pertama' => 3]))->assertSessionHasNoErrors();
        $this->assertSame(3, Perjalanan::where('nama', 'SEMERU SUNRISE JOURNEY')->firstOrFail()->jadwal()->value('kuota'));

        $this->actingAs($user)->post(route('admin.perjalanan.simpan'), $this->dataLengkap(['nama' => 'SEMERU KEDUA', 'kuota_pertama' => '']))->assertSessionHasNoErrors();
        $this->assertNull(Perjalanan::where('nama', 'SEMERU KEDUA')->firstOrFail()->jadwal()->value('kuota'));
    }

    public function test_membuat_journey_tanpa_field_apa_pun_menolak_dengan_pesan_indonesia_per_field(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.perjalanan.simpan'), []);

        $response->assertSessionHasErrors([
            'nama' => 'Nama journey wajib diisi.',
            'region' => 'Pilih region destinasi.',
            'tempat' => 'Lokasi destinasi wajib diisi.',
            'tagline' => 'Tagline wajib diisi.',
            'deskripsi' => 'Deskripsi wajib diisi.',
            'harga' => 'Harga wajib diisi.',
            'durasi_hari' => 'Durasi wajib diisi.',
            'maks_orang' => 'Kapasitas maksimal wajib diisi.',
            'img' => 'Gambar sampul wajib diunggah.',
            'highlights' => 'Isi minimal satu highlight.',
            'itinerary' => 'Isi minimal satu baris itinerary.',
            'praktis.pickup' => 'Titik jemput wajib diisi.',
            'praktis.tips' => 'Tips wajib diisi.',
            'tanggal_pertama' => 'Pilih tanggal keberangkatan pertama. Journey baru wajib punya minimal satu tanggal tersedia.',
        ]);
        $this->assertSame(0, Perjalanan::where('nama', 'SEMERU SUNRISE JOURNEY')->count());
    }

    #[DataProvider('satuFieldKosong')]
    public function test_satu_field_kosong_saja_sudah_membatalkan_pembuatan_journey(string $kunci, array $timpa): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.perjalanan.simpan'), $this->dataLengkap($timpa))
            ->assertSessionHasErrors($kunci);

        $this->assertDatabaseMissing('perjalanan', ['nama' => 'SEMERU SUNRISE JOURNEY']);
    }

    /**
     * @return array<string, array{string, array<string, mixed>}>
     */
    public static function satuFieldKosong(): array
    {
        return [
            'tagline' => ['tagline', ['tagline' => '']],
            'harga' => ['harga', ['harga' => null]],
            'itinerary kegiatan' => ['itinerary.0.x', ['itinerary' => [['t' => 'Hari 1', 'x' => '']]]],
            'highlight kosong' => ['highlights.0', ['highlights' => ['']]],
            'tips' => ['praktis.tips', ['praktis' => ['pickup' => 'A', 'start' => 'B', 'capacity' => 'C', 'tips' => '']]],
            'region tidak dikenal' => ['region', ['region' => 'bali']],
        ];
    }

    public function test_sampul_bukan_gambar_ditolak(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.perjalanan.simpan'), $this->dataLengkap(['img' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf')]))
            ->assertSessionHasErrors('img');
    }

    public function test_admin_bisa_mengubah_journey_tanpa_mengunggah_ulang_sampul(): void
    {
        $user = User::factory()->create();
        $journey = Perjalanan::factory()->create(['nama' => 'JOURNEY LAMA', 'img' => '/images/hero-2.jpg']);

        $response = $this->actingAs($user)->put(
            route('admin.perjalanan.perbarui', $journey),
            $this->dataLengkap(['nama' => 'JOURNEY BARU', 'maks_orang' => 8, 'img' => null]),
        );

        $response->assertRedirect(route('admin.perjalanan.indeks'));
        $this->assertSame('JOURNEY BARU', $journey->fresh()->nama);
        $this->assertSame(8, $journey->fresh()->maks_orang);
        $this->assertSame('/images/hero-2.jpg', $journey->fresh()->img);
    }

    public function test_slug_tidak_berubah_otomatis_saat_nama_diubah(): void
    {
        $user = User::factory()->create();
        $journey = Perjalanan::factory()->create(['nama' => 'JOURNEY LAMA', 'slug' => 'journey-lama']);

        $this->actingAs($user)->put(
            route('admin.perjalanan.perbarui', $journey),
            $this->dataLengkap(['nama' => 'JOURNEY BARU', 'img' => null]),
        );

        $this->assertSame('journey-lama', $journey->fresh()->slug);
    }

    public function test_mengganti_sampul_menghapus_berkas_lama(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        Storage::disk('public')->put('perjalanan/sampul/lama.jpg', 'x');
        $journey = Perjalanan::factory()->create(['img' => '/storage/perjalanan/sampul/lama.jpg']);

        $this->actingAs($user)->put(route('admin.perjalanan.perbarui', $journey), $this->dataLengkap(['nama' => $journey->nama]));

        Storage::disk('public')->assertMissing('perjalanan/sampul/lama.jpg');
        $this->assertNotSame('/storage/perjalanan/sampul/lama.jpg', $journey->fresh()->img);
    }

    public function test_admin_bisa_menghapus_sampul_dan_situs_publik_memakai_gambar_cadangan(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        Storage::disk('public')->put('perjalanan/sampul/lama.jpg', 'x');
        $journey = Perjalanan::factory()->create(['img' => '/storage/perjalanan/sampul/lama.jpg']);

        $this->actingAs($user)->put(
            route('admin.perjalanan.perbarui', $journey),
            $this->dataLengkap(['nama' => $journey->nama, 'img' => null, 'hapus_img' => '1']),
        );

        $journey->refresh();
        Storage::disk('public')->assertMissing('perjalanan/sampul/lama.jpg');
        $this->assertNull($journey->img);
        $this->assertSame(Perjalanan::SAMPUL_CADANGAN, $journey->sampul());
    }

    public function test_tampil_beranda_mati_tersimpan_sebagai_false(): void
    {
        $user = User::factory()->create();
        $journey = Perjalanan::factory()->create(['tampil_beranda' => true]);

        $this->actingAs($user)->put(
            route('admin.perjalanan.perbarui', $journey),
            $this->dataLengkap(['nama' => $journey->nama, 'img' => null, 'tampil_beranda' => '0']),
        );

        $this->assertFalse($journey->fresh()->tampil_beranda);
    }

    public function test_filter_region_admin_memakai_label_dan_slug(): void
    {
        $user = User::factory()->create();
        Perjalanan::factory()->create(['nama' => 'ZZ JATIM TRIP', 'region' => 'jawa-timur']);
        Perjalanan::factory()->create(['nama' => 'ZZ JABAR TRIP', 'region' => 'jawa-barat']);

        $this->actingAs($user)->get(route('admin.perjalanan.indeks', ['region' => 'jawa-timur']))
            ->assertSee('ZZ JATIM TRIP')
            ->assertDontSee('ZZ JABAR TRIP')
            ->assertSee('Jawa Barat');
    }
}
