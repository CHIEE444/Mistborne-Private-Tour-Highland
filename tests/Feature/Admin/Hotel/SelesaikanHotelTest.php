<?php

namespace Tests\Feature\Admin\Hotel;

use App\Enums\BookingStatus;
use App\Layanan\AlurReservasiHotel;
use App\Models\Hotel;
use App\Models\PersonelOperasional;
use App\Models\Pesanan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\Support\DataGuide;
use Tests\TestCase;

/** Tabel "Selesaikan Hotel" di dashboard (pindahan Urus Hotel dari menu Hotel) dan aturan Selesai yang menunggu hotel. */
class SelesaikanHotelTest extends TestCase
{
    private function pesanan(array $timpa = [], bool $denganHotel = true): Pesanan
    {
        $trip = Carbon::today()->addDays(20);
        $hotel = Hotel::factory()->create(['nama' => 'Hotel Antrean '.fake()->unique()->numerify('###'), 'whatsapp' => '081808606086']);

        return Pesanan::factory()->payment()->create([
            'tanggal_trip' => $trip->toDateString(),
            'tanggal_selesai' => $trip->copy()->addDay()->toDateString(),
            'jumlah_wisatawan' => 4,
            'status_penginapan' => 'diurus',
            'harga' => 4_000_000,
            'nominal_dibayar' => 4_000_000,
            ...($denganHotel ? [
                'hotel_id' => $hotel->id,
                'nama_hotel' => $hotel->nama,
                'hotel_check_in' => $trip->toDateString(),
                'hotel_check_out' => $trip->copy()->addDay()->toDateString(),
                'jumlah_kamar' => 1,
                'status_reservasi_hotel' => 'diminta',
            ] : ['hotel_id' => null, 'status_reservasi_hotel' => null]),
            ...$timpa,
        ]);
    }

    private function selesaikanSemua(Pesanan $pesanan): void
    {
        $pesanan->update([
            'status_reservasi_hotel' => 'terkonfirmasi', 'kode_booking_hotel' => 'OK',
            'permintaan_hotel_dikirim_at' => now(), 'bayar_hotel_nominal' => 700_000, 'bayar_hotel_tanggal' => today()->toDateString(),
            'bukti_bayar_hotel_dikirim_at' => now(), 'bayar_hotel_dikonfirmasi_at' => now(),
            'tamu_dikabari_hotel_at' => now(), 'konfirmasi_ulang_hotel_at' => now(),
        ]);
    }

    /** HTML kartu "Selesaikan Hotel" saja, supaya kartu dashboard lain (mis. Pesanan Terbaru) tidak ikut terbaca. */
    private function kartuSelesaikanHotel(): string
    {
        $html = $this->actingAs(User::factory()->create())->get(route('admin.dashboard.indeks'))->assertOk()->getContent();
        $awal = strpos($html, 'id="selesaikan-hotel"');
        $this->assertNotFalse($awal, 'Kartu Selesaikan Hotel tidak tampil di dashboard.');

        return substr($html, $awal, strpos($html, 'Pesanan per Bulan', $awal) - $awal);
    }

    public function test_dashboard_hanya_memuat_pesanan_payment_yang_hotelnya_belum_selesai_dan_trip_belum_lewat(): void
    {
        $masuk = $this->pesanan(['nama_lengkap' => 'Tamu Masuk Antrean']);
        $this->selesaikanSemua($this->pesanan(['nama_lengkap' => 'Tamu Sudah Selesai']));
        $this->pesanan(['status' => 'dihubungi']);
        $this->pesanan(['status' => 'dibatalkan']);
        $this->pesanan(['status_penginapan' => 'sudah']);
        $this->pesanan(['tanggal_trip' => today()->subDays(10)->toDateString(), 'tanggal_selesai' => today()->subDays(9)->toDateString()]);

        $this->actingAs(User::factory()->create())->get(route('admin.dashboard.indeks'))
            ->assertOk()
            ->assertViewHas('selesaikanHotel', fn (Collection $daftar): bool => $daftar->pluck('id')->all() === [$masuk->id]);
    }

