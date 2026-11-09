<?php

namespace Tests\Feature\Admin\Perjalanan;

use App\Models\JadwalKeberangkatan;
use App\Models\Pengaturan;
use App\Models\Perjalanan;
use App\Models\PersonelOperasional;
use App\Models\Pesanan;
use App\Models\User;
use Tests\Support\DataGuide;
use Tests\TestCase;

class KelolaTanggalTest extends TestCase
{
    public function test_admin_bisa_melihat_daftar_tanggal(): void
    {
        $user = User::factory()->create();
        $journey = Perjalanan::factory()->create();
        JadwalKeberangkatan::factory()->create(['perjalanan_id' => $journey->id]);

        $this->actingAs($user)
            ->get(route('admin.perjalanan.tanggal.indeks', $journey))
            ->assertOk();
    }

    public function test_ringkasan_menghitung_guide_penuh_sebagai_penuh_dan_tanggal_kurang_dari_h7_bukan_tersedia(): void
    {
        $this->tanpaGuideCadangan();
        PersonelOperasional::query()->create([...DataGuide::sim(), 'nama' => 'Guide Tunggal', 'peran' => 'guide', 'email' => 'guide.tunggal@example.com', 'status_operasional' => 'siap', 'aktif' => true]);
        $journey = Perjalanan::factory()->create(['durasi_hari' => 1]);
        $this->bukaTanggal($journey, now()->addDays(3));
        $this->bukaTanggal($journey, now()->addDays(20));
        $this->bukaTanggal($journey, now()->addDays(25));
        $this->bukaTanggal($journey, now()->addDays(30))->update(['diblok' => true]);
        // Satu-satunya guide dipakai trip journey lain di hari ke-20.
        Pesanan::factory()->create(['tanggal_trip' => now()->addDays(20)->toDateString(), 'tanggal_selesai' => now()->addDays(20)->toDateString()]);

        $html = $this->actingAs(User::factory()->create())
            ->get(route('admin.perjalanan.tanggal.indeks', $journey))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/>Tersedia<\/p>\s*<p[^>]*>1<\/p>/', $html);
        $this->assertMatchesRegularExpression('/>Penuh<\/p>\s*<p[^>]*>1<\/p>/', $html);
        $this->assertMatchesRegularExpression('/>Diblokir<\/p>\s*<p[^>]*>1<\/p>/', $html);
    }

    public function test_petunjuk_kuota_default_mengikuti_pengaturan(): void
    {
        $user = User::factory()->create();
        $journey = Perjalanan::factory()->create();
        Pengaturan::current()->update(['kuota_default' => 8]);

        $this->actingAs($user)
            ->get(route('admin.perjalanan.tanggal.indeks', $journey))
            ->assertSee('Kosongkan = default 8 grup');
    }

