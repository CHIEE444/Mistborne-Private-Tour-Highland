@php
    // KONSTANTA DESAIN section ini — dipakai berulang, didefinisikan sekali di sini.
    $hijauTuaTeks = warna('hijauTuaTeks');
    $abuTeks = warna('abuTeks');
@endphp
{{-- ALUR PERJALANAN: carousel lima langkah bernomor (scroll-snap) dengan panah dan titik indikator. --}}
@php
    // Data kartu: urutan perjalanan tamu dari hotel hingga fajar; kartu 3 (index 2) = aktif awal.
    $alurCards = [
        ['no' => '01', 'img' => '/images/jemputdepanhotel.jpg',
         'alt' => __('Pemandu MISTBORNE menyambut tamu di depan lobi hotel'),
         'title' => __('Jemput langsung di hotel'),
         'desc' => __('Driver menunggu di lobi, Anda tinggal duduk dan menikmati.')],
        ['no' => '02', 'img' => '/images/jeep.jpg',
         'alt' => __('Jip melaju di jalur tanah berkabut menuju dataran tinggi'),
         'title' => __('Jalan berkelok di atas kabut'),
         'desc' => __('Kami melaju pelan, Anda menikmati pemandangannya.')],
        ['no' => '03', 'img' => '/images/alur-03-menembus-kabut.webp',
         'alt' => __('Trip privat melaju pelan di jalan pegunungan berkabut tebal'),
         'title' => __('Menembus kabut pegunungan'),
         'desc' => __('Kabut pagi yang dingin, dilewati bersama driver yang hafal jalurnya.')],
        ['no' => '04', 'img' => '/images/jeepbromo.jpg',
         'alt' => __('Jip klasik berhenti di tepi kaldera dengan latar gunung berapi'),
         'title' => __('Jeep di tepi kaldera'),
         'desc' => __('Berhenti di titik yang Anda suka, selama yang Anda mau.')],
        ['no' => '05', 'img' => '/images/bromosunrise.jpg',
         'alt' => __('Matahari terbit di atas kaldera Bromo dan lautan awan'),
         'title' => __('Fajar di atas awan'),
         'desc' => __('Lautan awan pertama yang Anda lihat pagi itu.')],
    ];
