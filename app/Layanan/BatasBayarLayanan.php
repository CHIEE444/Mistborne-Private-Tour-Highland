<?php

namespace App\Layanan;

use App\Models\Pengaturan;
use App\Models\Pesanan;
use Illuminate\Support\Carbon;

/**
 * Hitung batas bayar pesanan yang sudah dikonfirmasi ("Konfirmasi, tinggal bayar").
 * Batas bayar TIDAK disimpan di kolom — selalu dihitung dari dikonfirmasi_at.
 */
class BatasBayarLayanan
{
    public function batasWaktu(Pesanan $pesanan): ?Carbon
    {
        if (! $pesanan->dikonfirmasi_at) {
            return null;
        }

        return $pesanan->dikonfirmasi_at->copy()->addDays($this->jumlahHari())->endOfDay();
    }

    public function sudahLewat(Pesanan $pesanan): bool
    {
        $batas = $this->batasWaktu($pesanan);

        return $batas !== null && now()->greaterThan($batas);
    }

    public function jumlahHari(): int
    {
        return Pengaturan::current()->batas_bayar_hari;
    }

    /** Hari setelah konfirmasi saat pesanan tanpa pembayaran dibatalkan otomatis: batas bayar + tenggang (bawaan 3 + 2 = 5). */
    public function hariPembatalan(): int
    {
        return $this->jumlahHari() + (int) config('mistborne.pembayaran.tenggang_batal_hari', 2);
    }

    public function batasPembatalan(Pesanan $pesanan): ?Carbon
    {
        return $pesanan->dikonfirmasi_at?->copy()->addDays($this->hariPembatalan())->endOfDay();
    }

    public function lewatBatasPembatalan(Pesanan $pesanan): bool
    {
        $batas = $this->batasPembatalan($pesanan);

        return $batas !== null && now()->greaterThan($batas);
    }
}
