<?php

namespace Database\Seeders;

use App\Models\Pengaturan;
use Illuminate\Database\Seeder;

class PengaturanSeeder extends Seeder
{
    /**
     * Baris pengaturan tunggal dengan nilai default.
     */
    public function run(): void
    {
        $pengaturan = Pengaturan::query()->first() ?? new Pengaturan;

        $pengaturan->fill([
            'batas_bayar_hari' => 3,
            'persen_charge_ganti_jadwal' => 5,
            'kuota_default' => 5,
            'batas_ulasan_hari' => 7,
            'batas_video_mb' => 100,
            'nomor_whatsapp_admin' => '6281808606086',
        ])->save();
    }
}
