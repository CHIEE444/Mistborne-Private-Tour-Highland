<?php

namespace Tests\Feature\Admin\PesananKustom;

use App\Enums\StatusPesananKustom;
use App\Enums\SumberPesanan;
use App\Layanan\BatasBayarLayanan;
use App\Layanan\StatistikDashboardLayanan;
use App\Models\Perjalanan;
use App\Models\PermintaanPesanan;
use App\Models\Pesanan;
use App\Models\PesananKustom;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class PenyamaanAlurTest extends TestCase
{
    private function admin(): User
    {
        return User::factory()->create(['jabatan' => 'admin']);
    }

    private function kustomMenungguJawaban(array $ubah = []): PesananKustom
    {
        return PesananKustom::factory()->create([
            'status' => StatusPesananKustom::MenungguJawaban,
            'harga_final' => 2_000_000,
            'rencana_perjalanan' => 'Hari 1 Dieng',
            'penawaran_dikirim_pada' => now(),
            ...$ubah,
        ]);
    }

    public function test_pesanan_dari_kustom_bersumber_kustom_dan_punya_link_balik_ke_kustom_asal(): void
    {
        $admin = $this->admin();
        $kustom = $this->kustomMenungguJawaban();
        $pesanan = Pesanan::factory()->create(['sumber' => SumberPesanan::Kustom]);
        $kustom->update(['pesanan_id' => $pesanan->id, 'status' => StatusPesananKustom::Disetujui]);

        $this->actingAs($admin)->get(route('admin.pesanan.detail', $pesanan))
            ->assertOk()
            ->assertSee('Sumber: Kustom')
            ->assertSee('Dari pesanan kustom #'.$kustom->kodeTampilan())
            ->assertSee(route('admin.pesanan-kustom.detail', $kustom->id), false)
            ->assertSee('Pesanan Dibuat dari Pesanan Kustom');
    }

    public function test_pesanan_dari_permintaan_form_menampilkan_link_balik_ke_permintaan(): void
    {
        $admin = $this->admin();
        $pesanan = Pesanan::factory()->create();
        PermintaanPesanan::query()->create([
            'token' => Str::random(64), 'whatsapp' => '081234567890', 'status' => 'disetujui', 'pesanan_id' => $pesanan->id,
            'token_berlaku_sampai' => now()->addDays(7),
        ]);

        $this->actingAs($admin)->get(route('admin.pesanan.detail', $pesanan))
            ->assertOk()
            ->assertSee('Dari Permintaan Form');
    }

    public function test_filter_sumber_kustom_hanya_menampilkan_pesanan_kustom(): void
    {
        $admin = $this->admin();
        $journey = Perjalanan::factory()->create();
        Pesanan::factory()->create(['perjalanan_id' => $journey->id, 'sumber' => SumberPesanan::Kustom, 'nama_lengkap' => 'Tamu Dari Kustom']);
        Pesanan::factory()->create(['perjalanan_id' => $journey->id, 'sumber' => SumberPesanan::Manual, 'nama_lengkap' => 'Tamu Dari Manual']);

        $this->actingAs($admin)->get(route('admin.pesanan.indeks', ['sumber' => 'kustom']))
            ->assertOk()
            ->assertSee('Tamu Dari Kustom')
            ->assertDontSee('Tamu Dari Manual');
        $this->actingAs($admin)->get(route('admin.pesanan.indeks', ['sumber' => 'bukan-sumber']))->assertSessionHasErrors('sumber');
    }

    public function test_detail_kustom_punya_tombol_salin_pesan_penawaran(): void
    {
        $kustom = $this->kustomMenungguJawaban(['nama' => 'Tamu Penawaran']);

        $this->actingAs($this->admin())->get(route('admin.pesanan-kustom.detail', $kustom->id))
            ->assertOk()
            ->assertSee('Salin Pesan')
            ->assertSee('Penawaran trip kustom')
            ->assertSee('Kirim Ulang Penawaran')
            ->assertSee('Tamu Penawaran', false);
    }

    public function test_tidak_jadi_di_permintaan_form_menyimpan_alasan_opsional(): void
    {
        $admin = $this->admin();
        $buat = fn () => PermintaanPesanan::query()->create([
            'token' => Str::random(64), 'whatsapp' => '081234567890', 'status' => 'menunggu_tinjauan', 'nama_lengkap' => 'Tamu Batal',
            'token_berlaku_sampai' => now()->addDays(7),
        ]);

        $denganAlasan = $buat();
        $this->actingAs($admin)->post(route('admin.permintaan-pesanan.tidak-jadi', $denganAlasan), ['alasan_tidak_jadi' => 'Harga di luar anggaran'])
            ->assertSessionHasNoErrors();
        $this->assertSame('tidak_jadi', $denganAlasan->fresh()->status);
        $this->assertSame('Harga di luar anggaran', $denganAlasan->fresh()->alasan_tidak_jadi);

        $tanpaAlasan = $buat();
        $this->actingAs($admin)->post(route('admin.permintaan-pesanan.tidak-jadi', $tanpaAlasan))->assertSessionHasNoErrors();
        $this->assertSame('tidak_jadi', $tanpaAlasan->fresh()->status);
        $this->assertNull($tanpaAlasan->fresh()->alasan_tidak_jadi);

        $this->actingAs($admin)->get(route('admin.permintaan-pesanan.indeks'))
            ->assertOk()
            ->assertSee('Alasan tidak jadi:')
            ->assertSee('Harga di luar anggaran');
    }

    public function test_alasan_tidak_jadi_lebih_dari_seribu_karakter_ditolak(): void
    {
        $permintaan = PermintaanPesanan::query()->create([
            'token' => Str::random(64), 'whatsapp' => '081234567890', 'status' => 'menunggu_tinjauan',
            'token_berlaku_sampai' => now()->addDays(7),
        ]);

        $this->actingAs($this->admin())->post(route('admin.permintaan-pesanan.tidak-jadi', $permintaan), ['alasan_tidak_jadi' => str_repeat('a', 1001)])
            ->assertSessionHasErrors('alasan_tidak_jadi');
        $this->assertSame('menunggu_tinjauan', $permintaan->fresh()->status);
    }

    public function test_penawaran_kustom_yang_kedaluwarsa_dihitung_dan_ditandai(): void
    {
        $admin = $this->admin();
        $kedaluwarsa = $this->kustomMenungguJawaban(['nama' => 'Tamu Basi', 'batas_penawaran' => today()->subDay()]);
        $this->kustomMenungguJawaban(['nama' => 'Tamu Berlaku', 'batas_penawaran' => today()->addDay()]);
        $this->kustomMenungguJawaban(['nama' => 'Tamu Tanpa Batas', 'batas_penawaran' => null]);
        PesananKustom::factory()->create(['status' => StatusPesananKustom::Diproses, 'batas_penawaran' => today()->subDays(3)]);

        $this->assertTrue($kedaluwarsa->penawaranKedaluwarsa());

        $hasil = app(StatistikDashboardLayanan::class)->perluDitindaklanjuti(app(BatasBayarLayanan::class));
        $this->assertSame(1, $hasil['penawaran_kedaluwarsa']);

        $this->actingAs($admin)->get(route('admin.dashboard.indeks'))->assertOk()->assertSee('Penawaran kustom kedaluwarsa');
        $this->actingAs($admin)->get(route('admin.pesanan-kustom.indeks'))->assertOk()->assertSee('Penawaran kedaluwarsa');
        $this->actingAs($admin)->get(route('admin.pesanan-kustom.detail', $kedaluwarsa->id))->assertOk()->assertSee('Penawaran kedaluwarsa');
    }
}
