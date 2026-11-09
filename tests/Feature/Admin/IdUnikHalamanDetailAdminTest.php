<?php

namespace Tests\Feature\Admin;

use App\Enums\StatusPesananKustom;
use App\Models\Pesanan;
use App\Models\PesananKustom;
use App\Models\User;
use Tests\TestCase;

/**
 * Halaman detail memuat beberapa formulir sekaligus (modal pembatalan, modal "Jadikan Pesanan Resmi"). Id kolom harus
 * unik supaya <label for> dan pembaca layar menunjuk ke kolom yang benar.
 */
class IdUnikHalamanDetailAdminTest extends TestCase
{
    public function test_detail_pesanan_tidak_punya_id_ganda_walau_memuat_dua_modal_pembatalan(): void
    {
        $pesanan = Pesanan::factory()->create();

        $html = $this->actingAs(User::factory()->create())->get(route('admin.pesanan.detail', $pesanan))->assertOk()->getContent();

        $this->assertStringContainsString('id="alasan_batal_bencana"', $html);
        $this->assertStringContainsString('id="alasan_batal_sebelum_trip"', $html);
        $this->assertSame([], $this->idGanda($html));
    }

    public function test_detail_pesanan_kustom_tidak_punya_id_ganda_saat_modal_jadikan_resmi_tampil(): void
    {
        $kustom = PesananKustom::factory()->create([
            'status' => StatusPesananKustom::MenungguJawaban,
            'tanggal_fleksibel' => false,
            'tanggal_diinginkan' => now()->addDays(30)->toDateString(),
            'harga_final' => 2_000_000,
        ]);

        $html = $this->actingAs(User::factory()->create())->get(route('admin.pesanan-kustom.detail', $kustom->id))->assertOk()->getContent();

        $this->assertStringContainsString('id="resmi_status_penginapan"', $html);
        $this->assertSame([], $this->idGanda($html));
    }

    /** @return list<string> */
    private function idGanda(string $html): array
    {
        preg_match_all('/\sid="([^"]+)"/', $html, $cocok);

        return array_values(array_keys(array_filter(array_count_values($cocok[1]), fn (int $jumlah): bool => $jumlah > 1)));
    }
}
