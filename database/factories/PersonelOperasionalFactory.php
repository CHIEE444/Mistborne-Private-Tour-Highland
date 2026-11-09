<?php

namespace Database\Factories;

use App\Models\PersonelOperasional;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Guide (merangkap driver) aktif dengan SIM yang masih berlaku.
 *
 * @extends Factory<PersonelOperasional>
 */
class PersonelOperasionalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => $this->faker->name(),
            'peran' => PersonelOperasional::PERAN,
            'whatsapp' => '62'.$this->faker->numerify('8##########'),
            'email' => $this->faker->unique()->safeEmail(),
            'wilayah_operasi' => $this->faker->randomElement(['Dieng & Wonosobo', 'Malang & Bromo', 'Banyuwangi & Ijen', 'Bandung & Ciwidey']),
            'pengalaman_tahun' => $this->faker->numberBetween(1, 10),
            'keahlian' => null,
            'nomor_sim' => $this->faker->numerify('#### #### ####'),
            'jenis_sim' => 'A',
            'masa_berlaku_sim' => now()->addYears(2)->toDateString(),
            'pengalaman_mengemudi_tahun' => $this->faker->numberBetween(2, 15),
            'kendaraan_dikuasai' => 'MPV, SUV',
            'status_operasional' => 'siap',
            'catatan' => null,
            'aktif' => true,
        ];
    }

    /** Guide yang punya akun Panel Guide untuk masuk ke /personel. */
    public function denganAkun(): static
    {
        return $this->state(fn (array $atribut): array => [
            'user_id' => User::factory()->state(['email' => $atribut['email'], 'jabatan' => PersonelOperasional::PERAN]),
        ]);
    }
}
