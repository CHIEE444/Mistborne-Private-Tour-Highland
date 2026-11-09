<?php

namespace Tests\Unit\Layanan;

use App\Layanan\BatasBayarLayanan;
use App\Models\Pengaturan;
use App\Models\Pesanan;
use Tests\TestCase;

class BatasBayarLayananTest extends TestCase
{
    private BatasBayarLayanan $layanan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->layanan = new BatasBayarLayanan;
    }

    public function test_belum_dikonfirmasi_tidak_punya_batas_waktu(): void
    {
        $pesanan = Pesanan::factory()->create(['dikonfirmasi_at' => null]);

        $this->assertNull($this->layanan->batasWaktu($pesanan));
        $this->assertFalse($this->layanan->sudahLewat($pesanan));
    }

    public function test_batas_waktu_n_hari_setelah_dikonfirmasi(): void
    {
        $pesanan = Pesanan::factory()->create(['dikonfirmasi_at' => now()]);

        $batas = $this->layanan->batasWaktu($pesanan);

        $this->assertNotNull($batas);
        $this->assertSame(now()->addDays($this->layanan->jumlahHari())->toDateString(), $batas->toDateString());
    }

    public function test_belum_lewat_sebelum_batas_waktu(): void
    {
        $pesanan = Pesanan::factory()->create(['dikonfirmasi_at' => now()]);

        $this->assertFalse($this->layanan->sudahLewat($pesanan));
    }

    public function test_sudah_lewat_setelah_batas_waktu(): void
    {
        $pesanan = Pesanan::factory()->create(['dikonfirmasi_at' => now()->subDays($this->layanan->jumlahHari() + 1)]);

        $this->assertTrue($this->layanan->sudahLewat($pesanan));
    }

    public function test_jumlah_hari_mengikuti_pengaturan(): void
    {
        Pengaturan::current()->update(['batas_bayar_hari' => 5]);

        $this->assertSame(5, $this->layanan->jumlahHari());
    }
}
