<?php

namespace Tests\Feature\Admin\PertanyaanTamu;

use App\Models\Perjalanan;
use App\Models\PermintaanPesanan;
use App\Models\PertanyaanTamu;
use App\Models\User;
use Tests\TestCase;

class PertanyaanTamuAdminTest extends TestCase
{
    public function test_admin_melihat_pertanyaan_beserta_angka_badge_di_sidebar(): void
    {
        $admin = User::factory()->create(['jabatan' => 'admin']);
        PertanyaanTamu::factory()->count(2)->create(['journey' => 'DIENG HIGHLAND ESCAPE']);
        PertanyaanTamu::factory()->create(['status' => 'dibalas']);

        $this->actingAs($admin)->get(route('admin.pertanyaan-tamu.indeks'))
            ->assertOk()
            ->assertSee('Pertanyaan Tamu')
            ->assertSee('DIENG HIGHLAND ESCAPE')
            ->assertSee('Belum memilih tanggal')
            ->assertSee('pertanyaan: 2', false);

        $this->actingAs($admin)->get(route('admin.pertanyaan-tamu.indeks', ['status' => 'dibalas']))->assertOk();
    }

    public function test_filter_status_tidak_valid_ditolak(): void
    {
        $this->actingAs(User::factory()->create(['jabatan' => 'admin']))
            ->get(route('admin.pertanyaan-tamu.indeks', ['status' => 'sembarang']))
            ->assertSessionHasErrors('status');
    }

    public function test_admin_menandai_dibalas_menyimpan_catatan_dan_membuka_kembali(): void
    {
        $admin = User::factory()->create(['jabatan' => 'admin']);
        $pertanyaan = PertanyaanTamu::factory()->create();

        $this->actingAs($admin)->patch(route('admin.pertanyaan-tamu.perbarui', $pertanyaan), ['status' => 'dibalas', 'catatan_admin' => 'Sudah dibalas via WA'])
            ->assertSessionHasNoErrors();
        $pertanyaan->refresh();
        $this->assertSame('dibalas', $pertanyaan->status);
        $this->assertSame('Sudah dibalas via WA', $pertanyaan->catatan_admin);

        $this->actingAs($admin)->patch(route('admin.pertanyaan-tamu.perbarui', $pertanyaan), ['catatan_admin' => 'Catatan baru'])
            ->assertSessionHasNoErrors();
        $this->assertSame('dibalas', $pertanyaan->fresh()->status);

        $this->actingAs($admin)->patch(route('admin.pertanyaan-tamu.perbarui', $pertanyaan), ['status' => 'baru']);
        $this->assertSame('baru', $pertanyaan->fresh()->status);
    }

    public function test_status_tidak_valid_ditolak_dan_jadi_booking_tidak_bisa_diubah_manual(): void
    {
        $admin = User::factory()->create(['jabatan' => 'admin']);
        $pertanyaan = PertanyaanTamu::factory()->create();

        $this->actingAs($admin)->patch(route('admin.pertanyaan-tamu.perbarui', $pertanyaan), ['status' => 'jadi_booking'])
            ->assertSessionHasErrors('status');

        $jadiBooking = PertanyaanTamu::factory()->create(['status' => 'jadi_booking']);
        $this->actingAs($admin)->patch(route('admin.pertanyaan-tamu.perbarui', $jadiBooking), ['status' => 'tidak_lanjut', 'catatan_admin' => 'Catatan akhir']);
        $this->assertSame('jadi_booking', $jadiBooking->fresh()->status);
        $this->assertSame('Catatan akhir', $jadiBooking->fresh()->catatan_admin);
    }

