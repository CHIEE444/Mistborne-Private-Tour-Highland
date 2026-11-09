@props(['pesanan'])

@php
    $batasPratinjau = now()->addDays(app(\App\Layanan\BatasBayarLayanan::class)->jumlahHari())->endOfDay();
    $pesanPratinjau = app(\App\Layanan\PesanWhatsAppLayanan::class)->konfirmasiBayar(
        tap(clone $pesanan, fn ($p) => $p->dikonfirmasi_at = now()),
    );
@endphp

<x-admin.modal :nama="'konfirmasi-bayar-'.$pesanan->id" size="lg">
    <x-slot:header>
        <p class="font-semibold text-ink">Konfirmasi Pesanan — Mulai Tenggat Pembayaran</p>
        <p class="text-xs text-muted">Pesanan masuk tahap Menunggu Konfirmasi Pembayaran; belum dianggap sudah bayar.</p>
    </x-slot:header>

    <div class="space-y-4">
        <div class="rounded-lg bg-page p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-muted">{{ $pesanan->nama_perjalanan }}</p>
            <div class="mt-2 grid gap-3 text-sm sm:grid-cols-2">
                <div>
                    <p class="text-muted">Nama Pemesan</p>
                    <p class="font-medium text-ink">{{ $pesanan->nama_lengkap }} ({{ $pesanan->jumlah_wisatawan }} orang)</p>
                </div>
                <div>
                    <p class="text-muted">Jadwal Keberangkatan</p>
                    <p class="font-medium text-ink">{{ $pesanan->tanggal_trip->translatedFormat('d M Y') }}</p>
                </div>
            </div>
            <div class="mt-3 flex items-center justify-between border-t border-surface-border pt-3">
                <span class="text-muted">Total Nominal Tagihan</span>
                <span class="text-lg font-semibold text-ink">{{ \App\Bantuan\FormatRupiah::dari($pesanan->totalTagihan()) ?? 'Belum ditentukan' }}</span>
            </div>
            <div class="mt-2 flex items-center justify-between text-sm">
                <span class="text-muted">Sisa tagihan yang perlu dibayar</span>
                <span class="font-semibold text-ink">{{ $pesanan->sisaTagihan() !== null ? \App\Bantuan\FormatRupiah::dari($pesanan->sisaTagihan()) : 'Belum dapat dihitung' }}</span>
            </div>
        </div>

        <x-admin.peringatan type="warning">
            <p class="font-medium">Aturan Batas Bayar Otomatis</p>
            <p class="mt-0.5">
                Konfirmasi ini mencatat waktu mulai tenggat bayar (sampai {{ $batasPratinjau->translatedFormat('d M Y, H:i') }} WIB). Status tetap Dihubungi dan kuota belum dikunci. Setelah pembayaran diterima, catat pembayarannya lalu tandai Sudah Bayar; sistem akan memeriksa kuota dan membuka penugasan Guide/Driver.
            </p>
        </x-admin.peringatan>

        <div>
            <p class="mb-2 text-sm font-medium text-ink">Pratinjau Pesan WhatsApp Siap Kirim</p>
            <x-admin.pratinjau-whatsapp :pesan="$pesanPratinjau" :nomor-tujuan="$pesanan->whatsapp" />
        </div>
    </div>

    <x-slot:footer>
        <x-admin.tombol variant="secondary" x-on:click="tutup()">Batal</x-admin.tombol>

        <form method="POST" action="{{ route('admin.pesanan.konfirmasi', $pesanan) }}">
            @csrf
            @method('PATCH')
            <x-admin.tombol type="submit" icon="task_alt">Konfirmasi Pesanan</x-admin.tombol>
        </form>
    </x-slot:footer>
</x-admin.modal>
