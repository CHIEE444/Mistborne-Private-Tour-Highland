{{-- REKAP PDF BULANAN Kalender Trip (dompdf, A4 landscape). Pengganti "Cetak Manifest Harian": satu dokumen berisi
     ringkasan bulan dan daftar keberangkatan terkonfirmasi per tanggal. Hanya CSS sederhana berbasis tabel karena
     dompdf tidak mendukung flex/grid; font DejaVu Sans bawaan dompdf memuat karakter seperti "·" dan "—". --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekap Keberangkatan {{ $bulan->translatedFormat('F Y') }}</title>
    <style>
        @page { margin: 16mm 12mm 16mm 12mm; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 8.5pt; color: #14231b; }
        .kepala { width: 100%; border-bottom: 2px solid #12301f; padding-bottom: 8px; margin-bottom: 10px; }
        .identitas { font-size: 8pt; letter-spacing: 1.5px; text-transform: uppercase; color: #486553; }
        h1 { margin: 2px 0 0; font-size: 17pt; color: #12301f; }
        .label-internal { float: right; border: 1px solid #12301f; border-radius: 10px; padding: 3px 9px; font-size: 7.5pt; font-weight: bold; color: #12301f; }
        .ringkasan { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin: 0 -6px 12px; }
        .ringkasan td { background: #eef4ef; border-radius: 6px; padding: 7px 9px; vertical-align: top; }
        .ringkasan .label { display: block; font-size: 7pt; text-transform: uppercase; letter-spacing: 0.6px; color: #5b6a61; }
        .ringkasan .nilai { display: block; margin-top: 2px; font-size: 12pt; font-weight: bold; color: #12301f; }
        .per-journey { margin: 0 0 12px; font-size: 7.5pt; color: #3e4743; }
        .hari { margin: 14px 0 4px; padding: 5px 8px; background: #12301f; color: #ffffff; font-weight: bold; font-size: 9pt; border-radius: 4px; }
        .hari span { font-weight: normal; color: #d9e6dc; }
        table.daftar { width: 100%; border-collapse: collapse; page-break-inside: auto; }
        table.daftar tr { page-break-inside: avoid; }
        table.daftar th { background: #f1f4ef; text-align: left; font-size: 7pt; text-transform: uppercase; letter-spacing: 0.4px; color: #5b6a61; padding: 5px 6px; border-bottom: 1px solid #d5ddd6; }
        table.daftar td { padding: 5px 6px; border-bottom: 1px solid #e6ebe5; vertical-align: top; }
        .kode { font-family: "DejaVu Sans Mono", monospace; font-size: 7pt; white-space: nowrap; }
        .redup { color: #6b7770; }
        .status { font-size: 7pt; font-weight: bold; color: #1d5a3f; }
        .kosong { margin-top: 24px; padding: 18px; text-align: center; border: 1px dashed #b8c4ba; color: #5b6a61; }
        .kaki { position: fixed; bottom: -10mm; left: 0; right: 0; font-size: 7pt; color: #6b7770; }
        .kaki .halaman:after { content: "Halaman " counter(page); }
    </style>
</head>
<body>
    <div class="kaki">
        <span>MISTBORNE · Rekap Keberangkatan {{ $bulan->translatedFormat('F Y') }} · Dicetak {{ now()->translatedFormat('d M Y, H:i') }} WIB</span>
        <span class="halaman" style="float: right;"></span>
    </div>

    <div class="kepala">
        <span class="label-internal">Dokumen Internal</span>
        <div class="identitas">Mistborne · Private Highland Travel</div>
        <h1>Rekap Keberangkatan {{ $bulan->translatedFormat('F Y') }}</h1>
        <div class="redup">Journey: {{ $journeyTerpilih?->nama ?? 'Semua Journey' }} · hanya pesanan yang sudah bayar</div>
    </div>

    <table class="ringkasan">
        <tr>
            <td><span class="label">Keberangkatan</span><span class="nilai">{{ $jumlahPesanan }} pesanan</span></td>
            <td><span class="label">Total tamu</span><span class="nilai">{{ $jumlahTamu }} orang</span></td>
            <td><span class="label">Hari berangkat</span><span class="nilai">{{ $jumlahHari }} hari</span></td>
            <td><span class="label">Bulan</span><span class="nilai">{{ $bulan->translatedFormat('F Y') }}</span></td>
        </tr>
    </table>

    @if ($perJourney->isNotEmpty())
        <p class="per-journey"><strong>Per journey:</strong> {{ $perJourney->map(fn (int $jumlah, string $nama): string => $nama.' ('.$jumlah.')')->implode(' · ') }}</p>
    @endif

    @if ($perTanggal->isEmpty())
        <div class="kosong">Belum ada keberangkatan dari pesanan yang sudah bayar di bulan ini.</div>
    @else
        @foreach ($perTanggal as $tanggal => $pesananHari)
            <div class="hari">
                {{ \Illuminate\Support\Carbon::parse($tanggal)->translatedFormat('l, d F Y') }}
                <span>· {{ $pesananHari->count() }} keberangkatan · {{ $pesananHari->sum('jumlah_wisatawan') }} tamu</span>
            </div>
            <table class="daftar">
                <thead>
                    <tr>
                        <th style="width: 11%;">Kode</th>
                        <th style="width: 12%;">Tamu</th>
                        <th style="width: 12%;">Journey</th>
                        <th style="width: 5%;">Peserta</th>
                        <th style="width: 14%;">Jemput</th>
                        <th style="width: 9%;">Hotel</th>
                        <th style="width: 8%;">Guide</th>
                        <th style="width: 10%;">Kendaraan</th>
                        <th style="width: 15%;">Catatan</th>
                        <th style="width: 5%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pesananHari as $item)
                        <tr>
                            <td class="kode">{{ $item->kode }}</td>
                            <td>{{ $item->nama_lengkap }}<br><span class="redup">{{ $item->whatsapp }}</span></td>
                            <td>{{ $item->nama_perjalanan }}</td>
                            <td>{{ $item->jumlah_wisatawan }} orang</td>
                            <td>{{ $item->waktu_pickup ? substr((string) $item->waktu_pickup, 0, 5).' WIB' : 'Jam belum diisi' }}<br><span class="redup">{{ $item->lokasi_pickup ?: '—' }}</span></td>
                            <td>{{ $item->nama_hotel ?: ($item->status_penginapan === \App\Enums\StatusPenginapan::Belum ? 'Belum punya' : '—') }}</td>
                            <td>{{ $item->nama_guide ?: '—' }}</td>
                            <td>{{ $item->kendaraan ? $item->kendaraan->nama.' · '.$item->kendaraan->plat_nomor : '—' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit(collect([$item->paket_opsional, $item->permintaan_khusus])->map(fn (?string $isi): string => trim((string) $isi))->reject(fn (string $isi): bool => in_array($isi, ["", "-", "—"], true))->implode(' · '), 140) ?: '—' }}</td>
                            <td class="status">{{ $item->labelTahap() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
    @endif
</body>
</html>
