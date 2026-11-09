@props(['pesanan'])

<x-admin.modal :nama="'hapus-bukti-'.$pesanan->id" size="sm">
    <x-slot:header>
        <p class="font-semibold text-ink">Hapus bukti pembayaran?</p>
    </x-slot:header>

    <p class="text-sm text-muted">
        Berkas bukti pembayaran untuk {{ $pesanan->kode }} akan dihapus permanen. Tindakan ini tidak bisa dibatalkan.
    </p>

    <x-slot:footer>
        <x-admin.tombol variant="secondary" x-on:click="tutup()">Batal</x-admin.tombol>

        <form method="POST" action="{{ route('admin.pesanan.bukti.hapus', $pesanan) }}">
            @csrf
            @method('DELETE')
            <x-admin.tombol type="submit" variant="danger">Ya, Hapus</x-admin.tombol>
        </form>
    </x-slot:footer>
</x-admin.modal>
