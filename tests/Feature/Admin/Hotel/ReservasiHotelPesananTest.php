<?php

namespace Tests\Feature\Admin\Hotel;

use App\Enums\StatusPenginapan;
use App\Enums\StatusReservasiHotel;
use App\Layanan\PemeriksaReservasiHotel;
use App\Layanan\PesanWhatsAppLayanan;
use App\Models\Hotel;
use App\Models\Pengaturan;
use App\Models\Perjalanan;
use App\Models\Pesanan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReservasiHotelPesananTest extends TestCase
{
    private function pesanan(array $timpa = []): Pesanan
    {
        $trip = Carbon::today()->addDays(20);

        return Pesanan::factory()->create([
            'tanggal_trip' => $trip->toDateString(),
            'tanggal_selesai' => $trip->copy()->addDay()->toDateString(),
            'jumlah_wisatawan' => 4,
            'status_penginapan' => 'belum',
            'nama_hotel' => null,
            ...$timpa,
        ]);
    }

    /**
     * @param  array<string, mixed>  $timpa
     * @return array<string, mixed>
     */
    private function data(Pesanan $pesanan, Hotel $hotel, array $timpa = []): array
    {
        return [
            'hotel_id' => $hotel->id,
            'hotel_check_in' => $pesanan->tanggal_trip->toDateString(),
            'hotel_check_out' => $pesanan->tanggal_trip->copy()->addDay()->toDateString(),
            'jumlah_kamar' => 2,
            'tipe_kamar' => 'Deluxe twin',
            'status_reservasi_hotel' => 'diminta',
            ...$timpa,
        ];
    }

    private function simpan(Pesanan $pesanan, array $data)
    {
        return $this->actingAs(User::factory()->create())->put(route('admin.pesanan.hotel.simpan', $pesanan), $data);
    }

    // ── Simpan reservasi ─────────────────────────────────────────────

    public function test_reservasi_tersimpan_nama_hotel_tersinkron_dan_status_belum_menjadi_dipesankan(): void
    {
        $hotel = Hotel::factory()->create(['nama' => 'Bromo Permai 1']);
        $pesanan = $this->pesanan();

        $this->simpan($pesanan, $this->data($pesanan, $hotel))->assertSessionHasNoErrors();

        $pesanan->refresh();
        $this->assertSame($hotel->id, $pesanan->hotel_id);
        $this->assertSame('Bromo Permai 1', $pesanan->nama_hotel);
        $this->assertSame(2, $pesanan->jumlah_kamar);
        $this->assertSame('Deluxe twin', $pesanan->tipe_kamar);
        $this->assertSame(StatusReservasiHotel::Diminta, $pesanan->status_reservasi_hotel);
        $this->assertSame(StatusPenginapan::Dipesankan, $pesanan->status_penginapan);
    }

    public function test_status_all_in_tetap_diurus_setelah_reservasi_disimpan(): void
    {
        $hotel = Hotel::factory()->create();
        $pesanan = $this->pesanan(['status_penginapan' => 'diurus']);

        $this->simpan($pesanan, $this->data($pesanan, $hotel))->assertSessionHasNoErrors();

        $this->assertSame(StatusPenginapan::Diurus, $pesanan->fresh()->status_penginapan);
    }

    public function test_isian_wajib_reservasi_divalidasi(): void
    {
        $hotel = Hotel::factory()->create();
        $pesanan = $this->pesanan();

        foreach (['hotel_id', 'hotel_check_in', 'hotel_check_out', 'jumlah_kamar', 'status_reservasi_hotel'] as $kolom) {
            $this->simpan($pesanan, $this->data($pesanan, $hotel, [$kolom => null]))->assertSessionHasErrors($kolom);
        }

        $this->simpan($pesanan, $this->data($pesanan, $hotel, ['hotel_id' => 999999]))->assertSessionHasErrors('hotel_id');
        $this->simpan($pesanan, $this->data($pesanan, $hotel, ['jumlah_kamar' => 0]))->assertSessionHasErrors('jumlah_kamar');
        $this->simpan($pesanan, $this->data($pesanan, $hotel, ['status_reservasi_hotel' => 'entah']))->assertSessionHasErrors('status_reservasi_hotel');
        $this->simpan($pesanan, $this->data($pesanan, $hotel, ['tipe_kamar' => '<b>']))->assertSessionHasErrors('tipe_kamar');
        $this->simpan($pesanan, $this->data($pesanan, $hotel, ['hotel_check_in' => 'kemarin']))->assertSessionHasErrors('hotel_check_in');

        $this->assertNull($pesanan->fresh()->hotel_id);
    }

    public function test_kamar_yang_kurang_dari_jumlah_peserta_ditolak_dan_extra_bed_membantu(): void
    {
        $tanpaBed = Hotel::factory()->create(['kapasitas_kamar' => 2, 'tersedia_extra_bed' => false]);
        $denganBed = Hotel::factory()->create(['kapasitas_kamar' => 2, 'tersedia_extra_bed' => true]);
        $pesanan = $this->pesanan(['jumlah_wisatawan' => 6]);

        $this->simpan($pesanan, $this->data($pesanan, $tanpaBed, ['jumlah_kamar' => 2]))->assertSessionHasErrors('jumlah_kamar');
        $this->simpan($pesanan, $this->data($pesanan, $tanpaBed, ['jumlah_kamar' => 3]))->assertSessionHasNoErrors();
        $this->simpan($pesanan, $this->data($pesanan, $denganBed, ['jumlah_kamar' => 2]))->assertSessionHasNoErrors();
    }

    public function test_tanggal_check_in_dan_check_out_harus_sesuai_trip(): void
    {
        $hotel = Hotel::factory()->create();
        $pesanan = $this->pesanan();
        $trip = $pesanan->tanggal_trip;

        $this->simpan($pesanan, $this->data($pesanan, $hotel, ['hotel_check_in' => $trip->copy()->addDay()->toDateString(), 'hotel_check_out' => $trip->copy()->addDays(2)->toDateString()]))
            ->assertSessionHasErrors('hotel_check_in');
        $this->simpan($pesanan, $this->data($pesanan, $hotel, ['hotel_check_in' => $trip->copy()->subDays(5)->toDateString()]))
            ->assertSessionHasErrors('hotel_check_in');
        $this->simpan($pesanan, $this->data($pesanan, $hotel, ['hotel_check_out' => $trip->toDateString()]))
            ->assertSessionHasErrors('hotel_check_out');
        // Check-out sebelum trip berakhir (trip 2 hari: selesai H+1).
        $this->simpan($pesanan, $this->data($pesanan, $hotel, ['hotel_check_in' => $trip->copy()->subDay()->toDateString(), 'hotel_check_out' => $trip->toDateString()]))
            ->assertSessionHasErrors('hotel_check_out');

        // Menginap malam sebelumnya (mis. sunrise trip) dan check-out saat trip selesai diperbolehkan.
        $this->simpan($pesanan, $this->data($pesanan, $hotel, ['hotel_check_in' => $trip->copy()->subDay()->toDateString(), 'hotel_check_out' => $trip->copy()->addDay()->toDateString(), 'jumlah_kamar' => 2]))
            ->assertSessionHasNoErrors();
    }

    public function test_hotel_nonaktif_ditolak_tetapi_hotel_lama_yang_sudah_terpilih_boleh_disimpan_ulang(): void
    {
        $nonaktif = Hotel::factory()->nonaktif()->create();
        $pesanan = $this->pesanan();

        $this->simpan($pesanan, $this->data($pesanan, $nonaktif))->assertSessionHasErrors('hotel_id');

        $pesanan->update(['hotel_id' => $nonaktif->id, 'status_penginapan' => 'dipesankan']);

        $this->simpan($pesanan->fresh(), $this->data($pesanan, $nonaktif, ['tipe_kamar' => 'Superior']))->assertSessionHasNoErrors();
        $this->assertSame('Superior', $pesanan->fresh()->tipe_kamar);
    }

    public function test_status_terkonfirmasi_wajib_kode_booking_atau_nama_petugas(): void
    {
        $hotel = Hotel::factory()->create();
        $pesanan = $this->pesanan();

        $this->simpan($pesanan, $this->data($pesanan, $hotel, ['status_reservasi_hotel' => 'terkonfirmasi']))->assertSessionHasErrors('kode_booking_hotel');
        $this->simpan($pesanan, $this->data($pesanan, $hotel, ['status_reservasi_hotel' => 'terkonfirmasi', 'kode_booking_hotel' => 'BP-2210-77']))->assertSessionHasNoErrors();

        $this->assertSame(StatusReservasiHotel::Terkonfirmasi, $pesanan->fresh()->status_reservasi_hotel);
    }

    public function test_tamu_yang_sudah_punya_hotel_sendiri_atau_pesanan_selesai_tidak_bisa_dipesankan(): void
    {
        $hotel = Hotel::factory()->create();
        $sudah = $this->pesanan(['status_penginapan' => 'sudah', 'nama_hotel' => 'Hotel Tamu']);
        $selesai = $this->pesanan(['status' => 'completed']);
        $batal = $this->pesanan(['status' => 'dibatalkan']);

        $this->simpan($sudah, $this->data($sudah, $hotel))->assertSessionHasErrors('hotel_id');
        $this->simpan($selesai, $this->data($selesai, $hotel))->assertForbidden();
        $this->simpan($batal, $this->data($batal, $hotel))->assertForbidden();
    }

    public function test_reservasi_dibatalkan_mengosongkan_nama_hotel_dan_tetap_dipesankan(): void
    {
        $hotel = Hotel::factory()->create();
        $pesanan = $this->pesanan();
        $this->simpan($pesanan, $this->data($pesanan, $hotel))->assertSessionHasNoErrors();

        $this->simpan($pesanan->fresh(), $this->data($pesanan, $hotel, ['status_reservasi_hotel' => 'dibatalkan']))->assertSessionHasNoErrors();

        $pesanan->refresh();
        $this->assertNull($pesanan->nama_hotel);
        $this->assertSame(StatusReservasiHotel::Dibatalkan, $pesanan->status_reservasi_hotel);
        $this->assertSame(StatusPenginapan::Dipesankan, $pesanan->status_penginapan);
    }

    public function test_melepas_hotel_membersihkan_reservasi_dan_mengembalikan_status_belum(): void
    {
        $hotel = Hotel::factory()->create();
        $pesanan = $this->pesanan();
        $this->simpan($pesanan, $this->data($pesanan, $hotel))->assertSessionHasNoErrors();

        $this->actingAs(User::factory()->create())->delete(route('admin.pesanan.hotel.lepas', $pesanan->fresh()))->assertSessionHasNoErrors();

        $pesanan->refresh();
        $this->assertNull($pesanan->hotel_id);
        $this->assertNull($pesanan->nama_hotel);
        $this->assertNull($pesanan->status_reservasi_hotel);
        $this->assertNull($pesanan->jumlah_kamar);
        $this->assertSame(StatusPenginapan::Belum, $pesanan->status_penginapan);
    }

    /**
     * @param  array<string, mixed>  $timpa
     * @return array<string, mixed>
     */
    private function dataUbahPesanan(Pesanan $pesanan, array $timpa = []): array
    {
        return [
            'konfirmasi_tamu' => 1,
            'nama_lengkap' => $pesanan->nama_lengkap,
            'whatsapp' => '0'.substr($pesanan->whatsapp, 2),
            'lokasi_pickup' => $pesanan->lokasi_pickup,
            'jumlah_wisatawan' => $pesanan->jumlah_wisatawan,
            'status_penginapan' => 'dipesankan',
            'nama_hotel' => null,
            ...$timpa,
        ];
    }

    public function test_mengubah_pesanan_menjaga_nama_hotel_sesuai_reservasi(): void
    {
        $hotel = Hotel::factory()->create(['nama' => 'Hotel Sesuai Reservasi']);
        $pesanan = $this->pesanan();
        $this->simpan($pesanan, $this->data($pesanan, $hotel))->assertSessionHasNoErrors();

        $this->actingAs(User::factory()->create())
            ->put(route('admin.pesanan.perbarui', $pesanan->fresh()), $this->dataUbahPesanan($pesanan, ['nama_hotel' => 'Ketikan Lain']))
            ->assertSessionHasNoErrors();

        $pesanan->refresh();
        $this->assertSame('Hotel Sesuai Reservasi', $pesanan->nama_hotel);
        $this->assertSame($hotel->id, $pesanan->hotel_id);
    }

    public function test_mengubah_penginapan_menjadi_sudah_atau_belum_melepas_reservasi_hotel(): void
    {
        $hotel = Hotel::factory()->create();
        $admin = User::factory()->create();

        $pesananSudah = $this->pesanan();
        $this->simpan($pesananSudah, $this->data($pesananSudah, $hotel))->assertSessionHasNoErrors();
        $this->actingAs($admin)->put(route('admin.pesanan.perbarui', $pesananSudah->fresh()), $this->dataUbahPesanan($pesananSudah, ['status_penginapan' => 'sudah', 'nama_hotel' => 'Hotel Tamu Sendiri']))->assertSessionHasNoErrors();
        $pesananSudah->refresh();
        $this->assertNull($pesananSudah->hotel_id);
        $this->assertNull($pesananSudah->status_reservasi_hotel);
        $this->assertSame('Hotel Tamu Sendiri', $pesananSudah->nama_hotel);

        $pesananBelum = $this->pesanan();
        $this->simpan($pesananBelum, $this->data($pesananBelum, $hotel))->assertSessionHasNoErrors();
        $this->actingAs($admin)->put(route('admin.pesanan.perbarui', $pesananBelum->fresh()), $this->dataUbahPesanan($pesananBelum, ['status_penginapan' => 'belum']))->assertSessionHasNoErrors();
        $pesananBelum->refresh();
        $this->assertNull($pesananBelum->hotel_id);
        $this->assertNull($pesananBelum->nama_hotel);
    }

    public function test_tab_rekomendasi_muncul_lagi_setelah_reservasi_dibatalkan(): void
    {
        $admin = User::factory()->create();
        $hotel = Hotel::factory()->create();
        $pesanan = $this->pesanan();
        $this->simpan($pesanan, $this->data($pesanan, $hotel))->assertSessionHasNoErrors();

        $this->actingAs($admin)->get(route('admin.pesanan.detail', $pesanan->fresh()))->assertDontSee('Rekomendasi Penginapan');

        $this->simpan($pesanan->fresh(), $this->data($pesanan, $hotel, ['status_reservasi_hotel' => 'dibatalkan']))->assertSessionHasNoErrors();

        $this->actingAs($admin)->get(route('admin.pesanan.detail', $pesanan->fresh()))->assertSee('Rekomendasi Penginapan');
    }

    public function test_tamu_atau_non_admin_tidak_bisa_menyimpan_reservasi(): void
    {
        $hotel = Hotel::factory()->create();
        $pesanan = $this->pesanan();

        $this->put(route('admin.pesanan.hotel.simpan', $pesanan), $this->data($pesanan, $hotel))->assertRedirect(route('admin.autentikasi.masuk'));
    }

    // ── Kartu & panel periksa ────────────────────────────────────────

    public function test_kartu_reservasi_hanya_tampil_untuk_status_yang_butuh_hotel(): void
    {
        $admin = User::factory()->create();

        foreach (['belum', 'dipesankan', 'diurus'] as $status) {
            $this->actingAs($admin)->get(route('admin.pesanan.detail', $this->pesanan(['status_penginapan' => $status])))
                ->assertOk()->assertSee('Reservasi Hotel');
        }

        $this->actingAs($admin)->get(route('admin.pesanan.detail', $this->pesanan(['status_penginapan' => 'sudah', 'nama_hotel' => 'Hotel Tamu'])))
            ->assertOk()->assertDontSee('Reservasi Hotel');
    }

    public function test_panel_periksa_dan_tombol_kirim_mengikuti_kelengkapan_data(): void
    {
        $admin = User::factory()->create();
        $baik = Hotel::factory()->create(['nama' => 'Hotel Siap Kirim', 'whatsapp' => '081234567890']);
        $tanpaWa = Hotel::factory()->tanpaWhatsapp()->create(['nama' => 'Hotel Tanpa Nomor']);

        $pesananBaik = $this->pesanan();
        $this->simpan($pesananBaik, $this->data($pesananBaik, $baik))->assertSessionHasNoErrors();
        $this->actingAs($admin)->get(route('admin.pesanan.detail', $pesananBaik->fresh()))
            ->assertDontSee('Periksa sebelum kirim')
            ->assertSee('Kirim permintaan ke hotel')
            ->assertSee('wa.me/62'.substr('081234567890', 1), false);

        $pesananTanpaWa = $this->pesanan();
        $this->simpan($pesananTanpaWa, $this->data($pesananTanpaWa, $tanpaWa))->assertSessionHasNoErrors();
        $html = $this->actingAs($admin)->get(route('admin.pesanan.detail', $pesananTanpaWa->fresh()))->getContent();
        $this->assertStringContainsString('Nomor WhatsApp hotel belum diisi', $html);
        $this->assertStringNotContainsString('wa.me/', $this->ambilBagianReservasi($html));
    }

    private function ambilBagianReservasi(string $html): string
    {
        $awal = strpos($html, 'id="reservasi-hotel"');

        return $awal === false ? '' : substr($html, $awal, 12000);
    }

    public function test_konfirmasi_ke_tamu_hanya_muncul_setelah_terkonfirmasi(): void
    {
        $admin = User::factory()->create();
        $hotel = Hotel::factory()->create(['nama' => 'Hotel Konfirmasi']);
        $pesanan = $this->pesanan();
        $this->simpan($pesanan, $this->data($pesanan, $hotel))->assertSessionHasNoErrors();

        $this->actingAs($admin)->get(route('admin.pesanan.detail', $pesanan->fresh()))->assertDontSee('Konfirmasi hotel ke tamu');

        $this->simpan($pesanan->fresh(), $this->data($pesanan, $hotel, ['status_reservasi_hotel' => 'terkonfirmasi', 'kode_booking_hotel' => 'KB-1']))->assertSessionHasNoErrors();

        $this->actingAs($admin)->get(route('admin.pesanan.detail', $pesanan->fresh()))->assertSee('Konfirmasi hotel ke tamu');
    }

    // ── Pemeriksa ────────────────────────────────────────────────────

    public function test_pemeriksa_memberi_peringatan_lunak_untuk_kamar_berlebih_ganjil_dan_tarif_kosong(): void
    {
        $hotel = Hotel::factory()->create(['tarif_mulai' => null, 'tarif_sampai' => null, 'kapasitas_kamar' => 2, 'tersedia_extra_bed' => true]);
        $pesanan = $this->pesanan(['jumlah_wisatawan' => 3]);

        $hasil = collect(app(PemeriksaReservasiHotel::class)->periksa($pesanan, $hotel, $pesanan->tanggal_trip, $pesanan->tanggal_trip->copy()->addDay(), 4))->keyBy('kunci');

        $this->assertTrue($hasil['kamar']['ok']);
        $this->assertFalse($hasil['kamar_berlebih']['ok']);
        $this->assertFalse($hasil['kamar_berlebih']['keras']);
        $this->assertFalse($hasil['tarif']['ok']);
        $this->assertFalse($hasil['tarif']['keras']);
        $this->assertSame([], app(PemeriksaReservasiHotel::class)->kegagalanKeras($hasil->values()->all()));
    }

    public function test_pemeriksa_menandai_kegagalan_keras_untuk_kamar_dan_tanggal(): void
    {
        $hotel = Hotel::factory()->create(['kapasitas_kamar' => 2, 'tersedia_extra_bed' => false]);
        $pesanan = $this->pesanan(['jumlah_wisatawan' => 5]);

        $hasil = app(PemeriksaReservasiHotel::class)->periksa($pesanan, $hotel, $pesanan->tanggal_trip->copy()->addDays(3), $pesanan->tanggal_trip->copy()->addDays(2), 1);
        $kunciGagal = collect(app(PemeriksaReservasiHotel::class)->kegagalanKeras($hasil))->pluck('kunci')->all();

        $this->assertContains('kamar', $kunciGagal);
        $this->assertContains('check_in', $kunciGagal);
        $this->assertContains('check_out', $kunciGagal);
    }

    // ── Template WhatsApp ────────────────────────────────────────────

    public function test_template_permintaan_reservasi_ke_hotel_memuat_semua_rincian(): void
    {
        $hotel = Hotel::factory()->create(['nama' => 'Hotel Tugu', 'nama_pic' => 'Bu Rina', 'jam_check_in' => '14:00', 'jam_check_out' => '12:00']);
        $pesanan = $this->pesanan(['nama_lengkap' => 'Dewi Lestari', 'jumlah_wisatawan' => 4, 'status_penginapan' => 'dipesankan']);
        $this->simpan($pesanan, $this->data($pesanan, $hotel, ['catatan_reservasi_hotel' => 'Kamar bersebelahan']))->assertSessionHasNoErrors();

        $teks = app(PesanWhatsAppLayanan::class)->permintaanReservasiHotel($pesanan->fresh());

        foreach (['Bapak/Ibu Bu Rina', 'MISTBORNE Private Highland Travel', 'Nama tamu: Dewi Lestari', 'Jumlah tamu: 4 orang', 'mulai 14:00', 'sebelum 12:00', 'Lama menginap: 1 malam', 'Kamar: 2 kamar (Deluxe twin)', 'Catatan: Kamar bersebelahan', 'Pembayaran kamar dilakukan tamu langsung di hotel'] as $potongan) {
            $this->assertStringContainsString($potongan, $teks);
        }
    }

    public function test_template_permintaan_untuk_all_in_menyebut_pembayaran_oleh_mistborne(): void
    {
        $hotel = Hotel::factory()->create();
        $pesanan = $this->pesanan(['status_penginapan' => 'diurus']);
        $this->simpan($pesanan, $this->data($pesanan, $hotel))->assertSessionHasNoErrors();

        $teks = app(PesanWhatsAppLayanan::class)->permintaanReservasiHotel($pesanan->fresh());

        $this->assertStringContainsString('Pembayaran akan kami selesaikan setelah reservasi terkonfirmasi', $teks);
        $this->assertStringNotContainsString('dilakukan tamu langsung', $teks);
    }

    public function test_template_konfirmasi_ke_tamu_membedakan_bayar_langsung_dan_all_in(): void
    {
        $hotel = Hotel::factory()->create(['nama' => 'Hotel Konfirmasi', 'alamat' => 'Jl. Contoh 1', 'tautan_peta' => 'https://maps.example/x', 'kebijakan_pembatalan' => 'Gratis batal H-3.', 'tarif_mulai' => 400000, 'tarif_sampai' => 500000]);

        $dipesankan = $this->pesanan(['status_penginapan' => 'dipesankan']);
        $this->simpan($dipesankan, $this->data($dipesankan, $hotel, ['status_reservasi_hotel' => 'terkonfirmasi', 'kode_booking_hotel' => 'KB-9']))->assertSessionHasNoErrors();
        $teks = app(PesanWhatsAppLayanan::class)->konfirmasiHotelKeTamu($dipesankan->fresh());

        foreach (['Hotel Konfirmasi', 'Jl. Contoh 1', 'https://maps.example/x', 'Kode booking hotel: KB-9', 'Pembayaran kamar dilakukan Kakak langsung ke hotel', 'Perkiraan tarif hotel: Rp 400.000 – Rp 500.000', 'Gratis batal H-3.'] as $potongan) {
            $this->assertStringContainsString($potongan, $teks);
        }

        $allIn = $this->pesanan(['status_penginapan' => 'diurus']);
        $this->simpan($allIn, $this->data($allIn, $hotel, ['status_reservasi_hotel' => 'terkonfirmasi', 'kode_booking_hotel' => 'KB-10']))->assertSessionHasNoErrors();
        $teksAllIn = app(PesanWhatsAppLayanan::class)->konfirmasiHotelKeTamu($allIn->fresh());

        $this->assertStringContainsString('sudah termasuk dalam harga all-inclusive', $teksAllIn);
        $this->assertStringNotContainsString('langsung ke hotel saat check-in', $teksAllIn);
    }

    public function test_template_pembatalan_ke_hotel_menyebut_kode_booking(): void
    {
        $hotel = Hotel::factory()->create(['nama' => 'Hotel Batal']);
        $pesanan = $this->pesanan(['nama_lengkap' => 'Budi', 'status_penginapan' => 'dipesankan']);
        $this->simpan($pesanan, $this->data($pesanan, $hotel, ['status_reservasi_hotel' => 'terkonfirmasi', 'kode_booking_hotel' => 'KB-77']))->assertSessionHasNoErrors();

        $teks = app(PesanWhatsAppLayanan::class)->pembatalanReservasiHotel($pesanan->fresh());

        $this->assertStringContainsString('Tim Reservasi Hotel Batal', $teks);
        $this->assertStringContainsString('atas nama Budi (kode booking: KB-77)', $teks);
    }

    // ── Rekomendasi dari data hotel ──────────────────────────────────

    public function test_rekomendasi_memakai_hotel_aktif_yang_ditautkan_ke_journey_pesanan(): void
    {
        Pengaturan::current()->update(['rekomendasi_penginapan' => '• Teks Umum']);
        $journey = Perjalanan::factory()->create(['tempat' => 'Bromo, Jawa Timur']);
        $aktif = Hotel::factory()->create(['nama' => 'Hotel Rekomendasi Aktif', 'tarif_mulai' => 400000, 'tarif_sampai' => 500000, 'fasilitas' => ['sarapan', 'air_panas'], 'tautan_peta' => 'https://maps.example/aktif']);
        $nonaktif = Hotel::factory()->nonaktif()->create(['nama' => 'Hotel Nonaktif Tersembunyi']);
        $lain = Hotel::factory()->create(['nama' => 'Hotel Journey Lain']);
        $aktif->perjalanan()->attach($journey->id);
        $nonaktif->perjalanan()->attach($journey->id);
        $pesanan = Pesanan::factory()->create(['perjalanan_id' => $journey->id, 'status_penginapan' => 'belum', 'nama_hotel' => null, 'nama_lengkap' => 'Sari']);

        $teks = app(PesanWhatsAppLayanan::class)->rekomendasiPenginapan($pesanan);

        $this->assertStringContainsString('di sekitar Bromo, Jawa Timur', $teks);
        $this->assertStringContainsString('• Hotel Rekomendasi Aktif ★★★★ — ', $teks);
        $this->assertStringContainsString('Tarif: Rp 400.000 – Rp 500.000 per kamar per malam', $teks);
        $this->assertStringContainsString('Fasilitas: Sarapan, Air panas', $teks);
        $this->assertStringContainsString('Peta: https://maps.example/aktif', $teks);
        $this->assertStringContainsString('pembayaran tetap dilakukan Kakak langsung ke hotel', $teks);
        $this->assertStringNotContainsString('Hotel Nonaktif Tersembunyi', $teks);
        $this->assertStringNotContainsString('Hotel Journey Lain', $teks);
        $this->assertStringNotContainsString('Teks Umum', $teks);
        $this->assertStringNotContainsString('dipesan dan dibayar langsung oleh Kakak', $teks);
    }

    public function test_rekomendasi_jatuh_ke_teks_journey_atau_umum_bila_tidak_ada_hotel_tertaut(): void
    {
        Pengaturan::current()->update(['rekomendasi_penginapan' => '• Teks Umum']);
        $journey = Perjalanan::factory()->create(['rekomendasi_penginapan' => null]);
        $pesanan = Pesanan::factory()->create(['perjalanan_id' => $journey->id, 'status_penginapan' => 'belum', 'nama_hotel' => null]);

        $this->assertStringContainsString('• Teks Umum', app(PesanWhatsAppLayanan::class)->rekomendasiPenginapan($pesanan));
    }

    // ── Dashboard & filter ───────────────────────────────────────────

    public function test_dashboard_dan_filter_menghitung_reservasi_hotel_yang_belum_terkonfirmasi(): void
    {
        $admin = User::factory()->create();
        $hotel = Hotel::factory()->create();
        $menunggu = $this->pesanan(['nama_lengkap' => 'Tamu Menunggu Hotel', 'status_penginapan' => 'dipesankan']);
        $this->pesanan(['nama_lengkap' => 'Tamu Hotel Terkonfirmasi', 'status_penginapan' => 'diurus', 'hotel_id' => $hotel->id, 'status_reservasi_hotel' => 'terkonfirmasi']);
        $this->pesanan(['nama_lengkap' => 'Tamu Hotel Sendiri', 'status_penginapan' => 'sudah', 'nama_hotel' => 'Hotel Tamu']);

        $this->assertSame(1, Pesanan::query()->hotelBelumTerkonfirmasi()->count());
        $this->assertTrue(Pesanan::query()->hotelBelumTerkonfirmasi()->whereKey($menunggu->id)->exists());

        $this->actingAs($admin)->get(route('admin.dashboard.indeks'))->assertOk()->assertSee('Reservasi hotel belum terkonfirmasi');
        $this->actingAs($admin)->get(route('admin.pesanan.indeks', ['penginapan' => 'hotel']))
            ->assertSee('Tamu Menunggu Hotel')
            ->assertDontSee('Tamu Hotel Terkonfirmasi')
            ->assertDontSee('Tamu Hotel Sendiri');
    }

    public function test_admin_bisa_memilih_dipesankan_di_form_pesanan_dan_lencananya_tampil(): void
    {
        $admin = User::factory()->create();
        $pesanan = $this->pesanan(['status_penginapan' => 'dipesankan']);

        $this->actingAs($admin)->get(route('admin.pesanan.ubah', $pesanan))->assertOk()->assertSee('Dipesankan MISTBORNE (bayar ke hotel)');
        $this->actingAs($admin)->get(route('admin.pesanan.detail', $pesanan))->assertSee('Dipesankan MISTBORNE')->assertSee('Hotel belum dipesankan');
    }

    public function test_status_penginapan_dari_form_publik_selalu_diurus_walau_tamu_mengirim_nilai_lain(): void
    {
        $tamu = User::factory()->create(['jabatan' => 'tamu']);
        $journey = Perjalanan::query()->where('template_pesanan_kustom', false)->whereNotNull('harga')->where('tone', '<>', 'custom')->orderBy('urutan')->firstOrFail();
        $this->bukaTanggal($journey, Carbon::today()->addDays(10));

        $this->actingAs($tamu)->post(route('booking.simpan'), [
            'token_booking' => (string) Str::uuid(),
            'destination' => $journey->nama,
            'pax' => 2,
            'nama_lengkap' => 'Tamu Nakal',
            'whatsapp' => '081234567890',
            'tanggal_trip' => Carbon::today()->addDays(10)->toDateString(),
            'lokasi_pickup' => 'Stasiun',
            'kendaraan_pilihan' => 'Jeep klasik (petualangan dataran tinggi) — Toyota Land Cruiser FJ40',
            'status_penginapan' => 'dipesankan',
        ])->assertSessionHasNoErrors();

        // Tamu tidak menentukan penginapan: sistem all-inclusive selalu memakai "diurus MISTBORNE".
        $this->assertDatabaseHas('permintaan_pesanan', ['nama_lengkap' => 'Tamu Nakal', 'status_penginapan' => 'diurus']);
        $this->assertDatabaseMissing('permintaan_pesanan', ['status_penginapan' => 'dipesankan']);
    }
}
