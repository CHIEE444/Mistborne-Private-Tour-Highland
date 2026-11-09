{{-- PAKET WISATA (#paket) — panel melebar: foto paket berjajar tegak; panel aktif melebar dan menampilkan detail
     (nama, deskripsi, kapsul kaca durasi/tamu, harga, tombol), panel lain ramping dengan nama vertikal. Di bawah layar lg
     semua panel bertumpuk dan terbuka. Desain thumbnail sebelumnya dicadangkan di storage/app/private/cadangan-desain. --}}
@php
    // Paket Relaksasi Alam = Perjalanan berjenis "paket" (menu Paket Wisata di admin): punya halaman detail, jadwal,
    // dan dipesan lewat alur booking yang sama dengan journey. Hanya paket yang ditandai tampil yang muncul di sini.
    $packages = \App\Models\Perjalanan::query()->paket()->tampilBeranda()->orderBy('urutan')->orderBy('id')->get()
        ->map(fn (\App\Models\Perjalanan $paket): array => [
            'title' => __($paket->nama),
            'chip' => $paket->chip ? __($paket->chip) : '',
            'desc' => $paket->tagline ? __($paket->tagline) : ($paket->deskripsi ? __($paket->deskripsi) : ''),
            'duration' => $paket->labelDurasiKartu(),
            'guests' => $paket->labelKapasitasTamu(),
            'price' => $paket->hargaFormat() ?? __('Harga sesuai rute'),
            'img' => $paket->sampul(),
            'tautan' => route('journeys.show', ['slug' => $paket->slug]),
        ])->values()->all();
@endphp
@if (count($packages) > 0)
<section class="relative scroll-mt-20 bg-white py-20 md:py-24" id="paket" aria-labelledby="paket-judul" x-data="{ aktif: 0 }">
    <div class="mx-auto max-w-[1320px] px-5 sm:px-8 lg:px-12">
        {{-- Kepala: label kecil + judul besar (kiri), ringkasan (kanan) — senada dengan Tentang Kami dan FAQ --}}
        <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between" data-reveal="up">
            <div>
                <p class="text-[15px] text-[#6B736F]">{{ __('Paket Wisata') }}</p>
                <h2 class="mt-3 text-[clamp(36px,4.4vw,60px)] font-semibold leading-[1.02] tracking-[-0.035em] text-[#1C2421]" id="paket-judul">{{ __('Paket Relaksasi Alam') }}</h2>
            </div>
            <p class="max-w-[400px] text-[15px] leading-relaxed text-[#6B736F] md:pb-2">{{ __('Rute tenang dengan panorama terbaik. Semua privat, sudah termasuk hotel, tiket masuk, dan pemandu.') }}</p>
        </div>

        {{-- Panel-panel paket --}}
        <div class="pk-deret mt-10 flex flex-col gap-3 lg:mt-12 lg:flex-row" data-reveal="up" data-reveal-delay="100">
            @foreach ($packages as $i => $paket)
                <article
                    class="pk-panel group relative overflow-hidden rounded-[28px] bg-[#1C2421]"
                    :class="aktif === {{ $i }} ? 'is-aktif' : ''"
                    @mouseenter="aktif = {{ $i }}"
                    @focusin="aktif = {{ $i }}"
                    aria-labelledby="pk-nama-{{ $i }}"
                >
                    <img src="{{ $paket['img'] }}" alt="{{ __('Paket :name', ['name' => $paket['title']]) }}" width="1600" height="1067" loading="lazy" decoding="async" class="pk-foto absolute inset-0 h-full w-full object-cover text-transparent">
                    <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/80 via-black/25 to-black/10" aria-hidden="true"></div>

                    {{-- Atas: kategori (kapsul kaca) + nomor urut --}}
                    <div class="absolute inset-x-5 top-5 flex items-start justify-between gap-3">
                        @if ($paket['chip'])
                            <span class="pk-kaca truncate rounded-full px-3.5 py-1.5 text-[12.5px] font-medium text-white">{{ $paket['chip'] }}</span>
                        @endif
                        <span class="shrink-0 text-[13px] font-medium tabular-nums text-white/80" aria-hidden="true">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                    </div>

                    {{-- Saat ramping (desktop): nama vertikal; seluruh panel bisa diklik untuk membukanya --}}
                    <button type="button" class="pk-pemicu absolute inset-0 hidden text-start lg:block" @click="aktif = {{ $i }}" :aria-expanded="aktif === {{ $i }}" aria-label="{{ __('Tampilkan :nama', ['nama' => $paket['title']]) }}">
                        <span class="pk-nama-tegak pointer-events-none absolute whitespace-nowrap text-[20px] font-semibold leading-none text-white" aria-hidden="true">{{ $paket['title'] }}</span>
                    </button>

                    {{-- Detail paket --}}
                    <div class="pk-detail absolute inset-x-6 bottom-6 text-white sm:inset-x-7 sm:bottom-7">
                        <h3 id="pk-nama-{{ $i }}" class="text-[clamp(26px,2.6vw,36px)] font-semibold leading-tight tracking-[-0.02em]">{{ $paket['title'] }}</h3>
                        @if ($paket['desc'])
                            <p class="mt-2 line-clamp-2 max-w-[440px] text-[15px] leading-relaxed text-white/85">{{ $paket['desc'] }}</p>
                        @endif
                        <div class="mt-5 flex flex-wrap items-end justify-between gap-4">
                            <div class="flex flex-wrap gap-2">
                                <span class="pk-kaca whitespace-nowrap rounded-full px-3.5 py-1.5 text-[13px]">{{ $paket['duration'] }}</span>
                                <span class="pk-kaca whitespace-nowrap rounded-full px-3.5 py-1.5 text-[13px]">{{ $paket['guests'] }}</span>
                            </div>
                            <div class="flex w-full flex-col items-stretch gap-3 sm:w-auto sm:flex-row sm:items-center sm:gap-4">
                                <div class="sm:text-end">
                                    <p class="text-[12px] text-white/70">{{ __('Mulai dari') }}</p>
                                    <p class="whitespace-nowrap text-[22px] font-semibold tabular-nums leading-tight">{{ $paket['price'] }} <span class="text-[12px] font-normal text-white/70">/ {{ __('paket') }}</span></p>
                                </div>
                                <a href="{{ $paket['tautan'] }}" class="hero-kaca-putih relative z-10 inline-flex h-12 shrink-0 items-center justify-between gap-3 whitespace-nowrap rounded-full pe-1.5 ps-5 text-[14px] font-semibold text-white sm:justify-start">
                                    {{ __('Lihat Detail Paket') }}
                                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-white text-[#12301F]"><svg class="h-[17px] w-[17px] rtl:-scale-x-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17 17 7M8 7h9v9"/></svg></span>
                                </a>
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
@endif
