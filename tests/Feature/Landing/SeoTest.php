<?php

namespace Tests\Feature\Landing;

use Tests\Support\ParsesHtml;
use Tests\TestCase;

/**
 * Tes SEO (SEO-01..17): meta, Open Graph, favicon/manifest, JSON-LD, robots, sitemap, dan hierarki heading.
 */
class SeoTest extends TestCase
{
    use ParsesHtml;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    /**
     * @testdox SEO-01 title default ≤60, memuat MISTBORNE, tanpa rental/sewa
     */
    public function test_seo_01(): void
    {
        $xp = $this->parseHtml($this->fetchHtml('/'));
        $titles = $xp->query('//head/title');

        $this->assertSame(1, $titles->length, '<title> harus tepat satu');
        $title = $this->norm($titles->item(0)->textContent);

        // Default dideklarasikan di komponen layout, bukan di JavaScript.
        $layout = $this->source('resources/views/komponen/tata-letak/aplikasi.blade.php');
        $this->assertMatchesRegularExpression(
            '/\$seoTitle = __\((["\'])(.+?)\1\)/u',
            $layout,
            'Default title harus dideklarasikan lewat __() di layout'
        );
        preg_match('/\$seoTitle = __\((["\'])(.+?)\1\)/u', $layout, $m);
        $expected = stripcslashes($m[2]);

        $this->assertSame($expected, $title, 'Title berbeda dari default layout');
        $this->assertLessThanOrEqual(60, mb_strlen($title), 'Title >60 karakter');
        $this->assertStringContainsString('MISTBORNE', $title);
        $this->assertDoesNotMatchRegularExpression('/rental|sewa/i', $title, 'Title memuat kata rental/sewa');
    }

    /**
     * @testdox SEO-02 description default panjang 150-160 tanpa kata rental
     */
    public function test_seo_02(): void
    {
        $xp = $this->parseHtml($this->fetchHtml('/'));
        $nodes = $xp->query('//head//meta[@name="description"]');

        $this->assertSame(1, $nodes->length, 'meta description harus tepat satu');
        $description = $this->norm($nodes->item(0)->getAttribute('content'));

        $this->assertGreaterThanOrEqual(150, mb_strlen($description), 'Description <150 karakter');
        $this->assertLessThanOrEqual(160, mb_strlen($description), 'Description >160 karakter');
        $this->assertDoesNotMatchRegularExpression('/rental/i', $description, 'Description memuat kata rental');
    }

    /**
     * @testdox SEO-03 canonical menunjuk URL request
     */
    public function test_seo_03(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $html = (string) $response->getContent();
        $xp = $this->parseHtml($html);

        $nodes = $xp->query('//head//link[@rel="canonical"]');
        $this->assertSame(1, $nodes->length, 'link canonical harus tepat satu');
        $this->assertSame(
            request()->fullUrl(),
            $nodes->item(0)->getAttribute('href'),
            'canonical berbeda dari URL request'
        );
    }

    /**
     * @testdox SEO-04 meta robots persis index, follow, max-image-preview:large
     */
    public function test_seo_04(): void
    {
        $xp = $this->parseHtml($this->fetchHtml('/'));
        $nodes = $xp->query('//head//meta[@name="robots"]');

        $this->assertSame(1, $nodes->length, 'meta robots harus tepat satu');
        $this->assertSame(
            'index, follow, max-image-preview:large',
            $nodes->item(0)->getAttribute('content')
        );
    }

    /**
     * @testdox SEO-05 meta theme-color #000000 (sama dengan footer, tanpa latar hijau saat overscroll)
     */
    public function test_seo_05(): void
    {
        $xp = $this->parseHtml($this->fetchHtml('/'));
        $nodes = $xp->query('//head//meta[@name="theme-color"]');

        $this->assertSame(1, $nodes->length, 'meta theme-color harus tepat satu');
        $this->assertSame('#000000', $nodes->item(0)->getAttribute('content'));
    }

    /**
     * @testdox SEO-06 html lang=id untuk bahasa Indonesia
     */
    public function test_seo_06(): void
    {
        $xp = $this->parseHtml($this->fetchHtml('/'));
        $htmls = $xp->query('//html');

        $this->assertSame(1, $htmls->length);
        $this->assertSame('id', $htmls->item(0)->getAttribute('lang'));
    }

