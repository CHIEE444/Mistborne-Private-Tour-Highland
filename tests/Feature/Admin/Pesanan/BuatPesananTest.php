<?php

namespace Tests\Feature\Admin\Pesanan;

use App\Enums\BookingStatus;
use App\Enums\SumberPesanan;
use App\Models\JadwalKeberangkatan;
use App\Models\Perjalanan;
use App\Models\Pesanan;
use App\Models\User;
use Tests\TestCase;

class BuatPesananTest extends TestCase
{
    public function test_admin_bisa_membuka_form_pesanan_manual(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.pesanan.buat'))->assertOk();
    }

    public function test_admin_bisa_membuat_pesanan_manual(): void
    {
        $user = User::factory()->create();
        $journey = Perjalanan::factory()->create(['harga' => 2_500_000]);
        $jadwal = JadwalKeberangkatan::factory()->create(['perjalanan_id' => $journey->id]);

        $response = $this->actingAs($user)->post(route('admin.pesanan.simpan'), [
            'perjalanan_id' => $journey->id,
            'tanggal_trip' => $jadwal->tanggal->toDateString(),
            'nama_lengkap' => 'Budi Santoso',
            'whatsapp' => '081234567890',
            'status_penginapan' => 'sudah',
            'nama_hotel' => 'Hotel XYZ',
            'jumlah_wisatawan' => 4,
            'lokasi_pickup' => 'Hotel XYZ',
        ]);

        $pesanan = Pesanan::where('nama_lengkap', 'Budi Santoso')->first();
        $response->assertRedirect(route('admin.pesanan.detail', $pesanan));

        $this->assertNotNull($pesanan);
        $this->assertStringStartsWith('MST-', $pesanan->kode);
        $this->assertSame(40, strlen($pesanan->token_rating));
        $this->assertSame($journey->nama, $pesanan->nama_perjalanan);
        $this->assertSame(2_500_000, $pesanan->harga);
        $this->assertSame(SumberPesanan::Manual, $pesanan->sumber);
        $this->assertSame(BookingStatus::Dihubungi, $pesanan->status);
    }

    public function test_harga_bisa_ditimpa_manual(): void
    {
        $user = User::factory()->create();
        $journey = Perjalanan::factory()->create(['harga' => 2_500_000]);
        $jadwal = JadwalKeberangkatan::factory()->create(['perjalanan_id' => $journey->id]);

        $this->actingAs($user)->post(route('admin.pesanan.simpan'), [
            'perjalanan_id' => $journey->id,
            'tanggal_trip' => $jadwal->tanggal->toDateString(),
            'nama_lengkap' => 'Siti',
            'whatsapp' => '081234567890',
            'status_penginapan' => 'sudah',
            'nama_hotel' => 'Hotel ABC',
            'jumlah_wisatawan' => 2,
            'lokasi_pickup' => 'Hotel ABC',
            'harga' => 1_800_000,
        ]);

        $this->assertSame(1_800_000, Pesanan::where('nama_lengkap', 'Siti')->first()->harga);
    }

    public function test_tamu_tidak_bisa_membuat_pesanan(): void
    {
        $journey = Perjalanan::factory()->create();

        $this->post(route('admin.pesanan.simpan'), ['perjalanan_id' => $journey->id])
            ->assertRedirect(route('admin.autentikasi.masuk'));
    }

    public function test_journey_internal_trip_kustom_tidak_bisa_dipilih_untuk_pesanan_manual(): void
    {
        $template = Perjalanan::factory()->create(['template_pesanan_kustom' => true, 'harga' => 1_000_000]);
        $jadwal = JadwalKeberangkatan::factory()->create(['perjalanan_id' => $template->id]);

        $this->actingAs(User::factory()->create())->post(route('admin.pesanan.simpan'), [
            'perjalanan_id' => $template->id,
            'tanggal_trip' => $jadwal->tanggal->toDateString(),
            'nama_lengkap' => 'Tamu Internal',
            'whatsapp' => '081234567890',
            'status_penginapan' => 'sudah',
            'nama_hotel' => 'Hotel A',
            'jumlah_wisatawan' => 2,
            'lokasi_pickup' => 'Hotel A',
        ])->assertSessionHasErrors('perjalanan_id');

        $this->assertDatabaseMissing('pesanan', ['nama_lengkap' => 'Tamu Internal']);
    }

    /** @return array<string, mixed> */
    private function dataPesananManual(Perjalanan $journey, JadwalKeberangkatan $jadwal, array $ubah = []): array
    {
        return [
            'perjalanan_id' => $journey->id,
            'tanggal_trip' => $jadwal->tanggal->toDateString(),
            'nama_lengkap' => 'Tamu Validasi',
            'whatsapp' => '081234567890',
            'status_penginapan' => 'sudah',
            'nama_hotel' => 'Hotel XYZ',
            'jumlah_wisatawan' => 2,
            'lokasi_pickup' => 'Hotel XYZ',
            ...$ubah,
        ];
    }

