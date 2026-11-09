<?php

namespace Tests\Feature\Admin\KalenderTrip;

use App\Layanan\RekapKeberangkatanLayanan;
use App\Models\JadwalKeberangkatan;
use App\Models\Perjalanan;
use App\Models\PersonelOperasional;
use App\Models\Pesanan;
use App\Models\User;
use Tests\Support\DataGuide;
use Tests\TestCase;

class KalenderTripTest extends TestCase
{
    public function test_tamu_tidak_bisa_mengakses(): void
    {
        $this->get(route('admin.kalender-trip.indeks'))->assertRedirect(route('admin.autentikasi.masuk'));
    }

    public function test_admin_bisa_melihat_kalender(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.kalender-trip.indeks'))->assertOk();
    }

    public function test_navigasi_bulan_lain(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.kalender-trip.indeks', ['bulan' => now()->addMonth()->format('Y-m')]))
            ->assertOk();
    }

    public function test_klik_tanggal_menampilkan_panel_hari(): void
    {
        $user = User::factory()->create();
        $journey = Perjalanan::factory()->create();
        $pesanan = Pesanan::factory()->payment()->create([
            'perjalanan_id' => $journey->id,
            'nama_perjalanan' => $journey->nama,
            'nama_lengkap' => 'Budi Santoso',
            'tanggal_trip' => now()->addDays(10),
        ]);

        $response = $this->actingAs($user)->get(route('admin.kalender-trip.indeks', [
            'bulan' => $pesanan->tanggal_trip->format('Y-m'),
            'tanggal' => $pesanan->tanggal_trip->toDateString(),
        ]));

        $response->assertOk()->assertSee('Budi Santoso');
    }

    public function test_panel_hari_memisahkan_pesanan_terkonfirmasi_dan_yang_menunggu_pembayaran(): void
    {
        $user = User::factory()->create();
        $tanggal = now()->addDays(15);
        Pesanan::factory()->create(['status' => 'dihubungi', 'tanggal_trip' => $tanggal, 'nama_lengkap' => 'Belum Bayar']);
        Pesanan::factory()->create(['status' => 'hold_schedule', 'tanggal_trip' => $tanggal, 'nama_lengkap' => 'Sedang Hold']);
        Pesanan::factory()->payment()->create(['tanggal_trip' => $tanggal, 'nama_lengkap' => 'Sudah Bayar', 'waktu_pickup' => '05:30']);
        Pesanan::factory()->dibatalkan()->create(['tanggal_trip' => $tanggal, 'nama_lengkap' => 'Sudah Batal']);

        $response = $this->actingAs($user)->get(route('admin.kalender-trip.indeks', [
            'bulan' => $tanggal->format('Y-m'),
            'tanggal' => $tanggal->toDateString(),
        ]));

        $response->assertOk()
            ->assertSee('1 terkonfirmasi · 2 menunggu pembayaran')
            ->assertSee('Sudah Bayar')
            ->assertSee('Jemput 05:30 WIB')
            ->assertSee('Belum Bayar')
            ->assertSee('Sedang Hold')
            ->assertSee('tetap menahan kuota')
            ->assertDontSee('Sudah Batal')
            ->assertDontSee('Cetak Manifest Harian');
    }

    public function test_kalender_menawarkan_rekap_pdf_bulan_yang_dibuka_sesuai_filter_journey(): void
    {
        $journey = Perjalanan::factory()->create();
        $bulan = now()->addMonth()->format('Y-m');

        $this->actingAs(User::factory()->create())
            ->get(route('admin.kalender-trip.indeks', ['bulan' => $bulan, 'journey' => $journey->id]))
            ->assertOk()
            ->assertSee('Rekap PDF')
            ->assertSee(route('admin.kalender-trip.rekap-pdf', ['bulan' => $bulan, 'journey' => $journey->id]))
            ->assertDontSee('Cetak Manifest Harian');
    }

