@props(['hotel'])

<x-admin.kartu :padding="false" class="flex flex-col overflow-hidden">
    <div class="flex flex-1 flex-col gap-3 p-5">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-muted">{{ $hotel->area }}</p>
                <h3 class="mt-1 font-semibold text-ink"><a href="{{ route('admin.hotel.detail', $hotel) }}" class="hover:text-hijau-aksi hover:underline">{{ $hotel->nama }}</a></h3>
                <p class="mt-0.5 text-sm text-[#B08D3A]" aria-label="{{ $hotel->bintang }} bintang">{{ $hotel->labelBintang() }}</p>
            </div>
            <span @class([
                'shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold',
                'bg-status-completed-bg text-status-completed-teks' => $hotel->aktif,
                'bg-page text-muted' => ! $hotel->aktif,
            ])>{{ $hotel->aktif ? 'Aktif' : 'Nonaktif' }}</span>
        </div>

        <dl class="grid grid-cols-2 gap-2 border-t border-surface-border pt-3 text-xs">
            <div><dt class="text-muted">Tarif / kamar / malam</dt><dd class="mt-0.5 font-medium text-ink">{{ $hotel->rentangTarif() ?? 'Belum diisi' }}</dd></div>
            <div><dt class="text-muted">Kapasitas kamar</dt><dd class="mt-0.5 font-medium text-ink">{{ $hotel->kapasitas_kamar }} orang{{ $hotel->tersedia_extra_bed ? ' (+extra bed)' : '' }}</dd></div>
            <div><dt class="text-muted">Check-in / out</dt><dd class="mt-0.5 font-medium text-ink">{{ $hotel->jamCheckIn() }} / {{ $hotel->jamCheckOut() }}</dd></div>
            <div><dt class="text-muted">Reservasi mendatang</dt><dd class="mt-0.5 font-medium text-ink">{{ $hotel->reservasi_mendatang_count }}</dd></div>
        </dl>

        @if ($hotel->teksFasilitas() !== '')
            <p class="text-xs text-muted">{{ $hotel->teksFasilitas() }}</p>
        @endif

        <div class="flex flex-wrap gap-1.5">
            @forelse ($hotel->perjalanan as $journey)
                <span class="rounded-full bg-page px-2 py-0.5 text-[11px] text-ink">{{ \Illuminate\Support\Str::title(mb_strtolower($journey->nama)) }}</span>
            @empty
                <span class="text-[11px] text-muted">Belum ditautkan ke journey</span>
            @endforelse
        </div>

        @if (blank($hotel->whatsapp))
            <p class="flex items-center gap-1.5 text-xs text-status-payment-teks">
                <span class="material-symbols-outlined text-sm" aria-hidden="true">warning</span>Nomor WhatsApp belum diisi
            </p>
        @endif

        <div class="mt-auto flex flex-wrap items-center gap-2 pt-1">
            <x-admin.tombol as="a" href="{{ route('admin.hotel.detail', $hotel) }}" size="sm" icon="open_in_new">Detail</x-admin.tombol>
            <x-admin.tombol as="a" href="{{ route('admin.hotel.ubah', $hotel) }}" variant="secondary" size="sm" icon="edit">Ubah</x-admin.tombol>
            <x-admin.tombol
                type="button"
                variant="danger"
                size="sm"
                icon="delete"
                class="ms-auto"
                x-on:click="window.dispatchEvent(new CustomEvent('buka-modal', { detail: { nama: 'hapus-hotel-{{ $hotel->id }}' } }))"
            >
                Hapus
            </x-admin.tombol>
        </div>
    </div>

    <x-admin.modal :nama="'hapus-hotel-'.$hotel->id" size="sm">
        <x-slot:header>
            <p class="font-semibold text-ink">Hapus &ldquo;{{ $hotel->nama }}&rdquo;?</p>
        </x-slot:header>

        <p class="text-sm text-muted">
            Hotel akan dihapus dari daftar rekomendasi. Hotel yang masih dipakai pada reservasi aktif tidak bisa dihapus.
        </p>

        <x-slot:footer>
            <x-admin.tombol variant="secondary" x-on:click="tutup()">Batal</x-admin.tombol>

            <form method="POST" action="{{ route('admin.hotel.hapus', $hotel) }}">
                @csrf
                @method('DELETE')
                <x-admin.tombol type="submit" variant="danger">Ya, Hapus</x-admin.tombol>
            </form>
        </x-slot:footer>
    </x-admin.modal>
</x-admin.kartu>
