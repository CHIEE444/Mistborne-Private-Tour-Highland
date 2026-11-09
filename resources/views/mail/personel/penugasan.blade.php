@php
    $peranLabel = 'Guide & Driver';
    $kolomStatus = 'status_konfirmasi_guide';
    $penugasanBaru = $pesanan->{$kolomStatus} === 'menunggu';
    $judul = $penugasanBaru ? 'Penugasan perjalanan baru' : 'Pembaruan dari admin';
    $akhir = $pesanan->tanggal_selesai ?? $pesanan->tanggal_trip;
    $durasi = $pesanan->durasi_hari ?: $pesanan->perjalanan?->durasi_hari;
    $durasiTeks = $durasi && $durasi > 1 ? $durasi.' hari '.($durasi - 1).' malam' : '1 hari';
    $tanggalTeks = $pesanan->tanggal_trip->translatedFormat('l, d F Y');
    if (! $akhir->isSameDay($pesanan->tanggal_trip)) {
        $tanggalTeks .= ' s.d. '.$akhir->translatedFormat('l, d F Y');
    }
    $jam = \App\Bantuan\JamPenjemputan::untukInput($pesanan->waktu_pickup);
    $kendaraan = $pesanan->kendaraan;
    $itinerary = collect($pesanan->perjalanan?->itinerary ?? [])->filter(fn ($baris) => is_array($baris) && filled($baris['x'] ?? null));
    $baris = array_filter([
        'Kode pesanan' => $pesanan->kode,
        'Perjalanan' => $pesanan->nama_perjalanan,
        'Tanggal' => $tanggalTeks,
        'Durasi' => $durasiTeks,
        'Jam jemput' => $jam ? $jam.' WIB (estimasi, admin mengabari bila berubah)' : 'Belum ditentukan — admin akan mengabari',
        'Titik jemput' => $pesanan->lokasi_pickup,
        'Jumlah tamu' => $pesanan->jumlah_wisatawan.' orang',
        'Tamu' => $pesanan->nama_lengkap.($pesanan->whatsapp ? ' · WA '.$pesanan->whatsapp : ''),
        'Penginapan tamu' => $pesanan->nama_hotel ? $pesanan->nama_hotel.($pesanan->alamat_hotel ? ' — '.$pesanan->alamat_hotel : '') : null,
        'Kendaraan' => $kendaraan ? $kendaraan->nama.' · '.$kendaraan->plat_nomor.($kendaraan->warna ? ' · '.$kendaraan->warna : '') : null,
        'Mobil destinasi' => $pesanan->kendaraan_pilihan,
        'Permintaan khusus tamu' => $pesanan->permintaan_khusus,
    ], fn ($nilai) => filled($nilai));
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $judul }} — MISTBORNE</title>
</head>
<body style="margin:0;background:#f5f6f2;color:#14231b;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:28px 12px;background:#f5f6f2;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#ffffff;border:1px solid #dfe3d8;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="padding:22px 28px;background:#12301f;">
                            <p style="margin:0;color:#ffffff;font-size:18px;font-weight:700;letter-spacing:3px;">MISTBORNE</p>
                            <p style="margin:4px 0 0;color:#b9d3c3;font-size:12px;">Panel Guide</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 28px 8px;line-height:1.6;">
                            <p style="margin:0 0 6px;color:#1d7a56;font-size:12px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;">{{ $peranLabel }}</p>
                            <h1 style="margin:0 0 14px;color:#14231b;font-size:22px;line-height:1.3;">{{ $judul }}</h1>
                            <p style="margin:0 0 6px;">Halo {{ $personel->nama }},</p>
                            <p style="margin:0;color:#46564c;">
                                @if ($penugasanBaru)
                                    Anda mendapat penugasan sebagai Guide sekaligus Driver (Anda yang mengemudikan kendaraan). Periksa rincian di bawah, lalu terima atau tolak tugas ini di panel.
                                @else
                                    Admin memperbarui informasi untuk perjalanan yang Anda tangani. Mohon dibaca sebelum hari keberangkatan.
                                @endif
                            </p>
                        </td>
                    </tr>

                    @if (filled($pesanan->catatan_admin))
                        <tr>
                            <td style="padding:16px 28px 0;">
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#fff8e6;border-left:4px solid #c8a24a;border-radius:6px;">
                                    <tr>
                                        <td style="padding:14px 16px;">
                                            <p style="margin:0 0 6px;color:#7a5a12;font-size:12px;font-weight:700;letter-spacing:1px;text-transform:uppercase;">Catatan admin</p>
                                            <p style="margin:0;color:#3b3118;font-size:14px;line-height:1.6;white-space:pre-line;">{{ $pesanan->catatan_admin }}</p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    @endif

                    <tr>
                        <td style="padding:20px 28px 4px;">
                            <p style="margin:0 0 8px;color:#617468;font-size:12px;font-weight:700;letter-spacing:1px;text-transform:uppercase;">Rincian perjalanan</p>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-top:1px solid #e8ebe5;">
                                @foreach ($baris as $label => $nilai)
                                    <tr>
                                        <td valign="top" style="width:38%;padding:10px 12px 10px 0;border-bottom:1px solid #e8ebe5;color:#68766d;font-size:13px;">{{ $label }}</td>
                                        <td valign="top" style="padding:10px 0;border-bottom:1px solid #e8ebe5;color:#14231b;font-size:14px;{{ $label === 'Kode pesanan' ? 'font-weight:700;' : '' }}">{{ $nilai }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>

                    @if ($itinerary->isNotEmpty())
                        <tr>
                            <td style="padding:18px 28px 4px;">
                                <p style="margin:0 0 8px;color:#617468;font-size:12px;font-weight:700;letter-spacing:1px;text-transform:uppercase;">Rencana perjalanan (estimasi)</p>
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                    @foreach ($itinerary as $butir)
                                        <tr>
                                            <td valign="top" style="width:38%;padding:6px 12px 6px 0;color:#1d7a56;font-size:13px;font-weight:700;">{{ $butir['t'] ?? '' }}</td>
                                            <td valign="top" style="padding:6px 0;color:#2c3a31;font-size:13px;line-height:1.5;">{{ $butir['x'] }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                                <p style="margin:6px 0 0;color:#7c8a80;font-size:12px;">Jam pada rencana hanya perkiraan dan dapat menyesuaikan kondisi lapangan.</p>
                            </td>
                        </tr>
                    @endif

                    <tr>
                        <td style="padding:22px 28px 28px;">
                            <a href="{{ route('personel.masuk') }}" style="display:inline-block;padding:12px 22px;border-radius:7px;background:#12301f;color:#ffffff;font-size:14px;font-weight:700;text-decoration:none;">Buka Panel Guide</a>
                            <p style="margin:18px 0 0;color:#7c8a80;font-size:12px;line-height:1.6;">Email ini adalah pemberitahuan internal untuk Guide, bukan invoice tamu. Jangan teruskan ke tamu.</p>
                        </td>
                    </tr>
                </table>
                <p style="margin:16px 0 0;color:#7c8a80;font-size:12px;">&copy; {{ now()->year }} MISTBORNE. Seluruh hak cipta dilindungi.</p>
            </td>
        </tr>
    </table>
</body>
</html>