    public function test_rekap_pdf_memuat_jam_jemput_catatan_dan_urut_tanggal_lalu_jam_jemput(): void
    {
        $bulan = now()->addMonth()->startOfMonth();
        $journey = Perjalanan::factory()->create();
        Pesanan::factory()->payment()->create(['perjalanan_id' => $journey->id, 'nama_perjalanan' => $journey->nama, 'tanggal_trip' => $bulan->copy()->addDays(9), 'nama_lengkap' => 'Tamu Tanggal Akhir', 'waktu_pickup' => '04:00']);
        Pesanan::factory()->payment()->create(['perjalanan_id' => $journey->id, 'nama_perjalanan' => $journey->nama, 'tanggal_trip' => $bulan->copy()->addDays(2), 'nama_lengkap' => 'Tamu Siang', 'waktu_pickup' => '09:15', 'permintaan_khusus' => 'Alergi kacang']);
        Pesanan::factory()->payment()->create(['perjalanan_id' => $journey->id, 'nama_perjalanan' => $journey->nama, 'tanggal_trip' => $bulan->copy()->addDays(2), 'nama_lengkap' => 'Tamu Subuh', 'waktu_pickup' => '04:30', 'paket_opsional' => 'Sarapan tambahan']);

        $rekap = app(RekapKeberangkatanLayanan::class)->bulanan($bulan);
        $html = view('cetak.rekap-keberangkatan', $rekap)->render();

        $this->assertSame(3, $rekap['jumlahPesanan']);
        $this->assertSame(2, $rekap['jumlahHari']);
        $this->assertSame(['Tamu Subuh', 'Tamu Siang', 'Tamu Tanggal Akhir'], $rekap['perTanggal']->flatten()->pluck('nama_lengkap')->all());
        $this->assertStringContainsString('04:30 WIB', $html);
        $this->assertStringContainsString('Alergi kacang', $html);
        $this->assertStringContainsString('Sarapan tambahan', $html);
        $this->assertStringContainsString('Rekap Keberangkatan '.$bulan->translatedFormat('F Y'), $html);
    }

    public function test_dihubungi_dan_hold_schedule_mengurangi_kuota(): void
    {
        $journey = Perjalanan::factory()->create();
        $tanggal = now()->addDays(18);
        $jadwal = JadwalKeberangkatan::factory()->create([
            'perjalanan_id' => $journey->id,
            'tanggal' => $tanggal,
            'kuota' => 2,
        ]);

        Pesanan::factory()->create(['perjalanan_id' => $journey->id, 'status' => 'dihubungi', 'tanggal_trip' => $tanggal]);
        Pesanan::factory()->create(['perjalanan_id' => $journey->id, 'status' => 'hold_schedule', 'tanggal_trip' => $tanggal]);
        // Kuota dihitung per journey: pesanan journey lain di tanggal yang sama tidak memakai kuota ini.
        Pesanan::factory()->create(['status' => 'payment', 'tanggal_trip' => $tanggal]);

        $this->assertSame(2, $jadwal->terisi());
        $this->assertTrue($jadwal->penuh());
    }

    public function test_filter_journey_membatasi_panel_hari_dan_rekap_pdf(): void
    {
        $user = User::factory()->create();
        $journeyTerpilih = Perjalanan::factory()->create(['nama' => 'Journey Terpilih']);
        $journeyLain = Perjalanan::factory()->create(['nama' => 'Journey Lain']);
        $tanggal = now()->addDays(15);
        Pesanan::factory()->payment()->create([
            'perjalanan_id' => $journeyTerpilih->id,
            'nama_perjalanan' => $journeyTerpilih->nama,
            'tanggal_trip' => $tanggal,
            'nama_lengkap' => 'Tamu Terpilih',
        ]);
        Pesanan::factory()->payment()->create([
            'perjalanan_id' => $journeyLain->id,
            'nama_perjalanan' => $journeyLain->nama,
            'tanggal_trip' => $tanggal,
            'nama_lengkap' => 'Tamu Journey Lain',
        ]);

        $this->actingAs($user)
            ->get(route('admin.kalender-trip.indeks', [
                'bulan' => $tanggal->format('Y-m'),
                'tanggal' => $tanggal->toDateString(),
                'journey' => $journeyTerpilih->id,
            ]))
            ->assertOk()
            ->assertSee('Tamu Terpilih')
            ->assertDontSee('Tamu Journey Lain');

        $rekap = app(RekapKeberangkatanLayanan::class)->bulanan($tanggal->copy()->startOfMonth(), $journeyTerpilih->id);
        $this->assertSame(['Tamu Terpilih'], $rekap['perTanggal']->flatten()->pluck('nama_lengkap')->all());
        $this->assertSame('Journey Terpilih', $rekap['journeyTerpilih']->nama);
    }

