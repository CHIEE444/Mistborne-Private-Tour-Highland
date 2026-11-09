<?php

namespace Database\Factories;

use App\Enums\StatusPenginapan;
use App\Models\Perjalanan;
use App\Models\PermintaanPesanan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Permintaan dari formulir booking website yang menunggu ditinjau admin.
 *
 * @extends Factory<PermintaanPesanan>
 */
class PermintaanPesananFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'token' => Str::random(64),
            'token_booking' => (string) Str::uuid(),
            'sumber' => 'booking',
            'whatsapp' => '08'.$this->faker->numerify('##########'),
            'status' => 'menunggu_tinjauan',
            'perjalanan_id' => Perjalanan::factory(),
            'nama_lengkap' => $this->faker->name(),
            'tanggal_trip' => now()->addDays(30)->toDateString(),
            'jumlah_wisatawan' => 2,
            'status_penginapan' => StatusPenginapan::Diurus,
            'lokasi_pickup' => $this->faker->streetAddress(),
            'waktu_pickup' => '05:00',
            'permintaan_khusus' => null,
            'token_berlaku_sampai' => PermintaanPesanan::batasBerlakuBaru(),
        ];
    }

    /** Tautan formulir sudah dikirim admin, tamu belum mengisi. */
    public function menungguTamu(): static
    {
        return $this->state(fn (): array => [
            'sumber' => 'form',
            'status' => 'menunggu_tamu',
            'perjalanan_id' => null,
            'nama_lengkap' => null,
            'tanggal_trip' => null,
            'jumlah_wisatawan' => null,
            'status_penginapan' => null,
            'lokasi_pickup' => null,
            'waktu_pickup' => null,
        ]);
    }
}
