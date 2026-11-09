{{-- Ikon garis highlight journey sesuai topik (nama dari Perjalanan::ikonHighlight). Inline SVG agar tidak
     bergantung pada subset font ikon. --}}
@props(['nama' => 'kilau'])

@php
    $jalur = [
        'matahari' => '<path d="M12 3v3M4.9 8.9l1.4 1.4M2 16h2M20 16h2M17.7 10.3l1.4-1.4M22 20H2M16 16a4 4 0 0 0-8 0"/>',
        'air-panas' => '<path d="M7 3.5c-1 1.5 1 2.5 0 4M12 3.5c-1 1.5 1 2.5 0 4M17 3.5c-1 1.5 1 2.5 0 4"/><path d="M3 12h18v3a5 5 0 0 1-5 5H8a5 5 0 0 1-5-5Z"/>',
        'sarapan' => '<path d="M3 9h14v5a5 5 0 0 1-5 5H8a5 5 0 0 1-5-5Z"/><path d="M17 11h1.5a2.5 2.5 0 0 1 0 5H17M7 3v3M10 3v3M13 3v3"/>',
        'hotel' => '<path d="M2 20V6M2 16h20M22 20v-7a3 3 0 0 0-3-3h-8v6"/><circle cx="6.5" cy="12" r="2"/>',
        'perahu' => '<path d="M3 15h18l-2.5 5h-13Z"/><path d="M12 3v12M12 4l6 8h-6"/>',
        'satwa' => '<circle cx="6.5" cy="9.5" r="1.6"/><circle cx="12" cy="6.5" r="1.6"/><circle cx="17.5" cy="9.5" r="1.6"/><path d="M12 12c-3 0-5 3.5-5 5.5S9 20.5 12 19.5s5 .5 5-1.5-2-6-5-6Z"/>',
        'belanja' => '<path d="M5 8h14l-1 13H6Z"/><path d="M9 10V6a3 3 0 0 1 6 0v4"/>',
        'museum' => '<path d="M3 21h18M4 10h16M12 3 3 8v2h18V8Z"/><path d="M6 10v8M10 10v8M14 10v8M18 10v8"/>',
        'desa' => '<path d="M3 11 12 3l9 8M5 9.5V21h14V9.5M10 21v-6h4v6"/>',
        'candi' => '<path d="M3 21h18M5 21v-4h14v4M7 17v-4h10v4M9 13V9h6v4M12 9V4M10.5 5.5h3"/>',
        'kawah' => '<path d="m2 20 7-11h6l7 11Z"/><path d="M9.5 6c.6-1.6 2.4-1.6 3 0s2.4 1.6 3 0M8 9l2 2.5 2-2.5 2 2.5 1-2.5"/>',
        'air-terjun' => '<path d="M4 3h16M7.5 3v11M12 3v14M16.5 3v11"/><path d="M2 20c1.7 0 1.7-1.2 3.3-1.2S7 20 8.7 20s1.7-1.2 3.3-1.2 1.7 1.2 3.3 1.2 1.7-1.2 3.4-1.2S20.3 20 22 20"/>',
        'air' => '<path d="M2 8c1.7 0 1.7-1.5 3.3-1.5S7 8 8.7 8s1.7-1.5 3.3-1.5S13.7 8 15.3 8s1.7-1.5 3.4-1.5S20.3 8 22 8M2 13c1.7 0 1.7-1.5 3.3-1.5S7 13 8.7 13s1.7-1.5 3.3-1.5 1.7 1.5 3.3 1.5 1.7-1.5 3.4-1.5S20.3 13 22 13M2 18c1.7 0 1.7-1.5 3.3-1.5S7 18 8.7 18s1.7-1.5 3.3-1.5 1.7 1.5 3.3 1.5 1.7-1.5 3.4-1.5S20.3 18 22 18"/>',
        'pantai' => '<path d="M12 3a8 8 0 0 0-8 8h16a8 8 0 0 0-8-8ZM12 11v6"/><path d="M2 20.5c1.7 0 1.7-1.2 3.3-1.2s1.7 1.2 3.4 1.2 1.6-1.2 3.3-1.2 1.7 1.2 3.3 1.2 1.7-1.2 3.4-1.2 1.6 1.2 3.3 1.2"/>',
        'teh' => '<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.5 19 2c1 2 2 4.2 2 8 0 5.5-4.8 10-10 10Z"/><path d="M2 21c0-3 1.9-5.4 5.1-6 2.4-.5 4.9-2 5.9-3"/>',
        'bunga' => '<circle cx="12" cy="8" r="2.2"/><path d="M12 5.8V3.5M12 10.2v1.3M14.2 8h2.3M7.5 8h2.3M13.6 6.4l1.6-1.6M8.8 11.2l1.6-1.6M13.6 9.6l1.6 1.6M8.8 4.8l1.6 1.6M12 12v9M12 17c-2.2 0-4-1.3-4.8-3.4M12 19c2.2 0 4-1.3 4.8-3.4"/>',
        'savana' => '<path d="M2 21h20M5 21c0-4 1-7 3-9M9 21c0-5-1-9-3-12M12 21c0-6 1-10 3-13M16 21c0-4-1-7-3-9M19 21c0-3 .5-5 2-7"/>',
        'hutan' => '<path d="m12 2-5 7h3l-4 6h12l-4-6h3Z"/><path d="M12 15v7"/>',
        'foto' => '<path d="M4 7h3l2-3h6l2 3h3a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V8a1 1 0 0 1 1-1Z"/><circle cx="12" cy="13" r="3.8"/>',
        'trekking' => '<circle cx="6" cy="19" r="2"/><circle cx="18" cy="5" r="2"/><path d="M8 19h8.5a3.5 3.5 0 0 0 0-7h-9a3.5 3.5 0 0 1 0-7H16"/>',
        'pemandu' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0M16 4.5a3.5 3.5 0 0 1 0 7M18 14a6.5 6.5 0 0 1 3.5 6"/>',
        'rute' => '<path d="M12 21s-6-5.3-6-10a6 6 0 0 1 12 0c0 4.7-6 10-6 10Z"/><circle cx="12" cy="11" r="2.2"/>',
        'udara' => '<path d="M3 8h11a3 3 0 1 0-3-3M3 12h15a3 3 0 1 1-3 3M3 16h7"/>',
        'gunung' => '<path d="m3 20 6-10 4 6 2-3 6 7Z"/>',
        'kilau' => '<path d="m12 3 1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8Z"/><path d="M19 16v4M17 18h4"/>',
    ];
@endphp

<svg {{ $attributes->class(['shrink-0']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $jalur[$nama] ?? $jalur['kilau'] !!}</svg>