    public function test_parameter_kalender_dan_rekap_pdf_divalidasi(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.kalender-trip.indeks', ['bulan' => 'bukan-bulan']))
            ->assertSessionHasErrors('bulan');

        $this->actingAs($user)
            ->get(route('admin.kalender-trip.indeks', ['tanggal' => '2026-02-31']))
            ->assertSessionHasErrors('tanggal');

        $this->actingAs($user)
            ->get(route('admin.kalender-trip.rekap-pdf', ['bulan' => '2026-13']))
            ->assertSessionHasErrors('bulan');
    }

    public function test_filter_journey(): void
    {
        $user = User::factory()->create();
        $journey = Perjalanan::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.kalender-trip.indeks', ['journey' => $journey->id]))
            ->assertOk();
    }

    public function test_statuskuota_tersedia_hampirpenuh_penuh_diblokir(): void
    {
        $journey = Perjalanan::factory()->create();

        $tersedia = JadwalKeberangkatan::factory()->create(['perjalanan_id' => $journey->id, 'tanggal' => now()->addDays(20), 'kuota' => 5]);
        $this->assertSame('tersedia', $tersedia->statusKuota());

        $hampirPenuh = JadwalKeberangkatan::factory()->create(['perjalanan_id' => $journey->id, 'tanggal' => now()->addDays(21), 'kuota' => 5]);
        Pesanan::factory()->payment()->count(4)->create(['perjalanan_id' => $journey->id, 'tanggal_trip' => $hampirPenuh->tanggal]);
        $this->assertSame('hampir_penuh', $hampirPenuh->statusKuota());

        $penuh = JadwalKeberangkatan::factory()->create(['perjalanan_id' => $journey->id, 'tanggal' => now()->addDays(22), 'kuota' => 5]);
        Pesanan::factory()->payment()->count(5)->create(['perjalanan_id' => $journey->id, 'tanggal_trip' => $penuh->tanggal]);
        $this->assertSame('penuh', $penuh->statusKuota());

        $diblokir = JadwalKeberangkatan::factory()->create(['perjalanan_id' => $journey->id, 'tanggal' => now()->addDays(23), 'diblok' => true]);
        $this->assertSame('diblokir', $diblokir->statusKuota());
    }

    public function test_jadwal_berkuota_satu_yang_masih_kosong_tidak_dianggap_hampir_penuh(): void
    {
        $journey = Perjalanan::factory()->create();
        $jadwal = JadwalKeberangkatan::factory()->create(['perjalanan_id' => $journey->id, 'tanggal' => now()->addDays(20), 'kuota' => 1]);

        $this->assertSame('tersedia', $jadwal->statusKuota());

        Pesanan::factory()->payment()->create(['perjalanan_id' => $journey->id, 'tanggal_trip' => $jadwal->tanggal]);
        $this->assertSame('penuh', $jadwal->statusKuota());
    }

