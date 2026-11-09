<?php

namespace Tests\Feature\Penginapan;

use App\Enums\StatusPenginapan;
use App\Layanan\PesanWhatsAppLayanan;
use App\Models\JadwalKeberangkatan;
use App\Models\Perjalanan;
use App\Models\PermintaanPesanan;
use App\Models\Pesanan;
use App\Models\PesananKustom;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Sistem all-inclusive: semua journey baku dijual dengan penginapan dan tiket masuk diurus MISTBORNE;
 * harga per booking sudah memuat semuanya (tanpa tambahan per orang). Journey kustom dikecualikan.
 */
class PaketAllInTest extends TestCase
{
    private function journeyAllIn(int $harga = 2_000_000): Perjalanan
    {
        return Perjalanan::factory()->create(['harga' => $harga, 'maks_orang' => 10, 'template_pesanan_kustom' => false]);
    }

    /**
     * @param  array<string, mixed>  $timpa
     * @return array<string, mixed>
     */
    private function dataBooking(Perjalanan $journey, array $timpa = []): array
    {
        return [
            'token_booking' => (string) Str::uuid(),
            'destination' => $journey->nama,
            'pax' => 3,
            'nama_lengkap' => 'Tamu All In',
            'whatsapp' => '081234567890',
            'tanggal_trip' => Carbon::today()->addDays(10)->toDateString(),
            'lokasi_pickup' => 'Stasiun Malang',
            'kendaraan_pilihan' => 'Jeep klasik (petualangan dataran tinggi) — Toyota Land Cruiser FJ40',
            'status_penginapan' => 'diurus',
            ...$timpa,
        ];
    }

    // ── Model & harga ────────────────────────────────────────────────

    public function test_harga_all_inclusive_per_booking_tidak_ditambah_biaya_per_orang(): void
    {
        $journey = $this->journeyAllIn(2_000_000);

        $this->assertTrue($journey->semuaTermasuk());
        $this->assertTrue($journey->menawarkanAllIn());
        $this->assertSame(2_000_000, $journey->hargaDefault('diurus', 4));
        $this->assertSame(2_000_000, $journey->hargaDefault('sudah', 1));
    }

    public function test_journey_baku_all_inclusive_tetapi_journey_kustom_tidak(): void
    {
        $biasa = Perjalanan::factory()->create(['harga' => 2_000_000]);
        $kustom = Perjalanan::factory()->create(['tone' => 'custom', 'template_pesanan_kustom' => true]);

        $this->assertTrue($biasa->semuaTermasuk());
        $this->assertFalse($kustom->semuaTermasuk());
    }

    // ── Booking website ──────────────────────────────────────────────

    public function test_booking_all_inclusive_diterima_dan_hotel_dikosongkan(): void
    {
        Notification::fake();
        $user = User::factory()->create(['jabatan' => 'tamu']);
        $journey = $this->journeyAllIn();
        $this->bukaTanggal($journey, Carbon::today()->addDays(10));

        $respons = $this->actingAs($user)->post(route('booking.simpan'), $this->dataBooking($journey, ['nama_hotel' => 'Diabaikan']));

        $respons->assertSessionHasNoErrors();
        $permintaan = PermintaanPesanan::query()->where('nama_lengkap', 'Tamu All In')->firstOrFail();
        $this->assertSame(StatusPenginapan::Diurus, $permintaan->status_penginapan);
        $this->assertNull($permintaan->nama_hotel);

        parse_str((string) parse_url((string) $respons->headers->get('Location'), PHP_URL_QUERY), $parameter);
        $this->assertStringContainsString('All-inclusive (penginapan dan tiket masuk diurus MISTBORNE)', $parameter['text'] ?? '');
    }

    public function test_booking_journey_tanpa_tambahan_tetap_all_inclusive(): void
    {
        Notification::fake();
        $user = User::factory()->create(['jabatan' => 'tamu']);
        $biasa = Perjalanan::factory()->create(['harga' => 2_000_000, 'maks_orang' => 10]);
        $this->bukaTanggal($biasa, Carbon::today()->addDays(10));

        $this->actingAs($user)->post(route('booking.simpan'), $this->dataBooking($biasa, ['status_penginapan' => 'sudah']))
            ->assertSessionHasNoErrors();

        $this->assertSame(StatusPenginapan::Diurus, PermintaanPesanan::query()->firstOrFail()->status_penginapan);
    }

