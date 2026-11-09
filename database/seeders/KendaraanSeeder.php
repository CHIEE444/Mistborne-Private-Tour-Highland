<?php

namespace Database\Seeders;

use App\Models\Kendaraan;
use App\Models\ServisKendaraan;
use Illuminate\Database\Seeder;

class KendaraanSeeder extends Seeder
{
    /** Kolom data unit yang hanya diisi bila masih kosong, jadi perubahan admin tidak tertimpa. */
    private const KOLOM_PELENGKAP = ['tahun', 'warna', 'bahan_bakar', 'odometer', 'stnk_berlaku_sampai', 'pajak_berlaku_sampai', 'catatan'];

    /**
     * Armada awal (plat sementara "... DUM") dan satu-satunya sumber pilihan kendaraan. Kapasitas = kursi penumpang.
     * Aman dijalankan ulang: unit hanya dibuat bila plat/nama belum ada, dan data yang diubah admin tidak ditimpa.
     */
    public function run(): void
    {
        $armada = [
            ['nama' => 'Toyota Land Cruiser FJ40', 'jenis' => 'jeep', 'plat_nomor' => 'B 0001 DUM', 'kapasitas' => 6, 'tahun' => null, 'warna' => 'Merah (atap putih)', 'bahan_bakar' => 'diesel', 'odometer' => 84200, 'stnk_berlaku_sampai' => '2027-03-14', 'pajak_berlaku_sampai' => '2027-01-25', 'catatan' => 'Jeep klasik restorasi; mesin diesel, dipakai khusus rute pasir Bromo.'],
            ['nama' => 'Suzuki Jimny', 'jenis' => 'jeep', 'plat_nomor' => 'B 0003 DUM', 'kapasitas' => 4, 'tahun' => 2020, 'warna' => 'Hijau army', 'bahan_bakar' => 'bensin', 'odometer' => 46800, 'stnk_berlaku_sampai' => '2028-02-20', 'pajak_berlaku_sampai' => '2027-02-20', 'catatan' => 'Muat 3 tamu; dipakai untuk paket khusus dan cadangan.'],
            ['nama' => 'Isuzu MU-X 4x4', 'jenis' => 'suv', 'plat_nomor' => 'B 0004 DUM', 'kapasitas' => 7, 'tahun' => 2020, 'warna' => 'Putih', 'bahan_bakar' => 'diesel', 'odometer' => 61500, 'stnk_berlaku_sampai' => '2027-08-09', 'pajak_berlaku_sampai' => '2027-01-18', 'catatan' => 'Penggerak 4x4; cek ban dan suspensi sebelum rute tanjakan.'],
            ['nama' => 'Jeep Willys 4x4', 'jenis' => 'jeep', 'plat_nomor' => 'B 0005 DUM', 'kapasitas' => 3, 'tahun' => null, 'warna' => 'Hijau zaitun', 'bahan_bakar' => 'bensin', 'odometer' => 72300, 'stnk_berlaku_sampai' => '2027-05-30', 'pajak_berlaku_sampai' => '2027-01-12', 'catatan' => 'Jeep klasik; unit dinonaktifkan dari armada aktif.'],
            ['nama' => 'Toyota Fortuner', 'jenis' => 'suv', 'plat_nomor' => 'B 0006 DUM', 'kapasitas' => 7, 'tahun' => 2020, 'warna' => 'Putih', 'bahan_bakar' => 'diesel', 'odometer' => 58900, 'stnk_berlaku_sampai' => '2028-06-17', 'pajak_berlaku_sampai' => '2027-06-17', 'catatan' => 'Bagasi lega; servis berkala rutin tiap 10.000 km.'],
            ['nama' => 'Toyota Hilux Double Cabin', 'jenis' => 'double_cabin', 'plat_nomor' => 'B 0007 DUM', 'kapasitas' => 5, 'tahun' => 2020, 'warna' => 'Hitam', 'bahan_bakar' => 'diesel', 'odometer' => 67400, 'stnk_berlaku_sampai' => '2027-09-25', 'pajak_berlaku_sampai' => '2027-03-03', 'catatan' => 'Penggerak 4x4; cek ban dan suspensi sebelum rute berbatu.'],
            ['nama' => 'Toyota Kijang Innova Venturer', 'jenis' => 'mpv', 'plat_nomor' => 'B 0008 DUM', 'kapasitas' => 7, 'tahun' => 2020, 'warna' => 'Putih', 'bahan_bakar' => 'diesel', 'odometer' => 52100, 'stnk_berlaku_sampai' => '2028-01-11', 'pajak_berlaku_sampai' => '2027-01-11', 'catatan' => 'Kabin nyaman untuk rute kebun teh dan perjalanan panjang.'],
            ['nama' => 'Nissan Terra', 'jenis' => 'suv', 'plat_nomor' => 'B 0009 DUM', 'kapasitas' => 7, 'tahun' => 2020, 'warna' => 'Putih', 'bahan_bakar' => 'diesel', 'odometer' => 49700, 'stnk_berlaku_sampai' => '2027-11-04', 'pajak_berlaku_sampai' => '2027-02-22', 'catatan' => 'Bagasi lega untuk perlengkapan basah rute pantai selatan.'],
            ['nama' => 'Hyundai Santa Fe', 'jenis' => 'suv', 'plat_nomor' => 'B 0010 DUM', 'kapasitas' => 7, 'tahun' => 2020, 'warna' => 'Silver', 'bahan_bakar' => 'diesel', 'odometer' => 44300, 'stnk_berlaku_sampai' => '2028-04-02', 'pajak_berlaku_sampai' => '2027-04-02', 'catatan' => 'Unit paling baru dirawat; dipakai rute aspal wisata.'],
            ['nama' => 'Toyota Rush TRD', 'jenis' => 'suv', 'plat_nomor' => 'B 0011 DUM', 'kapasitas' => 7, 'tahun' => 2020, 'warna' => 'Putih', 'bahan_bakar' => 'bensin', 'odometer' => 55600, 'stnk_berlaku_sampai' => '2027-10-15', 'pajak_berlaku_sampai' => '2027-01-02', 'catatan' => 'Ukuran ringkas untuk jalur sempit.'],
            ['nama' => 'Nissan X-Trail', 'jenis' => 'suv', 'plat_nomor' => 'B 0012 DUM', 'kapasitas' => 7, 'tahun' => 2020, 'warna' => 'Silver', 'bahan_bakar' => 'bensin', 'odometer' => 47200, 'stnk_berlaku_sampai' => '2028-03-21', 'pajak_berlaku_sampai' => '2027-03-21', 'catatan' => 'Nyaman untuk jalan aspal jarak jauh.'],
        ];

        /** Foto awal per nama unit (path statis di public/). Hanya dipasang bila kendaraan belum punya foto, jadi unggahan admin tidak tertimpa. */
        $fotoAwal = [
            'Toyota Kijang Innova Venturer' => '/images/Venturer putih 2020.jpeg',
            'Toyota Fortuner' => '/images/toyota fortuner 2020 putih.png',
            'Toyota Hilux Double Cabin' => '/images/toyota hilux double cabin 2020.webp',
            'Suzuki Jimny' => '/images/suzuki jimny hijau army 2020.jpeg',
            'Nissan Terra' => '/images/2019-nissan-terra.jpg',
            'Hyundai Santa Fe' => '/images/hyundai santa fe 2020.webp',
            'Toyota Rush TRD' => '/images/toyota rush trd 2020.jpeg',
            'Nissan X-Trail' => '/images/nissan xtrail 2020.jpeg',
        ];

        foreach ($armada as $data) {
            $sudahAda = Kendaraan::query()->withTrashed()
                ->where(fn ($query) => $query->where('plat_nomor', $data['plat_nomor'])->orWhere('nama', $data['nama']))
                ->first();

            $kendaraan = $sudahAda ?? Kendaraan::query()->create([...$data, 'status' => 'siap']);

            $kolomKosong = collect($data)->only(self::KOLOM_PELENGKAP)->filter(fn ($nilai, $kolom) => blank($kendaraan->{$kolom}))->all();

            if ($kolomKosong !== []) {
                $kendaraan->update($kolomKosong);
            }

            if (! $kendaraan->trashed() && ! $kendaraan->servis()->exists()) {
                $this->isiCatatanServis($kendaraan);
            }

            if (blank($kendaraan->foto) && ! $kendaraan->trashed() && isset($fotoAwal[$data['nama']])) {
                $kendaraan->update(['foto' => $fotoAwal[$data['nama']]]);
            }
        }

        // Daihatsu Taft tidak lagi dioperasikan: dihapus dari armada (pesanan lama tetap menyimpan teks pilihan tamu).
        Kendaraan::query()->where('nama', 'Daihatsu Taft')->delete();
    }

    /** Satu riwayat servis dan satu jadwal servis berikutnya, supaya kesiapan kendaraan tidak menganggap catatan servis kosong. */
    private function isiCatatanServis(Kendaraan $kendaraan): void
    {
        $odometer = (int) $kendaraan->odometer;

        $kendaraan->servis()->create([
            'jenis' => 'Servis berkala',
            'status' => ServisKendaraan::SELESAI,
            'tanggal_servis' => today()->subMonths(2),
            'odometer_servis' => max($odometer - 3000, 0),
            'biaya' => 1500000,
            'catatan' => 'Ganti oli mesin, filter oli, dan pemeriksaan rem.',
        ]);

        $kendaraan->servis()->create([
            'jenis' => 'Ganti oli mesin',
            'status' => ServisKendaraan::TERJADWAL,
            'jatuh_tempo_tanggal' => today()->addMonths(4),
            'jatuh_tempo_odometer' => $odometer + 7000,
        ]);
    }
}
