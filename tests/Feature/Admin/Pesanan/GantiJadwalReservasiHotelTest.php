<?php

namespace Tests\Feature\Admin\Pesanan;

use App\Enums\StatusPenginapan;
use App\Models\Hotel;
use App\Models\Perjalanan;
use App\Models\Pesanan;
use App\Models\User;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class GantiJadwalReservasiHotelTest extends TestCase
{
    private function pesananDenganHotel(string $statusReservasi): Pesanan
    {
        $journey = Perjalanan::query()->where('template_pesanan_kustom', false)->whereNotNull('harga')->firstOrFail();
        $mulai = Carbon::today()->addDays(30);

        return Pesanan::factory()->create([
            'perjalanan_id' => $journey->id,
            'nama_perjalanan' => $journey->nama,
            'tanggal_trip' => $mulai->toDateString(),
            'tanggal_selesai' => $mulai->toDateString(),
            'status_penginapan' => StatusPenginapan::Diurus,
            'hotel_id' => Hotel::factory()->create()->id,
            'hotel_check_in' => $mulai->toDateString(),
            'hotel_check_out' => $mulai->copy()->addDay()->toDateString(),
            'jumlah_kamar' => 1,
            'status_reservasi_hotel' => $statusReservasi,
            'kode_booking_hotel' => 'HTL-1',
        ]);
    }

    public function test_ganti_jadwal_memindahkan_tanggal_hotel_dan_meminta_konfirmasi_ulang(): void
    {
        $pesanan = $this->pesananDenganHotel('terkonfirmasi');
        $baru = Carbon::today()->addDays(50);

        $this->actingAs(User::factory()->create())
            ->post(route('admin.pesanan.ganti-jadwal', $pesanan), ['tanggal_trip' => $baru->toDateString()])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('sukses', fn (string $pesan): bool => str_contains($pesan, 'reservasi hotel ikut dipindah'));

        $pesanan->refresh();
        $this->assertSame($baru->toDateString(), $pesanan->hotel_check_in->toDateString());
        $this->assertSame($baru->copy()->addDay()->toDateString(), $pesanan->hotel_check_out->toDateString());
        $this->assertSame('diminta', $pesanan->status_reservasi_hotel->value);
    }

    public function test_reservasi_hotel_yang_dibatalkan_tidak_ikut_dipindah(): void
    {
        $pesanan = $this->pesananDenganHotel('dibatalkan');
        $tanggalHotelLama = $pesanan->hotel_check_in->toDateString();

        $this->actingAs(User::factory()->create())
            ->post(route('admin.pesanan.ganti-jadwal', $pesanan), ['tanggal_trip' => Carbon::today()->addDays(50)->toDateString()])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('sukses', fn (string $pesan): bool => ! str_contains($pesan, 'reservasi hotel'));

        $pesanan->refresh();
        $this->assertSame($tanggalHotelLama, $pesanan->hotel_check_in->toDateString());
        $this->assertSame('dibatalkan', $pesanan->status_reservasi_hotel->value);
    }
}
