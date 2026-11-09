<?php

namespace Tests\Feature\Landing;

use Tests\Support\ParsesHtml;
use Tests\TestCase;

/**
 * Tes multi-bahasa (LANG-01..16): dropdown bahasa, URL, hreflang/canonical, judul, dan kelengkapan terjemahan.
 */
class LangTest extends TestCase
{
    use ParsesHtml;

    /** URL bahasa → kode yang diharapkan. */
    private const LOCALES = [
        '/' => 'id',
        '/en' => 'en',
        '/ms' => 'ms',
        '/zh' => 'zh-Hans',
        '/ja' => 'ja',
        '/ko' => 'ko',
        '/es' => 'es',
        '/fr' => 'fr',
        '/ar' => 'ar',
    ];

    /** Locale yang punya file terjemahan JSON. */
    private const JSON_LOCALES = ['en', 'ms', 'zh', 'ja', 'ko', 'es', 'fr', 'ar'];

    /**
     * Kunci yang BOLEH nilainya sama dengan kuncinya (dan boleh tetap terlihat
     * "Inggris"), karena memang nama diri: merek, nama journey/paket, nama tempat,
     * alamat resmi, dan kata serapan yang dipakai sebagai istilah Indonesia.
     */
    private const PROPER_NOUNS = [
        // Nama journey / paket / acara
        'Journey',
        'Sunrise Dieng & Tambi',
        'Sunrise Dieng & Telaga Warna',
        'Bromo Sunrise Journey',
        'Sikunir Golden Sunrise',
        'Dieng Plateau Explorer',
        'Custom Rute Pegunungan',
        'Golden Sunrise 05.15 WIB',
        // Merek & tagline
        'MISTBORNE',
        'Private Highland Travel',
        'MISTBORNE — Private Highland Travel ke Dieng & Wonosobo',
        'MISTBORNE Private Highland Travel. Hak Cipta Dilindungi.',
        // Nama tempat & alamat resmi
        'Bromo',
        'Dieng',
        'Ijen',
        'Semarang',
        'Wonosobo',
        'Yogyakarta',
        'Sikunir',
        'Jawa Tengah',
        'Jawa Timur',
        'Jawa Barat',
        'Semarang / Yogyakarta / Dieng',
        'Jl. Raya Dieng KM 7, Garung',
        'Jl. Raya Dieng KM 7, Garung, Wonosobo, Jawa Tengah 56353',
        'Satelit 7°21\'S 109°54\'E',
        // Kata serapan yang sudah jadi istilah Indonesia (KBBI)
        'booking',
        // Nama journey/paket tersier (dipakai di form kontak) — diterjemahkan bebas antar bahasa,
        // boleh identik dengan kuncinya di locale Latin.
        'Bromo Sunrise Journey',
    ];

    /**
     * Frasa Indonesia yang tidak boleh muncul sebagai nilai terjemahan di locale lain (dipakai LANG-18).
     */
    private const ID_PHRASES = [
        'Perjalanan Populer',
        'Paket Wisata Dataran Tinggi',
        'Lihat Detail Paket',
        'Persiapan Perjalanan Dataran Tinggi',
        'Lokasi Pick-up',
        'Perjalanan Selesai',
    ];

    /** Kata berbahasa Inggris — penanda kunci belum diterjemahkan. */
    private const ENGLISH_WORDS = [
        'the', 'and', 'of', 'for', 'your', 'with', 'from', 'view', 'book', 'explore',
        'more', 'read', 'all', 'detail', 'full', 'tour', 'trip', 'private', 'highland',
        'highlands', 'travel', 'suv', 'custom', 'adventure', 'essential', 'flexible',
        'per', 'booking', 'learn', 'browse', 'available', 'verified', 'previous', 'next',
        'slide', 'choose', 'send', 'chat', 'open', 'menu', 'language', 'home', 'gallery',
        'contact', 'about', 'package', 'rating', 'guest', 'master', 'chauffeur', 'family',
        'corporate', 'retreat', 'sunrise', 'journey', 'plateau', 'explorer', 'guide',
        'best', 'top', 'pick', 'early', 'escape', 'experience', 'assistant',
    ];

