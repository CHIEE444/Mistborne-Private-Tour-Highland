@props(['title' => null])

<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' — MISTBORNE Admin' : 'MISTBORNE Admin Panel' }}</title>
    <x-bersama.ikon-tab jenis="admin" />
    @fonts
    <link rel="preload" as="font" type="font/woff2" href="{{ Vite::asset('resources/fonts/material-symbols-outlined.woff2') }}" crossorigin>
    @vite(['resources/css/admin/aplikasi.css', 'resources/js/admin/aplikasi.js'])
</head>
<body class="h-full bg-admin-content font-sans text-ink antialiased">
    <div class="grid min-h-screen grid-cols-1 lg:grid-cols-2">
        <div
            class="relative hidden flex-col justify-between overflow-hidden bg-admin-sidebar p-10 lg:flex"
            style="background-image: linear-gradient(180deg, rgba(11,31,20,.55), rgba(11,31,20,.9)), url('{{ asset('images/landscape-highland.jpg') }}'); background-size: cover; background-position: center;"
        >
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/10 text-emas">
                    <span class="material-symbols-outlined" aria-hidden="true">explore</span>
                </span>
                <div class="leading-tight text-white">
                    <p class="text-lg font-semibold tracking-wide">MISTBORNE</p>
                    <p class="text-[11px] uppercase tracking-[0.2em] text-admin-sidebar-teks">Private Highland Travel</p>
                </div>
            </div>

            <div class="max-w-md text-white">
                <p class="mb-3 h-px w-10 bg-emas"></p>
                <p class="font-serif text-2xl italic">&ldquo;Perjalanan pribadi, dikelola dengan tenang.&rdquo;</p>
                <p class="mt-3 text-xs uppercase tracking-[0.2em] text-admin-sidebar-teks">
                    Protokol Pelayanan Eksklusif
                </p>
            </div>
        </div>

        <div class="flex flex-col justify-center px-6 py-12 sm:px-12 lg:px-16">
            <div class="mx-auto w-full max-w-md">
                {{ $slot }}
            </div>
        </div>
    </div>

    <x-admin.notifikasi />

    @stack('scripts')
</body>
</html>
