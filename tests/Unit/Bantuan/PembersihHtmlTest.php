<?php

namespace Tests\Unit\Bantuan;

use App\Bantuan\PembersihHtml;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PembersihHtmlTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function payloadBerbahaya(): array
    {
        return [
            'script' => ['<p>a</p><script>alert(1)</script>'],
            'script campur huruf' => ['<ScRiPt>alert(1)</sCrIpT><p>x</p>'],
            'img onerror' => ['<p>x</p><img src=x onerror=alert(1)>'],
            'atribut on* pada p' => ['<p onclick="alert(1)" style="x:y">teks</p>'],
            'href javascript' => ['<a href="javascript:alert(1)">klik</a>'],
            'href javascript bertab' => ['<a href="jav&#x09;ascript:alert(1)">klik</a>'],
            'href data' => ['<a href="data:text/html,<script>alert(1)</script>">klik</a>'],
            'svg onload' => ['<svg onload=alert(1)><circle/></svg><p>x</p>'],
            'iframe srcdoc' => ['<iframe srcdoc="<script>alert(1)</script>"></iframe><p>x</p>'],
            'form dan input' => ['<form action="http://jahat"><input name=a></form><p>x</p>'],
            'tag tidak ditutup' => ['<p><b onmouseover=alert(1)>tebal'],
            'math' => ['<math><mi xlink:href="javascript:alert(1)">x</mi></math>'],
        ];
    }

    #[DataProvider('payloadBerbahaya')]
    public function test_payload_xss_tidak_lolos(string $payload): void
    {
        $hasil = strtolower(PembersihHtml::artikel($payload));

        foreach (['<script', '<iframe', '<svg', '<img', '<form', '<input', '<math', 'onerror', 'onload', 'onclick', 'onmouseover', 'javascript:', 'data:text', 'style=', 'srcdoc'] as $terlarang) {
            $this->assertStringNotContainsString($terlarang, $hasil, "Lolos: $terlarang dari $payload");
        }
    }

    public function test_pemformatan_dan_tautan_https_dipertahankan_dengan_atribut_aman(): void
    {
        $hasil = PembersihHtml::artikel('<h1>Judul</h1><p><strong>Tebal</strong> dan <a href="https://contoh.id/x" onclick="x()">tautan</a></p><ul><li>satu</li></ul>');

        $this->assertStringContainsString('<h2>Judul</h2>', $hasil);
        $this->assertStringContainsString('<strong>Tebal</strong>', $hasil);
        $this->assertStringContainsString('<a href="https://contoh.id/x" target="_blank" rel="noopener noreferrer nofollow">tautan</a>', $hasil);
        $this->assertStringContainsString('<ul><li>satu</li></ul>', $hasil);
    }

    public function test_teks_polos_di_escape_dan_dijadikan_paragraf(): void
    {
        $this->assertSame('<p>a &amp; b</p>'."\n".'<p>baris dua</p>', PembersihHtml::artikel("a & b\n\nbaris dua"));
        $this->assertSame('', PembersihHtml::artikel('   '));
    }
}
