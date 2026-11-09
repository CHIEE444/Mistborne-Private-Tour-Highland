<?php

namespace Tests\Feature\Admin\Pesanan;

use App\Models\Hotel;
use App\Models\Pengaturan;
use App\Models\PersonelOperasional;
use App\Models\Pesanan;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\DataGuide;
use Tests\TestCase;

class DetailPesananTest extends TestCase
{
    public function test_tamu_tidak_bisa_mengakses_daftar_pesanan(): void
    {
        $this->get(route('admin.pesanan.indeks'))->assertRedirect(route('admin.autentikasi.masuk'));
    }

    public function test_admin_bisa_melihat_daftar_pesanan(): void
    {
        $user = User::factory()->create();
        Pesanan::factory()->create(['nama_lengkap' => 'Budi Santoso']);

        $this->actingAs($user)
            ->get(route('admin.pesanan.indeks'))
            ->assertOk()
            ->assertSee('Budi Santoso');
    }

    public function test_aksi_konfirmasi_menjelaskan_status_tetap_dihubungi(): void
    {
        $user = User::factory()->create();
        $pesanan = Pesanan::factory()->create([
            'status' => 'dihubungi',
            'harga' => 1_000_000,
            'nominal_dibayar' => 0,
        ]);

        $this->actingAs($user)
            ->get(route('admin.pesanan.detail', $pesanan))
            ->assertOk()
            ->assertSee('Konfirmasi Pesanan')
            ->assertSee('status tetap Dihubungi sampai uang muka minimal 30% tercatat.')
            ->assertSee('Konfirmasi Pesanan');
    }

    public function test_pesanan_lunas_menampilkan_aksi_payment_dan_menyembunyikan_konfirmasi_bayar(): void
    {
        $user = User::factory()->create();
        $pesanan = Pesanan::factory()->create([
            'status' => 'dihubungi',
            'harga' => 1_000_000,
            'nominal_dibayar' => 1_000_000,
        ]);

        $this->actingAs($user)
            ->get(route('admin.pesanan.detail', $pesanan))
            ->assertOk()
            ->assertSee('Tandai Sudah Bayar')
            ->assertDontSee('Mulai tenggat pembayaran; status tetap Dihubungi');
    }

