<?php

namespace Tests\Feature\Admin\Pesanan;

use App\Enums\BookingStatus;
use App\Models\Kendaraan;
use App\Models\Perjalanan;
use App\Models\Pesanan;
use App\Models\User;
use Tests\TestCase;

class HalamanPesananSemuaKondisiTest extends TestCase
{
    public function test_halaman_admin_pesanan_tampil_untuk_semua_status_penginapan_dan_durasi(): void
    {
        $admin = User::factory()->create(['jabatan' => 'admin']);
        $kendaraan = Kendaraan::factory()->create();

        foreach (BookingStatus::cases() as $status) {
            foreach (['sudah', 'belum', 'dipesankan', 'diurus'] as $penginapan) {
                foreach ([null, 2, 3] as $durasi) {
                    $pesanan = Pesanan::factory()->create([
                        'perjalanan_id' => Perjalanan::factory()->create(['durasi_hari' => $durasi]),
                        'status' => $status,
                        'status_penginapan' => $penginapan,
                        'nama_hotel' => $penginapan === 'belum' ? null : 'Hotel Uji',
                        'alamat_hotel' => $penginapan === 'sudah' ? 'Jl. Uji No. 1' : null,
                        'kendaraan_pilihan' => $durasi === 3 ? 'Jeep klasik (petualangan dataran tinggi) — Toyota Land Cruiser FJ40' : null,
                        'kendaraan_id' => $durasi === 2 ? $kendaraan->id : null,
                        'waktu_pickup' => $durasi ? '14:00:00' : null,
                        'catatan_admin' => $durasi === 2 ? 'Catatan uji' : null,
                    ]);

                    $this->actingAs($admin)->get(route('admin.pesanan.detail', $pesanan))->assertOk();
                    $this->actingAs($admin)->get(route('admin.pesanan.ubah', $pesanan))->assertOk()->assertSee('value="'.($durasi ? '14:00' : '').'"', false);
                }
            }
        }

        $this->actingAs($admin)->get(route('admin.pesanan.indeks'))->assertOk();
        $this->actingAs($admin)->get(route('admin.permintaan-pesanan.indeks'))->assertOk();
    }
}
