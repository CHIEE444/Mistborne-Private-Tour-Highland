<?php

namespace Tests\Feature\Admin\PesananKustom;

use App\Enums\StatusPesananKustom;
use App\Models\JadwalKeberangkatan;
use App\Models\Perjalanan;
use App\Models\PersonelOperasional;
use App\Models\Pesanan;
use App\Models\PesananKustom;
use App\Models\User;
use Tests\Support\DataGuide;
use Tests\TestCase;

class PesananKustomTest extends TestCase
{
    public function test_tamu_tidak_bisa_mengakses(): void
    {
        $this->get(route('admin.pesanan-kustom.indeks'))->assertRedirect(route('admin.autentikasi.masuk'));
    }

    public function test_admin_bisa_melihat_daftar(): void
    {
        $user = User::factory()->create();
        PesananKustom::factory()->create(['nama' => 'Rendra Kurnia']);

        $this->actingAs($user)
            ->get(route('admin.pesanan-kustom.indeks'))
            ->assertOk()
            ->assertSee('Rendra Kurnia');
    }

    public function test_admin_bisa_membuat_pesanan_kustom_manual(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.pesanan-kustom.simpan'), [
            'nama' => 'Nadia Putri',
            'whatsapp' => '081234567890',
            'destinasi_diinginkan' => 'Dieng dan Bromo',
            'tanggal_diinginkan' => now()->addDays(10)->toDateString(),
            'durasi_hari' => 3,
            'jumlah_orang' => 4,
            'lokasi_penjemputan' => 'Wonosobo',
            'status_penginapan' => 'sudah',
            'nama_hotel' => 'Hotel Uji Penginapan',
            'minat_perjalanan' => ['alam', 'fotografi'],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('pesanan_kustom', [
            'nama' => 'Nadia Putri',
            'whatsapp' => '6281234567890',
            'destinasi_diinginkan' => 'Dieng dan Bromo',
        ]);
        $this->assertSame(['alam', 'fotografi'], PesananKustom::where('nama', 'Nadia Putri')->firstOrFail()->minat_perjalanan);
    }

    public function test_kode_tampilan_berformat_cst(): void
    {
        $kustom = PesananKustom::factory()->create();

        $this->assertMatchesRegularExpression('/^CST-\d{6}-\d{2}$/', $kustom->kodeTampilan());
    }

    public function test_panel_detail_tampil_saat_item_dipilih(): void
    {
        $user = User::factory()->create();
        $kustom = PesananKustom::factory()->create(['destinasi_diinginkan' => 'Overland Bromo-Ijen']);

        $this->actingAs($user)
            ->get(route('admin.pesanan-kustom.detail', $kustom->id))
            ->assertOk()
            ->assertSee('Overland Bromo-Ijen');
    }

    public function test_admin_bisa_memperbarui_data_permintaan(): void
    {
        $user = User::factory()->create();
        $kustom = PesananKustom::factory()->create();

        $response = $this->actingAs($user)->put(route('admin.pesanan-kustom.perbarui', $kustom), [
            'nama' => $kustom->nama,
            'whatsapp' => $kustom->whatsapp,
            'destinasi_diinginkan' => $kustom->destinasi_diinginkan,
            'tanggal_fleksibel' => 1,
            'durasi_hari' => 3,
            'lokasi_penjemputan' => 'Malang',
            'status_penginapan' => 'sudah',
            'nama_hotel' => 'Hotel Uji Penginapan',
            'jumlah_orang' => $kustom->jumlah_orang,
            'catatan' => 'Tamu minta tambahan sarapan',
        ]);

        $response->assertRedirect()->assertSessionHasNoErrors();
        $kustom->refresh();
        $this->assertSame(3, $kustom->durasi_hari);
        $this->assertSame('Tamu minta tambahan sarapan', $kustom->catatan);
    }

    public function test_admin_bisa_memperbarui_tindak_lanjut(): void
    {
        $user = User::factory()->create();
        $kustom = PesananKustom::factory()->create();

        $response = $this->actingAs($user)->put(route('admin.pesanan-kustom.perbarui-tindak-lanjut', $kustom), [
            'status' => 'diproses',
            'harga_final' => 7_500_000,
            'nama_pendamping' => 'Arya Swastika',
            'catatan_admin' => 'Sudah DP 50%',
        ]);

        $response->assertRedirect()->assertSessionHasNoErrors();
        $kustom->refresh();
        $this->assertSame(7_500_000, $kustom->harga_final);
        $this->assertSame(StatusPesananKustom::Diproses, $kustom->status);
    }

