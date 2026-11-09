<?php

namespace Database\Factories;

use App\Models\Artikel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Artikel>
 */
class ArtikelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'judul' => ucfirst($this->faker->sentence(6)),
            'kategori' => $this->faker->randomElement(config('mistborne.kategori_artikel')),
            'penulis' => $this->faker->name(),
            'ringkasan' => $this->faker->sentence(20),
            'isi' => implode("\n\n", $this->faker->paragraphs(5)),
            'img' => '/images/landscape-highland.jpg',
            'published_at' => now()->subDays($this->faker->numberBetween(1, 30)),
        ];
    }

    public function draf(): self
    {
        return $this->state(['published_at' => null]);
    }

    public function dijadwalkan(): self
    {
        return $this->state(['published_at' => now()->addDays($this->faker->numberBetween(1, 14))]);
    }
}
