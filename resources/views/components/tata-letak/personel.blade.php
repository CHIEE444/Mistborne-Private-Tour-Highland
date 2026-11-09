@props(['personel'])

<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Panel {{ ucfirst($personel->peran) }} — MISTBORNE</title>
    <x-bersama.ikon-tab jenis="admin" />
    @fonts
    <link rel="preload" as="font" type="font/woff2" href="{{ Vite::asset('resources/fonts/material-symbols-outlined.woff2') }}" crossorigin>
    @vite(['resources/css/admin/aplikasi.css', 'resources/js/admin/aplikasi.js'])
</head>
<body class="min-h-full bg-admin-content font-sans text-ink antialiased">
    <header class="border-b border-surface-border bg-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
            <a href="{{ route('personel.dashboard') }}" class="font-semibold tracking-wide text-admin-sidebar">MISTBORNE <span class="font-normal text-muted">· Panel {{ ucfirst($personel->peran) }}</span></a>
            <div class="flex items-center gap-3">
                <span class="hidden text-sm text-muted sm:inline">{{ $personel->nama }}</span>
                <form method="POST" action="{{ route('personel.keluar') }}">@csrf<x-admin.tombol type="submit" variant="secondary" size="sm">Keluar</x-admin.tombol></form>
            </div>
        </div>
    </header>
    <nav class="border-b border-surface-border bg-white" aria-label="Menu panel Guide">
        <div class="mx-auto flex max-w-7xl gap-1 overflow-x-auto px-4 sm:px-6 lg:px-8">
            @foreach ([['personel.dashboard', 'Penugasan', 'event_available'], ['personel.aktivitas', 'Log Aktivitas', 'history'], ['personel.profil', 'Profil', 'person']] as [$rute, $label, $ikon])
                <a href="{{ route($rute) }}" @class([
                    'flex shrink-0 items-center gap-2 whitespace-nowrap border-b-2 px-3 py-3 text-sm font-medium',
                    'border-admin-sidebar text-ink' => request()->routeIs($rute),
                    'border-transparent text-muted hover:text-ink' => ! request()->routeIs($rute),
                ])><span class="material-symbols-outlined text-lg" aria-hidden="true">{{ $ikon }}</span>{{ $label }}</a>
            @endforeach
        </div>
    </nav>
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">{{ $slot }}</main>
    <x-admin.notifikasi />
</body>
</html>