    public function test_admin_bisa_mencari_pesanan_lewat_kode(): void
    {
        $user = User::factory()->create();
        $pesanan = Pesanan::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.pesanan.indeks', ['cari' => $pesanan->kode]))
            ->assertOk()
            ->assertSee($pesanan->kode);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function statusProvider(): array
    {
        return [
            'dihubungi' => ['dihubungi'],
            'payment' => ['payment'],
            'hold_schedule' => ['hold_schedule'],
            'completed' => ['completed'],
            'dibatalkan' => ['dibatalkan'],
        ];
    }

    #[DataProvider('statusProvider')]
    public function test_halaman_detail_render_untuk_setiap_status(string $status): void
    {
        $user = User::factory()->create();
        $pesanan = Pesanan::factory()->create(['status' => $status]);

        $this->actingAs($user)
            ->get(route('admin.pesanan.detail', $pesanan))
            ->assertOk()
            ->assertSee($pesanan->kode);
    }

    public function test_aksi_cepat_menjelaskan_pembatalan_oleh_mistborne(): void
    {
        $user = User::factory()->create();
        $pesanan = Pesanan::factory()->dibatalkan()->create([
            'pihak_pembatal' => 'mistborne',
            'alasan_batal' => 'Cuaca ekstrem',
        ]);

        $this->actingAs($user)
            ->get(route('admin.pesanan.detail', $pesanan))
            ->assertOk()
            ->assertSee('Dibatalkan oleh MISTBORNE')
            ->assertSee('Cuaca ekstrem')
            ->assertDontSee('Hanya bisa dibatalkan MISTBORNE');
    }

    public function test_aksi_cepat_membedakan_pembatalan_atas_permintaan_tamu(): void
    {
        $user = User::factory()->create();
        $pesanan = Pesanan::factory()->dibatalkan()->create([
            'pihak_pembatal' => 'pelanggan',
            'alasan_batal' => 'Perubahan rencana tamu',
        ]);

        $this->actingAs($user)
            ->get(route('admin.pesanan.detail', $pesanan))
            ->assertOk()
            ->assertSee('Dibatalkan atas permintaan tamu')
            ->assertSee('Perubahan rencana tamu');
    }

    public function test_persen_charge_ganti_jadwal_di_modal_mengikuti_pengaturan(): void
    {
        $user = User::factory()->create();
        Pengaturan::current()->update(['persen_charge_ganti_jadwal' => 25]);
        $pesanan = Pesanan::factory()->payment()->create([
            'tanggal_trip' => now()->addDays(30),
            'harga' => 2_000_000,
        ]);

        $this->actingAs($user)
            ->get(route('admin.pesanan.detail', $pesanan))
            ->assertOk()
            ->assertSee('25% dari harga');
    }

    public function test_batas_ulasan_hari_di_kartu_tautan_rating_mengikuti_pengaturan(): void
    {
        $user = User::factory()->create();
        Pengaturan::current()->update(['batas_ulasan_hari' => 14]);
        $pesanan = Pesanan::factory()->completed()->create();

        $this->actingAs($user)
            ->get(route('admin.pesanan.detail', $pesanan))
            ->assertOk()
            ->assertSee('14 hari setelah selesai');
    }

    public function test_admin_bisa_membuka_form_ubah_pesanan(): void
    {
        $user = User::factory()->create();
        $pesanan = Pesanan::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.pesanan.ubah', $pesanan))
            ->assertOk();
    }

    public function test_admin_bisa_memperbarui_data_tamu(): void
    {
        $user = User::factory()->create();
        $pesanan = Pesanan::factory()->create();

        $response = $this->actingAs($user)->put(route('admin.pesanan.perbarui', $pesanan), [
            'konfirmasi_tamu' => 1,
            'nama_lengkap' => 'Nama Baru',
            'whatsapp' => '0811111111111',
            'status_penginapan' => 'sudah',
            'nama_hotel' => 'Hotel Baru',
            'lokasi_pickup' => 'Lokasi Baru',
            'jumlah_wisatawan' => 3,
        ]);

        $response->assertRedirect(route('admin.pesanan.detail', $pesanan));
        $this->assertSame('Nama Baru', $pesanan->fresh()->nama_lengkap);
    }

    public function test_aksi_cepat_menampilkan_atur_jadwal_hotel_setelah_guide_ditugaskan_tanpa_tautan_ganda(): void
    {
        $admin = User::factory()->create();
        $guide = PersonelOperasional::query()->create([...DataGuide::sim(), 'nama' => 'Guide Aksi', 'peran' => 'guide', 'status_operasional' => 'siap', 'aktif' => true]);
        $tanpaGuide = Pesanan::factory()->payment()->create(['harga' => 4_000_000, 'nominal_dibayar' => 4_000_000, 'guide_personel_id' => null]);
        $denganGuide = Pesanan::factory()->payment()->create(['harga' => 4_000_000, 'nominal_dibayar' => 4_000_000, 'status_penginapan' => 'diurus', 'guide_personel_id' => $guide->id, 'nama_guide' => $guide->nama, 'status_konfirmasi_guide' => 'diterima']);

        $this->actingAs($admin)->get(route('admin.pesanan.detail', $tanpaGuide))
            ->assertOk()
            ->assertSee('Tugaskan Guide')
            ->assertDontSee('Atur Jadwal Hotel');

        $this->actingAs($admin)->get(route('admin.pesanan.detail', $denganGuide))
            ->assertOk()
            ->assertSeeInOrder(['Atur Jadwal Hotel', 'Aksi lainnya', 'Lihat Operasional'])
            ->assertSee('href="#reservasi-hotel"', false)
            ->assertDontSee('Lihat Penugasan Operasional');
    }

    public function test_aksi_cepat_tetap_mengutamakan_lihat_operasional_bila_tamu_punya_penginapan_sendiri(): void
    {
        $guide = PersonelOperasional::query()->create([...DataGuide::sim(), 'nama' => 'Guide Aksi', 'peran' => 'guide', 'status_operasional' => 'siap', 'aktif' => true]);
        $pesanan = Pesanan::factory()->payment()->create(['harga' => 4_000_000, 'nominal_dibayar' => 4_000_000, 'status_penginapan' => 'sudah', 'guide_personel_id' => $guide->id, 'nama_guide' => $guide->nama, 'status_konfirmasi_guide' => 'diterima']);

        $this->actingAs(User::factory()->create())->get(route('admin.pesanan.detail', $pesanan))
            ->assertOk()
            ->assertSeeInOrder(['Lihat Operasional', 'Aksi lainnya'])
            ->assertDontSee('Atur Jadwal Hotel');
    }

    public function test_tombol_atur_jadwal_hotel_menampilkan_sisa_langkah_setelah_reservasi_disimpan(): void
    {
        $guide = PersonelOperasional::query()->create([...DataGuide::sim(), 'nama' => 'Guide Aksi', 'peran' => 'guide', 'status_operasional' => 'siap', 'aktif' => true]);
        $pesanan = Pesanan::factory()->payment()->create([
            'harga' => 4_000_000,
            'nominal_dibayar' => 4_000_000,
            'status_penginapan' => 'diurus',
            'hotel_id' => Hotel::factory()->create()->id,
            'status_reservasi_hotel' => 'diminta',
            'permintaan_hotel_dikirim_at' => now(),
            'guide_personel_id' => $guide->id,
            'nama_guide' => $guide->nama,
            'status_konfirmasi_guide' => 'diterima',
        ]);

        $this->actingAs(User::factory()->create())->get(route('admin.pesanan.detail', $pesanan))
            ->assertOk()
            ->assertSeeTextInOrder(['Atur Jadwal Hotel', '1/4', 'Kurang 3 tahap lagi, segera selesaikan.', 'Berikutnya: 2. Hotel setuju.']);
    }

    public function test_tombol_atur_jadwal_hotel_menyatakan_selesai_setelah_semua_langkah_hotel_tuntas(): void
    {
        $guide = PersonelOperasional::query()->create([...DataGuide::sim(), 'nama' => 'Guide Aksi', 'peran' => 'guide', 'status_operasional' => 'siap', 'aktif' => true]);
        $pesanan = Pesanan::factory()->payment()->create([
            'harga' => 4_000_000,
            'nominal_dibayar' => 4_000_000,
            'status_penginapan' => 'diurus',
            'hotel_id' => Hotel::factory()->create()->id,
            'status_reservasi_hotel' => 'terkonfirmasi',
            'permintaan_hotel_dikirim_at' => now()->subDays(5),
            'bayar_hotel_nominal' => 1_500_000,
            'bayar_hotel_tanggal' => now()->subDays(4),
            'bukti_bayar_hotel_dikirim_at' => now()->subDays(4),
            'bayar_hotel_dikonfirmasi_at' => now()->subDays(3),
            'tamu_dikabari_hotel_at' => now()->subDays(3),
            'konfirmasi_ulang_hotel_at' => now()->subDay(),
            'guide_personel_id' => $guide->id,
            'nama_guide' => $guide->nama,
            'status_konfirmasi_guide' => 'diterima',
        ]);

        $this->actingAs(User::factory()->create())->get(route('admin.pesanan.detail', $pesanan))
            ->assertOk()
            ->assertSeeTextInOrder(['Atur Jadwal Hotel', '4/4', 'Semua 4 tahap reservasi hotel sudah selesai.'])
            ->assertDontSeeText('segera selesaikan');
    }

    /** Pesanan all-inclusive yang Guide-nya sudah menerima tugas dan keempat tahap hotelnya selesai. */
    private function pesananHotelTuntas(array $timpa = []): Pesanan
    {
        $guide = PersonelOperasional::query()->create([...DataGuide::sim(), 'nama' => 'Guide Pengingat', 'peran' => 'guide', 'status_operasional' => 'siap', 'aktif' => true]);

        return Pesanan::factory()->payment()->create([
            'tanggal_trip' => today()->addDays(10),
            'harga' => 4_000_000,
            'nominal_dibayar' => 4_000_000,
            'status_penginapan' => 'diurus',
            'hotel_id' => Hotel::factory()->create(['whatsapp' => '081808606086'])->id,
            'status_reservasi_hotel' => 'terkonfirmasi',
            'kode_booking_hotel' => 'HTL-9',
            'permintaan_hotel_dikirim_at' => now()->subDays(5),
            'bayar_hotel_nominal' => 1_500_000,
            'bayar_hotel_tanggal' => now()->subDays(4),
            'bukti_bayar_hotel_dikirim_at' => now()->subDays(4),
            'bayar_hotel_dikonfirmasi_at' => now()->subDays(3),
            'tamu_dikabari_hotel_at' => now()->subDays(3),
            'guide_personel_id' => $guide->id,
            'nama_guide' => $guide->nama,
            'status_konfirmasi_guide' => 'diterima',
            ...$timpa,
        ]);
    }

    public function test_pengingat_tamu_muncul_di_aksi_cepat_setelah_semua_tahap_hotel_selesai(): void
    {
        $pesanan = $this->pesananHotelTuntas();

        $this->actingAs(User::factory()->create())->get(route('admin.pesanan.detail', $pesanan))
            ->assertOk()
            ->assertSeeTextInOrder(['Atur Jadwal Hotel', 'Pengingat ke tamu', '0/3 terkirim', 'Pengingat H-7', 'Kirim pengingat H-7', 'Lihat isi pesan', 'Pengingat H-3', 'Pengingat H-2', 'Aksi lainnya'])
            ->assertSee(route('admin.pesanan.pengingat', [$pesanan, 'h7']), false);
    }

    public function test_pengingat_tamu_belum_muncul_selama_tahap_hotel_belum_selesai(): void
    {
        $pesanan = $this->pesananHotelTuntas(['tamu_dikabari_hotel_at' => null]);

        $this->actingAs(User::factory()->create())->get(route('admin.pesanan.detail', $pesanan))
            ->assertOk()
            ->assertSeeText('Kurang 1 tahap lagi, segera selesaikan.')
            ->assertDontSeeText('Pengingat ke tamu');
    }

    public function test_baris_pengingat_h3_menggabungkan_konfirmasi_ulang_ke_hotel(): void
    {
        $pesanan = $this->pesananHotelTuntas(['pengingat_h7_terkirim' => true]);

        $this->actingAs(User::factory()->create())->get(route('admin.pesanan.detail', $pesanan))
            ->assertOk()
            ->assertSeeTextInOrder(['1/3 terkirim', 'Pengingat H-3', 'Kirim pengingat H-3', 'Konfirmasi ulang ke hotel'])
            ->assertSee(route('admin.pesanan.hotel.langkah.tandai', [$pesanan, 'ulang']), false);
    }

    public function test_tahap_pengingat_yang_terlewat_tidak_ditawarkan_lagi_dan_tahap_berlaku_yang_aktif(): void
    {
        $pesanan = $this->pesananHotelTuntas(['tanggal_trip' => today()->addDays(3)]);

        $this->actingAs(User::factory()->create())->get(route('admin.pesanan.detail', $pesanan))
            ->assertOk()
            ->assertSeeTextInOrder(['Pengingat H-7', 'Terlewat', 'Pengingat H-3', 'Kirim pengingat H-3'])
            ->assertDontSeeText('Kirim pengingat H-7');
    }

    public function test_pengingat_tamu_berganti_selesai_semua_setelah_h2_terkirim(): void
    {
        $pesanan = $this->pesananHotelTuntas([
            'pengingat_h7_terkirim' => true,
            'pengingat_h3_terkirim' => true,
            'konfirmasi_ulang_hotel_at' => now(),
            'pengingat_h2_terkirim' => true,
        ]);

        $this->actingAs(User::factory()->create())->get(route('admin.pesanan.detail', $pesanan))
            ->assertOk()
            ->assertSeeTextInOrder(['Pengingat ke tamu', '3/3 terkirim', 'Selesai semua', 'Pengingat H-7, H-3, dan H-2 sudah dikirim ke tamu, dan hotel sudah dikonfirmasi ulang.'])
            ->assertDontSeeText('Kirim pengingat');
    }

    public function test_pengingat_tamu_juga_muncul_bila_tamu_mengurus_penginapan_sendiri(): void
    {
        $pesanan = $this->pesananHotelTuntas(['status_penginapan' => 'sudah', 'hotel_id' => null, 'status_reservasi_hotel' => null]);

        $this->actingAs(User::factory()->create())->get(route('admin.pesanan.detail', $pesanan))
            ->assertOk()
            ->assertSeeTextInOrder(['Lihat Operasional', 'Pengingat ke tamu', 'Kirim pengingat H-7'])
            ->assertDontSeeText('Konfirmasi ulang ke hotel');
    }

    public function test_tombol_kirim_pengingat_menandai_terkirim_tanpa_membalik_tanda_saat_diklik_ulang(): void
    {
        $pesanan = $this->pesananHotelTuntas();
        $admin = User::factory()->create();

        $this->actingAs($admin)->patch(route('admin.pesanan.pengingat', [$pesanan, 'h7']), ['terkirim' => 1])->assertRedirect();
        $this->actingAs($admin)->patch(route('admin.pesanan.pengingat', [$pesanan, 'h7']), ['terkirim' => 1]);
        $this->assertTrue($pesanan->fresh()->pengingat_h7_terkirim);

        $this->actingAs($admin)->patch(route('admin.pesanan.pengingat', [$pesanan, 'h7']), ['terkirim' => 0]);
        $this->assertFalse($pesanan->fresh()->pengingat_h7_terkirim);
    }

    public function test_modal_reservasi_tersimpan_mengirim_sekali_klik_di_samping_nanti_saja(): void
    {
        $hotel = Hotel::factory()->create(['whatsapp' => '081808606086']);
        $pesanan = Pesanan::factory()->payment()->create(['status_penginapan' => 'diurus', 'hotel_id' => $hotel->id, 'status_reservasi_hotel' => 'diminta']);

        $this->actingAs(User::factory()->create())
            ->withSession(['modal_reservasi_hotel' => 'diminta'])
            ->get(route('admin.pesanan.detail', $pesanan))
            ->assertOk()
            ->assertSee('Reservasi tersimpan')
            ->assertSee('Nanti saja')
            ->assertSee('Kirim ke Hotel via WhatsApp')
            ->assertDontSee('Sudah dikirim semua');
    }
}
