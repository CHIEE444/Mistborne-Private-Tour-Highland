<?php

namespace Tests\Feature\Admin;

use App\Models\Perjalanan;
use App\Models\PermintaanPesanan;
use App\Models\Pesanan;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class KalenderTripDurasiTest extends TestCase
{
    private function pesananTigaHari(): Pesanan
    {
        return Pesanan::factory()->payment()->create([
            'tanggal_trip' => '2027-03-10',
            'tanggal_selesai' => '2027-03-12',
            'durasi_hari' => 3,
        ]);
    }

    public function test_pesanan_tampil_di_semua_hari_perjalanan_pada_ringkasan_kalender(): void
    {
        $admin = User::factory()->create();
        $pesanan = $this->pesananTigaHari();

        foreach (['2027-03-10', '2027-03-11', '2027-03-12'] as $tanggal) {
            $this->actingAs($admin)->get(route('admin.kalender-trip.indeks', ['bulan' => '2027-03', 'tanggal' => $tanggal]))
                ->assertOk()
                ->assertSee($pesanan->kode);
        }

        $this->actingAs($admin)->get(route('admin.kalender-trip.indeks', ['bulan' => '2027-03', 'tanggal' => '2027-03-13']))
            ->assertOk()
            ->assertDontSee($pesanan->kode);
    }

    public function test_panel_hari_menunjukkan_hari_ke_berapa_dan_grid_menandai_hari_lanjutan(): void
    {
        $admin = User::factory()->create();
        $this->pesananTigaHari();

        $respons = $this->actingAs($admin)->get(route('admin.kalender-trip.indeks', ['bulan' => '2027-03', 'tanggal' => '2027-03-11']))->assertOk();

        $respons->assertSee('Hari ke-2 dari 3');
        $respons->assertSee('1 trip berjalan');
    }

    public function test_rentang_tanggal_pesanan_ditulis_lengkap_di_daftar_admin(): void
    {
        $pesanan = $this->pesananTigaHari();

        $this->assertSame('10 Mar – 12 Mar 2027', $pesanan->fresh()->rentangTanggalTeks());
    }

    public function test_formulir_booking_menampilkan_durasi_dan_tanggal_selesai_dinamis(): void
    {
        $perjalanan = Perjalanan::factory()->create(['durasi_hari' => 3]);

        $this->actingAs(User::factory()->create(['jabatan' => 'tamu']))->get(route('booking.buat', ['destination' => $perjalanan->nama]))
            ->assertOk()
            ->assertSee('Durasi perjalanan')
            ->assertSee('durasiHari: 3', false);
    }

    public function test_durasi_journey_dibedakan_dan_itinerary_tidak_melewati_durasinya(): void
    {
        $durasi = Perjalanan::query()->journey()->whereNotNull('durasi_hari')->get()->keyBy('nama');

        $this->assertSame(4, $durasi['BROMO SUNRISE JOURNEY']->durasi_hari);
        $this->assertSame(3, $durasi['IJEN VOLCANIC ESCAPE']->durasi_hari);
        $this->assertGreaterThan(1, $durasi->pluck('durasi_hari')->unique()->count());

        foreach ($durasi as $perjalanan) {
            $hariTerjauh = collect($perjalanan->itinerary)->map(fn (array $baris): int => (int) preg_replace('/\D.*$/', '', substr((string) $baris['t'], 5)))->max();
            $this->assertSame((int) $perjalanan->durasi_hari, (int) $hariTerjauh, "Itinerary {$perjalanan->nama} harus berakhir tepat di hari terakhir.");
        }
    }

    public function test_formulir_dari_link_admin_membawa_durasi_tiap_journey(): void
    {
        $perjalanan = Perjalanan::factory()->create(['durasi_hari' => 3]);
        $permintaan = PermintaanPesanan::query()->create([
            'token' => Str::random(64),
            'whatsapp' => '081234567890',
            'status' => 'menunggu_tamu',
            'token_berlaku_sampai' => now()->addDay(),
        ]);

        $html = $this->get(route('form-pesanan.tampil', $permintaan->token))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<option value="'.$perjalanan->id.'"[^>]*data-durasi="3"/', $html);
    }
}