    public function test_kartu_selesaikan_hotel_menampilkan_kode_nama_tamu_dan_sisa_tahap(): void
    {
        $pesanan = $this->pesanan(['nama_lengkap' => 'Tamu Setengah Jalan', 'permintaan_hotel_dikirim_at' => now()]);

        $kartu = $this->kartuSelesaikanHotel();

        $this->assertStringContainsString('Selesaikan Hotel', $kartu);
        $this->assertStringContainsString($pesanan->kode, $kartu);
        $this->assertStringContainsString('Tamu Setengah Jalan', $kartu);
        $this->assertStringContainsString('Kurang 3 dari 4 tahap', $kartu);
        $this->assertStringContainsString('Berikutnya: 2. Hotel setuju', $kartu);
        $this->assertStringContainsString('href="'.route('admin.pesanan.detail', $pesanan).'#reservasi-hotel"', $kartu);
    }

    public function test_pesanan_tanpa_hotel_masih_kurang_semua_tahap_dan_diarahkan_memilih_hotel(): void
    {
        $this->pesanan(['nama_lengkap' => 'Tamu Tanpa Hotel'], denganHotel: false);

        $kartu = $this->kartuSelesaikanHotel();

        $this->assertStringContainsString('Tamu Tanpa Hotel', $kartu);
        $this->assertStringContainsString('Kurang 4 dari 4 tahap', $kartu);
        $this->assertStringContainsString('Berikutnya: 1. Pesan kamar ke hotel', $kartu);
    }

    public function test_menandai_tahap_di_detail_pesanan_langsung_mengurangi_sisa_tahap_di_dashboard(): void
    {
        $pesanan = $this->pesanan();
        $admin = User::factory()->create();

        $this->actingAs($admin)->patch(route('admin.pesanan.hotel.langkah.tandai', [$pesanan, 'permintaan']))->assertSessionHas('sukses');

        $this->actingAs($admin)->get(route('admin.pesanan.detail', $pesanan))->assertOk()->assertSee('Masih 3 tahap lagi');
        $this->assertStringContainsString('Kurang 3 dari 4 tahap', $this->kartuSelesaikanHotel());
    }

    public function test_dashboard_menyatakan_semua_hotel_selesai_bila_tidak_ada_yang_perlu_diurus(): void
    {
        $this->selesaikanSemua($this->pesanan(['nama_lengkap' => 'Tamu Beres']));

        $kartu = $this->kartuSelesaikanHotel();

        $this->assertStringContainsString('Semua hotel sudah selesai diurus', $kartu);
        $this->assertStringNotContainsString('Tamu Beres', $kartu);
    }

    public function test_menu_hotel_tidak_lagi_punya_urus_hotel_dan_alamat_lamanya_diarahkan_ke_halaman_selesaikan_hotel(): void
    {
        $this->pesanan(['nama_lengkap' => 'Tamu Belum Diurus']);
        $admin = User::factory()->create();

        $this->actingAs($admin)->get(route('admin.hotel.indeks'))
            ->assertOk()
            ->assertDontSee('Urus Hotel')
            ->assertDontSee('Hotel yang harus diurus');
        $this->actingAs($admin)->get(route('admin.hotel.urus'))->assertRedirect(route('admin.selesaikan-hotel.indeks'));
    }

    public function test_dashboard_menampilkan_lima_teratas_dan_lihat_semua_memuat_semuanya_per_kelompok(): void
    {
        $belum = collect(range(1, 4))->map(fn (int $nomor): Pesanan => $this->pesanan(['nama_lengkap' => "Tamu Belum {$nomor}", 'tanggal_trip' => today()->addDays(10 + $nomor)->toDateString()], denganHotel: false));
        $sedang = collect(range(1, 3))->map(fn (int $nomor): Pesanan => $this->pesanan(['nama_lengkap' => "Tamu Sedang {$nomor}", 'tanggal_trip' => today()->addDays(20 + $nomor)->toDateString(), 'permintaan_hotel_dikirim_at' => now()]));
        $admin = User::factory()->create();

        $kartu = $this->kartuSelesaikanHotel();
        $this->assertStringContainsString('Lihat semua (7)', $kartu);
        $this->assertStringContainsString('2 reservasi lainnya belum tuntas', $kartu);
        $this->assertStringContainsString('Tamu Belum 1', $kartu);
        $this->assertStringNotContainsString('Tamu Sedang 3', $kartu);

        $this->actingAs($admin)->get(route('admin.selesaikan-hotel.indeks'))
            ->assertOk()
            ->assertSeeInOrder(['Tamu Belum 1', 'Tamu Belum 4', 'Tamu Sedang 1', 'Tamu Sedang 3'])
            ->assertSee('Belum pilih hotel');

        $this->actingAs($admin)->get(route('admin.selesaikan-hotel.indeks', ['kelompok' => 'belum']))
            ->assertOk()
            ->assertSee('Tamu Belum 2')
            ->assertDontSee('Tamu Sedang 1');

        $this->actingAs($admin)->get(route('admin.selesaikan-hotel.indeks', ['kelompok' => 'sedang', 'cari' => $sedang[1]->kode]))
            ->assertOk()
            ->assertSee('Tamu Sedang 2')
            ->assertDontSee('Tamu Sedang 1')
            ->assertDontSee($belum[0]->nama_lengkap);

        $this->actingAs($admin)->get(route('admin.selesaikan-hotel.indeks', ['kelompok' => 'tidak-ada']))->assertSessionHasErrors('kelompok');
    }

