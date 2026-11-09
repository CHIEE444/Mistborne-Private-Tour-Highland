@props(['artikel'])

@php
    $gayaStatus = match ($artikel->status()) {
        'terbit' => ['bg' => 'bg-status-completed-bg', 'teks' => 'text-status-completed-teks'],
        'dijadwalkan' => ['bg' => 'bg-status-payment-bg', 'teks' => 'text-status-payment-teks'],
        default => ['bg' => 'bg-page', 'teks' => 'text-muted'],
    };
@endphp

<x-admin.kartu :padding="false" class="flex flex-col overflow-hidden">
    <div class="relative aspect-[16/10] w-full bg-page">
        @if ($artikel->img)
            <img src="{{ $artikel->imgUrl() }}" alt="{{ $artikel->judul }}" class="h-full w-full object-cover" />
        @endif

        <span class="absolute start-3 top-3 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $gayaStatus['bg'] }} {{ $gayaStatus['teks'] }}">
            {{ $artikel->labelStatus() }}
        </span>
    </div>

    <div class="flex flex-1 flex-col gap-3 p-5">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-wide text-muted">{{ $artikel->kategori }}</p>
            <h3 class="mt-1 line-clamp-2 font-semibold text-ink">{{ $artikel->judul }}</h3>
            <p class="mt-1 line-clamp-2 text-sm text-muted">{{ $artikel->ringkasan }}</p>
        </div>

        <div class="mt-auto flex items-center justify-between border-t border-surface-border pt-3 text-xs text-muted">
            <span>{{ $artikel->penulis }}</span>
            <span>{{ $artikel->published_at?->translatedFormat('d M Y') ?? 'Belum dijadwalkan' }}</span>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <x-admin.tombol as="a" href="{{ route('admin.artikel.ubah', $artikel) }}" variant="secondary" size="sm" icon="edit">Edit</x-admin.tombol>
            <x-admin.tombol
                type="button"
                variant="danger"
                size="sm"
                icon="delete"
                class="ms-auto"
                x-on:click="window.dispatchEvent(new CustomEvent('buka-modal', { detail: { nama: 'hapus-artikel-{{ $artikel->id }}' } }))"
            >
                Hapus
            </x-admin.tombol>
        </div>
    </div>

    <x-admin.artikel.dialog-hapus :artikel="$artikel" />
</x-admin.kartu>
