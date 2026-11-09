{{-- PROMO APLIKASI (#aplikasi): banner gambar jadi (public/images/app-banner.png). --}}
<section class="mx-auto max-w-[1200px] scroll-mt-20 px-4 py-12 sm:px-8 md:py-16" id="aplikasi" aria-labelledby="aplikasi-judul">
    <figure data-reveal="up" class="relative overflow-hidden rounded-[28px] shadow-[0_24px_60px_-24px_rgba(20,35,27,0.35)]">
        <img
            src="/images/app-banner.png"
            alt="{{ __('Banner aplikasi MISTBORNE: kiri prompt Mau Liburan Tapi Bingung Mulai dari Mana, kanan foto landscape dan dua tampilan aplikasi.') }}"
            width="1600"
            height="749"
            loading="lazy"
            decoding="async"
            class="h-full w-full object-cover"
        />
        <figcaption class="sr-only" id="aplikasi-judul">{{ __('Mau Liburan Tapi Bingung Mulai dari Mana? — MISTBORNE APP, segera hadir.') }}</figcaption>
        {{-- Satu area klik transparan menutupi tombol CTA di dalam gambar (tanpa dekorasi tambahan) --}}
        <a
            class="absolute left-[4%] top-[70%] h-[11%] w-[24%] rounded-full transition-opacity hover:opacity-90"
            href="#destinasi"
            aria-label="{{ __('Temukan Trip Sekarang') }}"
        ></a>
    </figure>
</section>
