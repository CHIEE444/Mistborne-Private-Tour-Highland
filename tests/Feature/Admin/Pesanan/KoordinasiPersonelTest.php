<?php

namespace Tests\Feature\Admin\Pesanan;

use App\Enums\BookingStatus;
use App\Layanan\BatasBayarLayanan;
use App\Layanan\StatistikDashboardLayanan;
use App\Models\PersonelOperasional;
use App\Models\Pesanan;
use App\Models\User;
use Tests\Support\DataGuide;
use Tests\TestCase;

class KoordinasiPersonelTest extends TestCase
{
    private function guide(string $nama): PersonelOperasional
    {
        return PersonelOperasional::query()->create([
            ...DataGuide::sim(),
            'nama' => $nama, 'peran' => 'guide', 'email' => strtolower(str_replace(' ', '.', $nama)).'@example.org', 'status_operasional' => 'siap', 'aktif' => true,
        ]);
    }

    /** @return array<string, mixed> */
    private function penugasan(string $statusGuide): array
    {
        $guide = $this->guide('Guide'.uniqid());

        return ['guide_personel_id' => $guide->id, 'nama_guide' => $guide->nama, 'status_konfirmasi_guide' => $statusGuide];
    }

    private function admin(): User
    {
        return User::factory()->create(['jabatan' => 'admin']);
    }

    public function test_penolakan_dan_penugasan_mendesak_dihitung_dari_data_pesanan(): void
    {
        $ditolak = Pesanan::factory()->payment()->create($this->penugasan('ditolak') + ['tanggal_trip' => today()->addDays(20)]);
        $aman = Pesanan::factory()->payment()->create($this->penugasan('diterima') + ['tanggal_trip' => today()->addDays(20)]);
        $dekatBelumLengkap = Pesanan::factory()->payment()->create(['tanggal_trip' => today()->addDays(3), 'guide_personel_id' => null]);
        $jauhBelumLengkap = Pesanan::factory()->payment()->create(['tanggal_trip' => today()->addDays(30), 'guide_personel_id' => null]);

        $this->assertSame(['Guide'], $ditolak->peranPenolak());
        $this->assertFalse($aman->penugasanDitolak());
        $this->assertTrue($dekatBelumLengkap->penugasanMendesak());
        $this->assertFalse($jauhBelumLengkap->penugasanMendesak());
        $this->assertFalse($aman->penugasanMendesak());
    }

    public function test_filter_penugasan_di_daftar_pesanan(): void
    {
        $admin = $this->admin();
        Pesanan::factory()->payment()->create($this->penugasan('ditolak') + ['nama_lengkap' => 'Tamu Ditolak', 'tanggal_trip' => today()->addDays(20)]);
        Pesanan::factory()->payment()->create($this->penugasan('menunggu') + ['nama_lengkap' => 'Tamu Menunggu', 'tanggal_trip' => today()->addDays(21)]);
        Pesanan::factory()->payment()->create(['nama_lengkap' => 'Tamu Belum Lengkap', 'tanggal_trip' => today()->addDays(22), 'guide_personel_id' => null]);
        Pesanan::factory()->payment()->create($this->penugasan('diterima') + ['nama_lengkap' => 'Tamu Aman', 'tanggal_trip' => today()->addDays(23)]);

        $this->actingAs($admin)->get(route('admin.pesanan.indeks', ['penugasan' => 'ditolak']))
            ->assertOk()->assertSee('Tamu Ditolak')->assertSee('Guide menolak')
            ->assertDontSee('Tamu Menunggu')->assertDontSee('Tamu Aman');
        $this->actingAs($admin)->get(route('admin.pesanan.indeks', ['penugasan' => 'menunggu']))
            ->assertSee('Tamu Menunggu')->assertDontSee('Tamu Ditolak');
        $this->actingAs($admin)->get(route('admin.pesanan.indeks', ['penugasan' => 'belum']))
            ->assertSee('Tamu Belum Lengkap')->assertDontSee('Tamu Aman');
        $this->actingAs($admin)->get(route('admin.pesanan.indeks', ['penugasan' => 'sembarang']))->assertSessionHasErrors('penugasan');
    }

