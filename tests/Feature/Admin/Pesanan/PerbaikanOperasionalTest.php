<?php

namespace Tests\Feature\Admin\Pesanan;

use App\Aksi\Pesanan\BuatPesananManual;
use App\Layanan\EmailPenugasanPersonelLayanan;
use App\Layanan\KetersediaanKendaraanLayanan;
use App\Layanan\PesanWhatsAppLayanan;
use App\Mail\PenugasanPersonel;
use App\Models\Kendaraan;
use App\Models\Pengaturan;
use App\Models\Perjalanan;
use App\Models\PermintaanPesanan;
use App\Models\PersonelOperasional;
use App\Models\Pesanan;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\Support\DataGuide;
use Tests\TestCase;

class PerbaikanOperasionalTest extends TestCase
{
    private function permintaan(array $timpa = []): PermintaanPesanan
    {
        $journey = Perjalanan::factory()->create(['harga' => 4_000_000, 'durasi_hari' => 2]);
        $this->bukaTanggal($journey, now()->addDays(20));

        return PermintaanPesanan::query()->create([
            'token' => Str::random(64),
            'whatsapp' => '081234567890',
            'status' => 'menunggu_tinjauan',
            'perjalanan_id' => $journey->id,
            'nama_lengkap' => 'Sari Wulandari',
            'tanggal_trip' => now()->addDays(20)->toDateString(),
            'jumlah_wisatawan' => 3,
            'status_penginapan' => 'sudah',
            'nama_hotel' => 'Hotel Tugu',
            'alamat_hotel' => 'Jl. Tugu No. 3, Malang',
            'kendaraan_pilihan' => 'Jeep klasik (petualangan dataran tinggi) — Daihatsu Taft',
            'lokasi_pickup' => 'Lobi Hotel Tugu',
            'token_berlaku_sampai' => PermintaanPesanan::batasBerlakuBaru(),
            ...$timpa,
        ]);
    }

    public function test_admin_mengirim_ulang_formulir_dengan_tautan_baru_yang_berlaku_satu_hari(): void
    {
        $this->freezeTime();
        $permintaan = $this->permintaan(['token_berlaku_sampai' => now()->subHour()]);
        $tokenLama = $permintaan->token;

        $respons = $this->actingAs(User::factory()->create())
            ->post(route('admin.permintaan-pesanan.kirim-ulang-form', $permintaan));

        $permintaan->refresh();
        $this->assertNotSame($tokenLama, $permintaan->token);
        $this->assertSame(now()->addDay()->toDateTimeString(), $permintaan->token_berlaku_sampai->toDateTimeString());
        $this->assertStringStartsWith('https://wa.me/6281234567890?', (string) $respons->headers->get('Location'));
        $this->assertStringContainsString($permintaan->token, urldecode((string) $respons->headers->get('Location')));

        $this->get(route('form-pesanan.tampil', $tokenLama))->assertNotFound();
        $this->get(route('form-pesanan.tampil', $permintaan->token))->assertOk()->assertSee('Tautan formulir ini berlaku sampai');
    }

    public function test_permintaan_yang_sudah_ditutup_tidak_bisa_dikirimi_tautan_baru(): void
    {
        $permintaan = $this->permintaan(['status' => 'tidak_jadi']);

        $this->actingAs(User::factory()->create())
            ->post(route('admin.permintaan-pesanan.kirim-ulang-form', $permintaan))
            ->assertSessionHasErrors('status');
    }

    public function test_pesanan_dari_permintaan_membawa_alamat_hotel_kendaraan_pilihan_dan_durasi_journey(): void
    {
        $permintaan = $this->permintaan();

        $this->actingAs(User::factory()->create())->post(route('admin.permintaan-pesanan.buat-pesanan', $permintaan))
            ->assertSessionHasNoErrors();

        $pesanan = Pesanan::query()->findOrFail($permintaan->fresh()->pesanan_id);
        $this->assertSame('Jl. Tugu No. 3, Malang', $pesanan->alamat_hotel);
        $this->assertSame('Jeep klasik (petualangan dataran tinggi) — Daihatsu Taft', $pesanan->kendaraan_pilihan);
        $this->assertSame(2, $pesanan->durasi_hari);
        $this->assertSame($pesanan->tanggal_trip->copy()->addDay()->toDateString(), $pesanan->tanggal_selesai->toDateString());
        $this->assertSame('2 hari 1 malam', $pesanan->labelDurasi());

        $this->actingAs(User::factory()->create())->get(route('admin.pesanan.detail', $pesanan))
            ->assertOk()
            ->assertSee('Jl. Tugu No. 3, Malang')
            ->assertSee('Mobil Destinasi')
            ->assertSee('2 hari 1 malam');
    }

