@props(['pesananKustom'])

<x-admin.modal :nama="'hapus-kustom-'.$pesananKustom->id" size="sm">
    <x-slot:header>
        <p class="font-semibold text-status-dibatalkan-teks">Hapus pesanan kustom?</p>
    </x-slot:header>

    <div class="space-y-2 text-sm text-muted">
        <p>Pesanan kustom #{{ $pesananKustom->kodeTampilan() }} atas nama {{ $pesananKustom->nama }} beserta riwayat penawarannya akan dihapus permanen dan tidak bisa dipulihkan.</p>
        @if ($pesananKustom->pesanan_id)
            <p class="text-xs">Pesanan resmi yang sudah dibuat dari permintaan ini tetap tersimpan di menu Pesanan.</p>
        @else
            <p class="text-xs">Ingin bisa dipulihkan? Pakai Arsipkan di halaman detail.</p>
        @endif
    </div>

    <x-slot:footer>
        <x-admin.tombol variant="secondary" x-on:click="tutup()">Batal</x-admin.tombol>

        <form method="POST" action="{{ route('admin.pesanan-kustom.hapus', $pesananKustom->id) }}">
            @csrf
            @method('DELETE')
            <x-admin.tombol type="submit" variant="danger">Ya, hapus permanen</x-admin.tombol>
        </form>
    </x-slot:footer>
</x-admin.modal>
