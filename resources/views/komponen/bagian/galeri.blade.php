@php
    // KONSTANTA DESAIN section ini — warna yang dipakai berulang didefinisikan sekali di sini.
    $hijauTua = warna('hijauTua');
    $abuGaris = warna('abuGarisMuda');
    $hijauPucat = warna('hijauPucat');
@endphp
{{-- GALERI (#galeri): bento grid dengan tab filter kategori. --}}
@php
    $gallery = [
        ['img' => '/images/jeepbromo.jpg', 'alt' => __('Jeep Trip Privat di Hamparan Savana'), 'title' => __('Jeep Trip Privat di Hamparan Savana'), 'badge' => __('Trip Privat'), 'category' => 'perjalanan'],
        ['img' => '/images/foto-ciwidey.avif', 'alt' => __('Jalan berkelok menembus perkebunan teh di dataran tinggi'), 'title' => __('Jalan Berkelok Kebun Teh'), 'badge' => __('Pemandangan'), 'category' => 'pemandangan'],
        ['img' => '/images/bromosunrise.jpg', 'alt' => __('Kawah Bromo diselimuti kabut pagi dari kejauhan'), 'title' => __('Kawah Bromo Saat Fajar'), 'badge' => __('Pemandangan'), 'category' => 'pemandangan'],
        ['img' => '/images/destinasi-kawahijen2.avif', 'alt' => __('Tamu berjalan menuju kawah Ijen bersama rombongan wisatawan lain'), 'title' => __('Tamu Trip Menuju Kawah Ijen'), 'badge' => __('Pelanggan'), 'category' => 'pelanggan'],
        ['img' => '/images/kawah-ijen.avif', 'alt' => __('Danau kawah Ijen berwarna toska dengan asap belerang'), 'title' => __('Kawah Ijen yang Berasap'), 'badge' => __('Destinasi'), 'category' => 'pemandangan perjalanan'],
        ['img' => '/images/foto-dieng.avif', 'alt' => __('Lanskap pegunungan dataran tinggi Dieng, Wonosobo'), 'title' => __('Lanskap Dataran Tinggi Dieng'), 'badge' => __('Pemandangan'), 'category' => 'pemandangan'],
        ['img' => '/images/gunung-papandayan.avif', 'alt' => __('Kawah Gunung Papandayan dengan uap belerang mengepul'), 'title' => __('Kawah Gunung Papandayan'), 'badge' => __('Perjalanan'), 'category' => 'perjalanan pemandangan'],
        ['img' => '/images/lembang-tangkuban-parahu.webp', 'alt' => __('Sawah dan desa di kaki Gunung Tangkuban Parahu saat matahari pagi'), 'title' => __('Pagi di Kaki Tangkuban Parahu'), 'badge' => __('Pemandangan'), 'category' => 'pemandangan'],
    ];
@endphp
<section class="mx-auto max-w-[1200px] scroll-mt-20 px-8 py-12" id="galeri" aria-labelledby="galeri-judul">
    <div data-reveal="up" class="mx-auto mb-5 max-w-[560px] text-center">
        <h2 class="font-serif text-3xl font-medium tracking-tight text-[#12301F] md:text-4xl" id="galeri-judul">{{ __('Galeri Mistborne') }}</h2>
        <p class="mt-1.5 text-base text-gray-600">{{ __('Potret perjalanan dan momen nyata bersama tamu kami di dataran tinggi.') }}</p>
    </div>
    <div class="mt-8 grid auto-rows-[200px] grid-cols-1 gap-3 pb-2 sm:grid-cols-2 md:grid-cols-4">
        @foreach ($gallery as $i => $item)
            <x-bersama.kartu-galeri
                :item="$item"
                :index="$i"
                data-reveal="scale"
                data-reveal-delay="{{ ($loop->index % 4) * 80 }}"
            />
        @endforeach
    </div>
    <div data-reveal="up" class="mt-8 text-center">
        <a class="inline-flex items-center justify-center rounded-full border border-[#C2C8C1]/60 bg-white px-8 py-3.5 text-sm font-semibold text-[#12301F] shadow-xs transition-all duration-200 hover:bg-[#EBEFE9] hover:text-[#0F2016]" href="{{ route('gallery.index') }}">{{ __('Lihat Semua Foto') }} →</a>
    </div>
</section>
