<?php

/*
 * Helper token desain dari config/desain.php, mis. <div class="bg-[{{ warna('emas') }}]">.
 */

if (! function_exists('desain')) {
    /** Ambil token desain apa pun: desain('warna.emas'), desain('tema.terang.latar'). */
    function desain(string $jalur, mixed $default = null): mixed
    {
        return config("desain.{$jalur}", $default);
    }
}

if (! function_exists('warna')) {
    /** Warna token: warna('emas') → '#C8A24A'. Kunci tak dikenal dikembalikan apa adanya. */
    function warna(string $kunci): string
    {
        return (string) config("desain.warna.{$kunci}", $kunci);
    }
}

if (! function_exists('ukuran')) {
    /** Ukuran/posisi token: ukuran('lebarMaks') → '1240px'. */
    function ukuran(string $kunci): string
    {
        return (string) config("desain.ukuran.{$kunci}", $kunci);
    }
}

if (! function_exists('font')) {
    /** Keluarga font token: font('sans') → 'Montserrat, ...'. */
    function font(string $jenis): string
    {
        return (string) config("desain.font.{$jenis}", $jenis);
    }
}

if (! function_exists('url_beranda')) {
    /** URL beranda sesuai bahasa aktif, supaya tautan "Beranda" tidak memental ke bahasa Indonesia. */
    function url_beranda(?string $jangkar = null): string
    {
        $bahasa = app()->getLocale();
        $url = $bahasa === 'id'
            ? route('home')
            : route('home.localized', ['locale' => $bahasa]);

        return $jangkar ? $url.'#'.ltrim($jangkar, '#') : $url;
    }
}

if (! function_exists('tautan_bagian')) {
    /**
     * Tautan ke satu bagian beranda: anchor lokal bila bagian itu ada di halaman yang sedang dibuka
     * (beranda memuat semuanya; halaman arsip hanya bagiannya sendiri), selain itu menuju bagiannya di beranda.
     */
    function tautan_bagian(string $bagian): string
    {
        $adaDiSini = request()->routeIs('home', 'home.localized')
            || ($bagian === 'journeys' && request()->routeIs('journeys.index', 'journeys.index.localized'))
            || ($bagian === 'galeri' && request()->routeIs('gallery.index', 'gallery.index.localized'))
            || ($bagian === 'artikel' && request()->routeIs('articles.index', 'articles.index.localized'));

        return $adaDiSini ? '#'.$bagian : url_beranda($bagian);
    }
}
