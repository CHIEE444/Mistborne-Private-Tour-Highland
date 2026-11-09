<?php

namespace Tests\Feature\Publik;

use App\Enums\BookingStatus;
use App\Enums\SumberPesanan;
use App\Models\JadwalKeberangkatan;
use App\Models\Pengaturan;
use App\Models\Perjalanan;
use App\Models\PermintaanPesanan;
use App\Models\Pesanan;
use App\Models\User;
use App\Notifications\PermintaanPesananMasukNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class BookingWebsiteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Booking hanya menerima tanggal yang dibuka di Tanggal Tersedia; sebagian besar tes di sini memakai H+10.
        Perjalanan::query()->where('template_pesanan_kustom', false)->each(fn (Perjalanan $journey) => $this->bukaTanggal($journey, Carbon::today()->addDays(10)));
    }

    public function test_form_booking_dibuka_dengan_jumlah_pax_sesuai_journey(): void
    {
        $journey = Perjalanan::query()->where('template_pesanan_kustom', false)->whereNotNull('harga')->where('tone', '<>', 'custom')->orderBy('urutan')->firstOrFail();
        $user = User::factory()->tamu()->create();

        $this->actingAs($user)
            ->get(route('booking.buat', ['destination' => $journey->nama]))
            ->assertOk()
            ->assertSee('pax: '.$journey->maks_orang.',', false);
    }

    public function test_form_booking_masuk_ke_permintaan_form_tanpa_menahan_kuota_dan_membuka_wa_admin(): void
    {
        Notification::fake();
        $journey = Perjalanan::query()->where('template_pesanan_kustom', false)->whereNotNull('harga')->where('tone', '<>', 'custom')->orderBy('urutan')->firstOrFail();
        $user = User::factory()->tamu()->create();
        $admin = User::factory()->create(['jabatan' => 'admin']);
        Pengaturan::current()->update(['nomor_whatsapp_admin' => '+62 811-2233-4455']);

        $response = $this->actingAs($user)->post(route('booking.simpan'), [
            'token_booking' => (string) Str::uuid(),
            'destination' => $journey->nama,
            'pax' => 2,
            'nama_lengkap' => 'Tamu Form Website',
            'whatsapp' => '081234567890',
            'tanggal_trip' => Carbon::today()->addDays(10)->toDateString(),
            'status_penginapan' => 'sudah',
            'nama_hotel' => 'Hotel Contoh',
            'alamat_hotel' => 'Jl. Uji Alamat No. 1, Kota Uji',
            'lokasi_pickup' => 'Jl. Contoh No. 10',
            'kendaraan_pilihan' => 'Jeep klasik (petualangan dataran tinggi) — Toyota Land Cruiser FJ40',
            'waktu_pickup' => '08:30',
            'paket_opsional' => 'Sarapan tambahan',
            'permintaan_khusus' => 'Tidak ada',
        ]);

        $this->assertSame(302, $response->getStatusCode());
        $tautan = (string) $response->headers->get('Location');
        $this->assertStringStartsWith('https://wa.me/6281122334455?', $tautan);

        $this->assertDatabaseMissing('pesanan', ['nama_lengkap' => 'Tamu Form Website']);
        $permintaan = PermintaanPesanan::query()->where('nama_lengkap', 'Tamu Form Website')->firstOrFail();
        $this->assertSame($user->id, $permintaan->user_web_id);
        $this->assertSame('booking', $permintaan->sumber);
        $this->assertSame('menunggu_tinjauan', $permintaan->status);
        $this->assertSame($journey->id, $permintaan->perjalanan_id);
        $this->assertSame(2, $permintaan->jumlah_wisatawan);
        $this->assertSame('081234567890', $permintaan->whatsapp);
        $this->assertSame('Jl. Contoh No. 10', $permintaan->lokasi_pickup);
        $this->assertSame('08:30', substr((string) $permintaan->waktu_pickup, 0, 5));
        $this->assertSame('Sarapan tambahan', $permintaan->paket_opsional);
        $this->assertNull($permintaan->pesanan_id);
        Notification::assertSentTo($admin, PermintaanPesananMasukNotification::class);
        Notification::assertNotSentTo($user, PermintaanPesananMasukNotification::class);

        parse_str((string) parse_url($tautan, PHP_URL_QUERY), $parameter);
        $this->assertStringContainsString('Perjalanan: '.$journey->nama, $parameter['text'] ?? '');
        $this->assertStringContainsString('Lokasi jemput: Jl. Contoh No. 10', $parameter['text'] ?? '');
        $this->assertStringContainsString('Jam jemput: 08:30 WIB', $parameter['text'] ?? '');
        $this->assertStringContainsString('Catatan tambahan: Sarapan tambahan', $parameter['text'] ?? '');
    }

    public function test_permintaan_dari_booking_bisa_diproses_admin_menjadi_pesanan_form_yang_tertaut(): void
    {
        Notification::fake();
        $journey = Perjalanan::query()->where('template_pesanan_kustom', false)->whereNotNull('harga')->where('tone', '<>', 'custom')->orderBy('urutan')->firstOrFail();
        $tamu = User::factory()->create(['jabatan' => 'tamu']);
        $admin = User::factory()->create(['jabatan' => 'admin']);
        $tokenBooking = (string) Str::uuid();
        $tanggal = Carbon::today()->addDays(12)->toDateString();
        $this->bukaTanggal($journey, $tanggal);

        $this->actingAs($tamu)->post(route('booking.simpan'), [
            'token_booking' => $tokenBooking,
            'destination' => $journey->nama,
            'pax' => 3,
            'nama_lengkap' => 'Tamu Booking Deal',
            'whatsapp' => '081298765432',
            'tanggal_trip' => $tanggal,
            'status_penginapan' => 'sudah',
            'nama_hotel' => 'Hotel Deal',
            'alamat_hotel' => 'Jl. Uji Alamat No. 1, Kota Uji',
            'lokasi_pickup' => 'Lobi Hotel Deal',
            'kendaraan_pilihan' => 'Jeep klasik (petualangan dataran tinggi) — Toyota Land Cruiser FJ40',
        ])->assertStatus(302);
        $permintaan = PermintaanPesanan::query()->where('token_booking', $tokenBooking)->firstOrFail();

        $this->actingAs($admin)->get(route('admin.permintaan-pesanan.indeks'))
            ->assertOk()
            ->assertSee('Tamu Booking Deal')
            ->assertSee('Dari website')
            ->assertSee('Konfirmasi via WhatsApp');

        $this->actingAs($admin)->post(route('admin.permintaan-pesanan.buat-pesanan', $permintaan))
            ->assertSessionHasNoErrors();

        $permintaan->refresh();
        $pesanan = Pesanan::query()->findOrFail($permintaan->pesanan_id);
        $this->assertSame('disetujui', $permintaan->status);
        $this->assertSame(SumberPesanan::Form, $pesanan->sumber);
        $this->assertSame(BookingStatus::Dihubungi, $pesanan->status);
        $this->assertSame($tamu->id, $pesanan->user_web_id);
        $this->assertSame($tokenBooking, $pesanan->token_booking);
        $this->assertSame('6281298765432', $pesanan->whatsapp);
        $this->assertSame(3, $pesanan->jumlah_wisatawan);
        $this->assertSame($journey->harga, $pesanan->harga);
    }

    public function test_halaman_form_menyertakan_journey_yang_dipilih(): void
    {
        $journey = Perjalanan::query()->where('template_pesanan_kustom', false)->where('tone', '<>', 'custom')->orderBy('urutan')->firstOrFail();

        $response = $this->actingAs(User::factory()->tamu()->create())
            ->get(route('booking.buat', ['destination' => $journey->nama]));

        $response->assertOk()
            ->assertSee('name="destination"', false)
            ->assertSee('value="'.$journey->nama.'"', false);
    }

    public function test_submit_tanpa_journey_ditolak_dan_tidak_membuat_permintaan(): void
    {
        $journey = Perjalanan::query()->whereNotNull('harga')->where('maks_orang', '<', 6)->orderBy('id')->firstOrFail();

        $response = $this->actingAs(User::factory()->tamu()->create())->post(route('booking.simpan'), [
            'pax' => 2,
            'nama_lengkap' => 'Tamu Tanpa Journey',
            'whatsapp' => '081234567890',
            'tanggal_trip' => Carbon::today()->addDays(10)->toDateString(),
            'status_penginapan' => 'sudah',
            'nama_hotel' => 'Hotel Contoh',
            'alamat_hotel' => 'Jl. Uji Alamat No. 1, Kota Uji',
            'lokasi_pickup' => 'Jl. Contoh No. 10',
            'kendaraan_pilihan' => 'Jeep klasik (petualangan dataran tinggi) — Toyota Land Cruiser FJ40',
        ]);

        $response->assertSessionHasErrors('destination');
        $this->assertDatabaseMissing('permintaan_pesanan', ['nama_lengkap' => 'Tamu Tanpa Journey']);
    }

    public function test_tamu_tanpa_login_langsung_melihat_form_booking_dan_bisa_mengirimnya(): void
    {
        Notification::fake();
        $journey = Perjalanan::query()->where('template_pesanan_kustom', false)->whereNotNull('harga')->where('maks_orang', '<', 6)->orderBy('id')->firstOrFail();
        Pengaturan::current()->update(['nomor_whatsapp_admin' => '+62 811-2233-4455']);

        $this->get(route('booking.buat', ['destination' => $journey->nama]))
            ->assertOk()
            ->assertSee('value="'.$journey->nama.'"', false);

        $data = [
            'token_booking' => (string) Str::uuid(),
            'destination' => $journey->nama,
            'pax' => 2,
            'nama_lengkap' => 'Tamu Tanpa Akun',
            'whatsapp' => '081234567890',
            'tanggal_trip' => Carbon::today()->addDays(10)->toDateString(),
            'lokasi_pickup' => 'Jl. Contoh No. 10',
        ];

        $pertama = $this->post(route('booking.simpan'), $data);
        $this->assertStringStartsWith('https://wa.me/6281122334455?', (string) $pertama->headers->get('Location'));
        $this->assertNull(PermintaanPesanan::query()->where('nama_lengkap', 'Tamu Tanpa Akun')->firstOrFail()->user_web_id);

        $this->post(route('booking.simpan'), $data)->assertLocation($pertama->headers->get('Location'));
        $this->assertSame(1, PermintaanPesanan::query()->where('token_booking', $data['token_booking'])->count());
    }

    public function test_tamu_tanpa_akun_tidak_bisa_membuka_permintaan_milik_tamu_lain_dengan_token_yang_sama(): void
    {
        Notification::fake();
        $journey = Perjalanan::query()->where('template_pesanan_kustom', false)->whereNotNull('harga')->where('maks_orang', '<', 6)->orderBy('id')->firstOrFail();
        $pemilik = User::factory()->tamu()->create();
        $data = [
            'token_booking' => (string) Str::uuid(),
            'destination' => $journey->nama,
            'pax' => 2,
            'nama_lengkap' => 'Tamu Pemilik',
            'whatsapp' => '081234567890',
            'tanggal_trip' => Carbon::today()->addDays(10)->toDateString(),
            'lokasi_pickup' => 'Jl. Contoh No. 10',
        ];

        $this->actingAs($pemilik)->post(route('booking.simpan'), $data)->assertStatus(302);
        auth()->logout();

        $this->post(route('booking.simpan'), $data)->assertNotFound();
    }

    public function test_pengiriman_ulang_form_dengan_token_sama_tidak_membuat_permintaan_ganda(): void
    {
        $journey = Perjalanan::query()->whereNotNull('harga')->where('maks_orang', '<', 6)->orderBy('id')->firstOrFail();
        $user = User::factory()->tamu()->create();
        $data = [
            'token_booking' => (string) Str::uuid(),
            'destination' => $journey->nama,
            'pax' => 2,
            'nama_lengkap' => 'Tamu Form Ulang',
            'whatsapp' => '081234567890',
            'tanggal_trip' => Carbon::today()->addDays(10)->toDateString(),
            'status_penginapan' => 'sudah',
            'nama_hotel' => 'Hotel Contoh',
            'alamat_hotel' => 'Jl. Uji Alamat No. 1, Kota Uji',
            'lokasi_pickup' => 'Jl. Contoh No. 10',
            'kendaraan_pilihan' => 'Jeep klasik (petualangan dataran tinggi) — Toyota Land Cruiser FJ40',
        ];

        $pertama = $this->actingAs($user)->post(route('booking.simpan'), $data);
        $data['pax'] = 999;
        $data['tanggal_trip'] = Carbon::today()->toDateString();
        $kedua = $this->actingAs($user)->post(route('booking.simpan'), $data);

        $pertama->assertStatus(302);
        $kedua->assertStatus(302)->assertLocation($pertama->headers->get('Location'));
        $this->assertSame(1, PermintaanPesanan::query()->where('token_booking', $data['token_booking'])->count());
    }

    public function test_kapasitas_pax_tanggal_dan_nomor_whatsapp_divalidasi_sebelum_permintaan_dibuat(): void
    {
        $journey = Perjalanan::query()->whereNotNull('harga')->where('maks_orang', '<', 6)->orderBy('id')->firstOrFail();
        $user = User::factory()->tamu()->create();
        $data = [
            'token_booking' => (string) Str::uuid(),
            'destination' => $journey->nama,
            'pax' => 6,
            'nama_lengkap' => 'Tamu Data Tidak Valid',
            'whatsapp' => '081234567890',
            'tanggal_trip' => Carbon::today()->addDays(10)->toDateString(),
            'status_penginapan' => 'sudah',
            'nama_hotel' => 'Hotel Contoh',
            'alamat_hotel' => 'Jl. Uji Alamat No. 1, Kota Uji',
            'lokasi_pickup' => 'Jl. Contoh No. 10',
            'permintaan_khusus' => 'Kursi anak',
        ];

        $this->actingAs($user)->from(route('booking.buat', ['destination' => $journey->nama]))
            ->post(route('booking.simpan'), $data)
            ->assertSessionHasErrors('pax');

        $this->get(route('booking.buat', ['destination' => $journey->nama]))
            ->assertSee('Kursi anak');

        $data['token_booking'] = (string) Str::uuid();
        $data['pax'] = 1;
        $data['whatsapp'] = '08123abc4567';
        $data['tanggal_trip'] = Carbon::today()->addDays(6)->toDateString();
        $this->post(route('booking.simpan'), $data)
            ->assertSessionHasErrors(['whatsapp', 'tanggal_trip']);

        $this->assertDatabaseMissing('permintaan_pesanan', ['nama_lengkap' => 'Tamu Data Tidak Valid']);
    }

    public function test_tanggal_diblokir_atau_penuh_menolak_booking_tanpa_membuat_permintaan(): void
    {
        $journey = Perjalanan::query()->whereNotNull('harga')->where('maks_orang', '<', 6)->orderBy('id')->firstOrFail();
        $tanggal = Carbon::today()->addDays(12)->toDateString();
        $user = User::factory()->tamu()->create();
        $data = [
            'token_booking' => (string) Str::uuid(),
            'destination' => $journey->nama,
            'pax' => 1,
            'nama_lengkap' => 'Tamu Tanggal Penuh',
            'whatsapp' => '081234567890',
            'tanggal_trip' => $tanggal,
            'status_penginapan' => 'sudah',
            'nama_hotel' => 'Hotel Contoh',
            'alamat_hotel' => 'Jl. Uji Alamat No. 1, Kota Uji',
            'lokasi_pickup' => 'Jl. Contoh No. 10',
            'kendaraan_pilihan' => 'Jeep klasik (petualangan dataran tinggi) — Toyota Land Cruiser FJ40',
        ];

        JadwalKeberangkatan::factory()->create([
            'perjalanan_id' => $journey->id,
            'tanggal' => $tanggal,
            'kuota' => 1,
            'diblok' => true,
        ]);

        $this->actingAs($user)->from(route('booking.buat', ['destination' => $journey->nama]))
            ->post(route('booking.simpan'), $data)
            ->assertSessionHasErrors('tanggal_trip');
        $this->assertDatabaseMissing('permintaan_pesanan', ['nama_lengkap' => 'Tamu Tanggal Penuh']);

        JadwalKeberangkatan::query()->where('perjalanan_id', $journey->id)->whereDate('tanggal', $tanggal)->update(['diblok' => false]);
        Pesanan::factory()->payment()->create([
            'perjalanan_id' => $journey->id,
            'nama_perjalanan' => $journey->nama,
            'tanggal_trip' => $tanggal,
        ]);

        $data['token_booking'] = (string) Str::uuid();
        $data['nama_lengkap'] = 'Tamu Kuota Penuh';
        $this->post(route('booking.simpan'), $data)
            ->assertSessionHasErrors('tanggal_trip');
        $this->assertDatabaseMissing('permintaan_pesanan', ['nama_lengkap' => 'Tamu Kuota Penuh']);
    }

    public function test_nomor_whatsapp_dengan_awalan_62_atau_pemisah_diterima_dan_disimpan_sebagai_08(): void
    {
        $journey = Perjalanan::query()->where('template_pesanan_kustom', false)->whereNotNull('harga')->where('tone', '<>', 'custom')->orderBy('urutan')->firstOrFail();
        $user = User::factory()->tamu()->create();

        foreach (['+62 812-3456-7890', '6281234567890', '0812 3456 7890'] as $indeks => $nomor) {
            $this->actingAs($user)->post(route('booking.simpan'), [
                'token_booking' => (string) Str::uuid(),
                'destination' => $journey->nama,
                'pax' => 1,
                'nama_lengkap' => "Tamu Nomor {$indeks}",
                'whatsapp' => $nomor,
                'tanggal_trip' => Carbon::today()->addDays(10)->toDateString(),
                'status_penginapan' => 'sudah',
                'nama_hotel' => 'Hotel Contoh',
                'alamat_hotel' => 'Jl. Uji Alamat No. 1, Kota Uji',
                'lokasi_pickup' => 'Jl. Contoh No. 10',
                'kendaraan_pilihan' => 'Jeep klasik (petualangan dataran tinggi) — Toyota Land Cruiser FJ40',
            ])->assertSessionHasNoErrors();

            $this->assertDatabaseHas('permintaan_pesanan', ['nama_lengkap' => "Tamu Nomor {$indeks}", 'whatsapp' => '081234567890']);
        }
    }

    public function test_booking_publik_menolak_karakter_tag_html_pada_nama_dan_lokasi(): void
    {
        $journey = Perjalanan::query()->where('template_pesanan_kustom', false)->whereNotNull('harga')->where('tone', '<>', 'custom')->orderBy('urutan')->firstOrFail();

        $this->actingAs(User::factory()->tamu()->create())->post(route('booking.simpan'), [
            'token_booking' => (string) Str::uuid(),
            'destination' => $journey->nama,
            'pax' => 1,
            'nama_lengkap' => '<img src=x onerror=alert(1)>',
            'whatsapp' => '081234567890',
            'tanggal_trip' => Carbon::today()->addDays(10)->toDateString(),
            'lokasi_pickup' => 'Jl. <Contoh>',
            'kendaraan_pilihan' => 'Jeep klasik (petualangan dataran tinggi) — Toyota Land Cruiser FJ40',
        ])->assertSessionHasErrors(['nama_lengkap', 'lokasi_pickup']);

        $this->assertDatabaseCount('permintaan_pesanan', 0);
    }
}
