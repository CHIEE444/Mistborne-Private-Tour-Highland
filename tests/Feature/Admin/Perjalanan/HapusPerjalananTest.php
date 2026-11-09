<?php

namespace Tests\Feature\Admin\Perjalanan;

use App\Models\Perjalanan;
use App\Models\User;
use Tests\TestCase;

class HapusPerjalananTest extends TestCase
{
    public function test_journey_tanpa_pesanan_aktif_boleh_dihapus(): void
    {
        $user = User::factory()->create();
        $journey = Perjalanan::factory()->create();

        $response = $this->actingAs($user)->delete(route('admin.perjalanan.hapus', $journey));

        $response->assertRedirect(route('admin.perjalanan.indeks'));
        $this->assertSoftDeleted('perjalanan', ['id' => $journey->id]);
    }

    public function test_sembunyikan_dari_beranda_mematikan_tampil_beranda(): void
    {
        $user = User::factory()->create();
        $journey = Perjalanan::factory()->create(['tampil_beranda' => true]);

        $response = $this->actingAs($user)->patch(route('admin.perjalanan.sembunyikan', $journey));

        $response->assertRedirect(route('admin.perjalanan.indeks'));
        $this->assertFalse($journey->fresh()->tampil_beranda);
        $this->assertNull($journey->fresh()->deleted_at);
    }

    public function test_tamu_tidak_bisa_menghapus_journey(): void
    {
        $journey = Perjalanan::factory()->create();

        $this->delete(route('admin.perjalanan.hapus', $journey))
            ->assertRedirect(route('admin.autentikasi.masuk'));

        $this->assertDatabaseHas('perjalanan', ['id' => $journey->id, 'deleted_at' => null]);
    }
}
