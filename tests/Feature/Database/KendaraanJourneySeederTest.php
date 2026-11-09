<?php

namespace Tests\Feature\Database;

use App\Models\Kendaraan;
use App\Models\Perjalanan;
use Database\Seeders\KendaraanJourneySeeder;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class KendaraanJourneySeederTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function pasangan(): array
    {
        return [
            'Bromo' => ['BROMO SUNRISE JOURNEY', 'Toyota Land Cruiser FJ40'],
            'Papandayan' => ['PAPANDAYAN CRATER JOURNEY', 'Toyota Hilux Double Cabin'],
            'Ijen' => ['IJEN VOLCANIC ESCAPE', 'Isuzu MU-X 4x4'],
            'Paket Telaga Sarangan' => ['Telaga Sarangan & Cemoro Sewu', 'Toyota Fortuner'],
            'Dieng' => ['DIENG HIGHLAND ESCAPE', 'Toyota Fortuner'],
            'Paket Bukit Sikunir' => ['Bukit Sikunir', 'Toyota Fortuner'],
            'Paket Hutan Pinus' => ['Hutan Pinus', 'Toyota Fortuner'],
            'Ciwidey' => ['CIWIDEY & RANCABALI ESCAPE', 'Toyota Kijang Innova Venturer'],
            'Paket Kawah Rengganis' => ['Kawah Rengganis', 'Toyota Rush TRD'],
            'Lembang' => ['LEMBANG & TANGKUBAN PARAHU JOURNEY', 'Hyundai Santa Fe'],
            'Borobudur' => ['BOROBUDUR & MERAPI SUNRISE JOURNEY', 'Nissan X-Trail'],
            'Green Canyon' => ['GREEN CANYON JOURNEY', 'Nissan Terra'],
        ];
    }

    #[DataProvider('pasangan')]
    public function test_tiap_destinasi_dipasangkan_dengan_mobil_dan_alasan_medannya(string $destinasi, string $mobil): void
    {
        $perjalanan = Perjalanan::query()->where('nama', $destinasi)->sole();

        $this->assertSame($mobil, $perjalanan->kendaraan?->nama);
        $this->assertNotEmpty($perjalanan->alasan_kendaraan);
    }

    public function test_journey_kustom_tidak_dipasangi_mobil(): void
    {
        $this->assertNull(Perjalanan::query()->where('nama', 'CUSTOM HIGHLAND JOURNEY')->sole()->kendaraan_id);
    }

    public function test_semua_mobil_destinasi_muat_kapasitas_journey_ditambah_guide(): void
    {
        $kursiStaf = (int) config('mistborne.kursi_staf', 1);

        foreach (Perjalanan::query()->whereNotNull('kendaraan_id')->with('kendaraan')->get() as $perjalanan) {
            $this->assertGreaterThanOrEqual(
                $perjalanan->maks_orang + $kursiStaf,
                $perjalanan->kendaraan->kapasitas,
                "{$perjalanan->nama} memakai {$perjalanan->kendaraan->nama} yang kursinya tidak muat.",
            );
        }
    }

    public function test_seeder_ulang_tidak_menimpa_mobil_atau_alasan_yang_diganti_admin(): void
    {
        $bromo = Perjalanan::query()->where('nama', 'BROMO SUNRISE JOURNEY')->sole();
        $hilux = Kendaraan::where('nama', 'Toyota Hilux Double Cabin')->value('id');
        $bromo->update(['kendaraan_id' => $hilux, 'alasan_kendaraan' => 'Alasan buatan admin.']);

        $this->seed(KendaraanJourneySeeder::class);

        $bromo->refresh();
        $this->assertSame($hilux, $bromo->kendaraan_id);
        $this->assertSame('Alasan buatan admin.', $bromo->alasan_kendaraan);
    }

    public function test_seeder_ulang_hanya_mengisi_destinasi_yang_mobilnya_masih_kosong(): void
    {
        $papandayan = Perjalanan::query()->where('nama', 'PAPANDAYAN CRATER JOURNEY')->sole();
        $papandayan->update(['kendaraan_id' => null, 'alasan_kendaraan' => null]);

        $this->seed(KendaraanJourneySeeder::class);

        $papandayan->refresh();
        $this->assertSame('Toyota Hilux Double Cabin', $papandayan->kendaraan->nama);
        $this->assertNotEmpty($papandayan->alasan_kendaraan);
    }

    public function test_mobil_yang_tidak_ada_di_armada_dilewati_tanpa_galat(): void
    {
        Kendaraan::where('nama', 'Nissan Terra')->forceDelete();
        Perjalanan::query()->where('nama', 'GREEN CANYON JOURNEY')->update(['kendaraan_id' => null, 'alasan_kendaraan' => null]);

        $this->seed(KendaraanJourneySeeder::class);

        $this->assertNull(Perjalanan::query()->where('nama', 'GREEN CANYON JOURNEY')->sole()->kendaraan_id);
    }
}
