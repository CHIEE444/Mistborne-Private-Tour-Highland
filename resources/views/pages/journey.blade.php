{{-- HALAMAN PERJALANAN (/journey): semua kartu journey dengan filter wilayah dan JSON-LD ItemList. --}}
@php
    $journeys = \App\Models\Perjalanan::query()->with('kendaraan')->journey()->where('template_pesanan_kustom', false)->orderBy('urutan')->get()->map->kePublik()->all();
    $included = array_map(fn (string $butir): string => __($butir), config('mistborne.termasuk'));
    $excluded = array_map(fn (string $butir): string => __($butir), config('mistborne.tidak_termasuk'));
    foreach ($journeys as $i => $journey) { $journeys[$i]['included'] = $included; $journeys[$i]['excluded'] = $excluded; }

    $daftarTab = collect(['all' => __('Semua')])->union(
        collect($journeys)->pluck('region')->filter()->unique()
            ->mapWithKeys(fn ($slug) => [$slug => __(\App\Models\Perjalanan::labelRegion($slug))])
    );

    $journeyLd = [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => __('Semua Perjalanan MISTBORNE'),
        'numberOfItems' => count($journeys),
        'itemListElement' => collect($journeys)->map(fn ($j, $i) => [
            '@type' => 'ListItem',
            'position' => $i + 1,
            'item' => [
                '@type' => 'TouristTrip',
                'name' => $j['name'],
                'url' => route('journeys.show', ['slug' => $j['slug']]),
            ],
        ])->values()->all(),
    ];
@endphp
<x-tata-letak.aplikasi>
    @push('head')
        <x-seo.json-ld :data="$journeyLd" />
    @endpush

    {{-- Senada dengan Perjalanan Populer di beranda: label kecil + judul besar, filter wilayah bergaris bawah dengan
         jumlah, grid kartu bersih, lalu ajakan trip kustom. Desain sebelumnya dicadangkan di storage/app/private/cadangan-desain. --}}
    <section class="scroll-mt-20 bg-white pb-20 pt-28 md:pb-24 md:pt-36" id="journeys" aria-labelledby="journeys-judul" x-data="{ tab: 'all' }">
        <div class="mx-auto max-w-[1320px] px-5 sm:px-8 lg:px-12">
            <x-bersama.tombol-beranda class="mb-10 md:mb-12" />

            <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="text-[15px] text-[#6B736F]">{{ __('Perjalanan') }}</p>
                    <h1 class="mt-3 text-[clamp(40px,5.4vw,76px)] font-semibold leading-[1.0] tracking-[-0.04em] text-[#1C2421]" id="journeys-judul">{{ __('Semua Perjalanan') }}</h1>
                </div>
                <p class="max-w-[420px] text-[15px] leading-relaxed text-[#6B736F] md:pb-2">{{ __('Semua private trip MISTBORNE di satu tempat: satu booking untuk satu kelompok, dengan sopir lokal dan itinerary fleksibel.') }}</p>
            </div>

            {{-- Filter wilayah: dibangun dari wilayah journey di database, jadi semua destinasi tetap terjangkau --}}
            @if ($daftarTab->count() > 1)
                <div class="-mx-1 mt-10 flex max-w-full items-center gap-6 overflow-x-auto border-b border-[#E3E7E2] px-1" role="tablist" aria-label="{{ __('Filter wilayah perjalanan') }}">
                    @foreach ($daftarTab as $kunci => $label)
                        @php $jumlahWilayah = $kunci === 'all' ? count($journeys) : collect($journeys)->where('region', $kunci)->count(); @endphp
                        <button
                            type="button"
                            role="tab"
                            :aria-selected="tab === '{{ $kunci }}'"
                            @click="tab = '{{ $kunci }}'"
                            class="-mb-px shrink-0 whitespace-nowrap border-b-2 pb-3 text-[15px] transition-colors duration-300"
                            :class="tab === '{{ $kunci }}' ? 'border-[#1C2421] font-semibold text-[#1C2421]' : 'border-transparent text-[#6B736F] hover:text-[#1C2421]'"
                        >{{ $label }}<sup class="ms-1 text-[11px] font-medium tabular-nums text-[#9AA39D]">{{ $jumlahWilayah }}</sup></button>
                    @endforeach
                </div>
            @endif

            <div class="mt-12 grid items-stretch gap-x-6 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($journeys as $journey)
                    <div
                        x-show="tab === 'all' || tab === '{{ $journey['region'] }}'"
                        x-transition:enter="transition duration-300 ease-out"
                        x-transition:enter-start="opacity-0 translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        class="h-full"
                    >
                        <x-bersama.kartu-journey :journey="$journey" :number="$loop->index + 1" />
                    </div>
                @endforeach
            </div>

            {{-- Ajakan trip kustom untuk tamu yang belum menemukan rute yang pas --}}
            <div class="mt-20 flex flex-col gap-6 border-t border-[#E3E7E2] pt-10 md:flex-row md:items-center md:justify-between">
                <div class="max-w-[560px]">
                    <h2 class="text-[clamp(24px,2.4vw,32px)] font-semibold leading-tight tracking-[-0.025em] text-[#1C2421]">{{ __('Tidak menemukan rute yang pas?') }}</h2>
                    <p class="mt-2 text-[15px] leading-relaxed text-[#6B736F]">{{ __('Susun rute, durasi, dan titik singgah sesuai keinginan. Harga ditentukan setelah konsultasi dengan admin.') }}</p>
                </div>
                <a class="inline-flex h-12 shrink-0 items-center gap-3 self-start whitespace-nowrap rounded-full bg-[#DDEBD3] pe-1.5 ps-6 text-[14px] font-semibold text-[#1C2421] transition-colors hover:bg-[#CFE3C2] md:self-auto" href="{{ route('rancang-trip-kustom') }}">
                    {{ __('Rancang Trip Kustom') }}
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-[#1C2421] text-white"><svg class="h-[17px] w-[17px] rtl:-scale-x-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17 17 7M8 7h9v9"/></svg></span>
                </a>
            </div>
        </div>
    </section>
</x-tata-letak.aplikasi>
