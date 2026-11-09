<?php

namespace App\Bantuan;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Membersihkan HTML dari editor teks (Trix) sebelum tampil di situs publik: hanya tag
 * pemformatan yang dikenal yang dipertahankan, semua atribut dibuang kecuali href tautan
 * (http/https/mailto), sehingga isi artikel tidak bisa menyisipkan script atau gaya liar.
 */
class PembersihHtml
{
    /** Tag yang dipertahankan apa adanya. */
    private const TAG_DIIZINKAN = ['p', 'div', 'br', 'strong', 'b', 'em', 'i', 'del', 's', 'a', 'h2', 'h3', 'blockquote', 'pre', 'code', 'ul', 'ol', 'li'];

    /** Tag yang beserta isinya dibuang total. */
    private const TAG_DIBUANG = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select', 'link', 'meta', 'svg', 'math'];

    public static function artikel(string $html): string
    {
        $html = trim($html);

        if ($html === '') {
            return '';
        }

        // Isi lama/teks polos (tanpa tag): jadikan paragraf.
        if ($html === strip_tags($html)) {
            return collect(preg_split('/\R{2,}/', $html) ?: [])
                ->map(fn (string $paragraf): string => '<p>'.nl2br(e(trim($paragraf))).'</p>')
                ->implode("\n");
        }

        $dokumen = new DOMDocument;
        $sebelumnya = libxml_use_internal_errors(true);
        $dokumen->loadHTML('<?xml encoding="utf-8"?><body>'.$html.'</body>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($sebelumnya);

        $badan = $dokumen->getElementsByTagName('body')->item(0);

        if (! $badan instanceof DOMElement) {
            return e(strip_tags($html));
        }

        self::bersihkan($badan);

        $hasil = '';
        foreach ($badan->childNodes as $anak) {
            $hasil .= $dokumen->saveHTML($anak);
        }

        return trim($hasil);
    }

    private static function bersihkan(DOMNode $induk): void
    {
        foreach (iterator_to_array($induk->childNodes) as $anak) {
            if (! $anak instanceof DOMElement) {
                continue;
            }

            $nama = strtolower($anak->tagName);

            if (in_array($nama, self::TAG_DIBUANG, true)) {
                $induk->removeChild($anak);

                continue;
            }

            self::bersihkan($anak);

            if (! in_array($nama, self::TAG_DIIZINKAN, true) && $nama !== 'h1') {
                // Tag tak dikenal (mis. figure/img lampiran): buang tag, pertahankan teks di dalamnya.
                while ($anak->firstChild) {
                    $induk->insertBefore($anak->firstChild, $anak);
                }
                $induk->removeChild($anak);

                continue;
            }

            if ($nama === 'h1') {
                // Judul artikel sudah <h1>; heading di badan jadi <h2>.
                $baru = $anak->ownerDocument->createElement('h2');
                while ($anak->firstChild) {
                    $baru->appendChild($anak->firstChild);
                }
                $induk->replaceChild($baru, $anak);
                $anak = $baru;
                $nama = 'h2';
            }

            $href = $nama === 'a' ? trim($anak->getAttribute('href')) : null;

            foreach (iterator_to_array($anak->attributes) as $atribut) {
                $anak->removeAttribute($atribut->nodeName);
            }

            if ($nama === 'a') {
                if ($href !== null && preg_match('#^(https?://|mailto:)#i', $href) === 1) {
                    $anak->setAttribute('href', $href);
                    $anak->setAttribute('target', '_blank');
                    $anak->setAttribute('rel', 'noopener noreferrer nofollow');
                } else {
                    while ($anak->firstChild) {
                        $induk->insertBefore($anak->firstChild, $anak);
                    }
                    $induk->removeChild($anak);
                }
            }
        }
    }
}
