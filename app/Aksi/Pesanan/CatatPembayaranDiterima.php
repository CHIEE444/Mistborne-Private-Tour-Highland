<?php

namespace App\Aksi\Pesanan;

use App\Enums\BookingStatus;
use App\Models\Pesanan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CatatPembayaranDiterima
{
    public function handle(Pesanan $pesanan, string $jenis, ?int $nominal, string $metode): Pesanan
    {
        return DB::transaction(function () use ($pesanan, $jenis, $nominal, $metode): Pesanan {
            $pesanan = Pesanan::query()->whereKey($pesanan->id)->lockForUpdate()->firstOrFail();

            if (! in_array($pesanan->status, [BookingStatus::Dihubungi, BookingStatus::HoldSchedule, BookingStatus::Payment], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Pembayaran hanya bisa dicatat untuk pesanan aktif yang belum lunas.',
                ]);
            }

            if ($pesanan->status === BookingStatus::Payment && $pesanan->sisaTagihan() === 0) {
                throw ValidationException::withMessages([
                    'status' => 'Pesanan ini sudah lunas dan tidak menerima pencatatan pembayaran tambahan.',
                ]);
            }

            $totalTagihan = $pesanan->totalTagihan();
            $sudahDibayar = (int) ($pesanan->nominal_dibayar ?? 0);
            $nominalDiterima = $jenis === 'lunas'
                ? $totalTagihan
                : ($nominal !== null && $nominal > 0 ? $sudahDibayar + $nominal : null);

            if ($totalTagihan === null || $nominalDiterima === null) {
                throw ValidationException::withMessages([
                    'nominal_dibayar' => 'Tentukan total harga dan nominal pembayaran sebelum mencatat pembayaran.',
                ]);
            }

            if ($nominalDiterima > $totalTagihan) {
                throw ValidationException::withMessages([
                    'nominal_dibayar' => 'Nominal transfer melebihi sisa tagihan Rp '.number_format(max(0, $totalTagihan - $sudahDibayar), 0, ',', '.').'.',
                ]);
            }

            $minimalUangMuka = (int) ceil(($pesanan->harga ?? 0) * 0.3);
            if ($pesanan->status !== BookingStatus::Payment && $nominalDiterima < $minimalUangMuka) {
                throw ValidationException::withMessages([
                    'nominal_dibayar' => 'Total pembayaran minimal 30% (Rp '.number_format($minimalUangMuka, 0, ',', '.').') untuk masuk tahap Sudah Bayar.',
                ]);
            }

            $pesanan->update([
                'nominal_dibayar' => $nominalDiterima,
                'metode_pembayaran' => $metode,
                'pembayaran_diterima_at' => now(),
                'status' => BookingStatus::Payment,
                'status_sebelum_hold' => null,
            ]);

            return $pesanan->refresh();
        });
    }
}
