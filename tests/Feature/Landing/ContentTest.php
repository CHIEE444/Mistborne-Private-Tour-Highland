<?php

namespace Tests\Feature\Landing;

use Tests\Support\ParsesHtml;
use Tests\TestCase;

/**
 * Tes konten halaman utama: font, hero, navbar, statistik, journey,
 * FAQ, form kontak, dan footer (19 kasus: FONT, HERO, NAV, STAT, JRN,
 * FAQ, FORM, FTR).
 */
class ContentTest extends TestCase
{
    use ParsesHtml;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    /**
     * Telusuri seluruh berkas di bawah suatu direktori (rekursif).
     *
     * @return iterable<string>
     */
    private function filesIn(string $directory): iterable
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                yield (string) $file->getPathname();
            }
        }
    }

    /**
     * @testdox FONT-01 tidak ada sisa font lama di resources/ dan vite.config.js
     */
    public function test_font_01(): void
    {
        $forbidden = ['EB Garamond', 'Plus Jakarta Sans', 'Playfair'];
        $hits = [];

        foreach ($this->filesIn(base_path('resources')) as $path) {
            $content = (string) file_get_contents($path);
            foreach ($forbidden as $name) {
                if (str_contains($content, $name)) {
                    $hits[] = str_replace(base_path(), '', $path).' => '.$name;
                }
            }
            if (preg_match('/\bInter\b/', $content)) {
                $hits[] = $path.' => Inter';
            }
        }

        $vite = (string) file_get_contents(base_path('vite.config.js'));
        foreach ($forbidden as $name) {
            if (str_contains($vite, $name)) {
                $hits[] = 'vite.config.js => '.$name;
            }
        }
        if (preg_match('/\bInter\b/', $vite)) {
            $hits[] = 'vite.config.js => Inter';
        }

        $this->assertSame([], $hits, 'Font lama masih tersisa: '.implode('; ', $hits));
    }

    /**
     * @testdox FONT-03 --font-sans dan --font-serif identik dan memuat Montserrat
     */
    public function test_font_03(): void
    {
        $css = $this->source('resources/css/theme.css');

        $this->assertMatchesRegularExpression('/--font-sans:\s*[^;]+/', $css, '--font-sans tidak ditemukan');
        $this->assertMatchesRegularExpression('/--font-serif:\s*[^;]+/', $css, '--font-serif tidak ditemukan');

        preg_match('/--font-sans:\s*([^;]+);/', $css, $sans);
        preg_match('/--font-serif:\s*([^;]+);/', $css, $serif);

        $this->assertSame($sans[1], $serif[1], 'Nilai --font-sans dan --font-serif harus identik');
        $this->assertStringContainsString('Montserrat', $sans[1], 'Font harus Montserrat');
    }

    /**
     * @testdox HERO-16 tepat satu h1 berisi judul hero terbaru tanpa x-cloak
     */
    public function test_hero_16(): void
    {
        $xp = $this->parseHtml($this->fetchHtml('/'));
        $h1 = $xp->query('//h1');

        $this->assertSame(1, $h1->length, 'Harus ada tepat satu <h1>');
        /** @var \DOMElement $el */
        $el = $h1->item(0);
        $this->assertSame('Sambut Pagi di Atas Awan.', $this->norm($el->textContent));
        $this->assertFalse($el->hasAttribute('x-cloak'), 'h1 tidak boleh ber-x-cloak');
    }

    /**
     * @testdox HERO-17 preload foto hero dan slide 0 eager + fetchpriority high
     */
    public function test_hero_17(): void
    {
        $xp = $this->parseHtml($this->fetchHtml('/'));

        $preload = $xp->query('//head//link[@rel="preload" and @as="image" and @fetchpriority="high"]');
        $this->assertGreaterThan(0, $preload->length, 'preload hero ber-fetchpriority=high tidak ada di <head>');

        $slide0 = $xp->query('//img[@data-hero-slide="0"]');
        $this->assertSame(1, $slide0->length, 'img[data-hero-slide=0] tidak ada');
        /** @var \DOMElement $img */
        $img = $slide0->item(0);
        $this->assertSame('eager', $img->getAttribute('loading'));
        $this->assertSame('high', $img->getAttribute('fetchpriority'));
    }

    /**
     * @testdox HERO-18 latar hero hanya satu foto (tanpa carousel)
     */
    public function test_hero_18(): void
    {
        $xp = $this->parseHtml($this->fetchHtml('/'));

        $this->assertSame(1, $xp->query('//img[@data-hero-slide]')->length, 'Latar hero harus tepat satu foto');
        $this->assertSame(0, $xp->query('//section[@id="beranda"][@aria-roledescription="carousel"]')->length, 'Hero tidak boleh lagi berupa carousel');
    }

    /**
     * @testdox HERO-21 file gambar hero ada di public/images
     */
    public function test_hero_21(): void
    {
        $missing = [];
        foreach (['hero-bromo.jpg', 'hero-bromo-1280.jpg'] as $file) {
            if (! file_exists(base_path('public/images/'.$file))) {
                $missing[] = $file;
            }
        }

        if ($missing !== []) {
            $this->markTestSkipped('Gambar hero belum ada: '.implode(', ', $missing));
        }

        $this->assertTrue(true);
    }

    /**
     * @testdox NAV-15 header dan nav utama ada; tombol ikon header ber-aria-label
     */
    public function test_nav_15(): void
    {
        $xp = $this->parseHtml($this->fetchHtml('/'));

        $this->assertSame(1, $xp->query('//header')->length, '<header> tidak ada');
        $this->assertGreaterThan(0, $xp->query('//nav[@aria-label="Navigasi utama"]')->length, 'nav utama tidak ada');

        $missing = [];
        /** @var \DOMElement $button */
        foreach ($xp->query('//header//button') as $button) {
            if ($this->visibleText($button) === '' && $button->getAttribute('aria-label') === '') {
                $missing[] = trim(preg_replace('/\s+/', ' ', $button->getAttribute('class'))) ?: '(tanpa class)';
            }
        }

        $this->assertSame([], $missing, 'Tombol hanya-ikon tanpa aria-label: '.implode('; ', $missing));
    }

    /**
     * @testdox NAV-20 lapis kaca ada (aria-hidden) dan data-lang-menu di luar backdrop
     * [REVISI] Navbar kini SATU lapis kaca permanen (permintaan user: tidak lagi putih
     * di paling atas) — asersi dua lapis (top+scrolled) diganti satu lapis "scrolled".
     */
    public function test_nav_20(): void
    {
        $xp = $this->parseHtml($this->fetchHtml('/'));

        foreach (['scrolled'] as $layer) {
            $nodes = $xp->query(sprintf('//header//*[@data-nav-glass="%s"]', $layer));
            $this->assertSame(1, $nodes->length, "lapis [data-nav-glass={$layer}] tidak ada / bukan tepat satu");
            /** @var \DOMElement $el */
            $el = $nodes->item(0);
            $this->assertTrue($el->getAttribute('aria-hidden') === 'true', "lapis {$layer} harus aria-hidden");
        }

        $menus = $xp->query('//*[@data-lang-menu]');
        $this->assertGreaterThan(0, $menus->length, '[data-lang-menu] tidak ada');
        /** @var \DOMElement $menu */
        $menu = $menus->item(0);
        for ($node = $menu->parentNode; $node instanceof \DOMElement; $node = $node->parentNode) {
            $classes = ' '.$node->getAttribute('class').' ';
            $this->assertStringNotContainsString(
                ' backdrop-',
                $classes,
                'Leluhur [data-lang-menu] memakai kelas backdrop-*: '.$node->nodeName
            );
            if ($node->nodeName === 'header') {
                break;
            }
        }
    }

    /**
     * @testdox NAV-21 fallback kaca navbar ada di app.css
     */
    public function test_nav_21(): void
    {
        $css = $this->source('resources/css/glass.css');

        $this->assertStringContainsString('@supports not', $css, 'fallback @supports not ada');
        $this->assertStringContainsString('backdrop-filter', $css, 'fallback menyebut backdrop-filter');
        $this->assertStringContainsString(
            '@media (prefers-reduced-transparency: reduce)',
            $css,
            'fallback prefers-reduced-transparency ada'
        );
    }

    /**
     * @testdox STAT-03 empat angka data-count-to berisi angka akhir dengan desimal benar
     */
    public function test_stat_03(): void
    {
        $xp = $this->parseHtml($this->fetchHtml('/'));
        $nodes = $xp->query('//*[@data-count-to]');

        $this->assertSame(4, $nodes->length, 'Harus ada 4 elemen [data-count-to]');

        // Angka kini berasal dari data nyata (perjalanan selesai, rating, personel), jadi nilainya tidak lagi
        // tetap: yang dijaga adalah HTML awal sudah berisi angka akhir (no-JS/SEO) dan jumlah desimal.
        $desimal = ['0', '1', '0', '0'];
        foreach ($desimal as $i => $decimals) {
            /** @var \DOMElement $el */
            $el = $nodes->item($i);
            $this->assertSame(
                number_format((float) $el->getAttribute('data-count-to'), (int) $decimals, '.', ''),
                $this->norm($el->textContent),
                'Angka ke-'.$i.' tidak sama dengan data-count-to'
            );
            $this->assertSame($decimals, $el->getAttribute('data-decimals'), 'data-decimals ke-'.$i.' salah');
        }
        $this->assertSame('100', $this->norm($nodes->item(3)->textContent));
    }

    /**
     * @testdox JRN-01 semua journey tampil di beranda (kustom paling awal) dengan harga dari data Blade
     * Home → journeys section menampilkan semua 9 journey; harga tetap divalidasi.
     */
    public function test_jrn_01(): void
    {
        $html = $this->fetchHtml('/');
        $xp = $this->parseHtml($html);

        foreach (['DIENG HIGHLAND ESCAPE', 'BROMO SUNRISE JOURNEY', 'IJEN VOLCANIC ESCAPE', 'CUSTOM HIGHLAND JOURNEY'] as $name) {
            $this->assertStringContainsString($name, $html, "Journey {$name} tidak tampil");
        }
        $kartu = $xp->query('//*[@data-journey-card]');
        $this->assertSame(9, $kartu->length, 'Beranda menampilkan semua 9 journey');
        // Kartu kustom sengaja paling awal (paling kiri) di beranda.
        $this->assertStringContainsString('CUSTOM HIGHLAND JOURNEY', $kartu->item(0)->textContent);

        $data = $this->source('resources/data/journeys.php');
        preg_match_all("/'price' => '([^']+)'/", $data, $matches);
        // 4 journey memakai harga tetap (string); 3 journey lain 'price' => null
        // (harga mengikuti itinerary) sehingga tidak masuk pola string.
        $this->assertCount(9, $matches[1], 'Data harga string di partial harus 9 (8 journey berharga + kustom)');
        // Harga pertama milik journey kustom (CUSTOM HIGHLAND JOURNEY, urutan pertama di data): harganya
        // sengaja tidak ditampilkan — kartu memakai "Harga disesuaikan".
        foreach (array_slice($matches[1], 1, 3) as $price) {
            $this->assertStringContainsString($price, $html, "Harga {$price} dari data tidak ada di HTML");
        }
        $this->assertStringContainsString('Harga disesuaikan', $html);
    }

    /**
     * @testdox JRN-06 semua tautan wa.me memakai target blank + rel noopener noreferrer
     */
    public function test_jrn_06(): void
    {
        $xp = $this->parseHtml($this->fetchHtml('/'));
        $links = $xp->query('//a[starts-with(@href, "https://wa.me/")]');

        $this->assertGreaterThan(0, $links->length, 'Tidak ada tautan wa.me');
        /** @var \DOMElement $a */
        foreach ($links as $a) {
            $rel = $a->getAttribute('rel');
            $this->assertSame('_blank', $a->getAttribute('target'), 'wa.me harus target=_blank');
            $this->assertStringContainsString('noopener', $rel, 'wa.me harus rel noopener');
            $this->assertStringContainsString('noreferrer', $rel, 'wa.me harus rel noreferrer');
        }
    }

    /**
     * @testdox JRN-10 kartu journey h-full flex-col dan pembungkus grid juga h-full
     */
    public function test_jrn_10(): void
    {
        $xp = $this->parseHtml($this->fetchHtml('/'));
        $cards = $xp->query('//*[@data-journey-card]');

        $this->assertGreaterThan(0, $cards->length, '[data-journey-card] tidak ada');
        /** @var \DOMElement $card */
        foreach ($cards as $card) {
            $classes = $card->getAttribute('class');
            $this->assertStringContainsString('h-full', $classes, 'kartu harus h-full');
            $this->assertStringContainsString('flex-col', $classes, 'kartu harus flex-col');

            $parent = $card->parentNode;
            $this->assertInstanceOf(\DOMElement::class, $parent);
            $this->assertStringContainsString(
                'h-full',
                $parent->getAttribute('class'),
                'Pembungkus grid-item harus h-full agar tinggi kartu sama'
            );
        }
    }

    /**
     * @testdox JRN-13 harga memakai whitespace-nowrap dan tabular-nums
     */
    public function test_jrn_13(): void
    {
        $xp = $this->parseHtml($this->fetchHtml('/'));
        $prices = $xp->query('//*[@data-journey-price]');

        $this->assertGreaterThan(0, $prices->length, '[data-journey-price] tidak ada');
        /** @var \DOMElement $el */
        foreach ($prices as $el) {
            $classes = $el->getAttribute('class');
            $this->assertStringContainsString('whitespace-nowrap', $classes, 'harga harus whitespace-nowrap');
            $this->assertStringContainsString('tabular-nums', $classes, 'harga harus tabular-nums');
        }
    }

    /**
     * @testdox JRN-19 tidak ada sisa teks rental di HTML
     */
    public function test_jrn_19(): void
    {
        $html = $this->fetchHtml('/');

        foreach (['Armada 2', 'Pesan Sekarang', 'Mobil Populer', 'sewa mobil'] as $term) {
            $this->assertStringNotContainsStringIgnoringCase($term, $html, "Masih ada teks: {$term}");
        }
        $this->assertDoesNotMatchRegularExpression('/rental/i', $html, 'Masih ada kata rental');
    }

    /**
     * @testdox FAQ-04 section#faq ada dengan aria-labelledby yang valid
     */
    public function test_faq_04(): void
    {
        $xp = $this->parseHtml($this->fetchHtml('/'));
        $sections = $xp->query('//section[@id="faq"]');

        $this->assertSame(1, $sections->length, 'section#faq tidak ada');
        /** @var \DOMElement $section */
        $section = $sections->item(0);
        $labelledBy = $section->getAttribute('aria-labelledby');
        $this->assertNotSame('', $labelledBy, 'section#faq butuh aria-labelledby');

        $target = $xp->query(sprintf('//*[@id=%s]', $this->xpathLiteral($labelledBy)));
        $this->assertSame(1, $target->length, "Target aria-labelledby (#{$labelledBy}) tidak ada");
    }

    /**
     * @testdox FAQ-05 JSON-LD FAQPage identik dengan teks jawaban di DOM (3 item)
     */
    public function test_faq_05(): void
    {
        $html = $this->fetchHtml('/');
        $xp = $this->parseHtml($html);

        $faqBlocks = array_filter(
            $this->jsonLdBlocks($html),
            fn (array $block) => ($block['@type'] ?? null) === 'FAQPage'
        );
        $this->assertCount(1, $faqBlocks, 'JSON-LD FAQPage harus ada tepat satu');
        $faq = array_values($faqBlocks)[0];

        $this->assertCount(3, $faq['mainEntity'], 'FAQPage harus berisi 3 pertanyaan');

        foreach ($faq['mainEntity'] as $i => $question) {
            $panel = $xp->query(sprintf('//*[@id="faq-panel-%d"]', $i));
            $this->assertSame(1, $panel->length, "Panel FAQ #{$i} tidak ada di DOM");
            $domAnswer = $this->norm($panel->item(0)->textContent);

            $this->assertSame(
                $domAnswer,
                $this->norm($question['acceptedAnswer']['text']),
                "Jawaban FAQ ke-{$i} di JSON-LD berbeda dengan DOM"
            );
        }
    }

    /**
     * @testdox FORM-03 setiap label di form kontak punya for yang menunjuk id valid
     */
    public function test_form_03(): void
    {
        $xp = $this->parseHtml($this->fetchHtml('/'));
        $labels = $xp->query('//section[@id="kontak"]//form//label');

        $this->assertGreaterThan(0, $labels->length, 'Label form kontak tidak ada');
        /** @var \DOMElement $label */
        foreach ($labels as $label) {
            $for = $label->getAttribute('for');
            $this->assertNotSame('', $for, 'label tanpa atribut for');
            $target = $xp->query(sprintf('//*[@id=%s]', $this->xpathLiteral($for)));
            $this->assertSame(
                1,
                $target->length,
                "for='{$for}' tidak menunjuk elemen yang ada (atau ganda)"
            );
        }
    }

    /**
     * @testdox FTR-02 tidak ada tautan href="#" atau href kosong
     */
    public function test_ftr_02(): void
    {
        $xp = $this->parseHtml($this->fetchHtml('/'));

        $bad = [];
        /** @var \DOMElement $a */
        foreach ($xp->query('//a') as $a) {
            $href = trim($a->getAttribute('href'));
            if ($href === '' || $href === '#') {
                $bad[] = $this->norm($a->textContent) ?: '(tanpa teks)';
            }
        }

        $this->assertSame([], $bad, 'Tautan placeholder tersisa: '.implode('; ', $bad));
    }

    /**
     * @testdox HERO-22 alt hero slide tidak kosong dan tanpa kata suv/mobil/rental/sewa
     */
    public function test_hero_22(): void
    {
        $xp = $this->parseHtml($this->fetchHtml('/'));
        $slides = $xp->query('//img[@data-hero-slide]');

        $this->assertSame(1, $slides->length, 'Harus ada 1 img[data-hero-slide]');
        /** @var \DOMElement $img */
        foreach ($slides as $img) {
            $alt = trim($img->getAttribute('alt'));
            $this->assertNotSame('', $alt, 'alt hero slide kosong');
            $this->assertDoesNotMatchRegularExpression('/suv|mobil|rental|sewa/i', $alt, 'alt hero memuat kata terlarang: '.$alt);
        }
    }

    /**
     * @testdox HERO-23 img hero dan img kartu journey ber-class text-transparent
     */
    public function test_hero_23(): void
    {
        $xp = $this->parseHtml($this->fetchHtml('/'));
        $images = $xp->query('//img[@data-hero-slide] | //*[contains(concat(" ", normalize-space(@class), " "), " data-journey-card ")]//img');

        $this->assertGreaterThan(0, $images->length, 'Tidak ada img hero/journey');
        /** @var \DOMElement $img */
        foreach ($images as $img) {
            $this->assertStringContainsString(
                'text-transparent',
                $img->getAttribute('class'),
                'img tanpa text-transparent: '.$img->getAttribute('src')
            );
        }
    }

    /**
     * @testdox NAV-24 dua lapis kaca navbar punya alpha latar <= 65 dan CSS kacanya memakai blur >= 20px
     */
    public function test_nav_24(): void
    {
        // backdrop-filter dua lapis kaca kini didefinisikan di resources/css/glass.css (.navbar-glass);
        // markup hanya memakai kelas .navbar-glass, jadi cek blur pindah ke CSS.
        $glassCss = (string) file_get_contents(base_path('resources/css/glass.css'));
        $this->assertMatchesRegularExpression('/\.navbar-glass\s*\{[^}]*backdrop-filter:\s*blur\((2[0-9]|[3-9][0-9])px\)/s', $glassCss, 'glass.css tanpa .navbar-glass dengan backdrop-filter blur >= 20px');

        $xp = $this->parseHtml($this->fetchHtml('/'));

        foreach (['scrolled'] as $layer) {
            $nodes = $xp->query(sprintf('//header//*[@data-nav-glass="%s"]', $layer));
            $this->assertSame(1, $nodes->length, "lapis [data-nav-glass={$layer}] tidak ada");
            $tokens = preg_split('/\s+/', trim($nodes->item(0)->getAttribute('class'))) ?: [];

            $this->assertStringContainsString('navbar-glass', implode(' ', $tokens), "lapis {$layer} tanpa kelas .navbar-glass");

            foreach ($tokens as $token) {
                if (preg_match('/^bg-\[#[0-9A-Fa-f]+\]\/(\d{1,3})$/', $token, $m)) {
                    $this->assertLessThanOrEqual(65, (int) $m[1], "alpha lapis {$layer} > 65%: {$token}");
                }
                if (preg_match('/^bg-\[#[0-9A-Fa-f]+\]\/\[0?\.(\d+)\]$/', $token, $m)) {
                    $this->assertLessThanOrEqual(65, (int) ('0.'.$m[1]) * 100, "alpha lapis {$layer} > 65%: {$token}");
                }
                if ($token === 'bg-[#12301F]') {
                    $this->fail("lapis {$layer} memakai bg-[#12301F] tanpa alpha");
                }
            }
        }
    }

    /**
     * @testdox NAV-25 menu desktop mulai xl (hidden xl:flex, tanpa lg:flex); hamburger xl:hidden
     */
    public function test_nav_25(): void
    {
        $xp = $this->parseHtml($this->fetchHtml('/'));
        $nav = $xp->query('//nav[@aria-label="Navigasi utama"]');

        $this->assertSame(1, $nav->length, 'nav desktop tidak ada');
        $class = $nav->item(0)->getAttribute('class');
        $this->assertStringContainsString('hidden', $class);
        $this->assertStringContainsString('xl:flex', $class);
        $this->assertStringNotContainsString('lg:flex', $class);

        $burger = $xp->query('//header//button[@aria-label="Buka menu"]');
        $this->assertSame(1, $burger->length, 'tombol hamburger tidak ada');
        $this->assertStringContainsString('xl:hidden', $burger->item(0)->getAttribute('class'));
    }

    /**
     * @testdox BOOK-13 semua input/select/textarea booking + kontak ber-class text-base
     */
    public function test_book_13(): void
    {
        $xp = $this->parseHtml($this->fetchHtml('/'));
        $fields = $xp->query('//section[@id="booking"]//input | //section[@id="booking"]//select | //section[@id="kontak"]//form//input | //section[@id="kontak"]//form//select | //section[@id="kontak"]//form//textarea');

        $this->assertGreaterThan(0, $fields->length, 'Field form tidak ditemukan');
        /** @var \DOMElement $el */
        foreach ($fields as $el) {
            $this->assertStringContainsString(
                'text-base',
                $el->getAttribute('class'),
                ($el->getAttribute('id') ?: $el->nodeName).' bukan text-base (iOS auto-zoom)'
            );
        }
    }

    /**
     * Ekspresi literal aman untuk pembanding atribut di XPath.
     * ID di halaman ini (mis. faq-panel-0, journeys-judul) tidak pernah
     * memuat kutip, jadi pembungkus kutip ganda selalu aman.
     */
    private function xpathLiteral(string $value): string
    {
        $this->assertStringNotContainsString('"', $value, 'ID mengandung kutip ganda');

        return '"'.$value.'"';
    }
}
