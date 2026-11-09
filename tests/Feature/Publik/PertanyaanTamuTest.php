<?php

namespace Tests\Feature\Publik;

use App\Models\PertanyaanTamu;
use App\Models\User;
use App\Notifications\PertanyaanTamuMasukNotification;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PertanyaanTamuTest extends TestCase
{
    /** @return array<string, mixed> isian sama seperti yang dikirim form kontak beranda */
    private function dataFormKontak(array $ubah = []): array
    {
        return [
            'sumber' => 'kontak',
            'journey' => 'DIENG HIGHLAND ESCAPE',
            'lokasi_pickup' => 'Semarang / Yogyakarta',
            'tanggal_perjalanan' => null,
            'pesan' => "Halo Mistborne, saya ingin bertanya tentang journey.\nPilihan Journey: DIENG HIGHLAND ESCAPE",
            ...$ubah,
        ];
    }

    public function test_klik_cta_whatsapp_dicatat_sebagai_pertanyaan(): void
    {
        $this->postJson(route('pertanyaan.simpan'), [
            'sumber' => 'cta_whatsapp',
            'pesan' => 'Halo Mistborne, saya ingin merencanakan trip privat ke dataran tinggi',
        ])->assertNoContent();

        $this->assertDatabaseHas('pertanyaan_tamu', ['sumber' => 'cta_whatsapp', 'status' => 'baru']);
    }

    public function test_form_kontak_dicatat_dan_admin_diberi_notifikasi(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['jabatan' => 'admin']);
        $tamu = User::factory()->create(['jabatan' => 'tamu']);

        $this->postJson(route('pertanyaan.simpan'), $this->dataFormKontak())->assertNoContent();

        $pertanyaan = PertanyaanTamu::query()->firstOrFail();
        $this->assertSame('kontak', $pertanyaan->sumber);
        $this->assertSame('baru', $pertanyaan->status);
        $this->assertSame('DIENG HIGHLAND ESCAPE', $pertanyaan->journey);
        $this->assertNull($pertanyaan->tanggal_perjalanan);
        $this->assertNull($pertanyaan->whatsapp);
        $this->assertNotNull($pertanyaan->sidik_pengirim);
        $this->assertDatabaseMissing('pesanan', ['nama_perjalanan' => 'DIENG HIGHLAND ESCAPE']);
        $this->assertDatabaseCount('permintaan_pesanan', 0);
        Notification::assertSentTo($admin, PertanyaanTamuMasukNotification::class);
        Notification::assertNotSentTo($tamu, PertanyaanTamuMasukNotification::class);
    }

    public function test_form_kontak_menyimpan_nama_dan_nomor_whatsapp_yang_dinormalisasi(): void
    {
        $this->postJson(route('pertanyaan.simpan'), [
            'sumber' => 'kontak',
            'nama' => 'Dewi Penanya',
            'whatsapp' => '+62 812-3456-7890',
            'journey' => 'BROMO SUNRISE JOURNEY',
            'tanggal_perjalanan' => now()->addDays(20)->toDateString(),
            'pesan' => 'Halo Mistborne, saya ingin bertanya.',
        ])->assertNoContent();

        $this->assertDatabaseHas('pertanyaan_tamu', ['nama' => 'Dewi Penanya', 'whatsapp' => '081234567890', 'sumber' => 'kontak']);
    }

    public function test_nomor_whatsapp_tidak_valid_dikosongkan_bukan_ditolak(): void
    {
        $this->postJson(route('pertanyaan.simpan'), $this->dataFormKontak(['whatsapp' => '12345']))->assertNoContent();

        $this->assertNull(PertanyaanTamu::query()->firstOrFail()->whatsapp);
    }

    public function test_pertanyaan_identik_dari_pengirim_yang_sama_tidak_dicatat_dua_kali(): void
    {
        $this->postJson(route('pertanyaan.simpan'), $this->dataFormKontak())->assertNoContent();
        $this->postJson(route('pertanyaan.simpan'), $this->dataFormKontak())->assertNoContent();

        $this->assertDatabaseCount('pertanyaan_tamu', 1);

        $this->postJson(route('pertanyaan.simpan'), $this->dataFormKontak(['pesan' => 'Pesan lain tentang Bromo']))->assertNoContent();
        $this->assertDatabaseCount('pertanyaan_tamu', 2);
    }

    public function test_bot_yang_mengisi_kolom_jebakan_diabaikan(): void
    {
        Notification::fake();
        User::factory()->create(['jabatan' => 'admin']);

        $this->postJson(route('pertanyaan.simpan'), $this->dataFormKontak(['website' => 'http://spam.example']))->assertNoContent();

        $this->assertDatabaseCount('pertanyaan_tamu', 0);
        Notification::assertNothingSent();
    }

    public function test_data_tidak_valid_ditolak(): void
    {
        $this->postJson(route('pertanyaan.simpan'), $this->dataFormKontak(['sumber' => 'lainnya']))->assertJsonValidationErrors('sumber');
        // Filter "Cek ketersediaan" beranda sudah dihapus, jadi sumber itu tidak lagi diterima.
        $this->postJson(route('pertanyaan.simpan'), $this->dataFormKontak(['sumber' => 'cek_ketersediaan']))->assertJsonValidationErrors('sumber');
        $this->postJson(route('pertanyaan.simpan'), $this->dataFormKontak(['pesan' => '']))->assertJsonValidationErrors('pesan');
        $this->postJson(route('pertanyaan.simpan'), $this->dataFormKontak(['tanggal_perjalanan' => '20-10-2026']))->assertJsonValidationErrors('tanggal_perjalanan');

        $this->assertDatabaseCount('pertanyaan_tamu', 0);
    }

    public function test_pengirim_dibatasi_jumlah_permintaannya(): void
    {
        foreach (range(1, 10) as $nomor) {
            $this->postJson(route('pertanyaan.simpan'), $this->dataFormKontak(['pesan' => "Pesan ke-{$nomor}"]))->assertNoContent();
        }

        $this->postJson(route('pertanyaan.simpan'), $this->dataFormKontak(['pesan' => 'Pesan ke-11']))->assertStatus(429);
    }

    public function test_beranda_menyertakan_token_csrf_dan_alamat_pencatatan_untuk_skrip_publik(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('name="csrf-token"', false)
            ->assertSee('window.PERTANYAAN_URL', false);
    }
}
