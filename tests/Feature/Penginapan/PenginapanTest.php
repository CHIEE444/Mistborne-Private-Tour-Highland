<?php

namespace Tests\Feature\Penginapan;

use App\Enums\StatusPenginapan;
use App\Layanan\PesanWhatsAppLayanan;
use App\Models\JadwalKeberangkatan;
use App\Models\Pengaturan;
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
 * Alur penginapan: MISTBORNE tidak menyediakan penginapan; tamu yang sudah punya mengisi namanya,
 * tamu yang belum punya mendapat rekomendasi dari admin.
 */
class PenginapanTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $timpa
     * @return array<string, mixed>
     */
    private function dataBooking(Perjalanan $journey, array $timpa = []): array
    {
        return [
            'token_booking' => (string) Str::uuid(),
            'destination' => $journey->nama,
            'pax' => 2,
            'nama_lengkap' => 'Tamu Penginapan',
            'whatsapp' => '081234567890',
            'tanggal_trip' => Carbon::today()->addDays(10)->toDateString(),
            'lokasi_pickup' => 'Stasiun Malang',
            'kendaraan_pilihan' => 'Jeep klasik (petualangan dataran tinggi) — Toyota Land Cruiser FJ40',
            'status_penginapan' => 'sudah',
            'nama_hotel' => 'Hotel Tugu Malang',
            'alamat_hotel' => 'Jl. Uji Alamat No. 1, Kota Uji',
            ...$timpa,
        ];
    }

    /** Journey berharga dengan tanggal H+10 dan H+12 terbuka di Tanggal Tersedia (tanggal yang dipakai formulir di tes ini). */
    private function journeyBooking(): Perjalanan
    {
        $journey = Perjalanan::query()->where('template_pesanan_kustom', false)->whereNotNull('harga')->where('tone', '<>', 'custom')->orderBy('urutan')->firstOrFail();
        $this->bukaTanggal($journey, Carbon::today()->addDays(10));
        $this->bukaTanggal($journey, Carbon::today()->addDays(12));

        return $journey;
    }

    // ── Booking website ──────────────────────────────────────────────

    public function test_booking_tanpa_data_hotel_diterima_dan_selalu_disimpan_sebagai_all_inclusive(): void
    {
        Notification::fake();
        $user = User::factory()->create(['jabatan' => 'tamu']);
        $journey = $this->journeyBooking();

        $respons = $this->actingAs($user)->post(route('booking.simpan'), $this->dataBooking($journey, [
            'status_penginapan' => 'belum',
            'nama_hotel' => 'Hotel Yang Seharusnya Diabaikan',
            'alamat_hotel' => 'Alamat Diabaikan',
        ]));

        $respons->assertSessionHasNoErrors();
        $permintaan = PermintaanPesanan::query()->where('nama_lengkap', 'Tamu Penginapan')->firstOrFail();
        $this->assertSame(StatusPenginapan::Diurus, $permintaan->status_penginapan);
        $this->assertNull($permintaan->nama_hotel);
        $this->assertNull($permintaan->alamat_hotel);

        parse_str((string) parse_url((string) $respons->headers->get('Location'), PHP_URL_QUERY), $parameter);
        $this->assertStringContainsString('Penginapan: All-inclusive (penginapan dan tiket masuk diurus MISTBORNE)', $parameter['text'] ?? '');
    }

    public function test_halaman_booking_menampilkan_all_inclusive_tanpa_pilihan_atau_isian_hotel(): void
    {
        $user = User::factory()->create(['jabatan' => 'tamu']);

        $this->actingAs($user)->get(route('booking.buat', ['destination' => $this->journeyBooking()->nama]))
            ->assertOk()
            ->assertSee('All-inclusive: tamu cukup membayar sekali')
            ->assertSee('Tidak ada biaya mendadak di lokasi')
            ->assertDontSee('name="status_penginapan"', false)
            ->assertDontSee('name="nama_hotel"', false)
            ->assertDontSee('Penginapan tidak termasuk dalam harga');
    }

    // ── Form pesanan lewat tautan WhatsApp ───────────────────────────

    public function test_form_pesanan_tautan_menyimpan_all_inclusive_tanpa_data_hotel(): void
    {
        Notification::fake();
        $journey = $this->journeyBooking();
        $permintaan = PermintaanPesanan::query()->create([
            'token' => Str::random(64),
            'whatsapp' => '081234567890',
            'status' => 'menunggu_tamu',
            'token_berlaku_sampai' => now()->addDays(7),
        ]);

        $this->post(route('form-pesanan.simpan', $permintaan->token), [
            'nama_lengkap' => 'Tamu Form Tautan',
            'whatsapp' => '081234567890',
            'perjalanan_id' => $journey->id,
            'tanggal_trip' => Carbon::today()->addDays(12)->toDateString(),
            'jumlah_wisatawan' => 2,
            'lokasi_pickup' => 'Bandara Juanda',
            'kendaraan_pilihan' => 'Jeep klasik (petualangan dataran tinggi) — Toyota Land Cruiser FJ40',
            'status_penginapan' => 'sudah',
            'nama_hotel' => 'Diabaikan',
        ])->assertSessionHasNoErrors();

        $permintaan->refresh();
        $this->assertSame(StatusPenginapan::Diurus, $permintaan->status_penginapan);
        $this->assertNull($permintaan->nama_hotel);
    }

    // ── Rancang Trip Kustom ──────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $timpa
     * @return array<string, mixed>
     */
    private function dataKustom(array $timpa = []): array
    {
        return [
            'nama' => 'Dewi Penginapan',
            'whatsapp' => '081234567890',
            'destinasi_diinginkan' => 'Rinjani',
            'tanggal_fleksibel' => 1,
            'durasi_hari' => 4,
            'lokasi_penjemputan' => 'Bandara Lombok',
            'jumlah_orang' => 3,
            'status_penginapan' => 'sudah',
            'nama_hotel' => 'Hotel Sembalun',
            ...$timpa,
        ];
    }

    public function test_trip_kustom_menyimpan_penginapan_dan_pesan_wa_memuatnya(): void
    {
        $this->post(route('rancang-trip-kustom.simpan'), $this->dataKustom())->assertSessionHasNoErrors();

        $kustom = PesananKustom::query()->where('nama', 'Dewi Penginapan')->firstOrFail();
        $this->assertSame(StatusPenginapan::Sudah, $kustom->status_penginapan);
        $this->assertSame('Hotel Sembalun', $kustom->nama_hotel);
        $this->assertStringContainsString('Penginapan: Hotel Sembalun', urldecode((string) session('tautanWa')));
    }

    public function test_trip_kustom_belum_punya_penginapan_meminta_rekomendasi(): void
    {
        $this->post(route('rancang-trip-kustom.simpan'), $this->dataKustom(['status_penginapan' => 'belum', 'nama_hotel' => 'Abaikan']))
            ->assertSessionHasNoErrors();

        $kustom = PesananKustom::query()->where('nama', 'Dewi Penginapan')->firstOrFail();
        $this->assertNull($kustom->nama_hotel);
        $this->assertStringContainsString('Penginapan: Belum punya, mohon dibantu rekomendasi', urldecode((string) session('tautanWa')));
    }

    public function test_trip_kustom_menolak_status_kosong_atau_hotel_kosong_saat_sudah_punya(): void
    {
        $this->post(route('rancang-trip-kustom.simpan'), $this->dataKustom(['status_penginapan' => null]))
            ->assertSessionHasErrors('status_penginapan');
        $this->post(route('rancang-trip-kustom.simpan'), $this->dataKustom(['nama_hotel' => '']))
            ->assertSessionHasErrors('nama_hotel');

        $this->assertSame(0, PesananKustom::count());
    }

    // ── Admin: pesanan ───────────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $timpa
     * @return array<string, mixed>
     */
    private function dataPesananAdmin(Perjalanan $journey, JadwalKeberangkatan $jadwal, array $timpa = []): array
    {
        return [
            'perjalanan_id' => $journey->id,
            'tanggal_trip' => $jadwal->tanggal->toDateString(),
            'nama_lengkap' => 'Tamu Admin Penginapan',
            'whatsapp' => '081234567890',
            'jumlah_wisatawan' => 2,
            'lokasi_pickup' => 'Stasiun Gambir',
            'status_penginapan' => 'sudah',
            'nama_hotel' => 'Hotel Admin',
            ...$timpa,
        ];
    }

    public function test_admin_membuat_pesanan_dengan_status_penginapan(): void
    {
        $admin = User::factory()->create();
        $journey = Perjalanan::factory()->create(['harga' => 2_000_000]);
        $jadwal = JadwalKeberangkatan::factory()->create(['perjalanan_id' => $journey->id]);

        $this->actingAs($admin)->post(route('admin.pesanan.simpan'), $this->dataPesananAdmin($journey, $jadwal, ['status_penginapan' => 'belum', 'nama_hotel' => null]))
            ->assertSessionHasNoErrors();

        $pesanan = Pesanan::query()->where('nama_lengkap', 'Tamu Admin Penginapan')->firstOrFail();
        $this->assertSame(StatusPenginapan::Belum, $pesanan->status_penginapan);
        $this->assertNull($pesanan->nama_hotel);
    }

    public function test_admin_wajib_memilih_status_dan_mengisi_hotel_bila_sudah_punya(): void
    {
        $admin = User::factory()->create();
        $journey = Perjalanan::factory()->create(['harga' => 2_000_000]);
        $jadwal = JadwalKeberangkatan::factory()->create(['perjalanan_id' => $journey->id]);

        $this->actingAs($admin)->post(route('admin.pesanan.simpan'), $this->dataPesananAdmin($journey, $jadwal, ['status_penginapan' => null]))
            ->assertSessionHasErrors('status_penginapan');
        $this->actingAs($admin)->post(route('admin.pesanan.simpan'), $this->dataPesananAdmin($journey, $jadwal, ['nama_hotel' => '']))
            ->assertSessionHasErrors('nama_hotel');
    }

    public function test_admin_memperbarui_penginapan_dari_belum_menjadi_sudah_setelah_tamu_memberi_kabar(): void
    {
        $admin = User::factory()->create();
        $pesanan = Pesanan::factory()->create(['status_penginapan' => 'belum', 'nama_hotel' => null, 'tanggal_trip' => now()->addDays(20)->toDateString()]);

        $this->actingAs($admin)->put(route('admin.pesanan.perbarui', $pesanan), [
            'konfirmasi_tamu' => 1,
            'nama_lengkap' => $pesanan->nama_lengkap,
            'whatsapp' => '0'.substr($pesanan->whatsapp, 2),
            'status_penginapan' => 'sudah',
            'nama_hotel' => 'Hotel Baru Dipesan',
            'lokasi_pickup' => $pesanan->lokasi_pickup,
            'jumlah_wisatawan' => $pesanan->jumlah_wisatawan,
        ])->assertSessionHasNoErrors();

        $pesanan->refresh();
        $this->assertSame(StatusPenginapan::Sudah, $pesanan->status_penginapan);
        $this->assertSame('Hotel Baru Dipesan', $pesanan->nama_hotel);
    }

    public function test_detail_pesanan_menampilkan_lencana_butuh_rekomendasi_dan_tab_pesan_rekomendasi(): void
    {
        $admin = User::factory()->create();
        Pengaturan::current()->update(['rekomendasi_penginapan' => "• Hotel Bromo Permai (± Rp 450.000)\n• Homestay Ngadisari"]);
        $pesanan = Pesanan::factory()->create(['status_penginapan' => 'belum', 'nama_hotel' => null]);

        $this->actingAs($admin)->get(route('admin.pesanan.detail', $pesanan))
            ->assertOk()
            ->assertSee('Butuh rekomendasi')
            ->assertSee('Rekomendasi Penginapan')
            ->assertSee('Hotel Bromo Permai');
    }

    public function test_tab_rekomendasi_tidak_tampil_bila_tamu_sudah_punya_penginapan(): void
    {
        $admin = User::factory()->create();
        $pesanan = Pesanan::factory()->create(['status_penginapan' => 'sudah', 'nama_hotel' => 'Hotel Ada']);

        $this->actingAs($admin)->get(route('admin.pesanan.detail', $pesanan))
            ->assertOk()
            ->assertDontSee('Rekomendasi Penginapan')
            ->assertSee('Hotel Ada');
    }

    // ── Pesan WhatsApp ───────────────────────────────────────────────

    public function test_pesan_rekomendasi_memakai_daftar_dari_pengaturan_dan_menjelaskan_aturan_main(): void
    {
        Pengaturan::current()->update(['rekomendasi_penginapan' => '• Hotel Contoh Satu']);
        $pesanan = Pesanan::factory()->create(['nama_lengkap' => 'Sari', 'status_penginapan' => 'belum', 'nama_hotel' => null]);

        $teks = app(PesanWhatsAppLayanan::class)->rekomendasiPenginapan($pesanan);

        $this->assertStringContainsString('Halo Kak Sari', $teks);
        $this->assertStringContainsString('• Hotel Contoh Satu', $teks);
        $this->assertStringContainsString('dipesan dan dibayar langsung oleh Kakak', $teks);
        $this->assertStringContainsString('kabari nama penginapannya', $teks);
    }

    public function test_pesan_rekomendasi_tetap_sopan_saat_daftar_belum_diisi(): void
    {
        Pengaturan::current()->update(['rekomendasi_penginapan' => null]);
        $pesanan = Pesanan::factory()->create(['status_penginapan' => 'belum', 'nama_hotel' => null]);

        $teks = app(PesanWhatsAppLayanan::class)->rekomendasiPenginapan($pesanan);

        $this->assertStringContainsString('akan kami kirimkan', $teks);
        $this->assertStringNotContainsString('Berikut beberapa penginapan', $teks);
    }

    public function test_pesan_konfirmasi_dan_pengingat_menangani_penginapan_yang_belum_ada(): void
    {
        $pesanan = Pesanan::factory()->create(['status_penginapan' => 'belum', 'nama_hotel' => null]);
        $layanan = app(PesanWhatsAppLayanan::class);

        $this->assertStringContainsString('Penginapan: Belum ada (kami bantu rekomendasi)', $layanan->pesananDisetujui($pesanan));
        $this->assertStringContainsString('Mohon kabari nama penginapan', $layanan->pengingatH2($pesanan));
    }

    // ── Admin: permintaan form, kustom, pengaturan, dashboard ────────

    public function test_permintaan_dengan_penginapan_belum_menampilkan_tombol_kirim_rekomendasi(): void
    {
        $admin = User::factory()->create();
        $journey = $this->journeyBooking();
        PermintaanPesanan::query()->create([
            'token' => Str::random(64),
            'whatsapp' => '081234567890',
            'status' => 'menunggu_tinjauan',
            'perjalanan_id' => $journey->id,
            'nama_lengkap' => 'Tamu Menunggu',
            'tanggal_trip' => now()->addDays(15)->toDateString(),
            'jumlah_wisatawan' => 2,
            'lokasi_pickup' => 'Stasiun Malang',
            'status_penginapan' => 'belum',
            'token_berlaku_sampai' => now()->addDays(7),
        ]);

        $this->actingAs($admin)->get(route('admin.permintaan-pesanan.indeks'))
            ->assertOk()
            ->assertSee('Kirim Rekomendasi Penginapan')
            ->assertSee('Butuh rekomendasi');
    }

    public function test_detail_pesanan_kustom_menampilkan_tombol_dan_lencana_rekomendasi_penginapan(): void
    {
        $kustom = PesananKustom::factory()->create(['status_penginapan' => 'belum', 'nama_hotel' => null]);
        $admin = User::factory()->create();

        $this->actingAs($admin)->get(route('admin.pesanan-kustom.detail', $kustom))
            ->assertOk()
            ->assertSeeInOrder(['Pesan Awal ke Tamu', 'Rekomendasi Penginapan'])
            ->assertSee('Butuh rekomendasi');
    }

    public function test_pengaturan_menyimpan_daftar_rekomendasi_dan_memvalidasinya(): void
    {
        $admin = User::factory()->create();
        $dasar = [
            'nomor_whatsapp_admin' => '6281234567890',
            'persen_charge_ganti_jadwal' => 20,
            'batas_bayar_hari' => 3,
            'kuota_default' => 5,
            'batas_ulasan_hari' => 14,
            'batas_video_mb' => 100,
        ];

        $this->actingAs($admin)->put(route('admin.pengaturan.perbarui'), [...$dasar, 'rekomendasi_penginapan' => '• Hotel A (± Rp 300.000)'])
            ->assertSessionHasNoErrors();
        $this->assertSame('• Hotel A (± Rp 300.000)', Pengaturan::current()->rekomendasi_penginapan);

        $this->actingAs($admin)->put(route('admin.pengaturan.perbarui'), [...$dasar, 'rekomendasi_penginapan' => str_repeat('x', 3001)])
            ->assertSessionHasErrors('rekomendasi_penginapan');
        $this->actingAs($admin)->put(route('admin.pengaturan.perbarui'), [...$dasar, 'rekomendasi_penginapan' => '<script>'])
            ->assertSessionHasErrors('rekomendasi_penginapan');
    }

    public function test_dashboard_menghitung_dan_pesanan_menyaring_tamu_yang_butuh_rekomendasi(): void
    {
        $admin = User::factory()->create();
        Pesanan::factory()->create(['nama_lengkap' => 'Tamu Perlu Rekomendasi', 'status_penginapan' => 'belum', 'nama_hotel' => null, 'tanggal_trip' => now()->addDays(15)->toDateString()]);
        Pesanan::factory()->create(['nama_lengkap' => 'Tamu Sudah Ada Hotel', 'status_penginapan' => 'sudah', 'tanggal_trip' => now()->addDays(15)->toDateString()]);
        Pesanan::factory()->create(['nama_lengkap' => 'Tamu Sudah Memberi Kabar', 'status_penginapan' => 'belum', 'nama_hotel' => 'Hotel Dipilih', 'tanggal_trip' => now()->addDays(15)->toDateString()]);

        $this->assertSame(1, Pesanan::query()->butuhRekomendasiPenginapan()->count());

        $this->actingAs($admin)->get(route('admin.dashboard.indeks'))->assertOk()->assertSee('Tamu butuh rekomendasi penginapan');

        $this->actingAs($admin)->get(route('admin.pesanan.indeks', ['penginapan' => 'belum']))
            ->assertOk()
            ->assertSee('Tamu Perlu Rekomendasi')
            ->assertDontSee('Tamu Sudah Ada Hotel')
            ->assertDontSee('Tamu Sudah Memberi Kabar');
    }

    // ── Publik: info journey & form kontak ───────────────────────────

    public function test_halaman_detail_journey_menjelaskan_penginapan_sudah_termasuk(): void
    {
        $journey = Perjalanan::factory()->create();

        $this->get(route('journeys.show', $journey->slug))
            ->assertOk()
            ->assertSee('Penginapan')
            ->assertSee('Hotel bintang 4')
            ->assertSee('Tiket masuk dan penginapan sudah termasuk');
    }

    public function test_form_kontak_beranda_tidak_lagi_memilih_penginapan(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('name="status_penginapan"', $html);
        $this->assertStringNotContainsString('Saya sudah punya penginapan', $html);
        $this->assertStringContainsString('All-inclusive (penginapan dan tiket masuk diurus MISTBORNE)', $html);
    }

    public function test_komponen_penginapan_tanpa_data_menampilkan_strip(): void
    {
        $this->assertSame('—', trim(strip_tags(view('components.admin.penginapan', ['status' => null, 'nama' => null])->render())));
    }

    public function test_pesan_rekomendasi_memakai_daftar_hotel_milik_journey_pesanan(): void
    {
        Pengaturan::current()->update(['rekomendasi_penginapan' => '• Hotel Umum Global']);
        $journey = Perjalanan::factory()->create(['tempat' => 'Dieng, Jawa Tengah', 'rekomendasi_penginapan' => '• Zaada Villa Dieng — Kejajar']);
        $pesanan = Pesanan::factory()->create(['perjalanan_id' => $journey->id, 'status_penginapan' => 'belum', 'nama_hotel' => null]);

        $teks = app(PesanWhatsAppLayanan::class)->rekomendasiPenginapan($pesanan);

        $this->assertStringContainsString('penginapan bintang 4 yang kami rekomendasikan di sekitar Dieng, Jawa Tengah', $teks);
        $this->assertStringContainsString('• Zaada Villa Dieng — Kejajar', $teks);
        $this->assertStringNotContainsString('Hotel Umum Global', $teks);
        $this->assertStringContainsString('Kelas bintang mengacu pada situs pemesanan', $teks);
    }

    public function test_pesan_rekomendasi_jatuh_ke_daftar_umum_bila_journey_tidak_punya_daftar(): void
    {
        Pengaturan::current()->update(['rekomendasi_penginapan' => '• Hotel Umum Global']);
        $journey = Perjalanan::factory()->create(['rekomendasi_penginapan' => null]);
        $pesanan = Pesanan::factory()->create(['perjalanan_id' => $journey->id, 'status_penginapan' => 'belum', 'nama_hotel' => null]);

        $teks = app(PesanWhatsAppLayanan::class)->rekomendasiPenginapan($pesanan);

        $this->assertStringContainsString('• Hotel Umum Global', $teks);
        $this->assertStringNotContainsString('bintang 4', $teks);
    }

    public function test_admin_menyimpan_rekomendasi_penginapan_di_form_journey(): void
    {
        $admin = User::factory()->create();
        $journey = Perjalanan::factory()->create();

        $this->actingAs($admin)->get(route('admin.perjalanan.ubah', $journey))->assertOk()->assertSee('Hotel Cadangan Terdekat')->assertSee('name="rekomendasi_penginapan"', false);

        $journey->update(['rekomendasi_penginapan' => '• Hotel Baru']);
        $this->assertSame('• Hotel Baru', $journey->fresh()->rekomendasi_penginapan);
    }

    public function test_pesan_rekomendasi_menyebut_perkiraan_kamar_sesuai_jumlah_tamu(): void
    {
        $layanan = app(PesanWhatsAppLayanan::class);

        $enam = Pesanan::factory()->create(['jumlah_wisatawan' => 6, 'status_penginapan' => 'belum', 'nama_hotel' => null]);
        $teksEnam = $layanan->rekomendasiPenginapan($enam);
        $this->assertStringContainsString('Untuk 6 orang, perkiraan kebutuhan sekitar 2 kamar (4 orang per kamar).', $teksEnam);
        $this->assertStringContainsString('kamar keluarga untuk 3–4 orang', $teksEnam);

        $lima = Pesanan::factory()->create(['jumlah_wisatawan' => 5, 'status_penginapan' => 'belum', 'nama_hotel' => null]);
        $this->assertStringContainsString('sekitar 2 kamar', $layanan->rekomendasiPenginapan($lima));

        $dua = Pesanan::factory()->create(['jumlah_wisatawan' => 2, 'status_penginapan' => 'belum', 'nama_hotel' => null]);
        $teksDua = $layanan->rekomendasiPenginapan($dua);
        $this->assertStringContainsString('sekitar 1 kamar', $teksDua);
        $this->assertStringNotContainsString('kamar keluarga', $teksDua);

        $satu = Pesanan::factory()->create(['jumlah_wisatawan' => 1, 'status_penginapan' => 'belum', 'nama_hotel' => null]);
        $this->assertStringContainsString('Untuk 1 orang, Kakak cukup memesan 1 kamar.', $layanan->rekomendasiPenginapan($satu));
    }

    public function test_pesan_rekomendasi_trip_kustom_memakai_jumlah_orang_kustom(): void
    {
        $kustom = PesananKustom::factory()->create(['jumlah_orang' => 6, 'status_penginapan' => 'belum', 'nama_hotel' => null]);

        $this->assertStringContainsString('Untuk 6 orang, perkiraan kebutuhan sekitar 2 kamar', app(PesanWhatsAppLayanan::class)->rekomendasiPenginapan($kustom));
    }
}