    public function test_detail_pesanan_menyorot_penolakan_dan_menawarkan_penggantian(): void
    {
        $pesanan = Pesanan::factory()->payment()->create($this->penugasan('ditolak') + ['harga' => 1_000_000, 'nominal_dibayar' => 1_000_000, 'tanggal_trip' => today()->addDays(20)]);

        $this->actingAs($this->admin())->get(route('admin.pesanan.detail', $pesanan))
            ->assertOk()
            ->assertSee('Guide menolak tugas ini.')
            ->assertSee('Ganti Guide yang Menolak')
            ->assertSee('Pesanan belum bisa ditandai Trip Selesai sebelum penugasan diganti.');
    }

    public function test_detail_pesanan_tanpa_penolakan_tidak_menampilkan_peringatan(): void
    {
        $pesanan = Pesanan::factory()->payment()->create($this->penugasan('diterima') + ['harga' => 1_000_000, 'nominal_dibayar' => 1_000_000, 'tanggal_trip' => today()->addDays(20)]);

        $this->actingAs($this->admin())->get(route('admin.pesanan.detail', $pesanan))
            ->assertOk()
            ->assertDontSee('menolak tugas ini.')
            ->assertSee('Lihat Operasional');
    }

    public function test_dashboard_menghitung_penolakan_dan_penugasan_mendesak(): void
    {
        Pesanan::factory()->payment()->create($this->penugasan('ditolak') + ['tanggal_trip' => today()->addDays(20)]);
        Pesanan::factory()->payment()->create($this->penugasan('ditolak') + ['tanggal_trip' => today()->addDays(25)]);
        Pesanan::factory()->payment()->create(['tanggal_trip' => today()->addDays(4), 'guide_personel_id' => null]);
        Pesanan::factory()->payment()->create(['tanggal_trip' => today()->addDays(40), 'guide_personel_id' => null]);
        Pesanan::factory()->dibatalkan()->create($this->penugasan('ditolak') + ['tanggal_trip' => today()->addDays(20)]);

        $hasil = app(StatistikDashboardLayanan::class)->perluDitindaklanjuti(app(BatasBayarLayanan::class));

        $this->assertSame(2, $hasil['penugasan_ditolak']);
        $this->assertSame(1, $hasil['penugasan_mendesak']);

        $this->actingAs($this->admin())->get(route('admin.dashboard.indeks'))
            ->assertOk()
            ->assertSee('Guide menolak tugas')
            ->assertSee('Trip ≤ 7 hari, Guide belum ditugaskan');
    }

    public function test_detail_pesanan_siap_completed_menampilkan_tombol_tandai_completed(): void
    {
        $pesanan = Pesanan::factory()->payment()->create($this->penugasan('diterima') + [
            'harga' => 1_000_000, 'nominal_dibayar' => 1_000_000, 'tanggal_trip' => today()->subDays(2), 'tanggal_selesai' => today()->subDay(),
            'guide_trip_selesai_at' => now(),
        ]);

        $this->actingAs($this->admin())->get(route('admin.pesanan.detail', $pesanan))
            ->assertOk()
            ->assertSee('Tandai Trip Selesai')
            ->assertSee(route('admin.pesanan.selesaikan', $pesanan), false);
    }

    public function test_tidak_bisa_completed_selama_guide_menolak_dengan_pesan_jelas(): void
    {
        $pesanan = Pesanan::factory()->payment()->create($this->penugasan('ditolak') + [
            'harga' => 1_000_000, 'nominal_dibayar' => 1_000_000, 'tanggal_trip' => today()->subDays(2), 'tanggal_selesai' => today()->subDay(),
            'guide_trip_selesai_at' => now(),
        ]);

        $this->actingAs($this->admin())->patch(route('admin.pesanan.selesaikan', $pesanan))
            ->assertSessionHasErrors(['status' => 'Guide menolak tugas ini. Ganti penugasannya di kartu Operasional sebelum menandai Trip Selesai.']);
        $this->assertSame(BookingStatus::Payment, $pesanan->fresh()->status);
    }
}
