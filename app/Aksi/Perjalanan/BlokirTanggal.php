<?php

namespace App\Aksi\Perjalanan;

use App\Models\JadwalKeberangkatan;
use Illuminate\Validation\ValidationException;

/**
 * Blokir/buka blokir satu tanggal keberangkatan. Admin hanya boleh memblokir
 * (bukan membuka blokir) paling lambat H-14 dari hari ini.
 */
class BlokirTanggal
{
    public function handle(JadwalKeberangkatan $jadwal, bool $diblok): JadwalKeberangkatan
    {
        if ($diblok && ! $jadwal->bolehDiblokir()) {
            throw ValidationException::withMessages([
                'diblok' => 'Tanggal ini kurang dari H-14, tidak boleh diblokir lagi.',
            ]);
        }

        if ($diblok && ($terisi = $jadwal->terisi()) > 0) {
            throw ValidationException::withMessages([
                'diblok' => "Tanggal ini sudah punya {$terisi} pesanan aktif, tidak bisa diblokir. Pindahkan atau batalkan pesanan dulu.",
            ]);
        }

        $jadwal->update(['diblok' => $diblok]);

        return $jadwal;
    }
}
