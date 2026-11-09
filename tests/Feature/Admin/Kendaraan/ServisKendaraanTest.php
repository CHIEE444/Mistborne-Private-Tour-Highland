<?php

namespace Tests\Feature\Admin\Kendaraan;

use App\Models\Kendaraan;
use App\Models\ServisKendaraan;
use App\Models\User;
use Tests\TestCase;

class ServisKendaraanTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $timpa
     * @return array<string, mixed>
     */
    private function dataTerjadwal(array $timpa = []): array
    {
        return [
            'jenis' => 'Ganti oli mesin',
            'status' => 'terjadwal',
            'jatuh_tempo_tanggal' => today()->addMonth()->toDateString(),
            ...$timpa,
        ];
    }

    /**
     * @param  array<string, mixed>  $timpa
     * @return array<string, mixed>
     */
    private function dataSelesai(array $timpa = []): array
    {
        return [
            'jenis' => 'Tune-up & ganti filter',
            'status' => 'selesai',
            'tanggal_servis' => today()->subDay()->toDateString(),
            'odometer_servis' => 45_000,
            'biaya' => 1_200_000,
            ...$timpa,
        ];
    }

    private function admin(): User
    {
        return User::factory()->create();
    }

    public function test_tamu_tidak_bisa_menambah_servis(): void
    {
        $kendaraan = Kendaraan::factory()->create();

        $this->post(route('admin.kendaraan.servis.simpan', $kendaraan), $this->dataTerjadwal())->assertRedirect(route('admin.autentikasi.masuk'));

        $this->assertSame(0, $kendaraan->servis()->count());
    }

    public function test_admin_menambah_servis_terjadwal_dan_kembali_ke_detail(): void
    {
        $kendaraan = Kendaraan::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.kendaraan.servis.simpan', $kendaraan), $this->dataTerjadwal(['jatuh_tempo_odometer' => 90_000, 'catatan' => 'Pakai oli sintetis.']))
            ->assertRedirect(route('admin.kendaraan.detail', $kendaraan))
            ->assertSessionHas('sukses');

        $servis = $kendaraan->servis()->sole();
        $this->assertSame('terjadwal', $servis->status);
        $this->assertSame(90_000, $servis->jatuh_tempo_odometer);
        $this->assertSame(today()->addMonth()->toDateString(), $servis->jatuh_tempo_tanggal->toDateString());
    }

    public function test_servis_terjadwal_boleh_hanya_dengan_batas_kilometer(): void
    {
        $kendaraan = Kendaraan::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.kendaraan.servis.simpan', $kendaraan), $this->dataTerjadwal(['jatuh_tempo_tanggal' => '', 'jatuh_tempo_odometer' => 90_000]))
            ->assertSessionHasNoErrors();

        $this->assertNull($kendaraan->servis()->sole()->jatuh_tempo_tanggal);
    }

    public function test_servis_terjadwal_tanpa_tanggal_dan_kilometer_ditolak(): void
    {
        $kendaraan = Kendaraan::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.kendaraan.servis.simpan', $kendaraan), $this->dataTerjadwal(['jatuh_tempo_tanggal' => '']))
            ->assertSessionHasErrors(['jatuh_tempo_tanggal' => 'Isi tanggal jatuh tempo atau batas kilometer servis.']);

        $this->assertSame(0, $kendaraan->servis()->count());
    }

    public function test_servis_selesai_wajib_bertanggal_dan_tidak_boleh_di_masa_depan(): void
    {
        $kendaraan = Kendaraan::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.kendaraan.servis.simpan', $kendaraan), $this->dataSelesai(['tanggal_servis' => '']))
            ->assertSessionHasErrors('tanggal_servis');
        $this->actingAs($admin)
            ->post(route('admin.kendaraan.servis.simpan', $kendaraan), $this->dataSelesai(['tanggal_servis' => today()->addDay()->toDateString()]))
            ->assertSessionHasErrors('tanggal_servis');

        $this->assertSame(0, $kendaraan->servis()->count());
    }

    public function test_input_tidak_valid_ditolak_dengan_pesan_bahasa_indonesia(): void
    {
        $kendaraan = Kendaraan::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.kendaraan.servis.simpan', $kendaraan), $this->dataSelesai(['jenis' => '<b>', 'status' => 'lain', 'biaya' => -5, 'odometer_servis' => 'abc']))
            ->assertSessionHasErrors(['jenis', 'status', 'biaya', 'odometer_servis']);
    }

    public function test_servis_terjadwal_membuang_data_pengerjaan_yang_ikut_terkirim(): void
    {
        $kendaraan = Kendaraan::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.kendaraan.servis.simpan', $kendaraan), $this->dataTerjadwal(['tanggal_servis' => today()->toDateString(), 'biaya' => 500_000]))
            ->assertSessionHasNoErrors();

        $servis = $kendaraan->servis()->sole();
        $this->assertNull($servis->tanggal_servis);
        $this->assertNull($servis->biaya);
    }

    public function test_servis_selesai_menaikkan_odometer_kendaraan_tetapi_tidak_menurunkannya(): void
    {
        $kendaraan = Kendaraan::factory()->create(['odometer' => 40_000]);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.kendaraan.servis.simpan', $kendaraan), $this->dataSelesai(['odometer_servis' => 45_000]));
        $this->assertSame(45_000, $kendaraan->fresh()->odometer);

        $this->actingAs($admin)->post(route('admin.kendaraan.servis.simpan', $kendaraan), $this->dataSelesai(['odometer_servis' => 30_000]));
        $this->assertSame(45_000, $kendaraan->fresh()->odometer);
    }

    public function test_menandai_servis_terjadwal_selesai_mengosongkan_jatuh_tempo(): void
    {
        $kendaraan = Kendaraan::factory()->create(['odometer' => 40_000]);
        $servis = ServisKendaraan::factory()->for($kendaraan)->create(['jatuh_tempo_odometer' => 41_000]);

        $this->actingAs($this->admin())
            ->put(route('admin.kendaraan.servis.perbarui', [$kendaraan, $servis]), $this->dataSelesai(['jenis' => $servis->jenis, 'odometer_servis' => 41_200]))
            ->assertRedirect(route('admin.kendaraan.detail', $kendaraan));

        $servis->refresh();
        $this->assertSame('selesai', $servis->status);
        $this->assertNull($servis->jatuh_tempo_tanggal);
        $this->assertNull($servis->jatuh_tempo_odometer);
        $this->assertSame(1_200_000, $servis->biaya);
        $this->assertSame(41_200, $kendaraan->fresh()->odometer);
    }

    public function test_servis_milik_kendaraan_lain_tidak_bisa_diubah_atau_dihapus(): void
    {
        $kendaraan = Kendaraan::factory()->create();
        $servisLain = ServisKendaraan::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.kendaraan.servis.perbarui', [$kendaraan, $servisLain]), $this->dataSelesai())->assertNotFound();
        $this->actingAs($admin)->delete(route('admin.kendaraan.servis.hapus', [$kendaraan, $servisLain]))->assertNotFound();

        $this->assertModelExists($servisLain);
        $this->assertSame('terjadwal', $servisLain->fresh()->status);
    }

    public function test_admin_menghapus_catatan_servis(): void
    {
        $kendaraan = Kendaraan::factory()->create();
        $servis = ServisKendaraan::factory()->selesai()->for($kendaraan)->create();

        $this->actingAs($this->admin())
            ->delete(route('admin.kendaraan.servis.hapus', [$kendaraan, $servis]))
            ->assertRedirect(route('admin.kendaraan.detail', $kendaraan));

        $this->assertModelMissing($servis);
    }

    public function test_catatan_servis_ikut_terhapus_saat_kendaraan_dihapus_permanen(): void
    {
        $kendaraan = Kendaraan::factory()->create();
        $servis = ServisKendaraan::factory()->for($kendaraan)->create();

        $kendaraan->forceDelete();

        $this->assertModelMissing($servis);
    }
}
