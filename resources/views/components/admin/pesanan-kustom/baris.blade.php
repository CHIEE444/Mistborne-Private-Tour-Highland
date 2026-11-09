@props(['item'])

<x-admin.baris-tabel>
    <x-admin.kolom-tabel>
        <div class="flex items-center gap-2.5">
            <x-admin.avatar :nama="$item->nama" size="sm" />
            <div>
                <p class="font-medium text-ink">{{ $item->nama }}</p>
                <p class="font-mono text-xs text-muted">#{{ $item->kodeTampilan() }}</p>
                <p class="text-xs text-muted">{{ $item->whatsapp }}</p>
            </div>
        </div>
    </x-admin.kolom-tabel>
    <x-admin.kolom-tabel>
        <p class="truncate font-medium text-ink" title="{{ $item->destinasi_diinginkan }}">{{ $item->destinasi_diinginkan }}</p>
        <p class="truncate text-xs text-muted" title="{{ $item->harga_final ? \App\Bantuan\FormatRupiah::dari($item->harga_final).' per orang' : 'Belum ada penawaran' }}">{{ $item->harga_final ? \App\Bantuan\FormatRupiah::dari($item->harga_final).' / orang' : 'Belum ada penawaran' }}</p>
    </x-admin.kolom-tabel>
    <x-admin.kolom-tabel>
        <p class="truncate" title="{{ $item->jadwalDiinginkanFormat() }}{{ $item->durasi_hari ? ' · '.$item->durasi_hari.' hari' : '' }}">{{ $item->jadwalDiinginkanFormat() }}{{ $item->durasi_hari ? ' · '.$item->durasi_hari.' hari' : '' }}</p>
        <p class="truncate text-xs text-muted" title="Jemput: {{ $item->lokasi_penjemputan ?: 'Belum diisi' }}">Jemput: {{ $item->lokasi_penjemputan ?: 'Belum diisi' }}</p>
    </x-admin.kolom-tabel>
    <x-admin.kolom-tabel>{{ $item->jumlah_orang }} orang</x-admin.kolom-tabel>
    <x-admin.kolom-tabel>
        <x-admin.lencana-status :status="$item->status" />
        @if ($item->penawaranKedaluwarsa())
            <p class="mt-1 text-xs font-semibold text-status-dibatalkan-teks">Penawaran kedaluwarsa</p>
        @endif
    </x-admin.kolom-tabel>
    <x-admin.kolom-tabel>
        <div class="flex items-center gap-1">
            <x-admin.tombol as="a" href="{{ route('admin.pesanan-kustom.detail', $item->id) }}" variant="secondary" size="sm">Buka</x-admin.tombol>
            <x-admin.tombol-ikon icon="delete" variant="danger" :label="'Hapus pesanan kustom '.$item->kodeTampilan()" x-on:click="window.dispatchEvent(new CustomEvent('buka-modal', { detail: { nama: 'hapus-kustom-{{ $item->id }}' } }))" />
        </div>
        <x-admin.pesanan-kustom.dialog-hapus :pesanan-kustom="$item" />
    </x-admin.kolom-tabel>
</x-admin.baris-tabel>
