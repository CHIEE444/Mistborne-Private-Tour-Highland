{{-- NAVBAR (satu file): merek, menu desktop, pengalih bahasa, tombol Masuk, dan drawer mobile.
     State Alpine milik <header>: open = drawer, lang = dropdown bahasa. --}}

@php
    // ── KONSTANTA DESAIN NAVBAR ─────────────────────────────────────────────
    $lebarMaks = ukuran('lebarMaks');          // lebar maksimum batang navbar
    $radiusBatang = ukuran('radiusBatang');         // sudut membulat batang kaca
    $tinggiBatang = ukuran('tinggiBarNavbar');         // tinggi baris dalam batang
    $warnaKrem = warna('krem');         // ikon/teks sekunder di atas kaca
    $warnaHijauMasuk = warna('hijauAksi');   // tombol Masuk
    $warnaHijauMasukHover = warna('hijauAksiTua');
    $warnaKaca = warna('hijauTua');         // dasar lapis kaca gelap (navbar, panel, drawer)

    $ukuran = ukuran('px10');       // kode bendera kecil / label chip

    // Menu menaut ke bagian beranda (anchor lokal bila bagiannya ada di halaman ini); bagian yang kosong tidak ditampilkan.
    $sedangDiBeranda = request()->routeIs('home', 'home.localized');
    $bagianKosong = array_filter([
        ! \App\Models\Artikel::query()->terbit()->exists() ? 'artikel' : null,
        ! \App\Models\Ulasan::query()->tampilPublik()->whereHas('pesanan')->exists() ? 'testimoni' : null,
    ]);
    $menu = collect([
        ['label' => __('Perjalanan'), 'bagian' => 'journeys'],
        ['label' => __('Paket Wisata'), 'bagian' => 'paket'],
        ['label' => __('Galeri'), 'bagian' => 'galeri'],
        ['label' => __('Artikel'), 'bagian' => 'artikel'],
        ['label' => __('Kontak'), 'bagian' => 'kontak'],
        ['label' => __('Ulasan'), 'bagian' => 'testimoni'],
    ])->map(fn (array $butir): array => [...$butir, 'href' => tautan_bagian($butir['bagian'])])
        ->reject(fn (array $butir): bool => in_array($butir['bagian'], $bagianKosong, true)
            && ! ($butir['bagian'] === 'artikel' && request()->routeIs('articles.index', 'articles.index.localized')))
        ->values()
        ->all();

    // ── DAFTAR BAHASA (9 bahasa, ar = rtl) ──────────────────────────────────
    $bahasa = [
        ['kode' => 'id', 'nama' => 'Bahasa Indonesia', 'url' => url('/')],
        ['kode' => 'en', 'nama' => 'English', 'url' => url('/en')],
        ['kode' => 'ms', 'nama' => 'Bahasa Melayu', 'url' => url('/ms')],
        ['kode' => 'zh', 'nama' => '中文 (简体)', 'url' => url('/zh')],
        ['kode' => 'ja', 'nama' => '日本語', 'url' => url('/ja')],
        ['kode' => 'ko', 'nama' => '한국어', 'url' => url('/ko')],
        ['kode' => 'es', 'nama' => 'Español', 'url' => url('/es')],
        ['kode' => 'fr', 'nama' => 'Français', 'url' => url('/fr')],
        ['kode' => 'ar', 'nama' => 'العربية', 'rtl' => true, 'url' => url('/ar')],
    ];
    $bahasaAktif = collect($bahasa)->firstWhere('kode', app()->getLocale());

    // ── FUNGSI: SVG bendera bahasa (inline, rasio 3:2, tanpa id duplikat) ───
    // Bendera Arab sengaja bukan bendera negara: lencana hijau tua + "ع" emas.
    $svgBendera = static function (string $kode): string
    {
        $isi = match ($kode) {
            'id' => '<rect width="24" height="8" fill="#CE1126"/><rect y="8" width="24" height="8" fill="#fff"/>',
            'en' => '<rect width="24" height="16" fill="#012169"/><path d="M0 0 L24 16 M24 0 L0 16" stroke="#fff" stroke-width="3.2"/><path d="M0 0 L24 16 M24 0 L0 16" stroke="#C8102E" stroke-width="1.6"/><path d="M12 0 V16 M0 8 H24" stroke="#fff" stroke-width="5"/><path d="M12 0 V16 M0 8 H24" stroke="#C8102E" stroke-width="3"/>',
            'ms' => '<rect width="24" height="16" fill="#fff"/>'.collect(range(0, 13))->filter(fn ($g) => $g % 2 === 0)->map(fn ($g) => sprintf('<rect y="%.2f" width="24" height="%.2f" fill="#CC0001"/>', $g * (16 / 14), 16 / 14))->implode('').'<rect width="12" height="8.6" fill="#010066"/><path d="M4.9 2.1 A2.4 2.4 0 1 0 4.9 6.4 A2 2 0 1 1 4.9 2.1 Z" fill="#FFCC00"/><circle cx="6" cy="4.3" r="1.1" fill="#FFCC00"/><path d="M9 1.4 L9.7 3.3 L11.7 3.4 L10.1 4.6 L10.7 6.5 L9 5.4 L7.3 6.5 L7.9 4.6 L6.3 3.4 L8.3 3.3 Z" fill="#FFCC00"/>',
            'zh' => '<rect width="24" height="16" fill="#DE2910"/><path d="M4.8 2.2 L5.6 4.5 L8 4.6 L6.2 6.1 L6.8 8.4 L4.8 7 L2.8 8.4 L3.4 6.1 L1.6 4.6 L4 4.5 Z" fill="#FFDE00"/><circle cx="9.6" cy="2.1" r="0.75" fill="#FFDE00"/><circle cx="11.4" cy="4" r="0.75" fill="#FFDE00"/><circle cx="11.4" cy="6.9" r="0.75" fill="#FFDE00"/><circle cx="9.6" cy="8.8" r="0.75" fill="#FFDE00"/>',
            'ja' => '<rect width="24" height="16" fill="#fff"/><circle cx="12" cy="8" r="4.2" fill="#BC002D"/>',
            'ko' => '<rect width="24" height="16" fill="#fff"/><g transform="rotate(-33 12 8)"><path d="M8 8 A4 4 0 0 1 16 8 Z" fill="#CD2E3A"/><path d="M16 8 A4 4 0 0 1 8 8 Z" fill="#0047A0"/><path d="M12 8 A2 2 0 0 1 12 4 A2 2 0 0 1 12 8" fill="#CD2E3A"/><path d="M12 12 A2 2 0 0 1 12 8 A2 2 0 0 1 12 12" fill="#CD2E3A"/><rect x="1" y="3" width="3.6" height="0.55" fill="#111" transform="rotate(33 2.8 3.3)"/><rect x="1" y="4.6" width="3.6" height="0.55" fill="#111" transform="rotate(33 2.8 4.9)"/><rect x="19.4" y="10.9" width="3.6" height="0.55" fill="#111" transform="rotate(33 21.2 11.2)"/><rect x="19.4" y="12.5" width="3.6" height="0.55" fill="#111" transform="rotate(33 21.2 12.8)"/></g>',
            'es' => '<rect width="24" height="16" fill="#AA151B"/><rect y="4" width="24" height="8" fill="#F1BF00"/>',
            'fr' => '<rect width="8" height="16" fill="#002395"/><rect x="8" width="8" height="16" fill="#fff"/><rect x="16" width="8" height="16" fill="#ED2939"/>',
            'ar' => '<rect width="24" height="16" fill="#12301F"/><circle cx="12" cy="8" r="6.6" fill="none" stroke="#C8A24A" stroke-width="0.9"/><text x="12" y="11.4" text-anchor="middle" font-family="&quot;Noto Sans Arabic&quot;,&quot;Geeza Pro&quot;,&quot;Segoe UI&quot;,Tahoma,sans-serif" font-size="9.5" fill="#C8A24A">ع</text>',
            default => '<rect width="24" height="16" fill="#486553"/>',
        };

        return '<svg width="24" height="16" viewBox="0 0 24 16" role="img" aria-hidden="true" focusable="false" data-flag="'.$kode.'" style="border-radius:3px;overflow:hidden;" class="rounded-[3px] ring-1 ring-white/20 shrink-0">'.$isi.'</svg>';
    };

