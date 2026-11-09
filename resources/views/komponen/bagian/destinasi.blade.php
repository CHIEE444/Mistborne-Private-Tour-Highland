@php
    // KONSTANTA DESAIN section ini — warna yang dipakai berulang didefinisikan sekali di sini.
    $hijauMalam = warna('hijauGelap');
    $hijauKabut = warna('hijauKabut');
@endphp
{{-- 4. DESTINASI POPULER (#destinasi) — grid editorial: kartu hero tinggi kiri + 2 kartu atas & 3 kartu bawah di kanan.
     Hanya destinasi dataran tinggi Jawa yang benar-benar ada di journey/paket MISTBORNE; tiap kartu menuju detail
     journey-nya dan menampilkan provinsinya (tanpa angka aktivitas karangan). --}}@php
    $slugJourney = \App\Models\Perjalanan::query()->whereIn('nama', [
        'BROMO SUNRISE JOURNEY', 'DIENG HIGHLAND ESCAPE', 'CIWIDEY & RANCABALI ESCAPE', 'IJEN VOLCANIC ESCAPE',
        'BOROBUDUR & MERAPI SUNRISE JOURNEY', 'LEMBANG & TANGKUBAN PARAHU JOURNEY',
    ])->pluck('slug', 'nama');
    $tautan = fn (string $nama): string => isset($slugJourney[$nama]) ? route('journeys.show', $slugJourney[$nama]) : route('journeys.index');
    $destinationHero = [
        'name' => __('Bromo'), 'region' => __('Jawa Timur'), 'img' => '/images/bromo-semeru-sunrise.webp', 'href' => $tautan('BROMO SUNRISE JOURNEY'),
        'alt' => __('Matahari terbit di atas kaldera Bromo dengan Gunung Semeru di kejauhan'),
    ];
    $destinationsTop = [
        ['name' => __('Dieng'), 'region' => __('Jawa Tengah'), 'img' => '/images/foto-dieng.avif', 'href' => $tautan('DIENG HIGHLAND ESCAPE'), 'alt' => __('Lanskap pegunungan dataran tinggi Dieng, Wonosobo')],
        ['name' => __('Kawah Putih'), 'region' => __('Jawa Barat'), 'img' => '/images/kawah-putih-ciwidey.webp', 'href' => $tautan('CIWIDEY & RANCABALI ESCAPE'), 'alt' => __('Danau Kawah Putih Ciwidey berwarna toska dikelilingi hutan')],
    ];
    $destinationsBottom = [
        ['name' => __('Kawah Ijen'), 'region' => __('Jawa Timur'), 'img' => '/images/kawah-ijen.avif', 'href' => $tautan('IJEN VOLCANIC ESCAPE'), 'alt' => __('Danau kawah Ijen berwarna toska dengan asap belerang')],
        ['name' => __('Borobudur'), 'region' => __('Jawa Tengah'), 'img' => '/images/borobudur-stupa.webp', 'href' => $tautan('BOROBUDUR & MERAPI SUNRISE JOURNEY'), 'alt' => __('Deretan stupa Candi Borobudur dengan perbukitan Menoreh di kejauhan')],
        ['name' => __('Tangkuban Parahu'), 'region' => __('Jawa Barat'), 'img' => '/images/lembang-tangkuban-parahu.webp', 'href' => $tautan('LEMBANG & TANGKUBAN PARAHU JOURNEY'), 'alt' => __('Sawah dan desa di kaki Gunung Tangkuban Parahu saat matahari pagi')],
    ];
@endphp
<section class="mx-auto max-w-[1400px] scroll-mt-20 px-4 py-12 sm:px-8 md:py-20" id="destinasi" aria-labelledby="destinasi-judul">
    {{-- Header rata tengah: eyebrow + garis dekoratif + heading + deskripsi --}}
    <div data-reveal="up" class="mx-auto max-w-[700px] text-center">
        <h2 class="mt-4 font-serif text-3xl font-bold tracking-tight leading-tight text-[#12301F] md:text-[40px]" id="destinasi-judul">{{ __('Jelajahi Dataran Tinggi Jawa') }}</h2>
        <p class="mx-auto mt-4 max-w-[660px] text-[17px] leading-relaxed text-gray-500">{{ __('Dari kabut Dieng hingga kawah toska Ijen, pilih destinasi yang ingin Anda nikmati dengan ritme Anda sendiri.') }}</p>
    </div>

    {{-- Grid editorial: hero 2 baris kiri + kanan (atas 2 kartu, bawah 3 kartu) --}}
    <div data-reveal="up" data-reveal-delay="100" class="mt-10 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-12">
        {{-- Hero: Bromo --}}
        <a
            href="{{ $destinationHero['href'] }}"
            aria-label="{{ __('Lihat perjalanan di') }} {{ $destinationHero['name'] }}"
            class="group relative h-80 overflow-hidden rounded-2xl sm:col-span-2 sm:h-[420px] lg:col-span-4 lg:row-span-2 lg:h-auto"
        >
            <img
                src="{{ $destinationHero['img'] }}"
                alt="{{ $destinationHero['alt'] }}"
                width="1600"
                height="1067"
                loading="lazy"
                decoding="async"
                class="absolute inset-0 h-full w-full object-cover transition-transform duration-[350ms] ease-out group-hover:scale-[1.03]"
            />
            <span class="absolute inset-0 bg-gradient-to-t from-[#0B1F14]/70 via-transparent to-transparent transition-opacity duration-300 group-hover:from-[#0B1F14]/80" aria-hidden="true"></span>
            <span class="absolute inset-x-0 bottom-0 block p-6 text-white sm:p-7">
                <span class="block font-serif text-2xl font-semibold tracking-tight sm:text-[24px]">{{ $destinationHero['name'] }}</span>
                <span class="mt-1 block text-base text-white/80">{{ $destinationHero['region'] }}</span>
            </span>
        </a>

        {{-- Kanan: 2 kartu atas + 3 kartu bawah --}}
        <div class="flex flex-col gap-4 sm:col-span-2 lg:col-span-8">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                @foreach ($destinationsTop as $d)
                    <a
                        href="{{ $d['href'] }}"
                        aria-label="{{ __('Lihat perjalanan di') }} {{ $d['name'] }}"
                        class="group relative h-64 overflow-hidden rounded-2xl sm:h-72 lg:h-[290px]"
                    >
                        <img
                            src="{{ $d['img'] }}"
                            alt="{{ $d['alt'] }}"
                            width="1200"
                            height="800"
                            loading="lazy"
                            decoding="async"
                            class="absolute inset-0 h-full w-full object-cover transition-transform duration-[350ms] ease-out group-hover:scale-[1.03]"
                        />
                        <span class="absolute inset-0 bg-gradient-to-t from-[#0B1F14]/70 via-transparent to-transparent transition-opacity duration-300 group-hover:from-[#0B1F14]/80" aria-hidden="true"></span>
                        <span class="absolute inset-x-0 bottom-0 block p-5 text-white sm:p-6">
                            <span class="block text-xl font-semibold tracking-tight sm:text-[22px]">{{ $d['name'] }}</span>
                            <span class="mt-1 block text-[15px] text-white/80">{{ $d['region'] }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                @foreach ($destinationsBottom as $d)
                    <a
                        href="{{ $d['href'] }}"
                        aria-label="{{ __('Lihat perjalanan di') }} {{ $d['name'] }}"
                        class="group relative h-56 overflow-hidden rounded-2xl sm:h-64 lg:h-[300px]"
                    >
                        <img
                            src="{{ $d['img'] }}"
                            alt="{{ $d['alt'] }}"
                            width="1200"
                            height="800"
                            loading="lazy"
                            decoding="async"
                            class="absolute inset-0 h-full w-full object-cover transition-transform duration-[350ms] ease-out group-hover:scale-[1.03]"
                        />
                        <span class="absolute inset-0 bg-gradient-to-t from-[#0B1F14]/70 via-transparent to-transparent transition-opacity duration-300 group-hover:from-[#0B1F14]/80" aria-hidden="true"></span>
                        <span class="absolute inset-x-0 bottom-0 block p-5 text-white">
                            <span class="block text-xl font-semibold tracking-tight">{{ $d['name'] }}</span>
                            <span class="mt-1 block text-[15px] text-white/80">{{ $d['region'] }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</section>