    public function test_halaman_booking_menjelaskan_all_inclusive_tanpa_biaya_mendadak(): void
    {
        $user = User::factory()->create(['jabatan' => 'tamu']);
        $journey = $this->journeyAllIn();

        $this->actingAs($user)->get(route('booking.buat', ['destination' => $journey->nama]))
            ->assertOk()
            ->assertSee('All-inclusive: tamu cukup membayar sekali')
            ->assertSee('Tidak ada biaya mendadak di lokasi')
            ->assertDontSee('value="diurus"', false)
            ->assertDontSee('Perkiraan total');
    }

    // ── Form pesanan lewat tautan ────────────────────────────────────

    public function test_form_pesanan_tautan_selalu_menyimpan_all_inclusive_untuk_journey_baku(): void
    {
        Notification::fake();
        $journey = $this->journeyAllIn();
        $this->bukaTanggal($journey, Carbon::today()->addDays(12));
        $permintaan = PermintaanPesanan::query()->create([
            'token' => Str::random(64),
            'whatsapp' => '081234567890',
            'status' => 'menunggu_tamu',
            'token_berlaku_sampai' => now()->addDays(7),
        ]);

        $this->post(route('form-pesanan.simpan', $permintaan->token), [
            'nama_lengkap' => 'Tamu Tautan All In',
            'whatsapp' => '081234567890',
            'tanggal_trip' => Carbon::today()->addDays(12)->toDateString(),
            'jumlah_wisatawan' => 2,
            'lokasi_pickup' => 'Bandara Juanda',
            'kendaraan_pilihan' => 'Jeep klasik (petualangan dataran tinggi) — Toyota Land Cruiser FJ40',
            'perjalanan_id' => $journey->id,
        ])->assertSessionHasNoErrors();

        $this->assertSame(StatusPenginapan::Diurus, $permintaan->fresh()->status_penginapan);
    }

    // ── Trip kustom ──────────────────────────────────────────────────

    public function test_trip_kustom_tidak_menawarkan_paket_all_in(): void
    {
        $this->post(route('rancang-trip-kustom.simpan'), [
            'nama' => 'Tamu Kustom',
            'whatsapp' => '081234567890',
            'destinasi_diinginkan' => 'Rinjani',
            'tanggal_fleksibel' => 1,
            'durasi_hari' => 3,
            'lokasi_penjemputan' => 'Bandara',
            'jumlah_orang' => 2,
            'status_penginapan' => 'diurus',
        ])->assertSessionHasErrors('status_penginapan');

        $this->assertSame(0, PesananKustom::count());
    }

    // ── Admin: pesanan ───────────────────────────────────────────────

    public function test_admin_membuat_pesanan_all_inclusive_dengan_harga_journey_tanpa_tambahan(): void
    {
        $admin = User::factory()->create();
        $journey = $this->journeyAllIn(2_000_000);
        $jadwal = JadwalKeberangkatan::factory()->create(['perjalanan_id' => $journey->id]);

        $this->actingAs($admin)->post(route('admin.pesanan.simpan'), [
            'perjalanan_id' => $journey->id,
            'tanggal_trip' => $jadwal->tanggal->toDateString(),
            'nama_lengkap' => 'Tamu Admin All In',
            'whatsapp' => '081234567890',
            'jumlah_wisatawan' => 4,
            'lokasi_pickup' => 'Stasiun Gambir',
            'status_penginapan' => 'diurus',
        ])->assertSessionHasNoErrors();

        $pesanan = Pesanan::query()->where('nama_lengkap', 'Tamu Admin All In')->firstOrFail();
        $this->assertSame(StatusPenginapan::Diurus, $pesanan->status_penginapan);
        $this->assertSame(2_000_000, $pesanan->harga);
        $this->assertNull($pesanan->nama_hotel);
    }

