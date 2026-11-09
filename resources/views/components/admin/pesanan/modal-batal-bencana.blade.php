@props(['pesanan'])

<x-admin.modal :nama="'batal-bencana-'.$pesanan->id" size="sm">
    <x-slot:header>
        <p class="font-semibold text-ink">Batalkan Karena Cuaca/Bencana</p>
        <p class="text-xs text-muted">{{ $pesanan->kode }} — {{ $pesanan->nama_perjalanan }}</p>
    </x-slot:header>

    <form id="form-batal-bencana-{{ $pesanan->id }}" method="POST" action="{{ route('admin.pesanan.batalkan', $pesanan) }}" class="space-y-4">
        @csrf
        <input type="hidden" name="pihak_pembatal" value="mistborne" />
        <input type="hidden" name="nominal_refund" value="{{ app(\App\Aksi\Pesanan\BatalkanPesanan::class)->saranRefund($pesanan, 'mistborne') }}" />

        <x-admin.peringatan type="info">
            Pembatalan pihak MISTBORNE (cuaca/bencana/khusus) mengembalikan <strong>100% pembayaran yang sudah diterima</strong>
            ({{ \App\Bantuan\FormatRupiah::dari($pesanan->nominal_dibayar) ?? 'Rp 0' }}).
        </x-admin.peringatan>

        <x-admin.formulir.area-teks name="alasan_batal" id-input="alasan_batal_bencana" label="Alasan (cuaca/bencana/kondisi khusus)" wajib rows="3" placeholder="Contoh: Cuaca ekstrem di jalur Dieng, trip dibatalkan demi keselamatan tamu." />
    </form>

    <x-slot:footer>
        <x-admin.tombol variant="secondary" x-on:click="tutup()">Batal</x-admin.tombol>
        <x-admin.tombol type="submit" form="form-batal-bencana-{{ $pesanan->id }}" variant="danger">Batalkan &amp; Refund 100%</x-admin.tombol>
    </x-slot:footer>
</x-admin.modal>
