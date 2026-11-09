<?php

namespace Tests\Feature\Publik;

use App\Bantuan\PilihanKendaraan;
use App\Models\Kendaraan;
use App\Models\Pengaturan;
use App\Models\Perjalanan;
use App\Models\PermintaanPesanan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class FormulirPermintaanPerjalananTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Formulir hanya menerima tanggal yang dibuka di Tanggal Tersedia journey.
        $this->bukaTanggal($this->isian()['perjalanan_id'], Carbon::today()->addDays(10));
    }

    private function permintaan(): PermintaanPesanan
    {
        return PermintaanPesanan::query()->create([
            'token' => Str::random(64),
            'whatsapp' => '081234567890',
            'status' => 'menunggu_tamu',
            'token_berlaku_sampai' => PermintaanPesanan::batasBerlakuBaru(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $timpa
     * @return array<string, mixed>
     */
    private function isian(array $timpa = []): array
    {
        return [
            'nama_lengkap' => 'Tamu Form',
            'whatsapp' => '081234567890',
            'perjalanan_id' => Perjalanan::query()->where('template_pesanan_kustom', false)->whereNotNull('harga')->where('tone', '<>', 'custom')->orderBy('urutan')->firstOrFail()->id,
            'tanggal_trip' => Carbon::today()->addDays(10)->toDateString(),
            'jumlah_wisatawan' => 3,
            'lokasi_pickup' => 'Stasiun Malang',
            'permintaan_khusus' => 'Alergi kacang',
            ...$timpa,
        ];
    }

    public function test_formulir_tamu_tidak_menampilkan_daftar_armada_dan_pilihan_admin_tetap_sama_dengan_armada_siap(): void
    {
        Kendaraan::factory()->create(['nama' => 'Toyota Hiace Premio', 'jenis' => 'minibus', 'kapasitas' => 10, 'tahun' => 2022]);
        Kendaraan::factory()->servis()->create(['nama' => 'Isuzu Elf Tua', 'jenis' => 'minibus', 'kapasitas' => 14]);

        $this->get(route('form-pesanan.tampil', $this->permintaan()->token))
            ->assertOk()
            ->assertDontSee('Toyota Hiace Premio')
            ->assertDontSee('Isuzu Elf Tua')
            ->assertDontSee('Daihatsu Taft')
            ->assertDontSee('<optgroup', false);

        $this->assertContains('Minibus — Toyota Hiace Premio 2022', PilihanKendaraan::nilaiSah());
        $this->assertSame(9, PilihanKendaraan::maksTamu('Minibus — Toyota Hiace Premio 2022'));
        $this->assertNotContains('Minibus — Isuzu Elf Tua', PilihanKendaraan::nilaiSah());
    }

    public function test_tautan_whatsapp_setelah_kirim_menuju_admin_dan_memuat_nomor_yang_diisi_tamu(): void
    {
        $permintaan = $this->permintaan();

        $this->post(route('form-pesanan.simpan', $permintaan->token), $this->isian(['whatsapp' => '089912345678']))->assertSessionHasNoErrors();

        $tautan = $this->get(route('form-pesanan.tampil', $permintaan->token))->assertOk()->viewData('tautanKonfirmasiWa');

        $this->assertStringStartsWith('https://wa.me/'.Pengaturan::current()->nomor_whatsapp_admin.'?text=', $tautan);
        $this->assertStringContainsString('Nomor WhatsApp saya: 089912345678', rawurldecode($tautan));
    }

    public function test_mobil_destinasi_yang_dihapus_tidak_menghalangi_pengiriman_dan_tidak_tersimpan(): void
    {
        $journey = Perjalanan::query()->findOrFail($this->isian()['perjalanan_id']);

        $journey->kendaraan->delete();

        $permintaan = $this->permintaan();
        $this->post(route('form-pesanan.simpan', $permintaan->token), $this->isian())->assertSessionHasNoErrors();

        $this->assertNull($permintaan->fresh()->kendaraan_pilihan);
        $this->get(route('form-pesanan.tampil', $this->permintaan()->token))->assertOk();
    }

    public function test_formulir_memberi_contoh_permintaan_khusus_tanpa_info_mobil(): void
    {
        $this->get(route('form-pesanan.tampil', $this->permintaan()->token))
            ->assertOk()
            ->assertSee('Pantangan makanan atau alergi')
            ->assertSee('Butuh car seat anak')
            ->assertDontSee('Mobil untuk perjalanan ini')
            ->assertDontSee('Kendaraan pilihan')
            ->assertDontSee('name="kendaraan_pilihan"', false)
            ->assertSee('formPermintaan', false);
    }

    public function test_peserta_melebihi_kapasitas_journey_ditolak_dan_isian_lain_tetap_terisi(): void
    {
        $this->batasiKapasitasJourney(2);
        $permintaan = $this->permintaan();
        $halaman = route('form-pesanan.tampil', $permintaan->token);

        $this->from($halaman)
            ->post(route('form-pesanan.simpan', $permintaan->token), $this->isian(['jumlah_wisatawan' => 5]))
            ->assertRedirect($halaman)
            ->assertSessionHasErrors('jumlah_wisatawan')
            ->assertSessionHasInput('nama_lengkap', 'Tamu Form')
            ->assertSessionHasInput('jumlah_wisatawan', 5)
            ->assertSessionHasInput('lokasi_pickup', 'Stasiun Malang')
            ->assertSessionHasInput('permintaan_khusus', 'Alergi kacang');

        $this->assertNull($permintaan->fresh()->kendaraan_pilihan);
    }

    public function test_pesan_galat_menyebut_kapasitas_journey(): void
    {
        $this->batasiKapasitasJourney(2);
        $permintaan = $this->permintaan();

        $this->post(route('form-pesanan.simpan', $permintaan->token), $this->isian(['jumlah_wisatawan' => 3]))
            ->assertSessionHasErrors(['jumlah_wisatawan' => 'Jumlah peserta melebihi kapasitas maksimal journey ini (2 tamu). Admin akan memeriksa ketersediaan sebelum mengonfirmasi.']);
    }

    public function test_jumlah_peserta_harus_angka_bulat_dalam_batas(): void
    {
        $permintaan = $this->permintaan();

        foreach (['0', '51', '2.5', 'dua'] as $salah) {
            $this->post(route('form-pesanan.simpan', $permintaan->token), $this->isian(['jumlah_wisatawan' => $salah]))
                ->assertSessionHasErrors('jumlah_wisatawan');
        }

        $this->post(route('form-pesanan.simpan', $permintaan->token), $this->isian(['jumlah_wisatawan' => 4]))
            ->assertSessionHasNoErrors();
        $this->assertSame(4, $permintaan->fresh()->jumlah_wisatawan);
    }

    public function test_kiriman_kendaraan_dari_tamu_diabaikan(): void
    {
        $permintaan = $this->permintaan();

        $this->post(route('form-pesanan.simpan', $permintaan->token), $this->isian(['kendaraan_pilihan' => 'Bus Pariwisata']))
            ->assertSessionHasNoErrors();

        $this->assertNull($permintaan->fresh()->kendaraan_pilihan);
    }

    /** Batasi journey bawaan formulir ke sekian tamu. */
    private function batasiKapasitasJourney(int $maksOrang): void
    {
        Perjalanan::query()->findOrFail($this->isian()['perjalanan_id'])->update(['maks_orang' => $maksOrang]);
    }
}