    /**
     * @testdox SEO-07 seluruh properti og:* ada, dimensi gambar 1200x630, locale id_ID
     */
    public function test_seo_07(): void
    {
        $xp = $this->parseHtml($this->fetchHtml('/'));

        foreach (['og:type', 'og:site_name', 'og:title', 'og:description', 'og:url', 'og:image', 'og:image:width', 'og:image:height', 'og:locale'] as $property) {
            $nodes = $xp->query(sprintf('//head//meta[@property="%s"]', $property));
            $this->assertSame(1, $nodes->length, $property.' harus muncul tepat satu kali');
            $this->assertNotSame(
                '',
                trim($nodes->item(0)->getAttribute('content')),
                "{$property} kosong"
            );
        }

        $value = fn (string $property) => $xp
            ->query(sprintf('//head//meta[@property="%s"]', $property))
            ->item(0)
            ->getAttribute('content');

        $this->assertSame('1200', $value('og:image:width'));
        $this->assertSame('630', $value('og:image:height'));
        $this->assertSame('id_ID', $value('og:locale'));
    }

    /**
     * @testdox SEO-08 meta twitter card/title/description/image; card=summary_large_image
     */
    public function test_seo_08(): void
    {
        $xp = $this->parseHtml($this->fetchHtml('/'));

        foreach (['twitter:card', 'twitter:title', 'twitter:description', 'twitter:image'] as $name) {
            $nodes = $xp->query(sprintf('//head//meta[@name="%s"]', $name));
            $this->assertSame(1, $nodes->length, "meta {$name} harus ada tepat satu kali");
            $this->assertNotSame('', trim($nodes->item(0)->getAttribute('content')), "{$name} kosong");
        }

        $card = $xp->query('//head//meta[@name="twitter:card"]')->item(0);
        $this->assertSame('summary_large_image', $card->getAttribute('content'));
    }

    /**
     * @testdox SEO-09 link icon, apple-touch-icon, dan manifest ada
     */
    public function test_seo_09(): void
    {
        $xp = $this->parseHtml($this->fetchHtml('/'));

        // Ikon boleh lebih dari satu format (ICO + SVG); apple-touch-icon dan manifest tetap tunggal.
        foreach (['icon' => null, 'apple-touch-icon' => 1, 'manifest' => 1] as $rel => $jumlahPasti) {
            $nodes = $xp->query(sprintf('//head//link[@rel="%s"]', $rel));
            $this->assertGreaterThan(0, $nodes->length, "link rel={$rel} tidak ada");
            if ($jumlahPasti !== null) {
                $this->assertSame($jumlahPasti, $nodes->length, "link rel={$rel} ganda");
            }
            foreach ($nodes as $node) {
                $this->assertNotSame('', $node->getAttribute('href'));
            }
        }
    }

    /**
     * @testdox SEO-10 tidak ada meta keywords
     */
    public function test_seo_10(): void
    {
        $xp = $this->parseHtml($this->fetchHtml('/'));

        $this->assertSame(
            0,
            $xp->query('//head//meta[@name="keywords"]')->length,
            'meta keywords tidak boleh ada'
        );
    }

    /**
     * @testdox SEO-11 JSON-LD berisi WebSite dan TravelAgency lengkap (bukan AutoRental)
     */
    public function test_seo_11(): void
    {
        $html = $this->fetchHtml('/');
        $blocks = $this->jsonLdBlocks($html);
        $this->assertNotEmpty($blocks, 'Tidak ada JSON-LD');

        $graph = [];
        foreach ($blocks as $block) {
            if (isset($block['@graph'])) {
                $graph = array_merge($graph, $block['@graph']);
            }
        }
        $this->assertNotEmpty($graph, '@graph tidak ada');

        $types = array_column($graph, '@type');
        $this->assertContains('WebSite', $types, 'WebSite tidak ada di @graph');
        $this->assertContains('TravelAgency', $types, 'TravelAgency (bukan AutoRental) harus ada');
        $this->assertNotContains('AutoRental', $types, 'AutoRental tidak boleh dipakai');

        $agency = null;
        foreach ($graph as $entity) {
            if (($entity['@type'] ?? null) === 'TravelAgency') {
                $agency = $entity;
            }
        }
        $this->assertIsArray($agency);

        foreach (['name', 'url', 'telephone', 'email', 'priceRange', 'address', 'geo', 'openingHoursSpecification', 'areaServed'] as $field) {
            $this->assertArrayHasKey($field, $agency, "TravelAgency tanpa field {$field}");
            $this->assertNotEmpty($agency[$field], "TravelAgency.{$field} kosong");
        }
        $this->assertSame('PostalAddress', $agency['address']['@type']);
        $this->assertSame('GeoCoordinates', $agency['geo']['@type']);
    }

