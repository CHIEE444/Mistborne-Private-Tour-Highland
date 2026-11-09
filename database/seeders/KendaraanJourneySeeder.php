<?php

namespace Database\Seeders;

use App\Models\Kendaraan;
use App\Models\Perjalanan;
use Illuminate\Database\Seeder;

class KendaraanJourneySeeder extends Seeder
{
    /**
     * Mobil tetap tiap destinasi sesuai medan (dicocokkan lewat nama). Hanya mengisi yang masih kosong; journey kustom tanpa mobil.
     */
    public function run(): void
    {
        foreach ($this->pasangan() as $mobil => $destinasi) {
            $kendaraan = Kendaraan::query()->where('nama', $mobil)->first();

            if ($kendaraan === null) {
                continue;
            }

            foreach ($destinasi as $nama => $alasan) {
                Perjalanan::query()
                    ->where('nama', $nama)
                    ->whereNull('kendaraan_id')
                    ->update(['kendaraan_id' => $kendaraan->id, 'alasan_kendaraan' => $alasan]);
            }
        }
    }

    /**
     * @return array<string, array<string, string>> nama mobil => [nama destinasi => alasan pemilihan]
     */
    private function pasangan(): array
    {
        return [
            'Toyota Land Cruiser FJ40' => [
                'BROMO SUNRISE JOURNEY' => 'Jeep 4x4 klasik untuk menyeberangi lautan pasir dan jalur berpasir kaldera Bromo.',
            ],
            'Toyota Hilux Double Cabin' => [
                'PAPANDAYAN CRATER JOURNEY' => 'Double cabin 4x4 yang kuat untuk jalan berbatu dan menanjak menuju area parkir kawah.',
            ],
            'Isuzu MU-X 4x4' => [
                'IJEN VOLCANIC ESCAPE' => 'SUV 4x4 bertenaga untuk tanjakan menuju Paltuding dan perjalanan dini hari.',
            ],
            'Toyota Fortuner' => [
                'DIENG HIGHLAND ESCAPE' => 'SUV tangguh untuk jalan pegunungan Dieng yang berkelok dan menanjak, dengan bagasi lega.',
                'Bukit Sikunir' => 'SUV tangguh untuk jalan pegunungan Dieng yang berkelok dan menanjak, dengan bagasi lega.',
                'Hutan Pinus' => 'SUV tangguh untuk jalan pegunungan Dieng yang berkelok dan menanjak, dengan bagasi lega.',
                'Telaga Sarangan & Cemoro Sewu' => 'SUV tangguh untuk tanjakan Cemoro Sewu dan jalan berkelok di kaki Gunung Lawu.',
            ],
            'Toyota Kijang Innova Venturer' => [
                'CIWIDEY & RANCABALI ESCAPE' => 'MPV nyaman untuk jalur kebun teh Ciwidey yang menanjak dan berkelok.',
            ],
            'Toyota Rush TRD' => [
                'Kawah Rengganis' => 'SUV ringkas yang lincah untuk jalur Ciwidey menuju kawah.',
            ],
            'Hyundai Santa Fe' => [
                'LEMBANG & TANGKUBAN PARAHU JOURNEY' => 'SUV nyaman untuk jalan aspal berkelok menuju Tangkuban Parahu.',
            ],
            'Nissan X-Trail' => [
                'BOROBUDUR & MERAPI SUNRISE JOURNEY' => 'SUV nyaman untuk jalan aspal menuju Borobudur dan Ketep Pass.',
            ],
            'Nissan Terra' => [
                'GREEN CANYON JOURNEY' => 'SUV berbagasi lega untuk perlengkapan basah dan perjalanan panjang di pantai selatan.',
            ],
        ];
    }
}