    public function test_nomor_whatsapp_bercampur_huruf_ditolak_bukan_diubah_diam_diam(): void
    {
        $journey = Perjalanan::factory()->create(['harga' => 1_000_000]);
        $jadwal = JadwalKeberangkatan::factory()->create(['perjalanan_id' => $journey->id]);

        $this->actingAs(User::factory()->create())->post(route('admin.pesanan.simpan'), $this->dataPesananManual($journey, $jadwal, ['whatsapp' => '08123abc4567']))
            ->assertSessionHasErrors('whatsapp');
        $this->assertDatabaseMissing('pesanan', ['nama_lengkap' => 'Tamu Validasi']);
    }

    public function test_jumlah_wisatawan_tidak_boleh_melebihi_kapasitas_journey(): void
    {
        $journey = Perjalanan::factory()->create(['harga' => 1_000_000, 'maks_orang' => 4]);
        $jadwal = JadwalKeberangkatan::factory()->create(['perjalanan_id' => $journey->id]);
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.pesanan.simpan'), $this->dataPesananManual($journey, $jadwal, ['jumlah_wisatawan' => 5]))
            ->assertSessionHasErrors(['jumlah_wisatawan' => 'Jumlah wisatawan melebihi kapasitas journey ini (maksimal 4 orang).']);
        $this->assertDatabaseMissing('pesanan', ['nama_lengkap' => 'Tamu Validasi']);

        $this->actingAs($admin)->post(route('admin.pesanan.simpan'), $this->dataPesananManual($journey, $jadwal, ['jumlah_wisatawan' => 4]))
            ->assertSessionHasNoErrors();
        $pesanan = Pesanan::query()->where('nama_lengkap', 'Tamu Validasi')->firstOrFail();

        $this->actingAs($admin)->put(route('admin.pesanan.perbarui', $pesanan), [
            'nama_nama' => 'x',
            'nama_lengkap' => 'Tamu Validasi',
            'whatsapp' => '081234567890',
            'status_penginapan' => 'sudah',
            'nama_hotel' => 'Hotel XYZ',
            'lokasi_pickup' => 'Hotel XYZ',
            'jumlah_wisatawan' => 9,
            'harga' => 1_000_000,
        ])->assertSessionHasErrors('jumlah_wisatawan');
        $this->assertSame(4, $pesanan->fresh()->jumlah_wisatawan);
    }

    public function test_pesan_galat_pembatalan_berbahasa_jelas(): void
    {
        $pesanan = Pesanan::factory()->create();

        $this->actingAs(User::factory()->create())->post(route('admin.pesanan.batalkan', $pesanan), [])
            ->assertSessionHasErrors([
                'pihak_pembatal' => 'Pilih pihak yang membatalkan.',
                'alasan_batal' => 'Alasan pembatalan wajib diisi.',
                'nominal_refund' => 'Isi nominal refund, atau 0 bila tidak ada pengembalian dana.',
            ]);
    }

    public function test_harga_nol_ditolak_dan_kolom_kosong_memakai_harga_journey(): void
    {
        $journey = Perjalanan::factory()->create(['harga' => 2_500_000]);
        $jadwal = JadwalKeberangkatan::factory()->create(['perjalanan_id' => $journey->id]);
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.pesanan.simpan'), $this->dataPesananManual($journey, $jadwal, ['harga' => 0]))
            ->assertSessionHasErrors(['harga' => 'Harga minimal Rp 1. Kosongkan kolom untuk memakai harga journey.']);
        $this->assertDatabaseMissing('pesanan', ['nama_lengkap' => 'Tamu Validasi']);

        $this->actingAs($admin)->post(route('admin.pesanan.simpan'), $this->dataPesananManual($journey, $jadwal, ['harga' => '']))
            ->assertSessionHasNoErrors();
        $this->assertSame(2_500_000, Pesanan::query()->where('nama_lengkap', 'Tamu Validasi')->firstOrFail()->harga);
    }

    public function test_karakter_tag_html_ditolak_pada_nama_hotel_dan_lokasi_jemput(): void
    {
        $journey = Perjalanan::factory()->create(['harga' => 1_000_000]);
        $jadwal = JadwalKeberangkatan::factory()->create(['perjalanan_id' => $journey->id]);

        $this->actingAs(User::factory()->create())->post(route('admin.pesanan.simpan'), $this->dataPesananManual($journey, $jadwal, [
            'nama_lengkap' => '<script>alert(1)</script>',
            'status_penginapan' => 'sudah',
            'nama_hotel' => 'Hotel <b>Bold</b>',
            'lokasi_pickup' => 'Jl. A > Jl. B',
        ]))->assertSessionHasErrors([
            'nama_lengkap' => 'Nama lengkap tidak boleh memuat karakter < atau >.',
            'nama_hotel' => 'Nama hotel tidak boleh memuat karakter < atau >.',
            'lokasi_pickup' => 'Lokasi jemput tidak boleh memuat karakter < atau >.',
        ]);
    }

    public function test_nama_dengan_angka_tanda_baca_dan_apostrof_tetap_diterima(): void
    {
        $journey = Perjalanan::factory()->create(['harga' => 1_000_000]);
        $jadwal = JadwalKeberangkatan::factory()->create(['perjalanan_id' => $journey->id]);

        $this->actingAs(User::factory()->create())->post(route('admin.pesanan.simpan'), $this->dataPesananManual($journey, $jadwal, [
            'nama_lengkap' => "Dewi O'Brien-Santoso, S.Kom 2",
        ]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('pesanan', ['nama_lengkap' => "Dewi O'Brien-Santoso, S.Kom 2"]);
    }
}
