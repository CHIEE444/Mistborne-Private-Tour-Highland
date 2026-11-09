@props(['jenis' => 'publik'])

{{-- Ikon tab browser. Publik: ubin hijau hutan dengan gunung putih (simbol logo navbar).
     Admin/panel internal (admin, guide, login, cetak): palet terbalik + matahari hijau agar tab-nya mudah dibedakan.
     Berkas dibuat dari SVG yang sama: favicon{-admin}.svg, .ico (16/32/48 px), dan apple-touch-icon{-admin}.png. --}}
@php
    $akhiran = $jenis === 'admin' ? '-admin' : '';
@endphp
<link rel="icon" href="/favicon{{ $akhiran }}.ico" sizes="48x48" />
<link rel="icon" href="/favicon{{ $akhiran }}.svg" type="image/svg+xml" />
<link rel="apple-touch-icon" href="/apple-touch-icon{{ $akhiran }}.png" />
