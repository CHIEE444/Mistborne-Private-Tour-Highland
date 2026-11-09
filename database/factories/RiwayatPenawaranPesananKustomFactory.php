<?php

namespace Database\Factories;

use App\Models\PesananKustom;
use App\Models\RiwayatPenawaranPesananKustom;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Satu versi penawaran yang sudah dikirim ke tamu, berikut salinan isinya saat itu.
 *
 * @extends Factory<RiwayatPenawaranPesananKustom>
 */
class RiwayatPenawaranPesananKustomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pesanan_kustom_id' => PesananKustom::factory(),
            'user_id' => null,
            'versi' => 1,
            'terkirim_pada' => now(),
            'snapshot' => [
                'rencana_perjalanan' => 'Hari 1: jemput dan perjalanan ke destinasi. Hari 2: sunrise dan wisata. Hari 3: kembali.',
                'rincian_penawaran' => 'Mobil, BBM, tol, guide-driver, dan tiket destinasi.',
                'harga_final' => 3_500_000,
            ],
        ];
    }
}
