<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Enums\StatusPenginapan;
use App\Enums\SumberPesanan;
use App\Models\Perjalanan;
use App\Models\Pesanan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pesanan>
 */
class PesananFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'perjalanan_id' => Perjalanan::factory(),
            'nama_perjalanan' => mb_strtoupper($this->faker->words(3, true)).' JOURNEY',
            'tanggal_trip' => $this->faker->dateTimeBetween('+7 days', '+90 days')->format('Y-m-d'),
            'nama_lengkap' => $this->faker->name(),
            'whatsapp' => '62'.$this->faker->numerify('8##########'),
            'nama_hotel' => $this->faker->company().' Hotel',
            'status_penginapan' => StatusPenginapan::Sudah,
            'lokasi_pickup' => $this->faker->address(),
            'jumlah_wisatawan' => $this->faker->numberBetween(1, 6),
            'harga' => $this->faker->numberBetween(1_500_000, 4_000_000),
            'status' => BookingStatus::Dihubungi,
            'sumber' => SumberPesanan::Manual,
        ];
    }

    public function payment(): self
    {
        return $this->state(['status' => BookingStatus::Payment, 'dikonfirmasi_at' => now()->subDays(2)]);
    }

    public function completed(): self
    {
        return $this->state(['status' => BookingStatus::Completed, 'dikonfirmasi_at' => now()->subDays(10)]);
    }

    public function dibatalkan(): self
    {
        return $this->state([
            'status' => BookingStatus::Dibatalkan,
            'status_sebelum_batal' => BookingStatus::Dihubungi->value,
            'pihak_pembatal' => 'pelanggan',
            'alasan_batal' => $this->faker->sentence(),
        ]);
    }
}
