{{-- Kartu perjalanan (bersih): foto 4:3 hanya dengan kapsul kaca durasi; di bawahnya nama, lokasi, kotak panah, garis
     tipis, dan harga. Seluruh kartu menuju halaman detail. Dipakai di beranda dan halaman Semua Perjalanan. --}}
@props(['journey', 'number' => null])

<article data-journey-card {{ isset($journey['region']) ? 'data-region="'.$journey['region'].'"' : '' }} class="jc-card relative flex h-full flex-col">
    <a
        href="{{ route('journeys.show', ['slug' => $journey['slug']]) }}"
        class="group flex h-full flex-col focus-visible:outline-none"
        aria-label="{{ __('Lihat detail :name', ['name' => $journey['name']]) }}"
    >
        <div class="relative aspect-[4/3] overflow-hidden rounded-[22px] bg-[#12231A] ring-offset-4 group-focus-visible:ring-2 group-focus-visible:ring-[#1C2421]">
            <img
                src="{{ $journey['img'] }}"
                alt="{{ $journey['place'] }}"
                class="jc-card__img text-transparent absolute inset-0 h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-[1.04]"
                loading="lazy"
                decoding="async"
                width="800"
                height="600"
            >
            {{-- Durasi dari data journey (durasi_hari), mis. "3 Hari · 2 Malam"; journey tanpa durasi = "Durasi fleksibel". --}}
            @if (filled($journey['duration'] ?? null))
                <span data-durasi-journey class="pk-kaca absolute start-4 top-4 z-[1] inline-flex items-center rounded-full px-3 py-1.5 text-[12.5px] font-medium leading-none text-white"><bdi>{{ $journey['duration'] }}</bdi></span>
            @endif
        </div>

        <div class="mt-5 flex items-start justify-between gap-4">
            <div class="min-w-0">
                {{-- h3 = nama diri (SEO-24); huruf awal kapital lewat .jc-ttr --}}
                <h3 class="text-[21px] font-semibold leading-snug tracking-[-0.015em] text-[#1C2421]"><bdi>{!! implode(' ', collect(preg_split('/\s+/u', trim($journey['name'])) ?: [])->map(fn ($w) => $w !== '' ? e(mb_substr($w, 0, 1)).'<span class="jc-ttr">'.e(mb_substr($w, 1)).'</span>' : e($w))->all()) !!}</bdi></h3>
                <p class="mt-1 truncate text-[14px] text-[#6B736F]">{{ $journey['place'] }}</p>
            </div>
            <span class="flex h-11 w-11 shrink-0 items-center justify-center bg-[#DDEBD3] text-[#2B2F2D] transition-colors duration-300 group-hover:bg-[#2B2F2D] group-hover:text-white" aria-hidden="true">
                <svg class="h-[18px] w-[18px] rtl:-scale-x-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17 17 7M8 7h9v9"/></svg>
            </span>
        </div>

        <div class="min-h-4 flex-1"></div>
        <p data-journey-price class="whitespace-nowrap border-t border-[#E3E7E2] pt-4 text-[14px] tabular-nums text-[#6B736F]">
            <bdi>@if ($journey['jadwal_fleksibel'] ?? false){{ __('Harga disesuaikan') }}@elseif ($journey['price']){{ __('Mulai dari') }} <span class="text-[16px] font-semibold text-[#1C2421]">{{ $journey['price'] }}</span> {{ __('per booking') }}@else{{ __('Harga sesuai rute') }}@endif</bdi>
        </p>
    </a>
</article>
