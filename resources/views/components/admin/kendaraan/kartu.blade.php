@props(['kendaraan'])

@php
    $gayaStatus = match ($kendaraan->status) {
        'siap' => 'bg-status-completed-bg text-status-completed-teks',
        'servis' => 'bg-status-payment-bg text-status-payment-teks',
        default => 'bg-page text-muted',
    };
@endphp

<x-admin.kartu :padding="false" class="flex flex-col overflow-hidden">
    <div class="relative aspect-[16/10] w-full bg-page">
        @if ($kendaraan->fotoUrl())
            <img src="{{ $kendaraan->fotoUrl() }}" alt="{{ $kendaraan->nama }}" loading="lazy" class="h-full w-full object-cover" />
        @else
            <div class="flex h-full w-full items-center justify-center text-muted/50">
                <span class="material-symbols-outlined text-6xl" aria-hidden="true">directions_car</span>
            </div>
        @endif

        <span class="absolute start-3 top-3 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $gayaStatus }}">{{ $kendaraan->labelStatus() }}</span>
    </div>

    <div class="flex flex-1 flex-col gap-3 p-5">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-wide text-muted">{{ $kendaraan->labelJenis() }}</p>
            <h3 class="mt-1 font-semibold text-ink"><a href="{{ route('admin.kendaraan.detail', $kendaraan) }}" class="hover:text-hijau-aksi hover:underline">{{ $kendaraan->nama }}</a></h3>
            <p class="mt-0.5 font-mono text-sm text-ink">{{ $kendaraan->plat_nomor }}</p>
        </div>

        <dl class="grid grid-cols-3 gap-2 border-t border-surface-border pt-3 text-xs">
            <div><dt class="text-muted">Kapasitas</dt><dd class="mt-0.5 font-medium text-ink">{{ $kendaraan->kapasitas }} orang</dd></div>
            <div><dt class="text-muted">Tahun</dt><dd class="mt-0.5 font-medium text-ink">{{ $kendaraan->tahun ?? '–' }}</dd></div>
            <div><dt class="text-muted">Pesanan</dt><dd class="mt-0.5 font-medium text-ink">{{ $kendaraan->pesanan_aktif_count }} aktif</dd></div>
        </dl>

        <div class="mt-auto flex flex-wrap items-center gap-2">
            <x-admin.tombol as="a" href="{{ route('admin.kendaraan.detail', $kendaraan) }}" size="sm" icon="open_in_new">Detail</x-admin.tombol>
            <x-admin.tombol as="a" href="{{ route('admin.kendaraan.ubah', $kendaraan) }}" variant="secondary" size="sm" icon="edit">Ubah</x-admin.tombol>
            <x-admin.tombol
                type="button"
                variant="danger"
                size="sm"
                icon="delete"
                class="ms-auto"
                x-on:click="window.dispatchEvent(new CustomEvent('buka-modal', { detail: { nama: 'hapus-kendaraan-{{ $kendaraan->id }}' } }))"
            >
                Hapus
            </x-admin.tombol>
        </div>
    </div>

    <x-admin.modal :nama="'hapus-kendaraan-'.$kendaraan->id" size="sm">
        <x-slot:header>
            <p class="font-semibold text-ink">Hapus &ldquo;{{ $kendaraan->nama }}&rdquo;?</p>
        </x-slot:header>

        <p class="text-sm text-muted">
            Kendaraan {{ $kendaraan->plat_nomor }} akan dihapus dari daftar armada. Kendaraan yang masih ditugaskan
            pada pesanan mendatang tidak bisa dihapus.
        </p>

        <x-slot:footer>
            <x-admin.tombol variant="secondary" x-on:click="tutup()">Batal</x-admin.tombol>

            <form method="POST" action="{{ route('admin.kendaraan.hapus', $kendaraan) }}">
                @csrf
                @method('DELETE')
                <x-admin.tombol type="submit" variant="danger">Ya, Hapus</x-admin.tombol>
            </form>
        </x-slot:footer>
    </x-admin.modal>
</x-admin.kartu>
