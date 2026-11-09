<?php

namespace Tests\Feature\Landing;

use Tests\Support\ParsesHtml;
use Tests\TestCase;

/**
 * Tes sistem animasi: penanda JS aktif, token CSS, aturan reveal,
 * dan penghormatan prefers-reduced-motion (6 kasus: ANIM).
 */
class AnimationTest extends TestCase
{
    use ParsesHtml;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    /**
     * @testdox ANIM-06 <head> memuat skrip inline penanda html.js
     */
    public function test_anim_06(): void
    {
        $html = $this->fetchHtml('/');
        $xp = $this->parseHtml($html);

        $scripts = $xp->query('//head//script[not(@src)]');
        $found = false;
        foreach ($scripts as $script) {
            if (str_contains($script->textContent, "classList.add('js')")) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, "Skrip inline classList.add('js') tidak ada di <head>");
    }

    /**
     * @testdox ANIM-07 CSS terpisah memuat @theme, @keyframes, dan reduced-motion
     */
    public function test_anim_07(): void
    {
        $theme = $this->source('resources/css/theme.css');
        $motion = $this->source('resources/css/motion.css');

        $this->assertStringContainsString('@theme', $theme, '@theme tidak ada');
        $this->assertStringContainsString('@keyframes', $motion, '@keyframes tidak ada');
        $this->assertStringContainsString(
            '@media (prefers-reduced-motion: reduce)',
            $motion,
            'aturan prefers-reduced-motion tidak ada'
        );
    }

    /**
     * @testdox ANIM-11 tanpa data-reveal di header, hero, dan concierge
     */
    public function test_anim_11(): void
    {
        $xp = $this->parseHtml($this->fetchHtml('/'));

        foreach (['//header', '//*[@id="beranda"]', '//*[@id="concierge"]'] as $scope) {
            $nodes = $xp->query($scope.'//*[@data-reveal]');
            $this->assertSame(
                0,
                $nodes->length,
                "Masih ada [data-reveal] di scope: {$scope}"
            );
        }
    }

    /**
     * @testdox ANIM-12 nilai data-reveal valid dan data-reveal-delay 0-800
     */
    public function test_anim_12(): void
    {
        $xp = $this->parseHtml($this->fetchHtml('/'));
        $allowed = ['up', 'fade', 'left', 'right', 'scale'];

        $reveals = $xp->query('//*[@data-reveal]');
        $this->assertGreaterThan(0, $reveals->length, 'Tidak ada elemen reveal sama sekali');

        foreach ($reveals as $el) {
            $value = $el->getAttribute('data-reveal');
            $this->assertContains($value, $allowed, "data-reveal='{$value}' di luar daftar");
        }

        foreach ($xp->query('//*[@data-reveal-delay]') as $el) {
            $delay = $el->getAttribute('data-reveal-delay');
            $this->assertMatchesRegularExpression('/^\d+$/', $delay, "delay non-numerik: {$delay}");
            $this->assertGreaterThanOrEqual(0, (int) $delay);
            $this->assertLessThanOrEqual(800, (int) $delay, "delay {$delay} > 800ms");
        }
    }

    /**
     * @testdox ANIM-13 setiap selector [data-reveal] di CSS berprefix html.js
     */
    public function test_anim_13(): void
    {
        $css = $this->source('resources/css/motion.css');
        $offset = 0;
        $count = 0;
        $offenders = [];

        while (($pos = strpos($css, '[data-reveal', $offset)) !== false) {
            $count++;
            // Potong mundur sampai batas blok { } ; atau baris baru: itulah selector-nya.
            $start = $pos;
            while ($start > 0 && ! in_array($css[$start - 1], ['{', '}', ';', "\n"], true)) {
                $start--;
            }
            $selector = trim(substr($css, $start, $pos - $start));
            if (! str_contains($selector, 'html.js')) {
                $offenders[] = $selector;
            }
            $offset = $pos + 1;
        }

        $this->assertGreaterThan(0, $count, 'Tidak ada selector [data-reveal] di motion.css');
        $this->assertSame([], $offenders, 'Selector [data-reveal] tanpa html.js: '.implode(' | ', $offenders));
    }

    /**
     * @testdox ANIM-15 h1 dan deskripsi hero slide 1 tanpa x-cloak
     */
    public function test_anim_15(): void
    {
        $xp = $this->parseHtml($this->fetchHtml('/'));

        $h1 = $xp->query('//h1');
        $this->assertSame(1, $h1->length);
        $this->assertFalse($h1->item(0)->hasAttribute('x-cloak'), 'h1 ber-x-cloak');

        // Deskripsi slide pertama: paragraf pertama di dalam konten hero.
        $desc = $xp->query('//*[@id="beranda"]//p[1]');
        $this->assertGreaterThan(0, $desc->length, 'deskripsi hero tidak ditemukan');
        /** @var \DOMElement $p */
        $p = $desc->item(0);
        $this->assertFalse($p->hasAttribute('x-cloak'), 'deskripsi hero slide 1 ber-x-cloak');
        $this->assertNotSame('', $this->norm($p->textContent), 'deskripsi hero slide 1 kosong');
    }
}
