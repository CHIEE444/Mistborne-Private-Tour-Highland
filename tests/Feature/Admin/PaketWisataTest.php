<?php

namespace Tests\Feature\Admin;

use App\Models\Perjalanan;
use App\Models\User;
use Database\Seeders\HotelSeeder;
use Database\Seeders\PaketWisataSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaketWisataTest extends TestCase
{
    private function admin(): User
    {
        return User::factory()->create(['jabatan' => 'admin']);
    }

    /**
     * @param  array<string, mixed>  $timpa
     * @return array<string, mixed>
     */
    private function data(array $timpa = []): array
    {
        return [
            'jenis' => 'paket',
            'nama' => 'Telaga Cebong Relaks',
            'chip' => 'Sehari Penuh',
            'label_unggulan' => 'PILIHAN BARU • 2H1M',
            'rating_label' => '4.9',
            'region' => 'jawa-tengah',
            'tempat' => 'Sembungan, Jawa Tengah',
            'tagline' => 'Telaga tenang dikelilingi bukit hijau.',
            'deskripsi' => 'Nikmati pagi yang tenang di tepi telaga bersama guide lokal yang juga menjadi driver.',
            'harga' => 3_850_000,
            'durasi_hari' => 2,
            'maks_orang' => 4,
            'urutan' => 9,
            'tampil_beranda' => '1',
            'img' => UploadedFile::fake()->image('telaga.jpg', 1600, 1200),
            'tanggal_pertama' => now()->addDays(20)->toDateString(),
            'highlights' => ['Telaga yang tenang', 'Udara pagi yang sejuk'],
            'itinerary' => [['t' => 'Hari 1 · 08.00', 'x' => 'Jemput di hotel'], ['t' => 'Hari 2 · 10.00', 'x' => 'Antar kembali']],
            'praktis' => ['pickup' => 'Hotel di Wonosobo', 'start' => 'Jam 08.00 WIB (estimasi)', 'capacity' => 'Maks. 4 tamu per booking, di luar pemandu yang merangkap sopir', 'tips' => 'Bawa jaket.'],
            ...$timpa,
        ];
    }

    public function test_beranda_menampilkan_paket_bawaan_dengan_harga_kapasitas_dan_tautan_detail(): void
    {
        $paket = Perjalanan::query()->paket()->where('nama', 'Bukit Sikunir')->firstOrFail();

        $this->assertSame(4, Perjalanan::query()->paket()->count());
        $this->get('/')->assertOk()
            ->assertSee('Paket Relaksasi Alam')
            ->assertSee('Rp 4.250.000')
            ->assertSee('Maks. 4 Tamu')
            ->assertSee($paket->slug, false)
            ->assertDontSee('Rp 1.850.000');
    }

    public function test_paket_tidak_muncul_di_daftar_journey_dan_sebaliknya(): void
    {
        $this->get(route('journeys.index'))->assertOk()->assertDontSee('Bukit Sikunir')->assertSee('DIENG HIGHLAND ESCAPE');
        $this->get('/')->assertOk();
    }

    public function test_halaman_detail_paket_lengkap_seperti_journey_dan_bisa_dibooking(): void
    {
        $paket = Perjalanan::query()->paket()->where('nama', 'Bukit Sikunir')->firstOrFail();

        $this->get(route('journeys.show', $paket->slug))->assertOk()
            ->assertSee('Bukit Sikunir')
            ->assertSee('Itinerary')
            ->assertSee('Hari 2')
            ->assertDontSee('04.00')
            ->assertSee('Golden sunrise di Bukit Sikunir')
            ->assertSee('Ketentuan &amp; Pembayaran', false)
            ->assertSee('DP minimal 30%')
            ->assertSee('Maks. 4 orang')
            ->assertSee('Kembali ke Beranda')
            ->assertSee(route('booking.buat', ['destination' => $paket->nama]), false);
    }

    public function test_paket_dipesan_lewat_alur_booking_journey(): void
    {
        $paket = Perjalanan::query()->paket()->where('nama', 'Telaga Sarangan & Cemoro Sewu')->firstOrFail();

        $this->actingAs($this->admin())
            ->get(route('booking.buat', ['destination' => $paket->nama]))
            ->assertOk()->assertSee('Telaga Sarangan & Cemoro Sewu');

        $this->actingAs(User::factory()->create(['jabatan' => 'tamu']))->post(route('booking.simpan'), [
            'token_booking' => (string) Str::uuid(),
            'destination' => $paket->nama,
            'pax' => 3,
            'nama_lengkap' => 'Tamu Paket',
            'whatsapp' => '081234567890',
            'tanggal_trip' => now()->addDays(15)->toDateString(),
            'status_penginapan' => 'belum',
            'lokasi_pickup' => 'Stasiun Madiun',
            'kendaraan_pilihan' => 'Jeep klasik (petualangan dataran tinggi) — Toyota Land Cruiser FJ40',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('permintaan_pesanan', ['perjalanan_id' => $paket->id, 'nama_lengkap' => 'Tamu Paket', 'jumlah_wisatawan' => 3]);
    }

    public function test_admin_menambah_paket_lengkap_dan_tampil_di_beranda_dan_detail(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())->post(route('admin.perjalanan.simpan'), $this->data())
            ->assertRedirect(route('admin.perjalanan.indeks', ['jenis' => 'paket']))
            ->assertSessionHasNoErrors();

        $paket = Perjalanan::query()->paket()->where('nama', 'Telaga Cebong Relaks')->firstOrFail();
        $this->assertSame('paket', $paket->jenis);
        $this->assertSame('Sehari Penuh', $paket->chip);
        $this->assertSame(1, $paket->jadwal()->count());

        $this->get('/')->assertOk()->assertSee('Telaga Cebong Relaks')->assertSee('Rp 3.850.000');
        $this->get(route('journeys.show', $paket->slug))->assertOk()->assertSee('Telaga yang tenang');
        $this->get(route('journeys.index'))->assertDontSee('Telaga Cebong Relaks');
    }

    public function test_kategori_paket_wajib_dan_aturan_durasi_berlaku(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.perjalanan.simpan'), $this->data(['chip' => '']))->assertSessionHasErrors('chip');
        $this->actingAs($admin)->post(route('admin.perjalanan.simpan'), $this->data(['durasi_hari' => 1]))->assertSessionHasErrors('durasi_hari');
        $this->actingAs($admin)->post(route('admin.perjalanan.simpan'), $this->data(['rating_label' => '9.9']))->assertSessionHasErrors('rating_label');
        $this->actingAs($admin)->post(route('admin.perjalanan.simpan'), $this->data(['label_unggulan' => '<b>x</b>']))->assertSessionHasErrors('label_unggulan');
    }

    public function test_daftar_admin_memisahkan_journey_dan_paket_dan_menu_samping_menampilkan_keduanya(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.perjalanan.indeks', ['jenis' => 'paket']))->assertOk()
            ->assertSee('Paket Wisata')->assertSee('Bukit Sikunir')->assertDontSee('DIENG HIGHLAND ESCAPE')->assertSee('Tambah Paket');
        $this->actingAs($admin)->get(route('admin.perjalanan.indeks'))->assertOk()
            ->assertSee('DIENG HIGHLAND ESCAPE')->assertDontSee('Bukit Sikunir')->assertSee('Paket Wisata')->assertSee('Tambah Journey');
        $this->actingAs($admin)->get(route('admin.perjalanan.buat', ['jenis' => 'paket']))->assertOk()->assertSee('Kategori Paket')->assertSee('Simpan Paket');
        $this->actingAs($admin)->get(route('admin.perjalanan.buat'))->assertOk()->assertDontSee('Kategori Paket');
    }

    public function test_admin_mengubah_harga_paket_dan_beranda_ikut_berubah_lalu_menyembunyikannya(): void
    {
        $admin = $this->admin();
        $paket = Perjalanan::query()->paket()->where('nama', 'Hutan Pinus')->firstOrFail();

        $this->actingAs($admin)->get(route('admin.perjalanan.ubah', $paket))->assertOk()->assertSee('Kategori Paket');
        $this->actingAs($admin)->put(route('admin.perjalanan.perbarui', $paket), $this->data([
            'nama' => 'Hutan Pinus', 'harga' => 4_100_000, 'img' => null, 'tanggal_pertama' => null, 'jenis' => 'paket',
        ]))->assertSessionHasNoErrors();

        $this->assertSame(4_100_000, $paket->fresh()->harga);
        $this->get('/')->assertSee('Rp 4.100.000');

        $this->actingAs($admin)->patch(route('admin.perjalanan.sembunyikan', $paket))->assertRedirect(route('admin.perjalanan.indeks', ['jenis' => 'paket']));
        $this->get('/')->assertDontSee('Rp 4.100.000');
    }

    public function test_seeder_paket_aman_dijalankan_ulang(): void
    {
        $this->seed(PaketWisataSeeder::class);

        $this->assertSame(4, Perjalanan::query()->paket()->count());
    }

    public function test_paket_kawah_ijen_diganti_telaga_sarangan_dengan_hotel_bintang_empat(): void
    {
        $this->seed(HotelSeeder::class);

        $this->assertNull(Perjalanan::query()->paket()->where('nama', 'Kawah Ijen')->first());

        $sarangan = Perjalanan::query()->paket()->where('nama', 'Telaga Sarangan & Cemoro Sewu')->firstOrFail();
        $this->assertSame(4_500_000, (int) $sarangan->harga);
        $this->assertFileExists(public_path(ltrim($sarangan->img, '/')));
        $this->assertSame(['Aston Madiun Hotel & Conference Center'], $sarangan->hotel()->pluck('nama')->all());

        $this->get(route('journeys.show', $sarangan->slug))
            ->assertOk()
            ->assertSee('Aston Madiun Hotel &amp; Conference Center', false)
            ->assertSee('Air Terjun Tirtosari');
    }
}
