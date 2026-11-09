@props([
    'icon' => 'inbox',
    'judul',
    'keterangan' => null,
])

<div {{ $attributes->class(['flex flex-col items-center justify-center gap-3 px-6 py-16 text-center']) }}>
    <span class="flex h-14 w-14 items-center justify-center rounded-full bg-page text-muted">
        <span class="material-symbols-outlined text-2xl" aria-hidden="true">{{ $icon }}</span>
    </span>

    <div>
        <p class="font-semibold text-ink">{{ $judul }}</p>

        @if ($keterangan)
            <p class="mt-1 max-w-sm text-sm text-muted">{{ $keterangan }}</p>
        @endif
    </div>

    @isset($aksi)
        <div class="mt-2">{{ $aksi }}</div>
    @endisset
</div>
