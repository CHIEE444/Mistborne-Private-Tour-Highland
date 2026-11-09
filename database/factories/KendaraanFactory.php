<?php

namespace Database\Factories;

use App\Models\Kendaraan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kendaraan>
 */
class KendaraanFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => $this->faker->randomElement(['Toyota Hiace Commuter', 'Toyota Innova Zenix', 'Isuzu Elf Long', 'Toyota Fortuner']),
            'jenis' => $this->faker->randomElement(array_keys(Kendaraan::JENIS)),
            'plat_nomor' => 'B '.$this->faker->unique()->numberBetween(1000, 9999).' '.strtoupper($this->faker->lexify('???')),
            'kapasitas' => $this->faker->numberBetween(4, 12),
            'tahun' => $this->faker->numberBetween(2017, 2025),
            'warna' => $this->faker->randomElement(['Putih', 'Hitam', 'Silver']),
            'status' => 'siap',
        ];
    }

    /** Data perawatan lengkap: odometer terisi, STNK dan pajak masih lama berlaku. */
    public function terawat(): self
    {
        return $this->state(fn (): array => [
            'bahan_bakar' => 'diesel',
            'odometer' => 40000,
            'stnk_berlaku_sampai' => today()->addYears(2),
            'pajak_berlaku_sampai' => today()->addMonths(8),
        ]);
    }

    public function servis(): self
    {
        return $this->state(['status' => 'servis']);
    }

    public function nonaktif(): self
    {
        return $this->state(['status' => 'nonaktif']);
    }
}
