{{-- FOOTER: latar foto berkabut, judul + kolom email (mailto), merek, hak cipta, ikon sosial, dan tombol ke atas.
     rapat=true memperkecil jarak atas untuk halaman yang diakhiri kartu; menempel=true tanpa jarak (beranda: bagian Kontak
     sudah memberi jarak bawah sendiri). --}}
@props(['rapat' => false, 'menempel' => false])
@php
    $emailFooter = 'travelling@mistborne.id';
    $tautanSosial = [
        ['label' => 'Instagram', 'href' => 'https://instagram.com', 'path' => 'M12 2.2c3.2 0 3.6 0 4.8.1 1.2.1 1.8.2 2.2.4.6.2 1 .5 1.4.9.4.4.7.8.9 1.4.2.4.4 1 .4 2.2.1 1.3.1 1.6.1 4.8s0 3.6-.1 4.8c-.1 1.2-.2 1.8-.4 2.2-.2.6-.5 1-.9 1.4-.4.4-.8.7-1.4.9-.4.2-1 .4-2.2.4-1.3.1-1.6.1-4.8.1s-3.6 0-4.8-.1c-1.2-.1-1.8-.2-2.2-.4-.6-.2-1-.5-1.4-.9-.4-.4-.7-.8-.9-1.4-.2-.4-.4-1-.4-2.2C2.2 15.6 2.2 15.2 2.2 12s0-3.6.1-4.8c.1-1.2.2-1.8.4-2.2.2-.6.5-1 .9-1.4.4-.4.8-.7 1.4-.9.4-.2 1-.4 2.2-.4C8.4 2.2 8.8 2.2 12 2.2Zm0 4.9a4.9 4.9 0 1 0 0 9.8 4.9 4.9 0 0 0 0-9.8Zm0 8.1a3.2 3.2 0 1 1 0-6.4 3.2 3.2 0 0 1 0 6.4Zm5.1-9.4a1.1 1.1 0 1 0 0 2.3 1.1 1.1 0 0 0 0-2.3Z'],
        ['label' => 'TikTok', 'href' => 'https://tiktok.com', 'path' => 'M16.6 2h-3.3v13.2a2.9 2.9 0 1 1-2.9-2.9c.3 0 .6 0 .9.1V9a6.3 6.3 0 1 0 5.3 6.2V8.6a7.6 7.6 0 0 0 4.4 1.4V6.7a4.4 4.4 0 0 1-4.4-4.4Z'],
        ['label' => 'YouTube', 'href' => 'https://youtube.com', 'path' => 'M22 8.1a3 3 0 0 0-2.1-2.1C18 5.5 12 5.5 12 5.5s-6 0-7.9.5A3 3 0 0 0 2 8.1C1.5 10 1.5 12 1.5 12s0 2 .5 3.9a3 3 0 0 0 2.1 2.1c1.9.5 7.9.5 7.9.5s6 0 7.9-.5a3 3 0 0 0 2.1-2.1c.5-1.9.5-3.9.5-3.9s0-2-.5-3.9ZM9.9 15.3V8.7l5.7 3.3-5.7 3.3Z'],
        ['label' => 'Facebook', 'href' => 'https://facebook.com', 'path' => 'M13.5 21.9v-8.2h2.8l.4-3.2h-3.2V8.4c0-.9.3-1.6 1.6-1.6h1.7V4a23 23 0 0 0-2.5-.1c-2.5 0-4.2 1.5-4.2 4.3v2.4H7.3v3.2h2.8v8.2h3.4Z'],
    ];
