<?php

namespace App\Aksi\Pesanan;

use App\Enums\BookingStatus;
use App\Models\Pesanan;
use Illuminate\Validation\ValidationException;

/**
 * "Konfirmasi, tinggal bayar": mengisi dikonfirmasi_at (dipakai BatasBayarLayanan untuk
 * menghitung batas waktu bayar). Status TETAP Dihubungi — baru pindah ke Pembayaran lewat
 * aksi terpisah (TandaiLunas) setelah admin cek transfer masuk.
 */
class KonfirmasiPesanan
{
    public function handle(Pesanan $pesanan): Pesanan
    {
        if ($pesanan->status !== BookingStatus::Dihubungi) {
            throw ValidationException::withMessages([
                'status' => 'Hanya pesanan berstatus Dihubungi yang bisa dikonfirmasi.',
            ]);
        }

        if ($pesanan->sisaTagihan() === 0) {
            throw ValidationException::withMessages([
                'status' => 'Tagihan sudah lunas. Tandai pesanan Sudah Bayar untuk mengunci kuota dan memulai persiapan operasional.',
            ]);
        }

        // Konfirmasi pertama memulai tenggat bayar. Pengiriman ulang request tidak
        // boleh menggeser tenggat karena aksi ini bisa terpanggil lebih dari sekali.
        if ($pesanan->dikonfirmasi_at === null) {
            $pesanan->update(['dikonfirmasi_at' => now()]);
        }

        return $pesanan;
    }
}
