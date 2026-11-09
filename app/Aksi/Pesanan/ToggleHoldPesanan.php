<?php

namespace App\Aksi\Pesanan;

use App\Enums\BookingStatus;
use App\Models\Pesanan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Jadwal Ditahan manual (bukan otomatis H-lewat): admin menahan sementara pesanan Dihubungi/
 * Pembayaran yang masih di atas H-7, lalu melepasnya kembali ke status semula kapan saja.
 */
class ToggleHoldPesanan
{
    public function handle(Pesanan $pesanan): Pesanan
    {
        return DB::transaction(function () use ($pesanan): Pesanan {
            $pesanan = Pesanan::query()->whereKey($pesanan->id)->lockForUpdate()->firstOrFail();

            if ($pesanan->status === BookingStatus::HoldSchedule) {
                $statusSebelumHold = BookingStatus::tryFrom($pesanan->status_sebelum_hold ?? '') ?? BookingStatus::Dihubungi;
                // Jadwal Ditahan tetap memegang slotnya, jadi melepas penahanan tidak perlu cek kuota lagi.

                $pesanan->update([
                    'status' => $statusSebelumHold,
                    'status_sebelum_hold' => null,
                ]);

                return $pesanan;
            }

            if (! in_array($pesanan->status, [BookingStatus::Dihubungi, BookingStatus::Payment], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya pesanan Dihubungi atau yang sudah bayar yang bisa ditahan jadwalnya.',
                ]);
            }

            if ($pesanan->sisaHari() <= 7) {
                throw ValidationException::withMessages([
                    'status' => 'Pesanan tidak dapat dipindahkan ke Jadwal Ditahan mulai H-7.',
                ]);
            }

            $pesanan->update([
                'status_sebelum_hold' => $pesanan->status->value,
                'status' => BookingStatus::HoldSchedule,
            ]);

            return $pesanan;
        });
    }
}