@endphp
<footer data-footer @class(['mt-10 md:mt-14' => $rapat && ! $menempel, 'mt-16 md:mt-24' => ! $rapat && ! $menempel])>
    <div class="relative isolate overflow-hidden text-white">
        {{-- Satu foto berkabut menjadi latar seluruh footer; selubung menggelap ke bawah supaya teks tetap kontras. --}}
        <img src="{{ asset('images/kabut.avif') }}" alt="" width="2070" height="1380" loading="lazy" decoding="async" class="absolute inset-0 -z-20 size-full object-cover" />
        <div class="absolute inset-0 -z-10 bg-gradient-to-b from-[#08130D]/45 via-[#08130D]/60 to-[#08130D]/85" aria-hidden="true"></div>

        {{-- Bagian atas --}}
        <div class="flex min-h-[300px] flex-col items-center justify-center px-6 py-16 text-center sm:min-h-[340px]">
            <p class="max-w-[640px] text-[30px] font-extrabold leading-[1.15] tracking-tight sm:text-[42px]">
                {{ __('Biarkan Perjalanan Dimulai') }}<br>{{ __('Jelajahi Dataran Tinggi Jawa') }}
            </p>
            <form action="mailto:{{ $emailFooter }}" method="get" class="mt-8 flex w-full max-w-[440px] items-center rounded-full border border-white/25 bg-white/10 p-1.5 ps-5 backdrop-blur-md">
                <input type="hidden" name="subject" value="{{ __('Berlangganan kabar MISTBORNE') }}">
                <label for="footer-email-atas" class="sr-only">{{ __('Alamat email') }}</label>
                <input id="footer-email-atas" type="email" name="body" required autocomplete="email" placeholder="{{ __('Tulis alamat email Anda di sini...') }}" class="min-w-0 flex-1 border-0 bg-transparent p-0 text-[14px] text-white placeholder:text-white/60 focus:outline-none focus:ring-0">
                <span class="mx-3 h-6 w-px shrink-0 bg-white/50" aria-hidden="true"></span>
                <button type="submit" class="shrink-0 rounded-full bg-white px-4 py-2.5 sm:px-6 text-[14px] font-semibold text-[#12301F] transition-colors hover:bg-[#EBEFE9]">{{ __('Berlangganan') }}</button>
            </form>
        </div>

        {{-- Bagian tengah --}}
        <div>
        <div class="mx-auto grid max-w-[1360px] gap-12 px-6 pb-12 pt-14 sm:px-12 lg:grid-cols-[minmax(0,1.25fr)_minmax(0,1fr)] lg:gap-20 lg:px-[84px] lg:pt-16">
            <div>
                <p class="text-[32px] font-extrabold leading-[1.12] tracking-tight sm:text-[44px]">{{ __('Lakukan yang Anda cintai, menjelajah.') }}</p>
                <p class="text-[32px] font-extrabold leading-[1.12] tracking-tight text-white/45 sm:text-[44px]">{{ __('Sisanya biar kami yang urus') }}</p>
                <form action="mailto:{{ $emailFooter }}" method="get" class="mt-8 flex w-full max-w-[590px] items-center rounded-full border border-white/25 bg-white/10 p-1.5 ps-5 backdrop-blur-md">
                    <input type="hidden" name="subject" value="{{ __('Berlangganan kabar MISTBORNE') }}">
                    <label for="footer-email-bawah" class="sr-only">{{ __('Alamat email') }}</label>
                    <input id="footer-email-bawah" type="email" name="body" required autocomplete="email" placeholder="{{ $emailFooter }}" class="min-w-0 flex-1 border-0 bg-transparent p-0 text-[14px] text-white placeholder:text-white/55 focus:outline-none focus:ring-0">
                    <span class="mx-3 h-6 w-px shrink-0 bg-white/50" aria-hidden="true"></span>
                    <button type="submit" class="shrink-0 rounded-full bg-white px-4 py-2.5 sm:px-6 text-[14px] font-semibold text-[#12301F] transition-colors hover:bg-[#EBEFE9]">{{ __('Berlangganan') }}</button>
                </form>
            </div>
            <div class="lg:justify-self-end">
                <p class="flex items-center gap-3">
                    <span class="material-symbols-outlined" style="font-size: 46px" aria-hidden="true">landscape</span>
                    <span class="text-[40px] font-extrabold leading-none tracking-tight sm:text-[48px]"><bdi>MISTBORNE</bdi></span>
                </p>
                <p class="mt-5 max-w-[360px] text-[16px] leading-[1.6] text-white/90">{{ __('Dari kabut Dieng hingga kawah biru Ijen, jelajahi keindahan dataran tinggi Jawa bersama pemandu lokal, tanpa repot memikirkan detail perjalanan.') }}</p>
            </div>
        </div>
        </div>

        {{-- Baris bawah --}}
        <div class="border-t border-white/15">
        <div class="mx-auto flex max-w-[1360px] flex-col gap-5 px-6 pb-24 pt-6 sm:flex-row sm:items-center sm:justify-between sm:px-12 sm:pb-6 lg:px-[84px]">
            <p class="text-[13px] text-white/85">© {{ date('Y') }} <bdi>{{ __('MISTBORNE. Seluruh hak cipta dilindungi.') }}</bdi></p>
            <div class="flex items-center gap-5">
                @foreach ($tautanSosial as $sosial)
                    <a href="{{ $sosial['href'] }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $sosial['label'] }}" class="text-white transition-opacity hover:opacity-70">
                        <svg viewBox="0 0 24 24" class="size-[22px] fill-current" aria-hidden="true" focusable="false"><path d="{{ $sosial['path'] }}"/></svg>
                    </a>
                @endforeach
                <a href="#{{ request()->routeIs('home', 'home.localized') ? 'beranda' : 'atas-halaman' }}" aria-label="{{ __('Kembali ke atas') }}" class="flex size-8 items-center justify-center rounded-full border border-white/30 text-sm text-white transition-colors hover:bg-white hover:text-[#12301F]">↑</a>
            </div>
        </div>
        </div>
    </div>
</footer>