    public function test_trip_beberapa_hari_yang_sudah_dimulai_tertulis_sedang_berlangsung_bukan_sisa_minus(): void
    {
        $this->pesanan(['nama_lengkap' => 'Tamu Sedang Jalan', 'tanggal_trip' => today()->subDay()->toDateString(), 'tanggal_selesai' => today()->addDay()->toDateString()]);

        $kartu = $this->kartuSelesaikanHotel();

        $this->assertStringContainsString('sedang berlangsung', $kartu);
        $this->assertStringNotContainsString('sisa -', $kartu);
    }

    public function test_detail_pesanan_mengikuti_progres_hotel_dan_berubah_hijau_saat_semua_selesai(): void
    {
        $pesanan = $this->pesanan();
        $admin = User::factory()->create();

        $this->actingAs($admin)->get(route('admin.pesanan.detail', $pesanan))
            ->assertOk()
            ->assertSee('Masih 4 tahap lagi')
            ->assertDontSee('Sudah diurus');

        $this->selesaikanSemua($pesanan);

        $this->actingAs($admin)->get(route('admin.pesanan.detail', $pesanan))
            ->assertOk()
            ->assertSee('Sudah diurus')
            ->assertDontSee('tahap lagi');
    }

    public function test_completed_ditolak_sampai_semua_langkah_reservasi_hotel_selesai(): void
    {
        $guide = PersonelOperasional::query()->create([...DataGuide::sim(), 'nama' => 'Guide Hotel', 'peran' => 'guide', 'email' => 'guide.hotel@example.org', 'status_operasional' => 'siap', 'aktif' => true]);
        $pesanan = $this->pesanan([
            'tanggal_trip' => today()->subDays(3)->toDateString(), 'tanggal_selesai' => today()->subDay()->toDateString(),
            'guide_personel_id' => $guide->id, 'nama_guide' => $guide->nama, 'status_konfirmasi_guide' => 'diterima', 'guide_trip_selesai_at' => now(),
        ]);
        $admin = User::factory()->create(['jabatan' => 'admin']);

        $this->actingAs($admin)->get(route('admin.pesanan.detail', $pesanan))
            ->assertOk()
            ->assertDontSee('Tandai Trip Selesai')
            ->assertSee('Reservasi hotel belum tuntas.');
        $this->actingAs($admin)->patch(route('admin.pesanan.selesaikan', $pesanan))
            ->assertSessionHasErrors(['status' => 'Pesanan hanya bisa ditandai Trip Selesai setelah semua langkah reservasi hotel selesai. Lengkapi kartu Reservasi Hotel terlebih dahulu.']);
        $this->assertSame(BookingStatus::Payment, $pesanan->fresh()->status);

        $this->selesaikanSemua($pesanan);

        $this->actingAs($admin)->get(route('admin.pesanan.detail', $pesanan))->assertSee('Tandai Trip Selesai');
        $this->actingAs($admin)->patch(route('admin.pesanan.selesaikan', $pesanan))->assertSessionHasNoErrors();
        $this->assertSame(BookingStatus::Completed, $pesanan->fresh()->status);
    }

    public function test_completed_tidak_menunggu_hotel_bila_tamu_mengurus_penginapan_sendiri(): void
    {
        $pesanan = $this->pesanan(['status_penginapan' => 'sudah'], denganHotel: false);

        $this->assertFalse(app(AlurReservasiHotel::class)->menghambatCompleted($pesanan));
    }
}
