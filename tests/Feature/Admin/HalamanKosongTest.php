<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class HalamanKosongTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function rutePrivatProvider(): array
    {
        return [
            'dashboard' => ['admin.dashboard.indeks'],
            'pesanan' => ['admin.pesanan.indeks'],
            'pesanan kustom' => ['admin.pesanan-kustom.indeks'],
            'kalender trip' => ['admin.kalender-trip.indeks'],
            'perjalanan' => ['admin.perjalanan.indeks'],
            'artikel' => ['admin.artikel.indeks'],
            'ulasan' => ['admin.ulasan.indeks'],
            'pengaturan' => ['admin.pengaturan.indeks'],
        ];
    }

    #[DataProvider('rutePrivatProvider')]
    public function test_admin_yang_sudah_masuk_bisa_mengakses_halaman(string $namaRoute): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route($namaRoute))->assertOk();
    }

    #[DataProvider('rutePrivatProvider')]
    public function test_tamu_diarahkan_ke_halaman_masuk(string $namaRoute): void
    {
        $this->get(route($namaRoute))->assertRedirect(route('admin.autentikasi.masuk'));
    }
}
