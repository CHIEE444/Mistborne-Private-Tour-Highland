<?php

namespace Tests\Feature\Admin\Hotel;

use App\Layanan\AlurReservasiHotel;
use App\Layanan\PesanWhatsAppLayanan;
use App\Models\Hotel;
use App\Models\Pesanan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AlurReservasiHotelTest extends TestCase
{
    private function pesanan(array $timpa = []): Pesanan
    {
        $trip = Carbon::today()->addDays(20);
        $hotel = Hotel::factory()->create(['nama' => 'Hotel Alur', 'whatsapp' => '081808606086', 'tarif_mulai' => 700_000]);

        return Pesanan::factory()->payment()->create([
            'tanggal_trip' => $trip->toDateString(),
            'tanggal_selesai' => $trip->copy()->addDay()->toDateString(),
            'jumlah_wisatawan' => 4,
            'status_penginapan' => 'diurus',
            'hotel_id' => $hotel->id,
            'nama_hotel' => $hotel->nama,
            'hotel_check_in' => $trip->toDateString(),
            'hotel_check_out' => $trip->copy()->addDay()->toDateString(),
            'jumlah_kamar' => 1,
            'tipe_kamar' => 'Family Room',
            'status_reservasi_hotel' => 'diminta',
            ...$timpa,
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create();
    }

    /** @return array<string, string> kunci langkah => status */
    private function statusLangkah(Pesanan $pesanan): array
    {
        return collect(app(AlurReservasiHotel::class)->langkah($pesanan->fresh()))->pluck('status', 'kunci')->all();
    }

    public function test_tahap_all_inclusive_berurutan_dan_menunjuk_satu_tahap_berikutnya(): void
    {
        $pesanan = $this->pesanan();
        $alur = app(AlurReservasiHotel::class);

        $this->assertSame('pesan', $alur->langkahBerikutnya($pesanan)['kunci']);

        $pesanan->update(['permintaan_hotel_dikirim_at' => now()]);
        $this->assertSame('setuju', $alur->langkahBerikutnya($pesanan->fresh())['kunci']);

        $pesanan->update(['status_reservasi_hotel' => 'terkonfirmasi', 'kode_booking_hotel' => 'ABC']);
        $this->assertSame('bayar', $alur->langkahBerikutnya($pesanan->fresh())['kunci']);
        $this->assertSame('transfer', $this->langkahBayarBerikutnya($pesanan));

        $pesanan->update(['bayar_hotel_nominal' => 700_000, 'bayar_hotel_tanggal' => today()->toDateString()]);
        $this->assertSame('bayar', $alur->langkahBerikutnya($pesanan->fresh())['kunci']);
        $this->assertSame('bukti', $this->langkahBayarBerikutnya($pesanan));

        $pesanan->update(['bukti_bayar_hotel_dikirim_at' => now()]);
        $this->assertSame('konfirmasi_bayar', $this->langkahBayarBerikutnya($pesanan));

        $pesanan->update(['bayar_hotel_dikonfirmasi_at' => now()]);
        $this->assertSame('kabari_tamu', $alur->langkahBerikutnya($pesanan->fresh())['kunci']);

        $pesanan->update(['tamu_dikabari_hotel_at' => now()]);
        $this->assertNull($alur->langkahBerikutnya($pesanan->fresh()));
        $this->assertSame(['selesai' => 4, 'total' => 4], $alur->ringkasan($pesanan->fresh()));
        $this->assertTrue($alur->sudahDiurus($pesanan->fresh()));
    }

    private function langkahBayarBerikutnya(Pesanan $pesanan): ?string
    {
        return collect(app(AlurReservasiHotel::class)->langkahBayar($pesanan->fresh()))->firstWhere('status', 'berikutnya')['kunci'] ?? null;
    }

    public function test_tamu_yang_membayar_di_hotel_tidak_punya_tahap_bayar_hotel(): void
    {
        $pesanan = $this->pesanan(['status_penginapan' => 'dipesankan', 'status_reservasi_hotel' => 'terkonfirmasi', 'permintaan_hotel_dikirim_at' => now()]);

        $this->assertSame(['pesan' => 'selesai', 'setuju' => 'selesai', 'kabari_tamu' => 'berikutnya'], $this->statusLangkah($pesanan));
        $this->assertSame(['selesai' => 2, 'total' => 3], app(AlurReservasiHotel::class)->ringkasan($pesanan));
    }

    public function test_hotel_yang_sudah_setuju_menuntaskan_tahap_pesan_walau_tanda_kirim_kosong(): void
    {
        $pesanan = $this->pesanan(['status_reservasi_hotel' => 'terkonfirmasi', 'kode_booking_hotel' => 'ABC', 'permintaan_hotel_dikirim_at' => null]);

        $this->assertSame('selesai', $this->statusLangkah($pesanan)['pesan']);
        $this->assertSame('bayar', app(AlurReservasiHotel::class)->langkahBerikutnya($pesanan)['kunci']);
    }

    public function test_kartu_hotel_menampilkan_banner_tahap_berikutnya_dan_tombol_kirim_satu_klik(): void
    {
        $pesanan = $this->pesanan();

        $this->actingAs($this->admin())->get(route('admin.pesanan.detail', $pesanan))
            ->assertOk()
            ->assertSee('Tahap berikutnya')
            ->assertSee('1. Pesan kamar ke hotel')
            ->assertSee('0 dari 4 tahap')
            ->assertSee('Kirim permintaan ke hotel')
            ->assertSee(route('admin.pesanan.hotel.langkah.tandai', [$pesanan, 'permintaan']), false);
    }

    public function test_tahap_bayar_hanya_membuka_sub_langkah_yang_sedang_dikerjakan(): void
    {
        $pesanan = $this->pesanan([
            'status_reservasi_hotel' => 'terkonfirmasi', 'kode_booking_hotel' => 'ABC', 'permintaan_hotel_dikirim_at' => now(),
            'bayar_hotel_nominal' => 700_000, 'bayar_hotel_tanggal' => today()->toDateString(),
        ]);

        $this->actingAs($this->admin())->get(route('admin.pesanan.detail', $pesanan))
            ->assertOk()
            ->assertSee('3. Bayar hotel')
            ->assertSee('Kirim bukti ke hotel')
            ->assertSee(route('admin.pesanan.hotel.langkah.tandai', [$pesanan, 'bukti']), false)
            ->assertDontSee(route('admin.pesanan.hotel.langkah.tandai', [$pesanan, 'konfirmasi_bayar']), false);
    }

    public function test_tahap_kabari_tamu_mengirim_info_kamar_sekali_klik_setelah_hotel_dibayar(): void
    {
        $pesanan = $this->pesanan([
            'status_reservasi_hotel' => 'terkonfirmasi', 'kode_booking_hotel' => 'ABC', 'permintaan_hotel_dikirim_at' => now(),
            'bayar_hotel_nominal' => 700_000, 'bayar_hotel_tanggal' => today()->toDateString(),
            'bukti_bayar_hotel_dikirim_at' => now(), 'bayar_hotel_dikonfirmasi_at' => now(),
        ]);

        $this->actingAs($this->admin())->get(route('admin.pesanan.detail', $pesanan))
            ->assertOk()
            ->assertSee('4. Kabari tamu')
            ->assertSee('Kirim info kamar ke tamu')
            ->assertSee(route('admin.pesanan.hotel.langkah.tandai', [$pesanan, 'kabari_tamu']), false);
    }

    public function test_tombol_hijau_mencatat_waktu_langkah_dan_idempoten(): void
    {
        $pesanan = $this->pesanan();
        $admin = $this->admin();

        $this->actingAs($admin)->patch(route('admin.pesanan.hotel.langkah.tandai', [$pesanan, 'permintaan']))->assertSessionHas('sukses');
        $waktu = $pesanan->fresh()->permintaan_hotel_dikirim_at;
        $this->assertNotNull($waktu);

        $this->travel(5)->minutes();
        $this->actingAs($admin)->patch(route('admin.pesanan.hotel.langkah.tandai', [$pesanan, 'permintaan']));
        $this->assertTrue($waktu->equalTo($pesanan->fresh()->permintaan_hotel_dikirim_at));

        $this->actingAs($admin)->delete(route('admin.pesanan.hotel.langkah.batal', [$pesanan, 'permintaan']))->assertSessionHas('sukses');
        $this->assertNull($pesanan->fresh()->permintaan_hotel_dikirim_at);
    }

    public function test_langkah_di_luar_daftar_ditolak_dan_langkah_yang_belum_waktunya_tidak_tercatat(): void
    {
        $pesanan = $this->pesanan();
        $admin = $this->admin();

        $this->actingAs($admin)->patch(route('admin.pesanan.hotel.langkah.tandai', [$pesanan, 'hapus_semua']))->assertNotFound();

        $this->actingAs($admin)->patch(route('admin.pesanan.hotel.langkah.tandai', [$pesanan, 'kabari_tamu']))->assertSessionHas('gagal');
        $this->actingAs($admin)->patch(route('admin.pesanan.hotel.langkah.tandai', [$pesanan, 'bukti']))->assertSessionHas('gagal');
        $this->assertNull($pesanan->fresh()->tamu_dikabari_hotel_at);
        $this->assertNull($pesanan->fresh()->bukti_bayar_hotel_dikirim_at);
    }

    public function test_pengunjung_tanpa_login_tidak_bisa_menandai_langkah(): void
    {
        $pesanan = $this->pesanan();

        $this->patch(route('admin.pesanan.hotel.langkah.tandai', [$pesanan, 'permintaan']))->assertRedirect(route('admin.autentikasi.masuk'));
        $this->assertNull($pesanan->fresh()->permintaan_hotel_dikirim_at);
    }

    public function test_catat_transfer_menyimpan_nominal_tanggal_dan_bukti_lalu_hanya_admin_bisa_melihatnya(): void
    {
        Storage::fake('local');
        $pesanan = $this->pesanan(['status_reservasi_hotel' => 'terkonfirmasi', 'kode_booking_hotel' => 'ABC']);
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.pesanan.hotel.pembayaran.simpan', $pesanan), [
            'bayar_hotel_nominal' => 1_450_000,
            'bayar_hotel_tanggal' => today()->toDateString(),
            'bayar_hotel_bukti' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertSessionHasNoErrors();

        $pesanan->refresh();
        $this->assertSame(1_450_000, $pesanan->bayar_hotel_nominal);
        $this->assertNotNull($pesanan->bayar_hotel_bukti);
        Storage::disk('local')->assertExists($pesanan->bayar_hotel_bukti);

        $this->actingAs($admin)->get(route('admin.pesanan.hotel.bukti.lihat', $pesanan))->assertOk();
        auth()->logout();
        $this->get(route('admin.pesanan.hotel.bukti.lihat', $pesanan))->assertRedirect(route('admin.autentikasi.masuk'));
    }

    public function test_catat_transfer_menolak_nominal_tanggal_dan_berkas_yang_tidak_valid(): void
    {
        Storage::fake('local');
        $pesanan = $this->pesanan(['status_reservasi_hotel' => 'terkonfirmasi', 'kode_booking_hotel' => 'ABC']);

        $this->actingAs($this->admin())->put(route('admin.pesanan.hotel.pembayaran.simpan', $pesanan), [
            'bayar_hotel_nominal' => 100,
            'bayar_hotel_tanggal' => today()->addDay()->toDateString(),
            'bayar_hotel_bukti' => UploadedFile::fake()->create('bukti.exe', 10, 'application/octet-stream'),
        ])->assertSessionHasErrors(['bayar_hotel_nominal', 'bayar_hotel_tanggal', 'bayar_hotel_bukti']);

        $this->assertNull($pesanan->fresh()->bayar_hotel_nominal);
    }

    public function test_catat_transfer_menolak_nominal_berhuruf(): void
    {
        $pesanan = $this->pesanan(['status_reservasi_hotel' => 'terkonfirmasi', 'kode_booking_hotel' => 'ABC']);

        $this->actingAs($this->admin())->put(route('admin.pesanan.hotel.pembayaran.simpan', $pesanan), [
            'bayar_hotel_nominal' => '1500rb',
            'bayar_hotel_tanggal' => today()->toDateString(),
        ])->assertSessionHasErrors(['bayar_hotel_nominal' => 'Nominal transfer harus berupa angka, tanpa huruf.']);

        $this->assertNull($pesanan->fresh()->bayar_hotel_nominal);
    }

    public function test_transfer_hanya_dicatat_untuk_all_inclusive_setelah_hotel_setuju(): void
    {
        $belumSetuju = $this->pesanan();
        $bayarDiHotel = $this->pesanan(['status_penginapan' => 'dipesankan', 'status_reservasi_hotel' => 'terkonfirmasi', 'kode_booking_hotel' => 'X']);
        $isian = ['bayar_hotel_nominal' => 700_000, 'bayar_hotel_tanggal' => today()->toDateString()];
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.pesanan.hotel.pembayaran.simpan', $belumSetuju), $isian)->assertSessionHas('gagal');
        $this->actingAs($admin)->put(route('admin.pesanan.hotel.pembayaran.simpan', $bayarDiHotel), $isian)->assertSessionHas('gagal');

        $this->assertNull($belumSetuju->fresh()->bayar_hotel_nominal);
        $this->assertNull($bayarDiHotel->fresh()->bayar_hotel_nominal);
    }

    public function test_melepas_reservasi_mengosongkan_alur_dan_menghapus_berkas_bukti(): void
    {
        Storage::fake('local');
        $pesanan = $this->pesanan(['permintaan_hotel_dikirim_at' => now(), 'bayar_hotel_nominal' => 700_000, 'bayar_hotel_tanggal' => today()->toDateString()]);
        Storage::disk('local')->put('bukti-bayar-hotel/lama.jpg', 'x');
        $pesanan->update(['bayar_hotel_bukti' => 'bukti-bayar-hotel/lama.jpg']);

        $this->actingAs($this->admin())->delete(route('admin.pesanan.hotel.lepas', $pesanan))->assertSessionHas('sukses');

        $pesanan->refresh();
        $this->assertNull($pesanan->hotel_id);
        $this->assertNull($pesanan->permintaan_hotel_dikirim_at);
        $this->assertNull($pesanan->bayar_hotel_nominal);
        $this->assertNull($pesanan->bayar_hotel_bukti);
        Storage::disk('local')->assertMissing('bukti-bayar-hotel/lama.jpg');
    }

    public function test_modal_reservasi_tersimpan_hanya_berisi_draft_hotel_yang_terkirim_dan_tertanda_sekali_klik(): void
    {
        $pesanan = $this->pesanan();
        $admin = $this->admin();

        $this->actingAs($admin)->withSession(['modal_reservasi_hotel' => 'diminta'])
            ->get(route('admin.pesanan.detail', $pesanan))
            ->assertOk()
            ->assertSee('Reservasi tersimpan. Kirim permintaan ke hotel sekarang')
            ->assertSee('Kirim ke Hotel via WhatsApp')
            ->assertDontSee('Kabari tamu: Info Penginapan All-inclusive')
            ->assertSee(route('admin.pesanan.hotel.langkah.tandai', [$pesanan, 'permintaan']), false);

        $this->actingAs($admin)->patch(route('admin.pesanan.hotel.langkah.tandai', [$pesanan, 'permintaan']));
        $this->assertNotNull($pesanan->fresh()->permintaan_hotel_dikirim_at);
    }

    public function test_draft_bukti_transfer_dan_konfirmasi_ulang_memuat_nominal_tanggal_dan_kode_booking(): void
    {
        $pesanan = $this->pesanan(['status_reservasi_hotel' => 'terkonfirmasi', 'kode_booking_hotel' => 'KODE-77', 'bayar_hotel_nominal' => 1_450_000, 'bayar_hotel_tanggal' => '2026-10-20']);
        $layanan = app(PesanWhatsAppLayanan::class);

        $bukti = $layanan->buktiTransferKeHotel($pesanan->fresh('hotel'));
        $this->assertStringContainsString('Rp 1.450.000', $bukti);
        $this->assertStringContainsString('20 Oktober 2026', $bukti);
        $this->assertStringContainsString('KODE-77', $bukti);
        $this->assertStringContainsString('Bukti transfer kami lampirkan', $bukti);

        $ulang = $layanan->konfirmasiUlangKeHotel($pesanan->fresh('hotel'));
        $this->assertStringContainsString('KODE-77', $ulang);
        $this->assertStringContainsString('Pembayaran sudah kami transfer pada 20 Oktober 2026', $ulang);
    }

    public function test_reservasi_dibatalkan_menampilkan_pemberitahuan_bukan_tahap(): void
    {
        $pesanan = $this->pesanan(['status_reservasi_hotel' => 'dibatalkan']);

        $this->actingAs($this->admin())->get(route('admin.pesanan.detail', $pesanan))
            ->assertOk()
            ->assertSee('Reservasi hotel dibatalkan, jadi tahap berikutnya tidak berlaku')
            ->assertDontSee('Tahap berikutnya');
    }

    public function test_reservasi_terkonfirmasi_bisa_dibatalkan_lalu_draft_pembatalan_ke_hotel_terbuka(): void
    {
        $pesanan = $this->pesanan(['status_reservasi_hotel' => 'terkonfirmasi', 'kode_booking_hotel' => 'ABC']);
        $admin = $this->admin();

        $this->actingAs($admin)->patch(route('admin.pesanan.hotel.batal', $pesanan))
            ->assertSessionHas('modal_reservasi_hotel', 'dibatalkan');

        $pesanan->refresh();
        $this->assertSame('dibatalkan', $pesanan->status_reservasi_hotel->value);
        $this->assertNull($pesanan->nama_hotel);
        $this->actingAs($admin)->patch(route('admin.pesanan.hotel.batal', $pesanan))->assertSessionHas('gagal');
    }

    public function test_mengganti_hotel_memulai_reservasi_baru_tanpa_tanda_dan_kode_booking_hotel_lama(): void
    {
        $pesanan = $this->pesanan(['permintaan_hotel_dikirim_at' => now()]);
        $hotelBaru = Hotel::factory()->create(['nama' => 'Hotel Baru', 'tipe_kamar' => [['nama' => 'Family Room', 'kapasitas' => 4]]]);

        $this->actingAs($this->admin())->put(route('admin.pesanan.hotel.simpan', $pesanan), [
            'hotel_id' => $hotelBaru->id,
            'hotel_check_in' => $pesanan->hotel_check_in->toDateString(),
            'hotel_check_out' => $pesanan->hotel_check_out->toDateString(),
            'jumlah_kamar' => 1,
            'tipe_kamar' => 'Family Room',
        ])->assertSessionHasNoErrors()->assertSessionHas('modal_reservasi_hotel', 'diminta');

        $pesanan->refresh();
        $this->assertSame($hotelBaru->id, $pesanan->hotel_id);
        $this->assertSame('diminta', $pesanan->status_reservasi_hotel->value);
        $this->assertNull($pesanan->permintaan_hotel_dikirim_at);
    }

    public function test_mengubah_kamar_pada_hotel_yang_sudah_setuju_mempertahankan_status_dan_kode_booking(): void
    {
        $pesanan = $this->pesanan(['status_reservasi_hotel' => 'terkonfirmasi', 'kode_booking_hotel' => 'ABC', 'permintaan_hotel_dikirim_at' => now()]);

        $this->actingAs($this->admin())->put(route('admin.pesanan.hotel.simpan', $pesanan), [
            'hotel_id' => $pesanan->hotel_id,
            'hotel_check_in' => $pesanan->hotel_check_in->toDateString(),
            'hotel_check_out' => $pesanan->hotel_check_out->toDateString(),
            'jumlah_kamar' => 2,
        ])->assertSessionHasNoErrors()->assertSessionMissing('modal_reservasi_hotel');

        $pesanan->refresh();
        $this->assertSame(2, $pesanan->jumlah_kamar);
        $this->assertSame('terkonfirmasi', $pesanan->status_reservasi_hotel->value);
        $this->assertSame('ABC', $pesanan->kode_booking_hotel);
    }
}
