<?php

namespace Tests\Feature\Admin\Ulasan;

use App\Bantuan\BatasUnggah;
use App\Models\Pengaturan;
use App\Models\Perjalanan;
use App\Models\Pesanan;
use App\Models\Ulasan;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UlasanTest extends TestCase
{
    public function test_tamu_tidak_bisa_mengakses(): void
    {
        $this->get(route('admin.ulasan.indeks'))->assertRedirect(route('admin.autentikasi.masuk'));
    }

    public function test_admin_bisa_melihat_daftar_menunggu(): void
    {
        $user = User::factory()->create();
        $ulasan = Ulasan::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.ulasan.indeks'))
            ->assertOk()
            ->assertSee($ulasan->pesanan->nama_lengkap);
    }

    public function test_tab_disetujui_tidak_menampilkan_yang_menunggu(): void
    {
        $user = User::factory()->create();
        $menunggu = Ulasan::factory()->create(['komentar' => 'Ulasan Menunggu Unik']);
        $disetujui = Ulasan::factory()->disetujui()->create(['komentar' => 'Ulasan Disetujui Unik']);

        $response = $this->actingAs($user)->get(route('admin.ulasan.indeks', ['status' => 'disetujui']));

        $response->assertSee('Ulasan Disetujui Unik')->assertDontSee('Ulasan Menunggu Unik');
    }

    public function test_admin_bisa_menyetujui_ulasan(): void
    {
        $user = User::factory()->create();
        $ulasan = Ulasan::factory()->create(['disetujui' => false]);

        $response = $this->actingAs($user)->patch(route('admin.ulasan.setujui', $ulasan));

        $response->assertRedirect();
        $this->assertTrue($ulasan->fresh()->disetujui);
    }

    public function test_menyetujui_ulasan_memperbarui_rating_journey(): void
    {
        $user = User::factory()->create();
        $journey = Perjalanan::factory()->create(['rating_label' => null, 'reviews_count' => null]);

        $pesanan1 = Pesanan::factory()->completed()->create(['perjalanan_id' => $journey->id]);
        $pesanan2 = Pesanan::factory()->completed()->create(['perjalanan_id' => $journey->id]);
        $ulasan1 = Ulasan::factory()->disetujui()->create(['pesanan_id' => $pesanan1->id, 'rating' => 5]);
        $ulasan2 = Ulasan::factory()->create(['pesanan_id' => $pesanan2->id, 'rating' => 3, 'disetujui' => false]);

        $this->actingAs($user)->patch(route('admin.ulasan.setujui', $ulasan2));

        $journey->refresh();
        $this->assertSame('4.0', $journey->rating_label);
        $this->assertSame(2, $journey->reviews_count);
    }

    public function test_ulasan_tanpa_izin_tampil_tidak_ikut_rating_walau_disetujui(): void
    {
        $user = User::factory()->create();
        $journey = Perjalanan::factory()->create();
        $pesanan = Pesanan::factory()->completed()->create(['perjalanan_id' => $journey->id]);
        $ulasan = Ulasan::factory()->tanpaIzinTampil()->create(['pesanan_id' => $pesanan->id, 'rating' => 5, 'disetujui' => false]);

        $this->actingAs($user)->patch(route('admin.ulasan.setujui', $ulasan));

        $journey->refresh();
        $this->assertNull($journey->reviews_count);
    }

    public function test_admin_bisa_mengunggah_video(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $ulasan = Ulasan::factory()->create();
        $video = UploadedFile::fake()->create('testimoni.mp4', 5000, 'video/mp4');

        $response = $this->actingAs($user)->post(route('admin.ulasan.video.unggah', $ulasan), ['video' => $video]);

        $response->assertRedirect();
        $ulasan->refresh();
        $this->assertNotNull($ulasan->video_path);
        Storage::disk('public')->assertExists($ulasan->video_path);
    }

    public function test_unggah_video_menolak_format_selain_mp4_mov(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $ulasan = Ulasan::factory()->create();
        $video = UploadedFile::fake()->create('testimoni.avi', 5000, 'video/x-msvideo');

        $response = $this->actingAs($user)->post(route('admin.ulasan.video.unggah', $ulasan), ['video' => $video]);

        $response->assertSessionHasErrors('video');
        $this->assertNull($ulasan->fresh()->video_path);
    }

    public function test_video_yang_ditolak_php_menampilkan_pesan_batas_server_bukan_pesan_umum(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $ulasan = Ulasan::factory()->create();
        $berkas = tempnam(sys_get_temp_dir(), 'vid');
        $video = new UploadedFile($berkas, 'testimoni.mp4', 'video/mp4', UPLOAD_ERR_INI_SIZE, true);

        $this->actingAs($user)->post(route('admin.ulasan.video.unggah', $ulasan), ['video' => $video])
            ->assertSessionHasErrors('video');

        $pesan = session('errors')->first('video');
        $this->assertStringContainsString('batas unggah PHP', $pesan);
        $this->assertStringNotContainsString('failed to upload', $pesan);
        $this->assertNull($ulasan->fresh()->video_path);
        @unlink($berkas);
    }

    public function test_jendela_unggah_video_menampilkan_batas_yang_benar_benar_berlaku(): void
    {
        $user = User::factory()->create();
        Ulasan::factory()->create();
        Pengaturan::current()->update(['batas_video_mb' => 100]);
        $server = BatasUnggah::serverMb();

        $respons = $this->actingAs($user)->get(route('admin.ulasan.indeks'))->assertOk();

        if ($server !== null && $server < 100) {
            $respons->assertSee("{$server} MB")->assertSee('dibatasi PHP di server ini');
        } else {
            $respons->assertSee('100 MB')->assertDontSee('dibatasi PHP di server ini');
        }
    }

    public function test_admin_bisa_menghapus_video(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        Storage::disk('public')->put('ulasan/video/contoh.mp4', 'isi-video');
        $ulasan = Ulasan::factory()->denganVideo()->create(['video_path' => 'ulasan/video/contoh.mp4']);

        $response = $this->actingAs($user)->delete(route('admin.ulasan.video.hapus', $ulasan));

        $response->assertRedirect();
        $this->assertNull($ulasan->fresh()->video_path);
        Storage::disk('public')->assertMissing('ulasan/video/contoh.mp4');
    }

    public function test_admin_bisa_menolak_dan_memulihkan_ulasan(): void
    {
        $user = User::factory()->create();
        $ulasan = Ulasan::factory()->create();

        $this->actingAs($user)->delete(route('admin.ulasan.tolak', $ulasan));
        $this->assertSoftDeleted('ulasan', ['id' => $ulasan->id]);

        $this->actingAs($user)->patch(route('admin.ulasan.pulihkan', $ulasan->id));
        $this->assertDatabaseHas('ulasan', ['id' => $ulasan->id, 'deleted_at' => null]);
    }

    public function test_arsip_tab_hanya_menampilkan_yang_ditolak(): void
    {
        $user = User::factory()->create();
        $aktif = Ulasan::factory()->create(['komentar' => 'Ulasan Masih Aktif Unik']);
        $arsip = Ulasan::factory()->create(['komentar' => 'Ulasan Sudah Ditolak Unik']);
        $arsip->delete();

        $response = $this->actingAs($user)->get(route('admin.ulasan.indeks', ['status' => 'arsip']));

        $response->assertSee('Ulasan Sudah Ditolak Unik')->assertDontSee('Ulasan Masih Aktif Unik');
    }
}
