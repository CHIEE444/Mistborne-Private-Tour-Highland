@php
    // Atribut lang/dir per bahasa (zh = zh-Hans; rtl hanya untuk ar).
    $htmlLang = ['id' => 'id', 'en' => 'en', 'ms' => 'ms', 'zh' => 'zh-Hans', 'ja' => 'ja', 'ko' => 'ko', 'es' => 'es', 'fr' => 'fr', 'ar' => 'ar'][app()->getLocale()] ?? 'id';
    $htmlDir = app()->getLocale() === 'ar' ? 'rtl' : 'ltr';
    // Title (≤60) dan description (150–160 karakter) SEO per bahasa.
    $seoTitle = __('MISTBORNE — Private Highland Travel ke Dieng & Wonosobo');
    $seoDescription = __('Private trip eksklusif ke dataran tinggi Dieng, Wonosobo, Kaligua, dan Bromo. Satu booking untuk satu kelompok dengan sopir lokal berpengalaman. Rute fleksibel.');

    // KONSTANTA DESAIN template utama — warna tema dari config/desain.php (halaman pusat token).
    $latarHalaman = warna('latarHalaman');
    $teksUtama = warna('teksUtama');
    $pilihLatar = warna('pilihLatar');   // latar teks terpilih (selection)
    $pilihTeks = warna('hijauPekat');
@endphp
@props([
    'title' => $seoTitle,
    'description' => $seoDescription,
    'image' => asset('images/og-image-dieng.jpg'),
    'canonical' => url()->current(),
    'type' => 'website',
    'footerRapat' => false,
    'footerMenempel' => false,
])

<!DOCTYPE html>
<html lang="{{ $htmlLang }}" dir="{{ $htmlDir }}" class="scroll-smooth bg-black">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    @if (request()->routeIs('home', 'home.localized'))
        {{-- Browser bisa mempertahankan scroll terakhir saat refresh. Beranda harus
             dimulai dari hero jika dimuat ulang tanpa hash anchor. --}}
        <script>
            (() => {
                const navigation = performance.getEntriesByType('navigation')[0];
                if (!navigation || navigation.type !== 'reload' || window.location.hash) return;

                const restorationSebelumnya = 'scrollRestoration' in history
                    ? history.scrollRestoration
                    : null;
                if (restorationSebelumnya !== null) history.scrollRestoration = 'manual';

                window.addEventListener('pageshow', () => {
                    document.documentElement.style.scrollBehavior = 'auto';
                    window.scrollTo(0, 0);
                    requestAnimationFrame(() => {
                        requestAnimationFrame(() => {
                            window.scrollTo(0, 0);
                            document.documentElement.style.removeProperty('scroll-behavior');
                            if (restorationSebelumnya !== null) {
                                history.scrollRestoration = restorationSebelumnya;
                            }
                        });
                    });
                }, { once: true });
            })();
        </script>
    @endif
    {{-- Penanda JS aktif: keadaan awal tersembunyi (reveal) hanya berlaku di bawah html.js, konten aman tanpa JS --}}
    <script>document.documentElement.classList.add('js')</script>
    <x-seo.meta :title="$title" :description="$description" :image="$image" :canonical="$canonical" :type="$type" />
    {{-- @fonts merender @font-face Montserrat dari laravel-vite-plugin. --}}
    @fonts
    {{-- Nomor WhatsApp admin dari pengaturan, diisi sebelum app.js dimuat. --}}
    <script>window.WA_NUMBER = @js(\App\Models\Pengaturan::current()->nomor_whatsapp_admin); window.PERTANYAAN_URL = @js(route('pertanyaan.simpan'))</script>
    <link rel="preload" as="font" type="font/woff2" href="{{ Vite::asset('resources/fonts/material-symbols-outlined.woff2') }}" crossorigin>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="bg-white text-[#14231B] font-sans antialiased selection:bg-[#C8E7D1] selection:text-[#1B2E22]">
    @unless (request()->routeIs('home', 'home.localized'))
        {{-- Tujuan tombol "kembali ke atas" di footer; di beranda tujuannya hero (#beranda). --}}
        <span id="atas-halaman" class="absolute left-0 top-0" aria-hidden="true"></span>
    @endunless
    <x-tata-letak.tautan-lompat />

    <x-tata-letak.navbar />

    <main id="konten" class="w-full relative">
        {{ $slot }}
    </main>

    <x-tata-letak.footer :rapat="$footerRapat" :menempel="$footerMenempel" />
    <x-tata-letak.asisten-perjalanan />
    <x-bersama.toast-wa />
</body>
</html>
