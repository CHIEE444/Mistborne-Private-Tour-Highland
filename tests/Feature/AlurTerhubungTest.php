<?php

namespace Tests\Feature;

use App\Enums\StatusReservasiHotel;
use App\Layanan\KetersediaanKendaraanLayanan;
use App\Models\Hotel;
use App\Models\Perjalanan;
use App\Models\PermintaanPesanan;
use App\Models\Pesanan;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Satu alur utuh: tamu mengisi formulir permintaan → admin membuat pesanan → armada ditugaskan → hotel dipesankan,
 * dikonfirmasi, lalu tamu dikabari. Memastikan data antar modul saling terhubung, bukan hanya tiap modul sendiri.
 */
class AlurTerhubungTest extends TestCase
{
    public function test_dari_formulir_tamu_sampai_hotel_terkonfirmasi_dan_tamu_dikabari(): void
    {
        $admin = User::factory()->create();
        $journey = Perjalanan::query()->where('template_pesanan_kustom', false)->whereNotNull('harga')->where('tone', '<>', 'custom')->orderBy('urutan')->firstOrFail();
        $this->bukaTanggal($journey, Carbon::today()->addDays(20));
        $hotel = Hotel::factory()->create(['nama' => 'Hotel Alur', 'whatsapp' => '081808606086', 'jam_check_in' => '15:00', 'jam_check_out' => '11:00', 'tipe_kamar' => [['nama' => 'Family Room', 'kapasitas' => 4]]]);
        $journey->hotel()->attach($hotel);

        // 1. Tamu: tidak ada pilihan atau info mobil; mobil ditentukan sistem.
        $permintaan = PermintaanPesanan::query()->create(['token' => Str::random(64), 'whatsapp' => '081234567890', 'status' => 'menunggu_tamu', 'token_berlaku_sampai' => PermintaanPesanan::batasBerlakuBaru()]);
        $this->get(route('form-pesanan.tampil', $permintaan->token))->assertOk()->assertDontSee('Toyota Fortuner 2020');
        $this->post(route('form-pesanan.simpan', $permintaan->token), [
            'nama_lengkap' => 'Tamu Alur', 'whatsapp' => '081234567890', 'perjalanan_id' => $journey->id,
            'tanggal_trip' => Carbon::today()->addDays(20)->toDateString(), 'jumlah_wisatawan' => 4,
            'lokasi_pickup' => 'Stasiun Malang',
        ])->assertSessionHasNoErrors();

        // 2. Admin: permintaan menjadi pesanan dan mobil utama journey otomatis ditugaskan.
        $this->actingAs($admin)->post(route('admin.permintaan-pesanan.buat-pesanan', $permintaan))->assertSessionHasNoErrors();
        $pesanan = Pesanan::query()->latest('id')->firstOrFail();
        $this->assertNull($pesanan->kendaraan_pilihan);
        $this->assertSame($journey->kendaraan_id, $pesanan->kendaraan_id);
        $this->assertSame('diurus', $pesanan->status_penginapan->value);

        // 3. Armada: unit yang ditugaskan tetap muncul di daftar pilihan admin.
        $this->assertArrayHasKey($pesanan->kendaraan_id, app(KetersediaanKendaraanLayanan::class)->daftarTersedia($pesanan)->all());

        // 4. Hotel dipesankan → modal draft ke hotel + info tamu.
        $trip = $pesanan->tanggal_trip;
        $this->actingAs($admin)->put(route('admin.pesanan.hotel.simpan', $pesanan), [
            'hotel_id' => $hotel->id, 'hotel_check_in' => $trip->toDateString(), 'hotel_check_out' => ($pesanan->tanggal_selesai ?? $trip->copy()->addDay())->toDateString(),
            'jumlah_kamar' => 1, 'tipe_kamar' => 'Family Room', 'status_reservasi_hotel' => 'diminta',
        ])->assertSessionHasNoErrors()->assertSessionHas('modal_reservasi_hotel', 'diminta');

        // 5. Hotel setuju → status terkonfirmasi, lanjut tahap Bayar hotel; draft tamu (bukan modal) memuat jam check-in/out.
        $this->actingAs($admin)->put(route('admin.pesanan.hotel.konfirmasi', $pesanan), ['kode_booking_hotel' => 'ALUR-1'])->assertSessionMissing('modal_reservasi_hotel');
        $pesanan->refresh();
        $this->assertSame(StatusReservasiHotel::Terkonfirmasi, $pesanan->status_reservasi_hotel);
        $this->assertSame('Hotel Alur', $pesanan->nama_hotel);

        $this->actingAs($admin)->get(route('admin.pesanan.detail', $pesanan))
            ->assertOk()
            ->assertSee('3. Bayar hotel')
            ->assertSee('Info Penginapan All-inclusive')
            ->assertSee('mulai pukul 15:00 WIB')
            ->assertSee('ALUR-1');
    }
}