    public function test_melanjutkan_pertanyaan_ke_form_booking_menautkannya_ke_permintaan_form(): void
    {
        $admin = User::factory()->create(['jabatan' => 'admin']);
        $pertanyaan = PertanyaanTamu::factory()->dariKontak()->create(['whatsapp' => '081234567890']);

        $respons = $this->actingAs($admin)->post(route('admin.permintaan-pesanan.kirim-form'), [
            'whatsapp' => '081234567890',
            'pertanyaan_id' => $pertanyaan->id,
        ]);

        $permintaan = PermintaanPesanan::query()->firstOrFail();
        $respons->assertRedirectContains('https://wa.me/6281234567890');
        $pertanyaan->refresh();
        $this->assertSame('dibalas', $pertanyaan->status);
        $this->assertSame($permintaan->id, $pertanyaan->permintaan_pesanan_id);

        $this->actingAs($admin)->get(route('admin.pertanyaan-tamu.indeks'))
            ->assertOk()
            ->assertSee('Form booking sudah dikirim ke tamu');

        $journey = Perjalanan::factory()->create();
        $this->bukaTanggal($journey, now()->addDays(10));
        $this->post(route('form-pesanan.simpan', $permintaan->token), [
            'nama_lengkap' => 'Budi',
            'whatsapp' => '081234567890',
            'perjalanan_id' => $journey->id,
            'tanggal_trip' => now()->addDays(10)->toDateString(),
            'jumlah_wisatawan' => 2,
            'status_penginapan' => 'sudah',
            'nama_hotel' => 'Hotel A',
            'alamat_hotel' => 'Jl. Uji Alamat No. 1, Kota Uji',
            'lokasi_pickup' => 'Hotel A',
            'kendaraan_pilihan' => 'Jeep klasik (petualangan dataran tinggi) — Toyota Land Cruiser FJ40',
        ])->assertSessionHasNoErrors();

        $this->assertSame('jadi_booking', $pertanyaan->fresh()->status);
        $this->actingAs($admin)->get(route('admin.pertanyaan-tamu.indeks'))
            ->assertSee('Sudah dilanjutkan ke form booking');
    }

    public function test_permintaan_ditutup_tidak_jadi_menutup_pertanyaan_asal_dan_form_baru_dapat_dikirim_ulang(): void
    {
        $admin = User::factory()->create(['jabatan' => 'admin']);
        $pertanyaan = PertanyaanTamu::factory()->create(['whatsapp' => '081234567890']);
        $kirim = fn () => $this->actingAs($admin)->post(route('admin.permintaan-pesanan.kirim-form'), [
            'whatsapp' => '081234567890',
            'pertanyaan_id' => $pertanyaan->id,
        ]);

        $kirim();
        $pertama = PermintaanPesanan::query()->firstOrFail();
        $this->actingAs($admin)->post(route('admin.permintaan-pesanan.tidak-jadi', $pertama));
        $this->assertSame('tidak_lanjut', $pertanyaan->fresh()->status);

        $kirim();
        $kedua = PermintaanPesanan::query()->latest('id')->firstOrFail();
        $this->assertNotSame($pertama->id, $kedua->id);
        $this->assertSame($kedua->id, $pertanyaan->fresh()->permintaan_pesanan_id);
        $this->assertSame('dibalas', $pertanyaan->fresh()->status);
    }

    public function test_kirim_form_dengan_pertanyaan_yang_tidak_ada_ditolak(): void
    {
        $this->actingAs(User::factory()->create(['jabatan' => 'admin']))->post(route('admin.permintaan-pesanan.kirim-form'), [
            'whatsapp' => '081234567890',
            'pertanyaan_id' => 9999,
        ])->assertSessionHasErrors('pertanyaan_id');
        $this->assertDatabaseCount('permintaan_pesanan', 0);
    }

    public function test_notifikasi_pertanyaan_masuk_ke_lonceng_dan_mengarah_ke_halaman_pertanyaan(): void
    {
        $admin = User::factory()->create(['jabatan' => 'admin']);
        $this->postJson(route('pertanyaan.simpan'), [
            'sumber' => 'kontak',
            'journey' => 'IJEN VOLCANIC ESCAPE',
            'pesan' => 'Halo Mistborne, saya ingin bertanya.',
        ])->assertNoContent();

        $respons = $this->actingAs($admin)->get(route('admin.notifikasi.ringkasan'));
        $respons->assertJsonPath('jumlah_pertanyaan_baru', 1)
            ->assertJsonPath('jumlah_belum_dibaca', 1)
            ->assertJsonPath('daftar.0.jenis', 'pertanyaan')
            ->assertJsonPath('daftar.0.label', 'Pertanyaan');
        $this->assertStringContainsString('IJEN VOLCANIC ESCAPE', $respons->json('daftar.0.baris.0'));

        $this->actingAs($admin)->post($respons->json('daftar.0.url_baca'))
            ->assertRedirect(route('admin.pertanyaan-tamu.indeks'));
    }

    public function test_halaman_pertanyaan_tidak_bisa_dibuka_tanpa_login_admin(): void
    {
        $this->get(route('admin.pertanyaan-tamu.indeks'))->assertRedirect(route('admin.autentikasi.masuk'));
    }
}
