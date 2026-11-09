<?php

namespace App\Console\Commands;

use App\Models\Pesanan;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Mencatat ke log daftar pengingat H-7/H-3/H-2 harian sebagai jejak operasional; tidak mengubah status pesanan.
 */
#[Signature('pesanan:catat-pengingat-hari-h')]
#[Description('Catat ke log jumlah pengingat H-7, H-3, dan H-2 yang hari ini belum terkirim')]
class BuatPengingatHariH extends Command
{
    public function handle(): int
    {
        $perTahap = Pesanan::pengingatBelumTerkirim()->countBy(fn (Pesanan $pesanan): string => (string) $pesanan->tahapPengingatBerlaku());
        $h7 = $perTahap->get('h7', 0);
        $h3 = $perTahap->get('h3', 0);
        $h2 = $perTahap->get('h2', 0);

        $this->info("Pengingat H-7: {$h7} pesanan. Pengingat H-3: {$h3} pesanan. Pengingat H-2: {$h2} pesanan.");
        logger()->info('Pengingat hari ini', ['h7' => $h7, 'h3' => $h3, 'h2' => $h2]);

        return self::SUCCESS;
    }
}
