<?php

namespace Database\Factories;

use App\Models\Perjalanan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Perjalanan>
 */
class PerjalananFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nama = mb_strtoupper($this->faker->unique()->words(3, true)).' JOURNEY';

        return [
            'jenis' => 'journey',
            'nama' => $nama,
            'region' => $this->faker->randomElement(['jawa-tengah', 'jawa-timur', 'jawa-barat']),
            'chip' => $this->faker->randomElement(['Esensial', 'Petualangan', 'Kustom']),
            'tempat' => $this->faker->city(),
            'tagline' => $this->faker->sentence(),
            'deskripsi' => $this->faker->paragraph(),
            'harga' => $this->faker->numberBetween(1_000_000, 4_000_000),
            'durasi_hari' => 2,
            'maks_orang' => 6,
            'img' => '/images/hero-2.jpg',
            'highlights' => $this->faker->sentences(4),
            'itinerary' => [
                ['t' => 'Hari 1', 'x' => $this->faker->sentence()],
                ['t' => 'Hari 2', 'x' => $this->faker->sentence()],
            ],
            'praktis' => [
                'pickup' => $this->faker->city(),
                'start' => 'Jam 03.30 WIB',
                'capacity' => 'Maks. 6 penumpang per booking',
                'tips' => $this->faker->sentence(),
            ],
            'tone' => Str::slug($nama),
            'rating_label' => number_format($this->faker->randomFloat(1, 4, 5), 1),
            'reviews_count' => $this->faker->numberBetween(10, 200),
            'tampil_beranda' => true,
            'urutan' => 0,
        ];
    }
}
