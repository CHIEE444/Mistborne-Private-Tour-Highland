<?php

namespace Tests\Feature\Publik;

use App\Models\User;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AsetDanTampilanTest extends TestCase
{
    public function test_semua_ikon_yang_dirujuk_head_dan_manifest_ada_dengan_ukuran_benar(): void
    {
        $manifest = json_decode((string) file_get_contents(public_path('site.webmanifest')), true);

        $ikon = collect($manifest['icons'])->mapWithKeys(fn (array $i): array => [ltrim($i['src'], '/') => $i['sizes']])
            ->put('apple-touch-icon.png', '180x180');

        foreach ($ikon as $berkas => $ukuran) {
            $this->assertFileExists(public_path($berkas));
            [$lebar, $tinggi] = explode('x', $ukuran);
            $this->assertSame([(int) $lebar, (int) $tinggi], array_slice(getimagesize(public_path($berkas)), 0, 2), $berkas);
        }

        $this->get('/')->assertSee('rel="apple-touch-icon" href="/apple-touch-icon.png"', false);
    }

    public function test_situs_publik_dan_panel_admin_memakai_ikon_tab_masing_masing(): void
    {
        foreach (['favicon.ico', 'favicon.svg', 'favicon-admin.ico', 'favicon-admin.svg', 'apple-touch-icon-admin.png'] as $berkas) {
            $this->assertGreaterThan(0, filesize(public_path($berkas)), $berkas);
        }

        $this->get('/')->assertOk()
            ->assertSee('<link rel="icon" href="/favicon.svg" type="image/svg+xml" />', false)
            ->assertDontSee('favicon-admin', false);
        $this->get(route('admin.autentikasi.masuk'))->assertOk()
            ->assertSee('<link rel="icon" href="/favicon-admin.svg" type="image/svg+xml" />', false)
            ->assertSee('href="/apple-touch-icon-admin.png"', false);
        $this->actingAs(User::factory()->create(['jabatan' => 'admin']))->get(route('admin.dashboard.indeks'))->assertOk()
            ->assertSee('href="/favicon-admin.ico"', false);
    }

    public function test_honeypot_form_kustom_tidak_disembunyikan_dengan_offset_negatif_yang_melebarkan_halaman_rtl(): void
    {
        $html = $this->get(route('rancang-trip-kustom'))->assertOk()->getContent();

        $this->assertStringNotContainsString('9999px', $html);
        $this->assertMatchesRegularExpression('/<div class="sr-only" aria-hidden="true">\s*<label for="website">/', $html);
    }

    public function test_menu_bahasa_tidak_memakai_plugin_alpine_focus_yang_tidak_dipasang(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('x-trap', $html);
        $this->assertStringNotContainsString('$focus', $html);
        $this->assertStringContainsString('pindahFokusMenu', $html);
        $this->assertStringContainsString('window.pindahFokusMenu', (string) file_get_contents(resource_path('js/app.js')));
    }

    /** x-data="nama()" tanpa Alpine.data('nama') membuat seluruh komponen (mis. drawer menu admin) mati dengan ReferenceError. */
    public function test_setiap_komponen_alpine_yang_dipakai_view_terdaftar(): void
    {
        $sumber = collect(File::allFiles(resource_path('js')))->merge(File::allFiles(resource_path('views')))
            ->map(fn ($berkas): string => $berkas->getContents())->implode("\n");
        preg_match_all('/Alpine\.data\(\s*[\'"](\w+)[\'"]/', $sumber, $terdaftar);
        preg_match_all('/x-data="(\w+)\(/', $sumber, $dipakai);

        $this->assertSame([], array_values(array_diff(array_unique($dipakai[1]), $terdaftar[1])));
    }

    /** Alpine hanya mengevaluasi ekspresi; pernyataan seperti try/for di x-init atau x-on memicu SyntaxError. */
    public function test_atribut_alpine_tidak_diawali_pernyataan_javascript(): void
    {
        $pelanggaran = collect(File::allFiles(resource_path('views')))
            ->filter(fn ($berkas): bool => (bool) preg_match('/\s(?:x-init|x-on:[\w.-]+|@[\w.-]+)="\s*(?:try|for|while|switch)\b/', $berkas->getContents()))
            ->map(fn ($berkas): string => $berkas->getRelativePathname())
            ->values()
            ->all();

        $this->assertSame([], $pelanggaran);
    }
}
