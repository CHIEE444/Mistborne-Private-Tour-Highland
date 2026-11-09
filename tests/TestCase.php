<?php

namespace Tests;

use App\Models\JadwalKeberangkatan;
use App\Models\Perjalanan;
use App\Models\PersonelOperasional;
use Carbon\CarbonInterface;
use Database\Seeders\KendaraanJourneySeeder;
use Database\Seeders\KendaraanSeeder;
use Database\Seeders\PaketWisataSeeder;
use Database\Seeders\PerjalananSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Carbon;
use Tests\Support\DataGuide;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Halaman publik (beranda, /journey) sekarang membaca journey dari database
     * (App\Models\Perjalanan), jadi setiap test butuh tabel ter-migrasi + terisi.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PerjalananSeeder::class);
        $this->seed(PaketWisataSeeder::class);
        // Pilihan kendaraan tamu dibaca dari tabel Kendaraan, jadi armada awal harus ada di setiap test.
        $this->seed(KendaraanSeeder::class);
        // Mobil tetap tiap destinasi (tamu tidak memilih mobil sendiri).
        $this->seed(KendaraanJourneySeeder::class);
        // Slot pesanan dibatasi jumlah guide (1 guide memegang 1 trip), jadi tes butuh guide siap seperti aplikasi nyata.
        $this->siapkanGuideCadangan();
    }

    /** Guide siap tambahan untuk kapasitas slot; nama diawali "Pemandu Cadangan" agar tidak bertabrakan dengan guide buatan tes. */
    protected function siapkanGuideCadangan(int $jumlah = 5): void
    {
        foreach (range(1, $jumlah) as $nomor) {
            PersonelOperasional::query()->create([
                ...DataGuide::sim(),
                'nama' => "Pemandu Cadangan Uji {$nomor}",
                'peran' => PersonelOperasional::PERAN,
                'email' => "pemandu.cadangan{$nomor}@example.com",
                'status_operasional' => 'siap',
                'aktif' => true,
            ]);
        }
    }

    /** Untuk tes yang menghitung jumlah guide: hanya guide buatan tes itu sendiri yang tersisa. */
    protected function tanpaGuideCadangan(): void
    {
        PersonelOperasional::query()->where('nama', 'like', 'Pemandu Cadangan Uji%')->delete();
    }

    /** Buka satu tanggal di Tanggal Tersedia journey (tes pemesanan hanya boleh memakai tanggal yang dibuka). */
    protected function bukaTanggal(Perjalanan|int $perjalanan, CarbonInterface|string $tanggal, ?int $kuota = null): JadwalKeberangkatan
    {
        $perjalananId = $perjalanan instanceof Perjalanan ? $perjalanan->id : $perjalanan;
        $tanggal = Carbon::parse($tanggal)->toDateString();
        $jadwal = JadwalKeberangkatan::query()->where('perjalanan_id', $perjalananId)->whereDate('tanggal', $tanggal)->first()
            ?? new JadwalKeberangkatan(['perjalanan_id' => $perjalananId, 'tanggal' => $tanggal]);

        $jadwal->fill(['kuota' => $kuota, 'diblok' => false])->save();

        return $jadwal;
    }
}
