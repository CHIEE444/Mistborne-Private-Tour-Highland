{{-- HALAMAN GALERI (/galeri): grid foto seragam 4:3 dengan tombol kembali ke beranda. --}}
@php
    $galleryPhotos = [
        ['img' => '/images/kabut.avif', 'alt' => __('Kabut pagi menyelimuti hutan dataran tinggi')],
        ['img' => '/images/kawah-putih-ciwidey.webp', 'alt' => __('Danau Kawah Putih Ciwidey berwarna toska dikelilingi hutan')],
        ['img' => '/images/hero-2.jpg', 'alt' => __('Fajar emas di puncak Sikunir')],
        ['img' => '/images/hero-3.jpg', 'alt' => __('Trip privat menembus kabut pegunungan')],
        ['img' => '/images/lembang-tangkuban-parahu.webp', 'alt' => __('Sawah dan desa di kaki Gunung Tangkuban Parahu saat matahari pagi')],
        ['img' => '/images/jawa-tengah.jpg', 'alt' => __('Gunung Merapi di atas lautan awan saat matahari terbit')],
        ['img' => '/images/green-canyon-cukang-taneuh.webp', 'alt' => __('Sungai hijau toska di antara tebing Green Canyon, Pangandaran')],
        ['img' => '/images/borobudur-stupa.webp', 'alt' => __('Deretan stupa Candi Borobudur dengan perbukitan Menoreh di kejauhan')],
        ['img' => '/images/hutan-pinus.webp', 'alt' => __('Pohon pinus menjulang ke langit pagi')],
        ['img' => '/images/kawah-rengganis.webp', 'alt' => __('Uap panas Kawah Rengganis di lembah hijau Ciwidey')],
        ['img' => '/images/sikunir-sindoro-pagi.webp', 'alt' => __('Gunung Sindoro di atas lautan kabut dilihat dari Bukit Sikunir')],
    ];
@endphp
<x-tata-letak.aplikasi>
    {{-- Senada dengan Semua Perjalanan dan Semua Artikel: tombol kembali ke beranda, label kecil + judul besar, lalu grid foto.
         Desain sebelumnya dicadangkan di storage/app/private/cadangan-desain. --}}
    <section class="scroll-mt-20 bg-white pb-20 pt-28 md:pb-24 md:pt-36" id="galeri" aria-labelledby="galeri-judul">
        <div class="mx-auto max-w-[1320px] px-5 sm:px-8 lg:px-12">
            <x-bersama.tombol-beranda class="mb-10 md:mb-12" />

            <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="text-[15px] text-[#6B736F]">{{ __('Galeri') }}</p>
                    <h1 class="mt-3 text-[clamp(40px,5.4vw,76px)] font-semibold leading-[1.0] tracking-[-0.04em] text-[#1C2421]" id="galeri-judul">{{ __('Galeri Mistborne') }}</h1>
                </div>
                <p class="max-w-[420px] text-[15px] leading-relaxed text-[#6B736F] md:pb-2">{{ __('Arsip lengkap potret perjalanan, lanskap, dan momen bersama tamu kami di seluruh Indonesia.') }}</p>
            </div>

            <div class="mt-12 grid grid-cols-1 gap-6 border-t border-[#E3E7E2] pt-12 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($galleryPhotos as $photo)
                    <figure data-reveal="up" data-reveal-delay="{{ ($loop->index % 3) * 60 }}" class="group overflow-hidden rounded-[22px] bg-[#EEF1EC]">
                        <img
                            src="{{ $photo['img'] }}"
                            alt="{{ $photo['alt'] }}"
                            width="1200"
                            height="900"
                            loading="lazy"
                            decoding="async"
                            class="aspect-[4/3] w-full object-cover transition-transform duration-700 group-hover:scale-[1.04]"
                        />
                    </figure>
                @endforeach
            </div>
        </div>
    </section>
</x-tata-letak.aplikasi>