@endphp
<section id="alur-perjalanan" aria-labelledby="alur-perjalanan-judul" class="overflow-hidden bg-white py-16 md:py-24">
    <div data-reveal="up" class="mx-auto max-w-[860px] px-6 text-center">
        {{-- Label hijau pelacak lebar --}}
        {{-- Judul dua nada. <p> (bukan h2) karena section ini berada DI ATAS hero —
             kontrak SEO (seo-17: heading pertama harus h1) tetap utuh. --}}
        <p id="alur-perjalanan-judul" class="mt-6 text-balance text-[28px] font-medium leading-[1.2] tracking-tight text-[#14301F] sm:text-[36px] lg:text-[44px]">
            <span class="text-[#14301F]">{{ __('Satu Perjalanan,') }}</span>
            <span class="font-normal text-[#7C8A80]">{{ __('Lima Momen Berharga') }}</span>
        </p>
        <p class="mx-auto mt-6 max-w-[34rem] text-[15px] leading-[1.7] text-[#5F6B63]">
            {{ __('Dari jemputan di depan pintu hotel sampai matahari pertama di atas awan, setiap detailnya kami urus. Anda cukup hadir dan menikmati setiap momennya.') }}
        </p>
    </div>

    {{-- Kartu selebar 280px agar track selalu bisa digeser mulus di semua ukuran layar. --}}
    <div class="mx-auto max-w-[1400px] px-4 sm:px-8">
    <div
        data-reveal="up"
        data-reveal-delay="80"
        x-data="{
            active: 2,
            reduced: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
            raf: null,
            cards: [],
            init() {
                this.$nextTick(() => {
                    this.cards = [...this.$refs.track.querySelectorAll('[data-alur-card]')];
                    this.scrollTo(2, 'auto');
                });
            },
            scrollTo(i, behavior) {
                const c = this.cards[i];
                const track = this.$refs.track;
                if (!c || !track) return;
                // Geser hanya track carousel (horizontal). scrollIntoView ikut menggulung
                // seluruh halaman ke carousel saat dimuat, sehingga beranda terbuka di bagian bawah.
                const selisihKartu = c.getBoundingClientRect().left - track.getBoundingClientRect().left;
                track.scrollTo({
                    left: track.scrollLeft + selisihKartu - (track.clientWidth - c.offsetWidth) / 2,
                    behavior: behavior ?? (this.reduced ? 'auto' : 'smooth'),
                });
            },
            go(i) {
                const n = this.cards.length;
                const idx = ((i % n) + n) % n;
                this.active = idx;
                this.scrollTo(idx); // SELALU scroll — track selalu lebih lebar dari kontainer
            },
            onScroll() {
                if (this.raf) return;
                this.raf = requestAnimationFrame(() => {
                    this.raf = null;
                    const track = this.$refs.track;
                    const mid = track.getBoundingClientRect().left + track.clientWidth / 2;
                    let best = 0, dist = Infinity;
                    this.cards.forEach((c, i) => {
                        const r = c.getBoundingClientRect();
                        const d = Math.abs(r.left + r.width / 2 - mid);
                        if (d < dist) { dist = d; best = i; }
                    });
                    this.active = best;
                });
            }
        }"
    >
        <div
            x-ref="track"
            role="region"
            aria-roledescription="carousel"
            aria-label="{{ __('Alur perjalanan lima langkah') }}"
            tabindex="0"
            @scroll.passive="onScroll"
            @keydown.arrow-left.prevent="go(active - 1)"
            @keydown.arrow-right.prevent="go(active + 1)"
            class="flex snap-x snap-mandatory gap-5 overflow-x-auto scroll-smooth px-[max(1.5rem,calc(50%-140px))] py-8 md:px-[calc(50%-140px)] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
        >
            @foreach ($alurCards as $card)
                <div data-alur-card class="w-[280px] shrink-0 snap-center">
                    <div
                        :aria-current="active === {{ $loop->index }} ? 'true' : null"
                        class="block w-full rounded-[12px]"
                        :class="active === {{ $loop->index }} ? 'translate-y-0' : 'translate-y-[24px] max-md:translate-y-2'"
                    >
                        <img
                            src="{{ $card['img'] }}"
                            alt="{{ $card['alt'] }}"
                            width="280" height="210"
                            loading="lazy" decoding="async"
                            @if (abs($loop->index - 2) <= 1) fetchpriority="high" @endif
                            class="aspect-[4/3] h-auto w-full overflow-hidden rounded-[12px] object-cover"
                        />
                        <div class="mt-3.5 transition-all duration-300" :class="active === {{ $loop->index }} ? '' : 'opacity-60'">
                            {{-- Nomor langkah: BESAR + TIPIS, hijau, di ATAS judul (bukan di
                                 dalam lingkaran/kotak); lebih besar di kartu aktif. --}}
                            <p class="font-light leading-none text-[#14301F]/45 transition-all duration-300" :class="active === {{ $loop->index }} ? 'text-[34px]' : 'text-[24px]'">{{ $card['no'] }}</p>
                            <p class="mt-2 text-[16px] font-semibold leading-snug text-[#14301F]">{{ $card['title'] }}</p>
                            <p class="mt-1 text-[13px] leading-[1.6] text-[#5F6B63]">{{ $card['desc'] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- KONTROL: panah kiri + dots (aktif pil hijau) + panah kanan — dots mengikuti kartu aktif --}}
        <div class="mt-4 flex items-center justify-center gap-7">
            <button
                type="button"
                @click="go(active - 1)"
                aria-label="{{ __('Langkah sebelumnya') }}"
                class="flex h-11 w-11 items-center justify-center rounded-full border border-[#14301F]/22 text-[#14301F] transition-colors hover:bg-[#14301F]/5"
            >
                <span class="material-symbols-outlined text-xl" aria-hidden="true">arrow_back</span>
            </button>
            <div class="flex h-11 items-center gap-2.5" role="tablist" aria-label="{{ __('Alur perjalanan lima langkah') }}">
                @foreach ($alurCards as $card)
                    <button
                        type="button"
                        @click="go({{ $loop->index }})"
                        :aria-current="active === {{ $loop->index }} ? 'true' : null"
                        :class="active === {{ $loop->index }} ? 'w-6 bg-[#14301F]' : 'w-2 bg-[#14301F]/25'"
                        class="h-2 rounded-full transition-all duration-300"
                        aria-label="{{ __('Ke langkah') }} {{ $loop->iteration }}: {{ $card['title'] }}"
                    ></button>
                @endforeach
            </div>
            <button
                type="button"
                @click="go(active + 1)"
                aria-label="{{ __('Langkah berikutnya') }}"
                class="flex h-11 w-11 items-center justify-center rounded-full border border-[#14301F]/22 text-[#14301F] transition-colors hover:bg-[#14301F]/5"
            >
                <span class="material-symbols-outlined text-xl" aria-hidden="true">arrow_forward</span>
            </button>
        </div>

    </div>
    </div>
</section>
