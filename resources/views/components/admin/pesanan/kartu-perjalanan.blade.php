@props(['pesanan', 'batasBayarWaktu' => null])

<x-admin.kartu>
    <h2 class="mb-4 text-sm font-semibold text-muted">Perjalanan</h2>

    <div class="grid gap-4 text-sm sm:grid-cols-2">
        <div>
            <p class="text-muted">Journey</p>
            <p class="font-medium text-ink">{{ $pesanan->nama_perjalanan }}</p>
        </div>
        <div>
            <p class="text-muted">Durasi &amp; Tanggal</p>
            <p class="font-medium text-ink">
                {{ $pesanan->tanggal_trip->translatedFormat('d M Y') }}
                @if ($pesanan->tanggal_selesai && ! $pesanan->tanggal_selesai->equalTo($pesanan->tanggal_trip))
                    &ndash; {{ $pesanan->tanggal_selesai->translatedFormat('d M Y') }}
                @endif
                <span class="block text-xs font-normal text-muted">{{ $pesanan->labelDurasi() }}</span>
            </p>
        </div>
        <div>
            <p class="text-muted">Harga (Saat Dipesan)</p>
            <p class="font-medium text-ink">{{ $pesanan->harga !== null ? \App\Bantuan\FormatRupiah::dari($pesanan->harga) : 'Belum ditentukan' }}</p>
        </div>
        @if ($pesanan->totalBiayaGantiJadwal() > 0)
            <div>
                <p class="text-muted">Biaya Ganti Jadwal</p>
                <p class="font-medium text-ink">{{ \App\Bantuan\FormatRupiah::dari($pesanan->totalBiayaGantiJadwal()) }}</p>
            </div>
            <div>
                <p class="text-muted">Total Tagihan</p>
                <p class="font-medium text-ink">{{ \App\Bantuan\FormatRupiah::dari($pesanan->totalTagihan()) }}</p>
            </div>
        @endif
        <div>
            <p class="text-muted">Jam jemput</p>
            <p class="font-medium text-ink">{{ $pesanan->waktu_pickup ? substr($pesanan->waktu_pickup, 0, 5).' WIB' : 'Belum ditentukan' }}</p>
        </div>
        <div>
            <p class="text-muted">Sudah dibayar</p>
            <p class="font-medium text-ink">{{ $pesanan->nominal_dibayar !== null ? \App\Bantuan\FormatRupiah::dari($pesanan->nominal_dibayar) : 'Belum dicatat' }}</p>
        </div>
        <div>
            <p class="text-muted">Sisa tagihan</p>
            <p class="font-medium text-ink">
                {{ $pesanan->sisaTagihan() !== null ? \App\Bantuan\FormatRupiah::dari($pesanan->sisaTagihan()) : 'Belum dapat dihitung' }}
            </p>
        </div>
        <div>
            <p class="text-muted">Metode pembayaran</p>
            <p class="font-medium text-ink">{{ ['transfer' => 'Transfer bank', 'qris' => 'QRIS', 'tunai' => 'Tunai', 'lainnya' => 'Lainnya'][$pesanan->metode_pembayaran] ?? 'Belum dicatat' }}</p>
        </div>
        <div>
            <p class="text-muted">Dibuat</p>
            <p class="font-medium text-ink">{{ $pesanan->created_at->translatedFormat('d M Y, H:i') }} WIB</p>
        </div>
    </div>

    @if ($pesanan->pembayaran_diterima_at)
        <x-admin.peringatan type="success" class="mt-4">
            <p class="font-medium">Pembayaran sudah diterima dan diverifikasi</p>
            <p class="mt-0.5">
                {{ $pesanan->pembayaran_diterima_at->translatedFormat('d M Y, H:i') }} WIB
                &middot; {{ ['transfer' => 'Transfer bank', 'qris' => 'QRIS', 'tunai' => 'Tunai', 'lainnya' => 'Lainnya'][$pesanan->metode_pembayaran] ?? 'Metode tidak dicatat' }}
            </p>
        </x-admin.peringatan>
    @endif

    @if ($pesanan->dikonfirmasi_at && $pesanan->status->value === 'dihubungi')
        <x-admin.peringatan :type="$batasBayarWaktu && now()->greaterThan($batasBayarWaktu) ? 'danger' : 'warning'" class="mt-4">
            <p class="font-medium">Status Pembayaran Belum Diterima</p>
            <p class="mt-0.5">
                Dikonfirmasi pada {{ $pesanan->dikonfirmasi_at->translatedFormat('d M Y, H:i') }}
                &middot; Batas bayar sampai {{ $batasBayarWaktu?->translatedFormat('d M Y, H:i') }}
                @if ($batasBayarWaktu && now()->greaterThan($batasBayarWaktu))
                    (Terlewat)
                @endif
            </p>
        </x-admin.peringatan>
    @endif
</x-admin.kartu>
