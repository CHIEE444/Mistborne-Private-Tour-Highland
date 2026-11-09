<?php

namespace Tests\Feature\Admin\Autentikasi;

use App\Models\User;
use Tests\TestCase;

class MasukTest extends TestCase
{
    public function test_halaman_masuk_bisa_diakses(): void
    {
        $this->get(route('admin.autentikasi.masuk'))->assertOk();
    }

    public function test_admin_bisa_masuk_dengan_kredensial_benar(): void
    {
        $user = User::factory()->create(['password' => 'kata-sandi-benar']);

        $response = $this->post(route('admin.autentikasi.proses-masuk'), [
            'email' => $user->email,
            'password' => 'kata-sandi-benar',
        ]);

        $response->assertRedirect(route('admin.dashboard.indeks'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_masuk_gagal_dengan_kata_sandi_salah_menampilkan_pesan_dan_sisa_percobaan(): void
    {
        $user = User::factory()->create(['password' => 'kata-sandi-benar']);

        $response = $this->from(route('admin.autentikasi.masuk'))->post(route('admin.autentikasi.proses-masuk'), [
            'email' => $user->email,
            'password' => 'salah',
        ]);

        $response->assertRedirect(route('admin.autentikasi.masuk'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_akun_terkunci_setelah_lima_kali_gagal(): void
    {
        $user = User::factory()->create(['password' => 'kata-sandi-benar']);

        for ($percobaan = 1; $percobaan <= 5; $percobaan++) {
            $this->post(route('admin.autentikasi.proses-masuk'), [
                'email' => $user->email,
                'password' => 'salah',
            ]);
        }

        // Percobaan ke-6, walau kata sandi benar, tetap ditolak karena sudah terkunci.
        $response = $this->post(route('admin.autentikasi.proses-masuk'), [
            'email' => $user->email,
            'password' => 'kata-sandi-benar',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_halaman_masuk_punya_dua_kategori_admin_dan_guide(): void
    {
        foreach (['admin.autentikasi.masuk', 'personel.masuk'] as $rute) {
            $this->get(route($rute))
                ->assertOk()
                ->assertSee('Masuk sebagai Admin')
                ->assertSee('Masuk sebagai Guide')
                ->assertSee(route('admin.autentikasi.proses-masuk'), false)
                ->assertSee(route('personel.proses-masuk'), false);
        }
    }
}
