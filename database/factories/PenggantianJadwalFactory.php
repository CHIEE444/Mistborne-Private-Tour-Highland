<?php

namespace Database\Factories;

use App\Models\PenggantianJadwal;
use App\Models\Pesanan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PenggantianJadwal>
 */
class PenggantianJadwalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pesanan_id' => Pesanan::factory(),
            'tanggal_lama' => $this->faker->dateTimeBetween('+7 days', '+30 days')->format('Y-m-d'),
            'tanggal_baru' => $this->faker->dateTimeBetween('+31 days', '+60 days')->format('Y-m-d'),
            'charge' => 0,
        ];
    }
}
