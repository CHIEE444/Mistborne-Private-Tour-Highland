<?php

namespace Tests\Feature\Admin\Pesanan;

use App\Layanan\RekapKeberangkatanLayanan;
use App\Models\Kendaraan;
use App\Models\Pesanan;
use App\Models\User;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class KendaraanPesananTest extends TestCase
{
    private function simpan(User $admin, Pesanan $pesanan, ?int $kendaraanId)
    {
        return $this->actingAs($admin)->put(route('admin.pesanan.operasional', $pesanan), ['kendaraan_id' => $kendaraanId]);
    }

    public function test_admin_menugaskan_kendaraan_ke_pesanan(): void
    {
        $admin = User::factory()->create();
        $pesanan = Pesanan::factory()->create(['jumlah_wisatawan' => 4]);
        $kendaraan = Kendaraan::factory()->create(['kapasitas' => 6]);

        $this->simpan($admin, $pesanan, $kendaraan->id)->assertSessionHasNoErrors();

        $this->assertSame($kendaraan->id, $pesanan->fresh()->kendaraan_id);
    }

    public function test_kendaraan_dilepas_saat_dikosongkan(): void
    {
        $admin = User::factory()->create();
        $kendaraan = Kendaraan::factory()->create(['kapasitas' => 6]);
        $pesanan = Pesanan::factory()->create(['jumlah_wisatawan' => 2, 'kendaraan_id' => $kendaraan->id]);

        $this->simpan($admin, $pesanan, null)->assertSessionHasNoErrors();

        $this->assertNull($pesanan->fresh()->kendaraan_id);
    }

    public function test_kendaraan_servis_nonaktif_atau_kurang_kursi_ditolak(): void
    {
        $admin = User::factory()->create();
        $pesanan = Pesanan::factory()->create(['jumlah_wisatawan' => 6]);

        foreach ([
            Kendaraan::factory()->servis()->create(['kapasitas' => 8]),
            Kendaraan::factory()->nonaktif()->create(['kapasitas' => 8]),
            Kendaraan::factory()->create(['kapasitas' => 4]),
        ] as $tidakLayak) {
            $this->simpan($admin, $pesanan, $tidakLayak->id)->assertSessionHasErrors('kendaraan_id');
        }

        $this->assertNull($pesanan->fresh()->kendaraan_id);
    }

    public function test_kendaraan_yang_dipakai_pesanan_lain_di_tanggal_sama_ditolak(): void
    {
        $admin = User::factory()->create();
        $tanggal = now()->addDays(15)->toDateString();
        $kendaraan = Kendaraan::factory()->create(['kapasitas' => 8]);
        Pesanan::factory()->create(['tanggal_trip' => $tanggal, 'kendaraan_id' => $kendaraan->id, 'status' => 'payment']);
        $pesananBaru = Pesanan::factory()->create(['tanggal_trip' => $tanggal, 'jumlah_wisatawan' => 2]);

        $this->simpan($admin, $pesananBaru, $kendaraan->id)->assertSessionHasErrors('kendaraan_id');
    }

    public function test_kendaraan_bisa_dipakai_lagi_di_tanggal_berbeda_atau_bila_pesanan_lain_dibatalkan(): void
    {
        $admin = User::factory()->create();
        $kendaraan = Kendaraan::factory()->create(['kapasitas' => 8]);
        Pesanan::factory()->create(['tanggal_trip' => now()->addDays(15)->toDateString(), 'kendaraan_id' => $kendaraan->id, 'status' => 'payment']);
        Pesanan::factory()->dibatalkan()->create(['tanggal_trip' => now()->addDays(40)->toDateString(), 'kendaraan_id' => $kendaraan->id]);

        $lainHari = Pesanan::factory()->create(['tanggal_trip' => now()->addDays(25)->toDateString(), 'jumlah_wisatawan' => 2]);
        $this->simpan($admin, $lainHari, $kendaraan->id)->assertSessionHasNoErrors();

        $hariPembatalan = Pesanan::factory()->create(['tanggal_trip' => now()->addDays(40)->toDateString(), 'jumlah_wisatawan' => 2]);
        $this->simpan($admin, $hariPembatalan, $kendaraan->id)->assertSessionHasNoErrors();
    }

    public function test_pilihan_kendaraan_tampil_di_detail_pesanan_hanya_yang_layak(): void
    {
        $admin = User::factory()->create();
        $pesanan = Pesanan::factory()->create(['jumlah_wisatawan' => 5]);
        Kendaraan::factory()->create(['nama' => 'Hiace Layak', 'kapasitas' => 12]);
        Kendaraan::factory()->create(['nama' => 'Mobil Kecil', 'kapasitas' => 3]);
        Kendaraan::factory()->servis()->create(['nama' => 'Mobil Servis', 'kapasitas' => 12]);

        $this->actingAs($admin)->get(route('admin.pesanan.detail', $pesanan))
            ->assertOk()
            ->assertSee('Hiace Layak')
            ->assertDontSee('Mobil Kecil')
            ->assertDontSee('Mobil Servis');
    }

    public function test_kendaraan_tampil_di_panel_kalender_dan_rekap_pdf(): void
    {
        $admin = User::factory()->create();
        $tanggal = now()->addDays(10)->toDateString();
        $kendaraan = Kendaraan::factory()->create(['nama' => 'Hiace Manifest', 'plat_nomor' => 'B 7777 MNF']);
        Pesanan::factory()->payment()->create(['tanggal_trip' => $tanggal, 'kendaraan_id' => $kendaraan->id]);

        $this->actingAs($admin)->get(route('admin.kalender-trip.indeks', ['bulan' => substr($tanggal, 0, 7), 'tanggal' => $tanggal]))
            ->assertSee('Hiace Manifest · B 7777 MNF');

        $rekap = app(RekapKeberangkatanLayanan::class)->bulanan(Carbon::parse($tanggal)->startOfMonth());
        $this->assertStringContainsString('Hiace Manifest · B 7777 MNF', view('cetak.rekap-keberangkatan', $rekap)->render());
    }
}
