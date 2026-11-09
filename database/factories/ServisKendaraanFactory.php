<?php

namespace Database\Factories;

use App\Models\Kendaraan;
use App\Models\ServisKendaraan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServisKendaraan>
 */
class ServisKendaraanFactory extends Factory
{
    /**
     * Servis terjadwal dengan jatuh tempo bulan depan.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kendaraan_id' => Kendaraan::factory(),
            'jenis' => $this->faker->randomElement(ServisKendaraan::JENIS_UMUM),
            'status' => ServisKendaraan::TERJADWAL,
            'jatuh_tempo_tanggal' => today()->addMonth(),
        ];
    }

    public function selesai(): self
    {
        return $this->state(fn (): array => [
            'status' => ServisKendaraan::SELESAI,
            'jatuh_tempo_tanggal' => null,
            'tanggal_servis' => today()->subMonth(),
            'odometer_servis' => $this->faker->numberBetween(10000, 90000),
            'biaya' => $this->faker->numberBetween(2, 24) * 100000,
        ]);
    }

    public function terlambat(): self
    {
        return $this->state(['jatuh_tempo_tanggal' => today()->subDays(10)]);
    }
}