@endphp

<header
    data-component="layout.navbar"
    {{-- kaca 0–1: di beranda latar kaca muncul berangsur mengikuti scroll sepanjang hero (tanpa loncatan); halaman lain langsung 1. --}}
    x-data="{
        terbuka: false,
        bahasa: false,
        diBeranda: {{ $sedangDiBeranda ? 'true' : 'false' }},
        kaca: {{ $sedangDiBeranda ? 0 : 1 }},
        hitungKaca() {
            if (! this.diBeranda) { this.kaca = 1; return; }
            const tinggi = document.querySelector('[data-hero]')?.offsetHeight || 800;
            this.kaca = Math.min(1, Math.max(0, (window.scrollY - tinggi * 0.25) / (tinggi * 0.45)));
        },
    }"
    x-init="hitungKaca()"
    @keydown.escape.window="terbuka = false; bahasa = false"
    @scroll.window.passive="hitungKaca()"
    @resize.window.passive="hitungKaca()"
    class="fixed inset-x-0 top-0 z-50"
>
    {{-- Progress bar scroll (scaleX via JS, passive + rAF) --}}
    <div data-scroll-progress class="pointer-events-none absolute inset-x-0 top-0 z-10 h-0.5 origin-left scale-x-0 bg-white rtl:origin-right"></div>

    {{-- FLOATING GLASS: wrapper memberi jarak dari viewport; lapis kaca memotong
         dirinya sendiri via border-radius:inherit (glass.css) --}}
    <div class="px-4 pt-3 md:px-8 md:pt-4">
        <div class="relative mx-auto rounded-[24px]" style="max-width: {{ $lebarMaks }}; border-radius: {{ $radiusBatang }};">
            {{-- Lapis kaca: SATU kondisi saja — hijau tua transparan selalu tampil --}}
            <div
                aria-hidden="true"
                data-nav-glass="scrolled"
                class="navbar-glass navbar-glass-scrolled navbar-glass-pudar absolute inset-0"
                :style="{ opacity: terbuka ? 1 : kaca }"
            ></div>

            {{-- Grid 3 kolom: logo | menu | aksi — menu benar-benar terpusat --}}
            <div class="relative grid w-full items-center gap-x-5 px-4 md:px-6 xl:gap-x-6" style="height: {{ $tinggiBatang }}; grid-template-columns: 1fr auto 1fr;">

                {{-- ══ BRAND: logo MISTBORNE + tagline (logo selalu menuju Beranda) ══
                     Semua halaman publik selain Beranda juga punya tombol "Kembali ke Beranda" di atas kontennya. --}}
                <div class="flex min-w-0 items-center gap-3 justify-self-start">
                    <a class="group flex shrink-0 items-center space-x-3" href="{{ url_beranda('beranda') }}">
                        <span class="material-symbols-outlined text-2xl text-white transition-transform group-hover:scale-105" aria-hidden="true">landscape</span>
                        <div class="flex flex-col">
                            <span class="font-serif text-xl font-bold leading-none tracking-wider text-white drop-shadow-sm"><bdi>MISTBORNE</bdi></span>
                            <span class="mt-0.5 text-[9px] font-semibold uppercase tracking-[0.2em] text-white/75 drop-shadow-sm">{{ __('Private Highland Travel') }}</span>
                        </div>
                    </a>
                </div>

                {{-- ══ MENU DESKTOP (tampil mulai xl; nav-active diperbarui scrollspy) ══ --}}
                <nav data-component="layout.navbar.menu-desktop" class="hidden min-w-0 items-center gap-7 justify-self-center xl:flex xl:gap-8" aria-label="{{ __('Navigasi utama') }}">
                    @if ($sedangDiBeranda)
                        <a aria-current="page" class="nav-link nav-active whitespace-nowrap py-1 text-xs font-medium text-white drop-shadow-sm lg:text-sm" href="{{ url_beranda('beranda') }}">{{ __('Beranda') }}</a>
                    @endif
                    @foreach ($menu as $butir)
                        <a class="nav-link whitespace-nowrap py-1 text-xs font-medium text-white/90 drop-shadow-sm transition-colors hover:text-white lg:text-sm" href="{{ $butir['href'] }}">{{ $butir['label'] }}</a>
                    @endforeach
                </nav>

                {{-- ══ AKSI: pengalih bahasa + Masuk + hamburger ══ --}}
                <div class="col-start-3 flex shrink-0 items-center justify-self-end space-x-2.5">
                    {{-- Ajakan pesan (hanya di beranda, layar lebar): kapsul kaca dengan lingkaran panah putih --}}
                    @if ($sedangDiBeranda)
                        <a href="{{ tautan_bagian('journeys') }}" class="nav-icon-glass hidden items-center gap-3 whitespace-nowrap rounded-full py-1.5 pe-1.5 ps-5 text-sm font-medium text-white xl:inline-flex">
                            {{ __('Pesan Sekarang') }}
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-[#12301F]"><svg class="h-[17px] w-[17px] rtl:-scale-x-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17 17 7M8 7h9v9"/></svg></span>
                        </a>
                    @endif
                    {{-- Pengalih bahasa (desktop): tombol bendera aktif + kode + chevron --}}
                    <div class="relative hidden sm:block">
                        <button
                            class="nav-icon-glass relative inline-flex items-center gap-1.5 rounded-full px-3 py-2 text-white transition-colors"
                            type="button"
                            @click="bahasa = !bahasa"
                            @keydown.arrow-down.prevent="bahasa = true; $nextTick(() => pindahFokusMenu(document.getElementById('lang-menu'), 1))"
                            :aria-expanded="bahasa"
                            aria-haspopup="true"
                            aria-controls="lang-menu"
                            aria-label="{{ __('Pilih bahasa') }}"
                        >
                            {!! ($svgBendera)($bahasaAktif['kode']) !!}
                            <span class="text-xs font-semibold tracking-wider">{{ strtoupper($bahasaAktif['kode']) }}</span>
                            <span class="material-symbols-outlined text-xs text-white/80" aria-hidden="true">keyboard_arrow_down</span>
                        </button>
                        {{-- Panel bahasa: kaca gelap (tetap di LUAR elemen ber-backdrop-* leluhur — NAV-20) --}}
                        <div
                            id="lang-menu"
                            data-lang-menu
                            x-show="bahasa"
                            x-cloak
                            x-transition:enter="transition duration-200 ease-out"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition duration-150 ease-in"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            x-origin="top end"
                            @click.outside="bahasa = false"
                            @keydown.arrow-down.prevent="pindahFokusMenu($el, 1)"
                            @keydown.arrow-up.prevent="pindahFokusMenu($el, -1)"
                            class="kaca-bahasa absolute end-0 mt-2 max-h-[min(70vh,28rem)] max-w-[calc(100vw-2rem)] origin-top-end overflow-y-auto rounded-[20px] p-1.5"
                            role="menu"
                        >
                            @foreach ($bahasa as $b)
                                <a
                                    href="{{ $b['url'] }}"
                                    hreflang="{{ $b['kode'] }}"
                                    lang="{{ $b['kode'] }}"
                                    role="menuitem"
                                    class="flex w-full items-center justify-between gap-3 whitespace-nowrap rounded-[14px] px-3.5 py-2.5 text-xs transition-colors {{ $b['kode'] === $bahasaAktif['kode'] ? 'bg-white/20 font-semibold text-white' : 'font-medium text-white/80 hover:bg-white/10 hover:text-white' }}"
                                    @if ($b['kode'] === $bahasaAktif['kode']) aria-current="true" @endif
                                >
                                    {{-- dir="rtl" hanya di <bdi> nama bahasa agar baris Arab tetap sejajar dengan bahasa lain. --}}
                                    <span class="flex items-center gap-3">
                                        {!! ($svgBendera)($b['kode']) !!}
                                        <bdi class="text-sm">{{ $b['nama'] }}</bdi>
                                        <span class="text-[10px] uppercase tracking-wider text-white/50">{{ strtoupper($b['kode']) }}</span>
                                    </span>
                                    @if ($b['kode'] === $bahasaAktif['kode'])
                                        <span class="material-symbols-outlined text-sm text-white" aria-hidden="true">check</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>

                    {{-- Hamburger (mobile) --}}
                    <button
                        class="nav-icon-glass relative rounded-xl p-1.5 text-white transition-colors xl:hidden"
                        type="button"
                        @click="terbuka = !terbuka"
                        :aria-expanded="terbuka"
                        aria-controls="mobile-drawer"
                        aria-label="{{ __('Buka menu') }}"
                    >
                        <span class="material-symbols-outlined text-2xl" x-text="terbuka ? 'close' : 'menu'" aria-hidden="true">menu</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══ DRAWER MOBILE: overlay fade + panel slide dari sisi akhir, item stagger.
         Semua anak <nav> memakai .drawer-item dan tetap ANAK LANGSUNG (animasi
         stagger motion.css memakai :nth-child) — jangan tambah pembungkus div. --}}
    <div class="xl:hidden">
        <div
            x-show="terbuka"
            x-cloak
            x-transition:enter="transition-opacity duration-300 ease-out"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity duration-200 ease-in"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[60] bg-black/50"
            @click="terbuka = false"
            aria-hidden="true"
        ></div>
        <aside
            id="mobile-drawer"
            x-show="terbuka"
            x-cloak
            x-transition:enter="transition-transform duration-300 ease-out"
            x-transition:enter-start="translate-x-full rtl:-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition-transform duration-200 ease-in"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full rtl:-translate-x-full"
            class="drawer fixed end-0 top-0 z-[70] h-full w-72 overflow-y-auto border-s border-white/15 shadow-2xl backdrop-blur-2xl"
            :class="terbuka ? 'drawer-open' : ''"
            style="background-color: {{ $warnaKaca }}CC;"
            role="dialog"
            aria-modal="true"
            aria-label="{{ __('Menu navigasi') }}"
        >
            <nav class="flex flex-col p-4" aria-label="{{ __('Navigasi mobile') }}">
                <div class="drawer-item flex items-center space-x-2 pb-3">
                    <span class="material-symbols-outlined text-2xl text-white" aria-hidden="true">landscape</span>
                    <span class="font-serif text-lg font-bold tracking-wider text-white"><bdi>MISTBORNE</bdi></span>
                </div>
                <a class="drawer-item nav-link {{ $sedangDiBeranda ? 'nav-active' : '' }} rounded-lg px-3 py-2.5 text-sm font-semibold text-white" href="{{ url_beranda('beranda') }}" @click="terbuka = false">{{ __('Beranda') }}</a>
                @foreach ($menu as $butir)
                    <a class="drawer-item rounded-lg px-3 py-2.5 text-sm font-medium text-white/90 transition-colors hover:bg-white/10 hover:text-white" href="{{ $butir['href'] }}" @click="terbuka = false">{{ $butir['label'] }}</a>
                @endforeach
                {{-- Kartu Booking: menuju bagian booking di beranda. --}}
                    <a
                        class="drawer-item mt-3 flex items-center gap-3 rounded-2xl border px-4 py-3.5 transition-colors hover:bg-white/10"
                        style="border-color: rgba(255,255,255,.4); background-color: rgba(255,255,255,.1);"
                        href="{{ tautan_bagian('booking') }}"
                        @click="terbuka = false"
                    >
                        <span class="material-symbols-outlined text-2xl text-white" aria-hidden="true">event_available</span>
                        <span class="flex min-w-0 flex-col">
                            <span class="text-sm font-semibold text-white">{{ __('Pesan Perjalanan') }}</span>
                            <span class="text-xs text-white/70">{{ __('Cek ketersediaan & Pesan langsung') }}</span>
                        </span>
                        <span class="material-symbols-outlined ms-auto shrink-0 text-lg text-white/60 rtl:-scale-x-100" aria-hidden="true">arrow_forward</span>
                    </a>
                {{-- Chip bahasa dengan bendera, 3 per baris --}}
                <div class="drawer-item mt-5 border-t border-white/10 pt-4">
                    <span class="mb-2 block text-[10px] font-semibold uppercase tracking-wider text-white/70">{{ __('Bahasa') }}</span>
                    <div class="grid grid-cols-3 gap-2">
                        @foreach ($bahasa as $b)
                            <a
                                href="{{ $b['url'] }}"
                                hreflang="{{ $b['kode'] }}"
                                lang="{{ $b['kode'] }}"
                                class="flex flex-col items-center gap-1 rounded-lg border px-2 py-2 text-xs transition-colors {{ $b['kode'] === $bahasaAktif['kode'] ? 'font-semibold text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}"
                                @if ($b['kode'] === $bahasaAktif['kode'])
                                    aria-current="true"
                                    style="border-color: #ffffff; background-color: rgba(255,255,255,.2);"
                                @else
                                    style="border-color: rgba(255,255,255,0.2);"
                                @endif
                            >
                                {!! ($svgBendera)($b['kode']) !!}
                                <span class="text-[10px] tracking-wider">{{ strtoupper($b['kode']) }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </nav>
        </aside>
    </div>
</header>