    /** Kata berbahasa Indonesia (termasuk serapan resmi) — meniadakan penanda Inggris. */
    private const INDONESIAN_WORDS = [
        'dan', 'yang', 'untuk', 'dengan', 'dari', 'ini', 'itu', 'tidak', 'atau', 'kami',
        'anda', 'sudah', 'akan', 'pada', 'dalam', 'juga', 'bisa', 'harus', 'di', 'ke',
        'saya', 'kita', 'semua', 'lihat', 'pesan', 'harga', 'wisata', 'perjalanan',
        'dataran', 'tinggi', 'kebun', 'teh', 'sopir', 'ulasan', 'artikel', 'galeri',
        'kontak', 'beranda', 'bahasa', 'buka', 'kembali', 'atas', 'esensial',
        'petualangan', 'kustom', 'nyaman', 'fleksibel', 'paket', 'tamu', 'tersedia',
        'terverifikasi', 'sebelumnya', 'selanjutnya', 'pilih', 'kirim', 'asisten',
        'privat', 'rute', 'pegunungan', 'destinasi', 'pemandangan', 'ekspedisi',
        'penjelajah', 'favorit', 'keluarga', 'pilihan', 'utama', 'berangkat', 'subuh',
        'durasi', 'pengalaman', 'korporat', 'langsung', 'bukan', 'sekadar', 'populer',
        'pemesanan', 'filter', 'wilayah', 'navigasi', 'pelanggan', 'jelajahi', 'hak',
        'lokasi', 'durasi',
        'cipta', 'dilindungi', 'direkomendasikan', 'cek', 'satelit', 'basecamp', 'popup',
        'concierge', 'whatsapp', 'mistborne', 'keberangkatan', 'kenyamanan', 'kapasitas',
        'bagasi', 'kabin', 'elevasi', 'aman', 'tercantik', 'tergesa', 'selevel',
        'eksekutif', 'disarankan', 'direksi', 'rombongan', 'penumpang', 'kendaraan',
        'perkebunan', 'pelayanan', 'penyedia', 'penjemputan', 'peralatan',
        'perlengkapan', 'permintaan', 'persiapan', 'pertanyaan', 'pembayaran',
        'pembatalan', 'pengembalian', 'pengemudi', 'penginapan', 'penilaian',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    /**
     * @testdox LANG-01 dropdown bahasa memuat 9 tautan lengkap di /
     */
    public function test_lang_01(): void
    {
        $xp = $this->parseHtml($this->fetchHtml('/'));
        $menus = $xp->query('//*[@data-lang-menu]');

        $this->assertSame(1, $menus->length, '[data-lang-menu] tidak ada / bukan tepat satu');
        $links = $xp->query('//*[@data-lang-menu]//a');
        $this->assertCount(9, $links, 'Dropdown bahasa harus 9 tautan');

        $codes = [];
        /** @var \DOMElement $a */
        foreach ($links as $a) {
            $this->assertNotSame('', $a->getAttribute('href'), 'tautan tanpa href');
            $this->assertNotSame('', $a->getAttribute('hreflang'), 'tautan tanpa hreflang');
            $this->assertNotSame('', $a->getAttribute('lang'), 'tautan tanpa lang');
            $codes[] = $a->getAttribute('hreflang');
        }

        $this->assertSame(['id', 'en', 'ms', 'zh', 'ja', 'ko', 'es', 'fr', 'ar'], $codes, 'Urutan/simpanan kode bahasa salah');
    }

    /**
     * @testdox LANG-02 tombol bahasa menampilkan kode aktif (EN, JA)
     */
    public function test_lang_02(): void
    {
        foreach (['/en' => 'EN', '/ja' => 'JA'] as $uri => $code) {
            $xp = $this->parseHtml($this->fetchHtml($uri));
            $button = $xp->query(sprintf('//header//button[span[normalize-space(.)="%s"]]', $code));

            $this->assertGreaterThan(
                0,
                $button->length,
                "Tombol bahasa di {$uri} tidak menampilkan {$code}"
            );
        }
    }

    /**
     * @testdox LANG-03 keenam URL bahasa merespons 200
     */
    public function test_lang_03(): void
    {
        foreach (array_keys(self::LOCALES) as $uri) {
            $this->get($uri)->assertStatus(200, "{$uri} bukan 200");
        }
    }

    /**
     * @testdox LANG-04 /id me-redirect 301 ke /
     */
    public function test_lang_04(): void
    {
        $response = $this->get('/id');
        $response->assertStatus(301, '/id harus 301');
        $response->assertHeader('Location', '/', 'Location /id harus persis /');
    }

    /**
     * @testdox LANG-05 kode bahasa asing (/xx) me-return 404
     */
    public function test_lang_05(): void
    {
        // /fr kini locale resmi (halaman 200); kode yang benar-benar tidak dikenal tetap 404.
        foreach (['/xx', '/qq'] as $uri) {
            $this->get($uri)->assertStatus(404, "{$uri} harus 404");
        }
    }

    /**
     * @testdox LANG-06 html lang sesuai tiap bahasa (zh = zh-Hans)
     */
    public function test_lang_06(): void
    {
        foreach (self::LOCALES as $uri => $expected) {
            $xp = $this->parseHtml($this->fetchHtml($uri));
            $html = $xp->query('//html');

            $this->assertSame($expected, $html->item(0)->getAttribute('lang'), "html lang di {$uri} salah");
        }
    }

    /**
     * @testdox LANG-07 head memuat 10 hreflang; x-default menunjuk /
     */
    public function test_lang_07(): void
    {
        $xp = $this->parseHtml($this->fetchHtml('/'));
        $alternates = $xp->query('//head//link[@rel="alternate" and @hreflang]');

        $this->assertSame(10, $alternates->length, 'harus 10 link hreflang (9 bahasa + x-default)');

        $xDefault = $xp->query('//head//link[@rel="alternate" and @hreflang="x-default"]');
        $this->assertSame(1, $xDefault->length, 'x-default harus tepat satu');
        $this->assertSame(url('/'), $xDefault->item(0)->getAttribute('href'), 'x-default harus menunjuk /');
    }

    /**
     * @testdox LANG-08 canonical per bahasa, og:locale tepat, ada og:locale:alternate
     */
    public function test_lang_08(): void
    {
        $cases = [
            '/en' => 'en_US',
            '/ja' => 'ja_JP',
        ];

        foreach ($cases as $uri => $locale) {
            $xp = $this->parseHtml($this->fetchHtml($uri));

            $canonical = $xp->query('//head//link[@rel="canonical"]')->item(0);
            $this->assertStringEndsWith($uri, $canonical->getAttribute('href'), "canonical {$uri} salah");

            $ogLocale = $xp->query('//head//meta[@property="og:locale"]')->item(0);
            $this->assertSame($locale, $ogLocale->getAttribute('content'), "og:locale di {$uri} salah");

            $this->assertGreaterThan(
                0,
                $xp->query('//head//meta[@property="og:locale:alternate"]')->length,
                "og:locale:alternate tidak ada di {$uri}"
            );
        }
    }

    /**
     * @testdox LANG-09 title /en berbeda dari / dan h1 tetap satu di semua bahasa
     */
    public function test_lang_09(): void
    {
        $titleOf = function (string $uri): string {
            $xp = $this->parseHtml($this->fetchHtml($uri));

            return $this->norm($xp->query('//head/title')->item(0)->textContent);
        };

        $titleId = $titleOf('/');
        $titleEn = $titleOf('/en');
        $this->assertNotSame($titleId, $titleEn, 'Title /en harus berbeda dari /');

        foreach (self::LOCALES as $uri => $expectedLang) {
            $xp = $this->parseHtml($this->fetchHtml($uri));
            $h1 = $xp->query('//h1');
            $this->assertSame(1, $h1->length, "Jumlah h1 di {$uri} harus 1");

            if ($uri === '/') {
                // Judul hero kini diterjemahkan (sumber Indonesia).
                $this->assertSame('Sambut Pagi di Atas Awan.', $this->norm($h1->item(0)->textContent));
            }
            unset($expectedLang);
        }
    }

    /**
     * @testdox LANG-10 setiap kunci __() di Blade ada di 5 file terjemahan
     */
    public function test_lang_10(): void
    {
        $keys = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(base_path('resources/views'), \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if (! $file->isFile() || $this->adalahFileAdmin($file->getPathname())) {
                continue;
            }
            $content = (string) file_get_contents($file->getPathname());
            preg_match_all("/__\\('((?:[^'\\\\]|\\\\.)*)'/u", $content, $single);
            preg_match_all('/__\\("([^"]+)"/u', $content, $double);
            foreach (array_merge($single[1], $double[1]) as $key) {
                $keys[stripcslashes($key)] = true;
            }
        }

        $keys = array_keys($keys);
        $this->assertGreaterThan(100, $keys, 'Ekstraksi kunci __() terlalu sedikit: '.count($keys));

        foreach (self::JSON_LOCALES as $locale) {
            $path = base_path("lang/{$locale}.json");
            $this->assertFileExists($path, "lang/{$locale}.json tidak ada");
            $translations = json_decode((string) file_get_contents($path), true);
            $this->assertIsArray($translations, "lang/{$locale}.json bukan JSON valid");

            foreach ($keys as $key) {
                $this->assertArrayHasKey($key, $translations, "Kunci hilang di {$locale}.json: {$key}");
                $this->assertNotSame('', trim((string) $translations[$key]), "Nilai kosong di {$locale}.json: {$key}");

                $keyPlaceholders = [];
                preg_match_all('/:[a-zA-Z_]+/', $key, $keyPlaceholders);
                $valuePlaceholders = [];
                preg_match_all('/:[a-zA-Z_]+/', (string) ($translations[$key] ?? ''), $valuePlaceholders);
                sort($keyPlaceholders[0]);
                sort($valuePlaceholders[0]);
                $this->assertSame(
                    $keyPlaceholders[0],
                    $valuePlaceholders[0],
                    "Placeholder tidak cocok di {$locale}.json: {$key}"
                );
            }
        }
    }

    /**
     * @testdox LANG-11 kelima file terjemahan lolos json_decode
     */
    public function test_lang_11(): void
    {
        foreach (self::JSON_LOCALES as $locale) {
            $path = base_path("lang/{$locale}.json");
            $this->assertFileExists($path);

            json_decode((string) file_get_contents($path), true);
            $this->assertSame(
                JSON_ERROR_NONE,
                json_last_error(),
                "lang/{$locale}.json gagal decode: ".json_last_error_msg()
            );
        }
    }

    /**
     * @testdox LANG-12 FAQPage /en identik dengan DOM dan inLanguage=en
     */
    public function test_lang_12(): void
    {
        $html = $this->fetchHtml('/en');
        $xp = $this->parseHtml($html);

        $faqBlocks = array_filter(
            $this->jsonLdBlocks($html),
            fn (array $block) => ($block['@type'] ?? null) === 'FAQPage'
        );
        $this->assertCount(1, $faqBlocks, 'FAQPage tidak ada di /en');
        $faq = array_values($faqBlocks)[0];

        $this->assertSame('en', $faq['inLanguage'] ?? null, 'FAQPage.inLanguage harus en');
        $this->assertCount(3, $faq['mainEntity']);

        foreach ($faq['mainEntity'] as $i => $question) {
            $panel = $xp->query(sprintf('//*[@id="faq-panel-%d"]', $i));
            $this->assertSame(1, $panel->length, "Panel FAQ #{$i} tidak ada");
            $this->assertSame(
                $this->norm($panel->item(0)->textContent),
                $this->norm($question['acceptedAnswer']['text']),
                "Jawaban FAQ ke-{$i} di JSON-LD ≠ DOM (bahasa en)"
            );
        }
    }

    /**
     * @testdox LANG-13 Accept-Language en tidak memicu pengalihan otomatis
     */
    public function test_lang_13(): void
    {
        $response = $this->get('/', ['Accept-Language' => 'en']);

        $response->assertStatus(200, 'GET / dengan Accept-Language:en harus tetap 200');
        $response->assertHeaderMissing('Location', 'GET / tidak boleh me-redirect');
    }

    /**
     * @testdox LANG-14 semua bahasa memuat merek, journey, harga, dan telepon dari data
     */
    public function test_lang_14(): void
    {
        $journeyData = $this->source('resources/data/journeys.php');
        preg_match_all("/'price' => '([^']+)'/", $journeyData, $prices);
        $this->assertCount(9, $prices[1], 'Harga journey di partial data harus 9');

        $homeBlade = $this->source('resources/views/pages/beranda.blade.php');
        $this->assertMatchesRegularExpression("/'telephone' => '([^']+)'/u", $homeBlade, 'telepon harus dibaca dari data');
        preg_match("/'telephone' => '([^']+)'/u", $homeBlade, $phone);

        foreach (array_keys(self::LOCALES) as $uri) {
            $html = $this->fetchHtml($uri);

            $this->assertStringContainsString('MISTBORNE', $html, "merek hilang di {$uri}");
            $this->assertStringContainsString('BROMO SUNRISE JOURNEY', $html, "nama journey hilang di {$uri}");
            // Harga ke-4 milik journey kustom, yang tampil sebagai "Harga disesuaikan" (bukan angka).
            foreach (array_slice($prices[1], 0, 3) as $price) {
                $this->assertStringContainsString($price, $html, "harga {$price} hilang di {$uri}");
            }
            $this->assertStringContainsString($phone[1], $html, "telepon hilang di {$uri}");
        }
    }

    /**
     * @testdox LANG-15 sitemap punya 9 URL dengan 10 xhtml:link masing-masing
     */
    public function test_lang_15(): void
    {
        if (! extension_loaded('SimpleXML')) {
            $this->markTestSkipped('Ekstensi SimpleXML tidak tersedia');
        }

        $path = base_path('public/sitemap.xml');
        $this->assertFileExists($path);
        $raw = (string) file_get_contents($path);

        $this->assertStringContainsString('xmlns:xhtml="http://www.w3.org/1999/xhtml"', $raw, 'deklarasi xmlns:xhtml hilang');
        $this->assertStringContainsString('TODO', $raw, 'TODO domain hilang');

        $xml = simplexml_load_file($path);
        $this->assertNotFalse($xml, 'sitemap.xml tidak valid');
        $this->assertCount(9, $xml->url, 'sitemap.xml harus 9 <url>');

        $xhtml = 'http://www.w3.org/1999/xhtml';
        $index = 0;
        foreach ($xml->url as $url) {
            $index++;
            $links = $url->children($xhtml);
            $this->assertCount(10, $links->link, "URL ke-{$index} harus punya 10 xhtml:link");
        }
    }

    /**
     * Admin panel (resources/views/admin, components/admin, components/tata-letak/admin*)
     * sengaja hanya berbahasa Indonesia (bukan bagian dari 9 bahasa situs publik),
     * jadi kunci __() di sana tidak wajib ada di lang/*.json.
     */
    private function adalahFileAdmin(string $path): bool
    {
        return str_contains($path, '/views/admin/')
            || str_contains($path, '/views/components/admin/')
            || str_ends_with($path, '/components/tata-letak/admin.blade.php')
            || str_ends_with($path, '/components/tata-letak/autentikasi.blade.php')
            || str_ends_with($path, '/components/tata-letak/masuk.blade.php');
    }

    /**
     * Kumpulkan semua kunci __('...') dari resources/views.
     *
     * @return array<string, int> kunci => jumlah pemakaian
     */
    private function bladeKeys(): array
    {
        $keys = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(base_path('resources/views'), \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if (! $file->isFile() || $this->adalahFileAdmin($file->getPathname())) {
                continue;
            }
            $content = (string) file_get_contents($file->getPathname());
            preg_match_all("/__\\('((?:[^'\\\\]|\\\\.)*)'/u", $content, $single);
            preg_match_all('/__\\("([^"]+)"/u', $content, $double);
            foreach (array_merge($single[1], $double[1]) as $key) {
                $keys[stripcslashes($key)] = ($keys[stripcslashes($key)] ?? 0) + 1;
            }
        }

        return $keys;
    }

    /**
     * Kata berbahasa Indonesia / Inggris di dalam sebuah kunci.
     *
     * @return array{int, int} [jumlah kata Inggris, jumlah kata Indonesia]
     */
    private function wordSignals(string $key): array
    {
        $words = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($key), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $en = 0;
        $id = 0;
        foreach ($words as $word) {
            if (in_array($word, self::ENGLISH_WORDS, true)) {
                $en++;
            }
            if (in_array($word, self::INDONESIAN_WORDS, true)) {
                $id++;
            }
        }

        return [$en, $id];
    }

    /**
     * Kunci yang terlihat berbahasa Inggris: ada kata Inggris dan tidak ada
     * satu pun kata Indonesia. Kata serapan yang sudah wajar dipakai di
     * Indonesia (basecamp, concierge, whatsapp) tidak dihitung Inggris.
     */
    private function looksEnglish(string $key): bool
    {
        [$en, $id] = $this->wordSignals($key);

        return $en > 0 && $id === 0;
    }

    /**
     * LANG-19 tidak ada teks campur bahasa: ' tidak boleh memuat copy Inggris lama
     * (judul hero kini diterjemahkan; tombol memakai kunci Indonesia).
     */
    public function test_lang_19(): void
    {
        $html = $this->fetchHtml('/');

        foreach (
            ['ESCAPE TO THE HIGHLANDS', 'Book Your Journey', 'Explore All Journeys',
                'PRIVATE HIGHLAND EXPERIENCE', 'Concierge'] as $forbidden
        ) {
            $this->assertStringNotContainsString($forbidden, $html, "' memuat teks Inggris: {$forbidden}");
        }
    }

    /**
     * LANG-20 locale non-id tidak memuat frasa sumber Indonesia sebagai NILAI terjemahan.
     * Nilai boleh beririsan dengan nama diri (mis. 'Dataran Tinggi'), jadi yang dicek adalah
     * kesamaan penuh kunci = nilai atau frasa panjang khas di nilai.
     */
    public function test_lang_20(): void
    {
        foreach (self::ID_PHRASES as $phrase) {
            foreach (self::JSON_LOCALES as $locale) {
                $translations = json_decode((string) file_get_contents(base_path("lang/{$locale}.json")), true);
                if (! isset($translations[$phrase])) {
                    continue; // kunci boleh tidak ada jika memang tidak dipakai Blade
                }
                $this->assertNotSame(
                    $phrase,
                    $translations[$phrase],
                    "Nilai masih sumber Indonesia di {$locale}.json: {$phrase}"
                );
            }
        }
    }

    /**
     * LANG-21 halaman es/fr/ar benar-benar berbeda dari Indonesia (menu + judul hero),
     * dan nilai default <select> (kode stabil) sama di semua bahasa.
     */
    public function test_lang_21(): void
    {
        $id = $this->fetchHtml('/');

        foreach (['/es', '/fr', '/ar'] as $uri) {
            $html = $this->fetchHtml($uri);

            $this->assertNotSame($id, $html, "{$uri} identik dengan /");

            $xpId = $this->parseHtml($id);
            $xp = $this->parseHtml($html);

            // Judul hero berbeda dari versi Indonesia
            $this->assertNotSame(
                $this->norm($xpId->query('//h1')->item(0)->textContent),
                $this->norm($xp->query('//h1')->item(0)->textContent),
                "h1 di {$uri} masih sama dengan versi Indonesia"
            );

            // Label menu desktop berbeda dari versi Indonesia
            $menuId = $xpId->query('//header//nav[@data-component]//a');
            $menu = $xp->query('//header//nav[@data-component]//a');
            $this->assertSame($menuId->length, $menu->length, "Jumlah tautan menu di {$uri} berubah");
            $diff = 0;
            for ($i = 0; $i < $menu->length; $i++) {
                if ($this->norm($menuId->item($i)->textContent) !== $this->norm($menu->item($i)->textContent)) {
                    $diff++;
                }
            }
            $this->assertGreaterThan(0, $diff, "Tidak ada satu pun label menu yang diterjemahkan di {$uri}");
        }
    }
}
