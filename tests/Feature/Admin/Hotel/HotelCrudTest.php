<?php

namespace Tests\Feature\Admin\Hotel;

use App\Models\Hotel;
use App\Models\Perjalanan;
use App\Models\Pesanan;
use App\Models\User;
use InvalidArgumentException;
use Tests\TestCase;

class HotelCrudTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $timpa
     * @return array<string, mixed>
     */
    private function dataValid(array $timpa = []): array
    {
        return [
            'nama' => 'Bromo Permai 1',
            'bintang' => 4,
            'area' => 'Ngadisari, Bromo',
            'jarak_destinasi' => '± 1 jam berkendara ke Bromo',
            'alamat' => 'Jl. Cemorolawang, Ngadisari',
            'tautan_peta' => 'https://maps.google.com/?q=bromo+permai',
            'whatsapp' => '+62 812-3456-7890',
            'email' => 'reservasi@bromopermai.test',
            'nama_pic' => 'Bu Rina',
            'tarif_mulai' => 450000,
            'tarif_sampai' => 650000,
            'kapasitas_kamar' => 2,
            'tersedia_extra_bed' => '1',
            'fasilitas' => ['sarapan', 'air_panas'],
            'jam_check_in' => '14:00',
            'jam_check_out' => '12:00',
            'kebijakan_pembatalan' => 'Gratis batal sampai H-3.',
            'aktif' => '1',
            ...$timpa,
        ];
    }

    public function test_kapasitas_tipe_kamar_tersimpan_sebagai_angka(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.hotel.simpan'), $this->dataValid(['tipe_kamar' => [['nama' => 'Deluxe', 'kapasitas' => '2']]]))
            ->assertSessionHasNoErrors();

        $this->assertSame([['nama' => 'Deluxe', 'kapasitas' => 2]], Hotel::query()->firstOrFail()->tipe_kamar);
    }

    public function test_tamu_tidak_bisa_mengakses_menu_hotel(): void
    {
        $this->get(route('admin.hotel.indeks'))->assertRedirect(route('admin.autentikasi.masuk'));
    }

    public function test_admin_melihat_daftar_mencari_dan_memfilter(): void
    {
        $admin = User::factory()->create();
        $journey = Perjalanan::factory()->create(['nama' => 'JOURNEY FILTER']);
        $hotelA = Hotel::factory()->create(['nama' => 'Hotel Alfa', 'area' => 'Dieng']);
        Hotel::factory()->create(['nama' => 'Hotel Beta', 'area' => 'Bromo']);
        Hotel::factory()->nonaktif()->create(['nama' => 'Hotel Gamma']);
        $hotelA->perjalanan()->attach($journey->id);

        $this->actingAs($admin)->get(route('admin.hotel.indeks'))->assertOk()->assertSee('Hotel Alfa')->assertSee('Hotel Beta')->assertSee('Hotel Gamma');
        $this->actingAs($admin)->get(route('admin.hotel.indeks', ['cari' => 'Bromo']))->assertSee('Hotel Beta')->assertDontSee('Hotel Alfa');
        $this->actingAs($admin)->get(route('admin.hotel.indeks', ['status' => 'nonaktif']))->assertSee('Hotel Gamma')->assertDontSee('Hotel Alfa');
        $this->actingAs($admin)->get(route('admin.hotel.indeks', ['journey' => $journey->id]))->assertSee('Hotel Alfa')->assertDontSee('Hotel Beta');
    }

    public function test_admin_menambah_hotel_dengan_semua_data_dan_journey(): void
    {
        $admin = User::factory()->create();
        $journey = Perjalanan::factory()->create();

        $this->actingAs($admin)->post(route('admin.hotel.simpan'), $this->dataValid(['perjalanan_ids' => [$journey->id]]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.hotel.indeks'));

        $hotel = Hotel::query()->where('nama', 'Bromo Permai 1')->firstOrFail();
        $this->assertSame('081234567890', $hotel->whatsapp);
        $this->assertTrue($hotel->tersedia_extra_bed);
        $this->assertSame(['sarapan', 'air_panas'], $hotel->fasilitas);
        $this->assertSame('14:00', $hotel->jamCheckIn());
        $this->assertSame([$journey->id], $hotel->perjalanan()->pluck('perjalanan.id')->all());
        $this->assertSame('Rp 450.000 – Rp 650.000', $hotel->rentangTarif());
    }

    public function test_isian_wajib_dan_format_divalidasi(): void
    {
        $admin = User::factory()->create();

        foreach (['nama', 'bintang', 'area', 'jarak_destinasi', 'jam_check_in', 'jam_check_out'] as $kolom) {
            $this->actingAs($admin)->post(route('admin.hotel.simpan'), $this->dataValid([$kolom => null]))->assertSessionHasErrors($kolom);
        }

        $this->actingAs($admin)->post(route('admin.hotel.simpan'), $this->dataValid([
            'nama' => '<b>',
            'bintang' => 9,
            'tautan_peta' => 'bukan-url',
            'whatsapp' => '12345',
            'email' => 'salah',
            'tarif_mulai' => 1000,
            'tarif_sampai' => 400000,
            'kapasitas_kamar' => 0,
            'jam_check_in' => '25:99',
            'fasilitas' => ['teleportasi'],
            'perjalanan_ids' => [999999],
        ]))->assertSessionHasErrors(['nama', 'bintang', 'tautan_peta', 'whatsapp', 'email', 'tarif_mulai', 'kapasitas_kamar', 'jam_check_in', 'fasilitas.0', 'perjalanan_ids.0']);

        $this->actingAs($admin)->post(route('admin.hotel.simpan'), $this->dataValid(['tarif_mulai' => 600000, 'tarif_sampai' => 500000]))
            ->assertSessionHasErrors('tarif_sampai');

        $this->assertDatabaseCount('hotel', 0);
    }

    public function test_kontak_tarif_dan_catatan_boleh_kosong(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.hotel.simpan'), $this->dataValid([
            'whatsapp' => '', 'email' => '', 'tarif_mulai' => null, 'tarif_sampai' => null, 'tautan_peta' => null, 'fasilitas' => [],
        ]))->assertSessionHasNoErrors();

        $hotel = Hotel::query()->firstOrFail();
        $this->assertNull($hotel->whatsapp);
        $this->assertNull($hotel->rentangTarif());
    }

    public function test_nama_hotel_harus_unik_tetapi_boleh_dipakai_lagi_setelah_dihapus(): void
    {
        $admin = User::factory()->create();
        $lama = Hotel::factory()->create(['nama' => 'Bromo Permai 1']);

        $this->actingAs($admin)->post(route('admin.hotel.simpan'), $this->dataValid())->assertSessionHasErrors('nama');

        $lama->delete();

        $this->actingAs($admin)->post(route('admin.hotel.simpan'), $this->dataValid())->assertSessionHasNoErrors();
    }

    public function test_admin_mengubah_hotel_dan_menyinkronkan_journey(): void
    {
        $admin = User::factory()->create();
        $hotel = Hotel::factory()->create(['nama' => 'Bromo Permai 1']);
        $lama = Perjalanan::factory()->create();
        $baru = Perjalanan::factory()->create();
        $hotel->perjalanan()->attach($lama->id);

        $this->actingAs($admin)->get(route('admin.hotel.ubah', $hotel))->assertOk()->assertSee('Bromo Permai 1');

        $this->actingAs($admin)->put(route('admin.hotel.perbarui', $hotel), $this->dataValid(['nama' => 'Bromo Permai 1', 'bintang' => 4, 'perjalanan_ids' => [$baru->id]]))
            ->assertSessionHasNoErrors();

        $hotel->refresh();
        $this->assertSame(4, $hotel->bintang);
        $this->assertSame([$baru->id], $hotel->perjalanan()->pluck('perjalanan.id')->all());
    }

    public function test_hotel_dengan_reservasi_aktif_tidak_bisa_dihapus(): void
    {
        $admin = User::factory()->create();
        $hotel = Hotel::factory()->create();
        Pesanan::factory()->create([
            'hotel_id' => $hotel->id,
            'hotel_check_in' => now()->addDays(10)->toDateString(),
            'hotel_check_out' => now()->addDays(11)->toDateString(),
            'status_penginapan' => 'dipesankan',
        ]);

        $this->actingAs($admin)->delete(route('admin.hotel.hapus', $hotel))->assertSessionHasErrors('hotel');
        $this->assertNotSoftDeleted('hotel', ['id' => $hotel->id]);
    }

    public function test_hotel_tanpa_reservasi_aktif_bisa_dihapus(): void
    {
        $admin = User::factory()->create();
        $hotel = Hotel::factory()->create();

        $this->actingAs($admin)->delete(route('admin.hotel.hapus', $hotel))->assertRedirect(route('admin.hotel.indeks'));
        $this->assertSoftDeleted('hotel', ['id' => $hotel->id]);
    }

    public function test_menu_hotel_ada_di_sidebar(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->get(route('admin.dashboard.indeks'))->assertSee(route('admin.hotel.indeks'), false);
    }

    public function test_hotel_di_bawah_bintang_empat_atau_di_atas_lima_ditolak_form_maupun_model(): void
    {
        $admin = User::factory()->create();

        foreach ([1, 3, 6] as $bintang) {
            $this->actingAs($admin)->post(route('admin.hotel.simpan'), $this->dataValid(['bintang' => $bintang]))->assertSessionHasErrors('bintang');
        }
        $this->assertSame(0, Hotel::query()->count());

        $this->assertSame(4, (new Hotel)->bintang);

        $hotel = Hotel::factory()->create();
        $hotel->bintang = 3;
        $this->expectException(InvalidArgumentException::class);
        $hotel->save();
    }

    public function test_satu_journey_hanya_boleh_punya_satu_hotel_aktif(): void
    {
        $admin = User::factory()->create();
        $journey = Perjalanan::factory()->create(['nama' => 'JOURNEY SATU HOTEL']);
        $pertama = Hotel::factory()->create(['nama' => 'Hotel Pertama']);
        $pertama->perjalanan()->sync([$journey->id]);

        $this->actingAs($admin)->post(route('admin.hotel.simpan'), $this->dataValid(['nama' => 'Hotel Kedua', 'perjalanan_ids' => [$journey->id]]))
            ->assertSessionHasErrors('perjalanan_ids');
        $this->assertNull(Hotel::query()->where('nama', 'Hotel Kedua')->first());

        $this->actingAs($admin)->post(route('admin.hotel.simpan'), $this->dataValid(['nama' => 'Hotel Kedua', 'aktif' => '0', 'perjalanan_ids' => [$journey->id]]))
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)->put(route('admin.hotel.perbarui', $pertama), $this->dataValid(['nama' => 'Hotel Pertama', 'perjalanan_ids' => [$journey->id]]))
            ->assertSessionHasNoErrors();
    }

    public function test_kapasitas_standar_per_kamar_mengikuti_kapasitas_journey_terbesar(): void
    {
        $admin = User::factory()->create();
        $empat = Perjalanan::factory()->create(['maks_orang' => 4]);
        $enam = Perjalanan::factory()->create(['maks_orang' => 6]);

        $this->actingAs($admin)->post(route('admin.hotel.simpan'), $this->dataValid(['kapasitas_kamar' => 2, 'perjalanan_ids' => [$empat->id]]))->assertSessionHasNoErrors();
        $hotel = Hotel::query()->where('nama', 'Bromo Permai 1')->firstOrFail();
        $this->assertSame(4, $hotel->kapasitas_kamar);

        $this->actingAs($admin)->put(route('admin.hotel.perbarui', $hotel), $this->dataValid(['perjalanan_ids' => [$empat->id, $enam->id]]))->assertSessionHasNoErrors();
        $this->assertSame(6, $hotel->fresh()->kapasitas_kamar);
    }

    public function test_daftar_hotel_memperingatkan_journey_yang_belum_punya_hotel_aktif(): void
    {
        $admin = User::factory()->create();
        $tanpa = Perjalanan::factory()->create(['nama' => 'JOURNEY TANPA HOTEL']);
        $dengan = Perjalanan::factory()->create(['nama' => 'JOURNEY DENGAN HOTEL']);
        Hotel::factory()->create()->perjalanan()->attach($dengan->id);

        $this->actingAs($admin)->get(route('admin.hotel.indeks'))
            ->assertOk()
            ->assertSee('Journey ini belum punya hotel aktif')
            ->assertSee('JOURNEY TANPA HOTEL')
            ->assertDontSee('JOURNEY DENGAN HOTEL, ')
            ->assertSee($tanpa->nama);
    }
}
