{{-- Ikon garis untuk kolom form WhatsApp (gaya seragam: 24px, stroke 1.7, sudut membulat). --}}
@props(['nama'])

@php
    $jalur = [
        'kalender' => '<rect x="3.5" y="5" width="17" height="15.5" rx="3.5"/><path d="M3.5 10h17M8 3v4M16 3v4"/><circle cx="8.5" cy="14.5" r=".9" fill="currentColor" stroke="none"/><circle cx="12" cy="14.5" r=".9" fill="currentColor" stroke="none"/><circle cx="15.5" cy="14.5" r=".9" fill="currentColor" stroke="none"/>',
        'peserta' => '<circle cx="9" cy="8.5" r="3.3"/><path d="M3 19.5c.6-3.3 3-5.2 6-5.2s5.4 1.9 6 5.2"/><path d="M15.5 5.6a3.1 3.1 0 0 1 0 5.8M17.6 14.6c1.8.7 3 2.4 3.4 4.9"/>',
        'lokasi' => '<path d="M12 21s-6.5-5.6-6.5-11a6.5 6.5 0 0 1 13 0c0 5.4-6.5 11-6.5 11Z"/><circle cx="12" cy="10" r="2.5"/>',
        'jam' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
        'nama' => '<rect x="3.5" y="5" width="17" height="14" rx="3"/><circle cx="9" cy="11" r="2.2"/><path d="M5.8 16.5c.5-1.8 1.7-2.8 3.2-2.8s2.7 1 3.2 2.8M14.5 10h3.5M14.5 13.5h2.5"/>',
        'catatan' => '<path d="M4 19.5h4.5L19 9a2.1 2.1 0 0 0-3-3L5.5 16.5 4 19.5Z"/><path d="M14 8l2.9 2.9M13 19.5h7"/>',
    ];
@endphp

<svg {{ $attributes->class(['h-[22px] w-[22px] shrink-0']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">{!! $jalur[$nama] ?? '' !!}</svg>