    public function test_admin_tidak_bisa_memilih_all_inclusive_untuk_journey_kustom(): void
    {
        $admin = User::factory()->create();
        $journey = Perjalanan::factory()->create(['harga' => 2_000_000, 'tone' => 'custom', 'maks_orang' => 10]);
        $jadwal = JadwalKeberangkatan::factory()->create(['perjalanan_id' => $journey->id]);

        $this->actingAs($admin)->post(route('admin.pesanan.simpan'), [
            'perjalanan_id' => $journey->id,
            'tanggal_trip' => $jadwal->tanggal->toDateString(),
            'nama_lengkap' => 'Tamu Salah Paket',
            'whatsapp' => '081234567890',
            'jumlah_wisatawan' => 2,
            'lokasi_pickup' => 'Stasiun Gambir',
            'status_penginapan' => 'diurus',
        ])->assertSessionHasErrors('status_penginapan');
    }

    public function test_admin_mengisi_nama_hotel_setelah_hotel_dipesankan_untuk_pesanan_all_in(): void
    {
        $admin = User::factory()->create();
        $journey = $this->journeyAllIn();
        $pesanan = Pesanan::factory()->create([
            'perjalanan_id' => $journey->id,
            'status_penginapan' => 'diurus',
            'nama_hotel' => null,
            'jumlah_wisatawan' => 2,
            'harga' => 3_500_000,
            'tanggal_trip' => now()->addDays(25)->toDateString(),
        ]);

        $this->actingAs($admin)->put(route('admin.pesanan.perbarui', $pesanan), [
            'konfirmasi_tamu' => 1,
            'nama_lengkap' => $pesanan->nama_lengkap,
            'whatsapp' => '0'.substr($pesanan->whatsapp, 2),
            'status_penginapan' => 'diurus',
            'nama_hotel' => 'Hotel Bromo Permai 1',
            'lokasi_pickup' => $pesanan->lokasi_pickup,
            'jumlah_wisatawan' => 2,
            'harga' => 3_500_000,
        ])->assertSessionHasNoErrors();

        $pesanan->refresh();
        $this->assertSame(StatusPenginapan::Diurus, $pesanan->status_penginapan);
        $this->assertSame('Hotel Bromo Permai 1', $pesanan->nama_hotel);
    }

    public function test_permintaan_all_inclusive_menjadi_pesanan_dengan_harga_journey(): void
    {
        Notification::fake();
        $admin = User::factory()->create();
        $journey = $this->journeyAllIn(2_000_000);
        $jadwal = JadwalKeberangkatan::factory()->create(['perjalanan_id' => $journey->id, 'tanggal' => now()->addDays(15)]);
        $permintaan = PermintaanPesanan::query()->create([
            'token' => Str::random(64),
            'whatsapp' => '081234567890',
            'status' => 'menunggu_tinjauan',
            'perjalanan_id' => $journey->id,
            'nama_lengkap' => 'Tamu Permintaan All In',
            'tanggal_trip' => $jadwal->tanggal->toDateString(),
            'jumlah_wisatawan' => 3,
            'lokasi_pickup' => 'Stasiun Malang',
            'status_penginapan' => 'diurus',
            'token_berlaku_sampai' => now()->addDays(7),
        ]);

        $this->actingAs($admin)->post(route('admin.permintaan-pesanan.buat-pesanan', $permintaan))->assertSessionHasNoErrors();

        $pesanan = Pesanan::query()->where('nama_lengkap', 'Tamu Permintaan All In')->firstOrFail();
        $this->assertSame(StatusPenginapan::Diurus, $pesanan->status_penginapan);
        $this->assertSame(2_000_000, $pesanan->harga);
    }

    public function test_detail_pesanan_all_in_menampilkan_lencana_tab_info_dan_dashboard_menghitungnya(): void
    {
        $admin = User::factory()->create();
        $journey = $this->journeyAllIn();
        $pesanan = Pesanan::factory()->create([
            'perjalanan_id' => $journey->id,
            'nama_lengkap' => 'Tamu Belum Dipesankan Hotel',
            'status_penginapan' => 'diurus',
            'nama_hotel' => null,
            'tanggal_trip' => now()->addDays(15)->toDateString(),
        ]);
        Pesanan::factory()->create(['perjalanan_id' => $journey->id, 'status_penginapan' => 'diurus', 'nama_hotel' => 'Hotel Sudah Dipesan', 'status_reservasi_hotel' => 'terkonfirmasi', 'tanggal_trip' => now()->addDays(15)->toDateString()]);

        $this->assertSame(1, Pesanan::query()->hotelBelumTerkonfirmasi()->count());

        $this->actingAs($admin)->get(route('admin.pesanan.detail', $pesanan))
            ->assertOk()
            ->assertSee('All-in · diurus MISTBORNE')
            ->assertSee('Hotel belum dipesankan')
            ->assertSee('Info Penginapan All-inclusive');

        $this->actingAs($admin)->get(route('admin.dashboard.indeks'))->assertOk()->assertSee('Reservasi hotel belum terkonfirmasi');

        $this->actingAs($admin)->get(route('admin.pesanan.indeks', ['penginapan' => 'hotel']))
            ->assertOk()
            ->assertSee('Tamu Belum Dipesankan Hotel')
            ->assertDontSee('Hotel Sudah Dipesan');
    }

