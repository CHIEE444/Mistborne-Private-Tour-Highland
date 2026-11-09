<?php

namespace Tests\Feature\Publik;

use App\Bantuan\JamPenjemputan;
use App\Bantuan\PilihanKendaraan;
use App\Models\Kendaraan;
use App\Models\Pengaturan;
use App\Models\Perjalanan;
use App\Models\PermintaanPesanan;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class PerbaikanTamuTest extends TestCase
{
    /** Journey berharga yang tanggal H+10-nya dibuka di Tanggal Tersedia (tanggal yang dipakai formulir di tes ini). */
    private function journeyBerharga(): Perjalanan
    {
        $journey = Perjalanan::query()->where('template_pesanan_kustom', false)->whereNotNull('harga')->where('tone', '<>', 'custom')->orderBy('urutan')->firstOrFail();
        $this->bukaTanggal($journey, Carbon::today()->addDays(10));

        return $journey;
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
            'pax' => 2,
            'nama_lengkap' => 'Tamu Perbaikan',
            'whatsapp' => '081234567890',
            'tanggal_trip' => Carbon::today()->addDays(10)->toDateString(),
            'lokasi_pickup' => 'Stasiun Malang',
            'status_penginapan' => 'sudah',
            'nama_hotel' => 'Hotel Tugu Malang',
            'alamat_hotel' => 'Jl. Tugu No. 3, Malang',
            ...$timpa,
        ];
    }

    public function test_bahasa_inggris_tidak_kembali_ke_indonesia_saat_membuka_kartu_journey_dan_booking(): void
    {
        $journey = $this->journeyBerharga();

        $this->get('/en')->assertOk();
        $this->assertSame('en', app()->getLocale());

        $this->get(route('journeys.show', $journey->slug))->assertOk()->assertSee('Terms &amp; Payment', false)->assertSee('Day 1', false);
        $this->assertSame('en', app()->getLocale());

        $this->get(route('journeys.index'))->assertOk();
        $this->assertSame('en', app()->getLocale());

        $this->actingAs(User::factory()->create(['jabatan' => 'tamu']))
            ->get(route('booking.buat', ['destination' => $journey->nama]))
            ->assertOk()
            ->assertDontSee('Vehicle for this trip');
        $this->assertSame('en', app()->getLocale());
    }

    public function test_detail_journey_berawalan_bahasa_terbuka_di_semua_bahasa(): void
    {
        $journey = $this->journeyBerharga();

        foreach (['en', 'ms', 'zh', 'ja', 'ko', 'es', 'fr', 'ar'] as $bahasa) {
            $this->get(route('journeys.show.localized', ['locale' => $bahasa, 'slug' => $journey->slug]))->assertOk();
            $this->assertSame($bahasa, app()->getLocale());
        }

        $this->get('/xx/journey/'.$journey->slug)->assertNotFound();
    }

    public function test_tautan_beranda_di_halaman_berbahasa_inggris_tetap_berbahasa_inggris(): void
    {
        $journey = $this->journeyBerharga();

        $this->get('/en')->assertOk();
        $respons = $this->get(route('journeys.show', $journey->slug));

        $respons->assertSee(url('/en').'#beranda', false);
        $respons->assertDontSee('href="'.url('/').'#beranda"', false);
    }

    public function test_memilih_bahasa_lain_lalu_indonesia_mengganti_bahasa_tersimpan(): void
    {
        $journey = $this->journeyBerharga();

        $this->get('/ja')->assertOk();
        $this->get(route('journeys.show', $journey->slug))->assertOk();
        $this->assertSame('ja', app()->getLocale());

        $this->get('/')->assertOk();
        $this->get(route('journeys.show', $journey->slug))->assertOk();
        $this->assertSame('id', app()->getLocale());
    }

    public function test_jam_jemput_dinormalkan_dari_berbagai_format(): void
    {
        $this->assertSame('14:00', JamPenjemputan::normalisasi('14:00'));
        $this->assertSame('14:00', JamPenjemputan::normalisasi('14:00:00'));
        $this->assertSame('14:00', JamPenjemputan::normalisasi('14.00'));
        $this->assertSame('14:00', JamPenjemputan::normalisasi('2:00 PM'));
        $this->assertSame('00:30', JamPenjemputan::normalisasi('12:30 AM'));
        $this->assertSame('09:05', JamPenjemputan::normalisasi(' 9:05 '));
        $this->assertNull(JamPenjemputan::normalisasi(''));
        $this->assertSame('25:00', JamPenjemputan::normalisasi('25:00'));
    }

    public function test_booking_menerima_jam_jemput_14_00_dalam_format_apa_pun(): void
    {
        $journey = $this->journeyBerharga();
        $user = User::factory()->create();

        foreach (['14:00', '14:00:00', '14.00'] as $jam) {
            $this->actingAs($user)
                ->post(route('booking.simpan'), $this->dataBooking($journey, ['waktu_pickup' => $jam]))
                ->assertSessionHasNoErrors();
        }

        $this->assertSame(3, PermintaanPesanan::query()->count());
        PermintaanPesanan::query()->get()->each(fn (PermintaanPesanan $permintaan) => $this->assertSame('14:00', substr((string) $permintaan->waktu_pickup, 0, 5)));

        $this->actingAs($user)
            ->post(route('booking.simpan'), $this->dataBooking($journey, ['waktu_pickup' => '99:99']))
            ->assertSessionHasErrors('waktu_pickup');
    }

    public function test_formulir_tamu_menerima_jam_jemput_yang_terisi_ulang_dari_database(): void
    {
        $journey = $this->journeyBerharga();
        $permintaan = PermintaanPesanan::query()->create([
            'token' => Str::random(64),
            'whatsapp' => '081234567890',
            'status' => 'menunggu_tamu',
            'token_berlaku_sampai' => PermintaanPesanan::batasBerlakuBaru(),
        ]);

        $this->post(route('form-pesanan.simpan', $permintaan->token), [
            'nama_lengkap' => 'Tamu Form',
            'whatsapp' => '081234567890',
            'perjalanan_id' => $journey->id,
            'tanggal_trip' => Carbon::today()->addDays(10)->toDateString(),
            'jumlah_wisatawan' => 2,
            'status_penginapan' => 'sudah',
            'nama_hotel' => 'Hotel Uji',
            'alamat_hotel' => 'Jl. Uji No. 5, Kota Uji',
            'lokasi_pickup' => 'Hotel Uji',
            'kendaraan_pilihan' => 'Jeep klasik (petualangan dataran tinggi) — Toyota Land Cruiser FJ40',
            'waktu_pickup' => '14:00',
        ])->assertSessionHasNoErrors();

        $this->get(route('form-pesanan.tampil', $permintaan->token))->assertOk()->assertSee('value="14:00"', false);

        $this->post(route('form-pesanan.simpan', $permintaan->token), [
            'nama_lengkap' => 'Tamu Form',
            'whatsapp' => '081234567890',
            'perjalanan_id' => $journey->id,
            'tanggal_trip' => Carbon::today()->addDays(10)->toDateString(),
            'jumlah_wisatawan' => 2,
            'status_penginapan' => 'sudah',
            'nama_hotel' => 'Hotel Uji',
            'alamat_hotel' => 'Jl. Uji No. 5, Kota Uji',
            'lokasi_pickup' => 'Hotel Uji',
            'kendaraan_pilihan' => 'Jeep klasik (petualangan dataran tinggi) — Toyota Land Cruiser FJ40',
            'waktu_pickup' => '14:00:00',
        ])->assertSessionHasNoErrors();
    }

    public function test_formulir_tamu_lewat_tautan_tidak_menyimpan_atau_menampilkan_mobil(): void
    {
        $journey = $this->journeyBerharga();
        $permintaan = PermintaanPesanan::query()->create(['token' => Str::random(64), 'whatsapp' => '081234567890', 'status' => 'menunggu_tamu', 'token_berlaku_sampai' => PermintaanPesanan::batasBerlakuBaru()]);

        $this->post(route('form-pesanan.simpan', $permintaan->token), [
            'nama_lengkap' => 'Tamu Form', 'whatsapp' => '081234567890', 'perjalanan_id' => $journey->id,
            'tanggal_trip' => Carbon::today()->addDays(10)->toDateString(), 'jumlah_wisatawan' => 2,
            'status_penginapan' => 'belum', 'lokasi_pickup' => 'Stasiun',
        ])->assertSessionHasNoErrors();

        $this->assertNull($permintaan->fresh()->kendaraan_pilihan);

        $permintaanBaru = PermintaanPesanan::query()->create(['token' => Str::random(64), 'whatsapp' => '081234567890', 'status' => 'menunggu_tamu', 'token_berlaku_sampai' => PermintaanPesanan::batasBerlakuBaru()]);
        $this->get(route('form-pesanan.tampil', $permintaanBaru->token))
            ->assertOk()
            ->assertDontSee('Mobil untuk perjalanan ini')
            ->assertDontSee($journey->kendaraan->namaUntukTamu())
            ->assertDontSee('Pilih kendaraan')
            ->assertDontSee('name="kendaraan_pilihan"', false);
    }

    public function test_tautan_formulir_berlaku_maksimal_satu_hari(): void
    {
        $this->freezeTime();
        $journey = $this->journeyBerharga();
        $this->actingAs(User::factory()->create())
            ->post(route('booking.simpan'), $this->dataBooking($journey))
            ->assertSessionHasNoErrors();

        $permintaan = PermintaanPesanan::query()->firstOrFail();
        $this->assertSame(now()->addDay()->toDateTimeString(), $permintaan->token_berlaku_sampai->toDateTimeString());

        $this->travel(25)->hours();
        $this->get(route('form-pesanan.tampil', $permintaan->token))->assertStatus(410);
    }

    public function test_data_hotel_dari_tamu_diabaikan_karena_penginapan_sudah_termasuk(): void
    {
        $journey = $this->journeyBerharga();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('booking.simpan'), $this->dataBooking($journey, ['alamat_hotel' => 'Diabaikan', 'nama_hotel' => 'Diabaikan']))
            ->assertSessionHasNoErrors();

        $permintaan = PermintaanPesanan::query()->firstOrFail();
        $this->assertNull($permintaan->nama_hotel);
        $this->assertNull($permintaan->alamat_hotel);
    }

    public function test_booking_tidak_menyimpan_mobil_dan_pesan_whatsapp_tanpa_kendaraan(): void
    {
        $journey = $this->journeyBerharga();

        $respons = $this->actingAs(User::factory()->create())->post(route('booking.simpan'), $this->dataBooking($journey));

        $respons->assertSessionHasNoErrors();
        $this->assertNull(PermintaanPesanan::query()->firstOrFail()->kendaraan_pilihan);
        $teks = urldecode((string) $respons->headers->get('Location'));
        $this->assertStringNotContainsString('Kendaraan', $teks);
        $this->assertStringContainsString('All-inclusive (penginapan dan tiket masuk diurus MISTBORNE)', $teks);
    }

    public function test_kiriman_kendaraan_dari_tamu_diabaikan(): void
    {
        $journey = $this->journeyBerharga();

        foreach (['Bus Pariwisata', 'MPV — Toyota Kijang Innova Zenix', 'Jeep klasik (petualangan dataran tinggi) — Toyota Land Cruiser FJ40'] as $kiriman) {
            $this->actingAs(User::factory()->create())
                ->post(route('booking.simpan'), $this->dataBooking($journey, ['kendaraan_pilihan' => $kiriman]))
                ->assertSessionHasNoErrors();
        }

        $this->assertSame([null], PermintaanPesanan::query()->pluck('kendaraan_pilihan')->unique()->values()->all());
    }

    public function test_destinasi_tanpa_mobil_tetap_bisa_dibooking_dengan_kendaraan_kosong(): void
    {
        $journey = $this->journeyBerharga();
        $journey->update(['kendaraan_id' => null, 'alasan_kendaraan' => null]);

        $this->actingAs(User::factory()->create())->post(route('booking.simpan'), $this->dataBooking($journey, ['kendaraan_pilihan' => 'Bus Pariwisata']))->assertSessionHasNoErrors();

        $this->assertNull(PermintaanPesanan::query()->firstOrFail()->kendaraan_pilihan);
    }

    public function test_jumlah_tamu_dibatasi_kapasitas_journey_bukan_kursi_mobil_utama(): void
    {
        $journey = $this->journeyBerharga();
        $journey->update(['maks_orang' => 4, 'kendaraan_id' => Kendaraan::where('nama', 'Suzuki Jimny')->value('id')]);

        $this->actingAs(User::factory()->create())
            ->post(route('booking.simpan'), $this->dataBooking($journey, ['pax' => 5]))
            ->assertSessionHasErrors('pax');

        $this->actingAs(User::factory()->create())
            ->post(route('booking.simpan'), $this->dataBooking($journey, ['pax' => 4]))
            ->assertSessionHasNoErrors();
    }

    public function test_formulir_booking_tidak_punya_pilihan_kendaraan_dan_tidak_menampilkan_mobil(): void
    {
        $journey = $this->journeyBerharga();

        $this->actingAs(User::factory()->create())->get(route('booking.buat', ['destination' => $journey->nama]))
            ->assertOk()
            ->assertDontSee('Mobil untuk perjalanan ini')
            ->assertDontSee($journey->kendaraan->namaUntukTamu())
            ->assertDontSee('data-mobil-destinasi', false)
            ->assertDontSee('Pilih kendaraan')
            ->assertDontSee('name="kendaraan_pilihan"', false);
    }

    public function test_batas_tamu_mengikuti_kapasitas_journey(): void
    {
        $journey = $this->journeyBerharga();

        $journey->update(['maks_orang' => 4]);
        $this->actingAs(User::factory()->create())->post(route('booking.simpan'), $this->dataBooking($journey, ['pax' => 4]))->assertSessionHasNoErrors();
        $this->actingAs(User::factory()->create())->post(route('booking.simpan'), $this->dataBooking($journey, ['pax' => 5]))->assertSessionHasErrors('pax');

        $journey->update(['maks_orang' => 8]);
        $this->actingAs(User::factory()->create())->post(route('booking.simpan'), $this->dataBooking($journey, ['pax' => 8]))->assertSessionHasNoErrors();
        $this->actingAs(User::factory()->create())->post(route('booking.simpan'), $this->dataBooking($journey, ['pax' => 9]))->assertSessionHasErrors('pax');
    }

    public function test_pilihan_kendaraan_admin_memuat_batas_tamu_masing_masing_unit(): void
    {
        $this->assertSame(5, PilihanKendaraan::maksTamu('Jeep klasik (petualangan dataran tinggi) — Toyota Land Cruiser FJ40'));
        $this->assertSame(3, PilihanKendaraan::maksTamu('Jeep klasik (petualangan dataran tinggi) — Suzuki Jimny 2020'));
        $this->assertSame(6, PilihanKendaraan::maksTamu('SUV — Toyota Fortuner 2020'));
        $this->assertSame(4, PilihanKendaraan::maksTamu('Double cabin 4x4 — Toyota Hilux Double Cabin 2020'));
        $this->assertSame(6, PilihanKendaraan::maksTamu('MPV — Toyota Kijang Innova Venturer 2020'));
        $this->assertSame(6, PilihanKendaraan::maksTamu('SUV — Isuzu MU-X 4x4 2020'));
        $this->assertSame(6, PilihanKendaraan::maksTamu('SUV — Nissan Terra 2020'));
        $this->assertSame(6, PilihanKendaraan::maksTamu('SUV — Hyundai Santa Fe 2020'));
        $this->assertSame(6, PilihanKendaraan::maksTamu('SUV — Toyota Rush TRD 2020'));
        $this->assertSame(6, PilihanKendaraan::maksTamu('SUV — Nissan X-Trail 2020'));
        $this->assertNull(PilihanKendaraan::maksTamu('Jeep klasik (petualangan dataran tinggi) — Daihatsu Taft'));
        $this->assertNull(PilihanKendaraan::maksTamu('Jeep klasik (petualangan dataran tinggi) — Suzuki Katana'));
    }

    public function test_formulir_booking_menampilkan_info_all_inclusive_dan_catatan_kapasitas(): void
    {
        $journey = $this->journeyBerharga();

        $this->actingAs(User::factory()->create())
            ->get(route('booking.buat', ['destination' => $journey->nama]))
            ->assertOk()
            ->assertDontSee('Daihatsu Taft')
            ->assertDontSee($journey->kendaraan->namaUntukTamu())
            ->assertDontSee('Alamat hotel')
            ->assertSee('All-inclusive')
            ->assertSee('memakai 1 kursi dan tidak dihitung sebagai tamu');
    }

    public function test_tombol_whatsapp_hero_dicatat_tanpa_bergantung_pada_filter_pencarian(): void
    {
        $this->get('/')->assertOk()->assertSee('data-catat-wa=', false)->assertDontSee('x-on:click="window.catatPertanyaan', false);

        $this->postJson(route('pertanyaan.simpan'), [
            'sumber' => 'cta_whatsapp',
            'pesan' => 'Halo Mistborne, saya ingin merencanakan trip privat ke dataran tinggi',
            'journey' => null,
        ])->assertNoContent();

        $this->assertDatabaseHas('pertanyaan_tamu', ['sumber' => 'cta_whatsapp', 'status' => 'baru']);
    }

    public function test_detail_journey_menampilkan_ketentuan_pembayaran_dan_itinerary_per_hari_tanpa_jam(): void
    {
        $journey = Perjalanan::query()->where('nama', 'DIENG HIGHLAND ESCAPE')->firstOrFail();
        $persen = Pengaturan::current()->persen_charge_ganti_jadwal;

        $this->get(route('journeys.show', $journey->slug))
            ->assertOk()
            ->assertSee('Ketentuan &amp; Pembayaran', false)
            ->assertSee('Bayar lunas')
            ->assertSee('Pembatalan gratis bila diajukan paling lambat 7 hari sebelum tanggal keberangkatan.')
            ->assertSee('DP minimal 30%')
            ->assertSee('paling lambat 3 hari sebelum keberangkatan')
            ->assertSee("biaya {$persen}% dari harga", false)
            ->assertDontSee('>H-7<', false)
            ->assertDontSee('>H-3<', false)
            ->assertSee('Hari 1')
            ->assertSee('Hari 3')
            ->assertDontSee('07.00')
            ->assertSee('jam berangkat fleksibel')
            ->assertSee('Jam Jemput')
            ->assertSee('Jam Jemput')
            ->assertSee('Maks. 4 orang')
            ->assertSee('3 Hari · 2 Malam')
            ->assertSee('Harga per booking')
            ->assertSee('bukan dihitung per orang');
    }

    public function test_journey_baku_minimal_dua_hari_satu_malam_dan_kapasitas_tamu_empat(): void
    {
        $baku = Perjalanan::query()->where('template_pesanan_kustom', false)->where('tone', '<>', 'custom')->get();

        $this->assertNotEmpty($baku);
        $baku->each(function (Perjalanan $perjalanan): void {
            $this->assertGreaterThanOrEqual(2, $perjalanan->durasi_hari, $perjalanan->nama);
            $this->assertSame(4, $perjalanan->maks_orang, $perjalanan->nama);
            $this->assertNotEmpty($perjalanan->itinerary, $perjalanan->nama);
        });
    }
}
