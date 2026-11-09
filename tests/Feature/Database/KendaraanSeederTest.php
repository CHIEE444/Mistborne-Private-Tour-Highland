<?php

namespace Tests\Feature\Database;

use App\Models\Kendaraan;
use Database\Seeders\KendaraanSeeder;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class KendaraanSeederTest extends TestCase
{
    public function test_seeder_membuat_armada_awal_lengkap_dengan_foto_dari_public_images(): void
    {
        $this->assertSame(11, Kendaraan::count());
        $this->assertSame(0, Kendaraan::where('nama', 'Suzuki Katana')->count());
        $this->assertSame(8, Kendaraan::whereNotNull('foto')->count());

        foreach (Kendaraan::whereNotNull('foto')->get() as $kendaraan) {
            $this->assertFileExists(public_path(ltrim($kendaraan->foto, '/')), "Foto {$kendaraan->nama} tidak ada di public/");
        }
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string, 3: int, 4: ?string, 5: string}>
     */
    public static function unitBaru(): array
    {
        return [
            'Isuzu MU-X 4x4' => ['Isuzu MU-X 4x4', 'suv', 'B 0004 DUM', 7, 'Putih', ''],
            'Nissan Terra' => ['Nissan Terra', 'suv', 'B 0009 DUM', 7, 'Putih', '/images/2019-nissan-terra.jpg'],
            'Hyundai Santa Fe' => ['Hyundai Santa Fe', 'suv', 'B 0010 DUM', 7, 'Silver', '/images/hyundai santa fe 2020.webp'],
            'Toyota Rush TRD' => ['Toyota Rush TRD', 'suv', 'B 0011 DUM', 7, 'Putih', '/images/toyota rush trd 2020.jpeg'],
            'Nissan X-Trail' => ['Nissan X-Trail', 'suv', 'B 0012 DUM', 7, 'Silver', '/images/nissan xtrail 2020.jpeg'],
        ];
    }

    #[DataProvider('unitBaru')]
    public function test_unit_baru_tercatat_dengan_spesifikasi_tahun_2020_dan_foto_yang_sesuai(string $nama, string $jenis, string $plat, int $kapasitas, ?string $warna, string $foto): void
    {
        $kendaraan = Kendaraan::where('nama', $nama)->sole();

        $this->assertSame($jenis, $kendaraan->jenis);
        $this->assertSame($plat, $kendaraan->plat_nomor);
        $this->assertSame($kapasitas, $kendaraan->kapasitas);
        $this->assertSame(2020, $kendaraan->tahun);
        $this->assertSame($warna, $kendaraan->warna);
        $this->assertSame($foto === '' ? null : $foto, $kendaraan->foto);
        $this->assertSame('siap', $kendaraan->status);
    }

    public function test_fortuner_dan_hilux_mendapat_foto_dan_tetap_memakai_data_lamanya(): void
    {
        $fortuner = Kendaraan::where('nama', 'Toyota Fortuner')->sole();
        $hilux = Kendaraan::where('nama', 'Toyota Hilux Double Cabin')->sole();

        $this->assertSame('/images/toyota fortuner 2020 putih.png', $fortuner->foto);
        $this->assertSame('/images/toyota hilux double cabin 2020.webp', $hilux->foto);
        $this->assertSame(['Putih', 7], [$fortuner->warna, $fortuner->kapasitas]);
        $this->assertSame(['double_cabin', 5], [$hilux->jenis, $hilux->kapasitas]);
    }

    public function test_seeder_ulang_tidak_menimpa_data_yang_sudah_diubah_admin(): void
    {
        Kendaraan::where('plat_nomor', 'B 0006 DUM')->update(['status' => 'servis', 'kapasitas' => 5, 'warna' => 'Hitam']);

        $this->seed(KendaraanSeeder::class);

        $fortuner = Kendaraan::where('plat_nomor', 'B 0006 DUM')->sole();
        $this->assertSame('servis', $fortuner->status);
        $this->assertSame(5, $fortuner->kapasitas);
        $this->assertSame('Hitam', $fortuner->warna);
    }

    public function test_seeder_ulang_tidak_membuat_unit_ganda_setelah_plat_sementara_diganti(): void
    {
        Kendaraan::where('plat_nomor', 'B 0008 DUM')->update(['plat_nomor' => 'D 1234 ASL']);

        $this->seed(KendaraanSeeder::class);

        $this->assertSame(11, Kendaraan::count());
        $this->assertSame(0, Kendaraan::where('plat_nomor', 'B 0008 DUM')->count());
    }

    public function test_seeder_ulang_tidak_menghidupkan_kembali_unit_yang_sengaja_dihapus(): void
    {
        Kendaraan::where('plat_nomor', 'B 0004 DUM')->sole()->delete();

        $this->seed(KendaraanSeeder::class);

        $this->assertSame(10, Kendaraan::count());
        $this->assertSame(1, Kendaraan::onlyTrashed()->count());
    }

    public function test_foto_bawaan_hanya_dipasang_bila_kosong_dan_tidak_menimpa_unggahan_admin(): void
    {
        Kendaraan::where('nama', 'Toyota Kijang Innova Venturer')->update(['foto' => '/storage/kendaraan/unggahan-admin.webp']);
        $this->seed(KendaraanSeeder::class);
        $this->assertSame('/storage/kendaraan/unggahan-admin.webp', Kendaraan::where('nama', 'Toyota Kijang Innova Venturer')->value('foto'));

        Kendaraan::where('nama', 'Toyota Kijang Innova Venturer')->update(['foto' => null]);
        $this->seed(KendaraanSeeder::class);
        $this->assertSame('/images/Venturer putih 2020.jpeg', Kendaraan::where('nama', 'Toyota Kijang Innova Venturer')->value('foto'));
    }
}
