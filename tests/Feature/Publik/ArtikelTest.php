<?php

namespace Tests\Feature\Publik;

use App\Models\Artikel;
use Tests\TestCase;

class ArtikelTest extends TestCase
{
    public function test_beranda_tidak_error_tanpa_artikel_sama_sekali(): void
    {
        $this->get('/')->assertOk()->assertDontSee('id="artikel"', false);
    }

    public function test_halaman_artikel_tidak_error_tanpa_artikel_sama_sekali(): void
    {
        $this->get('/artikel')->assertOk()->assertSee('Belum ada artikel yang diterbitkan');
    }

    public function test_artikel_terbit_tampil_di_beranda(): void
    {
        Artikel::factory()->create(['judul' => 'Artikel Terbit Unik di Beranda']);

        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('Artikel Terbit Unik di Beranda', $html);
    }

    public function test_artikel_draft_tidak_tampil_di_beranda(): void
    {
        Artikel::factory()->draf()->create(['judul' => 'Artikel Draft Tersembunyi Unik']);

        $html = $this->get('/')->getContent();

        $this->assertStringNotContainsString('Artikel Draft Tersembunyi Unik', $html);
    }

    public function test_artikel_dijadwalkan_tidak_tampil_di_beranda(): void
    {
        Artikel::factory()->dijadwalkan()->create(['judul' => 'Artikel Dijadwalkan Tersembunyi Unik']);

        $html = $this->get('/')->getContent();

        $this->assertStringNotContainsString('Artikel Dijadwalkan Tersembunyi Unik', $html);
    }

    public function test_artikel_terbit_tampil_di_halaman_artikel(): void
    {
        Artikel::factory()->create(['judul' => 'Artikel Terbit Unik di Arsip']);

        $html = $this->get('/artikel')->getContent();

        $this->assertStringContainsString('Artikel Terbit Unik di Arsip', $html);
    }

    public function test_artikel_terbaru_jadi_featured_di_halaman_artikel(): void
    {
        Artikel::factory()->create(['judul' => 'Artikel Lebih Lama', 'published_at' => now()->subDays(10)]);
        Artikel::factory()->create(['judul' => 'Artikel Paling Baru', 'published_at' => now()->subDay()]);

        $html = $this->get('/artikel')->getContent();

        $this->assertTrue(strpos($html, 'Artikel Paling Baru') < strpos($html, 'Artikel Lebih Lama'));
    }

    public function test_detail_artikel_menampilkan_format_dari_editor(): void
    {
        $artikel = Artikel::factory()->create(['isi' => '<h1>Subjudul</h1><div><strong>tebal</strong> <em>miring</em> <del>coret</del></div><blockquote>kutipan</blockquote><ul><li>satu</li></ul><ol><li>dua</li></ol><pre>kode</pre>']);

        $this->get(route('articles.show', $artikel->slug))
            ->assertOk()
            ->assertSee('<h2>Subjudul</h2>', false)
            ->assertSee('<strong>tebal</strong>', false)
            ->assertSee('<em>miring</em>', false)
            ->assertSee('<del>coret</del>', false)
            ->assertSee('<blockquote>kutipan</blockquote>', false)
            ->assertSee('<ul><li>satu</li></ul>', false)
            ->assertSee('<ol><li>dua</li></ol>', false)
            ->assertSee('<pre>kode</pre>', false);
    }

    public function test_detail_artikel_membersihkan_script_dan_atribut_berbahaya(): void
    {
        $artikel = Artikel::factory()->create(['isi' => '<div onclick="x()">aman <script>alert(1)</script><a href="javascript:alert(1)">jahat</a> <a href="https://contoh.id" onclick="y()">sehat</a></div>']);

        $html = $this->get(route('articles.show', $artikel->slug))->assertOk()->getContent();

        $this->assertStringNotContainsString('<script>alert', $html);
        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringNotContainsString('javascript:alert', $html);
        $this->assertStringContainsString('href="https://contoh.id"', $html);
    }

    public function test_detail_artikel_teks_polos_lama_dijadikan_paragraf(): void
    {
        $artikel = Artikel::factory()->create(['isi' => "Paragraf satu.\n\nParagraf dua."]);

        $this->get(route('articles.show', $artikel->slug))
            ->assertSee('<p>Paragraf satu.</p>', false)
            ->assertSee('<p>Paragraf dua.</p>', false);
    }

    public function test_draft_dan_dijadwalkan_tidak_bisa_dibuka_publik(): void
    {
        $draf = Artikel::factory()->draf()->create();
        $terjadwal = Artikel::factory()->dijadwalkan()->create();

        $this->get(route('articles.show', $draf->slug))->assertNotFound();
        $this->get(route('articles.show', $terjadwal->slug))->assertNotFound();
    }

    public function test_kartu_artikel_menaut_ke_halaman_detail(): void
    {
        $artikel = Artikel::factory()->create();

        $this->get('/artikel')->assertSee(route('articles.show', $artikel->slug), false);
    }
}
