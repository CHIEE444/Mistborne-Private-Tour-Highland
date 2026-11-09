@props(['title', 'description', 'image', 'canonical', 'type' => 'website'])

@php
    // Tag SEO dirender server agar terbaca crawler dan pratinjau WhatsApp (9 bahasa).
    $locales = [
        'id' => ['lang' => 'id',      'og' => 'id_ID', 'url' => url('/')],
        'en' => ['lang' => 'en',      'og' => 'en_US', 'url' => url('/en')],
        'ms' => ['lang' => 'ms',      'og' => 'ms_MY', 'url' => url('/ms')],
        'zh' => ['lang' => 'zh-Hans', 'og' => 'zh_CN', 'url' => url('/zh')],
        'ja' => ['lang' => 'ja',      'og' => 'ja_JP', 'url' => url('/ja')],
        'ko' => ['lang' => 'ko',      'og' => 'ko_KR', 'url' => url('/ko')],
        'es' => ['lang' => 'es',      'og' => 'es_ES', 'url' => url('/es')],
        'fr' => ['lang' => 'fr',      'og' => 'fr_FR', 'url' => url('/fr')],
        'ar' => ['lang' => 'ar',      'og' => 'ar_AR', 'url' => url('/ar')],
    ];
    $current = app()->getLocale();
@endphp

<title>{{ $title }}</title>
<meta name="description" content="{{ $description }}" />
<link rel="canonical" href="{{ $canonical }}" />
<meta name="robots" content="index, follow, max-image-preview:large" />
{{-- Hitam = warna footer: Safari memakai theme-color untuk area pantulan (overscroll), jadi tidak ada latar hijau di bawah footer. --}}
<meta name="theme-color" content="#000000" />

{{-- hreflang: semua bahasa + x-default → / --}}
@foreach ($locales as $loc)
    <link rel="alternate" hreflang="{{ $loc['lang'] }}" href="{{ $loc['url'] }}" />
@endforeach
<link rel="alternate" hreflang="x-default" href="{{ url('/') }}" />

{{-- Open Graph --}}
<meta property="og:type" content="{{ $type }}" />
<meta property="og:site_name" content="MISTBORNE — Private Highland Travel" />
<meta property="og:title" content="{{ $title }}" />
<meta property="og:description" content="{{ $description }}" />
<meta property="og:url" content="{{ $canonical }}" />
<meta property="og:image" content="{{ $image }}" />
<meta property="og:image:width" content="1200" />
<meta property="og:image:height" content="630" />
<meta property="og:image:alt" content="{{ __('MISTBORNE — private trip di dataran tinggi') }}" />
<meta property="og:locale" content="{{ $locales[$current]['og'] }}" />
@foreach ($locales as $code => $loc)
    @if ($code !== $current)
        <meta property="og:locale:alternate" content="{{ $loc['og'] }}" />
    @endif
@endforeach

{{-- Twitter --}}
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="{{ $title }}" />
<meta name="twitter:description" content="{{ $description }}" />
<meta name="twitter:image" content="{{ $image }}" />

{{-- Favicon & manifest --}}
<x-bersama.ikon-tab />
<link rel="manifest" href="/site.webmanifest" />
