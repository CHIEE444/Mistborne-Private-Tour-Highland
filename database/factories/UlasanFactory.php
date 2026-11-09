<?php

namespace Database\Factories;

use App\Models\Pesanan;
use App\Models\Ulasan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ulasan>
 */
class UlasanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pesanan_id' => Pesanan::factory()->completed(),
            'rating' => $this->faker->numberBetween(3, 5),
            'komentar' => $this->faker->paragraph(),
            'izin_tampil' => true,
            'disetujui' => false,
        ];
    }

    public function disetujui(): self
    {
        return $this->state(['disetujui' => true]);
    }

    public function tanpaIzinTampil(): self
    {
        return $this->state(['izin_tampil' => false]);
    }

    public function denganVideo(): self
    {
        return $this->state(['video_path' => 'ulasan/video/contoh.mp4']);
    }
}
