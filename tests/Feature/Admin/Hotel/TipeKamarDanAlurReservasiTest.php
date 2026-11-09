<?php

namespace Tests\Feature\Admin\Hotel;

use App\Enums\StatusPenginapan;
use App\Enums\StatusReservasiHotel;
use App\Layanan\PemeriksaReservasiHotel;
use App\Layanan\PesanWhatsAppLayanan;
use App\Models\Hotel;
use App\Models\Pesanan;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\HotelSeeder;
use Illuminate\Support\Arr;
use Tests\TestCase;

class TipeKamarDanAlurReservasiTest extends TestCase
{
    private function hotelBertipe(array $timpa = []): Hotel
    {
        return Hotel::factory()->create([
            'nama' => 'Hotel Bertipe',
            'tipe_kamar' => [['nama' => 'Deluxe', 'kapasitas' => 2], ['nama' => 'Family Room', 'kapasitas' => 4]],
            ...$timpa,
        ]);
    }

    private function pesanan(array $timpa = []): Pesanan
    {
        $trip = Carbon::today()->addDays(20);

        return Pesanan::factory()->create([
            'tanggal_trip' => $trip->toDateString(),
            'tanggal_selesai' => $trip->copy()->addDay()->toDateString(),
            'jumlah_wisatawan' => 4,
            'status_penginapan' => 'diurus',
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
            'jumlah_kamar' => 1,
            'tipe_kamar' => 'Family Room',
            'status_reservasi_hotel' => 'diminta',
            ...$timpa,
        ];
    }

    private function simpan(Pesanan $pesanan, array $data)
    {
        return $this->actingAs(User::factory()->create())->put(route('admin.pesanan.hotel.simpan', $pesanan), $data);
    }

    public function test_kamar_dihitung_dari_kapasitas_tipe_yang_dipilih(): void
    {
        $hotel = $this->hotelBertipe();
        $pesanan = $this->pesanan();

        $this->simpan($pesanan, $this->data($pesanan, $hotel, ['tipe_kamar' => 'Family Room', 'jumlah_kamar' => 1]))->assertSessionHasNoErrors();
        $this->assertSame('Family Room', $pesanan->fresh()->tipe_kamar);

        $this->simpan($pesanan, $this->data($pesanan, $hotel, ['tipe_kamar' => 'Deluxe', 'jumlah_kamar' => 1]))
            ->assertSessionHasErrors('jumlah_kamar');
        $this->simpan($pesanan, $this->data($pesanan, $hotel, ['tipe_kamar' => 'Deluxe', 'jumlah_kamar' => 2]))->assertSessionHasNoErrors();
    }

    public function test_tipe_kamar_harus_milik_hotel_yang_dipilih(): void
    {
        $hotel = $this->hotelBertipe();
        $pesanan = $this->pesanan();

        $this->simpan($pesanan, $this->data($pesanan, $hotel, ['tipe_kamar' => 'Presidential Suite']))->assertSessionHasErrors('tipe_kamar');
        $this->simpan($pesanan, $this->data($pesanan, $hotel, ['tipe_kamar' => null]))->assertSessionHasErrors('tipe_kamar');
        $this->assertNull($pesanan->fresh()->hotel_id);
    }

    public function test_hotel_tanpa_tipe_terdaftar_memakai_kapasitas_standar(): void
    {
        $hotel = Hotel::factory()->create(['tipe_kamar' => null, 'kapasitas_kamar' => 2]);
        $pesanan = $this->pesanan();

        $this->assertSame([['nama' => 'Kamar standar', 'kapasitas' => 2]], $hotel->daftarTipeKamar());
        $this->simpan($pesanan, $this->data($pesanan, $hotel, ['tipe_kamar' => 'Kamar standar', 'jumlah_kamar' => 2]))->assertSessionHasNoErrors();
    }

    public function test_admin_menyimpan_tipe_kamar_deskripsi_dan_jarak_hotel(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.hotel.simpan'), [
            'nama' => 'Hotel Baru Bertipe', 'bintang' => 4, 'area' => 'Dieng', 'kapasitas_kamar' => 2,
            'jam_check_in' => '14:00', 'jam_check_out' => '12:00', 'aktif' => '1',
            'deskripsi' => 'Hotel nyaman dekat Dieng.', 'jarak_destinasi' => '± 20 menit ke Sikunir',
            'tipe_kamar' => [['nama' => 'Deluxe', 'kapasitas' => 2], ['nama' => 'Suite', 'kapasitas' => 4], ['nama' => '', 'kapasitas' => '']],
        ])->assertSessionHasNoErrors();

        $hotel = Hotel::query()->where('nama', 'Hotel Baru Bertipe')->firstOrFail();
        $this->assertSame([['nama' => 'Deluxe', 'kapasitas' => 2], ['nama' => 'Suite', 'kapasitas' => 4]], $hotel->daftarTipeKamar());
        $this->assertSame('± 20 menit ke Sikunir', $hotel->jarak_destinasi);

