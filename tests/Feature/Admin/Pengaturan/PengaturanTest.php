<?php

namespace Tests\Feature\Admin\Pengaturan;

use App\Bantuan\BatasUnggah;
use App\Models\JadwalKeberangkatan;
use App\Models\Pengaturan;
use App\Models\Perjalanan;
use App\Models\User;
use Tests\TestCase;

class PengaturanTest extends TestCase
{
    public function test_tamu_tidak_bisa_mengakses(): void
    {
        $this->get(route('admin.pengaturan.indeks'))->assertRedirect(route('admin.autentikasi.masuk'));
    }

    public function test_admin_bisa_melihat_halaman_pengaturan_dengan_nilai_saat_ini(): void
    {
        $user = User::factory()->create();
        Pengaturan::current()->update(['batas_bayar_hari' => 4, 'nomor_whatsapp_admin' => '6289999999999']);

        $response = $this->actingAs($user)->get(route('admin.pengaturan.indeks'));

        $response->assertOk()
            ->assertSee('6289999999999')
            ->assertSee('value="4"', false);
    }

    public function test_halaman_pengaturan_menyebut_batas_unggah_php_yang_berlaku(): void
    {
        $user = User::factory()->create();
        Pengaturan::current()->update(['batas_video_mb' => 100]);
        $server = BatasUnggah::serverMb();

        $respons = $this->actingAs($user)->get(route('admin.pengaturan.indeks'))->assertOk();

        if ($server === null) {
            $respons->assertDontSee('(server maks.');
        } else {
            $respons->assertSee("Batas Ukuran Video Testimoni (server maks. {$server} MB)");
        }
    }

    public function test_halaman_pengaturan_punya_tombol_keluar_dengan_konfirmasi(): void
    {
        $user = User::factory()->create(['jabatan' => 'admin']);

        $this->actingAs($user)->get(route('admin.pengaturan.indeks'))
            ->assertOk()
            ->assertSee('Keluar dari Admin Panel')
            ->assertSee("nama: 'konfirmasi-keluar'", false)
            ->assertSee('Yakin ingin keluar?');

        $this->actingAs($user)->post(route('admin.autentikasi.keluar'))->assertRedirect();
        $this->assertGuest();
    }

    public function test_admin_bisa_memperbarui_pengaturan(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('admin.pengaturan.perbarui'), [
            'nomor_whatsapp_admin' => '6281234567890',
            'persen_charge_ganti_jadwal' => 15,
            'batas_bayar_hari' => 5,
            'kuota_default' => 8,
            'batas_ulasan_hari' => 10,
            'batas_video_mb' => 200,
        ]);

        $response->assertRedirect();

        $pengaturan = Pengaturan::current()->fresh();
        $this->assertSame('6281234567890', $pengaturan->nomor_whatsapp_admin);
        $this->assertSame(15, $pengaturan->persen_charge_ganti_jadwal);
        $this->assertSame(5, $pengaturan->batas_bayar_hari);
        $this->assertSame(8, $pengaturan->kuota_default);
        $this->assertSame(10, $pengaturan->batas_ulasan_hari);
        $this->assertSame(200, $pengaturan->batas_video_mb);
    }

    public function test_nomor_whatsapp_harus_format_62(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('admin.pengaturan.perbarui'), [
            'nomor_whatsapp_admin' => '081234567890',
            'persen_charge_ganti_jadwal' => 10,
            'batas_bayar_hari' => 3,
            'kuota_default' => 5,
            'batas_ulasan_hari' => 7,
            'batas_video_mb' => 100,
        ]);

        $response->assertSessionHasErrors('nomor_whatsapp_admin');
    }

    public function test_persen_charge_tidak_boleh_lebih_dari_seratus(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('admin.pengaturan.perbarui'), [
            'nomor_whatsapp_admin' => '6281234567890',
            'persen_charge_ganti_jadwal' => 150,
            'batas_bayar_hari' => 3,
            'kuota_default' => 5,
            'batas_ulasan_hari' => 7,
            'batas_video_mb' => 100,
        ]);

        $response->assertSessionHasErrors('persen_charge_ganti_jadwal');
    }

    public function test_perubahan_kuota_default_mempengaruhi_jadwal_tanpa_kuota_khusus(): void
    {
        $journey = Perjalanan::factory()->create();
        $jadwal = JadwalKeberangkatan::factory()->create(['perjalanan_id' => $journey->id, 'kuota' => null]);

        Pengaturan::current()->update(['kuota_default' => 9]);

        $this->assertSame(9, $jadwal->fresh()->kuotaEfektif());
    }

    public function test_perubahan_kuota_default_tidak_mempengaruhi_jadwal_dengan_kuota_khusus(): void
    {
        $journey = Perjalanan::factory()->create();
        $jadwal = JadwalKeberangkatan::factory()->create(['perjalanan_id' => $journey->id, 'kuota' => 12]);

        Pengaturan::current()->update(['kuota_default' => 9]);

        $this->assertSame(12, $jadwal->fresh()->kuotaEfektif());
    }
}
