<?php

namespace Database\Factories;

use App\Models\PertanyaanTamu;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PertanyaanTamu>
 */
class PertanyaanTamuFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $journey = $this->faker->randomElement(['DIENG HIGHLAND ESCAPE', 'BROMO SUNRISE JOURNEY', 'IJEN VOLCANIC ESCAPE']);

        return [
            'sumber' => 'kontak',
            'status' => 'baru',
            'journey' => $journey,
            'lokasi_pickup' => 'Semarang / Yogyakarta',
            'tanggal_perjalanan' => null,
            'durasi' => '2 Hari 1 Malam',
            'jenis_perjalanan' => 'Esensial',
            'pesan' => "Halo Mistborne, saya ingin bertanya tentang journey.\nPilihan Journey: {$journey}",
        ];
    }

    public function dariKontak(): static
    {
        return $this->state(fn (): array => [
            'sumber' => 'kontak',
            'nama' => $this->faker->name(),
            'whatsapp' => '08'.$this->faker->numerify('##########'),
        ]);
    }
}
