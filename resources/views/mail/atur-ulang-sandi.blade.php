<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Atur ulang kata sandi MISTBORNE</title>
</head>
<body style="margin:0;background:#f5f6f2;color:#14231b;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:32px 16px;background:#f5f6f2;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#ffffff;border:1px solid #dfe3d8;border-radius:12px;">
                    <tr>
                        <td style="padding:28px 32px 18px;border-bottom:1px solid #e8ebe5;">
                            <p style="margin:0;color:#12301f;font-size:18px;font-weight:700;letter-spacing:2px;">MISTBORNE</p>
                            <p style="margin:6px 0 0;color:#617468;font-size:13px;">Panel Operasional</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 32px 32px;line-height:1.65;">
                            <h1 style="margin:0 0 18px;color:#14231b;font-size:22px;line-height:1.3;">Atur ulang kata sandi</h1>
                            <p style="margin:0 0 16px;">Halo {{ $nama ?: 'Pengguna MISTBORNE' }},</p>
                            <p style="margin:0 0 22px;color:#46564c;">Kami menerima permintaan untuk mengatur ulang kata sandi akun panel MISTBORNE Anda. Tekan tombol di bawah untuk membuat kata sandi baru.</p>
                            <p style="margin:0 0 24px;">
                                <a href="{{ $tautan }}" style="display:inline-block;padding:12px 22px;border-radius:7px;background:#12301f;color:#ffffff;font-size:14px;font-weight:700;text-decoration:none;">Atur Ulang Kata Sandi</a>
                            </p>
                            <p style="margin:0 0 16px;color:#46564c;">Tautan ini berlaku selama {{ $kedaluwarsaMenit }} menit. Jika Anda tidak meminta pengaturan ulang, abaikan email ini; kata sandi Anda tidak akan berubah.</p>
                            <p style="margin:24px 0 6px;color:#617468;font-size:13px;">Jika tombol tidak berfungsi, salin dan buka tautan berikut:</p>
                            <p style="margin:0;overflow-wrap:anywhere;font-size:13px;"><a href="{{ $tautan }}" style="color:#1d7a56;">{{ $tautan }}</a></p>
                            <p style="margin:26px 0 0;color:#46564c;">Salam hangat,<br><strong>Tim MISTBORNE</strong></p>
                        </td>
                    </tr>
                </table>
                <p style="margin:18px 0 0;color:#7c8a80;font-size:12px;">&copy; {{ now()->year }} MISTBORNE. Seluruh hak cipta dilindungi.</p>
            </td>
        </tr>
    </table>
</body>
</html>
