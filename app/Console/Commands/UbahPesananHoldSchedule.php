<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Models\Pesanan;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Jadwal Ditahan otomatis: pesanan berstatus Dihubungi yang tanggal tripnya
 * sudah lewat dipindah ke Jadwal Ditahan, menunggu admin menghubungi ulang pelanggan.
 * Kondisi "<" (bukan "=="), idempotent — aman dijalankan ulang bila sempat terlewat.
 */
#[Signature('pesanan:hold-schedule-otomatis')]
#[Description('Pindahkan pesanan Dihubungi yang tanggal tripnya sudah lewat ke Jadwal Ditahan')]
class UbahPesananHoldSchedule extends Command
{
    public function handle(): int
    {
        $pesanan = Pesanan::query()
            ->where('status', BookingStatus::Dihubungi)
            ->whereDate('tanggal_trip', '<', now()->toDateString())
            ->get();

        foreach ($pesanan as $item) {
            $item->update([
                'status_sebelum_hold' => BookingStatus::Dihubungi->value,
                'status' => BookingStatus::HoldSchedule,
            ]);
        }

        $this->info("{$pesanan->count()} pesanan dipindah ke Jadwal Ditahan.");

        return self::SUCCESS;
    }
}
