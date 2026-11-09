@props(['kunci'])

{{-- Ikon garis untuk fasilitas hotel. SVG sebaris karena font ikon situs hanya memuat ikon yang sudah terpakai. --}}
<svg {{ $attributes->merge(['class' => 'h-5 w-5', 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.7', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round', 'aria-hidden' => 'true']) }}>
    @switch($kunci)
        @case('sarapan')
            <path d="M12 3v2M4.9 6.9l1.4 1.4M19.1 6.9l-1.4 1.4M2 17h2M20 17h2M2 21h20M16 17a4 4 0 0 0-8 0" />
            @break
        @case('ac')
            <path d="M12 2v20M3.3 7l17.4 10M3.3 17 20.7 7M9 4l3 2 3-2M9 20l3-2 3 2" />
            @break
        @case('air_panas')
            <path d="M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5S12.5 5 12 2.5C11.5 5 10 7.9 8 9.5S5 13 5 15a7 7 0 0 0 7 7Z" />
            @break
        @case('wifi')
            <path d="M2 8.8a15 15 0 0 1 20 0M5 12.9a10 10 0 0 1 14 0M8.5 16.4a5 5 0 0 1 7 0M12 20h.01" />
            @break
        @case('parkir')
            <rect x="3" y="3" width="18" height="18" rx="3" /><path d="M9 17V7h4a3 3 0 0 1 0 6H9" />
            @break
        @case('restoran')
            <path d="M3 2v7a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2V2M6 2v20M20 15V2a5 5 0 0 0-5 5v6a2 2 0 0 0 2 2h3Zm0 0v7" />
            @break
        @case('kolam_renang')
            <path d="M8 13V5a2 2 0 0 1 4 0M14 13V5a2 2 0 0 1 4 0M8 9h6M2 17c2 0 2-1.5 4-1.5s2 1.5 4 1.5 2-1.5 4-1.5 2 1.5 4 1.5 2-1.5 4-1.5M2 21c2 0 2-1.5 4-1.5s2 1.5 4 1.5 2-1.5 4-1.5 2 1.5 4 1.5 2-1.5 4-1.5" />
            @break
        @case('antar_jemput')
            <path d="M5 17H3v-5l2-5h10l3 5h3v5h-2M9.5 17h5" /><circle cx="7.5" cy="17" r="2" /><circle cx="16.5" cy="17" r="2" />
            @break
        @case('spa')
            <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.5 19 2c1 2 2 4.2 2 8 0 5.5-4.8 10-10 10ZM2 21c0-3 1.9-5.4 5.1-6C9.5 14.5 12 13 13 12" />
            @break
        @case('pusat_kebugaran')
            <path d="m6.5 6.5 11 11M21 21l-1-1M3 3l1 1M18 22l4-4M2 6l4-4M3 10l7-7M14 21l7-7" />
            @break
        @case('kafe_bar')
            <path d="M10 2v2M14 2v2M6 2v2M16 8h1a4 4 0 1 1 0 8h-1M3 8h13v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4Z" />
            @break
        @case('room_service')
            <path d="M3 20a1 1 0 0 1-1-1v-1a1 1 0 0 1 1-1h18a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1ZM20 16a8 8 0 1 0-16 0M12 4v4M10 4h4" />
            @break
        @case('resepsionis_24')
            <circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" />
            @break
        @case('lift')
            <rect x="4" y="3" width="16" height="18" rx="2" /><path d="m9 10 2-2 2 2M9 14l2 2 2-2" />
            @break
        @case('ruang_pertemuan')
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M22 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8" />
            @break
        @default
            <path d="m5 12 5 5L20 7" />
    @endswitch
</svg>
