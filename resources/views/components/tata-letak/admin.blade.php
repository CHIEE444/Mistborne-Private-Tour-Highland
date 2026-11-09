@props([
    'title' => null,
    'group' => null,
])

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' — MISTBORNE Admin' : 'MISTBORNE Admin Panel' }}</title>
    <x-bersama.ikon-tab jenis="admin" />
    {{-- @font-face Montserrat (bunny via laravel-vite-plugin) hanya dirender oleh direktif
         @fonts — tanpa ini seluruh admin panel fallback ke font sistem (sama seperti situs
         publik, lihat komponen/tata-letak/aplikasi.blade.php). --}}
    @fonts
    <link rel="preload" as="font" type="font/woff2" href="{{ Vite::asset('resources/fonts/material-symbols-outlined.woff2') }}" crossorigin>
    @vite(['resources/css/admin/aplikasi.css', 'resources/js/admin/aplikasi.js'])
</head>
<body class="min-h-dvh bg-admin-content font-sans text-ink antialiased" x-data="loadingAksi()" x-on:submit.window="mulai($event)" x-on:pageshow.window="pulihkan($event)">
    <div class="flex min-h-dvh" x-data="bilahSamping()" x-on:keydown.escape.window="tutup()">
        <x-admin.bilah-samping />

        <div class="flex min-w-0 flex-1 flex-col lg:ms-[80px]">
            <x-admin.bilah-atas :title="$title" :group="$group" />

            <main class="konten-admin flex-1 px-4 py-6 sm:px-6 lg:px-8">
                {{ $slot }}
            </main>
        </div>
    </div>

    <x-admin.notifikasi />
    <x-admin.modal-salin />

    <div
        x-cloak
        x-show="aktif"
        class="fixed inset-0 z-[200] flex items-center justify-center bg-black/40 p-4"
        role="dialog"
        aria-modal="true"
        aria-live="assertive"
        aria-labelledby="status-loading-aksi"
    >
        <div class="flex w-full max-w-sm flex-col items-center rounded-admin-kartu bg-white px-6 py-7 text-center shadow-admin-kartu">
            <span class="h-12 w-12 animate-spin rounded-full border-4 border-surface-border border-t-hijau-aksi" aria-hidden="true"></span>
            <h2 id="status-loading-aksi" class="mt-4 text-base font-semibold text-ink" x-text="judul"></h2>
            <p class="mt-1 text-sm text-muted" x-text="keterangan"></p>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
