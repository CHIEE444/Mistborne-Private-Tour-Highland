<?php

namespace Tests\Feature\Admin\Artikel;

use App\Models\Artikel;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArtikelCrudTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    /**
     * Payload form yang lolos semua validasi; override per tes.
     *
     * @param  array<string, mixed>  $timpa
     * @return array<string, mixed>
     */
    private function dataValid(array $timpa = []): array
    {
        return [
            'judul' => 'Lima Tips Aman di Jalur Dataran Tinggi',
            'kategori' => 'Tips & Panduan',
            'ringkasan' => 'Ringkasan singkat artikel ini yang cukup panjang.',
            'isi' => '<div><strong>Tebal</strong> dan <em>miring</em> '.str_repeat('isi artikel yang cukup panjang ', 6).'</div>',
            'aksi' => 'terbitkan',
            'published_at' => now()->format('Y-m-d\TH:i'),
            'img' => UploadedFile::fake()->image('sampul.jpg', 1200, 630),
            ...$timpa,
        ];
    }

    public function test_tamu_tidak_bisa_mengakses_daftar_artikel(): void
    {
        $this->get(route('admin.artikel.indeks'))->assertRedirect(route('admin.autentikasi.masuk'));
    }

    public function test_admin_bisa_melihat_daftar_artikel(): void
    {
        $user = User::factory()->create();
        $artikel = Artikel::factory()->create(['judul' => 'Judul Artikel Unik Satu']);

        $this->actingAs($user)
            ->get(route('admin.artikel.indeks'))
            ->assertOk()
            ->assertSee('Judul Artikel Unik Satu');
    }

    public function test_tab_draft_tidak_menampilkan_yang_terbit(): void
    {
        $user = User::factory()->create();
        Artikel::factory()->create(['judul' => 'Artikel Terbit Unik']);
        Artikel::factory()->draf()->create(['judul' => 'Artikel Draft Unik']);

        $response = $this->actingAs($user)->get(route('admin.artikel.indeks', ['status' => 'draft']));

        $response->assertSee('Artikel Draft Unik')->assertDontSee('Artikel Terbit Unik');
    }

    public function test_admin_bisa_mencari_artikel(): void
    {
        $user = User::factory()->create();
        Artikel::factory()->create(['judul' => 'Panduan Mendaki Gunung Dieng']);
        Artikel::factory()->create(['judul' => 'Protokol Keselamatan Ijen']);

        $response = $this->actingAs($user)->get(route('admin.artikel.indeks', ['cari' => 'Dieng']));

        $response->assertSee('Panduan Mendaki Gunung Dieng')->assertDontSee('Protokol Keselamatan Ijen');
    }

    public function test_admin_bisa_membuka_form_tambah_artikel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.artikel.buat'))->assertOk();
    }

    public function test_admin_bisa_membuka_form_ubah_artikel(): void
    {
        $user = User::factory()->create();
        $artikel = Artikel::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.artikel.ubah', $artikel))
            ->assertOk()
            ->assertSee($artikel->judul);
    }

    public function test_admin_bisa_membuat_artikel_dan_slug_dibuat_otomatis(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.artikel.simpan'), $this->dataValid());

        $response->assertRedirect(route('admin.artikel.indeks'));

        $artikel = Artikel::where('judul', 'Lima Tips Aman di Jalur Dataran Tinggi')->first();
        $this->assertNotNull($artikel);
        $this->assertSame('lima-tips-aman-di-jalur-dataran-tinggi', $artikel->slug);
        $this->assertSame('terbit', $artikel->status());
    }

    public function test_slug_otomatis_unik_saat_judul_mirip_setelah_judul_lama_dihapus(): void
    {
        $user = User::factory()->create();
        $lama = Artikel::factory()->create(['judul' => 'Judul Sama', 'slug' => 'judul-sama']);
        $lama->delete();

        $this->actingAs($user)->post(route('admin.artikel.simpan'), $this->dataValid(['judul' => 'Judul Sama']));

        $this->assertDatabaseHas('artikel', ['judul' => 'Judul Sama', 'slug' => 'judul-sama-2']);
    }

    public function test_judul_yang_sudah_dipakai_artikel_aktif_ditolak(): void
    {
        $user = User::factory()->create();
        Artikel::factory()->create(['judul' => 'Judul Sama']);

        $this->actingAs($user)->post(route('admin.artikel.simpan'), $this->dataValid(['judul' => 'Judul Sama']))
            ->assertSessionHasErrors('judul');
    }

    public function test_waktu_baca_dihitung_otomatis_dari_isi(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('admin.artikel.simpan'), $this->dataValid([
            'judul' => 'Artikel Panjang Untuk Hitung Waktu Baca',
            'isi' => implode(' ', array_fill(0, 400, 'kata')),
        ]));

        $artikel = Artikel::where('judul', 'Artikel Panjang Untuk Hitung Waktu Baca')->first();
        $this->assertSame(2, $artikel->waktu_baca_menit);
    }

    public function test_simpan_sebagai_draft_tidak_mengisi_published_at(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('admin.artikel.simpan'), $this->dataValid([
            'judul' => 'Artikel Draft Baru',
            'aksi' => 'draft',
            'published_at' => null,
        ]));

        $artikel = Artikel::where('judul', 'Artikel Draft Baru')->first();
        $this->assertNull($artikel->published_at);
        $this->assertSame('draft', $artikel->status());
    }

    public function test_kategori_baru_di_luar_saran_diterima(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('admin.artikel.simpan'), $this->dataValid(['kategori' => 'Teknologi Otomotif']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('artikel', ['kategori' => 'Teknologi Otomotif']);
    }

    public function test_kategori_dengan_karakter_aneh_ditolak(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('admin.artikel.simpan'), $this->dataValid(['kategori' => '<script>x</script>']))
            ->assertSessionHasErrors('kategori');
    }

    public function test_setiap_isian_yang_kosong_membuat_simpan_gagal(): void
    {
        $user = User::factory()->create();

        foreach (['judul', 'kategori', 'ringkasan', 'isi', 'img', 'published_at'] as $kolom) {
            $this->actingAs($user)
                ->post(route('admin.artikel.simpan'), $this->dataValid([$kolom => null]))
                ->assertSessionHasErrors($kolom);
        }

        $this->assertDatabaseCount('artikel', 0);
    }

    public function test_isi_yang_terlalu_pendek_atau_ringkasan_pendek_ditolak(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.artikel.simpan'), $this->dataValid(['isi' => '<p>Isi.</p>', 'ringkasan' => 'Pendek']))
            ->assertSessionHasErrors(['isi', 'ringkasan']);
    }

    public function test_sampul_harus_gambar_berukuran_cukup(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.artikel.simpan'), $this->dataValid(['img' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf')]))
            ->assertSessionHasErrors('img');

        $this->actingAs($user)
            ->post(route('admin.artikel.simpan'), $this->dataValid(['img' => UploadedFile::fake()->image('kecil.jpg', 300, 200)]))
            ->assertSessionHasErrors('img');
    }

    public function test_jadwal_terbit_di_masa_lalu_ditolak(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.artikel.simpan'), $this->dataValid(['published_at' => now()->subDay()->format('Y-m-d\TH:i')]))
            ->assertSessionHasErrors('published_at');
    }

    public function test_format_jadwal_salah_ditolak(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.artikel.simpan'), $this->dataValid(['published_at' => 'besok pagi']))
            ->assertSessionHasErrors('published_at');
    }

    public function test_artikel_dijadwalkan_otomatis_terbit_saat_waktunya_tiba(): void
    {
        $user = User::factory()->create();
        $jadwal = now()->addHours(3);

        $this->actingAs($user)->post(route('admin.artikel.simpan'), $this->dataValid([
            'judul' => 'Artikel Terjadwal Otomatis',
            'published_at' => $jadwal->format('Y-m-d\TH:i'),
        ]))->assertSessionHasNoErrors();

        $artikel = Artikel::where('judul', 'Artikel Terjadwal Otomatis')->firstOrFail();

        $this->assertSame('dijadwalkan', $artikel->status());
        $this->get(route('articles.show', $artikel->slug))->assertNotFound();

        $this->travelTo($jadwal->copy()->addMinute());

        $this->assertSame('terbit', $artikel->fresh()->status());
        $this->get(route('articles.show', $artikel->slug))->assertOk()->assertSee('Artikel Terjadwal Otomatis');
    }

    public function test_admin_bisa_mengunggah_gambar_sampul_dan_dikonversi_ke_webp(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('admin.artikel.simpan'), $this->dataValid([
            'judul' => 'Artikel Dengan Sampul',
            'img' => UploadedFile::fake()->image('sampul.jpg', 1600, 900),
        ]));

        $artikel = Artikel::where('judul', 'Artikel Dengan Sampul')->first();
        $this->assertNotNull($artikel->img);
        $this->assertStringEndsWith('.webp', $artikel->img);
        $this->assertStringStartsWith('/storage/', $artikel->img);
        Storage::disk('public')->assertExists(ltrim(str_replace('/storage/', '', $artikel->img), '/'));
    }

    /**
     * @return array<string, mixed>
     */
    private function dataUbah(Artikel $artikel, array $timpa = []): array
    {
        return [
            'judul' => $artikel->judul,
            'kategori' => $artikel->kategori,
            'ringkasan' => $artikel->ringkasan,
            'isi' => $artikel->isi,
            'aksi' => 'terbitkan',
            'published_at' => $artikel->published_at->format('Y-m-d\TH:i'),
            ...$timpa,
        ];
    }

    public function test_admin_bisa_mengubah_artikel_tanpa_mengunggah_ulang_sampul(): void
    {
        $user = User::factory()->create();
        $artikel = Artikel::factory()->create(['judul' => 'Judul Lama']);

        $response = $this->actingAs($user)->put(route('admin.artikel.perbarui', $artikel), $this->dataUbah($artikel, ['judul' => 'Judul Baru']));

        $response->assertRedirect(route('admin.artikel.indeks'));
        $this->assertSame('Judul Baru', $artikel->fresh()->judul);
    }

    public function test_artikel_terbit_lama_bisa_disimpan_ulang_dengan_jadwal_yang_sama(): void
    {
        $user = User::factory()->create();
        $artikel = Artikel::factory()->create(['published_at' => now()->subMonths(2)]);

        $this->actingAs($user)->put(route('admin.artikel.perbarui', $artikel), $this->dataUbah($artikel))
            ->assertSessionHasNoErrors();
    }

    public function test_slug_tidak_berubah_otomatis_saat_judul_diubah(): void
    {
        $user = User::factory()->create();
        $artikel = Artikel::factory()->create(['judul' => 'Judul Lama', 'slug' => 'judul-lama']);

        $this->actingAs($user)->put(route('admin.artikel.perbarui', $artikel), $this->dataUbah($artikel, ['judul' => 'Judul Baru']));

        $this->assertSame('judul-lama', $artikel->fresh()->slug);
    }

    public function test_mengubah_artikel_terbit_menjadi_draft_mengosongkan_published_at(): void
    {
        $user = User::factory()->create();
        $artikel = Artikel::factory()->create();

        $this->actingAs($user)->put(route('admin.artikel.perbarui', $artikel), $this->dataUbah($artikel, ['aksi' => 'draft']));

        $this->assertNull($artikel->fresh()->published_at);
    }

    public function test_admin_bisa_menghapus_dan_memulihkan_artikel(): void
    {
        $user = User::factory()->create();
        $artikel = Artikel::factory()->create();

        $this->actingAs($user)->delete(route('admin.artikel.hapus', $artikel));
        $this->assertSoftDeleted('artikel', ['id' => $artikel->id]);

        $this->actingAs($user)->patch(route('admin.artikel.pulihkan', $artikel->id));
        $this->assertDatabaseHas('artikel', ['id' => $artikel->id, 'deleted_at' => null]);
    }

    public function test_url_gambar_tersimpan_diubah_ke_path_storage_yang_aman(): void
    {
        $penuh = Artikel::factory()->create(['img' => 'http://localhost:8000/storage/artikel/sampul/a.webp']);
        $relatif = Artikel::factory()->create(['img' => 'artikel/sampul/b.webp']);
        $statis = Artikel::factory()->create(['img' => '/images/landscape-highland.jpg']);

        $this->assertSame('/storage/artikel/sampul/a.webp', $penuh->kePublik()['img']);
        $this->assertStringEndsWith('/storage/artikel/sampul/b.webp', $relatif->kePublik()['img']);
        $this->assertSame('/images/landscape-highland.jpg', $statis->kePublik()['img']);
    }
}
