{{-- HERO: foto Bromo penuh layar, judul besar di tengah, paragraf + dua tombol kaca bersentuhan putih di kiri bawah, dan paragraf + kapsul tema di
     kanan bawah. Satu <h1>, foto latar di-preload. Desain sebelumnya (slider journey, kartu ulasan) dicadangkan di
     storage/app/private/cadangan-desain. --}}

@php
    // Tiga ukuran dari foto HD (7008 px): ponsel 1280, desktop 1920, layar retina 2880, supaya tetap tajam tanpa unduhan berlebih.
    $fotoLatar = [
        'img' => asset('images/hero-bromo.jpg'), 'w' => 1920, 'h' => 1280,
        'srcset' => asset('images/hero-bromo-1280.jpg').' 1280w, '.asset('images/hero-bromo.jpg').' 1920w, '.asset('images/hero-bromo-2880.jpg').' 2880w',
        'alt' => __('Gunung Bromo dan Batok diterpa cahaya matahari terbit di atas lautan pasir'),
    ];

    // Kapsul tema di kanan bawah: menaut ke daftar journey.
    $temaHero = [__('Gunung'), __('Pantai'), __('Kawah'), __('Telaga'), __('Savana')];
@endphp
@push('head')
    <link rel="preload" as="image" href="{{ $fotoLatar['img'] }}" imagesrcset="{{ $fotoLatar['srcset'] }}" fetchpriority="high">
@endpush
<section id="beranda" aria-labelledby="beranda-judul" class="relative">
    <div data-hero class="relative flex min-h-[max(100svh,640px)] flex-col overflow-hidden bg-[#1E4A63] lg:min-h-[max(100svh,760px)]">
        {{-- Latar: foto Bromo saat matahari terbit, penuh layar --}}
        <div class="absolute inset-0">
            <img
                alt="{{ $fotoLatar['alt'] }}"
                class="text-transparent h-full w-full object-cover object-[60%_45%] lg:object-[50%_42%]"
                src="{{ $fotoLatar['img'] }}"
                srcset="{{ $fotoLatar['srcset'] }}"
                width="{{ $fotoLatar['w'] }}"
                height="{{ $fotoLatar['h'] }}"
                decoding="async"
                data-hero-slide="0"
                loading="eager" fetchpriority="high"
            />
        </div>

        {{-- Rona: langit dibiarkan bersih untuk judul; bawah digelapkan perlahan untuk tombol dan paragraf --}}
        <div class="pointer-events-none absolute inset-0" style="background:linear-gradient(to bottom,rgba(8,32,48,.42),rgba(8,32,48,.06) 24%,transparent 44%),linear-gradient(to top,rgba(5,18,28,.82),rgba(5,18,28,.4) 32%,transparent 60%)" aria-hidden="true"></div>
        {{-- HP dan tablet: teks menumpuk lebih tinggi, jadi bagian bawah digelapkan lebih kuat agar tetap terbaca --}}
        <div class="pointer-events-none absolute inset-0 xl:hidden" style="background:linear-gradient(to top,rgba(5,18,28,.78),rgba(5,18,28,.5) 40%,transparent 68%)" aria-hidden="true"></div>

        {{-- Judul besar di tengah atas --}}
        <div class="relative z-10 px-5 pt-32 text-center sm:pt-36 lg:pt-[clamp(150px,19vh,210px)]">
            <h1 id="beranda-judul" class="hero-rise mx-auto text-[clamp(46px,12vw,64px)] font-semibold leading-[0.95] tracking-[-0.045em] text-white [text-shadow:0_4px_40px_rgba(6,30,45,.22)] sm:text-[88px] lg:text-[clamp(96px,8.6vw,164px)]">
                <span class="block">{{ __('Sambut Pagi') }}</span>
                <span class="hero-judul-pudar block">{{ __('di Atas Awan.') }}</span>
            </h1>
        </div>

        {{-- Bawah: paragraf + dua tombol kaca bersentuhan putih (kiri) dan paragraf + kapsul tema (kanan, mulai layar xl) --}}
        <div class="relative z-10 mx-auto mt-auto flex w-full max-w-[1440px] flex-col gap-8 px-5 pb-8 pt-14 sm:px-8 sm:pb-12 xl:flex-row xl:items-end xl:justify-between xl:px-12 xl:pb-14">
            <div class="hero-muncul sm:max-w-[520px] xl:max-w-[460px]" style="--d:160ms">
                <p class="text-[15px] leading-relaxed text-white/90 [text-shadow:0_1px_10px_rgba(0,0,0,.4)] sm:text-[17px]">{{ __('Nikmati kebebasan mengeksplorasi keindahan alam tanpa batas dengan layanan penjemputan privat dan rute yang sepenuhnya fleksibel.') }}</p>
                <div class="mt-6 grid gap-3 sm:mt-7 sm:flex sm:flex-wrap sm:items-center">
                <a href="#journeys" class="hero-kaca-putih inline-flex h-[52px] items-center justify-between gap-3 rounded-full pe-2 ps-6 text-[15px] font-semibold text-white sm:h-[54px] sm:justify-start sm:ps-7">
                    {{ __('Lihat Perjalanan') }}
                    <span class="flex h-[38px] w-[38px] items-center justify-center rounded-full bg-white text-[#12301F]"><svg class="h-[18px] w-[18px] rtl:-scale-x-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17 17 7M8 7h9v9"/></svg></span>
                </a>
                <a href="{{ route('rancang-trip-kustom') }}" class="hero-kaca-putih inline-flex h-[52px] items-center justify-center rounded-full px-7 text-[15px] font-semibold text-white sm:h-[54px]">{{ __('Rancang Trip Kustom') }}</a>
                </div>
                {{-- HP dan tablet: kapsul tema langsung di bawah tombol (paragraf kanan disembunyikan agar tidak padat) --}}
                <ul class="mt-5 flex flex-wrap gap-2 xl:hidden" role="list">
                    @foreach ($temaHero as $tema)
                        <li><a href="#journeys" class="hero-kaca inline-flex h-9 items-center rounded-full px-4 text-[13.5px] font-medium text-white">{{ $tema }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div class="hero-muncul hidden max-w-[560px] text-end xl:block" style="--d:300ms">
                <p class="text-[15px] leading-relaxed text-white/95 [text-shadow:0_1px_10px_rgba(0,0,0,.35)] sm:text-[16.5px]">{{ __('Temukan panorama dataran tinggi yang memukau dan kembali dekat dengan alam lewat perjalanan pilihan kami. Nikmati pemandangan luar biasa dan rasakan serunya menjelajah keajaiban alam Jawa.') }}</p>
                <ul class="mt-5 flex flex-wrap justify-end gap-2.5" role="list">
                    @foreach ($temaHero as $tema)
                        <li><a href="#journeys" class="hero-kaca inline-flex h-10 items-center rounded-full px-5 text-[14px] font-medium text-white">{{ $tema }}</a></li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</section>
