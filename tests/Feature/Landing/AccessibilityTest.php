<?php

namespace Tests\Feature\Landing;

use Tests\Support\ParsesHtml;
use Tests\TestCase;

/**
 * Tes aksesibilitas: skip link, landmark, atribut section, gambar (alt,
 * dimensi, decoding, loading), ikon dekoratif, tombol tanpa teks, dan
 * keamanan tautan eksternal (10 kasus: A11Y).
 */
class AccessibilityTest extends TestCase
{
    use ParsesHtml;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    /**
     * @testdox A11Y-01 elemen fokus pertama adalah skip link ke main#konten
     */
    public function test_a11y_01(): void
    {
        $xp = $this->parseHtml($this->fetchHtml('/'));

        $main = $xp->query('//main[@id="konten"]');
        $this->assertSame(1, $main->length, 'main#konten tidak ada');

        $focusable = $xp->query(
            '(//body//a[@href] | //body//button | //body//input | //body//select | //body//textarea)[1]'
        );
        $this->assertGreaterThan(0, $focusable->length, 'Tidak ada elemen fokus di <body>');
        /** @var \DOMElement $first */
        $first = $focusable->item(0);
        $this->assertSame('a', $first->nodeName, 'Elemen fokus pertama harus <a>');
        $this->assertSame('#konten', $first->getAttribute('href'), 'Elemen fokus pertama harus skip link');
    }

    /**
     * @testdox A11Y-02 landmark header, nav utama, main, footer ada
     */
    public function test_a11y_02(): void
    {
        $xp = $this->parseHtml($this->fetchHtml('/'));

        $this->assertSame(1, $xp->query('//header')->length, 'header tidak ada');
        $this->assertGreaterThan(0, $xp->query('//nav[@aria-label="Navigasi utama"]')->length, 'nav utama tidak ada');
        $this->assertSame(1, $xp->query('//main[@id="konten"]')->length, 'main#konten tidak ada');
        $this->assertSame(1, $xp->query('//footer')->length, 'footer tidak ada');
    }

    /**
     * @testdox A11Y-03 setiap section punya aria-labelledby (valid) atau aria-label
     */
    public function test_a11y_03(): void
    {
        $xp = $this->parseHtml($this->fetchHtml('/'));
        $sections = $xp->query('//section');

        $this->assertGreaterThan(0, $sections->length, 'Tidak ada <section>');

        foreach ($sections as $i => $section) {
            $labelledBy = trim($section->getAttribute('aria-labelledby'));
            $label = trim($section->getAttribute('aria-label'));

            if ($labelledBy !== '') {
                $target = $xp->query(sprintf('//*[@id="%s"]', $labelledBy));
                $this->assertSame(
                    1,
                    $target->length,
                    'section ke-'.($i + 1)." aria-labelledby=#{$labelledBy} tidak ada"
                );
            } else {
                $this->assertNotSame(
                    '',
                    $label,
                    'section ke-'.($i + 1).' (id='.$section->getAttribute('id').') tanpa aria-labelledby maupun aria-label'
                );
            }
        }
    }

    /**
     * @testdox A11Y-04 semua img punya alt spesifik
     */
    public function test_a11y_04(): void
    {
        $xp = $this->parseHtml($this->fetchHtml('/'));
        $images = $xp->query('//img');

        $this->assertGreaterThan(0, $images->length, 'Tidak ada <img>');
        $generic = ['gambar', 'image', 'foto', 'img', 'photo'];

        /** @var \DOMElement $img */
        foreach ($images as $img) {
            $index = $img->getAttribute('data-hero-slide');
            $this->assertTrue($img->hasAttribute('alt'), "img#{$index} tanpa atribut alt");
            $this->assertNotContains(
                strtolower(trim($img->getAttribute('alt'))),
                $generic,
                'alt generik: '.$img->getAttribute('alt')
            );
        }
    }

    /**
     * @testdox A11Y-05 semua img punya width, height numerik, dan decoding async
     */
    public function test_a11y_05(): void
    {
        $xp = $this->parseHtml($this->fetchHtml('/'));

        /** @var \DOMElement $img */
        foreach ($xp->query('//img') as $img) {
            $src = basename($img->getAttribute('src'));
            $this->assertMatchesRegularExpression('/^\d+$/', $img->getAttribute('width'), "{$src}: width bukan angka");
            $this->assertMatchesRegularExpression('/^\d+$/', $img->getAttribute('height'), "{$src}: height bukan angka");
            $this->assertGreaterThan(0, (int) $img->getAttribute('width'), "{$src}: width harus >0");
            $this->assertGreaterThan(0, (int) $img->getAttribute('height'), "{$src}: height harus >0");
            $this->assertSame('async', $img->getAttribute('decoding'), "{$src}: decoding != async");
        }
    }

