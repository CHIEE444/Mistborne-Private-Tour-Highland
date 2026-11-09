<?php

namespace Tests\Unit\Layanan;

use App\Layanan\PesanWhatsAppLayanan;
use App\Models\Kendaraan;
use App\Models\Pesanan;
use Tests\TestCase;

class PesanWhatsAppLayananTest extends TestCase
{
    public function test_pengingat_h2_menampilkan_guide_yang_merangkap_driver_bila_terisi(): void
    {
        $pesanan = Pesanan::factory()->create([
            'lokasi_pickup' => 'Terminal Mendolo',
            'nama_hotel' => 'Hotel Dieng Asri',
            'nama_guide' => 'Pak Bambang',
        ]);

        $teks = app(PesanWhatsAppLayanan::class)->pengingatH2($pesanan);

        $this->assertStringContainsString('Guide (sekaligus driver): Pak Bambang', $teks);
        $this->assertStringNotContainsString('Driver:', $teks);
        $this->assertStringContainsString('Lokasi Pick-up: Terminal Mendolo', $teks);
        $this->assertStringContainsString('Penginapan: Hotel Dieng Asri', $teks);
    }

    public function test_pengingat_h2_menghilangkan_baris_guide_yang_masih_kosong(): void
    {
        $pesanan = Pesanan::factory()->create([
            'lokasi_pickup' => 'Terminal Mendolo',
            'nama_hotel' => 'Hotel Dieng Asri',
            'nama_guide' => null,
        ]);

        $teks = app(PesanWhatsAppLayanan::class)->pengingatH2($pesanan);

        $this->assertStringNotContainsString('Guide', $teks);
        $this->assertStringNotContainsString('Driver:', $teks);
        $this->assertStringContainsString('Jumlah Peserta: '.$pesanan->jumlah_wisatawan.' orang', $teks);
    }

    public function test_pengingat_h2_menampilkan_kendaraan_yang_ditugaskan_dan_menghilangkannya_bila_kosong(): void
    {
        $kendaraan = Kendaraan::factory()->create(['nama' => 'Toyota Fortuner', 'tahun' => 2020]);
        $denganUnit = Pesanan::factory()->create(['kendaraan_id' => $kendaraan->id]);
        $tanpaUnit = Pesanan::factory()->create(['kendaraan_id' => null]);

        $this->assertStringContainsString('Kendaraan: Toyota Fortuner 2020', app(PesanWhatsAppLayanan::class)->pengingatH2($denganUnit->fresh()));
        $this->assertStringNotContainsString('Kendaraan:', app(PesanWhatsAppLayanan::class)->pengingatH2($tanpaUnit));
    }
}
