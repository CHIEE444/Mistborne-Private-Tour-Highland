@props([
    'title' => 'Formulir Data Pesanan — MISTBORNE',
    'description' => 'Lengkapi data perjalanan agar admin MISTBORNE dapat memeriksa tanggal dan ketersediaan.',
])
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta name="description" content="{{ $description }}" />
    <meta name="theme-color" content="#f5f6f2" />
    <title>{{ $title }}</title>
    <x-bersama.ikon-tab />
    @fonts
    <link rel="preload" as="font" type="font/woff2" href="{{ Vite::asset('resources/fonts/material-symbols-outlined.woff2') }}" crossorigin>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen bg-[#f5f6f2] font-sans text-[#202a24] antialiased">
    <header class="border-b border-[#e5e9e2] bg-white">
        <div class="mx-auto flex min-h-[68px] max-w-4xl items-center justify-between gap-4 px-4 sm:px-6">
            <a href="{{ route('home') }}" class="rounded-sm text-sm font-semibold tracking-[.14em] text-[#294333] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#315f48] focus-visible:ring-offset-2" aria-label="MISTBORNE, beranda">
                MISTBORNE
            </a>
            <span class="text-xs text-[#78847b]">Private Highland Travel</span>
        </div>
    </header>

    <main id="konten" class="mx-auto w-full max-w-4xl px-4 pb-16 pt-8 sm:px-6 sm:pb-20 sm:pt-10">
        {{ $slot }}
    </main>
</body>
</html>
