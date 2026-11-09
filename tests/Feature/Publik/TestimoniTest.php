<?php

namespace Tests\Feature\Publik;

use App\Models\Pesanan;
use App\Models\Ulasan;
use Tests\TestCase;

class TestimoniTest extends TestCase
{
    public function test_beranda_tidak_error_tanpa_ulasan_sama_sekali(): void
    {
        $this->get('/')->assertOk()->assertDontSee('id="testimoni"', false);
    }

    public function test_ulasan_disetujui_dan_diizinkan_tampil_di_beranda(): void
    {
        $pesanan = Pesanan::factory()->completed()->create(['nama_lengkap' => 'Penulis Ulasan Publik']);
        Ulasan::factory()->disetujui()->create(['pesanan_id' => $pesanan->id, 'komentar' => 'Komentar publik yang tampil di beranda']);

        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('Penulis Ulasan Publik', $html);
        $this->assertStringContainsString('Komentar publik yang tampil di beranda', $html);
    }

    public function test_ulasan_belum_disetujui_tidak_tampil_di_beranda(): void
    {
        $pesanan = Pesanan::factory()->completed()->create(['nama_lengkap' => 'Belum Disetujui Nama']);
        Ulasan::factory()->create(['pesanan_id' => $pesanan->id, 'disetujui' => false, 'komentar' => 'Komentar belum disetujui unik']);

        $html = $this->get('/')->getContent();

        $this->assertStringNotContainsString('Komentar belum disetujui unik', $html);
    }

    public function test_ulasan_tanpa_izin_tampil_tidak_tampil_di_beranda(): void
    {
        $pesanan = Pesanan::factory()->completed()->create();
        Ulasan::factory()->disetujui()->tanpaIzinTampil()->create(['pesanan_id' => $pesanan->id, 'komentar' => 'Komentar tanpa izin tampil unik']);

        $html = $this->get('/')->getContent();

        $this->assertStringNotContainsString('Komentar tanpa izin tampil unik', $html);
    }
}
