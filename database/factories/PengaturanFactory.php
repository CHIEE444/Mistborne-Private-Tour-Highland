<?php

namespace Database\Factories;

use App\Models\Pengaturan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Baris pengaturan dengan nilai bawaan sistem (tabel ini hanya berisi satu baris, lihat Pengaturan::current()).
 *
 * @extends Factory<Pengaturan>
 */
class PengaturanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'batas_bayar_hari' => 3,
            'persen_charge_ganti_jadwal' => 5,
            'kuota_default' => 5,
            'batas_ulasan_hari' => 7,
            'batas_video_mb' => 100,
            'nomor_whatsapp_admin' => '62'.$this->faker->numerify('8##########'),
            'rekomendasi_penginapan' => null,
        ];
    }
}
