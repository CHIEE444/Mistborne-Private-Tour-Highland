@props(['pesananKustom'])

<x-admin.modal :nama="'arsipkan-kustom-'.$pesananKustom->id" size="sm">
    <x-slot:header>
        <p class="font-semibold text-ink">Arsipkan pesanan kustom ini?</p>
    </x-slot:header>

    <p class="text-sm text-muted">
        Pesanan #{{ $pesananKustom->kodeTampilan() }} akan dipindahkan ke tab Arsip (bisa dipulihkan kapan saja).
    </p>

    <x-slot:footer>
        <x-admin.tombol variant="secondary" x-on:click="tutup()">Batal</x-admin.tombol>

        <form method="POST" action="{{ route('admin.pesanan-kustom.arsipkan', $pesananKustom) }}">
            @csrf
            @method('DELETE')
            <x-admin.tombol type="submit" variant="danger">Ya, Arsipkan</x-admin.tombol>
        </form>
    </x-slot:footer>
</x-admin.modal>
