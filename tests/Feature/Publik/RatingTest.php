<?php

namespace Tests\Feature\Publik;

use App\Models\Pesanan;
use App\Models\Ulasan;
use Tests\TestCase;

class RatingTest extends TestCase
{
    public function test_token_tidak_valid_menampilkan_pesan_error(): void
    {
        $response = $this->get(route('rating.formulir', 'token-tidak-ada'));

        $response->assertNotFound()->assertSee('Tautan ulasan tidak valid');
    }

    public function test_pesanan_belum_completed_tautan_tidak_aktif(): void
    {
        $pesanan = Pesanan::factory()->payment()->create();

        $response = $this->get(route('rating.formulir', $pesanan->token_rating));

        $response->assertOk()->assertSee('sudah tidak berlaku');
    }

    public function test_pesanan_completed_dalam_jendela_waktu_form_tampil(): void
    {
        $pesanan = Pesanan::factory()->completed()->create(['tanggal_selesai' => now()->subDays(2)]);

        $response = $this->get(route('rating.formulir', $pesanan->token_rating));

        $response->assertOk()->assertSee('Kirim Ulasan');
    }

    public function test_pesanan_completed_lewat_batas_waktu_tautan_tidak_aktif(): void
    {
        $pesanan = Pesanan::factory()->completed()->create(['tanggal_selesai' => now()->subDays(10)]);

        $response = $this->get(route('rating.formulir', $pesanan->token_rating));

        $response->assertOk()->assertSee('sudah tidak berlaku');
    }

    public function test_submit_ulasan_valid(): void
    {
        $pesanan = Pesanan::factory()->completed()->create(['tanggal_selesai' => now()->subDay()]);

        $response = $this->post(route('rating.simpan', $pesanan->token_rating), [
            'rating' => 5,
            'komentar' => 'Perjalanan sangat menyenangkan dan rapi.',
            'izin_tampil' => '1',
        ]);

        $response->assertRedirect(route('rating.formulir', $pesanan->token_rating));

        $ulasan = Ulasan::where('pesanan_id', $pesanan->id)->first();
        $this->assertNotNull($ulasan);
        $this->assertSame(5, $ulasan->rating);
        $this->assertTrue($ulasan->izin_tampil);
        $this->assertFalse($ulasan->disetujui);
    }

    public function test_ulasan_baru_selalu_menunggu_persetujuan(): void
    {
        $pesanan = Pesanan::factory()->completed()->create(['tanggal_selesai' => now()->subDay()]);

        $this->post(route('rating.simpan', $pesanan->token_rating), [
            'rating' => 4,
            'komentar' => 'Bagus.',
        ]);

        $this->assertFalse(Ulasan::where('pesanan_id', $pesanan->id)->first()->disetujui);
    }

    public function test_tidak_bisa_submit_ulasan_dua_kali(): void
    {
        $pesanan = Pesanan::factory()->completed()->create(['tanggal_selesai' => now()->subDay()]);
        Ulasan::factory()->create(['pesanan_id' => $pesanan->id]);

        $response = $this->post(route('rating.simpan', $pesanan->token_rating), [
            'rating' => 3,
            'komentar' => 'Coba kirim lagi.',
        ]);

        $response->assertNotFound();
        $this->assertSame(1, Ulasan::where('pesanan_id', $pesanan->id)->count());
    }

    public function test_setelah_mengirim_ulasan_halaman_menampilkan_sudah_terkirim(): void
    {
        $pesanan = Pesanan::factory()->completed()->create(['tanggal_selesai' => now()->subDay()]);
        Ulasan::factory()->create(['pesanan_id' => $pesanan->id]);

        $response = $this->get(route('rating.formulir', $pesanan->token_rating));

        $response->assertSee('sudah mengirimkan ulasan');
    }

    public function test_validasi_rating_dan_komentar_wajib(): void
    {
        $pesanan = Pesanan::factory()->completed()->create(['tanggal_selesai' => now()->subDay()]);

        $response = $this->post(route('rating.simpan', $pesanan->token_rating), []);

        $response->assertSessionHasErrors(['rating', 'komentar']);
    }
}
