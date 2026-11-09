<?php

namespace App\Aksi\Pesanan;

use App\Enums\BookingStatus;
use App\Models\Pesanan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** "Ubah ke Pembayaran": dicentang admin setelah transfer dicek masuk. Sejak ini ikut kuota & Kalender Trip. */
class TandaiLunas
{
    public function handle(Pesanan $pesanan): Pesanan
    {
        return DB::transaction(function () use ($pesanan): Pesanan {
            $pesanan = Pesanan::query()->whereKey($pesanan->id)->lockForUpdate()->firstOrFail();

            if (! in_array($pesanan->status, [BookingStatus::Dihubungi, BookingStatus::HoldSchedule], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya pesanan Dihubungi atau Jadwal Ditahan yang bisa ditandai Sudah Bayar.',
                ]);
            }

            if ($pesanan->harga === null) {
                throw ValidationException::withMessages([
                    'nominal_dibayar' => 'Tentukan total harga sebelum pesanan dapat ditandai Sudah Bayar.',
                ]);
            }

            $minimalUangMuka = (int) ceil($pesanan->harga * 0.3);
            if (($pesanan->nominal_dibayar ?? 0) < $minimalUangMuka) {
                throw ValidationException::withMessages([
                    'nominal_dibayar' => 'Pesanan baru dapat dikunci setelah uang muka minimal 30% ('.$minimalUangMuka.' rupiah) diterima dan diverifikasi.',
                ]);
            }

            // Kuota tidak dihitung ulang: slot sudah dipegang pesanan ini sejak dibuat, jadi pembayaran tidak pernah terkunci.
            $pesanan->update([
                'status' => BookingStatus::Payment,
                'pembayaran_diterima_at' => $pesanan->pembayaran_diterima_at ?? now(),
            ]);

            return $pesanan;
        });
    }
}
