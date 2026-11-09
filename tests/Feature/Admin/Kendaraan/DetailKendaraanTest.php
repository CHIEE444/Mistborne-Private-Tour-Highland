<?php

namespace Tests\Feature\Admin\Kendaraan;

use App\Models\Kendaraan;
use App\Models\Pesanan;
use App\Models\ServisKendaraan;
use App\Models\User;
use Tests\TestCase;

class DetailKendaraanTest extends TestCase
{
    public function test_tamu_diarahkan_ke_halaman_masuk(): void
    {
        $kendaraan = Kendaraan::factory()->create();

        $this->get(route('admin.kendaraan.detail', $kendaraan))->assertRedirect(route('admin.autentikasi.masuk'));
    }

    public function test_halaman_menampilkan_plat_spesifikasi_dan_tanggal_registrasi(): void
    {
        $kendaraan = Kendaraan::factory()->terawat()->create([
            'nama' => 'Toyota Kijang Innova Venturer',
            'jenis' => 'mpv',
            'plat_nomor' => 'B 0008 DUM',
            'kapasitas' => 7,
            'tahun' => 2020,
            'warna' => 'Putih',
            'odometer' => 87420,
            'bahan_bakar' => 'diesel',
            'foto' => '/images/Venturer putih 2020.jpeg',
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.kendaraan.detail', $kendaraan))
            ->assertOk()
            ->assertSeeInOrder(['B 0008 DUM', 'Toyota Kijang Innova Venturer', '2020'])
            ->assertSee('7 penumpang')
            ->assertSee('Putih')
            ->assertSee('Diesel')
            ->assertSee('87.420 km')
            ->assertSee($kendaraan->created_at->translatedFormat('d F Y'))
            ->assertSee('Venturer putih 2020.jpeg', false);
    }

    public function test_data_perawatan_yang_kosong_tampil_sebagai_keadaan_kosong_bukan_angka_karangan(): void
    {
        $kendaraan = Kendaraan::factory()->create(['odometer' => null, 'bahan_bakar' => null, 'foto' => null]);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.kendaraan.detail', $kendaraan))
            ->assertOk()
            ->assertSee('Belum diisi')
            ->assertSee('Belum ada servis terjadwal')
            ->assertSee('Belum ada riwayat servis')
            ->assertSee('Kendaraan ini belum pernah ditugaskan ke pesanan.')
            ->assertSee('Masa berlaku STNK belum diisi.');
    }

    public function test_halaman_menampilkan_servis_trip_dan_peringatan_bentrok(): void
    {
        $kendaraan = Kendaraan::factory()->terawat()->create(['odometer' => 40_000]);
        ServisKendaraan::factory()->for($kendaraan)->create(['jenis' => 'Ganti oli mesin', 'jatuh_tempo_tanggal' => null, 'jatuh_tempo_odometer' => 40_820]);
        ServisKendaraan::factory()->selesai()->for($kendaraan)->create(['jenis' => 'Tune-up & ganti filter', 'biaya' => 1_200_000]);
        $pertama = Pesanan::factory()->for($kendaraan, 'kendaraan')->create(['nama_perjalanan' => 'IJEN VOLCANIC ESCAPE', 'nama_lengkap' => 'Budi Santoso', 'tanggal_trip' => today()->addDays(4), 'tanggal_selesai' => today()->addDays(6)]);
        $kedua = Pesanan::factory()->for($kendaraan, 'kendaraan')->create(['tanggal_trip' => today()->addDays(6)]);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.kendaraan.detail', $kendaraan))
            ->assertOk()
            ->assertSee('Ganti oli mesin')
            ->assertSee('820 km lagi')
            ->assertSee('Tune-up &amp; ganti filter', false)
            ->assertSee('Rp 1.200.000')
            ->assertSee('IJEN VOLCANIC ESCAPE')
            ->assertSee('Budi Santoso')
            ->assertSee('Jadwal bertabrakan')
            ->assertSee("Pesanan {$pertama->kode} dan {$kedua->kode}");
    }

    public function test_halaman_menampilkan_skor_kesiapan_dan_hasil_pemeriksaan(): void
    {
        $kendaraan = Kendaraan::factory()->terawat()->create(['stnk_berlaku_sampai' => today()->subDay()]);
        ServisKendaraan::factory()->selesai()->for($kendaraan)->create();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.kendaraan.detail', $kendaraan))
            ->assertOk()
            ->assertSee('Skor kesiapan 80 dari 100', false)
            ->assertSee('Baik')
            ->assertSee('STNK kedaluwarsa 1 hari lalu');
    }

    public function test_trip_yang_sedang_berjalan_dihitung_dan_penugasan_tidak_layak_diperingatkan(): void
    {
        $kendaraan = Kendaraan::factory()->terawat()->create(['kapasitas' => 4]);
        $pesanan = Pesanan::factory()->for($kendaraan, 'kendaraan')->create(['tanggal_trip' => today()->subDay(), 'tanggal_selesai' => today()->addDay(), 'jumlah_wisatawan' => 6]);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.kendaraan.detail', $kendaraan))
            ->assertOk()
            ->assertSee('1 trip')
            ->assertSee('Penugasan tidak lagi layak')
            ->assertSee($pesanan->kode.': kapasitas 4 kursi kurang dari 7 kursi');
    }

    public function test_kendaraan_yang_sudah_dihapus_tidak_bisa_dibuka(): void
    {
        $kendaraan = Kendaraan::factory()->create();
        $kendaraan->delete();

        $this->actingAs(User::factory()->create())->get(route('admin.kendaraan.detail', $kendaraan->id))->assertNotFound();
    }

    public function test_daftar_kendaraan_menautkan_ke_halaman_detail(): void
    {
        $kendaraan = Kendaraan::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.kendaraan.indeks'))
            ->assertOk()
            ->assertSee(route('admin.kendaraan.detail', $kendaraan), false);
    }
}
