@props(['pesanan'])

@php
    // Linimasa disusun dari created_at, dikonfirmasi_at, penggantian, dan pembatalan (terbaru di atas).
    $butir = [];

    if ($pesanan->status->value === 'dibatalkan') {
        $labelPihak = \App\Aksi\Pesanan\BatalkanPesanan::PIHAK[$pesanan->pihak_pembatal] ?? $pesanan->pihak_pembatal;

        $butir[] = [
            'judul' => 'Pesanan Dibatalkan',
            'keterangan' => "Pihak: {$labelPihak}",
            'ts' => $pesanan->updated_at,
            'waktu' => $pesanan->updated_at->translatedFormat('d M Y, H:i'),
        ];
    }

    foreach ($pesanan->penggantian as $riwayat) {
        $butir[] = [
            'judul' => 'Jadwal Diganti',
            'keterangan' => $riwayat->tanggal_lama->translatedFormat('d M Y').' → '.$riwayat->tanggal_baru->translatedFormat('d M Y')
                .($riwayat->charge > 0 ? ' · Biaya '.\App\Bantuan\FormatRupiah::dari($riwayat->charge) : ' · Gratis'),
            'ts' => $riwayat->created_at,
            'waktu' => $riwayat->created_at->translatedFormat('d M Y, H:i'),
        ];
    }

    if ($pesanan->dikonfirmasi_at) {
        $butir[] = [
            'judul' => 'Dikonfirmasi Admin MISTBORNE',
            'keterangan' => 'Pesanan dikonfirmasi dan tagihan dikirim ke tamu (tahap menunggu pembayaran)',
            'ts' => $pesanan->dikonfirmasi_at,
            'waktu' => $pesanan->dikonfirmasi_at->translatedFormat('d M Y, H:i'),
        ];
    }

    if ($pesanan->pembayaran_diterima_at) {
        $butir[] = [
            'judul' => 'Pembayaran Diterima — Status Pembayaran',
            'keterangan' => $pesanan->sisaTagihan() === 0
                ? 'Tagihan lunas; pesanan masuk tahap persiapan operasional'
                : 'Pembayaran dicatat; pesanan masuk tahap persiapan operasional',
            'ts' => $pesanan->pembayaran_diterima_at,
            'waktu' => $pesanan->pembayaran_diterima_at->translatedFormat('d M Y, H:i'),
        ];
    }

    $butir[] = [
        'judul' => match ($pesanan->sumber->value) {
            'form' => 'Form Terkirim oleh Tamu',
            'kustom' => 'Pesanan Dibuat dari Pesanan Kustom',
            default => 'Pesanan Dibuat (manual)',
        },
        'keterangan' => match ($pesanan->sumber->value) {
            'form' => 'Web Booking Direct',
            'kustom' => 'Penawaran kustom disetujui tamu',
            default => 'Diinput admin',
        },
        'ts' => $pesanan->created_at,
            'waktu' => $pesanan->created_at->translatedFormat('d M Y, H:i'),
    ];
    // Terbaru di atas: urutkan menurut waktu kejadian, bukan urutan penyusunan di atas.
    $butir = collect($butir)->sortByDesc(fn (array $item) => $item['ts']->getTimestamp())->values()->all();
@endphp

<x-admin.kartu>
    <h2 class="mb-4 text-sm font-semibold text-muted">Riwayat Status</h2>
    <x-admin.lini-masa :butir="$butir" />
</x-admin.kartu>
