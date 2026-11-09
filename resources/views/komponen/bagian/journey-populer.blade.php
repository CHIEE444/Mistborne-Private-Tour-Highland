{{-- PERJALANAN POPULER: kartu journey dengan tab filter wilayah; journey tanpa wilayah hanya tampil di "Semua". --}}
@props(['journeys'])
@php
    // Tab wilayah dibangun dari wilayah journey yang ada di database, jadi wilayah baru otomatis punya tab.
    $tabs = collect([['key' => 'all', 'label' => __('Semua')]])
        ->concat(
            collect($journeys)->pluck('region')->filter()->unique()
                ->sortBy(fn ($slug) => array_search($slug, array_keys(\App\Models\Perjalanan::daftarRegion())) === false ? 99 : array_search($slug, array_keys(\App\Models\Perjalanan::daftarRegion())))
                ->map(fn ($slug) => ['key' => $slug, 'label' => __(\App\Models\Perjalanan::labelRegion($slug))])
        )
        ->values()->all();
@endphp
{{-- Kepala senada dengan Paket/Tentang Kami/FAQ: label kecil + judul besar; filter wilayah berupa teks bergaris bawah
     dengan jumlah kecil; tombol di bawah grid. --}}
<section class="relative scroll-mt-20 bg-white py-20 md:py-24" id="journeys" aria-labelledby="journeys-judul" x-data="{ tab: 'all' }">
    <div class="mx-auto max-w-[1320px] px-5 sm:px-8 lg:px-12">
        <div data-reveal="up" class="flex flex-col gap-7 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-[15px] text-[#6B736F]">{{ __('Perjalanan') }}</p>
                <h2 class="mt-3 text-[clamp(36px,4.4vw,60px)] font-semibold leading-[1.02] tracking-[-0.035em] text-[#1C2421]" id="journeys-judul">{{ __('Perjalanan Populer') }}</h2>
            </div>
            {{-- Filter wilayah: teks bergaris bawah, jumlah perjalanan per wilayah ditulis kecil --}}
            <div class="-mx-1 flex max-w-full items-center gap-6 overflow-x-auto px-1 pb-1 lg:pb-3" role="tablist" aria-label="{{ __('Filter wilayah perjalanan') }}">
                @foreach ($tabs as $t)
                    @php $jumlahWilayah = $t['key'] === 'all' ? count($journeys) : collect($journeys)->where('region', $t['key'])->count(); @endphp
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="tab === '{{ $t['key'] }}'"
                        @click="tab = '{{ $t['key'] }}'"
                        class="shrink-0 whitespace-nowrap border-b-2 pb-1.5 text-[15px] transition-colors duration-300"
                        :class="tab === '{{ $t['key'] }}' ? 'border-[#1C2421] font-semibold text-[#1C2421]' : 'border-transparent text-[#6B736F] hover:text-[#1C2421]'"
                    >{{ $t['label'] }}<sup class="ms-1 text-[11px] font-medium tabular-nums text-[#9AA39D]">{{ $jumlahWilayah }}</sup></button>
                @endforeach
            </div>
        </div>

        {{-- Grid 3/2/1 kolom; min-height mencegah tata letak melompat saat ganti tab. --}}
        <div class="mt-12 lg:min-h-[480px]">
            <div class="grid items-stretch gap-x-6 gap-y-12 max-lg:grid-cols-2 max-sm:grid-cols-1 lg:grid-cols-3">
                @foreach ($journeys as $journey)
                    <div
                        x-show="tab === 'all' {{ $journey['region'] ? "|| tab === '{$journey['region']}'" : '' }}"
                        x-transition:enter="transition duration-300 ease-out"
                        x-transition:enter-start="opacity-0 translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        data-reveal="up"
                        data-reveal-delay="{{ ($loop->index % 3) * 80 }}"
                        class="h-full"
                    >
                        <x-bersama.kartu-journey :journey="$journey" :number="$loop->index + 1" />
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mt-14 flex flex-wrap justify-center gap-3" data-reveal="up">
            <a class="inline-flex h-12 items-center gap-2 whitespace-nowrap rounded-full border border-[#1C2421] px-6 text-[14px] font-semibold text-[#1C2421] transition-colors hover:bg-[#1C2421] hover:text-white" href="{{ route('journeys.index') }}">
                {{ __('Lihat Semua Perjalanan') }} <span aria-hidden="true" class="rtl:-scale-x-100">→</span>
            </a>
            <a class="inline-flex h-12 items-center whitespace-nowrap rounded-full bg-[#DDEBD3] px-6 text-[14px] font-semibold text-[#1C2421] transition-colors hover:bg-[#CFE3C2]" href="{{ route('rancang-trip-kustom') }}">{{ __('Rancang Trip Kustom') }}</a>
        </div>
    </div>
</section>
