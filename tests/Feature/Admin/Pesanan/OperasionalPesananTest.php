<?php

namespace Tests\Feature\Admin\Pesanan;

use App\Layanan\KetersediaanPersonelOperasionalLayanan;
use App\Mail\PenugasanPersonel;
use App\Models\PersonelOperasional;
use App\Models\Pesanan;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;
use Tests\Support\DataGuide;
use Tests\TestCase;

class OperasionalPesananTest extends TestCase
{
    private function guide(string $nama, array $timpa = []): PersonelOperasional
    {
        return PersonelOperasional::query()->create([
            ...DataGuide::sim(),
            'nama' => $nama,
            'peran' => 'guide',
            'status_operasional' => 'siap',
            'aktif' => true,
            ...$timpa,
        ]);
    }

    public function test_penugasan_dan_pelepasan_guide_tercatat_di_log_aktivitas_guide(): void
    {
        Mail::fake();
        $admin = User::factory()->create();
        $guideA = $this->guide('Guide A', ['email' => 'a@example.com']);
        $guideB = $this->guide('Guide B', ['email' => 'b@example.com']);
        $pesanan = Pesanan::factory()->create();

        $this->actingAs($admin)->put(route('admin.pesanan.operasional', $pesanan), ['guide_personel_id' => $guideA->id])->assertSessionHasNoErrors();
        $this->actingAs($admin)->put(route('admin.pesanan.operasional', $pesanan), ['guide_personel_id' => $guideB->id])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('aktivitas_personel', ['personel_id' => $guideA->id, 'jenis' => 'ditugaskan', 'pesanan_id' => $pesanan->id]);
        $this->assertDatabaseHas('aktivitas_personel', ['personel_id' => $guideA->id, 'jenis' => 'dilepas', 'pesanan_id' => $pesanan->id]);
        $this->assertDatabaseHas('aktivitas_personel', ['personel_id' => $guideB->id, 'jenis' => 'ditugaskan', 'pesanan_id' => $pesanan->id]);
    }

    public function test_status_penugasan_mencatat_email_terkirim_dan_menunggu_konfirmasi_guide(): void
    {
        Mail::fake();
        $admin = User::factory()->create();
        $pesanan = Pesanan::factory()->create();
        $guide = $this->guide('Guide Status', ['email' => 'status@example.com']);

        $this->actingAs($admin)->put(route('admin.pesanan.operasional', $pesanan), ['guide_personel_id' => $guide->id])->assertSessionHasNoErrors();

        $this->actingAs($admin)->getJson(route('admin.pesanan.penugasan.status', $pesanan))
            ->assertOk()
            ->assertJsonPath('guide', 'Guide Status')
            ->assertJsonPath('konfirmasi', 'menunggu')
            ->assertJsonPath('email.status', 'terkirim')
            ->assertJsonPath('email.alamat', 'status@example.com');

        $this->actingAs($admin)->get(route('admin.pesanan.detail', $pesanan))
            ->assertOk()
            ->assertSee('Status penugasan guide')
            ->assertSee(route('admin.pesanan.penugasan.kirim-ulang', $pesanan), false);
    }

    public function test_jawaban_guide_langsung_terbaca_oleh_status_penugasan_admin(): void
    {
        $admin = User::factory()->create();
        $akunGuide = User::factory()->create(['jabatan' => 'guide']);
        $guide = $this->guide('Guide Menjawab', ['email' => 'jawab@example.com', 'user_id' => $akunGuide->id]);
        $pesanan = Pesanan::factory()->payment()->create(['guide_personel_id' => $guide->id, 'nama_guide' => $guide->nama, 'status_konfirmasi_guide' => 'menunggu']);

        $this->actingAs($akunGuide)->post(route('personel.pesanan.tanggapi', $pesanan), ['jawaban' => 'diterima'])->assertSessionHasNoErrors();

        $status = $this->actingAs($admin)->getJson(route('admin.pesanan.penugasan.status', $pesanan))->assertOk();
        $status->assertJsonPath('konfirmasi', 'diterima');
        $this->assertNotNull($status->json('konfirmasi_waktu'));
    }