    public function test_tindak_lanjut_memilih_guide_dari_daftar_guide_aktif_dengan_catatan_guide(): void
    {
        PersonelOperasional::query()->create([...DataGuide::sim(), 'nama' => 'Guide Aktif Kustom', 'peran' => 'guide', 'status_operasional' => 'siap', 'aktif' => true]);
        PersonelOperasional::query()->create([...DataGuide::sim(), 'nama' => 'Guide Nonaktif Kustom', 'peran' => 'guide', 'status_operasional' => 'siap', 'aktif' => false]);
        $kustom = PesananKustom::factory()->create(['nama_pendamping' => 'Nama Lama Tersimpan']);

        $this->actingAs(User::factory()->create())->get(route('admin.pesanan-kustom.detail', $kustom))
            ->assertOk()
            ->assertSee('<option value="Guide Aktif Kustom"', false)
            ->assertSee('<option value="Nama Lama Tersimpan"', false)
            ->assertDontSee('Guide Nonaktif Kustom')
            ->assertSee('Catatan guide')
            ->assertDontSee('PIC internal');
    }

    public function test_harga_total_dihitung_per_orang(): void
    {
        $kustom = PesananKustom::factory()->create(['harga_final' => 7_500_000, 'jumlah_orang' => 5]);

        $this->assertSame(37_500_000, $kustom->hargaTotal());
    }

    public function test_admin_bisa_mengarsipkan_dan_memulihkan(): void
    {
        $user = User::factory()->create();
        $kustom = PesananKustom::factory()->create();

        $this->actingAs($user)->delete(route('admin.pesanan-kustom.arsipkan', $kustom));
        $this->assertSoftDeleted('pesanan_kustom', ['id' => $kustom->id]);

        $this->actingAs($user)->patch(route('admin.pesanan-kustom.pulihkan', $kustom->id));
        $this->assertDatabaseHas('pesanan_kustom', ['id' => $kustom->id, 'deleted_at' => null]);
    }

    public function test_admin_bisa_menghapus_permanen_dari_daftar_termasuk_dari_arsip(): void
    {
        $user = User::factory()->create();
        $pesananResmi = Pesanan::factory()->create();
        $kustom = PesananKustom::factory()->create(['pesanan_id' => $pesananResmi->id]);
        $arsip = PesananKustom::factory()->create();
        $arsip->delete();

        $this->actingAs($user)->get(route('admin.pesanan-kustom.indeks'))->assertSee(route('admin.pesanan-kustom.hapus', $kustom->id), false);

        $this->actingAs($user)->delete(route('admin.pesanan-kustom.hapus', $kustom->id))->assertRedirect(route('admin.pesanan-kustom.indeks'));
        $this->actingAs($user)->delete(route('admin.pesanan-kustom.hapus', $arsip->id));

        $this->assertDatabaseMissing('pesanan_kustom', ['id' => $kustom->id]);
        $this->assertDatabaseMissing('pesanan_kustom', ['id' => $arsip->id]);
        $this->assertModelExists($pesananResmi);
    }

    public function test_arsip_tab_hanya_menampilkan_yang_dihapus(): void
    {
        $user = User::factory()->create();
        $aktif = PesananKustom::factory()->create(['nama' => 'Masih Aktif']);
        $arsip = PesananKustom::factory()->create(['nama' => 'Sudah Arsip']);
        $arsip->delete();

        $response = $this->actingAs($user)->get(route('admin.pesanan-kustom.indeks', ['status' => 'arsip']));

        $response->assertSee('Sudah Arsip')->assertDontSee('Masih Aktif');
    }

    public function test_jadikan_pesanan_resmi(): void
    {
        $user = User::factory()->create();
        $journey = Perjalanan::factory()->create();
        $jadwal = JadwalKeberangkatan::factory()->create(['perjalanan_id' => $journey->id]);
        $kustom = PesananKustom::factory()->create([
            'nama' => 'Hendra Wijaya',
            'whatsapp' => '6281234567890',
            'jumlah_orang' => 4,
            'harga_final' => 1_000_000,
            'status' => StatusPesananKustom::Diproses,
            'rencana_perjalanan' => 'Hari 1 Dieng, Hari 2 Bromo',
            'tanggal_fleksibel' => false,
            'tanggal_diinginkan' => $jadwal->tanggal,
        ]);

        $this->actingAs($user)->post(route('admin.pesanan-kustom.penawaran-terkirim', $kustom))
            ->assertSessionHasNoErrors();

        $response = $this->actingAs($user)->post(route('admin.pesanan-kustom.jadikan-resmi', $kustom), [
            'perjalanan_id' => $journey->id,
            'tamu_menyetujui' => '1',
            'tanggal_trip' => $jadwal->tanggal->toDateString(),
            'lokasi_pickup' => 'Hotel Malang',
            'status_penginapan' => 'sudah',
            'nama_hotel' => 'Hotel Uji Penginapan',
        ]);

        $response->assertRedirect()->assertSessionHasNoErrors();

        $pesanan = Pesanan::where('nama_lengkap', 'Hendra Wijaya')->first();
        $this->assertNotNull($pesanan);
        $this->assertSame(4, $pesanan->jumlah_wisatawan);
        $this->assertSame(4_000_000, $pesanan->harga);
        $this->assertSame('kustom', $pesanan->sumber->value);
        $this->assertSame('sudah', $pesanan->status_penginapan->value);
        $this->assertSame('Hotel Uji Penginapan', $pesanan->nama_hotel);

        $this->assertSame(StatusPesananKustom::Disetujui, $kustom->fresh()->status);
    }

