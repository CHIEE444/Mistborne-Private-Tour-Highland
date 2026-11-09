@php
    // KONSTANTA DESAIN section ini — warna yang dipakai berulang didefinisikan sekali di sini.
    $gelap = warna('hitamLunak');
    $teksGelap = warna('teksGelap');
    $hijauSedang = warna('hijauSedang');
    $jinggaBintang = warna('emas');
@endphp
{{-- TESTIMONI: hanya ulasan yang disetujui admin; foto dan teks berganti halus dengan panah dan titik indikator.
     Bagian ini tidak dirender bila belum ada ulasan. --}}
@php
    // Foto ulasan: Bromo saat fajar untuk semua cerita, kecuali journey yang punya foto destinasinya sendiri.
    $fotoGenerik = [
        'src' => '/images/bromo-semeru-sunrise.webp',
        'alt' => 'Matahari terbit di atas kaldera Bromo dengan Gunung Semeru di kejauhan',
        'w' => 2560,
        'h' => 1707,
    ];

    $fotoJourney = [
        'IJEN VOLCANIC ESCAPE' => [
            'src' => '/images/kawah-ijen.avif',
            'alt' => 'Danau kawah Ijen berwarna toska dengan pohon kering di tepi kawah',
            'w' => 2070,
            'h' => 1381,
        ],
    ];

    $reviews = \App\Models\Ulasan::query()
        ->tampilPublik()
        ->with('pesanan')
        ->latest('created_at')
        ->take(6)
        ->get()
        ->filter(fn ($ulasan) => $ulasan->pesanan !== null)
        ->map(function ($ulasan) use ($fotoJourney, $fotoGenerik) {
            $foto = ($fotoJourney[$ulasan->pesanan->nama_perjalanan] ?? null) ?? $fotoGenerik;

            return [
                'body' => $ulasan->komentar,
                'name' => $ulasan->pesanan->nama_lengkap,
                'loc' => $ulasan->pesanan->nama_perjalanan,
                'rating' => $ulasan->rating,
                'photo' => $foto,
                'verified' => true,
            ];
        })
        ->values()
        ->all();

    // Peta alt key -> string terjemahan locale aktif, dikirim ke Alpine.
    $fotoAlts = collect([$fotoGenerik, ...array_values($fotoJourney)])
        ->mapWithKeys(fn ($f) => [$f['alt'] => __($f['alt'])])
        ->all();