    public function test_email_penugasan_yang_gagal_tercatat_dan_bisa_dikirim_ulang(): void
    {
        $admin = User::factory()->create();
        $guide = $this->guide('Guide Gagal Email', ['email' => 'gagal@example.com']);
        $pesanan = Pesanan::factory()->create();

        // SMTP diarahkan ke port lokal yang tertutup: koneksi ditolak seketika, seperti server email yang tidak bisa dihubungi.
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 9, 'mail.mailers.smtp.timeout' => 2]);
        $this->actingAs($admin)->put(route('admin.pesanan.operasional', $pesanan), ['guide_personel_id' => $guide->id])->assertSessionHas('popup_email_penugasan')->assertSessionMissing('sukses');

        $pesanan->refresh();
        $this->assertSame('gagal', $pesanan->email_penugasan_status);
        $this->assertSame('gagal@example.com', $pesanan->email_penugasan_alamat);
        $this->assertNotNull($pesanan->email_penugasan_galat);

        Mail::fake();
        $this->actingAs($admin)->post(route('admin.pesanan.penugasan.kirim-ulang', $pesanan))->assertSessionHas('popup_email_penugasan');

        Mail::assertSent(PenugasanPersonel::class, fn (PenugasanPersonel $mail): bool => $mail->hasTo('gagal@example.com'));
        $this->assertSame('terkirim', $pesanan->fresh()->email_penugasan_status);
        $this->assertNull($pesanan->fresh()->email_penugasan_galat);
    }

    public function test_popup_email_penugasan_menampilkan_hasil_terkirim_atau_gagal_setelah_menugaskan_guide(): void
    {
        Mail::fake();
        $admin = User::factory()->create();
        $guide = $this->guide('Guide Popup', ['email' => 'popup.guide@example.com']);
        $pesanan = Pesanan::factory()->create();

        $this->actingAs($admin)->from(route('admin.pesanan.detail', $pesanan))
            ->followingRedirects()
            ->put(route('admin.pesanan.operasional', $pesanan), ['guide_personel_id' => $guide->id])
            ->assertSee('Email penugasan terkirim ke guide')
            ->assertSee('popup.guide@example.com');

        $tanpaEmail = $this->guide('Guide Tanpa Alamat', ['email' => null]);
        $pesananLain = Pesanan::factory()->create();

        $this->actingAs($admin)->from(route('admin.pesanan.detail', $pesananLain))
            ->followingRedirects()
            ->put(route('admin.pesanan.operasional', $pesananLain), ['guide_personel_id' => $tanpaEmail->id])
            ->assertSee('Email penugasan belum sampai ke guide')
            ->assertSee('Guide belum punya alamat email.');

        $this->actingAs($admin)->get(route('admin.pesanan.detail', $pesananLain))->assertDontSee('data-popup-email-penugasan', false);
    }

    public function test_kirim_ulang_ke_guide_tanpa_email_memberi_tahu_admin(): void
    {
        $admin = User::factory()->create();
        $guide = $this->guide('Guide Tanpa Email', ['email' => null]);
        $pesanan = Pesanan::factory()->create(['guide_personel_id' => $guide->id, 'nama_guide' => $guide->nama, 'status_konfirmasi_guide' => 'menunggu']);

        $this->actingAs($admin)->post(route('admin.pesanan.penugasan.kirim-ulang', $pesanan))->assertSessionHas('popup_email_penugasan');

        $this->assertSame('tanpa_email', $pesanan->fresh()->email_penugasan_status);
    }

    public function test_riwayat_status_menampilkan_pembayaran_lunas_di_atas_konfirmasi_admin(): void
    {
        $admin = User::factory()->create();
        $pesanan = Pesanan::factory()->payment()->create(['pembayaran_diterima_at' => now()->subDay()]);

        $html = $this->actingAs($admin)->get(route('admin.pesanan.detail', $pesanan))->assertOk()->getContent();

        $this->assertLessThan(strpos($html, 'Dikonfirmasi Admin MISTBORNE'), strpos($html, 'Pembayaran Diterima'));
        $this->assertStringNotContainsString('beralih ke', $html);
    }

    public function test_admin_dapat_menyimpan_bukti_pembayaran_dan_mengirim_email_penugasan_ke_guide(): void
    {
        Storage::fake('local');
        Mail::fake();

        $admin = User::factory()->create();
        $pesanan = Pesanan::factory()->create();
        $guide = $this->guide('Guide Contoh', ['email' => 'guide@example.com']);

        $response = $this->actingAs($admin)
            ->from(route('admin.pesanan.detail', $pesanan))
            ->put(route('admin.pesanan.operasional', $pesanan), [
                'guide_personel_id' => $guide->id,
                'bukti_pembayaran' => UploadedFile::fake()->image('bukti.jpg')->size(256),
            ]);

        $response->assertRedirect(route('admin.pesanan.detail', $pesanan));
        $this->assertDatabaseHas('pesanan', ['id' => $pesanan->id, 'guide_personel_id' => $guide->id, 'status_konfirmasi_guide' => 'menunggu']);
        $this->assertNotNull($pesanan->fresh()->bukti_pembayaran);
        Storage::disk('local')->assertExists($pesanan->fresh()->bukti_pembayaran);
        $response->assertSessionHas('popup_email_penugasan', 'Data operasional disimpan.');
        $this->assertSame('terkirim', $pesanan->fresh()->email_penugasan_status);
        Mail::assertSent(PenugasanPersonel::class, 1);
        Mail::assertSent(PenugasanPersonel::class, fn (PenugasanPersonel $mail): bool => $mail->hasTo('guide@example.com'));
    }

    public function test_bukti_pembayaran_di_atas_batas_5_mb_ditolak(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create();
        $pesanan = Pesanan::factory()->create();

        $response = $this->actingAs($admin)
            ->from(route('admin.pesanan.detail', $pesanan))
            ->put(route('admin.pesanan.operasional', $pesanan), [
                'bukti_pembayaran' => UploadedFile::fake()->create('bukti.pdf', 5121, 'application/pdf'),
            ]);

        $response->assertRedirect(route('admin.pesanan.detail', $pesanan));
        $response->assertSessionHasErrors('bukti_pembayaran');
        $this->assertNull($pesanan->fresh()->bukti_pembayaran);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_gagal_unggah_dari_batas_php_menampilkan_validasi_dan_tidak_menyimpan_palsu(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create();
        $pesanan = Pesanan::factory()->create();
        $berkasSementara = UploadedFile::fake()->image('bukti.jpg');
        $unggahanGagal = new SymfonyUploadedFile(
            $berkasSementara->getPathname(),
            'bukti.jpg',
            'image/jpeg',
            UPLOAD_ERR_INI_SIZE,
            true,
        );

        $response = $this->actingAs($admin)
            ->from(route('admin.pesanan.detail', $pesanan))
            ->put(route('admin.pesanan.operasional', $pesanan), [
                'bukti_pembayaran' => $unggahanGagal,
            ]);

        $response->assertRedirect(route('admin.pesanan.detail', $pesanan));
        $response->assertSessionHasErrors('bukti_pembayaran');
        $this->assertNull($pesanan->fresh()->bukti_pembayaran);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_guide_yang_sedang_ditugaskan_tidak_bisa_dipakai_untuk_jadwal_bentrok(): void
    {
        $admin = User::factory()->create();
        $guide = $this->guide('Guide Sibuk');
        $tanggalTrip = today()->addDays(20);
        Pesanan::factory()->create([
            'tanggal_trip' => $tanggalTrip,
            'tanggal_selesai' => $tanggalTrip->copy()->addDay(),
            'guide_personel_id' => $guide->id,
            'status_konfirmasi_guide' => 'diterima',
        ]);
        $pesananTarget = Pesanan::factory()->create([
            'tanggal_trip' => $tanggalTrip->copy()->addDay(),
            'tanggal_selesai' => $tanggalTrip->copy()->addDays(2),
        ]);

        $this->actingAs($admin)
            ->from(route('admin.pesanan.detail', $pesananTarget))
            ->put(route('admin.pesanan.operasional', $pesananTarget), ['guide_personel_id' => $guide->id])
            ->assertRedirect(route('admin.pesanan.detail', $pesananTarget))
            ->assertSessionHasErrors('guide_personel_id');

        $this->assertNull($pesananTarget->fresh()->guide_personel_id);
    }

    public function test_guide_dengan_sim_habis_sebelum_trip_selesai_atau_belum_diisi_tidak_bisa_ditugaskan(): void
    {
        $admin = User::factory()->create();
        $pesanan = Pesanan::factory()->create(['tanggal_trip' => today()->addDays(20), 'tanggal_selesai' => today()->addDays(21)]);
        $simHabis = $this->guide('Guide SIM Habis', ['masa_berlaku_sim' => today()->addDays(20)->toDateString()]);
        $tanpaSim = $this->guide('Guide Tanpa SIM', ['nomor_sim' => null, 'jenis_sim' => null, 'masa_berlaku_sim' => null]);
        $simAman = $this->guide('Guide SIM Aman', ['masa_berlaku_sim' => today()->addDays(60)->toDateString()]);

        $this->actingAs($admin)->put(route('admin.pesanan.operasional', $pesanan), ['guide_personel_id' => $simHabis->id])->assertSessionHasErrors('guide_personel_id');
        $this->actingAs($admin)->put(route('admin.pesanan.operasional', $pesanan), ['guide_personel_id' => $tanpaSim->id])->assertSessionHasErrors('guide_personel_id');
        $this->assertNull($pesanan->fresh()->guide_personel_id);

        $this->actingAs($admin)->put(route('admin.pesanan.operasional', $pesanan), ['guide_personel_id' => $simAman->id])->assertSessionHasNoErrors();
        $this->assertSame($simAman->id, $pesanan->fresh()->guide_personel_id);

        $daftar = app(KetersediaanPersonelOperasionalLayanan::class)->daftarTersedia(Pesanan::factory()->create(['tanggal_trip' => today()->addDays(40), 'tanggal_selesai' => today()->addDays(41)]));
        $this->assertTrue($daftar->has($simAman->id));
        $this->assertFalse($daftar->has($simHabis->id));
        $this->assertFalse($daftar->has($tanpaSim->id));
    }

    public function test_email_penugasan_hanya_dikirim_ke_email_kontak_bila_berbeda_dari_email_akun(): void
    {
        Mail::fake();
        $admin = User::factory()->create();
        $akun = User::factory()->create(['email' => 'akun.guide@example.com', 'jabatan' => 'guide']);
        $guide = $this->guide('Guide Dua Email', ['email' => 'kontak.guide@example.com', 'user_id' => $akun->id]);
        $pesanan = Pesanan::factory()->create();

        $respons = $this->actingAs($admin)->put(route('admin.pesanan.operasional', $pesanan), ['guide_personel_id' => $guide->id]);

        $respons->assertSessionHas('popup_email_penugasan');
        $this->assertSame('kontak.guide@example.com', $pesanan->fresh()->email_penugasan_alamat);
        Mail::assertSent(PenugasanPersonel::class, 1);
        Mail::assertSent(PenugasanPersonel::class, fn (PenugasanPersonel $mail): bool => $mail->hasTo('kontak.guide@example.com') && ! $mail->hasTo('akun.guide@example.com'));
    }

    public function test_simpan_ulang_tanpa_perubahan_tetap_mengirim_email_dan_status_guide_tidak_berubah(): void
    {
        Mail::fake();
        $admin = User::factory()->create();
        $guide = $this->guide('Guide Sudah', ['email' => 'guide.sudah@example.com']);
        $pesanan = Pesanan::factory()->create(['guide_personel_id' => $guide->id, 'status_konfirmasi_guide' => 'diterima', 'catatan_admin' => 'Simpan ulang']);

        $this->actingAs($admin)->put(route('admin.pesanan.operasional', $pesanan), ['guide_personel_id' => $guide->id, 'catatan_admin' => 'Simpan ulang'])->assertSessionHasNoErrors();

        Mail::assertSent(PenugasanPersonel::class, fn (PenugasanPersonel $mail): bool => $mail->hasTo('guide.sudah@example.com'));
        $this->assertSame('diterima', $pesanan->fresh()->status_konfirmasi_guide);
    }

    public function test_simpan_ulang_mengirim_ulang_email_ke_guide_yang_belum_menanggapi(): void
    {
        Mail::fake();
        $admin = User::factory()->create();
        $guide = $this->guide('Guide Belum', ['email' => 'guide.belum@example.com']);
        $pesanan = Pesanan::factory()->create(['guide_personel_id' => $guide->id, 'status_konfirmasi_guide' => 'menunggu', 'catatan_admin' => 'Simpan ulang']);

        $this->actingAs($admin)->put(route('admin.pesanan.operasional', $pesanan), ['guide_personel_id' => $guide->id, 'catatan_admin' => 'Simpan ulang'])->assertSessionHasNoErrors();

        Mail::assertSent(PenugasanPersonel::class, 1);
        Mail::assertSent(PenugasanPersonel::class, fn (PenugasanPersonel $mail): bool => $mail->hasTo('guide.belum@example.com'));
    }

    public function test_catatan_admin_baru_dikirim_ke_email_guide_yang_bertugas(): void
    {
        Mail::fake();
        $admin = User::factory()->create();
        $guide = $this->guide('Guide Catatan', ['email' => 'guide.catatan@example.com']);
        $pesanan = Pesanan::factory()->create(['guide_personel_id' => $guide->id, 'status_konfirmasi_guide' => 'diterima', 'catatan_admin' => null]);

        $this->actingAs($admin)->put(route('admin.pesanan.operasional', $pesanan), [
            'guide_personel_id' => $guide->id,
            'catatan_admin' => 'Tamu membawa lansia, jemput lebih pagi.',
        ])->assertSessionHasNoErrors();

        Mail::assertSent(PenugasanPersonel::class, 1);
        Mail::assertSent(PenugasanPersonel::class, fn (PenugasanPersonel $mail): bool => $mail->hasTo('guide.catatan@example.com')
            && str_contains($mail->render(), 'Tamu membawa lansia, jemput lebih pagi.')
            && str_contains($mail->render(), 'Pembaruan dari admin'));
    }

    public function test_catatan_admin_yang_tidak_berubah_tetap_dikirim_saat_operasional_disimpan(): void
    {
        Mail::fake();
        $admin = User::factory()->create();
        $guide = $this->guide('Guide Tetap', ['email' => 'guide.tetap@example.com']);
        $pesanan = Pesanan::factory()->create(['guide_personel_id' => $guide->id, 'status_konfirmasi_guide' => 'diterima', 'catatan_admin' => 'Catatan lama']);

        $this->actingAs($admin)->put(route('admin.pesanan.operasional', $pesanan), ['guide_personel_id' => $guide->id, 'catatan_admin' => 'Catatan lama'])->assertSessionHasNoErrors();

        Mail::assertSent(PenugasanPersonel::class, fn (PenugasanPersonel $mail): bool => $mail->hasTo('guide.tetap@example.com'));
    }

    public function test_guide_tanpa_email_nyata_dilaporkan_dan_tidak_dikirimi_alamat_demo(): void
    {
        Mail::fake();
        $admin = User::factory()->create();
        $guide = $this->guide('Guide Email Uji', ['email' => 'guide@mistborne.test']);
        $pesanan = Pesanan::factory()->create();

        $response = $this->actingAs($admin)->put(route('admin.pesanan.operasional', $pesanan), ['guide_personel_id' => $guide->id]);

        Mail::assertNothingSent();
        $response->assertSessionHas('popup_email_penugasan');
        $this->assertSame('tanpa_email', $pesanan->fresh()->email_penugasan_status);
        $this->assertSame($guide->id, $pesanan->fresh()->guide_personel_id);
    }
}
