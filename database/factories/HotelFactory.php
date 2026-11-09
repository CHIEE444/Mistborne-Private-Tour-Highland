<?php

namespace Database\Factories;

use App\Models\Hotel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Hotel>
 */
class HotelFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => 'Hotel '.$this->faker->unique()->company(),
            'bintang' => 4,
            'area' => $this->faker->randomElement(['Ngadisari, Bromo', 'Licin, Banyuwangi', 'Kejajar, Dieng', 'Ciwidey, Bandung']),
            'alamat' => $this->faker->address(),
            'whatsapp' => '08'.$this->faker->numerify('##########'),
            'tarif_mulai' => 450_000,
            'tarif_sampai' => 650_000,
            'kapasitas_kamar' => 2,
            'tersedia_extra_bed' => false,
            'fasilitas' => ['sarapan', 'ac', 'air_panas'],
            'jam_check_in' => '14:00',
            'jam_check_out' => '12:00',
            'aktif' => true,
        ];
    }

    public function nonaktif(): self
    {
        return $this->state(['aktif' => false]);
    }

    public function tanpaWhatsapp(): self
    {
        return $this->state(['whatsapp' => null]);
    }
}
