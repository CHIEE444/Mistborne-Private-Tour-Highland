<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfilTest extends TestCase
{
    public function test_tamu_tidak_bisa_mengakses_halaman_profil(): void
    {
        $this->get(route('admin.profil.ubah'))->assertRedirect(route('admin.autentikasi.masuk'));
    }

    public function test_admin_bisa_melihat_halaman_profil(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.profil.ubah'))
            ->assertOk()
            ->assertSee($user->name);
    }

    public function test_admin_bisa_memperbarui_nama_dan_jabatan(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('admin.profil.perbarui'), [
            'name' => 'Nama Baru',
            'jabatan' => 'Admin',
        ]);

        $response->assertRedirect();
        $this->assertSame('Nama Baru', $user->fresh()->name);
        $this->assertSame('Admin', $user->fresh()->jabatan);
    }

    public function test_admin_tidak_bisa_mengganti_jabatannya_menjadi_selain_admin(): void
    {
        $user = User::factory()->create(['jabatan' => 'Admin Operasional']);

        foreach (['Guide', 'tamu', 'Operations Director', ''] as $jabatan) {
            $this->actingAs($user)
                ->put(route('admin.profil.perbarui'), ['name' => $user->name, 'jabatan' => $jabatan])
                ->assertSessionHasErrors('jabatan');
        }

        $this->assertSame('Admin Operasional', $user->fresh()->jabatan);
        $this->actingAs($user->fresh())->get(route('admin.pesanan.indeks'))->assertOk();
    }

    public function test_admin_bisa_mengganti_kata_sandi(): void
    {
        $user = User::factory()->create(['password' => 'kata-sandi-lama']);

        $this->actingAs($user)->put(route('admin.profil.perbarui'), [
            'name' => $user->name,
            'password' => 'kata-sandi-baru-123',
            'password_confirmation' => 'kata-sandi-baru-123',
        ]);

        $this->assertTrue(Hash::check('kata-sandi-baru-123', $user->fresh()->password));
    }
}
