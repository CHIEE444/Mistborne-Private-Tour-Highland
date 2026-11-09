<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Tests\TestCase;

class AksesPanelAdminTest extends TestCase
{
    public function test_akun_tanpa_jabatan_tidak_bisa_membuka_panel_admin(): void
    {
        $akun = User::factory()->create(['jabatan' => null]);

        $this->actingAs($akun)
            ->get(route('admin.pesanan.indeks'))
            ->assertRedirect(route('admin.autentikasi.masuk'));

        $this->assertGuest();
    }

    public function test_jabatan_selain_admin_ditolak_dan_jabatan_admin_diterima(): void
    {
        foreach (['tamu', 'guide', 'Kasir'] as $jabatan) {
            $this->actingAs(User::factory()->create(['jabatan' => $jabatan]))
                ->get(route('admin.pesanan.indeks'))
                ->assertRedirect(route('admin.autentikasi.masuk'));
        }

        foreach (['admin', 'Admin Operasional'] as $jabatan) {
            $this->actingAs(User::factory()->create(['jabatan' => $jabatan]))
                ->get(route('admin.pesanan.indeks'))
                ->assertOk();
        }
    }
}
