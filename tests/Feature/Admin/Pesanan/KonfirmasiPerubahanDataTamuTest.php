<?php

namespace Tests\Feature\Admin\Pesanan;

use App\Models\Pesanan;
use App\Models\User;
use Tests\TestCase;

class KonfirmasiPerubahanDataTamuTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $timpa
     * @return array<string, mixed>
     */
    private function dataFormulir(Pesanan $pesanan, array $timpa = []): array
    {
        return [
            'nama_lengkap' => $pesanan->nama_lengkap,
            'whatsapp' => '0'.substr($pesanan->whatsapp, 2),
            'lokasi_pickup' => $pesanan->lokasi_pickup,
            'jumlah_wisatawan' => $pesanan->jumlah_wisatawan,
            'status_penginapan' => $pesanan->status_penginapan->value,
            'nama_hotel' => $pesanan->nama_hotel,
            'harga' => $pesanan->harga,
            ...$timpa,
        ];
    }

    public function test_perubahan_data_tamu_ditolak_tanpa_konfirmasi_ke_tamu(): void
    {
        $admin = User::factory()->create();
        $pesanan = Pesanan::factory()->create(['lokasi_pickup' => 'Lokasi Lama']);

        $this->actingAs($admin)
            ->put(route('admin.pesanan.perbarui', $pesanan), $this->dataFormulir($pesanan, ['lokasi_pickup' => 'Lokasi Baru']))
            ->assertSessionHasErrors('konfirmasi_tamu');

        $this->assertSame('Lokasi Lama', $pesanan->fresh()->lokasi_pickup);
    }

    public function test_perubahan_dengan_konfirmasi_tersimpan_dan_pesan_konfirmasi_ke_tamu_memuat_data_baru(): void
    {
        $admin = User::factory()->create();
        $pesanan = Pesanan::factory()->create(['lokasi_pickup' => 'Lokasi Lama', 'jumlah_wisatawan' => 3]);

        $this->actingAs($admin)
            ->followingRedirects()
            ->put(route('admin.pesanan.perbarui', $pesanan), $this->dataFormulir($pesanan, ['lokasi_pickup' => 'Lokasi Baru', 'jumlah_wisatawan' => 2, 'konfirmasi_tamu' => 1]))
            ->assertOk()
            ->assertSee('Konfirmasi Perubahan Data')
            ->assertSee('Lokasi jemput: Lokasi Lama → Lokasi Baru')
            ->assertSee('Jumlah peserta: 3 → 2');

        $this->assertSame('Lokasi Baru', $pesanan->fresh()->lokasi_pickup);
    }

    public function test_simpan_tanpa_perubahan_data_tidak_meminta_konfirmasi(): void
    {
        $admin = User::factory()->create();
        $pesanan = Pesanan::factory()->create();

        $this->actingAs($admin)
            ->put(route('admin.pesanan.perbarui', $pesanan), $this->dataFormulir($pesanan))
            ->assertSessionHasNoErrors();
    }

    public function test_halaman_ubah_memuat_modal_konfirmasi_perubahan(): void
    {
        $admin = User::factory()->create();
        $pesanan = Pesanan::factory()->create();

        $this->actingAs($admin)->get(route('admin.pesanan.ubah', $pesanan))
            ->assertOk()
            ->assertSee('konfirmasi-perubahan-data')
            ->assertSee('Saya sudah mengonfirmasi perubahan ini ke tamu');
    }
}
