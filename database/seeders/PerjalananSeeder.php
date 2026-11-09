<?php

namespace Database\Seeders;

use App\Layanan\HargaPerjalananLayanan;
use App\Models\Perjalanan;
use Illuminate\Database\Seeder;

class PerjalananSeeder extends Seeder
{
    /**
     * Isi tabel perjalanan dari data awal resources/data/journeys.php.
     */
    public function run(): void
    {
        $this->buatJourneyInternalTripKustom();

        $journeys = require base_path('resources/data/journeys.php');
        $layananHarga = app(HargaPerjalananLayanan::class);

        foreach ($journeys as $urutan => $data) {
            $hargaMentah = $data['price'] ?? null;
            // Harga dihitung dari lembar biaya (resources/data/biaya-perjalanan.php) bila journey ada di sana.
            $hitungan = isset($data['durasi_hari']) ? $layananHarga->untuk($data['name'], (int) $data['durasi_hari']) : null;
            $harga = $hitungan['harga'] ?? (is_string($hargaMentah) ? (int) preg_replace('/\D/', '', $hargaMentah) : null);

            Perjalanan::query()->updateOrCreate(
                ['nama' => $data['name']],
                [
                    'region' => $data['region'] ?? null,
                    'chip' => $data['chip'] ?? null,
                    'tempat' => $data['place'] ?? null,
                    'tagline' => $data['tagline'] ?? null,
                    'deskripsi' => $data['desc'] ?? null,
                    'harga' => $harga,
                    'durasi_hari' => $data['durasi_hari'] ?? null,
                    // Kapasitas dihitung untuk tamu saja; driver dan guide memakai 2 kursi kendaraan.
                    'maks_orang' => $data['maks_orang'] ?? 6,
                    'img' => $data['img'] ?? null,
                    'highlights' => $data['highlights'] ?? [],
                    'itinerary' => $data['itinerary'] ?? [],
                    'praktis' => $data['practical'] ?? [],
                    'tone' => $data['tone'] ?? null,
                    'rating_label' => $data['rating'] ?? null,
                    'reviews_count' => $data['reviews'] ?? null,
                    'rekomendasi_penginapan' => $data['rekomendasi_penginapan'] ?? null,
                    'tampil_beranda' => $data['home'] ?? true,
                    'urutan' => $urutan,
                ],
            );
        }

        $this->buatJadwalKeberangkatan();
    }

    /**
     * Journey internal sebagai jangkar pesanan hasil konversi Trip Kustom (JadikanPesananResmi).
     * Tidak tampil di beranda dan tidak punya harga maupun jadwal.
     */
    private function buatJourneyInternalTripKustom(): void
    {
        Perjalanan::query()->withTrashed()->updateOrCreate(
            ['slug' => 'trip-kustom-operasional'],
            [
                'nama' => 'Trip Kustom (Internal)',
                'maks_orang' => 50,
                'urutan' => 0,
                'tampil_beranda' => false,
                'template_pesanan_kustom' => true,
            ],
        );
    }

    /**
     * Tanggal contoh ±60 hari ke depan, hanya untuk journey yang sudah punya harga
     * (journey tanpa harga dianggap belum punya inventaris resmi — tampil "Jadwal menyusul").
     */
    private function buatJadwalKeberangkatan(): void
    {
        $polaHari = [8, 15, 22, 29, 36, 43, 50, 57];

        Perjalanan::query()->journey()->whereNotNull('harga')->each(function (Perjalanan $perjalanan) use ($polaHari): void {
            foreach ($polaHari as $tambahHari) {
                $perjalanan->jadwal()->updateOrCreate(
                    ['tanggal' => now()->addDays($tambahHari)->toDateString()],
                    ['kuota' => null, 'diblok' => false],
                );
            }
        });
    }
}
