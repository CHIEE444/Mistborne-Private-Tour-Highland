<?php

namespace App\Layanan;

use App\Mail\PembatalanPenugasanPersonel;
use App\Mail\PenugasanPersonel;
use App\Models\PersonelOperasional;
use App\Models\Pesanan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Mengirim email penugasan/perubahan jadwal ke Guide serta mencatat kegagalannya. */
class EmailPenugasanPersonelLayanan
{
    /**
     * Email kontak guide dipakai lebih dulu; email akun panel hanya cadangan bila kontak kosong. Tidak dikirim ke dua
     * alamat sekaligus karena email akun kadang berupa alamat uji yang tidak ada (memantul sebagai "Delivery Status
     * Notification"). Alamat uji `.test` dan ID login yang bukan email tidak dipakai.
     */
    public function alamatTujuan(PersonelOperasional $personel): ?string
    {
        foreach ([$personel->email, $personel->user?->email] as $kandidat) {
            if (filled($kandidat) && filter_var($kandidat, FILTER_VALIDATE_EMAIL) && ! str_ends_with(mb_strtolower($kandidat), '.test')) {
                return $kandidat;
            }
        }

        return null;
    }

    /**
     * @param  iterable<PersonelOperasional>  $daftarPersonel
     * @return array{terkirim: int, gagal: int, autentikasi_ditolak: bool, alamat: list<string>}
     */
    public function kirim(Pesanan $pesanan, iterable $daftarPersonel): array
    {
        $hasil = ['terkirim' => 0, 'gagal' => 0, 'autentikasi_ditolak' => false, 'alamat' => []];

        foreach ($daftarPersonel as $personel) {
            $alamat = $this->alamatTujuan($personel);

            try {
                Mail::to($alamat)->send(new PenugasanPersonel($pesanan, $personel));
                $hasil['terkirim']++;
                $hasil['alamat'][] = $alamat;
                $this->catatHasil($pesanan, $personel, 'terkirim', $alamat);
            } catch (Throwable $exception) {
                $hasil['gagal']++;
                $pesanException = strtolower($exception->getMessage());
                $autentikasiDitolak = str_contains($pesanException, 'badcredentials') || str_contains($pesanException, '535');
                $hasil['autentikasi_ditolak'] = $hasil['autentikasi_ditolak'] || $autentikasiDitolak;
                $this->catatHasil($pesanan, $personel, 'gagal', $alamat, $autentikasiDitolak
                    ? 'Gmail menolak login SMTP (535). Periksa MAIL_USERNAME dan App Password di MAIL_PASSWORD.'
                    : 'Server email menolak atau tidak bisa dihubungi. Periksa koneksi internet dan pengaturan SMTP.');
                Log::warning('Email penugasan personel gagal dikirim.', [
                    'personel_id' => $personel->id,
                    'pesanan_id' => $pesanan->id,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }

        return $hasil;
    }

    /**
     * Simpan hasil email penugasan terakhir di pesanan bila personelnya memang guide pesanan itu, supaya panel
     * Operasional terus menampilkan "terkirim ke ..." atau "gagal" beserta alasannya.
     */
    public function catatHasil(Pesanan $pesanan, PersonelOperasional $personel, string $status, ?string $alamat, ?string $galat = null): void
    {
        if ((int) $pesanan->guide_personel_id !== (int) $personel->id) {
            return;
        }

        $pesanan->forceFill([
            'email_penugasan_status' => $status,
            'email_penugasan_alamat' => $alamat,
            'email_penugasan_at' => now(),
            'email_penugasan_galat' => $galat,
        ])->save();
    }

    /**
     * Kabar pembatalan ke guide yang masih tercatat pada pesanan. Kegagalan hanya dicatat di log supaya
     * pembatalan pesanan tidak ikut gagal; mengembalikan true bila email diterima layanan email.
     */
    public function kirimPembatalan(Pesanan $pesanan): bool
    {
        $personel = $pesanan->guide;
        $alamat = $personel ? $this->alamatTujuan($personel) : null;

        if ($personel === null || $alamat === null) {
            return false;
        }

        try {
            Mail::to($alamat)->send(new PembatalanPenugasanPersonel($pesanan, $personel));

            return true;
        } catch (Throwable $exception) {
            Log::warning('Email pembatalan ke personel gagal dikirim.', [
                'personel_id' => $personel->id,
                'pesanan_id' => $pesanan->id,
                'exception' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
