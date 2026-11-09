<?php

namespace Tests\Feature\Admin\Pesanan;

use App\Mail\PembatalanPenugasanPersonel;
use App\Models\PersonelOperasional;
use App\Models\Pesanan;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class HapusPesananBerbayarTest extends TestCase
{
    public function test_pesanan_dihubungi_tanpa_pembayaran_boleh_dihapus(): void
    {
        $pesanan = Pesanan::factory()->create(['nominal_dibayar' => null]);

        $this->actingAs(User::factory()->create())
            ->delete(route('admin.pesanan.hapus', $pesanan))
            ->assertRedirect(route('admin.pesanan.indeks'));

        $this->assertModelMissing($pesanan);
    }

    public function test_pesanan_batal_tanpa_pembayaran_boleh_dihapus(): void
    {
        $pesanan = Pesanan::factory()->dibatalkan()->create(['nominal_dibayar' => null]);

        $this->actingAs(User::factory()->create())->delete(route('admin.pesanan.hapus', $pesanan));

        $this->assertModelMissing($pesanan);
    }

    public function test_pesanan_yang_sudah_menerima_uang_atau_selesai_tetap_bisa_dihapus_dengan_peringatan(): void
    {
        $admin = User::factory()->create();
        $kasus = [
            Pesanan::factory()->payment()->create(['harga' => 2_000_000, 'nominal_dibayar' => 2_000_000]),
            Pesanan::factory()->completed()->create(['harga' => 2_000_000, 'nominal_dibayar' => 2_000_000]),
            Pesanan::factory()->dibatalkan()->create(['harga' => 2_000_000, 'nominal_dibayar' => 600_000, 'nominal_refund' => 300_000]),
        ];

        foreach ($kasus as $pesanan) {
            $this->actingAs($admin)->get(route('admin.pesanan.detail', $pesanan))->assertSee('sudah punya catatan keuangan');

            $this->actingAs($admin)
                ->delete(route('admin.pesanan.hapus', $pesanan))
                ->assertRedirect(route('admin.pesanan.indeks'));

            $this->assertModelMissing($pesanan);
        }
    }

    public function test_setiap_baris_daftar_pesanan_punya_tombol_hapus(): void
    {
        $admin = User::factory()->create();
        $bebas = Pesanan::factory()->create(['nominal_dibayar' => null]);
        $berbayar = Pesanan::factory()->payment()->create(['harga' => 2_000_000, 'nominal_dibayar' => 2_000_000]);

        $this->actingAs($admin)->get(route('admin.pesanan.indeks'))->assertOk()
            ->assertSee('Hapus pesanan '.$bebas->kode)
            ->assertSee('Hapus pesanan '.$berbayar->kode)
            ->assertSee(route('admin.pesanan.hapus', $berbayar), false);
    }

    public function test_menghapus_pesanan_aktif_mengabari_guide_lewat_email(): void
    {
        Mail::fake();
        $guide = PersonelOperasional::factory()->create(['peran' => 'guide', 'email' => 'guide.hapus@example.com']);
        $pesanan = Pesanan::factory()->create(['nominal_dibayar' => null, 'guide_personel_id' => $guide->id, 'nama_guide' => $guide->nama, 'status_konfirmasi_guide' => 'diterima']);

        $this->actingAs(User::factory()->create())->delete(route('admin.pesanan.hapus', $pesanan));

        $this->assertModelMissing($pesanan);
        Mail::assertSent(PembatalanPenugasanPersonel::class, fn (PembatalanPenugasanPersonel $mail): bool => $mail->hasTo('guide.hapus@example.com'));
    }
}
