<?php

namespace App\Aksi\Pesanan;

use App\Enums\BookingStatus;
use App\Layanan\AlurReservasiHotel;
use App\Models\Pesanan;
use Illuminate\Validation\ValidationException;

/** "Tandai Trip Selesai": hanya dari Pembayaran, dan hanya setelah trip berjalan/selesai. */
class SelesaikanPesanan
{
    public function __construct(private readonly AlurReservasiHotel $alurHotel) {}

    public function handle(Pesanan $pesanan): Pesanan
    {
        if ($pesanan->status !== BookingStatus::Payment) {
            throw ValidationException::withMessages([
                'status' => 'Hanya pesanan yang sudah bayar yang bisa ditandai Trip Selesai.',
            ]);
        }

        if ($pesanan->sisaTagihan() !== 0) {
            throw ValidationException::withMessages([
                'status' => 'Pesanan hanya bisa ditandai Trip Selesai setelah seluruh tagihan lunas.',
            ]);
        }

        if ($pesanan->penugasanDitolak()) {
            throw ValidationException::withMessages([
                'status' => implode(' dan ', $pesanan->peranPenolak()).' menolak tugas ini. Ganti penugasannya di kartu Operasional sebelum menandai Trip Selesai.',
            ]);
        }

        if ($pesanan->jumlahPersonelDitugaskan() !== 1 || ! $pesanan->tripOperasionalTuntas()) {
            throw ValidationException::withMessages([
                'status' => 'Pesanan hanya bisa ditandai Trip Selesai setelah Guide ditugaskan dan menandai trip selesai.',
            ]);
        }

        if ($this->alurHotel->menghambatCompleted($pesanan)) {
            throw ValidationException::withMessages([
                'status' => 'Pesanan hanya bisa ditandai Trip Selesai setelah semua langkah reservasi hotel selesai. Lengkapi kartu Reservasi Hotel terlebih dahulu.',
            ]);
        }

        if (now()->startOfDay()->lt($pesanan->tanggal_trip->copy()->startOfDay())) {
            throw ValidationException::withMessages([
                'status' => 'Trip belum berjalan — belum bisa ditandai Trip Selesai.',
            ]);
        }

        $pesanan->update(['status' => BookingStatus::Completed]);

        return $pesanan;
    }
}