    public function test_jadikan_pesanan_resmi_mewajibkan_status_penginapan_dan_membolehkan_belum_punya(): void
    {
        $user = User::factory()->create();
        $journey = Perjalanan::factory()->create();
        $jadwal = JadwalKeberangkatan::factory()->create(['perjalanan_id' => $journey->id]);
        $kustom = PesananKustom::factory()->create([
            'nama' => 'Tamu Tanpa Hotel',
            'jumlah_orang' => 2,
            'harga_final' => 500_000,
            'status' => StatusPesananKustom::Diproses,
            'rencana_perjalanan' => 'Hari 1 Dieng',
            'tanggal_fleksibel' => false,
            'tanggal_diinginkan' => $jadwal->tanggal,
            'status_penginapan' => 'belum',
            'nama_hotel' => null,
        ]);
        $this->actingAs($user)->post(route('admin.pesanan-kustom.penawaran-terkirim', $kustom))->assertSessionHasNoErrors();

        $dasar = [
            'perjalanan_id' => $journey->id,
            'tamu_menyetujui' => '1',
            'tanggal_trip' => $jadwal->tanggal->toDateString(),
            'lokasi_pickup' => 'Stasiun Malang',
        ];

        $this->actingAs($user)->post(route('admin.pesanan-kustom.jadikan-resmi', $kustom), $dasar)
            ->assertSessionHasErrors('status_penginapan');
        $this->actingAs($user)->post(route('admin.pesanan-kustom.jadikan-resmi', $kustom), [...$dasar, 'status_penginapan' => 'sudah'])
            ->assertSessionHasErrors('nama_hotel');

        $this->actingAs($user)->post(route('admin.pesanan-kustom.jadikan-resmi', $kustom), [...$dasar, 'status_penginapan' => 'belum'])
            ->assertSessionHasNoErrors();

        $pesanan = Pesanan::where('nama_lengkap', 'Tamu Tanpa Hotel')->firstOrFail();
        $this->assertSame('belum', $pesanan->status_penginapan->value);
        $this->assertNull($pesanan->nama_hotel);
    }

    public function test_data_permintaan_dengan_tanggal_lewat_tetap_bisa_diubah_jika_tanggal_tidak_berubah(): void
    {
        $user = User::factory()->create();
        $kustom = PesananKustom::factory()->create(['tanggal_diinginkan' => today()->subDays(3), 'tanggal_fleksibel' => false]);
        $data = [
            'nama' => $kustom->nama,
            'whatsapp' => $kustom->whatsapp,
            'destinasi_diinginkan' => $kustom->destinasi_diinginkan,
            'durasi_hari' => 3,
            'lokasi_penjemputan' => 'Malang',
            'status_penginapan' => 'sudah',
            'nama_hotel' => 'Hotel Uji Penginapan',
            'jumlah_orang' => $kustom->jumlah_orang,
            'catatan' => 'Catatan baru',
        ];

        $this->actingAs($user)->put(route('admin.pesanan-kustom.perbarui', $kustom), $data + ['tanggal_diinginkan' => today()->subDays(3)->toDateString()])
            ->assertSessionHasNoErrors();
        $this->assertSame('Catatan baru', $kustom->fresh()->catatan);

        $this->actingAs($user)->put(route('admin.pesanan-kustom.perbarui', $kustom), $data + ['tanggal_diinginkan' => today()->subDay()->toDateString()])
            ->assertSessionHasErrors('tanggal_diinginkan');
    }

    public function test_alur_kustom_menolak_konversi_tanpa_penawaran_aktif_dan_ganda(): void
    {
        $user = User::factory()->create();
        $journey = Perjalanan::factory()->create();
        $jadwal = JadwalKeberangkatan::factory()->create(['perjalanan_id' => $journey->id]);
        $kustom = PesananKustom::factory()->create([
            'harga_final' => 500_000,
            'status' => StatusPesananKustom::Diproses,
            'rencana_perjalanan' => 'Hari 1 Dieng',
            'tanggal_fleksibel' => false,
            'tanggal_diinginkan' => $jadwal->tanggal,
        ]);
        $konversi = fn () => $this->actingAs($user)->post(route('admin.pesanan-kustom.jadikan-resmi', $kustom), [
            'tamu_menyetujui' => '1',
            'tanggal_trip' => $jadwal->tanggal->toDateString(),
            'lokasi_pickup' => 'Hotel Malang',
            'status_penginapan' => 'sudah',
            'nama_hotel' => 'Hotel Uji Penginapan',
        ]);

        $konversi()->assertSessionHasErrors('status');
        $this->assertDatabaseCount('pesanan', 0);

        $this->actingAs($user)->post(route('admin.pesanan-kustom.penawaran-terkirim', $kustom))->assertSessionHasNoErrors();
        $this->assertCount(1, $kustom->fresh()->riwayatPenawaran);

        $konversi()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('pesanan', 1);

        $konversi()->assertForbidden();
        $this->assertDatabaseCount('pesanan', 1);
    }
}
