{{-- Tombol "Kembali ke Beranda" untuk semua halaman publik selain Beranda (halaman daftar, detail, booking, dan Rancang Trip Kustom): kapsul putih bergaris tipis dengan
     bulatan panah hijau muda, senada dengan tombol ↗ di kartu. Menaut ke beranda sesuai bahasa aktif. --}}
<a
    href="{{ url_beranda() }}"
    {{ $attributes->class(['group/beranda inline-flex h-11 items-center gap-3 whitespace-nowrap rounded-full border border-[#E3E7E2] bg-white pe-5 ps-1.5 text-[14px] font-semibold text-[#1C2421] transition-colors duration-300 hover:border-[#1C2421] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1D7A56]']) }}
>
    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#DDEBD3] transition-colors duration-300 group-hover/beranda:bg-[#2B2F2D] group-hover/beranda:text-white" aria-hidden="true">
        <svg class="h-4 w-4 transition-transform duration-300 group-hover/beranda:-translate-x-0.5 rtl:-scale-x-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
    </span>
    {{ __('Kembali ke Beranda') }}
</a>
