<?php

namespace Database\Factories;

use App\Enums\StatusPenginapan;
use App\Enums\StatusPesananKustom;
use App\Models\PesananKustom;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PesananKustom>
 */
class PesananKustomFactory extends Factory
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
            'whatsapp' => '62'.$this->faker->numerify('8##########'),
            'destinasi_diinginkan' => $this->faker->randomElement(['Dieng & Bromo', 'Ijen Private Trekking', 'Overland Jawa Tengah-Timur']),
            'jadwal_diinginkan' => $this->faker->randomElement(['Awal bulan depan', 'Libur akhir tahun', 'Fleksibel, 3-4 hari']),
            'jumlah_orang' => $this->faker->numberBetween(2, 8),
            'catatan' => $this->faker->sentence(),
            'tanggal_fleksibel' => false,
            'durasi_hari' => 3,
            'lokasi_penjemputan' => $this->faker->city(),
            'status_penginapan' => StatusPenginapan::Sudah,
            'nama_hotel' => $this->faker->company().' Hotel',
            'status' => StatusPesananKustom::Baru,
        ];
    }
}