    public function test_durasi_pesanan_mengikuti_journey_tiga_hari(): void
    {
        $journey = Perjalanan::factory()->create(['harga' => 4_250_000, 'durasi_hari' => 3]);
        $tanggal = now()->addDays(15)->toDateString();
        $this->bukaTanggal($journey, $tanggal);

        $pesanan = app(BuatPesananManual::class)->handle($journey, [
            'tanggal_trip' => $tanggal,
            'nama_lengkap' => 'Tamu Tiga Hari',
            'whatsapp' => '6281234567890',
            'jumlah_wisatawan' => 2,
            'status_penginapan' => 'belum',
            'lokasi_pickup' => 'Stasiun',
        ]);

        $this->assertSame(3, $pesanan->durasi_hari);
        $this->assertSame(now()->addDays(17)->toDateString(), $pesanan->tanggal_selesai->toDateString());
        $this->assertSame('3 hari 2 malam', $pesanan->labelDurasi());
    }

    public function test_pesan_konfirmasi_permintaan_memuat_alamat_masa_berlaku_dan_tautan_konfirmasi_wa_tanpa_kendaraan(): void
    {
        $permintaan = $this->permintaan();
        $teks = app(PesanWhatsAppLayanan::class)->konfirmasiPermintaan($permintaan);

        $this->assertStringContainsString('Alamat Hotel: Jl. Tugu No. 3, Malang', $teks);
        $this->assertStringNotContainsString('Kendaraan:', $teks);
        $this->assertStringContainsString('berlaku sampai', $teks);
        $this->assertStringContainsString('https://wa.me/'.preg_replace('/\D/', '', (string) Pengaturan::current()->nomor_whatsapp_admin).'?text=', $teks);
        $this->assertStringContainsString(route('form-pesanan.tampil', $permintaan->token), $teks);
    }

    public function test_rekomendasi_penginapan_menyertakan_tautan_konfirmasi_hotel_sendiri_dan_formulir(): void
    {
        $permintaan = $this->permintaan(['status_penginapan' => 'belum', 'nama_hotel' => null, 'alamat_hotel' => null]);
        $teks = app(PesanWhatsAppLayanan::class)->rekomendasiPenginapan($permintaan);

        $this->assertStringContainsString('memesan kamar atas nama sendiri', $teks);
        $this->assertStringContainsString('wa.me/', $teks);
        $this->assertStringContainsString(rawurlencode('Nama hotel: '), $teks);
        $this->assertStringContainsString(route('form-pesanan.tampil', $permintaan->token), $teks);

        $pesanan = Pesanan::factory()->create(['status_penginapan' => 'belum', 'nama_hotel' => null]);
        $teksPesanan = app(PesanWhatsAppLayanan::class)->rekomendasiPenginapan($pesanan);
        $this->assertStringContainsString($pesanan->kode, urldecode($teksPesanan));
        $this->assertStringNotContainsString('form-pesanan', $teksPesanan);
    }

    public function test_invoice_pembayaran_memuat_pilihan_lunas_atau_dp_dan_rincian_trip(): void
    {
        $journey = Perjalanan::factory()->create(['harga' => 4_000_000, 'durasi_hari' => 2]);
        $pesanan = Pesanan::factory()->payment()->create([
            'perjalanan_id' => $journey->id,
            'harga' => 4_000_000,
            'nominal_dibayar' => null,
            'status_penginapan' => 'sudah',
            'nama_hotel' => 'Hotel Tugu',
            'alamat_hotel' => 'Jl. Tugu No. 3, Malang',
            'kendaraan_pilihan' => 'Jeep klasik (petualangan dataran tinggi) — Toyota Land Cruiser FJ40',
        ]);

        $teks = app(PesanWhatsAppLayanan::class)->konfirmasiBayar($pesanan->fresh());

        $this->assertStringContainsString('Lunas: bayar Rp 4.000.000', $teks);
        $this->assertStringContainsString('DP 30%: bayar Rp 1.200.000', $teks);
        $this->assertStringContainsString('paling lambat H-3', $teks);
        $this->assertStringContainsString('Durasi: 2 hari 1 malam', $teks);
        $this->assertStringContainsString('Alamat Hotel: Jl. Tugu No. 3, Malang', $teks);
        $this->assertStringNotContainsString('Kendaraan:', $teks);
    }

