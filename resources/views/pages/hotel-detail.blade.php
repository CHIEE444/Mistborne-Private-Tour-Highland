{{-- HALAMAN DETAIL HOTEL (/hotel/{slug}) — hotel mitra MISTBORNE: galeri, ikhtisar, tipe kamar berdampingan dengan yang termasuk, dan rating Google.
     Semua isian dari menu Hotel di admin. Panel Booking di kanan memilih journey dan menghitung kamar sesuai jumlah tamu. --}}
@php
    $jumlahFoto = $foto->count();
    $tamuPerKamar = max(1, (int) config('mistborne.biaya_perjalanan.tamu_per_kamar'));
    $journeyUtama = $daftarJourney->first();
    $urlBooking = $journeyUtama ? route('booking.buat', ['destination' => $journeyUtama->nama]) : route('journeys.index');
    $fasilitas = collect($hotel->fasilitas ?? [])->filter(fn ($kunci) => isset(\App\Models\Hotel::FASILITAS[$kunci]))->values();
    $tipeKamar = $hotel->daftarTipeKamar();
    $tamuAwal = max(1, min(4, (int) ($journeyUtama?->maks_orang ?? 2)));
    $susunanAwal = $hotel->susunanKamar($tamuAwal);
    $masuk = $hotel->jamCheckIn();
    $keluar = $hotel->jamCheckOut();
    $deskripsi = trim((string) $hotel->deskripsi);
    $daftarBooking = $daftarJourney->map(fn ($j): array => ['nama' => $j->nama, 'url' => route('booking.buat', ['destination' => $j->nama]), 'maks' => max(1, (int) $j->maks_orang)])->values();

    $adaRatingGoogle = $hotel->google_rating !== null && (float) $hotel->google_rating > 0;
    $ratingGoogle = number_format((float) $hotel->google_rating, 1);
    $jumlahUlasanGoogle = number_format((int) $hotel->google_jumlah_ulasan, 0, ',', '.');

    $tombolUtama = 'inline-flex items-center justify-center gap-2 rounded-full bg-[#1D7A56] px-7 py-3 text-sm font-semibold text-white transition-colors duration-200 hover:bg-[#166045] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#1D7A56] focus-visible:ring-offset-2';
    $tombolSoft = 'inline-flex items-center justify-center gap-2 rounded-full bg-[#F0F2EF] px-5 py-2.5 text-sm font-medium text-[#141414] transition-colors duration-200 hover:bg-[#E3E7E2] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#1D7A56]';
    $judulSeksi = 'text-[28px] font-semibold leading-tight tracking-tight text-[#141414]';
    $judulSeksiKartu = 'text-[22px] font-semibold leading-tight tracking-tight text-[#141414]';
    $tab = ['ikhtisar' => __('Ikhtisar'), 'kamar' => __('Kamar'), 'termasuk' => __('Yang Termasuk')];
@endphp
<x-tata-letak.aplikasi
    :title="$hotel->nama.' — MISTBORNE'"
    :description="\Illuminate\Support\Str::limit($deskripsi !== '' ? $deskripsi : $hotel->nama.' · '.$hotel->area, 155)"
    :image="$foto->isNotEmpty() ? url($foto->first()['url']) : asset('images/og-image-dieng.jpg')"
    :footer-rapat="true"
