{{-- ARTIKEL (#artikel) — jurnal editorial: artikel utama besar di kiri, daftar artikel bernomor bergaris pemisah di kanan.
     Tab kategori dibuat dari kategori artikel yang benar-benar terbit dan menyaring isinya (Alpine). Disembunyikan bila
     belum ada artikel terbit. Desain sebelumnya dicadangkan di storage/app/private/cadangan-desain. --}}
@php
    $artikel = \App\Models\Artikel::query()->terbit()->orderByDesc('published_at')->take(6)->get()
        ->map(fn (\App\Models\Artikel $a): array => $a->kePublik())->values()->all();

    $kategori = collect($artikel)->pluck('category')->filter()->unique()->values()->all();
@endphp
@if ($artikel !== [])
<section
    class="scroll-mt-20 bg-white py-20 md:py-24"
    id="artikel"
    aria-labelledby="artikel-judul"
    x-data="{
        kat: 'semua',
        kategori: {{ \Illuminate\Support\Js::from(collect($artikel)->pluck('category')->all()) }},
        cocok(i) { return this.kat === 'semua' || this.kategori[i] === this.kat; },
        utama() { return this.kategori.findIndex((_, i) => this.cocok(i)); },
        nomor(i) { let n = 0; for (let j = 0; j <= i; j++) { if (this.cocok(j) && j !== this.utama()) { n++; } } return String(n).padStart(2, '0'); },
    }"
>
    <div class="mx-auto max-w-[1320px] px-5 sm:px-8 lg:px-12">
        {{-- Kepala: label + judul (kiri), tautan semua artikel (kanan) --}}
        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between" data-reveal="up">
            <div>
                <p class="text-[15px] text-[#6B736F]">{{ __('Jurnal MISTBORNE') }}</p>
                <h2 class="mt-3 text-[clamp(36px,4.4vw,60px)] font-semibold leading-[1.02] tracking-[-0.035em] text-[#1C2421]" id="artikel-judul">{{ __('Cerita & tips perjalanan') }}</h2>
            </div>
            <a class="inline-flex shrink-0 items-center gap-2 self-start whitespace-nowrap border-b border-[#1C2421] pb-1 text-[14px] font-semibold text-[#1C2421] sm:mb-2 sm:self-auto" href="{{ route('articles.index', app()->getLocale() === 'id' ? null : app()->getLocale()) }}">
                {{ __('Lihat semua artikel') }} <span aria-hidden="true" class="rtl:-scale-x-100">→</span>
            </a>
        </div>

        {{-- Tab kategori: teks bergaris bawah; hanya kategori yang punya artikel --}}
        @if (count($kategori) > 1)
            <div class="-mx-1 mt-8 flex max-w-full items-center gap-6 overflow-x-auto px-1 pb-1" role="tablist" aria-label="{{ __('Filter artikel') }}">
                @foreach (['semua' => __('Semua'), ...array_combine($kategori, $kategori)] as $kunci => $label)
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="kat === @js($kunci)"
                        @click="kat = @js($kunci)"
                        class="shrink-0 whitespace-nowrap border-b-2 pb-1.5 text-[15px] transition-colors duration-300"
                        :class="kat === @js($kunci) ? 'border-[#1C2421] font-semibold text-[#1C2421]' : 'border-transparent text-[#6B736F] hover:text-[#1C2421]'"
                    >{{ $label }}</button>
                @endforeach
            </div>
        @endif

        <div class="mt-10 grid grid-cols-1 gap-10 lg:grid-cols-[1.25fr_1fr] lg:items-start lg:gap-14">
            {{-- Artikel utama: artikel pertama yang cocok dengan tab aktif --}}
            <div class="grid">
                @foreach ($artikel as $i => $a)
                    <article class="relative [grid-area:1/1]" x-show="utama() === {{ $i }}" x-transition.opacity.duration.400ms @if ($i > 0) x-cloak @endif data-reveal="up">
                        <div class="overflow-hidden rounded-[24px]">
                            <img src="{{ $a['img'] }}" alt="{{ $a['alt'] }}" width="1600" height="1000" loading="lazy" decoding="async" class="aspect-[16/10] w-full object-cover transition-transform duration-700 hover:scale-[1.03]">
                        </div>
                        <p class="mt-6 text-[13px] text-[#6B736F]">{{ $a['category'] }} · {{ $a['date'] }} · {{ $a['read'] }}</p>
                        <h3 class="mt-2 max-w-[640px] text-[clamp(24px,2.4vw,32px)] font-semibold leading-tight tracking-[-0.025em] text-[#1C2421]">
                            <a href="{{ $a['url'] }}" class="after:absolute after:inset-0 hover:underline hover:decoration-[#C9D6C1] hover:underline-offset-4">{{ $a['title'] }}</a>
                        </h3>
                        @if ($a['excerpt'])
                            <p class="mt-3 max-w-[580px] text-[15px] leading-relaxed text-[#6B736F]">{{ $a['excerpt'] }}</p>
                        @endif
                    </article>
                @endforeach
            </div>

            {{-- Daftar artikel lain yang cocok: bernomor, bergaris pemisah, foto kecil di kanan --}}
            <div class="border-b border-[#E3E7E2]" x-show="kategori.some((_, i) => cocok(i) && i !== utama())" @if (count($artikel) < 2) x-cloak @endif>
                @foreach ($artikel as $i => $a)
                    <article class="group relative flex items-center gap-5 border-t border-[#E3E7E2] py-6" x-show="cocok({{ $i }}) && utama() !== {{ $i }}" @if ($i === 0) x-cloak @endif data-reveal="up" data-reveal-delay="{{ min($i * 80, 320) }}">
                        <span class="w-6 shrink-0 text-[13px] tabular-nums text-[#9AA39D]" x-text="nomor({{ $i }})" aria-hidden="true">{{ str_pad((string) max(1, $i), 2, '0', STR_PAD_LEFT) }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="text-[12.5px] text-[#6B736F]">{{ $a['category'] }} · {{ $a['date'] }}</p>
                            <h3 class="mt-1 text-[18px] font-semibold leading-snug tracking-[-0.01em] text-[#1C2421]">
                                <a href="{{ $a['url'] }}" class="after:absolute after:inset-0 group-hover:underline group-hover:decoration-[#C9D6C1] group-hover:underline-offset-4">{{ $a['title'] }}</a>
                            </h3>
                        </div>
                        <img src="{{ $a['img'] }}" alt="{{ $a['alt'] }}" width="192" height="160" loading="lazy" decoding="async" class="h-20 w-24 shrink-0 rounded-[14px] object-cover">
                    </article>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endif
