@php
    // KONSTANTA DESAIN section ini — warna yang dipakai berulang didefinisikan sekali di sini.
    $hijauTua = warna('hijauTua');
    $teksUtama = warna('teksUtama');
    $hijauHutan = warna('hijauHutan');
    $emas = warna('emas');
@endphp
{{-- KENAPA MISTBORNE (#keunggulan): judul dan paragraf di kiri, foto lengkung di kanan, tiga kolom keunggulan. --}}

<section class="relative scroll-mt-20 overflow-hidden py-16 lg:py-20" id="keunggulan" aria-labelledby="keunggulan-judul">
    <div class="relative mx-auto max-w-[1200px] px-8">
        <div class="grid items-center gap-8 lg:grid-cols-12 lg:gap-10">
            {{-- Kiri: heading besar + subjudul + paragraf ringkas + CTA --}}
            <div class="lg:col-span-6">
                <div data-reveal="up">
                    <h2 class="mt-4 text-balance font-serif text-3xl font-medium tracking-tight leading-tight text-[#14231B] sm:text-4xl lg:text-[44px]" id="keunggulan-judul">{{ __('Trip Privat, Bukan Sekadar Perjalanan') }}</h2>
                    <p class="mt-4 max-w-[520px] text-base leading-relaxed text-gray-600">{{ __('Satu Pesanan untuk satu kelompok: sopir, rute, dan itinerary disiapkan khusus untuk Anda di dataran tinggi.') }}</p>
                    <p class="mt-3 max-w-[520px] text-sm leading-relaxed text-[#3B5A48]">{{ __('Berangkat pagi buta dari hotel Anda, pulang dengan ritme sendiri.') }}</p>
                    <div class="mt-6">
                        <a class="btn-shine inline-flex items-center gap-2 rounded-full bg-[#12301F] px-6 py-3 text-sm font-semibold text-white shadow-sm transition-all hover:bg-[#1D7A56] active:scale-[.98]" href="#journeys">
                            <span>{{ __('Lihat paket perjalanan') }}</span>
                            <span class="material-symbols-outlined text-base text-white rtl:-scale-x-100" aria-hidden="true">arrow_forward</span>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Kanan: foto bingkai arch ber-kabut dengan aksen emas tipis --}}
            <div data-reveal="right" class="lg:col-span-6">
                <div class="relative mx-auto w-full max-w-[420px]">
                    {{-- Glow emas lembut di belakang foto (dekoratif, statis) --}}
                    <div class="pointer-events-none absolute -top-8 -end-6 h-40 w-40 rounded-full bg-[#12301F]/15 blur-3xl" aria-hidden="true"></div>
                    <div class="pointer-events-none absolute -bottom-8 -start-6 h-40 w-40 rounded-full bg-[#1B422B]/10 blur-3xl" aria-hidden="true"></div>
                    {{-- Bingkai arch: border emas tipis + foto membulat penuh di atas --}}
                    <div class="relative rounded-t-full rounded-b-[28px] border border-[#C8A24A]/40 bg-white p-2 shadow-[0_20px_48px_rgba(20,35,27,0.12)]">
                        <div class="relative overflow-hidden rounded-t-full rounded-b-[22px] bg-gradient-to-br from-[#1B422B] via-[#12301F] to-[#0C1A12]">
                            <img src="{{ asset('images/bromo2.jpg') }}" alt="{{ __('Jalan berkelok di tengah perkebunan teh dataran tinggi') }}" loading="lazy" decoding="async" width="1059" height="1341" class="h-[380px] w-full object-cover lg:h-[440px]" />
                            {{-- Kabut halus di dasar foto --}}
                            <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-[#C8A24A]/35 via-transparent to-transparent" aria-hidden="true"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tiga kolom keunggulan: hairline hijau/abu-hijau, tanpa penomoran/ikon --}}
        <div class="mt-8 grid grid-cols-1 gap-x-10 gap-y-8 md:grid-cols-3 lg:mt-10">
            <div data-reveal="up" class="border-t border-[#12301F]/15 pt-5">
                <h3 class="font-sans text-base font-semibold tracking-tight text-[#14231B]">{{ __('Sopir yang hafal jalurnya') }}</h3>
                <p class="mt-1.5 max-w-[38ch] text-sm leading-relaxed text-gray-600">{{ __('Jalur ke Dieng menanjak dan sering berkabut sebelum matahari terbit. Sopir kami orang lokal yang sudah biasa melewatinya.') }}</p>
            </div>
            <div data-reveal="up" data-reveal-delay="80" class="border-t border-[#12301F]/15 pt-5">
                <h3 class="font-sans text-base font-semibold tracking-tight text-[#14231B]">{{ __('Harga sudah termasuk: tiket, penginapan, dan transportasi') }}</h3>
                <p class="mt-1.5 max-w-[38ch] text-sm leading-relaxed text-gray-600">{{ __('Tamu cukup membayar sekali. Yang dibayar sendiri hanya makan dan minum, oleh-oleh, serta tip. Rinciannya ada di tiap paket.') }}</p>
            </div>
            <div data-reveal="up" data-reveal-delay="160" class="border-t border-[#12301F]/15 pt-5">
                <h3 class="font-sans text-base font-semibold tracking-tight text-[#14231B]">{{ __('Tanya dulu lewat WhatsApp') }}</h3>
                <p class="mt-1.5 max-w-[38ch] text-sm leading-relaxed text-gray-600">
                    {{ __('Pesan dan pertanyaan soal jalur, cuaca, atau barang bawaan bisa langsung lewat') }}
                    <x-bersama.tautan-wa class="text-[#12301F] font-medium underline decoration-[#12301F]/25 underline-offset-4 transition-colors hover:text-[#C8A24A] hover:decoration-[#C8A24A]/40" aria-label="{{ __('Tanya dulu lewat WhatsApp') }}">{{ __('WhatsApp') }}</x-bersama.tautan-wa>.
                </p>
            </div>
        </div>
    </div>
</section>
