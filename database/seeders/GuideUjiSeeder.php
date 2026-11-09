<?php

namespace Database\Seeders;

use App\Models\PersonelOperasional;
use Illuminate\Database\Seeder;

class GuideUjiSeeder extends Seeder
{
    /**
     * Guide uji untuk mencoba penugasan dan email ke personel. Semuanya memakai kontak bawaan (config mistborne.kontak_uji),
     * jadi email penugasan masuk ke satu kotak masuk. Tidak dimasukkan ke DatabaseSeeder; jalankan manual selama masa uji coba.
     * Aman dijalankan ulang: guide dicari lewat nama dan data yang sudah diubah admin tidak ditimpa.
     */
    public function run(): void
    {
        $whatsapp = (string) config('mistborne.kontak_uji.whatsapp');

        $guide = [
            ['nama' => 'Guide Uji Satu', 'wilayah_operasi' => 'Malang & Bromo', 'kendaraan_dikuasai' => 'Jeep klasik (petualangan dataran tinggi), Double cabin 4x4, SUV'],
            ['nama' => 'Guide Uji Dua', 'wilayah_operasi' => 'Dieng & Magelang', 'kendaraan_dikuasai' => 'SUV, MPV'],
        ];

        foreach ($guide as $data) {
            PersonelOperasional::query()->firstOrCreate(['nama' => $data['nama']], [
                ...$data,
                'peran' => PersonelOperasional::PERAN,
                'whatsapp' => '62'.substr($whatsapp, 1),
                'email' => config('mistborne.kontak_uji.email'),
                'pengalaman_tahun' => 5,
                'nomor_sim' => 'A 1234 5678 9012',
                'jenis_sim' => 'A',
                'masa_berlaku_sim' => now()->addYears(3)->toDateString(),
                'pengalaman_mengemudi_tahun' => 5,
                'status_operasional' => 'siap',
                'aktif' => true,
            ]);
        }
    }
}
