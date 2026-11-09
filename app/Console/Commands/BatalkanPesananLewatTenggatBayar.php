<?php

namespace App\Console\Commands;

use App\Aksi\Pesanan\SinkronkanTahapPesanan;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Menjalankan SinkronkanTahapPesanan: pesanan yang sudah deal tetapi belum membayar sama sekali dibatalkan pada hari
 * ke-(batas bayar + tenggang) setelah konfirmasi supaya kuota dibebaskan, dan trip yang sudah usai dengan semua syarat
 * terpenuhi disimpan sebagai Trip Selesai. Pesanan yang sudah membayar sebagian tidak dibatalkan (perlu keputusan admin).
 */
#[Signature('pesanan:batalkan-lewat-tenggat-bayar')]
#[Description('Batalkan pesanan tanpa pembayaran setelah batas pembatalan dan simpan trip yang sudah usai sebagai Trip Selesai')]
class BatalkanPesananLewatTenggatBayar extends Command
{
    public function handle(SinkronkanTahapPesanan $sinkron): int
    {
        $hasil = $sinkron->handle();

        $this->info("{$hasil['dibatalkan']} pesanan dibatalkan otomatis karena lewat batas pembatalan. {$hasil['selesai']} trip disimpan sebagai Trip Selesai.");

        return self::SUCCESS;
    }
}
