@props(['perjalanan'])

@php $terproteksi = $perjalanan->punyaPesananAktif(); @endphp

<x-admin.modal :nama="'hapus-perjalanan-'.$perjalanan->id" size="sm">
    <x-slot:header>
        <p class="font-semibold text-ink">Hapus &ldquo;{{ $perjalanan->nama }}&rdquo;?</p>
    </x-slot:header>

    @if ($terproteksi)
        <x-admin.peringatan type="warning">
            Journey ini masih punya pesanan aktif (belum selesai/batal), jadi tidak bisa dihapus.
            Anda bisa menyembunyikannya dari beranda sebagai gantinya.
        </x-admin.peringatan>
    @else
        <p class="text-sm text-muted">
            Tindakan ini akan mengarsipkan journey (soft delete) &mdash; bisa dipulihkan kembali oleh pengembang
            bila diperlukan, tapi akan langsung hilang dari katalog admin dan situs publik.
        </p>
    @endif

    <x-slot:footer>
        <x-admin.tombol variant="secondary" x-on:click="tutup()">Batal</x-admin.tombol>

        @if ($terproteksi)
            <form method="POST" action="{{ route('admin.perjalanan.sembunyikan', $perjalanan) }}">
                @csrf
                @method('PATCH')
                <x-admin.tombol type="submit" variant="danger">Sembunyikan dari Beranda</x-admin.tombol>
            </form>
        @else
            <form method="POST" action="{{ route('admin.perjalanan.hapus', $perjalanan) }}">
                @csrf
                @method('DELETE')
                <x-admin.tombol type="submit" variant="danger">Ya, Hapus</x-admin.tombol>
            </form>
        @endif
    </x-slot:footer>
</x-admin.modal>
