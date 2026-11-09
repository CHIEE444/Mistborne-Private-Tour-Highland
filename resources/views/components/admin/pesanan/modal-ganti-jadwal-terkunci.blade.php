@props(['pesanan'])

<x-admin.modal :nama="'ganti-jadwal-'.$pesanan->id" size="sm">
    <x-slot:header>
        <p class="font-semibold text-ink">Ganti Jadwal Pesanan</p>
        <p class="text-xs text-muted">{{ $pesanan->kode }} — {{ $pesanan->nama_perjalanan }}</p>
    </x-slot:header>

    <x-admin.peringatan type="danger">
        <p class="font-medium">Jadwal Terkunci</p>
        <p class="mt-0.5">
            @if (in_array($pesanan->status->value, ['dihubungi', 'hold_schedule', 'payment'], true))
                Tanggal keberangkatan tinggal {{ $pesanan->sisaHari() }} hari lagi
                (H-7 atau kurang). Jadwal tidak bisa diganti lagi. Jika dibatalkan, refund mengikuti aturan pembatalan yang berlaku.
            @else
                Pesanan dengan status {{ $pesanan->status->label() }} tidak bisa mengganti jadwal.
            @endif
        </p>
    </x-admin.peringatan>

    <x-slot:footer>
        <x-admin.tombol variant="secondary" x-on:click="tutup()">Tutup</x-admin.tombol>
    </x-slot:footer>
</x-admin.modal>