>
    <article
        class="mx-auto max-w-[1200px] px-4 pb-24 pt-28 sm:px-8 md:pb-4 md:pt-32"
        aria-labelledby="hotel-nama"
        x-data="{
            aktif: 'ikhtisar',
            terbuka: null,
            salin: false,
            foto: @js($foto->all()),
            journey: @js($daftarBooking),
            pilih: 0,
            tamu: {{ $tamuAwal }},
            tamuPerKamar: {{ $tamuPerKamar }},
            daftarTipe: @js($tipeKamar),
            slide: 0,
            get urlBooking() { return this.journey[this.pilih]?.url ?? @js($urlBooking); },
            get maksTamu() { return this.journey[this.pilih]?.maks ?? 6; },
            isiNyaman(t) { return Math.max(1, Math.min(t.kapasitas, this.tamuPerKamar)); },
            kamarUntuk(t) { return Math.max(1, Math.ceil(this.tamu / this.isiNyaman(t))); },
            get tipeDisarankan() { return [...this.daftarTipe].sort((a, b) => this.kamarUntuk(a) - this.kamarUntuk(b) || a.kapasitas - b.kapasitas)[0] ?? null; },
            get kamar() { return this.tipeDisarankan ? this.kamarUntuk(this.tipeDisarankan) : Math.max(1, Math.ceil(this.tamu / this.tamuPerKamar)); },
            ubahTamu(arah) { this.tamu = Math.min(this.maksTamu, Math.max(1, this.tamu + arah)); },
            pilihJourney(i) { this.pilih = i; this.tamu = Math.min(this.tamu, this.maksTamu); },
            buka(i) { this.terbuka = i; document.body.classList.add('overflow-hidden'); },
            tutup() { this.terbuka = null; document.body.classList.remove('overflow-hidden'); },
            geser(arah) { this.terbuka = (this.terbuka + arah + this.foto.length) % this.foto.length; },
            ke(id) { document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' }); },
            async bagikan() {
                const data = { title: @js($hotel->nama), url: window.location.href };
                if (navigator.share) { try { await navigator.share(data); } catch (e) {} return; }
                try { await navigator.clipboard.writeText(data.url); this.salin = true; setTimeout(() => this.salin = false, 2500); } catch (e) {}
            },
            init() {
                const sorot = new IntersectionObserver((daftar) => {
                    daftar.forEach((e) => { if (e.isIntersecting) { this.aktif = e.target.id; } });
                }, { rootMargin: '-30% 0px -60% 0px' });
                ['ikhtisar', 'kamar', 'termasuk'].forEach((id) => { const el = document.getElementById(id); if (el) { sorot.observe(el); } });

                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) { return; }
                const muncul = new IntersectionObserver((daftar) => {
                    daftar.forEach((e) => { if (e.isIntersecting) { e.target.classList.remove('opacity-0', 'translate-y-5'); muncul.unobserve(e.target); } });
                }, { threshold: 0.12 });
                this.$root.querySelectorAll('[data-muncul]').forEach((el) => {
                    el.classList.add('opacity-0', 'translate-y-5', 'transition', 'duration-700', 'ease-out');
                    muncul.observe(el);
                });
            },
        }"
        x-on:keydown.escape.window="terbuka !== null && tutup()"
        x-on:keydown.arrow-right.window="terbuka !== null && geser(1)"
        x-on:keydown.arrow-left.window="terbuka !== null && geser(-1)"
    >
        <x-bersama.tombol-beranda class="mb-6" />

        {{-- Bar ringkas: lokasi, jam menginap, isi kamar, dan Booking --}}
        <div class="flex flex-wrap items-center gap-x-4 gap-y-3 border-b border-[#E3E7E2] pb-5">
            <span class="text-lg font-medium text-[#141414]">{{ __('Detail Hotel') }}</span>

            <div class="ms-auto hidden items-center gap-4 text-sm text-[#3E4743] lg:flex">
                <span class="inline-flex items-center gap-2 px-2 py-2"><span class="material-symbols-outlined text-lg text-[#1D7A56]" aria-hidden="true">location_on</span>{{ $hotel->area }}</span>
                <span class="inline-flex items-center gap-2 px-2 py-2"><span class="material-symbols-outlined text-lg text-[#1D7A56]" aria-hidden="true">schedule</span>{{ __('Check-in :masuk · Check-out :keluar', ['masuk' => $masuk, 'keluar' => $keluar]) }}</span>
                <span class="inline-flex items-center gap-2 px-2 py-2"><span class="material-symbols-outlined text-lg text-[#1D7A56]" aria-hidden="true">group</span>{{ __('Maks. :n orang per kamar', ['n' => $tamuPerKamar]) }}</span>
            </div>
            <div class="ms-auto flex items-center lg:ms-3">
                <a :href="urlBooking" href="{{ $urlBooking }}" class="{{ $tombolUtama }} !py-2.5">{{ __('Booking') }}</a>
            </div>
        </div>

        <nav class="mt-8 text-[13px] text-[#7A847E]" aria-label="Breadcrumb">
            <ol class="flex flex-wrap items-center gap-2">
                <li><a href="{{ url_beranda() }}" class="hover:text-[#141414]">{{ __('Beranda') }}</a></li>
                <li aria-hidden="true">/</li>
                {{-- Tanpa tombol mundur: jalan kembali ke perjalanan asal (konteks ?dari=) lewat breadcrumb ini. --}}
                @if ($journeyUtama)
                    <li><a href="{{ route('journeys.show', $journeyUtama->slug) }}" class="hover:text-[#141414]">{{ __($journeyUtama->nama) }}</a></li>
                @else
                    <li><a href="{{ route('journeys.index') }}" class="hover:text-[#141414]">{{ __('Perjalanan') }}</a></li>
                @endif
                <li aria-hidden="true">/</li>
                <li aria-current="page">{{ $hotel->nama }}</li>
            </ol>
        </nav>

        <header class="mt-4 flex flex-wrap items-start justify-between gap-x-8 gap-y-4">
            <div class="min-w-0">
                <h1 id="hotel-nama" class="text-[36px] font-semibold leading-[1.1] tracking-tight text-[#141414] md:text-[48px]">{{ $hotel->nama }}</h1>
                <div class="mt-4 flex flex-wrap items-center gap-x-6 gap-y-2 text-[15px] text-[#5B6560]">
                    <span class="inline-flex items-center gap-2">
                        <span class="material-symbols-outlined text-xl text-[#141414]" aria-hidden="true">star</span>
                        @if ($adaRatingGoogle)
                            <a href="{{ $hotel->tautanUlasanGoogle() }}" target="_blank" rel="noopener noreferrer" class="hover:underline">{{ $ratingGoogle }} · {{ __(':n ulasan Google', ['n' => $jumlahUlasanGoogle]) }}</a>
                        @else
                            <span>{{ __('Hotel bintang :n', ['n' => (int) $hotel->bintang]) }}</span>
                        @endif
                    </span>
                    <span class="inline-flex items-center gap-2"><span class="material-symbols-outlined text-xl" aria-hidden="true">location_on</span>{{ $hotel->area }}</span>
                    @if ($adaRatingGoogle)
                        <span class="inline-flex items-center gap-2"><span class="tracking-widest text-[#C9A24B]" role="img" aria-label="{{ __('Hotel bintang :n', ['n' => (int) $hotel->bintang]) }}">{{ str_repeat('★', (int) $hotel->bintang) }}</span></span>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" x-on:click="bagikan()" class="{{ $tombolSoft }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="18" cy="5" r="3" /><circle cx="6" cy="12" r="3" /><circle cx="18" cy="19" r="3" /><path d="m8.6 13.5 6.8 4M15.4 6.5l-6.8 4" /></svg>
                    <span x-text="salin ? @js(__('Tautan hotel disalin')) : @js(__('Bagikan'))">{{ __('Bagikan') }}</span>
                </button>
                <a href="{{ $hotel->petaUrl() }}" target="_blank" rel="noopener noreferrer" class="{{ $tombolSoft }}"><span class="material-symbols-outlined text-lg" aria-hidden="true">map</span>{{ __('Lihat lokasi di Google Maps') }}</a>
            </div>
        </header>

        {{-- Galeri --}}
        <section class="mt-7" aria-label="{{ __('Galeri foto hotel') }}">
            @if ($jumlahFoto === 0)
                <div class="flex aspect-[16/7] w-full items-center justify-center rounded-3xl bg-[#EAF3EE] text-[#1D7A56]" aria-hidden="true"><span class="material-symbols-outlined text-6xl">hotel</span></div>
            @else
                <div class="relative hidden md:block">
                    {{-- Satu foto: rasio 16:9 (maks. 640px) agar foto 4:3 tidak terpotong habis atas-bawahnya; lebih dari satu: mosaik 440px --}}
                    <div @class(['grid grid-cols-4 grid-rows-2 gap-3', 'aspect-[16/9] max-h-[640px]' => $jumlahFoto === 1, 'h-[440px]' => $jumlahFoto > 1])>
                        @foreach ($foto->take(4) as $i => $item)
                            @php
                                $petak = match (true) {
                                    $jumlahFoto === 1 => 'col-span-4 row-span-2',
                                    $jumlahFoto === 2 => 'col-span-2 row-span-2',
                                    $i === 0 => 'col-span-2 row-span-2',
                                    $jumlahFoto === 3, $i === 1 => 'col-span-2',
                                    default => 'col-span-1',
                                };
                            @endphp
                            <button type="button" x-on:click="buka({{ $i }})" class="group relative overflow-hidden rounded-3xl bg-[#E6EBE5] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#1D7A56] {{ $petak }}" aria-label="{{ $item['label'] ?? $hotel->nama }}">
                                <img src="{{ $item['url'] }}" alt="{{ $item['label'] ?? $hotel->nama }}" class="absolute inset-0 h-full w-full object-cover object-center transition-opacity duration-300 group-hover:opacity-90" loading="{{ $i === 0 ? 'eager' : 'lazy' }}" decoding="async">
                                @if ($item['label'] && ! ($loop->last && $jumlahFoto > 1))
                                    <span class="absolute bottom-4 start-4 rounded-full bg-black/45 px-3 py-1 text-xs font-medium text-white backdrop-blur-sm">{{ $item['label'] }}</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                    @if ($jumlahFoto > 1)
                        <button type="button" x-on:click="buka(0)" class="absolute bottom-5 end-5 inline-flex items-center gap-2 rounded-full bg-white px-5 py-2.5 text-sm font-semibold text-[#141414] transition-colors hover:bg-[#F0F2EF]">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1.5" /><rect x="14" y="3" width="7" height="7" rx="1.5" /><rect x="3" y="14" width="7" height="7" rx="1.5" /><rect x="14" y="14" width="7" height="7" rx="1.5" /></svg>
                            {{ __('Lihat semua foto') }} ({{ $jumlahFoto }})
                        </button>
                    @endif
                </div>

                <div class="relative md:hidden">
                    <div class="-mx-4 flex snap-x snap-mandatory gap-3 overflow-x-auto px-4 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" x-on:scroll.passive="slide = Math.round($el.scrollLeft / ($el.firstElementChild?.offsetWidth || 1))">
                        @foreach ($foto as $i => $item)
                            <button type="button" x-on:click="buka({{ $i }})" class="relative aspect-[4/3] w-[88%] shrink-0 snap-center overflow-hidden rounded-3xl bg-[#E6EBE5]" aria-label="{{ $item['label'] ?? $hotel->nama }}">
                                <img src="{{ $item['url'] }}" alt="{{ $item['label'] ?? $hotel->nama }}" class="absolute inset-0 h-full w-full object-cover object-center" loading="{{ $i === 0 ? 'eager' : 'lazy' }}" decoding="async">
                                @if ($item['label'])
                                    <span class="absolute bottom-3 start-3 rounded-full bg-black/45 px-3 py-1 text-xs font-medium text-white backdrop-blur-sm">{{ $item['label'] }}</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                    @if ($jumlahFoto > 1)
                        <span class="pointer-events-none absolute bottom-3 end-7 rounded-full bg-black/55 px-3 py-1 text-xs font-medium tabular-nums text-white"><span x-text="slide + 1">1</span> / {{ $jumlahFoto }}</span>
                    @endif
                </div>
            @endif
        </section>

        {{-- Tab melekat --}}
        <nav class="sticky top-[84px] z-30 -mx-4 mt-8 bg-white/90 px-4 backdrop-blur sm:-mx-8 sm:px-8" aria-label="{{ __('Detail Hotel') }}">
            <ul class="flex gap-2 overflow-x-auto py-3 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                @foreach ($tab as $kunciTab => $labelTab)
                    <li class="shrink-0">
                        <a href="#{{ $kunciTab }}" x-on:click.prevent="ke('{{ $kunciTab }}')" :aria-current="aktif === '{{ $kunciTab }}' ? 'true' : null" :class="aktif === '{{ $kunciTab }}' ? 'border-[#141414] bg-[#141414] text-white' : 'border-[#E3E7E2] bg-white text-[#4A534E] hover:border-[#141414]/40'" class="inline-flex rounded-full border px-6 py-3 text-sm font-medium transition-colors duration-200">{{ $labelTab }}</a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="mt-10 grid gap-12 lg:grid-cols-[minmax(0,1fr)_380px] lg:items-start">
            <div class="space-y-12">
                {{-- Ikhtisar --}}
                <section id="ikhtisar" class="scroll-mt-40" data-muncul>
                    <h2 class="{{ $judulSeksi }}">{{ __('Tempat nyaman untuk Anda') }}</h2>
                    @if ($deskripsi !== '')
                        <div x-data="{ baca: false }">
                            <p class="mt-4 max-w-[70ch] text-base leading-[1.8] text-[#5B6560]" :class="baca ? '' : 'line-clamp-4'">{{ $deskripsi }}</p>
                            @if (mb_strlen($deskripsi) > 240)
                                <button type="button" x-on:click="baca = ! baca" class="mt-1 text-sm font-semibold text-[#1D7A56] hover:underline" x-text="baca ? @js(__('Ringkas')) : @js(__('Baca selengkapnya'))">{{ __('Baca selengkapnya') }}</button>
                            @endif
                        </div>
                    @endif

                    <dl class="mt-8 grid grid-cols-2 gap-x-8 gap-y-6 sm:grid-cols-4">
                        @foreach ([
                            [__('Check-in'), $masuk],
                            [__('Check-out'), $keluar],
                            [__('Isi kamar'), __('Maks. :n orang', ['n' => $tamuPerKamar])],
                            [__('Tipe kamar'), __(':n tipe', ['n' => count($tipeKamar)])],
                        ] as [$label, $nilai])
                            <div>
                                <dt class="text-[13px] text-[#7A847E]">{{ $label }}</dt>
                                <dd class="mt-1 text-xl font-semibold tracking-tight text-[#141414]">{{ $nilai }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    @if (filled($hotel->jarak_destinasi))
                        <p class="mt-8 flex items-start gap-3 text-[15px] leading-snug text-[#141414]">
                            <span class="material-symbols-outlined mt-0.5 text-xl text-[#1D7A56]" aria-hidden="true">schedule</span>
                            <span><span class="font-semibold">{{ __('Estimasi perjalanan destinasi–hotel') }}:</span> {{ $hotel->estimasiPerjalanan() }}<span class="mt-1 block text-[13px] text-[#7A847E]">{{ __('Estimasi saja; bisa berubah karena lalu lintas dan kondisi jalan.') }}</span></span>
                        </p>
                    @endif

                    @if ($fasilitas->isNotEmpty())
                        <h3 class="mt-12 text-xl font-semibold tracking-tight text-[#141414]">{{ __('Fasilitas terbaik kami') }}</h3>
                        <ul class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach ($fasilitas as $kunciFasilitas)
                                <li class="flex h-full min-h-[64px] items-center gap-4 rounded-2xl border border-[#E3E7E2] bg-white px-4 py-3 text-[15px] font-medium leading-snug text-[#141414]">
                                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-[#EEF6F1]" aria-hidden="true"><x-bersama.ikon-fasilitas :kunci="$kunciFasilitas" class="h-5 w-5 text-[#1D7A56]" /></span>
                                    <span>{{ __(\App\Models\Hotel::FASILITAS[$kunciFasilitas]) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                {{-- Kamar dan yang termasuk berdampingan (dua kartu sejajar di layar lebar, bertumpuk di ponsel).
                     Tiap tipe kamar menampilkan kapasitas dan jumlah kamar untuk jumlah tamu di panel Booking;
                     tipe dengan kamar paling sedikit disorot (aturan sama dengan form reservasi admin). --}}
                <div class="grid items-start gap-6 xl:grid-cols-2">
                <section id="kamar" class="scroll-mt-40 rounded-3xl border border-[#E3E7E2] bg-white p-6" data-muncul>
                    <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-2">
                        <h2 class="{{ $judulSeksiKartu }}">{{ __('Tipe kamar dan kapasitas') }}</h2>
                        <p class="text-sm text-[#5B6560]" x-text="@js(__('Dihitung untuk :n tamu', ['n' => '__N__'])).replace('__N__', tamu)">{{ __('Dihitung untuk :n tamu', ['n' => $tamuAwal]) }}</p>
                    </div>
                    <ul class="mt-6 grid gap-3">
                        @foreach ($tipeKamar as $tipe)
                            @php
                                $disarankanAwal = $tipe['nama'] === $susunanAwal['tipe'];
                                $kamarAwal = (int) ceil($tamuAwal / max(1, min($tipe['kapasitas'], $tamuPerKamar)));
                            @endphp
                            <li
                                @class(['flex items-center justify-between gap-4 rounded-2xl p-4 transition-colors', 'bg-[#F1F7F3] ring-2 ring-[#1D7A56]' => $disarankanAwal, 'bg-white ring-1 ring-[#E3E7E2]' => ! $disarankanAwal])
                                :class="tipeDisarankan?.nama === @js($tipe['nama']) ? 'bg-[#F1F7F3] ring-2 ring-[#1D7A56]' : 'bg-white ring-1 ring-[#E3E7E2]'"
                            >
                                <div class="min-w-0">
                                    <p class="flex flex-wrap items-center gap-2.5 text-base font-semibold text-[#141414]">
                                        {{ $tipe['nama'] }}
                                        @if (count($tipeKamar) > 1)
                                            <span x-show="tipeDisarankan?.nama === @js($tipe['nama'])" @style(['display: none' => ! $disarankanAwal]) class="rounded-full bg-[#1D7A56] px-2.5 py-0.5 text-[11.5px] font-semibold text-white">{{ __('Disarankan') }}</span>
                                        @endif
                                    </p>
                                    <p class="mt-1.5 flex items-center gap-2.5 text-sm text-[#5B6560]">
                                        <span class="flex items-center gap-0.5 text-[#1D7A56]" aria-hidden="true">
                                            @for ($p = 0; $p < min(6, (int) $tipe['kapasitas']); $p++)
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="7.5" r="3.5" /><path d="M5 20c0-3.9 3.1-6.5 7-6.5s7 2.6 7 6.5Z" /></svg>
                                            @endfor
                                        </span>
                                        {{ __('Muat :n orang', ['n' => $tipe['kapasitas']]) }}
                                    </p>
                                </div>
                                <p class="shrink-0 text-end text-[13px] text-[#5B6560]">
                                    <span class="block text-[24px] font-semibold leading-none tabular-nums text-[#12301F]" x-text="kamarUntuk(@js($tipe))">{{ $kamarAwal }}</span>
                                    <span class="mt-1 block">{{ __('kamar dibutuhkan') }}</span>
                                </p>
                            </li>
                        @endforeach
                    </ul>
                    <p class="mt-4 text-sm leading-relaxed text-[#6C7670]">{{ __('Satu kamar diisi paling banyak :n orang. MISTBORNE memesankan tipe dengan jumlah kamar paling sedikit untuk rombongan Anda; ubah jumlah tamu di panel Booking untuk melihat hitungannya.', ['n' => $tamuPerKamar]) }}</p>
                </section>

                {{-- Yang termasuk --}}
                <section id="termasuk" class="scroll-mt-40 rounded-3xl border border-[#E3E7E2] bg-white p-6" data-muncul>
                    <h2 class="{{ $judulSeksiKartu }}">{{ __('Yang sudah termasuk dalam paket') }}</h2>
                    <ul class="mt-6 grid gap-y-4">
                        @foreach ((array) config('mistborne.termasuk', []) as $butir)
                            <li class="flex items-start gap-3 text-[15px] leading-snug text-[#141414]"><x-bersama.ikon-centang class="mt-0.5" /><span>{{ __($butir) }}</span></li>
                        @endforeach
                    </ul>
                    <dl class="mt-8 divide-y divide-[#E3E7E2] border-y border-[#E3E7E2] text-[15px]">
                        <div class="flex justify-between gap-6 py-3.5"><dt class="text-[#6C7670]">{{ __('Check-in') }} / {{ __('Check-out') }}</dt><dd class="font-semibold text-[#141414]">{{ $masuk }} / {{ $keluar }}</dd></div>
                        <div class="flex justify-between gap-6 py-3.5"><dt class="text-[#6C7670]">{{ __('Isi kamar') }}</dt><dd class="font-semibold text-[#141414]">{{ __('Maks. :n orang per kamar', ['n' => $tamuPerKamar]) }}</dd></div>
                        <div class="flex justify-between gap-6 py-3.5"><dt class="text-[#6C7670]">Extra bed</dt><dd class="text-end font-semibold text-[#141414]">{{ $hotel->tersedia_extra_bed ? __('Extra bed tersedia (+1 orang per kamar)') : __('Extra bed tidak tersedia') }}</dd></div>
                        @if (filled($hotel->kebijakan_pembatalan))
                            <div class="flex justify-between gap-6 py-3.5"><dt class="shrink-0 text-[#6C7670]">{{ __('Kebijakan pembatalan hotel') }}</dt><dd class="text-end font-semibold text-[#141414]">{{ $hotel->kebijakan_pembatalan }}</dd></div>
                        @endif
                    </dl>
                </section>
                </div>
            </div>

            {{-- Kolom kanan: booking dan rating Google --}}
            <aside class="rounded-3xl border border-[#E3E7E2] bg-white">
                <section class="p-6">
                    <h2 class="text-xl font-semibold tracking-tight text-[#141414]">{{ __('Booking') }}</h2>
                    <p class="mt-1 text-sm text-[#1D7A56]">{{ __('Termasuk dalam harga paket') }}</p>

                    @if ($daftarJourney->isNotEmpty())
                        <fieldset class="mt-5">
                            <legend class="text-sm font-medium text-[#6C7670]">{{ __('Pilih perjalanan') }}</legend>
                            <div class="mt-2 space-y-2">
                                @foreach ($daftarJourney as $i => $itemJourney)
                                    <label class="flex cursor-pointer items-center justify-between gap-3 rounded-2xl border px-4 py-3 text-sm transition-colors" :class="pilih === {{ $i }} ? 'border-[#1D7A56] bg-[#EEF6F1]' : 'border-[#E3E7E2] hover:border-[#141414]/30'">
                                        <span class="font-semibold text-[#141414]">{{ $itemJourney->nama }}</span>
                                        <input type="radio" name="journey" class="h-4 w-4 border-[#B9C2BC] text-[#1D7A56] focus:ring-[#1D7A56]" x-on:change="pilihJourney({{ $i }})" @checked($i === 0)>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>

                        <div class="mt-5">
                            <p class="text-sm font-medium text-[#6C7670]">{{ __('Jumlah tamu') }}</p>
                            <div class="mt-2 flex items-center justify-between rounded-2xl border border-[#E3E7E2] p-1.5">
                                <button type="button" x-on:click="ubahTamu(-1)" :disabled="tamu <= 1" class="flex h-10 w-10 items-center justify-center rounded-xl text-xl text-[#141414] transition-colors hover:bg-[#F0F2EF] disabled:text-[#B9C2BC] disabled:hover:bg-transparent" aria-label="−">−</button>
                                <span class="text-xl font-semibold tabular-nums text-[#141414]" x-text="tamu" aria-live="polite">{{ $tamuAwal }}</span>
                                <button type="button" x-on:click="ubahTamu(1)" :disabled="tamu >= maksTamu" class="flex h-10 w-10 items-center justify-center rounded-xl text-xl text-[#141414] transition-colors hover:bg-[#F0F2EF] disabled:text-[#B9C2BC] disabled:hover:bg-transparent" aria-label="+">+</button>
                            </div>
                            <p class="mt-3 text-sm text-[#4A534E]">
                                <span class="font-semibold text-[#141414]" x-text="@js(__(':n tamu = :k kamar', ['n' => '__N__', 'k' => '__K__'])).replace('__N__', tamu).replace('__K__', kamar)">{{ __(':n tamu = :k kamar', ['n' => $tamuAwal, 'k' => $susunanAwal['kamar']]) }}</span>
                                · <span x-text="tipeDisarankan?.nama">{{ $susunanAwal['tipe'] }}</span>
                            </p>
                        </div>
                    @endif

                    <a :href="urlBooking" href="{{ $urlBooking }}" class="{{ $tombolUtama }} mt-6 w-full">{{ __('Booking') }}<span class="material-symbols-outlined text-lg" aria-hidden="true">arrow_forward</span></a>
                </section>

                <section class="border-t border-[#E3E7E2] p-6" aria-labelledby="ulasan-judul">
                    <h2 id="ulasan-judul" class="text-xl font-semibold tracking-tight text-[#141414]">{{ __('Ulasan dan rating') }}</h2>
                    @if ($adaRatingGoogle)
                        <div class="mt-5 flex items-center gap-4">
                            <span class="text-[56px] font-semibold leading-none tracking-tight text-[#141414]">{{ $ratingGoogle }}</span>
                            <div>
                                <span class="relative inline-block whitespace-nowrap text-2xl leading-none" role="img" aria-label="{{ $ratingGoogle }} / 5">
                                    <span class="text-[#DDE2DB]" aria-hidden="true">★★★★★</span>
                                    <span class="absolute inset-y-0 start-0 overflow-hidden whitespace-nowrap text-[#F4B400]" style="width: {{ min(100, max(0, (float) $hotel->google_rating / 5 * 100)) }}%" aria-hidden="true">★★★★★</span>
                                </span>
                                <p class="mt-1.5 text-[13px] text-[#6C7670]">{{ __('Berdasarkan :n ulasan di Google', ['n' => $jumlahUlasanGoogle]) }}</p>
                            </div>
                        </div>
                    @else
                        <p class="mt-3 text-sm leading-relaxed text-[#6C7670]">{{ __('Rating Google hotel ini belum tersedia. Lihat langsung di Google Maps.') }}</p>
                    @endif
                    <a href="{{ $hotel->tautanUlasanGoogle() }}" target="_blank" rel="noopener noreferrer" class="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-[#1D7A56] underline decoration-[#1D7A56]/30 underline-offset-4 transition-colors hover:decoration-[#1D7A56]">{{ __('Lihat ulasan di Google Maps') }}<span class="material-symbols-outlined text-lg" aria-hidden="true">open_in_new</span></a>
                </section>
            </aside>
        </div>

        {{-- Tombol Booking tetap di bawah layar pada ponsel --}}
        <div class="fixed inset-x-0 bottom-0 z-40 border-t border-[#E3E7E2] bg-white/95 px-4 py-3 backdrop-blur md:hidden">
            <a :href="urlBooking" href="{{ $urlBooking }}" class="{{ $tombolUtama }} w-full">{{ __('Booking') }}<span class="material-symbols-outlined text-lg" aria-hidden="true">arrow_forward</span></a>
        </div>

        {{-- Penampil foto layar penuh dengan strip thumbnail --}}
        <div x-show="terbuka !== null" x-cloak class="fixed inset-0 z-[100] flex flex-col bg-[#0B1510]/95" role="dialog" aria-modal="true" aria-label="{{ __('Galeri foto hotel') }}">
            <div class="flex items-center justify-between px-5 py-4 text-white">
                <p class="text-sm tabular-nums text-white/80" x-text="terbuka !== null ? @js(__('Foto :n dari :total', ['n' => '__N__', 'total' => '__T__'])).replace('__N__', terbuka + 1).replace('__T__', foto.length) : ''"></p>
                <button type="button" x-on:click="tutup()" class="flex h-10 w-10 items-center justify-center rounded-full border border-white/25 transition-colors hover:bg-white hover:text-[#12301F]" aria-label="{{ __('Tutup') }}"><span class="material-symbols-outlined" aria-hidden="true">close</span></button>
            </div>
            <div class="relative flex min-h-0 flex-1 items-center justify-center px-4 sm:px-16" x-on:click.self="tutup()">
                <button type="button" x-show="foto.length > 1" x-on:click="geser(-1)" class="absolute start-3 flex h-11 w-11 items-center justify-center rounded-full border border-white/25 text-white transition-colors hover:bg-white hover:text-[#12301F] sm:start-6" aria-label="{{ __('Sebelumnya') }}"><span class="material-symbols-outlined" aria-hidden="true">arrow_back</span></button>
                <figure class="flex max-h-full flex-col items-center gap-3">
                    <img :src="terbuka !== null ? foto[terbuka].url : ''" :alt="terbuka !== null ? (foto[terbuka].label ?? @js($hotel->nama)) : ''" class="max-h-[68vh] max-w-full rounded-lg object-contain">
                    <figcaption class="text-sm text-white/80" x-text="terbuka !== null ? (foto[terbuka].label ?? @js($hotel->nama)) : ''"></figcaption>
                </figure>
                <button type="button" x-show="foto.length > 1" x-on:click="geser(1)" class="absolute end-3 flex h-11 w-11 items-center justify-center rounded-full border border-white/25 text-white transition-colors hover:bg-white hover:text-[#12301F] sm:end-6" aria-label="{{ __('Berikutnya') }}"><span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span></button>
            </div>
            <div class="flex justify-center gap-2 overflow-x-auto px-4 py-4" x-show="foto.length > 1">
                <template x-for="(f, i) in foto" :key="i">
                    <button type="button" x-on:click="terbuka = i" class="h-14 w-20 shrink-0 overflow-hidden rounded-md transition" :class="terbuka === i ? 'opacity-100 ring-2 ring-white' : 'opacity-50 hover:opacity-90'"><img :src="f.url" :alt="f.label ?? @js($hotel->nama)" class="h-full w-full object-cover"></button>
                </template>
            </div>
        </div>
    </article>
</x-tata-letak.aplikasi>
