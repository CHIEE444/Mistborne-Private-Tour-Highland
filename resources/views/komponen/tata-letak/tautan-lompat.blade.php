@php
    // KONSTANTA DESAIN — warna dari config/desain.php (halaman pusat token).
    $hijauTua = warna('hijauTua');
@endphp
{{-- Skip link aksesibilitas: tautan pertama di <body>, tampil saat fokus keyboard --}}
    {{-- Skip link: tampil saat fokus --}}
    <a href="#konten" class="sr-only focus:not-sr-only focus:fixed focus:start-4 focus:top-4 focus:z-[100] focus:rounded-full focus:bg-[#12301F] focus:px-5 focus:py-2.5 focus:text-sm focus:font-semibold focus:text-white focus:shadow-xl">
        {{ __('Lewati ke konten') }}
    </a>