    public function test_kendaraan_harus_memuat_tamu_ditambah_guide_yang_merangkap_driver(): void
    {
        $pesanan = Pesanan::factory()->create(['jumlah_wisatawan' => 4]);
        $enam = Kendaraan::factory()->create(['kapasitas' => 5, 'nama' => 'Lima Kursi']);
        $lima = Kendaraan::factory()->create(['kapasitas' => 4, 'nama' => 'Empat Kursi']);
        $layanan = app(KetersediaanKendaraanLayanan::class);

        $this->assertSame(5, $layanan->kursiDibutuhkan($pesanan));
        $this->assertNull($layanan->alasanTidakLayak($enam, $pesanan));
        $this->assertStringContainsString('merangkap driver', (string) $layanan->alasanTidakLayak($lima, $pesanan));
        $this->assertTrue($layanan->daftarTersedia($pesanan)->has($enam->id));
        $this->assertFalse($layanan->daftarTersedia($pesanan)->has($lima->id));
    }

    public function test_email_personel_memuat_kendaraan_penginapan_itinerary_dan_catatan_dengan_format_rapi(): void
    {
        $journey = Perjalanan::factory()->create(['itinerary' => [['t' => 'Hari 1 · 07.00', 'x' => 'Jemput di hotel']]]);
        $kendaraan = Kendaraan::factory()->create(['nama' => 'Toyota Fortuner', 'plat_nomor' => 'N 1234 AB']);
        $guide = PersonelOperasional::query()->create([...DataGuide::sim(), 'nama' => 'Guide Email', 'peran' => 'guide', 'whatsapp' => '6281200000001', 'status_operasional' => 'siap', 'aktif' => true]);
        $pesanan = Pesanan::factory()->create([
            'perjalanan_id' => $journey->id,
            'waktu_pickup' => '14:00:00',
            'nama_hotel' => 'Hotel Tugu',
            'alamat_hotel' => 'Jl. Tugu No. 3, Malang',
            'kendaraan_id' => $kendaraan->id,
            'guide_personel_id' => $guide->id,
            'status_konfirmasi_guide' => 'menunggu',
            'catatan_admin' => "Bawa papan nama tamu.\nJemput tepat waktu.",
        ]);

        $html = (new PenugasanPersonel($pesanan->fresh(), $guide))->render();

        $this->assertStringContainsString('Penugasan perjalanan baru', $html);
        $this->assertStringContainsString('Catatan admin', $html);
        $this->assertStringContainsString('Bawa papan nama tamu.', $html);
        $this->assertStringContainsString('14:00 WIB', $html);
        $this->assertStringNotContainsString('14:00:00', $html);
        $this->assertStringContainsString('Toyota Fortuner · N 1234 AB', $html);
        $this->assertStringContainsString('Jl. Tugu No. 3, Malang', $html);
        $this->assertStringContainsString('sekaligus Driver', $html);
        $this->assertStringNotContainsString('Driver Email', $html);
        $this->assertStringContainsString('Rencana perjalanan (estimasi)', $html);
        $this->assertStringContainsString('Jemput di hotel', $html);
    }

    public function test_login_personel_memakai_email_asli_dan_tidak_ada_lagi_pintasan_id_demo(): void
    {
        $guideAkun = User::factory()->create(['email' => 'budi.santoso@example.com', 'jabatan' => 'guide', 'password' => 'KataSandiAman123']);
        $guideAkun->personel()->create([...DataGuide::sim(), 'nama' => 'Budi Santoso', 'peran' => 'guide', 'email' => 'budi.santoso@example.com', 'status_operasional' => 'siap', 'aktif' => true]);

        $this->post(route('personel.proses-masuk'), ['email' => 'budi.santoso@example.com', 'password' => 'KataSandiAman123'])
            ->assertRedirect(route('personel.dashboard'));
        $this->assertAuthenticatedAs($guideAkun);
        $this->post(route('personel.keluar'));

        $this->post(route('personel.proses-masuk'), ['email' => 'DRIVER01', 'password' => 'PASSWORDDRIVER01'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_alamat_tujuan_email_mengabaikan_id_login_yang_bukan_alamat_email(): void
    {
        $pengguna = User::factory()->create(['email' => 'driver01', 'jabatan' => 'guide']);
        $personel = $pengguna->personel()->create(['nama' => 'Driver Tanpa Email', 'peran' => 'guide', 'status_operasional' => 'siap', 'aktif' => true]);

        $this->assertNull(app(EmailPenugasanPersonelLayanan::class)->alamatTujuan($personel->fresh()));
    }
}