    /**
     * @testdox SEO-12 JSON-LD tanpa aggregateRating, Review, dan sameAs '#'
     */
    public function test_seo_12(): void
    {
        $raw = '';
        foreach ($this->jsonLdBlocks($this->fetchHtml('/')) as $block) {
            $raw .= json_encode($block, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $this->assertNotSame('', $raw, 'Tidak ada JSON-LD untuk diperiksa');
        $this->assertStringNotContainsString('aggregateRating', $raw);
        $this->assertStringNotContainsString('Review', $raw);
        $this->assertStringNotContainsString('"sameAs":"#"', $raw);
    }

    /**
     * @testdox SEO-13 robots.txt punya User-agent, Allow, Sitemap, dan TODO
     */
    public function test_seo_13(): void
    {
        $robots = $this->source('public/robots.txt');

        $this->assertStringContainsString('User-agent: *', $robots);
        $this->assertStringContainsString('Allow: /', $robots);
        $this->assertStringContainsString('Sitemap:', $robots);
        $this->assertStringContainsString('TODO', $robots, 'robots.txt wajib menyimpan TODO domain');
    }

    /**
     * @testdox SEO-14 sitemap valid dengan 9 URL dan lastmod 2026-10-04
     */
    public function test_seo_14(): void
    {
        if (! extension_loaded('SimpleXML')) {
            $this->markTestSkipped('Ekstensi SimpleXML tidak tersedia');
        }

        $path = base_path('public/sitemap.xml');
        $this->assertFileExists($path);

        $xml = simplexml_load_file($path);
        $this->assertNotFalse($xml, 'sitemap.xml tidak lolos simplexml_load_file');

        $urls = $xml->url;
        $this->assertCount(9, $urls, 'sitemap.xml harus berisi 9 <url> (satu per bahasa)');

        $index = 0;
        foreach ($urls as $url) {
            $index++;
            $this->assertNotEmpty((string) $url->loc, "loc ke-{$index} kosong");
            $this->assertSame('2026-10-04', (string) $url->lastmod, 'lastmod salah');
            $this->assertSame('monthly', (string) $url->changefreq);
            $this->assertSame('1.0', (string) $url->priority);
        }

        $this->assertStringContainsString(
            'TODO',
            (string) file_get_contents($path),
            'sitemap.xml wajib menyimpan TODO domain'
        );
    }

    /**
     * @testdox SEO-15 site.webmanifest JSON valid dengan field wajib
     */
    public function test_seo_15(): void
    {
        $raw = $this->source('public/site.webmanifest');
        $manifest = json_decode($raw, true);

        $this->assertIsArray($manifest, 'site.webmanifest bukan JSON valid: '.json_last_error_msg());
        foreach (['name', 'short_name', 'theme_color', 'background_color', 'display', 'start_url'] as $field) {
            $this->assertArrayHasKey($field, $manifest, "manifest tanpa {$field}");
            $this->assertNotEmpty($manifest[$field], "manifest.{$field} kosong");
        }
    }

    /**
     * @testdox SEO-16 jumlah h1 tepat satu
     */
    public function test_seo_16(): void
    {
        $xp = $this->parseHtml($this->fetchHtml('/'));

        $this->assertSame(1, $xp->query('//h1')->length, 'Jumlah <h1> harus tepat 1');
    }

    /**
     * @testdox SEO-17 hierarki heading tidak melompat lebih dari satu tingkat
     */
    public function test_seo_17(): void
    {
        $xp = $this->parseHtml($this->fetchHtml('/'));
        $headings = $xp->query('//h1|//h2|//h3|//h4|//h5|//h6');

        $this->assertGreaterThan(0, $headings->length, 'Tidak ada heading sama sekali');

        $previous = 0;
        foreach ($headings as $i => $heading) {
            $level = (int) substr($heading->nodeName, 1);

            if ($i === 0) {
                $this->assertSame(1, $level, 'Heading pertama harus h1');
            }
            if ($level > $previous && $level - $previous > 1) {
                $this->fail(sprintf(
                    'Heading melompat dari h%d ke h%d pada "%s"',
                    $previous,
                    $level,
                    $this->norm($heading->textContent)
                ));
            }
            $previous = $level;
        }

        $this->assertTrue(true);
    }

    /**
     * Ratakan seluruh node di @graph (termasuk node bersarang) menjadi satu daftar.
     *
     * @param  array<string, mixed>  $node
     * @param  array<int, array<string, mixed>>  $out
     */
    private function flattenGraph(array $node, array &$out): void
    {
        if (isset($node['@type']) || isset($node['@id'])) {
            $out[] = $node;
        }
        foreach ($node as $value) {
            if (is_array($value)) {
                $this->flattenGraph($value, $out);
            }
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function graphOf(string $uri): array
    {
        $flat = [];
        foreach ($this->jsonLdBlocks($this->fetchHtml($uri)) as $block) {
            foreach ((array) ($block['@graph'] ?? []) as $node) {
                if (is_array($node)) {
                    $this->flattenGraph($node, $flat);
                }
            }
        }

        return $flat;
    }

    /**
     * @testdox SEO-23 @id unik dan setiap referensi {@id} menunjuk node terdefinisi
     */
    public function test_seo_23(): void
    {
        $flat = $this->graphOf('/');

        $ids = [];
        $refs = [];
        foreach ($flat as $node) {
            if (! isset($node['@id']) || ! is_string($node['@id'])) {
                continue;
            }
            if (isset($node['@type'])) {
                // list biasa (bukan map ber-key @id): key array PHP otomatis unik,
                // jadi pengecekan duplikat baru benar-benar terjadi di array_unique di bawah
                $ids[] = $node['@id'];
            } elseif (count($node) === 1) {
                $refs[] = $node['@id'];
            }
        }

        $this->assertGreaterThan(0, count($ids), 'Tidak ada @id terdefinisi di @graph');
        $this->assertCount(count($ids), array_unique($ids), 'Ada @id duplikat di @graph');
        foreach (array_unique($refs) as $ref) {
            $this->assertContains($ref, $ids, "Referensi @id tidak terdefinisi: {$ref}");
        }
    }

    /**
     * @testdox SEO-24 9 TouristTrip, name sama persis dengan h3 kartu journey (urut)
     */
    public function test_seo_24(): void
    {
        $html = $this->fetchHtml('/');
        $trips = array_values(array_filter(
            $this->graphOf('/'),
            fn (array $node) => ($node['@type'] ?? null) === 'TouristTrip'
        ));

        $this->assertCount(9, $trips, 'JSON-LD harus memuat 9 TouristTrip (semua journey)');

        // Beranda menampilkan SEMUA journey; urutan TouristTrip wajib sama dengan urutan kartu.
        $cards = $this->parseHtml($html)->query('//*[@data-journey-card]');
        $this->assertSame(9, $cards->length, 'Beranda menampilkan semua 9 kartu journey');
        foreach ($trips as $i => $trip) {
            $h3 = $cards->item($i)->getElementsByTagName('h3')->item(0);
            $this->assertNotNull($h3, "Kartu ke-{$i} tanpa h3");
            $this->assertSame(
                $this->norm($h3->textContent),
                $this->norm((string) $trip['name']),
                "TouristTrip ke-{$i} tidak sama dengan h3 kartu"
            );
        }
    }

    /**
     * @testdox SEO-25 WebPage.inLanguage benar di keenam URL bahasa
     */
    public function test_seo_25(): void
    {
        $expected = ['/' => 'id', '/en' => 'en', '/ms' => 'ms', '/zh' => 'zh-Hans', '/ja' => 'ja', '/ko' => 'ko'];
        foreach ($expected as $uri => $lang) {
            $webpages = array_values(array_filter(
                $this->graphOf($uri),
                fn (array $node) => ($node['@type'] ?? null) === 'WebPage'
            ));

            $this->assertCount(1, $webpages, "WebPage tidak ada di {$uri}");
            $this->assertSame($lang, $webpages[0]['inLanguage'] ?? null, "WebPage.inLanguage di {$uri} salah");
        }
    }

    /**
     * @testdox SEO-26 url/image/@id absolut http(s), tanpa GANTI-DOMAIN/example.com
     */
    public function test_seo_26(): void
    {
        $flat = $this->graphOf('/');

        $checked = 0;
        foreach ($flat as $node) {
            foreach (['@id', 'url', 'image'] as $key) {
                if (! isset($node[$key]) || ! is_string($node[$key])) {
                    continue;
                }
                $value = $node[$key];
                $this->assertMatchesRegularExpression('#^https?://#', $value, "{$key} bukan URL absolut: {$value}");
                $this->assertStringNotContainsString('GANTI-DOMAIN', $value);
                $this->assertStringNotContainsString('example.com', $value);
                $checked++;
            }
        }

        $this->assertGreaterThan(0, $checked, 'Tidak ada nilai url/image/@id untuk diperiksa');
    }

    /**
     * @testdox SEO-27 semua blok JSON-LD valid dengan @context schema.org (6 URL)
     */
    public function test_seo_27(): void
    {
        foreach (['/', '/en', '/ms', '/zh', '/ja', '/ko'] as $uri) {
            $blocks = $this->jsonLdBlocks($this->fetchHtml($uri));
            $this->assertNotEmpty($blocks, "JSON-LD tidak ada di {$uri}");
            foreach ($blocks as $block) {
                $this->assertSame('https://schema.org', $block['@context'] ?? null, "@context salah di {$uri}");
            }
        }
    }
}
