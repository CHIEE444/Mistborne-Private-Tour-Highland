<?php

namespace App\Console\Commands;

use App\Models\Pesanan;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Mencatat ke log trip yang berangkat besok tetapi belum siap (cek akhir H-1). Tidak mengubah status pesanan.
 */
#[Signature('pesanan:catat-peringatan-h1')]
#[Description('Catat ke log trip besok yang belum siap berangkat (cek akhir H-1)')]
class PeringatanHariSatu extends Command
{
    public function handle(): int
    {
        $belumSiap = Pesanan::query()->peringatanH1()->get()->filter(fn (Pesanan $pesanan): bool => $pesanan->belumSiapBerangkat() !== []);

        $this->info("{$belumSiap->count()} trip yang berangkat besok belum siap.");
        logger()->info('Cek akhir H-1', ['belum_siap' => $belumSiap->count(), 'kode' => $belumSiap->pluck('kode')->values()->all()]);

        return self::SUCCESS;
    }
}
