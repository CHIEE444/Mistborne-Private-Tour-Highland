<?php

namespace Tests\Unit\Layanan;

use App\Enums\BookingStatus;
use App\Layanan\BiayaJadwalLayanan;
use App\Models\Pengaturan;
use App\Models\Pesanan;
use Tests\TestCase;

class BiayaJadwalLayananTest extends TestCase
{
    private BiayaJadwalLayanan $layanan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->layanan = new BiayaJadwalLayanan;
    }

    public function test_dihubungi_selalu_boleh_ganti_jadwal_gratis(): void
    {
        $pesanan = Pesanan::factory()->create(['status' => BookingStatus::Dihubungi, 'tanggal_trip' => now()->addDay(), 'harga' => 2_000_000]);

        $this->assertTrue($this->layanan->bolehGantiJadwal($pesanan));
        $this->assertSame(0, $this->layanan->hitungCharge($pesanan));
    }

    public function test_payment_sisa_lebih_dari_h7_boleh_dan_kena_charge_pertama(): void
    {
        $pesanan = Pesanan::factory()->payment()->create(['tanggal_trip' => now()->addDays(8), 'harga' => 2_000_000]);

        $this->assertTrue($this->layanan->bolehGantiJadwal($pesanan));
        $this->assertSame(100_000, $this->layanan->hitungCharge($pesanan));
    }

    public function test_payment_tepat_h7_terkunci(): void
    {
        $pesanan = Pesanan::factory()->payment()->create(['tanggal_trip' => now()->addDays(7)]);

        $this->assertFalse($this->layanan->bolehGantiJadwal($pesanan));
    }

    public function test_payment_sisa_kurang_dari_h7_terkunci(): void
    {
        $pesanan = Pesanan::factory()->payment()->create(['tanggal_trip' => now()->addDays(3)]);

        $this->assertFalse($this->layanan->bolehGantiJadwal($pesanan));
    }

    public function test_completed_tidak_boleh_ganti_jadwal(): void
    {
        $pesanan = Pesanan::factory()->completed()->create(['tanggal_trip' => now()->addDays(30)]);

        $this->assertFalse($this->layanan->bolehGantiJadwal($pesanan));
    }

    public function test_dibatalkan_tidak_boleh_ganti_jadwal(): void
    {
        $pesanan = Pesanan::factory()->dibatalkan()->create(['tanggal_trip' => now()->addDays(30)]);

        $this->assertFalse($this->layanan->bolehGantiJadwal($pesanan));
    }

    public function test_charge_nol_bila_sudah_pernah_ganti_jadwal(): void
    {
        $pesanan = Pesanan::factory()->payment()->create(['tanggal_trip' => now()->addDays(20), 'harga' => 2_000_000]);
        $pesanan->penggantian()->create([
            'tanggal_lama' => now()->addDays(10),
            'tanggal_baru' => now()->addDays(20),
            'charge' => 100_000,
        ]);

        $this->assertFalse($this->layanan->adalahGantiPertama($pesanan));
        $this->assertSame(0, $this->layanan->hitungCharge($pesanan));
    }

    public function test_persen_default_lima(): void
    {
        $this->assertSame(5, $this->layanan->persen());
    }

    public function test_persen_mengikuti_pengaturan(): void
    {
        Pengaturan::current()->update(['persen_charge_ganti_jadwal' => 15]);

        $this->assertSame(15, $this->layanan->persen());
    }
}