    /**
     * @testdox A11Y-06 semua img selain hero slide 0 memakai loading lazy
     */
    public function test_a11y_06(): void
    {
        $xp = $this->parseHtml($this->fetchHtml('/'));

        /** @var \DOMElement $img */
        foreach ($xp->query('//img') as $img) {
            if ($img->getAttribute('data-hero-slide') === '0') {
                continue;
            }
            $this->assertSame(
                'lazy',
                $img->getAttribute('loading'),
                'img bukan hero-0 tanpa loading=lazy: '.basename($img->getAttribute('src'))
            );
        }
    }

    /**
     * @testdox A11Y-07 ikon material-symbols selalu aria-hidden
     */
    public function test_a11y_07(): void
    {
        $xp = $this->parseHtml($this->fetchHtml('/'));
        $icons = $xp->query('//*[contains(concat(" ", normalize-space(@class), " "), " material-symbols-outlined ")]');

        $this->assertGreaterThan(0, $icons->length, 'Ikon material-symbols tidak ditemukan');

        /** @var \DOMElement $icon */
        foreach ($icons as $icon) {
            $this->assertSame(
                'true',
                $icon->getAttribute('aria-hidden'),
                'Ikon tanpa aria-hidden: '.trim($icon->textContent)
            );
        }
    }

    /**
     * @testdox A11Y-08 tombol/tautan tanpa teks terlihat wajib punya aria-label
     */
    public function test_a11y_08(): void
    {
        $xp = $this->parseHtml($this->fetchHtml('/'));

        $missing = [];
        /** @var \DOMElement $el */
        foreach ($xp->query('//a | //button') as $el) {
            if ($this->visibleText($el) === '' && $el->getAttribute('aria-label') === '') {
                $missing[] = $el->nodeName.':'.($el->getAttribute('href') ?: $el->getAttribute('id') ?: '(tanpa identitas)');
            }
        }

        $this->assertSame([], $missing, 'Elemen tanpa teks & tanpa aria-label: '.implode(', ', $missing));
    }

    /**
     * @testdox A11Y-09 semua tautan target=_blank memakai rel noopener noreferrer
     */
    public function test_a11y_09(): void
    {
        $xp = $this->parseHtml($this->fetchHtml('/'));
        $links = $xp->query('//a[@target="_blank"]');

        $this->assertGreaterThan(0, $links->length, 'Tidak ada tautan target=_blank');
        /** @var \DOMElement $a */
        foreach ($links as $a) {
            $rel = $a->getAttribute('rel');
            $this->assertStringContainsString('noopener', $rel, 'rel tanpa noopener: '.$a->getAttribute('href'));
            $this->assertStringContainsString('noreferrer', $rel, 'rel tanpa noreferrer: '.$a->getAttribute('href'));
        }
    }

    /**
     * @testdox A11Y-14 nama ikon valid menurut paket material-symbols, tanpa 'sparkles'
     */
    public function test_a11y_14(): void
    {
        $xp = $this->parseHtml($this->fetchHtml('/'));
        $icons = $xp->query('//*[contains(concat(" ", normalize-space(@class), " "), " material-symbols-outlined ")]');

        $names = [];
        /** @var \DOMElement $icon */
        foreach ($icons as $icon) {
            $names[] = trim($icon->textContent);
        }

        $this->assertNotContains('sparkles', $names, "Ikon 'sparkles' tidak valid dan masih dipakai");

        $typeFile = base_path('node_modules/material-symbols/index.d.ts');
        if (! file_exists($typeFile)) {
            $this->markTestSkipped('node_modules/material-symbols tidak tersedia untuk verifikasi nama ikon');
        }

        preg_match_all('/"([a-z0-9_]+)"/', (string) file_get_contents($typeFile), $matches);
        $valid = array_unique($matches[1]);

        $invalid = array_values(array_diff(array_unique($names), $valid));
        $this->assertSame([], $invalid, 'Nama ikon tidak ada di paket material-symbols: '.implode(', ', $invalid));
    }

    /**
     * @testdox A11Y-15 semua atribut id unik (di 6 URL bahasa)
     */
    public function test_a11y_15(): void
    {
        foreach (['/', '/en', '/ms', '/zh', '/ja', '/ko'] as $uri) {
            $xp = $this->parseHtml($this->fetchHtml($uri));
            $ids = $xp->query('//@id');

            $seen = [];
            $duplicates = [];
            /** @var \DOMAttr $attr */
            foreach ($ids as $attr) {
                $value = $attr->textContent;
                if (isset($seen[$value])) {
                    $duplicates[] = $value;
                }
                $seen[$value] = true;
            }

            $this->assertSame([], array_unique($duplicates), "id duplikat di {$uri}: ".implode(', ', array_unique($duplicates)));
        }
    }

    /**
     * @testdox A11Y-16 tidak ada <a> di dalam <button> maupun sebaliknya
     */
    public function test_a11y_16(): void
    {
        $xp = $this->parseHtml($this->fetchHtml('/'));

        $this->assertSame(0, $xp->query('//button//a')->length, 'Ada <a> di dalam <button>');
        $this->assertSame(0, $xp->query('//a//button')->length, 'Ada <button> di dalam <a>');
    }
}
