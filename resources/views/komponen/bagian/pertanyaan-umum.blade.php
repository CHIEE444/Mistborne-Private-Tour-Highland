{{-- 11. FAQ (#faq) — v2 mengikuti referensi: kiri judul + kontak WhatsApp, kanan accordion berikon (3 pertanyaan paling umum), item pertama terbuka; JSON-LD FAQPage DIBANGUN DARI array $faqs yang sama (teks identik, ikut bahasa aktif) --}}
@php
    $faqs = [
        ['q' => __('Apa saja yang termasuk dalam satu booking perjalanan?'), 'a' => __('Satu booking adalah satu kelompok privat dengan sistem all-inclusive: sopir lokal berpengalaman, transportasi dan BBM, penginapan bintang 4 (4 orang per kamar), serta tiket masuk objek wisata sudah termasuk. Anda tidak digabung dengan rombongan lain dan tidak ada biaya mendadak di lokasi.')],
        ['q' => __('Bagaimana sistem pembayaran dan deposit?'), 'a' => __('Reservasi dikunci setelah pembayaran uang muka (Down Payment) sebesar 30%. Batas pembayaran awal dihitung setelah admin mengonfirmasi pesanan. Sisa tagihan dapat dilunasi paling lambat H-3 (tiga hari sebelum keberangkatan) melalui rekening resmi MISTBORNE.')],
        ['q' => __('Bagaimana kebijakan pembatalan & reschedule?'), 'a' => __('Ganti jadwal dapat diajukan lebih dari H-7 sebelum keberangkatan, bergantung pada ketersediaan. Ganti jadwal pertama dikenakan biaya sesuai ketentuan; perubahan berikutnya gratis. Perubahan jadwal tidak dapat diajukan mulai H-7. Pembatalan lebih dari H-7 mendapat refund hingga 50% dari harga, maksimal sebesar pembayaran yang telah diterima; pembatalan H-7 atau kurang tidak mendapat refund. Jika pembatalan dilakukan MISTBORNE karena cuaca atau bencana, pembayaran yang telah diterima dikembalikan 100%.')],
    ];

    // Kontak FAQ: nomor WhatsApp admin dari pengaturan (saat ini 0818-0860-6086), ditampilkan format +62.
    $nomorFaq = preg_replace('/\D+/', '', (string) \App\Models\Pengaturan::current()->nomor_whatsapp_admin);
    $nomorFaqTampil = strlen($nomorFaq) >= 10
        ? '+'.substr($nomorFaq, 0, 2).' '.substr($nomorFaq, 2, 3).'-'.substr($nomorFaq, 5, 4).'-'.substr($nomorFaq, 9)
        : '+'.$nomorFaq;
    $faqLd = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'inLanguage' => app()->getLocale(),
        'mainEntity' => collect($faqs)->map(fn ($faq) => [
            '@type' => 'Question',
            'name' => $faq['q'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['a']],
        ])->values()->all(),
    ];
@endphp
<x-seo.json-ld :data="$faqLd" />
{{-- Tata letak mengikuti referensi FAQ terbaru: latar putih polos, judul besar dua baris di kiri, daftar pertanyaan
     berjarak lega di kanan dengan tombol kotak. Kontak WhatsApp tetap tersedia lewat tautan kecil di bawah judul. --}}
<section class="scroll-mt-20 bg-white py-20 md:py-28" id="faq" aria-labelledby="faq-judul">
    <div class="mx-auto grid max-w-[1320px] gap-12 px-5 sm:px-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] lg:gap-16 lg:px-12">
        <div data-reveal="up">
            <p class="text-[15px] text-[#6B736F]">{{ __('Pertanyaan Umum') }}</p>
            <h2 class="mt-4 text-[clamp(36px,2.9vw,54px)] font-semibold leading-[1.04] tracking-[-0.035em] text-[#1C2421]" id="faq-judul">
                <span class="block lg:whitespace-nowrap">{{ __('Ada pertanyaan?') }}</span>
                <span class="block lg:whitespace-nowrap">{{ __('Kami siap menjawab.') }}</span>
            </h2>
            <a href="https://wa.me/{{ $nomorFaq }}" target="_blank" rel="noopener noreferrer" class="mt-8 inline-flex items-center gap-2 text-sm font-medium text-[#1C2421] underline decoration-[#C9D6C1] underline-offset-4 transition-colors hover:decoration-[#1C2421]">{{ __('Masih ragu? Tanya admin via WhatsApp') }} <span dir="ltr" class="tabular-nums text-[#6B736F]">{{ $nomorFaqTampil }}</span></a>
        </div>

        <div class="space-y-12 md:space-y-14">
            @foreach ($faqs as $i => $faq)
                <div data-reveal="up" data-reveal-delay="{{ ($loop->index % 4) * 60 }}">
                    <x-bersama.item-pertanyaan :faq="$faq" :index="$i" :open="$i === 0" />
                </div>
            @endforeach
        </div>
    </div>
</section>