    public function test_pesan_info_penginapan_all_in_menyebut_hotel_atau_kabar_menyusul(): void
    {
        $layanan = app(PesanWhatsAppLayanan::class);
        $tanpaHotel = Pesanan::factory()->create(['nama_lengkap' => 'Sari', 'jumlah_wisatawan' => 5, 'status_penginapan' => 'diurus', 'nama_hotel' => null]);
        $denganHotel = Pesanan::factory()->create(['nama_lengkap' => 'Budi', 'jumlah_wisatawan' => 4, 'status_penginapan' => 'diurus', 'nama_hotel' => 'Hotel Sawah Ijen']);

        $teksMenunggu = $layanan->infoPenginapanAllIn($tanpaHotel);
        $this->assertStringContainsString('sedang kami siapkan (sekitar 2 kamar', $teksMenunggu);

        $teksHotel = $layanan->infoPenginapanAllIn($denganHotel);
        $this->assertStringContainsString('• Hotel Sawah Ijen', $teksHotel);
        $this->assertStringContainsString('Jumlah kamar: 1 (4 orang per kamar)', $teksHotel);
        $this->assertStringContainsString('Penginapan dan tiket masuk objek wisata sudah termasuk', $teksHotel);

        $this->assertStringContainsString('All-inclusive', $layanan->pesananDisetujui($tanpaHotel));
        $this->assertStringContainsString('detail hotel akan kami kirim terpisah', $layanan->pengingatH2($tanpaHotel));
    }

    // ── Admin: journey ───────────────────────────────────────────────

    public function test_form_journey_admin_tidak_lagi_memiliki_tambahan_all_in(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->get(route('admin.perjalanan.buat'))
            ->assertOk()
            ->assertSee('All-inclusive:')
            ->assertDontSee('name="tambahan_all_in"', false);
    }

    // ── Publik: detail journey & form kontak ─────────────────────────

    public function test_detail_journey_baku_menampilkan_all_inclusive_dan_rincian_termasuk(): void
    {
        $baku = $this->journeyAllIn();

        $this->get(route('journeys.show', $baku->slug))
            ->assertOk()
            ->assertSee('Tiket masuk dan penginapan sudah termasuk')
            ->assertSee('Penginapan minimal bintang 4 (maks. 4 orang per kamar)')
            ->assertSee('Tiket masuk objek wisata')
            ->assertDontSee('Paket All-in tersedia')
            ->assertDontSee('Dengan Paket All-in, ditambahkan');
    }

    public function test_form_kontak_beranda_all_inclusive_tanpa_pilihan_penginapan(): void
    {
        $this->journeyAllIn();

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('kontak-penginapan-diurus', $html);
        $this->assertStringContainsString('All-inclusive (penginapan dan tiket masuk diurus MISTBORNE)', $html);
    }

    public function test_halaman_form_pesanan_all_inclusive_tanpa_isian_hotel(): void
    {
        $this->journeyAllIn();
        $permintaan = PermintaanPesanan::query()->create([
            'token' => Str::random(64),
            'whatsapp' => '081234567890',
            'status' => 'menunggu_tamu',
            'token_berlaku_sampai' => now()->addDays(7),
        ]);

        $this->get(route('form-pesanan.tampil', $permintaan->token))
            ->assertOk()
            ->assertSee('All-inclusive: tamu cukup membayar sekali')
            ->assertDontSee('name="status_penginapan"', false)
            ->assertDontSee('name="nama_hotel"', false);
    }
}
