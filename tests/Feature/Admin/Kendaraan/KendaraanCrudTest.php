<?php

namespace Tests\Feature\Admin\Kendaraan;

use App\Models\Kendaraan;
use App\Models\Pesanan;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KendaraanCrudTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    /**
     * @param  array<string, mixed>  $timpa
     * @return array<string, mixed>
     */
    private function dataValid(array $timpa = []): array
    {
        return [
            'nama' => 'Toyota Hiace Commuter',
            'jenis' => 'minibus',
            'plat_nomor' => 'b 1234 xyz',
            'kapasitas' => 12,
            'tahun' => 2022,
            'warna' => 'Putih',
            'status' => 'siap',
            'catatan' => 'Ada charger di tiap kursi.',
            ...$timpa,
        ];
    }

    public function test_tamu_tidak_bisa_mengakses_daftar_kendaraan(): void
    {
        $this->get(route('admin.kendaraan.indeks'))->assertRedirect(route('admin.autentikasi.masuk'));
    }

    public function test_admin_melihat_daftar_dan_bisa_mencari_serta_memfilter_status(): void
    {
        $user = User::factory()->create();
        Kendaraan::factory()->create(['nama' => 'Hiace Satu', 'plat_nomor' => 'B 1111 AAA']);
        Kendaraan::factory()->servis()->create(['nama' => 'Elf Servis', 'plat_nomor' => 'B 2222 BBB']);

        $this->actingAs($user)->get(route('admin.kendaraan.indeks'))
            ->assertOk()->assertSee('Hiace Satu')->assertSee('Elf Servis');

        $this->actingAs($user)->get(route('admin.kendaraan.indeks', ['cari' => '2222']))
            ->assertSee('Elf Servis')->assertDontSee('Hiace Satu');

        $this->actingAs($user)->get(route('admin.kendaraan.indeks', ['status' => 'servis']))
            ->assertSee('Elf Servis')->assertDontSee('Hiace Satu');
    }

    public function test_admin_menambah_kendaraan_dan_plat_dirapikan(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('admin.kendaraan.simpan'), $this->dataValid([
            'foto' => UploadedFile::fake()->image('hiace.jpg', 1600, 900),
        ]))->assertRedirect(route('admin.kendaraan.indeks'));

        $kendaraan = Kendaraan::where('nama', 'Toyota Hiace Commuter')->firstOrFail();
        $this->assertSame('B 1234 XYZ', $kendaraan->plat_nomor);
        $this->assertStringEndsWith('.webp', $kendaraan->foto);
        Storage::disk('public')->assertExists(ltrim(str_replace('/storage/', '', $kendaraan->foto), '/'));
    }

    public function test_data_perawatan_kendaraan_tersimpan_dan_boleh_dikosongkan_kecuali_odometer(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('admin.kendaraan.simpan'), $this->dataValid([
            'bahan_bakar' => 'diesel',
            'odometer' => '87420',
            'stnk_berlaku_sampai' => '2027-03-15',
            'pajak_berlaku_sampai' => '2026-12-31',
        ]))->assertSessionHasNoErrors();

        $kendaraan = Kendaraan::where('nama', 'Toyota Hiace Commuter')->firstOrFail();
        $this->assertSame('diesel', $kendaraan->bahan_bakar);
        $this->assertSame(87420, $kendaraan->odometer);
        $this->assertSame('2027-03-15', $kendaraan->stnk_berlaku_sampai->toDateString());
        $this->assertSame('2026-12-31', $kendaraan->pajak_berlaku_sampai->toDateString());

        $this->actingAs($user)->put(route('admin.kendaraan.perbarui', $kendaraan), $this->dataValid([
            'bahan_bakar' => '',
            'odometer' => '87420',
            'stnk_berlaku_sampai' => '',
            'pajak_berlaku_sampai' => '',
        ]))->assertSessionHasNoErrors();

        $kendaraan->refresh();
        $this->assertNull($kendaraan->bahan_bakar);
        $this->assertSame(87420, $kendaraan->odometer);
        $this->assertNull($kendaraan->stnk_berlaku_sampai);
        $this->assertNull($kendaraan->pajak_berlaku_sampai);
    }

    public function test_data_perawatan_yang_tidak_valid_ditolak(): void
    {
        $this->actingAs(User::factory()->create())->post(route('admin.kendaraan.simpan'), $this->dataValid([
            'bahan_bakar' => 'air',
            'odometer' => -1,
            'stnk_berlaku_sampai' => 'bukan-tanggal',
            'pajak_berlaku_sampai' => '31/31/2026',
        ]))->assertSessionHasErrors(['bahan_bakar', 'odometer', 'stnk_berlaku_sampai', 'pajak_berlaku_sampai']);

        $this->assertDatabaseCount('kendaraan', 11);
    }

    public function test_isian_wajib_dan_formatnya_divalidasi(): void
    {
        $user = User::factory()->create();

        foreach (['nama', 'jenis', 'plat_nomor', 'kapasitas', 'status'] as $kolom) {
            $this->actingAs($user)->post(route('admin.kendaraan.simpan'), $this->dataValid([$kolom => null]))
                ->assertSessionHasErrors($kolom);
        }

        $this->actingAs($user)->post(route('admin.kendaraan.simpan'), $this->dataValid([
            'plat_nomor' => '1234',
            'kapasitas' => 0,
            'tahun' => 1900,
            'jenis' => 'roket',
            'status' => 'hilang',
            'nama' => '<b>',
        ]))->assertSessionHasErrors(['plat_nomor', 'kapasitas', 'tahun', 'jenis', 'status', 'nama']);

        $this->assertDatabaseMissing('kendaraan', ['plat_nomor' => '1234']);
    }

    public function test_plat_nomor_harus_unik_tetapi_boleh_dipakai_lagi_setelah_dihapus(): void
    {
        $user = User::factory()->create();
        $lama = Kendaraan::factory()->create(['plat_nomor' => 'B 1234 XYZ']);

        $this->actingAs($user)->post(route('admin.kendaraan.simpan'), $this->dataValid())
            ->assertSessionHasErrors('plat_nomor');

        $lama->delete();

        $this->actingAs($user)->post(route('admin.kendaraan.simpan'), $this->dataValid())
            ->assertSessionHasNoErrors();
    }

    public function test_admin_mengubah_kendaraan_tanpa_bentrok_dengan_platnya_sendiri(): void
    {
        $user = User::factory()->create();
        $kendaraan = Kendaraan::factory()->create(['plat_nomor' => 'B 1234 XYZ']);

        $this->actingAs($user)->put(route('admin.kendaraan.perbarui', $kendaraan), $this->dataValid(['nama' => 'Nama Baru Hiace']))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.kendaraan.indeks'));

        $this->assertSame('Nama Baru Hiace', $kendaraan->fresh()->nama);
    }

    public function test_tahun_sebelum_1990_ditolak_tetapi_tahun_boleh_dikosongkan(): void
    {
        $user = User::factory()->create();
        $kendaraan = Kendaraan::factory()->create(['plat_nomor' => 'B 1234 XYZ', 'tahun' => null]);

        $this->actingAs($user)->put(route('admin.kendaraan.perbarui', $kendaraan), $this->dataValid(['tahun' => 1989]))
            ->assertSessionHasErrors('tahun');

        $this->actingAs($user)->put(route('admin.kendaraan.perbarui', $kendaraan), $this->dataValid(['tahun' => null, 'status' => 'servis']))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.kendaraan.indeks'));
        $this->assertSame('servis', $kendaraan->fresh()->status);
    }

    public function test_kendaraan_dengan_pesanan_mendatang_tidak_bisa_dihapus(): void
    {
        $user = User::factory()->create();
        $kendaraan = Kendaraan::factory()->create();
        Pesanan::factory()->create(['kendaraan_id' => $kendaraan->id, 'tanggal_trip' => now()->addDays(10)]);

        $this->actingAs($user)->delete(route('admin.kendaraan.hapus', $kendaraan))
            ->assertSessionHasErrors('kendaraan');

        $this->assertNotSoftDeleted('kendaraan', ['id' => $kendaraan->id]);
    }

    public function test_kendaraan_tidak_bisa_dihapus_saat_trip_multi_hari_sedang_berjalan(): void
    {
        $kendaraan = Kendaraan::factory()->create();
        Pesanan::factory()->for($kendaraan, 'kendaraan')->create(['tanggal_trip' => today()->subDay(), 'tanggal_selesai' => today()->addDay()]);

        $this->actingAs(User::factory()->create())->delete(route('admin.kendaraan.hapus', $kendaraan))
            ->assertSessionHasErrors('kendaraan');

        $this->assertNotSoftDeleted('kendaraan', ['id' => $kendaraan->id]);
    }

    public function test_kendaraan_bisa_dihapus_bila_trip_terakhirnya_sudah_berakhir_atau_dibatalkan(): void
    {
        $kendaraan = Kendaraan::factory()->create();
        Pesanan::factory()->for($kendaraan, 'kendaraan')->create(['tanggal_trip' => today()->subDays(3), 'tanggal_selesai' => today()->subDay()]);
        Pesanan::factory()->for($kendaraan, 'kendaraan')->dibatalkan()->create(['tanggal_trip' => today()->addDays(4)]);

        $this->actingAs(User::factory()->create())->delete(route('admin.kendaraan.hapus', $kendaraan))
            ->assertSessionHasNoErrors();

        $this->assertSoftDeleted('kendaraan', ['id' => $kendaraan->id]);
    }

    public function test_daftar_menghitung_trip_yang_sedang_berjalan_sebagai_pesanan_aktif(): void
    {
        $kendaraan = Kendaraan::factory()->create(['nama' => 'Unit Sedang Jalan']);
        Pesanan::factory()->for($kendaraan, 'kendaraan')->create(['tanggal_trip' => today()->subDay(), 'tanggal_selesai' => today()->addDay()]);

        $this->actingAs(User::factory()->create())->get(route('admin.kendaraan.indeks', ['cari' => 'Unit Sedang Jalan']))
            ->assertOk()
            ->assertSee('1 aktif');
    }

    public function test_mengubah_status_atau_kapasitas_menampilkan_peringatan_untuk_pesanan_yang_terdampak(): void
    {
        $kendaraan = Kendaraan::factory()->create(['kapasitas' => 8]);
        $pesanan = Pesanan::factory()->for($kendaraan, 'kendaraan')->create(['tanggal_trip' => today()->addDays(3), 'jumlah_wisatawan' => 6]);

        $this->actingAs(User::factory()->create())
            ->put(route('admin.kendaraan.perbarui', $kendaraan), $this->dataValid(['plat_nomor' => $kendaraan->plat_nomor, 'kapasitas' => 3, 'status' => 'servis']))
            ->assertRedirect(route('admin.kendaraan.indeks'))
            ->assertSessionHas('sukses')
            ->assertSessionHas('peringatan', fn (string $pesan): bool => str_contains($pesan, $pesanan->kode) && str_contains($pesan, 'status dalam servis'));

        $this->assertSame(3, $kendaraan->fresh()->kapasitas);
    }

    public function test_perubahan_yang_tidak_mengganggu_pesanan_tidak_memunculkan_peringatan(): void
    {
        $kendaraan = Kendaraan::factory()->create(['kapasitas' => 8]);
        Pesanan::factory()->for($kendaraan, 'kendaraan')->create(['tanggal_trip' => today()->addDays(3), 'jumlah_wisatawan' => 4]);

        $this->actingAs(User::factory()->create())
            ->put(route('admin.kendaraan.perbarui', $kendaraan), $this->dataValid(['plat_nomor' => $kendaraan->plat_nomor, 'kapasitas' => 6, 'status' => 'siap']))
            ->assertSessionMissing('peringatan');
    }

    public function test_kendaraan_tanpa_pesanan_bisa_dihapus(): void
    {
        $user = User::factory()->create();
        $kendaraan = Kendaraan::factory()->create();

        $this->actingAs($user)->delete(route('admin.kendaraan.hapus', $kendaraan))
            ->assertRedirect(route('admin.kendaraan.indeks'));

        $this->assertSoftDeleted('kendaraan', ['id' => $kendaraan->id]);
    }

    public function test_odometer_hanya_boleh_naik_saat_ubah_kendaraan(): void
    {
        $admin = User::factory()->create();
        $kendaraan = Kendaraan::factory()->create(['odometer' => 50_000]);
        $data = fn (array $timpa = []) => [
            'nama' => $kendaraan->nama, 'jenis' => $kendaraan->jenis, 'plat_nomor' => $kendaraan->plat_nomor,
            'kapasitas' => $kendaraan->kapasitas, 'status' => $kendaraan->status, ...$timpa,
        ];

        $this->actingAs($admin)->put(route('admin.kendaraan.perbarui', $kendaraan), $data(['odometer' => 49_999]))
            ->assertSessionHasErrors(['odometer' => 'Odometer hanya boleh naik: tidak boleh lebih kecil dari yang tercatat (50.000 km).']);
        $this->actingAs($admin)->put(route('admin.kendaraan.perbarui', $kendaraan), $data(['odometer' => '']))
            ->assertSessionHasErrors('odometer');
        $this->assertSame(50_000, $kendaraan->fresh()->odometer);

        $this->actingAs($admin)->put(route('admin.kendaraan.perbarui', $kendaraan), $data(['odometer' => 50_000]))->assertSessionHasNoErrors();
        $this->actingAs($admin)->put(route('admin.kendaraan.perbarui', $kendaraan), $data(['odometer' => 51_250]))->assertSessionHasNoErrors();
        $this->assertSame(51_250, $kendaraan->fresh()->odometer);
    }

    public function test_odometer_yang_belum_pernah_diisi_boleh_diisi_atau_dikosongkan(): void
    {
        $admin = User::factory()->create();
        $kendaraan = Kendaraan::factory()->create(['odometer' => null]);
        $data = fn (array $timpa = []) => [
            'nama' => $kendaraan->nama, 'jenis' => $kendaraan->jenis, 'plat_nomor' => $kendaraan->plat_nomor,
            'kapasitas' => $kendaraan->kapasitas, 'status' => $kendaraan->status, ...$timpa,
        ];

        $this->actingAs($admin)->put(route('admin.kendaraan.perbarui', $kendaraan), $data(['odometer' => '']))->assertSessionHasNoErrors();
        $this->actingAs($admin)->put(route('admin.kendaraan.perbarui', $kendaraan), $data(['odometer' => 1200]))->assertSessionHasNoErrors();
        $this->assertSame(1200, $kendaraan->fresh()->odometer);
    }
}