    public function test_rekap_pdf_terunduh_sebagai_file_pdf_bernama_bulan(): void
    {
        $bulan = now()->addMonth()->startOfMonth();
        Pesanan::factory()->payment()->create(['tanggal_trip' => $bulan->copy()->addDays(4), 'nama_lengkap' => 'Tamu Rekap']);

        $response = $this->actingAs(User::factory()->create())->get(route('admin.kalender-trip.rekap-pdf', ['bulan' => $bulan->format('Y-m')]));

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload('Rekap-Keberangkatan-'.$bulan->translatedFormat('F-Y').'.pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_rekap_pdf_bulan_tanpa_keberangkatan_menampilkan_keterangan_kosong(): void
    {
        Pesanan::factory()->create(['status' => 'dihubungi', 'tanggal_trip' => now()->addMonths(2)->startOfMonth()->addDays(3)]);

        $rekap = app(RekapKeberangkatanLayanan::class)->bulanan(now()->addMonths(2)->startOfMonth());

        $this->assertSame(0, $rekap['jumlahPesanan']);
        $this->assertStringContainsString('Belum ada keberangkatan dari pesanan yang sudah bayar', view('cetak.rekap-keberangkatan', $rekap)->render());
    }

    public function test_tamu_tidak_bisa_mengunduh_rekap_pdf(): void
    {
        $this->get(route('admin.kalender-trip.rekap-pdf'))->assertRedirect(route('admin.autentikasi.masuk'));
    }

    public function test_sel_tanggal_menampilkan_trip_berjalan_dari_guide_siap_dan_semua_journey(): void
    {
        $user = User::factory()->create();
        $tanggal = now()->addDays(10);

        foreach (range(1, 4) as $nomor) {
            JadwalKeberangkatan::factory()->create([
                'perjalanan_id' => Perjalanan::factory()->create(['nama' => "JOURNEY NOMOR {$nomor}"])->id,
                'tanggal' => $tanggal,
                'kuota' => 3,
            ]);
        }
        Pesanan::factory()->count(3)->create(['status' => 'payment', 'tanggal_trip' => $tanggal]);

        $this->actingAs($user)
            ->get(route('admin.kalender-trip.indeks', ['bulan' => $tanggal->format('Y-m')]))
            ->assertOk()
            // 3 trip berjalan dari 5 guide siap (guide cadangan TestCase).
            ->assertSee('3/5')
            ->assertSee('+1 journey lain')
            ->assertDontSee('+1 lagi');
    }

    public function test_journey_berkuota_ditandai_penuh_saat_semua_guide_bertugas_di_hari_itu(): void
    {
        $this->tanpaGuideCadangan();
        PersonelOperasional::query()->create([...DataGuide::sim(), 'nama' => 'Guide Tunggal', 'peran' => 'guide', 'email' => 'guide.tunggal@example.com', 'status_operasional' => 'siap', 'aktif' => true]);
        $tanggal = now()->addDays(20);
        JadwalKeberangkatan::factory()->create(['perjalanan_id' => Perjalanan::factory()->create(['nama' => 'JOURNEY MASIH BERKUOTA'])->id, 'tanggal' => $tanggal, 'kuota' => 5]);
        Pesanan::factory()->payment()->create(['tanggal_trip' => $tanggal->toDateString(), 'tanggal_selesai' => $tanggal->toDateString()]);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.kalender-trip.indeks', ['bulan' => $tanggal->format('Y-m')]))
            ->assertOk()
            ->assertSee('1/1')
            ->assertSee('JOURNEY MASIH BERKUOTA · Penuh, semua guide bertugas');
    }

    public function test_tanggal_berpesanan_tanpa_baris_jadwal_tetap_terlihat_di_kalender(): void
    {
        $user = User::factory()->create();
        $tanggal = now()->addDays(12);
        Pesanan::factory()->create(['status' => 'dihubungi', 'tanggal_trip' => $tanggal]);

        $this->actingAs($user)
            ->get(route('admin.kalender-trip.indeks', ['bulan' => $tanggal->format('Y-m')]))
            ->assertSee('1/5');
    }

    public function test_kuota_tanggal_tidak_boleh_di_bawah_jumlah_pesanan_aktif(): void
    {
        $user = User::factory()->create();
        $journey = Perjalanan::factory()->create();
        $tanggal = now()->addDays(20);
        $jadwal = JadwalKeberangkatan::factory()->create(['perjalanan_id' => $journey->id, 'tanggal' => $tanggal, 'kuota' => 5]);
        Pesanan::factory()->count(3)->create(['perjalanan_id' => $journey->id, 'status' => 'payment', 'tanggal_trip' => $tanggal]);

        $this->actingAs($user)
            ->put(route('admin.perjalanan.tanggal.perbarui', [$journey, $jadwal]), ['kuota' => 2])
            ->assertSessionHasErrors('kuota');

        $this->assertSame(5, $jadwal->fresh()->kuota);
    }

    public function test_tanggal_yang_sudah_berpesanan_tidak_bisa_diblokir(): void
    {
        $user = User::factory()->create();
        $journey = Perjalanan::factory()->create();
        $tanggal = now()->addDays(30);
        $jadwal = JadwalKeberangkatan::factory()->create(['perjalanan_id' => $journey->id, 'tanggal' => $tanggal]);
        Pesanan::factory()->create(['perjalanan_id' => $journey->id, 'status' => 'payment', 'tanggal_trip' => $tanggal]);

        $this->actingAs($user)
            ->patch(route('admin.perjalanan.tanggal.blokir', [$journey, $jadwal]))
            ->assertSessionHasErrors('diblok');

        $this->assertFalse($jadwal->fresh()->diblok);
    }
}
