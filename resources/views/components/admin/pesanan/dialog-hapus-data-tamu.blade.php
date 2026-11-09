@props(['pesanan'])

<x-admin.modal :nama="'hapus-data-tamu-'.$pesanan->id" size="sm">
    <x-slot:header>
        <p class="font-semibold text-status-dibatalkan-teks">Hapus data tamu?</p>
    </x-slot:header>

    <div class="space-y-2 text-sm text-muted">
        <p>Apakah Anda yakin ingin menghapus data tamu untuk pesanan {{ $pesanan->kode }}?</p>
        <p class="font-medium text-status-dibatalkan-teks">Seluruh data pesanan, riwayat terkait, bukti pembayaran, dan video ulasan akan dihapus permanen. Tindakan ini tidak bisa dibatalkan.</p>
        @if ($pesanan->punyaCatatanKeuangan())
            <p class="rounded-lg bg-status-dibatalkan-bg px-3 py-2 text-xs font-semibold leading-5 text-status-dibatalkan-teks">Pesanan ini sudah punya catatan keuangan ({{ $pesanan->status->label() }}, dibayar {{ \App\Bantuan\FormatRupiah::dari((int) $pesanan->nominal_dibayar) ?? 'Rp 0' }}). Catatan pembayaran dan refund ikut hilang. Bila trip hanya tidak jadi, lebih aman pakai Batalkan Pesanan.</p>
        @endif
        @if ($pesanan->guide_personel_id && ! $pesanan->terkunci())
            <p class="text-xs">Guide {{ $pesanan->nama_guide }} akan dikabari lewat email bahwa tugas ini dibatalkan.</p>
        @endif
    </div>

    <x-slot:footer>
        <x-admin.tombol variant="secondary" x-on:click="tutup()">Batal</x-admin.tombol>

        <form method="POST" action="{{ route('admin.pesanan.hapus', $pesanan) }}">
            @csrf
            @method('DELETE')
            <x-admin.tombol type="submit" variant="danger">Ya, hapus data</x-admin.tombol>
        </form>
    </x-slot:footer>
</x-admin.modal>
