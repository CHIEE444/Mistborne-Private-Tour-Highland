{{-- DETAIL JOURNEY: hero foto, bar booking, tentang + highlight, itinerary per hari, hotel, dan info sebelum berangkat. --}}
@php
    $kustom = $journey['jadwal_fleksibel'];
    $urlBooking = $kustom ? route('rancang-trip-kustom') : route('booking.buat', ['destination' => $journey['name']]);
    $paketWisata = ($journey['jenis'] ?? 'journey') === 'paket';
    $persenDp = (int) config('mistborne.pembayaran.dp_persen', 30);
    $hariPelunasan = (int) config('mistborne.pembayaran.pelunasan_hari_sebelum', 3);
    $persenGantiJadwal = \App\Models\Pengaturan::current()->persen_charge_ganti_jadwal;
    $itineraryPerHari = collect($journey['itinerary'])->groupBy(fn ($baris) => $baris['hari'] ?? 0);
    $hariTerakhir = $itineraryPerHari->keys()->max();
    $jumlahHari = max(1, $itineraryPerHari->count());
    $hargaAngka = $journey['harga_angka'] ?? null;
    $durasi = $journey['duration'] ?? null;
    $deskripsi = trim((string) ($journey['desc'] ?? ''));
    $hargaTampil = $kustom ? __('Harga disesuaikan') : ($journey['price'] ?? __('Harga sesuai rute'));

    // Garis bergelombang itinerary (desktop): melewati titik tengah tiap kolom hari, lengkung naik-turun bergantian.
    $gelombang = 'M 0 40';
    $xSebelum = 0;
    for ($i = 0; $i < $jumlahHari; $i++) {
        $x = (2 * $i + 1) / (2 * $jumlahHari) * 1000;
        $tengah = ($x - $xSebelum) / 2;
        $puncak = $i % 2 === 0 ? 6 : 74;
        $gelombang .= sprintf(' C %.1f %d, %.1f %d, %.1f 40', $xSebelum + $tengah, $puncak, $x - $tengah, $puncak, $x);
        $xSebelum = $x;
    }
    $gelombang .= sprintf(' C %.1f %d, %.1f %d, 1000 40', $xSebelum + (1000 - $xSebelum) / 2, $jumlahHari % 2 === 0 ? 6 : 74, 1000 - (1000 - $xSebelum) / 2, $jumlahHari % 2 === 0 ? 6 : 74);

    // Foto potret (mis. Green Canyon) tetap memenuhi hero, titik fokusnya digeser sedikit ke bawah supaya subjek
    // (sungai) tidak terpotong; foto lanskap berfokus di tengah.
    $ukuranFoto = str_starts_with((string) $journey['img'], '/') && is_file(public_path(ltrim(rawurldecode($journey['img']), '/'))) ? @getimagesize(public_path(ltrim(rawurldecode($journey['img']), '/'))) : false;
    $fotoPotret = $ukuranFoto && $ukuranFoto[1] > $ukuranFoto[0];
    $bintangHotel = (int) (collect($hotel)->min('bintang') ?: \App\Models\Hotel::BINTANG_STANDAR);
    $praktis = array_values(array_filter([
        ['ikon' => 'location_on', 'label' => __('Titik Jemput'), 'nilai' => $journey['practical']['pickup'] ?? null],
        ['ikon' => 'schedule', 'label' => __('Jam Jemput'), 'nilai' => __('Fleksibel')],
        ['ikon' => 'group', 'label' => __('Kapasitas'), 'nilai' => $journey['capacity']],
        ['ikon' => 'directions_car', 'label' => __('Kendaraan'), 'nilai' => __('Jeep adventure dan SUV privat')],
        ['ikon' => 'hotel', 'label' => __('Penginapan'), 'nilai' => __('Hotel bintang :n', ['n' => $bintangHotel])],
    ], fn ($butir) => filled($butir['nilai'])));
    $tips = $journey['practical']['tips'] ?? null;

    // Ketentuan: tiap kartu cukup satu paragraf utuh, tanpa label angka terpisah.
    $ketentuan = [
        __('Bayar lunas sekali di awal, atau amankan tanggal lebih dulu dengan DP minimal :persen% dari harga perjalanan.', ['persen' => $persenDp]),
        __('Bila memilih DP, sisa pembayaran dilunasi paling lambat :hari hari sebelum keberangkatan, tanpa biaya tambahan.', ['hari' => $hariPelunasan]),
        __('Pembatalan gratis bila diajukan paling lambat 7 hari sebelum tanggal keberangkatan.'),
        __('Ganti jadwal bisa diajukan lebih dari 7 hari sebelum keberangkatan; penggantian pertama dikenai biaya :persen% dari harga, berikutnya gratis.', ['persen' => $persenGantiJadwal]),
    ];
    $jumlahHighlight = count($journey['highlights']);
    $kolomHighlight = [1 => 'lg:grid-cols-1', 2 => 'lg:grid-cols-2', 3 => 'lg:grid-cols-3', 4 => 'lg:grid-cols-4', 5 => 'lg:grid-cols-5'][$jumlahHighlight] ?? 'lg:grid-cols-6';
    $judulBagian = 'text-[30px] font-semibold leading-[1.1] tracking-tight text-[#12301F] sm:text-[36px]';
    $judulPanel = 'text-[20px] font-semibold tracking-tight text-[#12301F]';
