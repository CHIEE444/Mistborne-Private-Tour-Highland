@php
    $akhir = $pesanan->tanggal_selesai ?? $pesanan->tanggal_trip;
    $tanggalTeks = $pesanan->tanggal_trip->translatedFormat('l, d F Y');
    if (! $akhir->isSameDay($pesanan->tanggal_trip)) {
        $tanggalTeks .= ' s.d. '.$akhir->translatedFormat('l, d F Y');
    }
    $baris = array_filter([
        'Kode pesanan' => $pesanan->kode,
        'Perjalanan' => $pesanan->nama_perjalanan,
        'Tanggal' => $tanggalTeks,
        'Tamu' => $pesanan->nama_lengkap,
        'Alasan' => $pesanan->alasan_batal,
    ], fn ($nilai) => filled($nilai));
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Perjalanan dibatalkan — MISTBORNE</title>
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
                            <h1 style="margin:0 0 12px;font-size:20px;color:#9b1c1c;">Perjalanan dibatalkan</h1>
                            <p style="margin:0 0 16px;font-size:15px;">Halo {{ $personel->nama }}, perjalanan berikut <strong>dibatalkan</strong>. Tugas Anda untuk trip ini berakhir dan Anda tidak perlu berangkat.</p>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="font-size:14px;">
                                @foreach ($baris as $label => $nilai)
                                    <tr>
                                        <td style="padding:6px 0;color:#5d6b62;width:130px;vertical-align:top;">{{ $label }}</td>
                                        <td style="padding:6px 0;font-weight:600;">{{ $nilai }}</td>
                                    </tr>
                                @endforeach
                            </table>
                            <p style="margin:18px 0;font-size:14px;color:#5d6b62;">Jadwal Anda pada tanggal tersebut kembali kosong dan bisa menerima penugasan lain.</p>
                            <p style="margin:0 0 24px;"><a href="{{ route('personel.masuk') }}" style="display:inline-block;padding:11px 20px;background:#12301f;color:#ffffff;text-decoration:none;border-radius:8px;font-size:14px;">Buka panel Guide</a></p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
