@props(['title' => null, 'tab' => 'admin'])

{{-- Halaman Masuk terpadu Admin/Guide: panel ilustrasi di kiri, kartu masuk dua tab di kanan (tab awal mengikuti URL). --}}
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' — MISTBORNE' : 'MISTBORNE' }}</title>
    <x-bersama.ikon-tab jenis="admin" />
    @fonts
    <link rel="preload" as="font" type="font/woff2" href="{{ Vite::asset('resources/fonts/material-symbols-outlined.woff2') }}" crossorigin>
    @vite(['resources/css/admin/aplikasi.css', 'resources/js/admin/aplikasi.js'])
    <style>
        [x-cloak] { display: none !important; }
        @keyframes mk-naik { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes mk-apung { 0%, 100% { transform: translateY(0) rotate(var(--r, 0deg)); } 50% { transform: translateY(-9px) rotate(var(--r, 0deg)); } }
        @keyframes mk-kartu { 0%, 100% { transform: rotate(-4deg) translateY(0); } 50% { transform: rotate(-4deg) translateY(-7px); } }
        .mk-masuk { animation: mk-naik .6s ease-out both; }
        .mk-apung { animation: mk-apung 5.5s ease-in-out infinite; }
        .mk-kartu { animation: mk-kartu 7s ease-in-out infinite; }
        .mk-garis { background-image: repeating-linear-gradient(135deg, rgba(255,255,255,.07) 0 1px, transparent 1px 9px); }
        .mk-petak { background-color: #F7F8F6; background-image: linear-gradient(#E6E9E4 1px, transparent 1px), linear-gradient(90deg, #E6E9E4 1px, transparent 1px); background-size: 84px 84px; background-position: center; }
        .mk-arsir { background-image: repeating-linear-gradient(135deg, #D8DDD6 0 1px, transparent 1px 7px); }
        .mk-input { width: 100%; height: 46px; padding: 0 14px 0 42px; background: #fff; border: 1px solid #DCE1D9; border-radius: 12px; font-size: 14px; color: #14231B; outline: none; transition: border-color .15s, box-shadow .15s; }
        .mk-input::placeholder { color: #98A39B; }
        .mk-input:focus { border-color: #12301F; box-shadow: 0 0 0 3px rgba(18, 48, 31, .10); }
        .mk-input.galat { border-color: #C2410C; }
        .mk-ikon { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: #6B776E; font-size: 19px; pointer-events: none; }
        .mk-titik { position: absolute; width: 10px; height: 10px; border-radius: 999px; background: #D5DAD2; }
        @media (prefers-reduced-motion: reduce) { .mk-masuk, .mk-apung, .mk-kartu { animation: none; } }
    </style>
</head>
<body class="h-full bg-[#D9DEE3] font-sans text-[#14231B] antialiased">
    @php
        $teks = [
            'admin' => ['judul' => 'Kelola perjalanan privat dengan tenang dan terukur', 'isi' => 'Pantau pesanan, jadwal keberangkatan, dan permintaan tamu dari satu panel yang rapi.', 'foto' => 'images/bromo2.jpg', 'pos' => 'center 60%', 'chip' => 'Pesanan Baru'],
            'personel' => ['judul' => 'Setiap perjalanan dimulai dari tim yang siap', 'isi' => 'Lihat jadwal, terima atau tolak penugasan, dan siapkan detail jemputan tamu.', 'foto' => 'images/jemputdepanhotel.jpg', 'pos' => 'center 35%', 'chip' => 'Penugasan Guide'],
        ];
    @endphp
    <div class="flex min-h-full items-center justify-center p-3 sm:p-6">
        <div x-data="{ peran: @js($tab), mengirim: false }" class="mk-masuk grid w-full max-w-[1300px] overflow-hidden rounded-[6px] bg-white p-2 shadow-[0_30px_70px_-40px_rgba(18,48,31,.4)] lg:grid-cols-[0.82fr_1fr] lg:p-3">

            {{-- KIRI: panel hijau bergaris + ilustrasi --}}
            <div class="relative hidden min-h-[720px] overflow-hidden bg-[#12301F] lg:block">
                <div class="mk-garis absolute inset-x-0 bottom-0 h-[55%] [mask-image:linear-gradient(to_top,#000,transparent)]" aria-hidden="true"></div>
                <div class="relative z-10 px-12 pt-24">
                    <span class="flex size-9 items-center justify-center rounded-full bg-white/15 text-white"><span class="material-symbols-outlined text-[20px]" aria-hidden="true">landscape</span></span>
                    @foreach ($teks as $kunci => $t)
                        <div x-show="peran === '{{ $kunci }}'" @if ($kunci !== $tab) x-cloak @endif x-transition.opacity.duration.300ms>
                            <h2 class="mt-5 max-w-[360px] text-[30px] font-semibold leading-[1.15] tracking-tight text-white">{{ $t['judul'] }}</h2>
                            <p class="mt-3 max-w-[340px] text-sm leading-relaxed text-white/60">{{ $t['isi'] }}</p>
                        </div>
                    @endforeach
                </div>

                {{-- Ilustrasi: kartu perjalanan miring + chip melayang --}}
                <div class="absolute inset-x-0 bottom-24 z-10 h-[290px]">
                    <div class="mk-kartu absolute left-12 top-8 w-[300px] rounded-2xl bg-white p-3 shadow-[0_24px_50px_-20px_rgba(0,0,0,.6)]">
                        <div class="relative h-[150px] overflow-hidden rounded-xl bg-[#E6EBE4]">
                            @foreach ($teks as $kunci => $t)
                                <img src="{{ asset($t['foto']) }}" alt="" width="600" height="400" class="absolute inset-0 h-full w-full object-cover transition-opacity duration-500" style="object-position: {{ $t['pos'] }}; opacity: {{ $kunci === $tab ? 1 : 0 }};" x-bind:style="{ opacity: peran === '{{ $kunci }}' ? 1 : 0 }" @if ($kunci !== $tab) loading="lazy" @endif decoding="async">
                            @endforeach
                        </div>
                        <div class="mt-3 flex items-center justify-between px-1">
                            <div>
                                <p class="text-[13px] font-semibold text-[#14231B]">Bromo Sunrise Journey</p>
                                <p class="text-[11px] text-[#6B776E]">Jawa Timur · 4 orang</p>
                            </div>
                            <span class="flex items-center gap-1 rounded-full bg-[#EEF3EC] px-2.5 py-1 text-[11px] font-semibold text-[#12301F]"><span class="material-symbols-outlined text-[14px]" aria-hidden="true">star</span>4.9</span>
                        </div>
                    </div>
                    <span class="mk-apung absolute right-14 top-0 inline-flex items-center gap-2 rounded-full bg-white px-3.5 py-2 text-[12px] font-semibold shadow-lg" style="--r: 6deg"><span class="material-symbols-outlined text-[16px] text-[#1D7A56]" aria-hidden="true">calendar_month</span>Jadwal H-7</span>
                    <span class="mk-apung absolute left-[250px] top-[232px] inline-flex items-center gap-2 rounded-full bg-white px-3.5 py-2 text-[12px] font-semibold shadow-lg" style="--r: -5deg; animation-delay: -1.5s"><span class="material-symbols-outlined text-[16px] text-[#1D7A56]" aria-hidden="true">groups</span>Guide</span>
                    <span class="mk-apung absolute right-10 top-[150px] inline-flex items-center gap-2 rounded-full bg-white px-3.5 py-2 text-[12px] font-semibold shadow-lg" style="--r: 4deg; animation-delay: -3s"><span class="material-symbols-outlined text-[16px] text-[#1D7A56]" aria-hidden="true">chat</span>WhatsApp</span>
                    @foreach ($teks as $kunci => $t)
                        <span x-show="peran === '{{ $kunci }}'" @if ($kunci !== $tab) x-cloak @endif class="mk-apung absolute right-12 top-[262px] rounded-full bg-[#E5B84C] px-3.5 py-2 text-[12px] font-semibold text-[#14231B] shadow-lg" style="--r: -3deg; animation-delay: -2s">{{ $t['chip'] }}</span>
                    @endforeach
                </div>

                <p class="absolute inset-x-0 bottom-8 z-10 text-center text-xs text-white/60">&copy; {{ now()->year }} MISTBORNE. Seluruh hak cipta dilindungi.</p>
            </div>

            {{-- KANAN: latar berpetak + kartu masuk --}}
            <div class="mk-petak relative flex min-h-[640px] items-center justify-center overflow-hidden px-4 py-10 sm:px-8">
                <div class="mk-arsir absolute left-[22%] top-0 hidden h-6 w-[14%] sm:block" aria-hidden="true"></div>
                <div class="mk-arsir absolute right-[22%] top-0 hidden h-6 w-[14%] sm:block" aria-hidden="true"></div>
                <div class="mk-arsir absolute bottom-0 left-[24%] hidden h-6 w-[52%] sm:block" aria-hidden="true"></div>

                <div class="relative w-full max-w-[430px] rounded-[22px] border border-[#DADFD7] bg-white shadow-[0_18px_40px_-26px_rgba(18,48,31,.35)]">
                    <span class="mk-titik left-3 top-3" aria-hidden="true"></span><span class="mk-titik right-3 top-3" aria-hidden="true"></span>
                    <span class="mk-titik bottom-3 left-3" aria-hidden="true"></span><span class="mk-titik bottom-3 right-3" aria-hidden="true"></span>

                    <div class="px-7 pb-6 pt-9 sm:px-9">
                        <a href="{{ route('home') }}" class="mx-auto flex size-11 items-center justify-center rounded-full bg-[#12301F] text-white" aria-label="MISTBORNE"><span class="material-symbols-outlined text-[22px]" aria-hidden="true">landscape</span></a>
                        <h1 class="mt-4 text-center text-[22px] font-semibold tracking-tight">Masuk ke <span class="text-[#8A968D]">MISTBORNE</span></h1>
                        <p class="mx-auto mt-1.5 max-w-[300px] text-center text-[13px] leading-relaxed text-[#6B776E]">Kelola perjalanan privat dengan tenang. Pilih peran Anda untuk melanjutkan.</p>

                        <div class="mt-6 grid grid-cols-2 gap-1.5 rounded-xl bg-[#F1F3EF] p-1" role="tablist" aria-label="Pilih peran">
                            <button type="button" role="tab" :aria-selected="peran === 'admin'" x-on:click="peran = 'admin'" class="h-10 rounded-lg text-[13px] font-semibold transition-colors" :class="peran === 'admin' ? 'bg-white text-[#12301F] shadow-sm' : 'text-[#6B776E] hover:text-[#14231B]'">Admin</button>
                            <button type="button" role="tab" :aria-selected="peran === 'personel'" x-on:click="peran = 'personel'" class="h-10 rounded-lg text-[13px] font-semibold transition-colors" :class="peran === 'personel' ? 'bg-white text-[#12301F] shadow-sm' : 'text-[#6B776E] hover:text-[#14231B]'">Guide</button>
                        </div>

                        {{-- FORM ADMIN --}}
                        <form method="POST" action="{{ route('admin.autentikasi.proses-masuk') }}" x-show="peran === 'admin'" @if ($tab !== 'admin') x-cloak @endif x-on:submit="mengirim = true" class="mt-5 space-y-4">
                            @csrf
                            @php $galatAdmin = $tab === 'admin' && $errors->any(); @endphp
                            <div>
                                <label for="admin-email" class="mb-1.5 block text-[13px] font-semibold text-[#3C4A41]">Email</label>
                                <div class="relative"><span class="mk-ikon material-symbols-outlined" aria-hidden="true">mail</span>
                                    <input id="admin-email" name="email" type="email" value="{{ $tab === 'admin' ? old('email') : '' }}" placeholder="admin@mistborne.co.id" autocomplete="username" required class="mk-input {{ $galatAdmin ? 'galat' : '' }}"></div>
                            </div>
                            <div x-data="{ lihat: false }">
                                <div class="mb-1.5 flex items-center justify-between"><label for="admin-password" class="text-[13px] font-semibold text-[#3C4A41]">Kata sandi</label><a href="{{ route('admin.autentikasi.lupa-sandi') }}" class="text-[12.5px] font-semibold text-[#1D7A56] hover:underline">Lupa sandi?</a></div>
                                <div class="relative"><span class="mk-ikon material-symbols-outlined" aria-hidden="true">lock</span>
                                    <input id="admin-password" name="password" :type="lihat ? 'text' : 'password'" placeholder="Masukkan kata sandi" autocomplete="current-password" required class="mk-input {{ $galatAdmin ? 'galat' : '' }}" style="padding-right: 44px;">
                                    <button type="button" x-on:click="lihat = ! lihat" :aria-label="lihat ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'" class="absolute right-3 top-1/2 -translate-y-1/2 text-[#6B776E] hover:text-[#14231B]"><span class="material-symbols-outlined text-[20px]" aria-hidden="true" x-text="lihat ? 'visibility_off' : 'visibility'">visibility</span></button></div>
                            </div>
                            @if ($galatAdmin)<p class="text-[13px] text-[#C2410C]" role="alert">{{ $errors->first() }}</p>@endif
                            <label class="flex items-center gap-2.5 text-[13px] text-[#4B5A52]"><input type="checkbox" name="ingat" value="1" class="size-4 rounded border-[#C5CEC2] accent-[#12301F]"> Ingat saya di perangkat ini</label>
                            <button type="submit" :disabled="mengirim" class="flex h-12 w-full items-center justify-center rounded-full bg-[#1B2B22] text-[15px] font-semibold text-white shadow-[0_10px_20px_-12px_rgba(0,0,0,.6)] transition-colors hover:bg-[#12301F] disabled:cursor-not-allowed disabled:opacity-70">Masuk sebagai Admin</button>
                        </form>

                        {{-- FORM GUIDE --}}
                        <form method="POST" action="{{ route('personel.proses-masuk') }}" x-show="peran === 'personel'" @if ($tab !== 'personel') x-cloak @endif x-on:submit="mengirim = true" class="mt-5 space-y-4">
                            @csrf
                            @php $galatPersonel = $tab === 'personel' && $errors->any(); @endphp
                            <div>
                                <label for="personel-email" class="mb-1.5 block text-[13px] font-semibold text-[#3C4A41]">Email personel</label>
                                <div class="relative"><span class="mk-ikon material-symbols-outlined" aria-hidden="true">badge</span>
                                    <input id="personel-email" name="email" type="email" value="{{ $tab === 'personel' ? old('email') : '' }}" placeholder="nama@email.com" autocomplete="username" required class="mk-input {{ $galatPersonel ? 'galat' : '' }}"></div>
                            </div>
                            <div x-data="{ lihat: false }">
                                <label for="personel-password" class="mb-1.5 block text-[13px] font-semibold text-[#3C4A41]">Kata sandi</label>
                                <div class="relative"><span class="mk-ikon material-symbols-outlined" aria-hidden="true">lock</span>
                                    <input id="personel-password" name="password" :type="lihat ? 'text' : 'password'" placeholder="Masukkan kata sandi" autocomplete="current-password" required class="mk-input {{ $galatPersonel ? 'galat' : '' }}" style="padding-right: 44px;">
                                    <button type="button" x-on:click="lihat = ! lihat" :aria-label="lihat ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'" class="absolute right-3 top-1/2 -translate-y-1/2 text-[#6B776E] hover:text-[#14231B]"><span class="material-symbols-outlined text-[20px]" aria-hidden="true" x-text="lihat ? 'visibility_off' : 'visibility'">visibility</span></button></div>
                            </div>
                            @if ($galatPersonel)<p class="text-[13px] text-[#C2410C]" role="alert">{{ $errors->first() }}</p>@endif
                            <label class="flex items-center gap-2.5 text-[13px] text-[#4B5A52]"><input type="checkbox" name="ingat" value="1" class="size-4 rounded border-[#C5CEC2] accent-[#12301F]"> Ingat saya di perangkat ini</label>
                            <button type="submit" :disabled="mengirim" class="flex h-12 w-full items-center justify-center rounded-full bg-[#1B2B22] text-[15px] font-semibold text-white shadow-[0_10px_20px_-12px_rgba(0,0,0,.6)] transition-colors hover:bg-[#12301F] disabled:cursor-not-allowed disabled:opacity-70">Masuk sebagai Guide</button>
                            {{ $slot }}
                        </form>
                    </div>

                    <div class="border-t border-[#E3E7E0] px-7 py-4 text-center text-[13px] text-[#6B776E]">
                        <span x-show="peran === 'admin'">Guide? <button type="button" x-on:click="peran = 'personel'" class="font-semibold text-[#14231B] hover:underline">Masuk di sini &rsaquo;</button></span>
                        <span x-show="peran === 'personel'" x-cloak>Pengelola? <button type="button" x-on:click="peran = 'admin'" class="font-semibold text-[#14231B] hover:underline">Masuk sebagai admin &rsaquo;</button></span>
                        <span class="mx-2 text-[#C5CEC2]">·</span><a href="{{ route('home') }}" class="font-medium hover:underline">Ke website</a>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <x-admin.notifikasi />

    @stack('scripts')
</body>
</html>