    public function test_admin_bisa_menambah_satu_tanggal(): void
    {
        $user = User::factory()->create();
        $journey = Perjalanan::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.perjalanan.tanggal.simpan', $journey), [
            'mode' => 'satu',
            'tanggal' => now()->addDays(30)->toDateString(),
        ]);

        $response->assertRedirect();
        $this->assertSame(1, $journey->jadwal()->count());
    }

    public function test_admin_bisa_menambah_rentang_tanggal(): void
    {
        $user = User::factory()->create();
        $journey = Perjalanan::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.perjalanan.tanggal.simpan', $journey), [
            'mode' => 'rentang',
            'tanggal_mulai' => now()->addDays(10)->toDateString(),
            'tanggal_selesai' => now()->addDays(14)->toDateString(),
        ]);

        $response->assertRedirect();
        $this->assertSame(5, $journey->jadwal()->count());
    }

    public function test_menambah_tanggal_yang_sudah_ada_tidak_duplikat(): void
    {
        $user = User::factory()->create();
        $journey = Perjalanan::factory()->create();
        $tanggal = now()->addDays(15)->toDateString();
        JadwalKeberangkatan::factory()->create(['perjalanan_id' => $journey->id, 'tanggal' => $tanggal]);

        $this->actingAs($user)->post(route('admin.perjalanan.tanggal.simpan', $journey), [
            'mode' => 'satu',
            'tanggal' => $tanggal,
        ]);

        $this->assertSame(1, $journey->jadwal()->count());
    }

    public function test_admin_bisa_memblokir_tanggal_h14_atau_lebih(): void
    {
        $user = User::factory()->create();
        $journey = Perjalanan::factory()->create();
        $jadwal = JadwalKeberangkatan::factory()->create(['perjalanan_id' => $journey->id, 'tanggal' => now()->addDays(14)]);

        $response = $this->actingAs($user)->patch(route('admin.perjalanan.tanggal.blokir', [$journey, $jadwal]));

        $response->assertRedirect();
        $this->assertTrue($jadwal->fresh()->diblok);
    }

    public function test_blokir_tanggal_kurang_dari_h14_ditolak(): void
    {
        $user = User::factory()->create();
        $journey = Perjalanan::factory()->create();
        $jadwal = JadwalKeberangkatan::factory()->create(['perjalanan_id' => $journey->id, 'tanggal' => now()->addDays(5)]);

        $response = $this->actingAs($user)->patch(route('admin.perjalanan.tanggal.blokir', [$journey, $jadwal]));

        $response->assertSessionHasErrors('diblok');
        $this->assertFalse($jadwal->fresh()->diblok);
    }

    public function test_admin_bisa_mengubah_kuota_tanggal(): void
    {
        $user = User::factory()->create();
        $journey = Perjalanan::factory()->create();
        $jadwal = JadwalKeberangkatan::factory()->create(['perjalanan_id' => $journey->id]);

        $this->actingAs($user)->put(route('admin.perjalanan.tanggal.perbarui', [$journey, $jadwal]), ['kuota' => 8]);

        $this->assertSame(8, $jadwal->fresh()->kuota);
    }

    public function test_admin_bisa_menghapus_tanggal(): void
    {
        $user = User::factory()->create();
        $journey = Perjalanan::factory()->create();
        $jadwal = JadwalKeberangkatan::factory()->create(['perjalanan_id' => $journey->id]);

        $response = $this->actingAs($user)->delete(route('admin.perjalanan.tanggal.hapus', [$journey, $jadwal]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('jadwal_keberangkatan', ['id' => $jadwal->id]);
    }

    public function test_tanggal_yang_sudah_punya_pesanan_tidak_bisa_dihapus(): void
    {
        $user = User::factory()->create();
        $journey = Perjalanan::factory()->create();
        $jadwal = JadwalKeberangkatan::factory()->create(['perjalanan_id' => $journey->id]);
        Pesanan::factory()->create(['perjalanan_id' => $journey->id, 'tanggal_trip' => $jadwal->tanggal]);

        $response = $this->actingAs($user)->delete(route('admin.perjalanan.tanggal.hapus', [$journey, $jadwal]));

        $response->assertSessionHasErrors('tanggal');
        $this->assertDatabaseHas('jadwal_keberangkatan', ['id' => $jadwal->id]);
    }

    public function test_dialog_hapus_tanggal_membedakan_tanggal_kosong_dan_tanggal_berpesanan(): void
    {
        $user = User::factory()->create();
        $journey = Perjalanan::factory()->create();
        $kosong = JadwalKeberangkatan::factory()->create(['perjalanan_id' => $journey->id, 'tanggal' => now()->addDays(30)->toDateString()]);
        $berpesanan = JadwalKeberangkatan::factory()->create(['perjalanan_id' => $journey->id, 'tanggal' => now()->addDays(40)->toDateString()]);
        Pesanan::factory()->create(['perjalanan_id' => $journey->id, 'tanggal_trip' => $berpesanan->tanggal]);

        $html = $this->actingAs($user)->get(route('admin.perjalanan.tanggal.indeks', $journey))
            ->assertOk()
            ->assertDontSee('return confirm(', false)
            ->assertSee('Hapus tanggal keberangkatan?')
            ->assertSee('Tanggal ini tidak bisa dihapus')
            ->assertSee('Sudah ada 1 pesanan di tanggal ini.')
            ->getContent();

        $this->assertStringContainsString('hapus-tanggal-'.$kosong->id, $html);
        $this->assertStringContainsString('hapus-tanggal-'.$berpesanan->id, $html);
        // Hanya tanggal kosong yang diberi tombol hapus; tanggal berpesanan ditolak server, jadi dialognya tanpa tombol hapus.
        $this->assertSame(1, substr_count($html, 'Ya, hapus tanggal'));
    }
}
