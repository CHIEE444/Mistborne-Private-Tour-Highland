<?php

namespace Tests\Feature\Database;

use App\Enums\BookingStatus;
use App\Layanan\StatistikDashboardLayanan;
use App\Models\Artikel;
use App\Models\PenggantianJadwal;
use App\Models\Pesanan;
use App\Models\PesananKustom;
use App\Models\RiwayatPenawaranPesananKustom;
use App\Models\Ulasan;
use App\Models\User;
use Database\Seeders\DataContohSeeder;
use Tests\TestCase;

class DataContohSeederTest extends TestCase
{
    public function test_mengisi_satu_data_untuk_setiap_fitur_yang_masih_kosong(): void
    {
        User::factory()->create();

        $this->seed(DataContohSeeder::class);

        $this->assertSame(1, Artikel::query()->count());
        $this->assertSame(1, PesananKustom::query()->count());
        $this->assertSame(1, RiwayatPenawaranPesananKustom::query()->count());
        $this->assertSame(1, Pesanan::query()->count());
        $this->assertSame(1, PenggantianJadwal::query()->count());
        $this->assertSame(1, Ulasan::query()->count());
    }

    public function test_dijalankan_dua_kali_tidak_menggandakan_data(): void
    {
        $this->seed(DataContohSeeder::class);
        $this->seed(DataContohSeeder::class);

        $this->assertSame(1, Artikel::query()->count());
        $this->assertSame(1, PesananKustom::query()->count());
        $this->assertSame(1, Pesanan::query()->count());
        $this->assertSame(1, Ulasan::query()->count());
    }

    public function test_data_contoh_tidak_tampil_di_situs_publik_dan_tidak_menahan_kuota(): void
    {
        $this->seed(DataContohSeeder::class);

        $artikel = Artikel::query()->firstOrFail();
        $this->assertNull($artikel->published_at);
        $this->get(route('articles.show', $artikel->slug))->assertNotFound();

        $this->assertFalse(Ulasan::query()->firstOrFail()->disetujui);

        $pesanan = Pesanan::query()->firstOrFail();
        $this->assertSame(BookingStatus::Completed, $pesanan->status);
        $this->assertTrue($pesanan->tanggal_trip->isPast());
        $this->assertSame($pesanan->tanggal_trip->toDateString(), PenggantianJadwal::query()->firstOrFail()->tanggal_baru->toDateString());
    }

    public function test_semua_data_contoh_diberi_tanda_agar_mudah_dihapus(): void
    {
        $this->seed(DataContohSeeder::class);

        $this->assertStringContainsString('(Contoh)', Artikel::query()->firstOrFail()->judul);
        $this->assertStringContainsString('(Contoh)', PesananKustom::query()->firstOrFail()->nama);
        $this->assertStringContainsString('(Contoh)', Pesanan::query()->firstOrFail()->nama_lengkap);
    }

    public function test_halaman_admin_menampilkan_data_contoh_tanpa_error(): void
    {
        $admin = User::factory()->create();
        $this->seed(DataContohSeeder::class);
        $artikel = Artikel::query()->firstOrFail();
        $kustom = PesananKustom::query()->firstOrFail();
        $pesanan = Pesanan::query()->firstOrFail();

        $this->actingAs($admin);

        $this->get(route('admin.artikel.indeks'))->assertOk()->assertSee('Tips Memotret Sunrise');
        $this->get(route('admin.artikel.ubah', $artikel))->assertOk()->assertSee('Tips Memotret Sunrise');
        $this->get(route('admin.pesanan-kustom.indeks'))->assertOk()->assertSee('Rina Wulandari');
        $this->get(route('admin.pesanan-kustom.detail', $kustom->id))->assertOk()->assertSee('Rina Wulandari');
        $this->get(route('admin.pesanan.indeks', ['status' => 'completed']))->assertOk()->assertSee('Dewi Anggraini');
        $this->get(route('admin.pesanan.detail', $pesanan))->assertOk()->assertSee('Dewi Anggraini');
        $this->get(route('admin.ulasan.indeks'))->assertOk()->assertSee('Dewi Anggraini');
    }

    public function test_data_contoh_tidak_mengubah_angka_kpi_dashboard(): void
    {
        $sebelum = app(StatistikDashboardLayanan::class)->pendapatanBulanIni();
        $kuotaSebelum = Pesanan::query()->count();

        $this->seed(DataContohSeeder::class);

        $sesudah = app(StatistikDashboardLayanan::class)->pendapatanBulanIni();
        $this->assertSame(1, Pesanan::query()->count() - $kuotaSebelum);
        $this->assertSame($sebelum['jumlah'], $sesudah['jumlah']);
        $this->assertSame($sebelum['sparkline'], $sesudah['sparkline']);
        $this->assertSame($sebelum['tren'], $sesudah['tren']);
        $this->assertTrue(Pesanan::query()->firstOrFail()->created_at->lt(now()->subMonths(2)));
    }
}
