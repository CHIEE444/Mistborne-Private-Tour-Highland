<?php

namespace Tests\Feature\Admin\Perjalanan;

use App\Models\Kendaraan;
use App\Models\Perjalanan;
use App\Models\User;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class MobilDestinasiAdminTest extends TestCase
{
    private function journey(string $nama = 'BROMO SUNRISE JOURNEY'): Perjalanan
    {
        return Perjalanan::query()->where('nama', $nama)->firstOrFail();
    }

    /**
     * Data formulir ubah yang valid, diambil dari journey yang ada.
     *
     * @param  array<string, mixed>  $timpa
     * @return array<string, mixed>
     */
    private function data(Perjalanan $journey, array $timpa = []): array
    {
        return [
            'nama' => $journey->nama,
            'region' => $journey->region,
            'tempat' => $journey->tempat,
            'tagline' => $journey->tagline,
            'deskripsi' => $journey->deskripsi,
            'harga' => $journey->harga,
            'durasi_hari' => $journey->durasi_hari,
            'maks_orang' => $journey->maks_orang,
            'urutan' => $journey->urutan,
            'highlights' => $journey->highlights,
            'itinerary' => $journey->itinerary,
            'praktis' => $journey->praktis,
            'kendaraan_id' => $journey->kendaraan_id,
            'alasan_kendaraan' => $journey->alasan_kendaraan,
            ...$timpa,
        ];
    }

    private function simpan(Perjalanan $journey, array $timpa = []): TestResponse
    {
        return $this->actingAs(User::factory()->create())->put(route('admin.perjalanan.perbarui', $journey), $this->data($journey, $timpa));
    }

    public function test_form_ubah_menampilkan_pilihan_mobil_dan_alasan_tanpa_input_teks_rekomendasi_lama(): void
    {
        $bromo = $this->journey();

        $this->actingAs(User::factory()->create())->get(route('admin.perjalanan.ubah', $bromo))
            ->assertOk()
            ->assertSee('Mobil Destinasi')
            ->assertSee('Toyota Land Cruiser FJ40 · B 0001 DUM · 6 kursi')
            ->assertSee('Isuzu MU-X 4x4 2020 · B 0004 DUM · 7 kursi')
            ->assertSee('Jeep 4x4 klasik untuk menyeberangi lautan pasir')
            ->assertDontSee('Rekomendasi Kendaraan');
    }

    public function test_admin_mengganti_mobil_destinasi_beserta_alasannya(): void
    {
        $bromo = $this->journey();
        $hilux = Kendaraan::where('nama', 'Toyota Hilux Double Cabin')->firstOrFail();

        $this->simpan($bromo, ['kendaraan_id' => $hilux->id, 'alasan_kendaraan' => 'Lebih lega untuk rombongan kecil.'])->assertSessionHasNoErrors();

        $bromo->refresh();
        $this->assertSame($hilux->id, $bromo->kendaraan_id);
        $this->assertSame('Lebih lega untuk rombongan kecil.', $bromo->alasan_kendaraan);
    }

    public function test_alasan_wajib_diisi_bila_mobil_dipilih(): void
    {
        $this->simpan($this->journey(), ['alasan_kendaraan' => ''])
            ->assertSessionHasErrors(['alasan_kendaraan' => 'Isi alasan memilih mobil ini (mis. kuat di tanjakan), supaya tamu tahu kenapa mobilnya itu.']);
    }

    public function test_mengosongkan_mobil_ikut_mengosongkan_alasan(): void
    {
        $bromo = $this->journey();

        $this->simpan($bromo, ['kendaraan_id' => '', 'alasan_kendaraan' => 'Sisa alasan lama'])->assertSessionHasNoErrors();

        $bromo->refresh();
        $this->assertNull($bromo->kendaraan_id);
        $this->assertNull($bromo->alasan_kendaraan);
    }

    public function test_mobil_harus_muat_kapasitas_maksimal_ditambah_guide(): void
    {
        $bromo = $this->journey();
        $jimny = Kendaraan::where('nama', 'Suzuki Jimny')->firstOrFail();

        $this->simpan($bromo, ['kendaraan_id' => $jimny->id, 'alasan_kendaraan' => 'Lincah di pasir.'])
            ->assertSessionHasErrors(['kendaraan_id' => 'Mobil ini hanya punya 4 kursi, kurang dari 5 kursi (4 tamu + guide yang merangkap driver). Pilih mobil yang lebih besar atau kurangi kapasitas maksimal.']);

        $this->simpan($bromo, ['kendaraan_id' => $jimny->id, 'alasan_kendaraan' => 'Lincah di pasir.', 'maks_orang' => 3])->assertSessionHasNoErrors();
        $this->assertSame($jimny->id, $bromo->fresh()->kendaraan_id);
    }

    public function test_mobil_yang_sudah_dihapus_atau_tidak_ada_ditolak(): void
    {
        $bromo = $this->journey();
        $hapus = Kendaraan::where('nama', 'Toyota Fortuner')->firstOrFail();
        $hapus->delete();

        $this->simpan($bromo, ['kendaraan_id' => $hapus->id])->assertSessionHasErrors('kendaraan_id');
        $this->simpan($bromo, ['kendaraan_id' => 999999])->assertSessionHasErrors('kendaraan_id');
    }

    public function test_journey_kustom_tidak_boleh_diberi_mobil_tetap(): void
    {
        $kustom = $this->journey('CUSTOM HIGHLAND JOURNEY');

        $this->actingAs(User::factory()->create())->get(route('admin.perjalanan.ubah', $kustom))
            ->assertOk()
            ->assertSee('tidak punya mobil tetap');

        $this->simpan($kustom, ['kendaraan_id' => Kendaraan::where('nama', 'Toyota Fortuner')->value('id'), 'alasan_kendaraan' => 'Coba'])
            ->assertSessionHasErrors('kendaraan_id');
    }

    public function test_kendaraan_yang_dipasang_di_destinasi_tidak_bisa_dihapus(): void
    {
        $fj40 = Kendaraan::where('nama', 'Toyota Land Cruiser FJ40')->firstOrFail();

        $this->actingAs(User::factory()->create())->delete(route('admin.kendaraan.hapus', $fj40))
            ->assertSessionHasErrors(['kendaraan' => 'Kendaraan ini masih dipasang sebagai mobil tetap pada 1 destinasi. Ganti mobil destinasi itu dulu di menu Journey atau Paket Wisata, atau ubah statusnya jadi Nonaktif.']);

        $this->assertNotSoftDeleted('kendaraan', ['id' => $fj40->id]);

        Perjalanan::where('kendaraan_id', $fj40->id)->update(['kendaraan_id' => null]);
        $this->actingAs(User::factory()->create())->delete(route('admin.kendaraan.hapus', $fj40))->assertSessionHasNoErrors();
        $this->assertSoftDeleted('kendaraan', ['id' => $fj40->id]);
    }

    public function test_mengubah_status_atau_kapasitas_mobil_destinasi_menampilkan_peringatan(): void
    {
        $fortuner = Kendaraan::where('nama', 'Toyota Fortuner')->firstOrFail();
        $data = ['nama' => $fortuner->nama, 'jenis' => $fortuner->jenis, 'plat_nomor' => $fortuner->plat_nomor, 'kapasitas' => 4, 'status' => 'servis', 'odometer' => $fortuner->odometer];

        $this->actingAs(User::factory()->create())->put(route('admin.kendaraan.perbarui', $fortuner), $data)
            ->assertSessionHas('peringatan', fn (string $pesan): bool => str_contains($pesan, '4 destinasi masih memasang')
                && str_contains($pesan, 'DIENG HIGHLAND ESCAPE (status dalam servis)')
                && str_contains($pesan, 'Bukit Sikunir')
                && str_contains($pesan, 'Hutan Pinus')
                && str_contains($pesan, 'Telaga Sarangan & Cemoro Sewu'));

        $this->actingAs(User::factory()->create())->put(route('admin.kendaraan.perbarui', $fortuner), [...$data, 'kapasitas' => 7, 'status' => 'siap'])
            ->assertSessionMissing('peringatan');
    }

    public function test_detail_kendaraan_menampilkan_daftar_destinasi_yang_memakainya(): void
    {
        $fortuner = Kendaraan::where('nama', 'Toyota Fortuner')->firstOrFail();

        $this->actingAs(User::factory()->create())->get(route('admin.kendaraan.detail', $fortuner))
            ->assertOk()
            ->assertSee('Mobil Tetap untuk Destinasi')
            ->assertSee('DIENG HIGHLAND ESCAPE')
            ->assertSee('Bukit Sikunir')
            ->assertSee('Hutan Pinus');

        $jimny = Kendaraan::where('nama', 'Suzuki Jimny')->firstOrFail();
        $this->actingAs(User::factory()->create())->get(route('admin.kendaraan.detail', $jimny))
            ->assertOk()
            ->assertDontSee('Mobil Tetap untuk Destinasi');
    }
}
