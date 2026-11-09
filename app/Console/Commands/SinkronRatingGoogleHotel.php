<?php

namespace App\Console\Commands;

use App\Layanan\RatingGoogleHotelLayanan;
use App\Models\Hotel;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('hotel:sinkron-google')]
#[Description('Perbarui rating dan jumlah ulasan Google untuk semua hotel aktif')]
class SinkronRatingGoogleHotel extends Command
{
    public function handle(RatingGoogleHotelLayanan $layanan): int
    {
        if (! $layanan->tersedia()) {
            $this->warn('GOOGLE_PLACES_API_KEY belum diisi di .env, jadi rating tidak bisa ditarik otomatis. Rating bisa diisi manual di menu Hotel.');

            return self::SUCCESS;
        }

        $berhasil = 0;

        foreach (Hotel::query()->aktif()->orderBy('nama')->get() as $hotel) {
            if ($layanan->sinkron($hotel)) {
                $berhasil++;
                $this->info("✓ {$hotel->nama}: {$hotel->google_rating} ({$hotel->google_jumlah_ulasan} ulasan)");
            } else {
                $this->warn("Gagal memperbarui {$hotel->nama}. Isi Place ID atau rating manual di menu Hotel.");
            }
        }

        $this->line("{$berhasil} hotel diperbarui.");

        return self::SUCCESS;
    }
}
