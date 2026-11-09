{{-- HALAMAN ARTIKEL (/artikel) — jurnal editorial senada dengan bagian Artikel di beranda: tab kategori dari artikel yang
     benar-benar terbit, artikel utama melebar (foto kiri, teks kanan), lalu grid kartu bersih untuk artikel lainnya.
     Desain sebelumnya dicadangkan di storage/app/private/cadangan-desain. --}}
@php
    $artikel = \App\Models\Artikel::query()->terbit()->orderByDesc('published_at')->get()
        ->map(fn (\App\Models\Artikel $a): array => $a->kePublik())->values()->all();

    $kategori = collect($artikel)->pluck('category')->filter()->unique()->values()->all();
@endphp
<x-tata-letak.aplikasi>
    <section
        class="scroll-mt-20 bg-white pb-20 pt-28 md:pb-24 md:pt-36"
        id="artikel"
        aria-labelledby="artikel-judul"
        x-data="{
            kat: 'semua',
            kategori: {{ \Illuminate\Support\Js::from(collect($artikel)->pluck('category')->all()) }},
            cocok(i) { return this.kat === 'semua' || this.kategori[i] === this.kat; },
            utama() { return this.kategori.findIndex((_, i) => this.cocok(i)); },
        }"
    >
        <div class="mx-auto max-w-[1320px] px-5 sm:px-8 lg:px-12">
            <x-bersama.tombol-beranda class="mb-10 md:mb-12" />

            <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="text-[15px] text-[#6B736F]">{{ __('Jurnal MISTBORNE') }}</p>
                    <h1 class="mt-3 text-[clamp(40px,5.4vw,76px)] font-semibold leading-[1.0] tracking-[-0.04em] text-[#1C2421]" id="artikel-judul">{{ __('Cerita & tips perjalanan') }}</h1>
                </div>
                @if ($artikel !== [])
                    <p class="text-[15px] tabular-nums text-[#6B736F] md:pb-2">{{ count($artikel) }} {{ __('Artikel') }}</p>
                @endif
            </div>

            @if ($artikel !== [])
                {{-- Tab kategori: teks bergaris bawah dengan jumlah; hanya kategori yang punya artikel --}}
                @if (count($kategori) > 1)
                    <div class="-mx-1 mt-10 flex max-w-full items-center gap-6 overflow-x-auto border-b border-[#E3E7E2] px-1" role="tablist" aria-label="{{ __('Filter artikel') }}">
                        @foreach (['semua' => __('Semua'), ...array_combine($kategori, $kategori)] as $kunci => $label)
                            @php $jumlahKategori = $kunci === 'semua' ? count($artikel) : collect($artikel)->where('category', $kunci)->count(); @endphp
                            <button
                                type="button"
                                role="tab"
                                :aria-selected="kat === @js($kunci)"
                                @click="kat = @js($kunci)"
                                class="-mb-px shrink-0 whitespace-nowrap border-b-2 pb-3 text-[15px] transition-colors duration-300"
                                :class="kat === @js($kunci) ? 'border-[#1C2421] font-semibold text-[#1C2421]' : 'border-transparent text-[#6B736F] hover:text-[#1C2421]'"
                            >{{ $label }}<sup class="ms-1 text-[11px] font-medium tabular-nums text-[#9AA39D]">{{ $jumlahKategori }}</sup></button>
                        @endforeach
                    </div>
                @endif

                {{-- Artikel utama: artikel terbaru yang cocok dengan tab aktif --}}
                <div class="mt-12 grid">
                    @foreach ($artikel as $i => $a)
                        <article class="group relative grid items-center gap-8 [grid-area:1/1] lg:grid-cols-[1.35fr_1fr] lg:gap-14" x-show="utama() === {{ $i }}" x-transition.opacity.duration.400ms @if ($i > 0) x-cloak @endif>
                            <div class="overflow-hidden rounded-[24px]">
                                <img src="{{ $a['img'] }}" alt="{{ $a['alt'] }}" width="1600" height="1000" loading="lazy" decoding="async" class="aspect-[16/10] w-full object-cover transition-transform duration-700 group-hover:scale-[1.03]">
                            </div>
                            <div>
                                <span class="inline-flex rounded-full bg-[#DDEBD3] px-3.5 py-1.5 text-[12.5px] font-medium text-[#1C2421]">{{ __('Artikel pilihan') }}</span>
                                <p class="mt-5 text-[13px] text-[#6B736F]">{{ $a['category'] }} · {{ $a['date'] }} · {{ $a['read'] }}</p>
                                <h2 class="mt-2 text-[clamp(26px,2.8vw,40px)] font-semibold leading-[1.1] tracking-[-0.03em] text-[#1C2421]">
                                    <a href="{{ $a['url'] }}" class="after:absolute after:inset-0 group-hover:underline group-hover:decoration-[#C9D6C1] group-hover:underline-offset-4">{{ $a['title'] }}</a>
                                </h2>
                                @if ($a['excerpt'])
                                    <p class="mt-4 line-clamp-4 text-[15px] leading-relaxed text-[#6B736F]">{{ $a['excerpt'] }}</p>
                                @endif
                                <div class="mt-7 flex items-center justify-between gap-4 border-t border-[#E3E7E2] pt-5">
                                    <p class="truncate text-[14px] font-medium text-[#1C2421]">{{ $a['author'] }}</p>
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[12px] bg-[#DDEBD3] text-[#1C2421] transition-colors duration-300 group-hover:bg-[#2B2F2D] group-hover:text-white" aria-hidden="true"><svg class="h-[17px] w-[17px] rtl:-scale-x-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17 17 7M8 7h9v9"/></svg></span>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                {{-- Artikel lainnya yang cocok: kartu bersih (foto, meta, judul, ringkasan) --}}
                <div class="mt-16 grid gap-x-6 gap-y-12 border-t border-[#E3E7E2] pt-12 sm:grid-cols-2 lg:grid-cols-3" x-show="kategori.some((_, i) => cocok(i) && i !== utama())" @if (count($artikel) < 2) x-cloak @endif>
                    @foreach ($artikel as $i => $a)
                        <article class="group relative flex flex-col" x-show="cocok({{ $i }}) && utama() !== {{ $i }}" @if ($i === 0) x-cloak @endif>
                            <div class="overflow-hidden rounded-[22px]">
                                <img src="{{ $a['img'] }}" alt="{{ $a['alt'] }}" width="800" height="600" loading="lazy" decoding="async" class="aspect-[4/3] w-full object-cover transition-transform duration-700 group-hover:scale-[1.04]">
                            </div>
                            <p class="mt-5 text-[13px] text-[#6B736F]">{{ $a['category'] }} · {{ $a['date'] }}</p>
                            <h3 class="mt-1.5 text-[20px] font-semibold leading-snug tracking-[-0.015em] text-[#1C2421]">
                                <a href="{{ $a['url'] }}" class="after:absolute after:inset-0 group-hover:underline group-hover:decoration-[#C9D6C1] group-hover:underline-offset-4">{{ $a['title'] }}</a>
                            </h3>
                            @if ($a['excerpt'])
                                <p class="mt-2 line-clamp-2 text-[14.5px] leading-relaxed text-[#6B736F]">{{ $a['excerpt'] }}</p>
                            @endif
                            <p class="mt-4 text-[13px] text-[#9AA39D]">{{ $a['read'] }}</p>
                        </article>
                    @endforeach
                </div>
            @else
                <p class="mt-10 border-t border-[#E3E7E2] pt-10 text-[15px] text-[#6B736F]">{{ __('Belum ada artikel yang diterbitkan. Nantikan jurnal perjalanan terbaru dari kami.') }}</p>
            @endif
        </div>
    </section>
</x-tata-letak.aplikasi>
