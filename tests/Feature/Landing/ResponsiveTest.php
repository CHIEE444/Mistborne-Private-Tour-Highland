<?php

namespace Tests\Feature\Landing;

use Tests\Support\ParsesHtml;
use Tests\TestCase;

/**
 * Tes responsif statis (Tahap 6): anti-pola lebar viewport, meta viewport,
 * penghormatan min-h-svh, dan safe-area concierge (6 kasus: RESP-07..12).
 */
class ResponsiveTest extends TestCase
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
     * @testdox RESP-07 resources/** tidak memuat w-screen dan 100vw
     */
    public function test_resp_07(): void
    {
        // Pengecualian terdokumentasi: max-w-[calc(100vw-2rem)] pada panel dropdown bahasa
        // justru GUARD anti-overflow (membatasi lebar panel, bukan membuat elemen selebar viewport).
        $allowed = [
            'resources/views/komponen/tata-letak/navbar.blade.php',
        ];

        $hits = [];
        foreach ($this->filesIn(base_path('resources')) as $path) {
            $content = (string) file_get_contents($path);
            $short = str_replace(base_path().'/', '', $path);
            if (in_array($short, $allowed, true)) {
                continue;
            }
            if (str_contains($content, 'w-screen')) {
                $hits[] = $short.' => w-screen';
            }
            if (str_contains($content, '100vw')) {
                $hits[] = $short.' => 100vw';
            }
        }

        $this->assertSame([], $hits, 'Anti-pola lebar viewport tersisa: '.implode('; ', $hits));
    }

    /**
     * @testdox RESP-08 elemen ber-class min-h-screen juga punya min-h-svh
     */
    public function test_resp_08(): void
    {
        foreach (['/', '/en', '/ms', '/zh', '/ja', '/ko'] as $uri) {
            $xp = $this->parseHtml($this->fetchHtml($uri));
            $elements = $xp->query('//*[contains(concat(" ", normalize-space(@class), " "), " min-h-screen ")]');

            /** @var \DOMElement $el */
            foreach ($elements as $el) {
                $this->assertStringContainsString(
                    'min-h-svh',
                    $el->getAttribute('class'),
                    "min-h-screen tanpa min-h-svh di {$uri}"
                );
            }
            $this->assertTrue(true, "Pemindaian min-h-screen selesai untuk {$uri}");
        }
    }

    /**
     * @testdox RESP-09 w-[Npx] dengan N>320 selalu ber-prefix lg:/xl:/2xl: atau berpasangan max-w-full
     */
    public function test_resp_09(): void
    {
        foreach (['/', '/en', '/ms', '/zh', '/ja', '/ko'] as $uri) {
            $html = $this->fetchHtml($uri);
            preg_match_all('/class="([^"]*)"/', $html, $matches);

            $offenders = [];
            foreach ($matches[1] as $class) {
                $tokens = preg_split('/\s+/', trim($class)) ?: [];
                $hasPrefix = (bool) array_filter($tokens, fn (string $t) => preg_match('/^(lg|xl|2xl):/', $t) === 1);
                $hasMaxFull = in_array('max-w-full', $tokens, true);

                foreach ($tokens as $token) {
                    if (preg_match('/^w-\[(\d+)px\]$/', $token, $m) && (int) $m[1] > 320 && ! $hasPrefix && ! $hasMaxFull) {
                        $offenders[] = $token;
                    }
                }
            }

            $this->assertSame([], $offenders, "w-[Npx] berisiko overflow di {$uri}: ".implode(', ', array_unique($offenders)));
        }
    }

    /**
     * @testdox RESP-10 meta viewport memuat width=device-width, initial-scale=1, viewport-fit=cover
     */
    public function test_resp_10(): void
    {
        $xp = $this->parseHtml($this->fetchHtml('/'));
        $meta = $xp->query('//head//meta[@name="viewport"]');

        $this->assertSame(1, $meta->length, 'meta viewport tidak ada');
        $content = $meta->item(0)->getAttribute('content');
        $this->assertStringContainsString('width=device-width', $content);
        $this->assertStringContainsString('initial-scale=1', $content);
        $this->assertStringContainsString('viewport-fit=cover', $content);
    }

    /**
     * @testdox RESP-11 html dan body tidak ber-class overflow-x-hidden (di 6 URL)
     */
    public function test_resp_11(): void
    {
        foreach (['/', '/en', '/ms', '/zh', '/ja', '/ko'] as $uri) {
            $xp = $this->parseHtml($this->fetchHtml($uri));

            $htmlClass = $xp->query('//html')->item(0)->getAttribute('class');
            $bodyClass = $xp->query('//body')->item(0)->getAttribute('class');

            $this->assertStringNotContainsString('overflow-x-hidden', $htmlClass, "html ber-overflow-x-hidden di {$uri}");
            $this->assertStringNotContainsString('overflow-x-hidden', $bodyClass, "body ber-overflow-x-hidden di {$uri}");
        }
    }

    /**
     * @testdox RESP-12 #concierge menghormati env(safe-area-inset-bottom)
     */
    public function test_resp_12(): void
    {
        $xp = $this->parseHtml($this->fetchHtml('/'));
        $concierge = $xp->query('//*[@id="concierge"]');

        $this->assertSame(1, $concierge->length, '#concierge tidak ada');
        $this->assertStringContainsString(
            'safe-area-inset-bottom',
            $concierge->item(0)->getAttribute('class'),
            '#concierge tanpa env(safe-area-inset-bottom)'
        );
    }
}