@endphp
<x-tata-letak.aplikasi
    :title="$journey['name'].' — MISTBORNE'"
    :description="\Illuminate\Support\Str::limit($journey['tagline'] ?? $journey['desc'] ?? '', 155)"
    :image="url($journey['img'])"
    :footer-rapat="true"
>
    <article class="mx-auto max-w-[1240px] px-4 pt-28 sm:px-8 md:pt-32" aria-labelledby="journey-nama">

        <x-bersama.tombol-beranda class="mb-6" />

        {{-- HERO --}}
        <section class="relative overflow-hidden rounded-[32px] bg-[#12231A]">
            <img src="{{ $journey['img'] }}" alt="{{ $journey['place'] }}" class="absolute inset-0 h-full w-full object-cover" style="object-position: {{ $fotoPotret ? '50% 62%' : '50% 50%' }}" width="1600" height="1000" decoding="async" fetchpriority="high">
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-[#08130D]/90 via-[#08130D]/35 to-[#08130D]/5" aria-hidden="true"></div>

            <div class="relative flex min-h-[540px] flex-col p-6 pb-20 sm:min-h-[640px] sm:p-10 sm:pb-24 lg:p-14 lg:pb-28">
                <div class="mt-auto pt-20 text-white">
                    <h1 id="journey-nama" class="max-w-[14ch] text-[40px] font-semibold leading-[0.98] tracking-[-0.02em] sm:text-[64px] lg:text-[76px]">{{ $journey['name'] }}</h1>
                    @if ($journey['tagline'])
                        <p class="mt-5 max-w-[48ch] text-[16px] leading-relaxed text-white/80 sm:text-[18px]">{{ $journey['tagline'] }}</p>
                    @endif
                    <ul class="mt-7 grid grid-cols-2 gap-x-4 gap-y-1.5 text-[14px] font-medium text-white/90 sm:flex sm:flex-wrap sm:items-center sm:gap-y-2 sm:[&>li+li]:ms-4 sm:[&>li+li]:border-s sm:[&>li+li]:border-white/30 sm:[&>li+li]:ps-4">
                        @foreach (array_filter([$journey['place'], $durasi, $journey['capacity'], $journey['region_label'] ? __($journey['region_label']) : null]) as $fakta)
                            <li>{{ $fakta }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </section>

        {{-- Bar booking menumpang di tepi bawah hero --}}
        <div class="relative z-10 mx-3 -mt-12 grid overflow-hidden rounded-[26px] bg-white shadow-[0_30px_70px_-35px_rgba(8,19,13,.55)] ring-1 ring-[#E3E7E2] sm:mx-6 lg:mx-12 lg:grid-cols-[minmax(0,15rem)_minmax(0,1fr)_auto] lg:items-center" aria-label="{{ __('Ringkasan booking') }}">
            <div class="px-6 pt-6 lg:py-6 lg:pe-0">
                <p class="text-[13px] text-[#6C7670]">{{ $kustom ? __('Harga disesuaikan') : __('Mulai dari') }}</p>
                <p class="mt-1 font-semibold leading-tight tabular-nums text-[#12301F] {{ $kustom ? 'text-xl' : 'text-[26px] sm:text-[30px]' }}">{{ $kustom ? __('Sesuai rancangan Anda') : $hargaTampil }}</p>
            </div>
            <div class="mx-6 mt-5 border-t border-[#E3E7E2] pt-5 lg:mx-0 lg:mt-0 lg:border-s lg:border-t-0 lg:px-8 lg:py-6">
                <h2 class="flex items-center gap-2 text-[13px] font-semibold text-[#12301F]"><span class="material-symbols-outlined text-lg text-[#1D7A56]" aria-hidden="true">calendar_month</span>{{ $kustom ? __('Jadwal fleksibel') : __('Tanggal tersedia') }}</h2>
                @if ($kustom)
                    <p class="mt-2 text-[13.5px] leading-relaxed text-[#5B6560]">{{ __('Tidak ada tanggal tetap. Anda yang menentukan hari keberangkatannya, kami menyesuaikan.') }}</p>
                @elseif ($jadwal->where('tersedia', true)->isEmpty())
                    <p class="mt-2 text-[13.5px] text-[#5B6560]">{{ __('Jadwal menyusul — belum ada tanggal tersedia.') }}</p>
                @else
                    <ul class="mt-2.5 flex flex-wrap gap-1.5">
                        @foreach ($jadwal as $opsi)
                            <li @class([
                                'rounded-full px-3 py-1 text-[12.5px] font-medium tabular-nums',
                                'bg-[#F1F4F0] text-[#12301F]' => $opsi['tersedia'],
                                'bg-[#F6F7F5] text-[#9AA39D] line-through decoration-[#B3412E]/60' => ! $opsi['tersedia'],
                            ]) @if (! $opsi['tersedia']) title="{{ __('Penuh') }}" @endif>
                                {{ \Illuminate\Support\Carbon::parse($opsi['tanggal'])->translatedFormat('d M Y') }}@if (! $opsi['tersedia'])<span class="sr-only"> · {{ __('Penuh') }}</span>@endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <div class="p-6 lg:py-6 lg:ps-0">
                <a href="{{ $urlBooking }}" class="inline-flex w-full items-center justify-center gap-2 whitespace-nowrap rounded-full bg-[#12301F] px-7 py-3.5 text-sm font-semibold text-white transition-colors hover:bg-[#1D7A56]">{{ $kustom ? __('Rancang Trip Kustom') : __('Booking Sekarang') }}<span class="material-symbols-outlined text-lg rtl:-scale-x-100" aria-hidden="true">arrow_forward</span></a>
            </div>
        </div>

        {{-- Tiga jaminan di bawah bar booking: satu kalimat singkat per poin, bertumpuk di HP --}}
        @php
            $jaminan = $kustom
                ? [['ikon' => 'route', 'isi' => __('Penawaran disusun setelah kami menerima rute dan jadwal Anda.')]]
                : array_values(array_filter([
                    ['ikon' => 'payments', 'isi' => __('Harga per booking, bukan dihitung per orang')],
                    ['ikon' => 'group', 'isi' => __('Maks. :maks tamu', ['maks' => $journey['maks_orang']])],
                    $journey['semua_termasuk'] ? ['ikon' => 'verified', 'isi' => __('Tiket masuk dan penginapan sudah termasuk')] : null,
                ]));
        @endphp
        <ul @class(['mx-3 mt-8 grid gap-6 sm:mx-6 lg:mx-12 lg:gap-0', 'md:grid-cols-3' => count($jaminan) === 3, 'md:grid-cols-2' => count($jaminan) === 2])>
            @foreach ($jaminan as $butir)
                <li class="flex items-center gap-4 lg:border-s lg:border-[#E3E7E2] lg:px-8 lg:first:border-s-0 lg:first:ps-0">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-white text-[#1D7A56] ring-1 ring-[#E3E7E2]"><span class="material-symbols-outlined text-[21px]" aria-hidden="true">{{ $butir['ikon'] }}</span></span>
                    <p class="text-[15px] font-medium leading-snug text-[#12301F]">{{ $butir['isi'] }}</p>
                </li>
            @endforeach
        </ul>

        {{-- TENTANG + pita highlight --}}
        <section class="mt-24" aria-labelledby="tentang-judul">
            <div class="grid gap-6 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] lg:gap-16">
                <h2 id="tentang-judul" class="{{ $judulBagian }}">{{ __('Tentang Perjalanan Ini') }}</h2>
                @if ($deskripsi !== '')
                    <div x-data="{ baca: false }">
                        <p class="whitespace-pre-line text-[16px] leading-[1.9] text-[#3F4A44] sm:text-[17px]" :class="baca ? '' : 'line-clamp-5'">{{ $deskripsi }}</p>
                        @if (mb_strlen($deskripsi) > 300)
                            <button type="button" x-on:click="baca = ! baca" class="mt-3 text-sm font-semibold text-[#12301F] underline decoration-[#1D7A56]/40 underline-offset-4 hover:decoration-[#1D7A56]" x-text="baca ? @js(__('Ringkas')) : @js(__('Baca selengkapnya'))">{{ __('Baca selengkapnya') }}</button>
                        @endif
                    </div>
                @endif
            </div>

            @if ($jumlahHighlight)
                <h3 class="mt-14 text-[13px] font-semibold text-[#6C7670]">{{ __('Highlight Perjalanan') }}</h3>
                <ul class="mt-4 grid gap-px overflow-hidden rounded-[24px] bg-[#E3E7E2] ring-1 ring-[#E3E7E2] sm:grid-cols-2 md:grid-cols-3 {{ $kolomHighlight }}">
                    @foreach ($journey['highlights'] as $i => $butir)
                        <li class="flex items-center gap-4 bg-white p-5 sm:flex-col sm:items-start sm:gap-5 sm:p-6">
                            <x-bersama.ikon-highlight :nama="$journey['highlight_ikon'][$i] ?? 'kilau'" class="h-8 w-8 text-[#1D7A56]" />
                            <span class="text-[15px] font-medium leading-snug text-[#141414]">{{ $butir }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- ITINERARY bergelombang --}}
        @if (count($journey['itinerary']))
            <section class="mt-24" aria-labelledby="itinerary-judul">
                <div class="grid gap-4 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] lg:items-end lg:gap-16">
                    <h2 id="itinerary-judul" class="{{ $judulBagian }}">{{ __('Itinerary') }}</h2>
                    <p class="max-w-[60ch] text-[15px] leading-relaxed text-[#5B6560]">{{ __('Kami menyediakan destinasinya; jam berangkat fleksibel mengikuti keinginan Anda. Urutan kunjungan dapat menyesuaikan kondisi lapangan dan cuaca.') }}</p>
                </div>

                <div class="relative mt-10">
                    <svg class="pointer-events-none absolute inset-x-0 top-0 hidden h-20 w-full lg:block" viewBox="0 0 1000 80" preserveAspectRatio="none" aria-hidden="true" focusable="false">
                        <path d="{{ $gelombang }}" fill="none" stroke="#1D7A56" stroke-opacity=".45" stroke-width="2" stroke-dasharray="7 7" vector-effect="non-scaling-stroke" />
                    </svg>
                    <ol class="relative grid gap-6 lg:gap-5" style="--hari: {{ $jumlahHari }}">
                        @foreach ($itineraryPerHari as $nomorHari => $barisHari)
                            <li class="itinerary-hari">
                                <div class="flex items-center gap-3 lg:h-20 lg:flex-col lg:justify-center lg:gap-0">
                                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-[#12301F] text-base font-semibold tabular-nums text-white ring-[6px] ring-[#F7F8F4]" aria-hidden="true">{{ $nomorHari ?: '•' }}</span>
                                    <span class="text-lg font-semibold text-[#12301F] lg:hidden">{{ $nomorHari ? __('Hari :nomor', ['nomor' => $nomorHari]) : __('Itinerary') }}</span>
                                </div>
                                <div class="mt-3 rounded-[22px] bg-white p-5 ring-1 ring-[#E3E7E2] sm:p-6">
                                    <div class="hidden items-baseline justify-between gap-3 lg:flex">
                                        <h3 class="text-[17px] font-semibold text-[#12301F]">{{ $nomorHari ? __('Hari :nomor', ['nomor' => $nomorHari]) : __('Itinerary') }}</h3>
                                        <span class="text-xs tabular-nums text-[#7A847E]">{{ __(':n destinasi', ['n' => $barisHari->count()]) }}</span>
                                    </div>
                                    <ul class="relative space-y-3.5 before:absolute before:inset-y-2 before:start-[5px] before:w-px before:bg-[#D7DED6] lg:mt-5">
                                        @foreach ($barisHari as $baris)
                                            @php $ujung = ($loop->first && $nomorHari === 1) || ($loop->last && $nomorHari === $hariTerakhir); @endphp
                                            <li class="relative flex items-start gap-3.5">
                                                <span @class(['relative mt-[5px] h-[11px] w-[11px] shrink-0 rounded-full border-2 border-[#1D7A56]', 'bg-[#1D7A56]' => $ujung, 'bg-white' => ! $ujung]) aria-hidden="true"></span>
                                                <span class="text-[14.5px] leading-snug text-[#141414]">@if ($baris['jam'] ?? null)<span class="me-1 font-semibold tabular-nums text-[#1D7A56]">{{ $baris['jam'] }}</span>@endif{{ $baris['x'] }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </section>
        @endif

        {{-- HOTEL: panel fitur lebar (foto · detail). Tanpa foto: foto journey digelapkan sebagai latar. --}}
        @if (count($hotel))
            <section class="mt-24" aria-labelledby="hotel-judul">
                <div class="grid gap-4 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] lg:items-end lg:gap-16">
                    <h2 id="hotel-judul" class="{{ $judulBagian }}">{{ __('Akomodasi Selama Perjalanan') }}</h2>
                    <p class="text-[15px] leading-relaxed text-[#5B6560]">{{ __('Hotel bintang :bintang pilihan MISTBORNE, sudah termasuk dalam harga. Jumlah kamar menyesuaikan jumlah tamu, maksimal :n orang per kamar.', ['bintang' => $bintangHotel, 'n' => max(1, (int) config('mistborne.biaya_perjalanan.tamu_per_kamar'))]) }}</p>
                </div>
                <div class="mt-10 grid gap-6">
                    @foreach ($hotel as $item)
                        @php $fotoHotel = collect($item['foto'] ? [['url' => $item['foto'], 'label' => null]] : [])->merge($item['galeri'] ?? [])->values(); @endphp
                        <article class="grid overflow-hidden rounded-[28px] bg-white ring-1 ring-[#E3E7E2] lg:grid-cols-2">
                            @if ($fotoHotel->isNotEmpty())
                                <div x-data="{ aktif: 0, foto: @js($fotoHotel->all()) }" class="relative min-h-[280px] lg:min-h-full">
                                    <img src="{{ $fotoHotel[0]['url'] }}" :src="foto[aktif].url" alt="{{ $item['nama'] }}" class="absolute inset-0 h-full w-full object-cover" width="1600" height="1000" loading="lazy" decoding="async">
                                    @if ($fotoHotel->count() > 1)
                                        <ul class="absolute inset-x-4 bottom-4 flex gap-2 overflow-x-auto" aria-label="{{ __('Galeri foto hotel') }}">
                                            @foreach ($fotoHotel as $indeks => $foto)
                                                <li class="shrink-0">
                                                    <button type="button" x-on:click="aktif = {{ $indeks }}" :aria-current="aktif === {{ $indeks }}" aria-label="{{ $foto['label'] ?? $item['nama'] }}" :class="aktif === {{ $indeks }} ? 'ring-2 ring-white' : 'opacity-75 hover:opacity-100'" class="block w-16 overflow-hidden rounded-lg transition">
                                                        <img src="{{ $foto['url'] }}" alt="{{ $foto['label'] ?? $item['nama'] }}" class="aspect-[4/3] w-full object-cover" width="160" height="120" loading="lazy" decoding="async">
                                                    </button>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            @else
                                <div class="relative flex min-h-[240px] items-end overflow-hidden bg-[#12301F] p-7 lg:min-h-full">
                                    <img src="{{ $journey['img'] }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-35 mix-blend-luminosity" width="1600" height="1000" loading="lazy" decoding="async">
                                    <p class="relative text-[13px] font-medium text-white/80">{{ __('Hotel bintang :n', ['n' => $item['bintang']]) }} · {{ $item['area'] }}</p>
                                </div>
                            @endif
                            <div class="p-6 sm:p-9">
                                <p class="flex items-center gap-2 text-sm text-[#5B6560]"><span class="tracking-[0.2em] text-[#1D7A56]" role="img" aria-label="{{ __('Hotel bintang :n', ['n' => $item['bintang']]) }}">{{ str_repeat('★', $item['bintang']) }}</span><span aria-hidden="true">·</span>{{ $item['area'] }}</p>
                                <h3 class="mt-2 text-[28px] font-semibold leading-tight tracking-tight text-[#12301F]"><a href="{{ $item['url'] }}" class="transition-colors hover:text-[#1D7A56]">{{ $item['nama'] }}</a></h3>
                                @if (! empty($item['deskripsi']))
                                    <p class="mt-4 text-[15px] leading-[1.8] text-[#4A544F]">{{ $item['deskripsi'] }}</p>
                                @endif
                                @if (! empty($item['jarak']))
                                    <p class="mt-5 border-s-2 border-[#1D7A56] ps-4 text-[14px] leading-snug text-[#141414]"><span class="font-semibold">{{ __('Estimasi perjalanan destinasi–hotel') }}:</span> {{ $item['jarak'] }}<span class="mt-1 block text-xs text-[#7A847E]">{{ __('Estimasi saja; bisa berubah karena lalu lintas dan kondisi jalan.') }}</span></p>
                                @endif
                                @if ($item['fasilitas'])
                                    <p class="mt-7 text-[13px] font-semibold text-[#6C7670]">{{ __('Fasilitas yang didapat') }}</p>
                                    <ul class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2.5 sm:grid-cols-3">
                                        @foreach ($item['fasilitas'] as $indeksFasilitas => $fasilitas)
                                            <li class="flex items-center gap-2 text-[13.5px] text-[#3E4743]">
                                                @if (isset($item['fasilitas_kunci'][$indeksFasilitas]))
                                                    <x-bersama.ikon-fasilitas :kunci="$item['fasilitas_kunci'][$indeksFasilitas]" class="h-4 w-4 text-[#1D7A56]" />
                                                @endif
                                                {{ $fasilitas }}
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                                <div class="mt-8 flex flex-wrap gap-3">
                                    <a href="{{ $item['url'] }}" class="inline-flex items-center gap-2 rounded-full bg-[#12301F] px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#1D7A56]">{{ __('Lihat detail hotel') }}<span class="material-symbols-outlined text-lg rtl:-scale-x-100" aria-hidden="true">arrow_forward</span></a>
                                    <a href="{{ $item['peta'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-full px-4 py-2.5 text-sm font-medium text-[#12301F] ring-1 ring-[#D7DED6] transition-colors hover:bg-[#F1F4F0]"><span class="material-symbols-outlined text-lg" aria-hidden="true">map</span>{{ __('Lihat lokasi di Google Maps') }}</a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- SEBELUM BERANGKAT: satu panel — lembar info praktis, termasuk/tidak, lalu empat kartu ketentuan satu paragraf --}}
        <section class="mt-24 rounded-[32px] bg-white p-6 ring-1 ring-[#E3E7E2] sm:p-10 lg:p-12" aria-label="{{ __('Info Praktis') }}">
            <div class="grid gap-12 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] lg:gap-16">
                <div aria-labelledby="praktis-judul">
                    <h2 id="praktis-judul" class="{{ $judulPanel }}">{{ __('Info Praktis') }}</h2>
                    <dl class="mt-5 border-t border-[#E3E7E2]">
                        @foreach ($praktis as $butir)
                            <div class="flex items-start justify-between gap-6 border-b border-[#E3E7E2] py-3.5">
                                <dt class="flex shrink-0 items-center gap-2.5 text-[14px] text-[#6C7670]"><span class="material-symbols-outlined text-[19px] text-[#1D7A56]" aria-hidden="true">{{ $butir['ikon'] }}</span>{{ $butir['label'] }}</dt>
                                <dd class="text-end text-[14.5px] font-semibold leading-snug text-[#12301F]">{{ $butir['nilai'] }}</dd>
                            </div>
                        @endforeach
                    </dl>
                    @if (filled($tips))
                        <p class="mt-5 text-[14px] leading-relaxed text-[#4A544F]"><span class="font-semibold text-[#12301F]">{{ __('Tips') }}.</span> {{ $tips }}</p>
                    @endif
                </div>

                <div aria-labelledby="termasuk-judul">
                    <h2 id="termasuk-judul" class="{{ $judulPanel }}">{{ __('Termasuk & Tidak Termasuk') }}</h2>
                    <div class="mt-5 grid gap-8 border-t border-[#E3E7E2] pt-5 sm:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
                        <div>
                            <h3 class="text-[13px] font-semibold text-[#1D5A3F]">{{ __('Termasuk dalam harga') }}</h3>
                            <ul class="mt-3.5 space-y-3">
                                @foreach (config('mistborne.termasuk') as $butir)
                                    <li class="flex items-start gap-3 text-[14px] leading-snug text-[#141414]"><x-bersama.ikon-centang class="mt-px" /><span>{{ __($butir) }}</span></li>
                                @endforeach
                            </ul>
                        </div>
                        <div>
                            <h3 class="text-[13px] font-semibold text-[#6C7670]">{{ __('Tidak termasuk (dibayar tamu)') }}</h3>
                            <ul class="mt-3.5 flex flex-wrap gap-2">
                                @foreach (config('mistborne.tidak_termasuk') as $butir)
                                    <li class="rounded-full bg-[#F1F4F0] px-3.5 py-1.5 text-[13.5px] leading-snug text-[#4A544F]">{{ __($butir) }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-14 border-t border-[#E3E7E2] pt-10" aria-labelledby="pembayaran-judul">
                <div class="flex flex-wrap items-baseline justify-between gap-x-6 gap-y-2">
                    <h2 id="pembayaran-judul" class="{{ $judulPanel }}">{{ __('Ketentuan & Pembayaran') }}</h2>
                    @if ($hargaAngka && ! $kustom)
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                            <p class="inline-flex items-center gap-2 rounded-full bg-[#12301F] px-4 py-2 text-[14px] text-white">{{ __('Bayar lunas') }}: <bdi class="font-semibold tabular-nums">{{ $hargaTampil }}</bdi></p>
                            <p class="text-[13px] text-[#7A847E]">{{ __('atau DP minimal :persen%', ['persen' => $persenDp]) }}: <bdi class="tabular-nums">{{ \App\Bantuan\FormatRupiah::dari((int) ceil($hargaAngka * $persenDp / 100)) }}</bdi></p>
                        </div>
                    @endif
                </div>
                <ol class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($ketentuan as $butir)
                        <li class="rounded-[22px] border border-[#E3E7E2] bg-white p-6">
                            <p class="text-[15px] leading-relaxed text-[#12301F]">{{ $butir }}</p>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>

    </article>

    @once
        <style>
            /* Itinerary: desktop menyamping (satu kolom per hari); HP bertumpuk dengan garis bergelombang vertikal di kiri. */
            @media (min-width: 1024px) {
                .itinerary-hari { min-width: 0; }
                ol[style*='--hari'] { grid-template-columns: repeat(var(--hari), minmax(0, 1fr)); }
            }
            @media (max-width: 1023px) {
                .itinerary-hari { position: relative; }
                .itinerary-hari > div:last-child { margin-inline-start: 60px; }
                .itinerary-hari:not(:last-child)::after {
                    content: ''; position: absolute; top: 52px; bottom: -20px; inset-inline-start: 18px; width: 12px;
                    background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='28' viewBox='0 0 12 28'%3E%3Cpath d='M6 0 C 12 7, 0 21, 6 28' fill='none' stroke='%231D7A56' stroke-opacity='.45' stroke-width='2'/%3E%3C/svg%3E") repeat-y;
                }
            }
        </style>
    @endonce
</x-tata-letak.aplikasi>
