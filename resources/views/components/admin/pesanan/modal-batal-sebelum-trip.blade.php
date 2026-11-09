@props(['pesanan'])

@php
    $aksi = app(\App\Aksi\Pesanan\BatalkanPesanan::class);
    $saranPelanggan = $aksi->saranRefund($pesanan, 'pelanggan');
@endphp

<x-admin.modal :nama="'batal-sebelum-trip-'.$pesanan->id" size="sm">
    <x-slot:header>
        <p class="font-semibold text-ink">Batalkan Pesanan</p>
        <p class="text-xs text-muted">{{ $pesanan->kode }} — {{ $pesanan->nama_perjalanan }}</p>
    </x-slot:header>

    <form id="form-batal-{{ $pesanan->id }}" method="POST" action="{{ route('admin.pesanan.batalkan', $pesanan) }}" class="space-y-4">
        @csrf
        <input type="hidden" name="pihak_pembatal" value="pelanggan" />

        <x-admin.peringatan type="warning">
            Aturan refund: batal H-7 atau lebih = gratis, refund 100%; H-6 s/d H-2 = refund 50%; setelahnya hangus (maksimal pembayaran yang sudah diterima). Berlaku saat ini: <strong>{{ $aksi->persenRefundPelanggan($pesanan) }}%</strong>. Sisa trip saat ini:
            <strong>{{ $pesanan->sisaHari() }} hari</strong>.
        </x-admin.peringatan>

        <x-admin.formulir.area-teks name="alasan_batal" id-input="alasan_batal_sebelum_trip" label="Alasan Pembatalan" wajib rows="3" />

        <x-admin.formulir.uang name="nominal_refund" :label="'Nominal Refund (saran otomatis, maks. '.(\App\Bantuan\FormatRupiah::dari($pesanan->nominal_dibayar) ?? 'Rp 0').')'" :value="$saranPelanggan" />
    </form>

    <x-slot:footer>
        <x-admin.tombol variant="secondary" x-on:click="tutup()">Batal</x-admin.tombol>
        <x-admin.tombol type="submit" form="form-batal-{{ $pesanan->id }}" variant="danger">Batalkan Pesanan</x-admin.tombol>
    </x-slot:footer>
</x-admin.modal>
