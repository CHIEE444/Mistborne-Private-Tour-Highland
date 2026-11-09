<?php

namespace Tests\Support;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Trait pembantu tes landing page: parsing HTML (DOMDocument + DOMXPath),
 * normalisasi teks, ekstraksi JSON-LD, dan pembacaan file sumber proyek.
 */
trait ParsesHtml
{
    /**
     * Parse HTML menjadi DOMXPath; kesalahan parser ditekan (HTML mentah
     * sering memakai atribut tanpa kutip yang membuat DOMDocument riuh).
     */
    protected function parseHtml(string $html): DOMXPath
    {
        $doc = new DOMDocument;
        $useInternal = libxml_use_internal_errors(true);
        // Deklarasi XML di depan memaksa libxml membaca sebagai UTF-8
        // (meniru meta charset yang terlambat terbaca oleh parser).
        $doc->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        libxml_use_internal_errors($useInternal);

        return new DOMXPath($doc);
    }

    /**
     * GET halaman, pastikan 200, kembalikan HTML mentahnya.
     */
    protected function fetchHtml(string $uri = '/'): string
    {
        $response = $this->get($uri);
        $response->assertStatus(200);

        return (string) $response->getContent();
    }

    /**
     * Normalisasi teks: gabungkan spasi bertumpuk dan buang spasi awal/akhir.
     */
    protected function norm(?string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', (string) $value) ?? '');
    }

    /**
     * Teks "terlihat" milik sebuah elemen: seluruh subtree ber-aria-hidden="true"
     * dibuang lebih dulu sehingga ikon dekoratif tidak dihitung sebagai konten.
     */
    protected function visibleText(DOMElement $element): string
    {
        $out = '';
        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement) {
                if ($child->getAttribute('aria-hidden') === 'true') {
                    continue;
                }
                $out .= ' '.$this->visibleText($child);
            } else {
                $out .= $child->nodeValue;
            }
        }

        return trim($out);
    }

    /**
     * Semua payload <script type="application/ld+json"> sebagai array PHP.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function jsonLdBlocks(string $html): array
    {
        preg_match_all(
            '#<script type="application/ld\+json">(.*?)</script>#s',
            $html,
            $matches
        );

        $blocks = [];
        foreach ($matches[1] as $json) {
            $decoded = json_decode($json, true);
            $this->assertIsArray($decoded, 'JSON-LD tidak valid: '.$json);
            $blocks[] = $decoded;
        }

        return $blocks;
    }

    /**
     * Baca file sumber relatif terhadap root proyek (untuk tes non-HTTP
     * seperti pemeriksaan font, data Blade, dan robots/sitemap).
     */
    protected function source(string $relativePath): string
    {
        $full = base_path($relativePath);
        $this->assertFileExists($full, "File sumber tidak ditemukan: {$relativePath}");

        return (string) file_get_contents($full);
    }
}
