<?php

namespace Tests\Feature\Admin;

use App\Bantuan\AturanNama;
use App\Enums\BookingStatus;
use App\Models\Perjalanan;
use App\Models\PersonelOperasional;
use App\Models\Pesanan;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Tests\Support\DataGuide;
use Tests\TestCase;

class ValidasiKetatTest extends TestCase
{
    private function admin(): User
    {
        return User::factory()->create(['jabatan' => 'admin']);
    }

    public function test_aturan_nama_menerima_nama_wajar_dan_menolak_simbol_angka_saja_dan_terlalu_pendek(): void
    {
        $lolos = fn (string $nama): bool => Validator::make(['n' => $nama], ['n' => AturanNama::aturan()])->passes();

        foreach (['Budi Santoso', 'Siti Nur Aini', "D'Angelo", 'Anne-Marie', 'Ir. H. Ahmad, M.T.', 'Müller', '李小龙', 'Testing 2'] as $nama) {
            $this->assertTrue($lolos($nama), $nama);
        }
        foreach (['ab', '12345', 'Budi@#', '<b>Budi</b>', 'Budi 😀', '   ', 'Rp 1.000 $'] as $nama) {
            $this->assertFalse($lolos($nama), $nama);
        }
    }

    public function test_nama_tamu_dirapikan_dari_spasi_ganda(): void
    {
        $journey = Perjalanan::query()->where('template_pesanan_kustom', false)->whereNotNull('harga')->firstOrFail();

        $this->actingAs($this->admin())->post(route('admin.pesanan.simpan'), [
            'perjalanan_id' => $journey->id,
            'tanggal_trip' => now()->addDays(15)->toDateString(),
            'nama_lengkap' => '  Budi    Santoso  ',
            'whatsapp' => '081234567890',
            'status_penginapan' => 'belum',
            'lokasi_pickup' => 'Stasiun Malang',
            'jumlah_wisatawan' => 2,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('pesanan', ['nama_lengkap' => 'Budi Santoso']);
    }

    public function test_nama_pesanan_dengan_simbol_ditolak_di_admin(): void
    {
        $journey = Perjalanan::query()->where('template_pesanan_kustom', false)->whereNotNull('harga')->firstOrFail();

        $this->actingAs($this->admin())->post(route('admin.pesanan.simpan'), [
            'perjalanan_id' => $journey->id,
            'tanggal_trip' => now()->addDays(15)->toDateString(),
            'nama_lengkap' => 'Budi $$$',
            'whatsapp' => '081234567890',
            'status_penginapan' => 'belum',
            'lokasi_pickup' => 'Stasiun Malang',
            'jumlah_wisatawan' => 2,
        ])->assertSessionHasErrors('nama_lengkap');
    }

    /**
     * @param  array<string, mixed>  $timpa
     * @return array<string, mixed>
     */
    private function dataPersonel(array $timpa = []): array
    {
        return [
            'nama' => 'Agus Prasetyo',
            'peran' => 'guide',
            'whatsapp' => '081211112222',
            'email' => 'agus.prasetyo@example.com',
            ...DataGuide::form(),
            'status_operasional' => 'siap',
            'aktif' => '1',
            ...$timpa,
        ];
    }

    public function test_personel_boleh_memakai_email_dan_whatsapp_kontak_yang_sama_selama_masa_uji(): void
    {
        $this->tanpaGuideCadangan();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.guide.simpan'), $this->dataPersonel())->assertSessionHasNoErrors();
        $this->assertDatabaseHas('personel_operasional', ['nama' => 'Agus Prasetyo', 'whatsapp' => '6281211112222']);

        $this->actingAs($admin)->post(route('admin.guide.simpan'), $this->dataPersonel(['nama' => 'Dewi Lestari']))->assertSessionHasNoErrors();
        $this->assertSame(2, PersonelOperasional::query()->count());
        $this->assertSame(2, PersonelOperasional::query()->where('whatsapp', '6281211112222')->count());
    }

    public function test_personel_nama_dan_nomor_sim_divalidasi(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.guide.simpan'), $this->dataPersonel(['nama' => 'A']))->assertSessionHasErrors('nama');
        $this->actingAs($admin)->post(route('admin.guide.simpan'), $this->dataPersonel(['nama' => 'Agus <script>']))->assertSessionHasErrors('nama');
        $this->actingAs($admin)->post(route('admin.guide.simpan'), $this->dataPersonel(['nomor_sim' => '12']))->assertSessionHasErrors('nomor_sim');
        $this->actingAs($admin)->post(route('admin.guide.simpan'), $this->dataPersonel(['email' => 'bukan-email']))->assertSessionHasErrors('email');
        $this->actingAs($admin)->post(route('admin.guide.simpan'), $this->dataPersonel(['whatsapp' => '12345']))->assertSessionHasErrors('whatsapp');
    }

    public function test_guide_wajib_punya_data_mengemudi_lengkap_dan_sim_yang_masih_berlaku(): void
    {
        $this->tanpaGuideCadangan();
        $admin = $this->admin();
        $kirim = fn (array $timpa) => $this->actingAs($admin)->post(route('admin.guide.simpan'), $this->dataPersonel(['email' => 'g'.uniqid().'@example.com', 'whatsapp' => '0812'.random_int(10000000, 99999999)] + $timpa));

        $kirim(['nomor_sim' => ''])->assertSessionHasErrors('nomor_sim');
        $kirim(['jenis_sim' => 'C'])->assertSessionHasErrors('jenis_sim');
        $kirim(['jenis_sim' => ''])->assertSessionHasErrors('jenis_sim');
        $kirim(['masa_berlaku_sim' => now()->subDay()->toDateString()])->assertSessionHasErrors('masa_berlaku_sim');
        $kirim(['masa_berlaku_sim' => ''])->assertSessionHasErrors('masa_berlaku_sim');
        $kirim(['pengalaman_mengemudi_tahun' => 0])->assertSessionHasErrors('pengalaman_mengemudi_tahun');
        $kirim(['pengalaman_mengemudi_tahun' => ''])->assertSessionHasErrors('pengalaman_mengemudi_tahun');
        $kirim(['kendaraan_dikuasai' => []])->assertSessionHasErrors('kendaraan_dikuasai');
        $kirim(['kendaraan_dikuasai' => ['Bus Pariwisata']])->assertSessionHasErrors('kendaraan_dikuasai.0');
        $kirim(['peran' => 'driver'])->assertSessionHasErrors('peran');
        $this->assertSame(0, PersonelOperasional::query()->count());

        $kirim(['kendaraan_dikuasai' => ['MPV', 'Jeep klasik (petualangan dataran tinggi)']])->assertSessionHasNoErrors();
        $guide = PersonelOperasional::query()->firstOrFail();
        $this->assertSame('guide', $guide->peran);
        $this->assertSame('MPV, Jeep klasik (petualangan dataran tinggi)', $guide->kendaraan_dikuasai);
        $this->assertTrue($guide->dataMengemudiLengkap());
        $this->assertTrue($guide->simBerlakuSampai(now()->addYear()));
        $this->assertFalse($guide->simBerlakuSampai(now()->addYears(3)));
    }

    public function test_pembatalan_menolak_alasan_terlalu_pendek_dan_refund_melebihi_pembayaran(): void
    {
        $pesanan = Pesanan::factory()->create(['status' => BookingStatus::Payment, 'harga' => 4_000_000, 'nominal_dibayar' => 1_200_000, 'dikonfirmasi_at' => now()]);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.pesanan.batalkan', $pesanan), ['pihak_pembatal' => 'tamu', 'alasan_batal' => 'x', 'nominal_refund' => 0])->assertSessionHasErrors('alasan_batal');
        $this->actingAs($admin)->post(route('admin.pesanan.batalkan', $pesanan), ['pihak_pembatal' => 'tamu', 'alasan_batal' => 'Tamu sakit mendadak', 'nominal_refund' => 2_000_000])->assertSessionHasErrors('nominal_refund');
    }
}
