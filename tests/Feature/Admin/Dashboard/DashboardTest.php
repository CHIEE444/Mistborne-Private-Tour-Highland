<?php

namespace Tests\Feature\Admin\Dashboard;

use App\Enums\BookingStatus;
use App\Enums\StatusPesananKustom;
use App\Layanan\BatasBayarLayanan;
use App\Layanan\StatistikDashboardLayanan;
use App\Models\Kendaraan;
use App\Models\Perjalanan;
use App\Models\PersonelOperasional;
use App\Models\Pesanan;
use App\Models\PesananKustom;
use App\Models\Ulasan;
use App\Models\User;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    public function test_tamu_tidak_bisa_mengakses(): void
    {
        $this->get(route('admin.dashboard.indeks'))->assertRedirect(route('admin.autentikasi.masuk'));
    }

    public function test_admin_bisa_melihat_dashboard_dengan_data_lengkap(): void
    {
        $user = User::factory()->create();
        $journey = Perjalanan::factory()->create(['nama' => 'DASHBOARD JOURNEY TEST']);

        Pesanan::factory()->create(['status' => BookingStatus::Dihubungi, 'perjalanan_id' => $journey->id, 'nama_perjalanan' => $journey->nama, 'nama_lengkap' => 'Terbaru Tamu']);
        Pesanan::factory()->payment()->create(['perjalanan_id' => $journey->id, 'nama_perjalanan' => $journey->nama]);
        Pesanan::factory()->payment()->create(['perjalanan_id' => $journey->id, 'nama_perjalanan' => $journey->nama, 'tanggal_trip' => now()->addDays(7)]);
        Pesanan::factory()->payment()->create(['perjalanan_id' => $journey->id, 'nama_perjalanan' => $journey->nama, 'tanggal_trip' => now()->addDays(2)]);
        Pesanan::factory()->create(['status' => BookingStatus::Dihubungi, 'perjalanan_id' => $journey->id, 'nama_perjalanan' => $journey->nama, 'tanggal_trip' => now()->addDay()]);
        PesananKustom::factory()->create(['nama' => 'Kustom Dashboard Test']);

        $response = $this->actingAs($user)->get(route('admin.dashboard.indeks'));

        $response->assertOk()
            ->assertSee('Terbaru Tamu')
            ->assertSee('DASHBOARD JOURNEY TEST')
            ->assertSee('Kustom Dashboard Test')
            ->assertSee('Rating Google belum tersambung.');
    }

    public function test_kepala_dashboard_menggantikan_tombol_tambah_dengan_pengingat_hari_ini_berjumlah(): void
    {
        Pesanan::factory()->payment()->create(['tanggal_trip' => today()->addDays(5)]);

        $this->actingAs(User::factory()->create())->get(route('admin.dashboard.indeks'))
            ->assertOk()
            ->assertDontSee('Tambah Pesanan')
            ->assertDontSee('Tambah Journey')
            ->assertSeeInOrder(['Pengingat Hari Ini', '1</span>'], false);
    }

    public function test_popup_pengingat_tidak_terbuka_otomatis_saat_halaman_membawa_toast_hasil_aksi(): void
    {
        Pesanan::factory()->payment()->create(['tanggal_trip' => today()->addDays(5)]);
        $admin = User::factory()->create();

        $this->actingAs($admin)->get(route('admin.dashboard.indeks'))->assertSee('mistborne-pengingat-dilihat');

        $this->actingAs($admin)->withSession(['sukses' => 'Pengingat H-7 ditandai terkirim.'])->get(route('admin.dashboard.indeks'))
            ->assertOk()
            ->assertSee('1 pengingat ke tamu belum dikirim')
            ->assertDontSee('mistborne-pengingat-dilihat');
    }

    public function test_cek_akhir_h1_menandai_trip_besok_yang_siap_dan_menyebut_yang_belum_beres(): void
    {
        $guide = PersonelOperasional::query()->where('peran', PersonelOperasional::PERAN)->firstOrFail();
        Pesanan::factory()->payment()->create([
            'nama_lengkap' => 'Tamu Siap Berangkat',
            'tanggal_trip' => today()->addDay(),
            'harga' => 2_000_000,
            'nominal_dibayar' => 2_000_000,
            'guide_personel_id' => $guide->id,
            'status_konfirmasi_guide' => 'diterima',
            'kendaraan_id' => Kendaraan::query()->value('id'),
            'pengingat_h2_terkirim' => true,
        ]);
        Pesanan::factory()->create(['nama_lengkap' => 'Tamu Belum Bayar', 'status' => BookingStatus::Dihubungi, 'tanggal_trip' => today()->addDay(), 'harga' => 2_000_000]);

        $this->actingAs(User::factory()->create())->get(route('admin.dashboard.indeks'))
            ->assertOk()
            ->assertSee('Cek Akhir Berangkat Besok')
            ->assertSee('1 dari 2 trip besok belum siap berangkat.')
            ->assertSeeInOrder(['Tamu Belum Bayar', 'Belum siap', 'Pembayaran belum lunas', 'Guide belum menerima tugas', 'Info keberangkatan (H-2) belum dikirim ke tamu', 'Tamu Siap Berangkat', 'Siap berangkat']);
    }

    public function test_tombol_nanti_saja_menyimpan_tanggal_dan_isi_popup_sebagai_json_bukan_kode_blade_mentah(): void
    {
        $pesanan = Pesanan::factory()->payment()->create(['tanggal_trip' => today()->addDays(5)]);

        $this->actingAs(User::factory()->create())->get(route('admin.dashboard.indeks'))
            ->assertOk()
            ->assertDontSee('@js(', false)
            ->assertSee("localStorage.setItem('mistborne-pengingat-ditutup', '".e(json_encode(['tanggal' => today()->toDateString(), 'kunci' => [$pesanan->id.'-h7']]))."')", false);
    }

    public function test_popup_pengingat_tetap_bisa_dibuka_saat_tidak_ada_yang_perlu_ditangani(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.dashboard.indeks'))
            ->assertOk()
            ->assertSee('Pengingat Hari Ini')
            ->assertSee('Tidak ada yang perlu ditangani hari ini')
            ->assertDontSee('mistborne-pengingat-dilihat');
    }

    public function test_kartu_rating_menampilkan_ulasan_tamu_walau_google_belum_tersambung(): void
    {
        config(['services.google_places.api_key' => null]);
        Ulasan::factory()->create(['rating' => 5, 'komentar' => 'Sunrise Sikunir luar biasa.', 'disetujui' => true]);
        Ulasan::factory()->create(['rating' => 4, 'komentar' => 'Ulasan lama.', 'created_at' => now()->subDay()]);

        $this->actingAs(User::factory()->create())->get(route('admin.dashboard.indeks'))
            ->assertOk()
            ->assertSee('Rating Google belum tersambung.')
            ->assertSee('setelah profil Google Bisnis MISTBORNE dihubungkan')
            ->assertDontSee('GOOGLE_PLACES_API_KEY')
            ->assertSee('4.5')
            ->assertSee('2 ulasan tamu · 1 menunggu persetujuan')
            ->assertSee('Sunrise Sikunir luar biasa.');
    }

    public function test_dashboard_bisa_kosong_tanpa_data(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.dashboard.indeks'))->assertOk();
    }

    public function test_filter_tahun_grafik(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.dashboard.indeks', ['tahun' => now()->year - 1]))
            ->assertOk();
    }

    public function test_checkbox_pengingat_h7_bisa_ditandai(): void
    {
        $user = User::factory()->create();
        $pesanan = Pesanan::factory()->payment()->create(['tanggal_trip' => now()->addDays(7)]);

        $response = $this->actingAs($user)->patch(route('admin.pesanan.pengingat', [$pesanan, 'h7']));

        $response->assertRedirect();
        $this->assertTrue($pesanan->fresh()->pengingat_h7_terkirim);
    }

    public function test_checkbox_pengingat_bisa_di_toggle_lagi(): void
    {
        $user = User::factory()->create();
        $pesanan = Pesanan::factory()->payment()->create(['tanggal_trip' => now()->addDays(2), 'pengingat_h2_terkirim' => true]);

        $this->actingAs($user)->patch(route('admin.pesanan.pengingat', [$pesanan, 'h2']));

        $this->assertFalse($pesanan->fresh()->pengingat_h2_terkirim);
    }

    public function test_popup_pengingat_menampilkan_tahap_yang_belum_terkirim_dengan_tombol_kirim_sekali_klik(): void
    {
        $user = User::factory()->create();
        $telat = Pesanan::factory()->payment()->create(['nama_lengkap' => 'Tamu Telat H7', 'tanggal_trip' => today()->addDays(5)]);
        Pesanan::factory()->payment()->create(['nama_lengkap' => 'Tamu Sudah Dikirimi', 'tanggal_trip' => today()->addDays(2), 'pengingat_h2_terkirim' => true]);

        $html = $this->actingAs($user)->get(route('admin.dashboard.indeks'))
            ->assertOk()
            ->assertSee('1 pengingat ke tamu belum dikirim')
            ->assertSee("nama: 'popup-pengingat'", false)
            ->getContent();

        // Popup hanya memuat yang belum terkirim; yang sudah terkirim tetap terlihat di kartu pengingat.
        $popup = substr($html, strpos($html, 'pengingat ke tamu belum dikirim'), strpos($html, 'Lihat di kartu pengingat') - strpos($html, 'pengingat ke tamu belum dikirim'));
        $this->assertStringContainsString('Tamu Telat H7', $popup);
        $this->assertStringContainsString('Kirim pengingat H-7', $popup);
        $this->assertStringNotContainsString('Tamu Sudah Dikirimi', $popup);
        $this->assertStringContainsString('Tamu Sudah Dikirimi', $html);
        $this->assertStringContainsString(route('admin.pesanan.pengingat', [$telat, 'h7']), $popup);
    }

    public function test_popup_pengingat_tidak_muncul_bila_semua_sudah_terkirim(): void
    {
        Pesanan::factory()->payment()->create(['tanggal_trip' => today()->addDays(7), 'pengingat_h7_terkirim' => true]);

        $this->actingAs(User::factory()->create())->get(route('admin.dashboard.indeks'))
            ->assertOk()
            ->assertDontSee('pengingat ke tamu belum dikirim')
            ->assertSee('Semua terkirim');
    }

    public function test_statistik_pendapatan_bulan_ini_menghitung_uang_masuk_payment_dan_completed(): void
    {
        $journey = Perjalanan::factory()->create();
        Pesanan::factory()->payment()->create(['perjalanan_id' => $journey->id, 'harga' => 1_000_000, 'nominal_dibayar' => 300_000, 'tanggal_trip' => now()]);
        Pesanan::factory()->completed()->create(['perjalanan_id' => $journey->id, 'harga' => 2_000_000, 'nominal_dibayar' => 2_000_000, 'tanggal_trip' => now()]);
        Pesanan::factory()->create(['status' => BookingStatus::Dihubungi, 'perjalanan_id' => $journey->id, 'harga' => 5_000_000, 'nominal_dibayar' => 1_000_000, 'tanggal_trip' => now()]);

        $statistik = app(StatistikDashboardLayanan::class);

        $this->assertSame(2_300_000, $statistik->pendapatanBulanIni()['jumlah']);
    }

    public function test_perlu_ditindaklanjuti_menghitung_pesanan_kustom_dihubungi(): void
    {
        PesananKustom::factory()->create(['status' => StatusPesananKustom::Diproses]);
        PesananKustom::factory()->create(['status' => StatusPesananKustom::TidakJadi]);

        $statistik = app(StatistikDashboardLayanan::class);
        $hasil = $statistik->perluDitindaklanjuti(app(BatasBayarLayanan::class));

        $this->assertSame(1, $hasil['pesanan_kustom_dihubungi']);
    }

    public function test_grafik_pesanan_per_bulan_menghitung_total_dan_dibatalkan(): void
    {
        $tahun = now()->year;
        Pesanan::factory()->create(['created_at' => now()->setDate($tahun, 3, 10)]);
        Pesanan::factory()->create(['created_at' => now()->setDate($tahun, 3, 20)]);
        Pesanan::factory()->dibatalkan()->create(['created_at' => now()->setDate($tahun, 3, 25)]);
        Pesanan::factory()->create(['created_at' => now()->setDate($tahun - 1, 3, 10)]);

        $maret = app(StatistikDashboardLayanan::class)->pesananPerBulan($tahun)->get(2);

        $this->assertSame('Mar', $maret['label']);
        $this->assertSame(3, $maret['nilai']);
        $this->assertSame(1, $maret['nilaiKedua']);
    }

    public function test_dashboard_menormalkan_parameter_tahun_yang_tidak_wajar(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->get(route('admin.dashboard.indeks', ['tahun' => 99999]))->assertOk();
        $this->actingAs($admin)->get(route('admin.dashboard.indeks', ['tahun' => 'abc']))->assertOk();
    }

    public function test_perlu_ditindaklanjuti_menghitung_pesanan_yang_tidak_kebagian_guide(): void
    {
        // 5 guide siap (cadangan TestCase), tetapi 6 trip aktif berjalan di hari yang sama.
        $tanggal = now()->addDays(20)->startOfDay();
        Pesanan::factory()->count(6)->create(['tanggal_trip' => $tanggal, 'tanggal_selesai' => $tanggal, 'status' => BookingStatus::Dihubungi]);
        Pesanan::factory()->create(['tanggal_trip' => $tanggal->copy()->addDays(20), 'tanggal_selesai' => $tanggal->copy()->addDays(20), 'status' => BookingStatus::Dihubungi]);
        Pesanan::factory()->create(['tanggal_trip' => $tanggal, 'tanggal_selesai' => $tanggal, 'status' => BookingStatus::Dibatalkan]);

        $hasil = app(StatistikDashboardLayanan::class)->perluDitindaklanjuti(app(BatasBayarLayanan::class));

        $this->assertSame(6, $hasil['guide_tidak_cukup']);
    }
}