        $this->actingAs($admin)->post(route('admin.hotel.simpan'), [
            'nama' => 'Hotel Tipe Kembar', 'bintang' => 4, 'area' => 'Dieng', 'kapasitas_kamar' => 2,
            'jam_check_in' => '14:00', 'jam_check_out' => '12:00',
            'tipe_kamar' => [['nama' => 'Deluxe', 'kapasitas' => 2], ['nama' => 'deluxe', 'kapasitas' => 3]],
        ])->assertSessionHasErrors('tipe_kamar.1.nama');
    }

    public function test_menyimpan_reservasi_membuka_modal_yang_hanya_berisi_draft_untuk_hotel(): void
    {
        $hotel = $this->hotelBertipe(['whatsapp' => '081808606086']);
        $pesanan = $this->pesanan();

        $this->simpan($pesanan, Arr::except($this->data($pesanan, $hotel), ['status_reservasi_hotel']))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('modal_reservasi_hotel', 'diminta');

        $this->actingAs(User::factory()->create())->withSession(['modal_reservasi_hotel' => 'diminta'])
            ->get(route('admin.pesanan.detail', $pesanan))
            ->assertOk()
            ->assertSee('Kirim permintaan ke hotel')
            ->assertSee('Tim Reservasi Hotel Bertipe')
            ->assertDontSee('Kabari tamu: Info Penginapan All-inclusive')
            ->assertSee('buka-modal', false);
    }

    public function test_draft_hotel_ada_di_kartu_pesan_whatsapp_dan_bukan_lagi_di_kartu_reservasi(): void
    {
        $hotel = $this->hotelBertipe(['whatsapp' => '081808606086']);
        $pesanan = $this->pesanan();
        $this->simpan($pesanan, $this->data($pesanan, $hotel));

        $this->actingAs(User::factory()->create())->get(route('admin.pesanan.detail', $pesanan))
            ->assertOk()
            ->assertSee('Reservasi Hotel')
            ->assertSee('Permintaan reservasi ke hotel')
            ->assertSee('Buka WhatsApp Hotel')
            ->assertDontSee('Draft permintaan reservasi ke hotel');
    }

    public function test_hotel_sudah_setuju_menandai_terkonfirmasi_tanpa_membuka_draft_ke_tamu(): void
    {
        $hotel = $this->hotelBertipe();
        $pesanan = $this->pesanan();
        $this->simpan($pesanan, $this->data($pesanan, $hotel));
        $admin = User::factory()->create();

        $this->actingAs($admin)->put(route('admin.pesanan.hotel.konfirmasi', $pesanan), ['kode_booking_hotel' => ''])->assertSessionHasErrors('kode_booking_hotel');

        $this->actingAs($admin)->put(route('admin.pesanan.hotel.konfirmasi', $pesanan), ['kode_booking_hotel' => 'HTL-7788'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('sukses', 'Hotel ditandai sudah setuju. Lanjut tahap Bayar hotel: catat transfer dan unggah buktinya.')
            ->assertSessionMissing('modal_reservasi_hotel');

        $pesanan->refresh();
        $this->assertSame(StatusReservasiHotel::Terkonfirmasi, $pesanan->status_reservasi_hotel);
        $this->assertSame('HTL-7788', $pesanan->kode_booking_hotel);
        $this->assertNotNull($pesanan->permintaan_hotel_dikirim_at);
    }

    public function test_info_penginapan_tamu_memuat_jam_check_in_dan_check_out_setelah_terkonfirmasi(): void
    {
        $hotel = $this->hotelBertipe(['jam_check_in' => '15:00', 'jam_check_out' => '11:00', 'alamat' => 'Jl. Uji No. 1']);
        $pesanan = $this->pesanan();
        $this->simpan($pesanan, $this->data($pesanan, $hotel, ['status_reservasi_hotel' => 'terkonfirmasi', 'kode_booking_hotel' => 'HTL-1']));

        $pesan = app(PesanWhatsAppLayanan::class)->infoPenginapanAllIn($pesanan->fresh());

        $this->assertStringContainsString('mulai pukul 15:00 WIB', $pesan);
        $this->assertStringContainsString('paling lambat pukul 11:00 WIB', $pesan);
        $this->assertStringContainsString('tipe Family Room', $pesan);
        $this->assertStringContainsString('HTL-1', $pesan);
        $this->assertStringContainsString('tidak dapat diganti', $pesan);
        $this->assertStringContainsString('biaya (charge)', $pesan);
        $this->assertStringNotContainsString('akan kami informasikan lagi menjelang keberangkatan', $pesan);
    }

    public function test_info_penginapan_saat_menunggu_hotel_memuat_rencana_jam_dan_janji_kabar(): void
    {
        $hotel = $this->hotelBertipe();
        $pesanan = $this->pesanan();
        $this->simpan($pesanan, $this->data($pesanan, $hotel));

        $pesan = app(PesanWhatsAppLayanan::class)->infoPenginapanAllIn($pesanan->fresh());

        $this->assertStringContainsString('menunggu jawaban pihak hotel', $pesan);
        $this->assertStringContainsString('mulai pukul 14:00 WIB', $pesan);
    }

    public function test_hotel_terkonfirmasi_tidak_bisa_diganti_atau_dilepas_tanpa_persetujuan_biaya(): void
    {
        $hotelA = $this->hotelBertipe();
        $hotelB = $this->hotelBertipe(['nama' => 'Hotel Pengganti']);
        $pesanan = $this->pesanan();
        $this->simpan($pesanan, $this->data($pesanan, $hotelA, ['status_reservasi_hotel' => 'terkonfirmasi', 'kode_booking_hotel' => 'HTL-1']));
        $admin = User::factory()->create();

        $this->actingAs($admin)->put(route('admin.pesanan.hotel.simpan', $pesanan), $this->data($pesanan, $hotelB, ['status_reservasi_hotel' => 'terkonfirmasi', 'kode_booking_hotel' => 'HTL-2']))
            ->assertSessionHasErrors('hotel_id');
        $this->assertSame($hotelA->id, $pesanan->fresh()->hotel_id);

        $this->actingAs($admin)->delete(route('admin.pesanan.hotel.lepas', $pesanan))->assertSessionHas('gagal');
        $this->assertSame($hotelA->id, $pesanan->fresh()->hotel_id);

        $this->actingAs($admin)->put(route('admin.pesanan.hotel.simpan', $pesanan), $this->data($pesanan, $hotelB, ['status_reservasi_hotel' => 'terkonfirmasi', 'kode_booking_hotel' => 'HTL-2', 'setuju_biaya_ganti_hotel' => '1']))
            ->assertSessionHasNoErrors();
        $this->assertSame($hotelB->id, $pesanan->fresh()->hotel_id);
    }

    public function test_hotel_yang_belum_terkonfirmasi_masih_bebas_diganti(): void
    {
        $hotelA = $this->hotelBertipe();
        $hotelB = $this->hotelBertipe(['nama' => 'Hotel Pengganti']);
        $pesanan = $this->pesanan();
        $this->simpan($pesanan, $this->data($pesanan, $hotelA));

        $this->simpan($pesanan, $this->data($pesanan, $hotelB))->assertSessionHasNoErrors();
        $this->assertSame($hotelB->id, $pesanan->fresh()->hotel_id);
        $this->assertSame(StatusPenginapan::Diurus, $pesanan->fresh()->status_penginapan);
    }

    public function test_seeder_hotel_memakai_nomor_uji_dan_hanya_hotel_bintang_empat_atau_lima_terverifikasi_yang_aktif(): void
    {
        $this->seed(HotelSeeder::class);

        $this->assertSame(0, Hotel::query()->where('whatsapp', '<>', config('mistborne.kontak_uji.whatsapp'))->count());
        $this->assertSame(0, Hotel::query()->whereNull('tautan_peta')->count());
        $this->assertSame(0, Hotel::query()->whereNotIn('bintang', Hotel::BINTANG_DIIZINKAN)->count());
        $this->assertSame(5, Hotel::query()->where('nama', 'The Singhasari Resort')->value('bintang'));
        $this->assertTrue(Hotel::query()->where('nama', 'Artotel Wanasaba')->firstOrFail()->aktif);
        $this->assertNull(Hotel::query()->where('nama', 'Lereng Bromo Hotel')->first());
        $this->assertNull(Hotel::query()->where('nama', 'Bromo Permai 1')->first());
        Hotel::query()->each(fn (Hotel $hotel) => $this->assertNotEmpty($hotel->daftarTipeKamar()));
    }

    public function test_pemeriksa_memperingatkan_bila_kamar_melebihi_standar_nyaman_empat_orang_tanpa_menghalangi_simpan(): void
    {
        $hotel = $this->hotelBertipe(['tipe_kamar' => [['nama' => 'Deluxe', 'kapasitas' => 2], ['nama' => 'Family Room', 'kapasitas' => 4], ['nama' => 'Villa', 'kapasitas' => 8]]]);
        $pesanan = $this->pesanan(['jumlah_wisatawan' => 6]);
        $pemeriksa = app(PemeriksaReservasiHotel::class);
        $periksa = fn (int $kamar, string $tipe) => collect($pemeriksa->periksa($pesanan, $hotel, $pesanan->tanggal_trip, $pesanan->tanggal_trip->copy()->addDay(), $kamar, $tipe))->keyBy('kunci');

        $hemat = $periksa(1, 'Villa');
        $this->assertFalse($hemat['kenyamanan']['ok']);
        $this->assertFalse($hemat['kenyamanan']['keras']);
        $this->assertStringContainsString('2 kamar untuk 6 peserta', $hemat['kenyamanan']['keterangan']);
        $this->assertSame([], $pemeriksa->kegagalanKeras($hemat->values()->all()));

        $this->assertArrayNotHasKey('kenyamanan', $periksa(2, 'Family Room')->all());

        $pasEmpat = $this->pesanan(['jumlah_wisatawan' => 4]);
        $satuKamar = collect($pemeriksa->periksa($pasEmpat, $hotel, $pasEmpat->tanggal_trip, $pasEmpat->tanggal_trip->copy()->addDay(), 1, 'Family Room'))->keyBy('kunci');
        $this->assertArrayNotHasKey('kenyamanan', $satuKamar->all());
    }
}
