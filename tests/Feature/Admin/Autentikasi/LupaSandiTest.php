<?php

namespace Tests\Feature\Admin\Autentikasi;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class LupaSandiTest extends TestCase
{
    public function test_halaman_lupa_sandi_bisa_diakses(): void
    {
        $this->get(route('admin.autentikasi.lupa-sandi'))->assertOk();
    }

    public function test_tautan_reset_dikirim_untuk_email_terdaftar(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->post(route('admin.autentikasi.kirim-tautan-reset'), [
            'email' => $user->email,
        ]);

        $response->assertSessionHas('status');
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_kata_sandi_baru_tersimpan_dengan_token_valid(): void
    {
        $user = User::factory()->create(['password' => 'kata-sandi-lama']);

        $token = Password::broker('users')->createToken($user);

        $response = $this->post(route('admin.autentikasi.simpan-sandi-baru'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'kata-sandi-baru-123',
            'password_confirmation' => 'kata-sandi-baru-123',
        ]);

        $response->assertRedirect(route('admin.autentikasi.masuk'));
        $this->assertTrue(Hash::check('kata-sandi-baru-123', $user->fresh()->password));
    }
}
