@php
    // Nomor hotline dari pengaturan admin (format +62 xxx-xxxx-xxxx).
    $nomorAdmin = preg_replace('/\D+/', '', (string) \App\Models\Pengaturan::current()->nomor_whatsapp_admin);

    // Tiga janji khas yang memang berlaku di sistem: trip privat, jadwal fleksibel, dan all-inclusive.
    $janji = [
        ['label' => __('Privat'), 'judul' => __('Satu booking, satu rombongan'), 'isi' => __('Trip sepenuhnya privat. Anda tidak pernah digabung dengan tamu lain.')],
        ['label' => __('Fleksibel'), 'judul' => __('Jadwal mengikuti ritme Anda'), 'isi' => __('Jam jemput Anda tentukan sendiri, dan urutan kunjungan bisa menyesuaikan kondisi serta keinginan rombongan.')],
        ['label' => __('All-inclusive'), 'judul' => __('Sekali bayar, semua beres'), 'isi' => __('Transportasi, penginapan, dan tiket masuk sudah termasuk. Tanpa biaya mendadak.')],
    ];
@endphp
{{-- TENTANG KAMI (v5, mengikuti referensi halaman "About us"): foto panorama dengan judul raksasa, pengantar di tengah,
     lalu tiga nilai bersanding foto pemandu. --}}
<section class="tk relative bg-white pb-20 pt-6 sm:pb-28 text-[#12231A] sm:pt-10" id="tentang-kami" aria-labelledby="tentang-kami-judul">
    {{-- 1. Foto panorama + judul raksasa yang menumpang di bagian foto yang memudar ke putih --}}
    <div class="mx-auto max-w-[1320px] px-4 sm:px-8">
        <div class="relative overflow-hidden rounded-[28px]">
            <img
                src="{{ asset('images/tentang-savana-bromo.jpg') }}"
                srcset="{{ asset('images/tentang-savana-bromo-1200.jpg') }} 1200w, {{ asset('images/tentang-savana-bromo.jpg') }} 2400w"
                sizes="(min-width: 1320px) 1260px, 94vw"
                width="2400" height="1600" loading="lazy" decoding="async"
                alt="{{ __('Lembah Savana Bromo yang hijau keemasan diapit dinding kaldera') }}"
                class="h-[clamp(420px,58vw,760px)] w-full object-cover object-[50%_30%]"
            >
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-b from-transparent from-35% via-white/70 via-75% to-white" aria-hidden="true"></div>
            <h2 id="tentang-kami-judul" class="absolute inset-x-0 bottom-[7%] whitespace-nowrap text-center text-[clamp(44px,10.4vw,176px)] font-semibold leading-none tracking-[-0.05em] text-[#14211A]" data-reveal="up">{{ __('Tentang Kami') }}</h2>
        </div>
    </div>

    {{-- 2. Pengantar di tengah --}}
    <div class="mx-auto mt-16 max-w-[640px] px-5 text-center sm:mt-20" data-reveal="up">
        <p class="text-[13px] font-medium text-[#6B756F]">MISTBORNE</p>
        <p class="mt-3 text-[clamp(28px,4vw,44px)] font-medium leading-[1.12] tracking-[-0.025em] text-[#14211A]">{{ __('Dari kabut pagi hingga cerita yang Anda bawa pulang.') }}</p>
        <p class="mx-auto mt-6 max-w-[52ch] text-[14.5px] leading-relaxed text-[#5B6560]">{{ __('MISTBORNE lahir dari satu keyakinan sederhana: dataran tinggi Jawa paling indah dinikmati dengan tenang, berangkat sebelum subuh, menembus kabut, tanpa rombongan lain.') }}</p>
        <p class="mx-auto mt-4 max-w-[52ch] text-[14.5px] leading-relaxed text-[#5B6560]">{{ __('Kami merancang trip privat ke Dieng, Bromo, dan dataran tinggi lainnya, lengkap dengan pemandu lokal, mobil sesuai medan, dan penginapan, supaya Anda cukup menikmati perjalanannya.') }}</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="https://wa.me/{{ $nomorAdmin }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-md border border-[#14211A] px-4 py-2 text-[13px] font-medium text-[#14211A] transition-colors hover:bg-[#14211A] hover:text-white">{{ __('Hubungi kami') }} <span aria-hidden="true" class="rtl:-scale-x-100">→</span></a>
            <a href="mailto:travelling@mistborne.id" class="inline-flex items-center rounded-md border border-[#D3DCD2] px-4 py-2 text-[13px] font-medium text-[#14211A] transition-colors hover:bg-[#EEF3EC]">travelling@mistborne.id</a>
        </div>
    </div>

    {{-- 3. Tiga nilai bersanding foto pemandu --}}
    <div class="mx-auto mt-20 grid max-w-[1200px] items-center gap-12 px-5 sm:px-8 lg:mt-32 lg:grid-cols-2 lg:gap-16">
        <ul class="divide-y divide-[#E3E7E2]" role="list">
            @foreach ($janji as $i => $butir)
                <li @class(['py-7 first:pt-0', 'opacity-55' => $i > 0]) data-reveal="up" data-reveal-delay="{{ 60 + $i * 80 }}">
                    <span class="block text-[12px] font-medium text-[#6B756F]">{{ $butir['label'] }}</span>
                    <h3 class="mt-2 text-[clamp(22px,2.4vw,30px)] font-medium leading-tight tracking-[-0.02em] text-[#14211A]">{{ $butir['judul'] }}</h3>
                    <p class="mt-2 max-w-[44ch] text-[14px] leading-relaxed text-[#5B6560]">{{ $butir['isi'] }}</p>
                </li>
            @endforeach
        </ul>
        <div class="overflow-hidden rounded-[22px]" data-reveal="up" data-reveal-delay="160">
            <img src="{{ asset('images/guide.jpg') }}" width="1000" height="1500" loading="lazy" decoding="async" alt="{{ __('Pemandu lokal MISTBORNE menemani tamu di jalur pegunungan') }}" class="aspect-[5/4] w-full object-cover object-[50%_35%]">
        </div>
    </div>

</section>
