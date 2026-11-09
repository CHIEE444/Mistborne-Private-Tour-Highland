<?php

namespace Database\Factories;

use App\Models\JadwalKeberangkatan;
use App\Models\Perjalanan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JadwalKeberangkatan>
 */
class JadwalKeberangkatanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'perjalanan_id' => Perjalanan::factory(),
            'tanggal' => now()->addDays($this->faker->unique()->numberBetween(7, 365))->toDateString(),
            'kuota' => null,
            'diblok' => false,
        ];
    }

    public function diblokir(): self
    {
        return $this->state(['diblok' => true]);
    }
}