@endphp
@if (count($reviews) > 0)
<section class="py-16 lg:py-24" id="testimoni" aria-labelledby="testimonials-judul">
    <div class="mx-auto max-w-[1200px] px-4 sm:px-8">
        <div x-data="{
            i: 0,
            n: {{ count($reviews) }},
            show: true,
            t: null,
            reviews: {{ Js::from($reviews) }},
            alts: {{ Js::from($fotoAlts) }},
            go(idx) {
                if (idx === this.i) return;
                clearTimeout(this.t);
                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) { this.i = idx; return; }
                this.show = false;
                this.t = setTimeout(() => { this.i = idx; this.show = true; }, 220);
            },
            next() { this.go((this.i + 1) % this.n) },
            prev() { this.go((this.i - 1 + this.n) % this.n) }
        }">
            {{-- Judul: tengah, dengan label kecil --}}
            <div class="mx-auto mb-12 max-w-[640px] text-center">
                <h2 id="testimonials-judul" class="text-[32px] font-bold leading-[1.1] tracking-[-0.01em] sm:text-4xl lg:text-[44px]" style="color: {{ $teksGelap }};">
                    {{ __('Cerita Para Traveler') }}
                </h2>
            </div>

            {{-- Kartu besar: foto kiri (utuh, sudut membulat) + kutipan kanan; tanpa bidang kosong --}}
            <div class="grid overflow-hidden rounded-[32px] border border-[#E3E7DF] bg-white shadow-[0_28px_60px_-36px_rgba(18,48,31,.35)] lg:grid-cols-[1fr_1.05fr]">
                {{-- FOTO: crossfade antar ulasan; chip nama journey melayang --}}
                <div class="relative h-[300px] lg:h-auto lg:min-h-[500px]">
                    @foreach ($reviews as $ri => $r)
                        <img
                            src="{{ asset($r['photo']['src']) }}"
                            alt="{{ __($r['photo']['alt']) }}"
                            width="{{ $r['photo']['w'] }}"
                            height="{{ $r['photo']['h'] }}"
                            loading="lazy"
                            decoding="async"
                            class="absolute inset-0 h-full w-full object-cover object-center transition-opacity duration-700 ease-in-out motion-reduce:transition-none"
                            style="opacity: {{ $ri === 0 ? 1 : 0 }};"
                            :style="{ opacity: i === {{ $ri }} ? 1 : 0 }"
                            :aria-hidden="i === {{ $ri }} ? 'false' : 'true'"
                        />
                    @endforeach
                    <span class="pointer-events-none absolute inset-x-0 bottom-0 h-1/3 bg-gradient-to-t from-black/50 to-transparent" aria-hidden="true"></span>
                    <span class="absolute bottom-5 left-5 inline-flex items-center gap-2 rounded-full bg-white/95 px-4 py-2 text-[12.5px] font-semibold text-[#12301F]">
                        <span class="material-symbols-outlined text-[18px]" aria-hidden="true">location_on</span>
                        <span x-text="reviews[i].loc">{{ $reviews[0]['loc'] }}</span>
                    </span>
                </div>

                {{-- KUTIPAN --}}
                <div class="flex flex-col justify-between p-8 sm:p-10 lg:p-14">
                    <div
                        aria-live="polite"
                        class="transition-opacity duration-300 ease-in-out motion-reduce:transition-none"
                        :class="show ? 'opacity-100' : 'opacity-0'"
                    >
                        <div class="mb-6 flex flex-wrap items-center gap-3">
                            <div class="flex items-center gap-1" style="color: {{ $jinggaBintang }};" role="img" :aria-label="reviews[i].rating + ' ' + '{{ __('dari 5 bintang') }}'">
                                @for ($s = 1; $s <= 5; $s++)
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" :class="reviews[i].rating >= {{ $s }} ? 'opacity-100' : 'opacity-25'"><path d="M12 17.27 18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                                @endfor
                            </div>
                            <span x-show="reviews[i].verified" class="h-3.5 w-px bg-[#D9D9D9]" aria-hidden="true"></span>
                            <span x-show="reviews[i].verified" class="flex items-center gap-1.5 text-[11px] font-medium uppercase tracking-wide text-[#8A8A8A]">
                                <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 48 48" aria-hidden="true">
                                        <path fill="#FFC107" d="M43.611,20.083H42V20H24v8h11.303c-1.649,4.657-6.08,8-11.303,8c-6.627,0-12-5.373-12-12c0-6.627,5.373-12,12-12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C12.955,4,4,12.955,4,24c0,11.045,8.955,20,20,20c11.045,0,20-8.955,20-20C44,22.659,43.862,21.35,43.611,20.083z" />
                                        <path fill="#FF3D00" d="M6.306,14.691l6.571,4.819C14.655,15.108,18.961,12,24,12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C16.318,4,9.656,8.337,6.306,14.691z" />
                                        <path fill="#4CAF50" d="M24,44c5.166,0,9.86-1.977,13.409-5.192l-6.19-5.238C29.211,35.091,26.715,36,24,36c-5.202,0-9.619-3.317-11.283-7.946l-6.522,5.025C9.505,39.556,16.227,44,24,44z" />
                                        <path fill="#1976D2" d="M43.611,20.083H42V20H24v8h11.303c-0.792,2.237-2.231,4.166-4.087,5.571c0.001-0.001,0.002-0.001,0.003-0.002l6.19,5.238C36.971,39.205,44,34,44,24C44,22.659,43.862,21.35,43.611,20.083z" />
                                    </svg>
                                {{ __('Diverifikasi oleh Google Maps') }}
                            </span>
                        </div>
                        <span class="block font-serif text-[64px] font-bold leading-[.6] text-[#12301F]/15" aria-hidden="true">“</span>
                        <blockquote class="mt-2 text-[19px] font-medium leading-[1.65] text-[#1F2A24] lg:text-[22px]">
                            <p x-text="reviews[i].body">{{ $reviews[0]['body'] }}</p>
                        </blockquote>
                    </div>

                    <div class="mt-10 flex items-center justify-between gap-4 border-t border-[#E3E7DF] pt-6">
                        <div class="flex items-center gap-4">
                            <img
                                src="{{ asset($reviews[0]['photo']['src']) }}"
                                :src="reviews[i].photo.src"
                                alt="{{ __($reviews[0]['photo']['alt']) }}"
                                :alt="reviews[i].name"
                                loading="lazy"
                                decoding="async"
                                width="120"
                                height="120"
                                class="h-[56px] w-[56px] shrink-0 rounded-full object-cover"
                            />
                            <div>
                                <p class="text-[13px] font-semibold uppercase tracking-[0.16em]" style="color: {{ $teksGelap }};" x-text="reviews[i].name">{{ $reviews[0]['name'] }}</p>
                                <p class="mt-0.5 text-[13px] text-[#666666]" x-text="reviews[i].loc">{{ $reviews[0]['loc'] }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" class="flex h-11 w-11 items-center justify-center rounded-full border border-[#12301F]/25 text-[#12301F] transition-colors hover:bg-[#12301F] hover:text-white" @click="prev()" aria-label="{{ __('Ulasan sebelumnya') }}">
                                <span class="material-symbols-outlined text-base" aria-hidden="true">arrow_back</span>
                            </button>
                            <button type="button" class="flex h-11 w-11 items-center justify-center rounded-full border border-[#12301F]/25 text-[#12301F] transition-colors hover:bg-[#12301F] hover:text-white" @click="next()" aria-label="{{ __('Ulasan berikutnya') }}">
                                <span class="material-symbols-outlined text-base" aria-hidden="true">arrow_forward</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Dot indikator: dot aktif memanjang jadi pill; klik = pindah ulasan --}}
            <div class="mt-7 flex items-center justify-center gap-2.5" aria-label="{{ __('Pilih ulasan') }}">
                @foreach ($reviews as $ri => $r)
                    <button
                        type="button"
                        class="h-2 rounded-full transition-all duration-300 ease-out motion-reduce:transition-none"
                        style="width: {{ $ri === 0 ? '24px' : '8px' }}; background-color: {{ $ri === 0 ? $gelap : '#CFD3D2' }};"
                        :style="i === {{ $ri }}
                            ? { width: '24px', backgroundColor: '{{ $gelap }}' }
                            : { width: '8px', backgroundColor: '#CFD3D2' }"
                        @click="go({{ $ri }})"
                        aria-label="{{ __('Ulasan :n dari :total', ['n' => $ri + 1, 'total' => count($reviews)]) }}"
                        :aria-pressed="i === {{ $ri }} ? 'true' : 'false'"
                    ></button>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endif