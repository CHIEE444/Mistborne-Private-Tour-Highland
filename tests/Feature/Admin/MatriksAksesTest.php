<?php

namespace Tests\Feature\Admin;

use App\Models\PersonelOperasional;
use App\Models\Pesanan;
use App\Models\User;
use Illuminate\Routing\Route as RuteLaravel;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class MatriksAksesTest extends TestCase
{
    private const RUTE_TERBUKA = [
        'admin.autentikasi.masuk', 'admin.autentikasi.proses-masuk', 'admin.autentikasi.lupa-sandi',
        'admin.autentikasi.kirim-tautan-reset', 'admin.autentikasi.atur-ulang-sandi', 'admin.autentikasi.simpan-sandi-baru',
        'personel.masuk', 'personel.proses-masuk',
    ];

    /**
     * @return list<RuteLaravel>
     */
    private function ruteBerawalan(string $awalan): array
    {
        return collect(Route::getRoutes()->getRoutes())
            ->filter(fn (RuteLaravel $rute) => str_starts_with((string) $rute->getName(), $awalan)
                && ! in_array($rute->getName(), self::RUTE_TERBUKA, true))
            ->values()
            ->all();
    }

    private function panggil(RuteLaravel $rute): TestResponse
    {
        $uri = '/'.preg_replace('/\{[^}]+\}/', '1', $rute->uri());
        $metode = collect($rute->methods())->first(fn (string $m) => ! in_array($m, ['HEAD'], true));

        return $this->call($metode, $uri);
    }

    public function test_semua_rute_admin_menolak_tamu_tanpa_login_dan_semua_jabatan_non_admin(): void
    {
        $rute = $this->ruteBerawalan('admin.');
        $this->assertGreaterThan(80, count($rute));

        $pengguna = [
            'anonim' => null,
            'tamu' => User::factory()->tamu()->create(),
            'guide' => User::factory()->create(['jabatan' => 'guide']),
            'tanpa jabatan' => User::factory()->create(['jabatan' => null]),
        ];

        foreach ($pengguna as $label => $user) {
            foreach ($rute as $r) {
                $this->flushSession();
                $user ? $this->actingAs($user) : $this->app['auth']->forgetGuards();

                $respons = $this->panggil($r);

                $this->assertTrue(
                    $respons->isRedirect(route('admin.autentikasi.masuk')),
                    "[$label] {$r->getName()} ({$r->methods()[0]} {$r->uri()}) seharusnya dialihkan ke login admin, status {$respons->getStatusCode()}",
                );
            }
        }
    }

    public function test_semua_rute_panel_guide_menolak_anonim_admin_dan_tamu(): void
    {
        $rute = $this->ruteBerawalan('personel.');
        $this->assertCount(7, $rute);

        $pengguna = [
            'anonim' => null,
            'admin' => User::factory()->create(),
            'tamu' => User::factory()->tamu()->create(),
            'guide tanpa data personel' => User::factory()->create(['jabatan' => 'guide']),
        ];

        foreach ($pengguna as $label => $user) {
            foreach ($rute as $r) {
                $this->flushSession();
                $user ? $this->actingAs($user) : $this->app['auth']->forgetGuards();

                $respons = $this->panggil($r);

                $this->assertTrue(
                    $respons->isRedirect(route('personel.masuk')),
                    "[$label] {$r->getName()} seharusnya dialihkan ke login guide, status {$respons->getStatusCode()}",
                );
            }
        }
    }

    public function test_guide_tidak_bisa_menyelesaikan_trip_milik_guide_lain(): void
    {
        $akun = User::factory()->create(['jabatan' => 'guide']);
        $akun->personel()->create([
            'nama' => 'Guide A', 'peran' => 'guide', 'email' => $akun->email, 'status_operasional' => 'siap', 'aktif' => true,
        ]);
        $guideLain = PersonelOperasional::query()->create([
            'nama' => 'Guide B', 'peran' => 'guide', 'email' => 'guide-b@example.test', 'status_operasional' => 'siap', 'aktif' => true,
        ]);
        $pesanan = Pesanan::factory()->create([
            'guide_personel_id' => $guideLain->id,
            'status_konfirmasi_guide' => 'diterima',
            'tanggal_trip' => now()->subDays(3),
        ]);

        $this->actingAs($akun)
            ->post(route('personel.pesanan.trip-selesai', $pesanan))
            ->assertForbidden();

        $this->assertNull($pesanan->fresh()->guide_trip_selesai_at);
    }
}
