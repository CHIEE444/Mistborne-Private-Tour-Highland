<?php

namespace Tests\Unit\Aksi;

use App\Aksi\Perjalanan\BlokirTanggal;
use App\Models\JadwalKeberangkatan;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BlokirTanggalTest extends TestCase
{
    public function test_tanggal_h_14_atau_lebih_boleh_diblokir(): void
    {
        $jadwal = JadwalKeberangkatan::factory()->create(['tanggal' => now()->addDays(14)]);

        $hasil = (new BlokirTanggal)->handle($jadwal, true);

        $this->assertTrue($hasil->fresh()->diblok);
    }

    public function test_tanggal_kurang_dari_h_14_ditolak_saat_diblokir(): void
    {
        $jadwal = JadwalKeberangkatan::factory()->create(['tanggal' => now()->addDays(10)]);

        $this->expectException(ValidationException::class);

        (new BlokirTanggal)->handle($jadwal, true);
    }

    public function test_membuka_blokir_tidak_terikat_aturan_h_14(): void
    {
        $jadwal = JadwalKeberangkatan::factory()->create(['tanggal' => now()->addDays(3), 'diblok' => true]);

        $hasil = (new BlokirTanggal)->handle($jadwal, false);

        $this->assertFalse($hasil->fresh()->diblok);
    }
}
