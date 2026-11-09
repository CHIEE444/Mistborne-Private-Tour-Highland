<?php

namespace Database\Factories;

use App\Models\AktivitasPersonel;
use App\Models\PersonelOperasional;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AktivitasPersonel>
 */
class AktivitasPersonelFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'personel_id' => PersonelOperasional::query()->create([
                'nama' => fake()->name(),
                'peran' => 'guide',
                'status_operasional' => 'siap',
                'aktif' => true,
            ])->id,
            'jenis' => 'masuk',
            'keterangan' => 'Masuk ke panel Guide.',
        ];
    }
}
