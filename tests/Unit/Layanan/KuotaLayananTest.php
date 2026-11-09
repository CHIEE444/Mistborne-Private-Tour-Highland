<?php

namespace Tests\Unit\Layanan;

use App\Enums\BookingStatus;
use App\Layanan\KuotaLayanan;
use App\Models\JadwalKeberangkatan;
use App\Models\Perjalanan;
use App\Models\Pesanan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class KuotaLayananTest extends TestCase
{
    public function test_terisi_massal_menghitung_banyak_jadwal_dalam_satu_query(): void
    {
        $journey = Perjalanan::factory()->create();
        $jadwalA = JadwalKeberangkatan::factory()->create(['perjalanan_id' => $journey->id, 'tanggal' => now()->addDays(10)]);
        $jadwalB = JadwalKeberangkatan::factory()->create(['perjalanan_id' => $journey->id, 'tanggal' => now()->addDays(20)]);

        Pesanan::factory()->payment()->count(3)->create(['perjalanan_id' => $journey->id, 'tanggal_trip' => $jadwalA->tanggal]);
        Pesanan::factory()->payment()->count(1)->create(['perjalanan_id' => $journey->id, 'tanggal_trip' => $jadwalB->tanggal]);
        // Semua pesanan aktif menghitung kuota, termasuk Dihubungi.
        Pesanan::factory()->create(['perjalanan_id' => $journey->id, 'tanggal_trip' => $jadwalA->tanggal, 'status' => BookingStatus::Dihubungi]);

        $peta = (new KuotaLayanan)->terisiMassal([$jadwalA, $jadwalB]);

        $this->assertSame(4, $peta[$journey->id.'|'.$jadwalA->tanggal->toDateString()]);
        $this->assertSame(1, $peta[$journey->id.'|'.$jadwalB->tanggal->toDateString()]);
    }

    public function test_terapkan_ke_daftar_memakai_satu_query_bukan_per_baris(): void
    {
        $journey = Perjalanan::factory()->create();
        $daftarJadwal = JadwalKeberangkatan::factory()->count(5)->create(['perjalanan_id' => $journey->id]);

        DB::enableQueryLog();
        (new KuotaLayanan)->terapkanKeDaftar($daftarJadwal);
        $jumlahQuery = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(1, $jumlahQuery, 'terapkanKeDaftar() seharusnya cuma 1 query, bukan 1 per jadwal.');

        foreach ($daftarJadwal as $jadwal) {
            $this->assertSame(0, $jadwal->terisi());
        }
    }

    public function test_terisi_massal_kosong_untuk_daftar_jadwal_kosong(): void
    {
        $this->assertSame([], (new KuotaLayanan)->terisiMassal([]));
    }
}
