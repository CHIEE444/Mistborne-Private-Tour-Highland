<?php

namespace Tests\Feature\Admin;

use App\Enums\BookingStatus;
use App\Models\PersonelOperasional;
use App\Models\Pesanan;
use App\Models\User;
use Tests\Support\DataGuide;
use Tests\TestCase;

class PersonelOperasionalTest extends TestCase
{
    public function test_nomor_whatsapp_personel_harus_valid_dan_format_08_dinormalisasi(): void
    {
        $admin = User::factory()->create(['jabatan' => 'admin']);
        $data = [
            ...DataGuide::form(),
            'nama' => 'Guide Uji',
            'peran' => 'guide',
            'whatsapp' => '08abc12345678',
            'status_operasional' => 'siap',
        ];

        $this->actingAs($admin)
            ->from(route('admin.guide.indeks'))
            ->post(route('admin.guide.simpan'), $data)
            ->assertRedirect(route('admin.guide.indeks'))
            ->assertSessionHasErrors('whatsapp');

        $data['whatsapp'] = '081234567890';

        $this->actingAs($admin)
            ->post(route('admin.guide.simpan'), $data)
            ->assertRedirect(route('admin.guide.indeks'));

        $this->assertDatabaseHas('personel_operasional', [
            'nama' => 'Guide Uji',
            'whatsapp' => '6281234567890',
        ]);
    }

    public function test_trip_yang_sedang_berlangsung_mendapat_prioritas_status_di_atas_tugas_mendatang(): void
    {
        $admin = User::factory()->create(['jabatan' => 'admin']);
        $guide = PersonelOperasional::query()->create([...DataGuide::sim(),
            'nama' => 'Guide Uji',
            'peran' => 'guide',
            'status_operasional' => 'siap',
            'aktif' => true,
        ]);

        Pesanan::factory()->create([
            'nama_perjalanan' => 'Trip berlangsung',
            'tanggal_trip' => today(),
            'tanggal_selesai' => today(),
            'guide_personel_id' => $guide->id,
            'status_konfirmasi_guide' => 'diterima',
            'status' => BookingStatus::Payment,
        ]);
        Pesanan::factory()->create([
            'nama_perjalanan' => 'Trip mendatang',
            'tanggal_trip' => today()->addDays(5),
            'tanggal_selesai' => today()->addDays(5),
            'guide_personel_id' => $guide->id,
            'status_konfirmasi_guide' => 'menunggu',
            'status' => BookingStatus::Dihubungi,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.guide.indeks'))
            ->assertOk()
            ->assertSee('Sedang bertugas')
            ->assertSee('Bertugas')
            ->assertSee('Tidak bertugas')
            ->assertSee('Menunggu konfirmasi')
            ->assertSee('Trip mendatang');
    }

    public function test_admin_dapat_memperbarui_draft_personel_dan_nomor_whatsappnya(): void
    {
        $admin = User::factory()->create(['jabatan' => 'admin']);
        $guide = PersonelOperasional::query()->create([...DataGuide::sim(),
            'nama' => 'Guide Lama',
            'peran' => 'guide',
            'whatsapp' => '628111111111',
            'status_operasional' => 'siap',
            'aktif' => true,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.guide.indeks'))
            ->put(route('admin.guide.perbarui', $guide), [...DataGuide::form(),
                'edit_personel_id' => $guide->id,
                'personel' => [
                    $guide->id => [
                        ...DataGuide::form(),
                        'nama' => 'Guide Baru',
                        'peran' => 'guide',
                        'whatsapp' => '081234567890',
                        'status_operasional' => 'siap',
                        'aktif' => '1',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.guide.indeks'));

        $this->assertDatabaseHas('personel_operasional', [
            'id' => $guide->id,
            'nama' => 'Guide Baru',
            'whatsapp' => '6281234567890',
            'aktif' => true,
        ]);
    }

    public function test_personel_dengan_pesanan_aktif_tidak_dapat_dihapus(): void
    {
        $admin = User::factory()->create(['jabatan' => 'admin']);
        $guide = PersonelOperasional::query()->create([...DataGuide::sim(),
            'nama' => 'Guide Bertugas',
            'peran' => 'guide',
            'status_operasional' => 'siap',
            'aktif' => true,
        ]);
        Pesanan::factory()->create([
            'guide_personel_id' => $guide->id,
            'status' => BookingStatus::Payment,
            'tanggal_trip' => today()->addDays(5),
        ]);

        $this->actingAs($admin)
            ->from(route('admin.guide.indeks'))
            ->delete(route('admin.guide.hapus', $guide))
            ->assertRedirect(route('admin.guide.indeks'))
            ->assertSessionHas('gagal');

        $this->assertDatabaseHas('personel_operasional', ['id' => $guide->id]);
    }

    public function test_personel_dengan_pesanan_aktif_tidak_dapat_dinonaktifkan(): void
    {
        $admin = User::factory()->create(['jabatan' => 'admin']);
        $guide = PersonelOperasional::query()->create([...DataGuide::sim(),
            'nama' => 'Guide Bertugas',
            'peran' => 'guide',
            'status_operasional' => 'siap',
            'aktif' => true,
        ]);
        Pesanan::factory()->create([
            'guide_personel_id' => $guide->id,
            'status' => BookingStatus::Payment,
            'tanggal_trip' => today()->addDays(5),
        ]);

        $this->actingAs($admin)
            ->from(route('admin.guide.indeks'))
            ->put(route('admin.guide.perbarui', $guide), [...DataGuide::form(),
                'edit_personel_id' => $guide->id,
                'personel' => [
                    $guide->id => [
                        ...DataGuide::form(),
                        'nama' => $guide->nama,
                        'peran' => 'guide',
                        'status_operasional' => 'siap',
                        'aktif' => '0',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.guide.indeks'))
            ->assertSessionHasErrors('personel.'.$guide->id.'.aktif');

        $this->assertDatabaseHas('personel_operasional', ['id' => $guide->id, 'aktif' => true]);
    }

    public function test_nomor_whatsapp_personel_menerima_awalan_62_dan_pemisah_tetapi_menolak_huruf(): void
    {
        $admin = User::factory()->create(['jabatan' => 'admin']);
        $data = [...DataGuide::form(), 'nama' => 'Guide Nomor', 'peran' => 'guide', 'status_operasional' => 'siap', 'aktif' => '1'];

        $this->actingAs($admin)->post(route('admin.guide.simpan'), $data + ['whatsapp' => '+62 812-3456-7890'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('personel_operasional', ['nama' => 'Guide Nomor', 'whatsapp' => '6281234567890']);

        $this->actingAs($admin)->post(route('admin.guide.simpan'), [...DataGuide::form(), 'nama' => 'Guide Huruf'] + $data + ['whatsapp' => '08123abc4567'])
            ->assertSessionHasErrors('whatsapp');
        $this->assertDatabaseMissing('personel_operasional', ['nama' => 'Guide Huruf']);

        $guide = PersonelOperasional::query()->where('nama', 'Guide Nomor')->firstOrFail();
        $this->actingAs($admin)->put(route('admin.guide.perbarui', $guide), [...DataGuide::form(),
            'edit_personel_id' => $guide->id,
            'personel' => [$guide->id => [...DataGuide::form(), 'nama' => 'Guide Nomor', 'peran' => 'guide', 'whatsapp' => '0812 9999 8888', 'status_operasional' => 'siap', 'aktif' => '1']],
        ])->assertSessionHasNoErrors();
        $this->assertSame('6281299998888', $guide->fresh()->whatsapp);
    }
}
