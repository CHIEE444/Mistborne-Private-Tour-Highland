<?php

namespace Tests\Feature\Admin\PesananKustom;

use App\Enums\StatusPesananKustom;
use App\Layanan\PesanWhatsAppLayanan;
use App\Models\Pesanan;
use App\Models\PesananKustom;
use App\Models\User;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/** Alur admin pesanan kustom "Hubungi – Susun – Kirim – Resmikan" dengan satu tombol hijau per tahap di Aksi Cepat. */
class AlurTripKustomTest extends TestCase
{
    /** Permintaan dengan data lengkap (tanggal pasti) pada status tertentu. */
    private function kustom(array $ubah = []): PesananKustom
    {
        return PesananKustom::factory()->create([
            'nama' => 'Tamu Alur Kustom',
            'tanggal_diinginkan' => today()->addDays(30),
            ...$ubah,
        ]);
    }

    private function halaman(PesananKustom $kustom): TestResponse
    {
        return $this->actingAs(User::factory()->create())->get(route('admin.pesanan-kustom.detail', $kustom->id))->assertOk();
    }

    public function test_tahap_1_hubungi_tamu_satu_klik_memindahkan_status_ke_diproses(): void
    {
        $kustom = $this->kustom();

        $this->halaman($kustom)
            ->assertSeeInOrder(['Hubungi tamu', 'Susun penawaran', 'Kirim penawaran', 'Resmikan pesanan'])
            ->assertSeeInOrder(['Aksi Cepat', 'Tahap 1 dari 4', 'Hubungi via WhatsApp'])
            ->assertSee(route('admin.pesanan-kustom.hubungi', $kustom), false)
            ->assertDontSee('Data permintaan belum lengkap');

        $this->actingAs(User::factory()->create())->patch(route('admin.pesanan-kustom.hubungi', $kustom))
            ->assertSessionHas('sukses', 'Tamu dihubungi lewat WhatsApp. Lanjut tahap 2: susun penawaran.');
        $this->assertSame(StatusPesananKustom::Diproses, $kustom->fresh()->status);
    }

    public function test_hubungi_via_whatsapp_menampilkan_popup_validasi_bila_data_belum_lengkap(): void
    {
        $kustom = $this->kustom(['lokasi_penjemputan' => null, 'tanggal_diinginkan' => null]);

        $this->halaman($kustom)
            ->assertSee('Data permintaan belum lengkap')
            ->assertSee('Area penjemputan')
            ->assertSee('Tanggal perjalanan (atau centang tanggal fleksibel)')
            ->assertDontSee(route('admin.pesanan-kustom.hubungi', $kustom), false);

        $this->actingAs(User::factory()->create())->patch(route('admin.pesanan-kustom.hubungi', $kustom))
            ->assertSessionHas('gagal', 'Lengkapi data permintaan dulu: Tanggal perjalanan (atau centang tanggal fleksibel), Area penjemputan.');
        $this->assertSame(StatusPesananKustom::Baru, $kustom->fresh()->status);
    }

    public function test_tahap_2_simpan_penawaran_tanpa_memilih_status(): void
    {
        $kustom = $this->kustom();

        $this->actingAs(User::factory()->create())->put(route('admin.pesanan-kustom.perbarui-tindak-lanjut', $kustom), [
            'rencana_perjalanan' => 'Hari 1 Dieng, Hari 2 Bromo',
            'harga_final' => 1_500_000,
        ])->assertSessionHasNoErrors();

        $kustom->refresh();
        $this->assertSame(StatusPesananKustom::Diproses, $kustom->status);
        $this->assertSame(1_500_000, $kustom->harga_final);
        $this->assertSame(3, $kustom->tahapAlur());
    }

    public function test_tahap_2_mengarahkan_ke_kartu_susun_penawaran_dan_menyebut_yang_kurang(): void
    {
        $this->halaman($this->kustom(['status' => StatusPesananKustom::Diproses]))
            ->assertSeeInOrder(['Tahap 2 dari 4', 'Susun Penawaran', 'Isi rencana yang akan ditawarkan dan harga per orang'])
            ->assertSee('id="susun-penawaran"', false)
            ->assertDontSee('id="status"', false);
    }

    public function test_tahap_3_kirim_penawaran_satu_klik_memindahkan_status_ke_menunggu_jawaban(): void
    {
        $kustom = $this->kustom(['status' => StatusPesananKustom::Diproses, 'rencana_perjalanan' => 'Hari 1 Dieng', 'harga_final' => 1_500_000]);

        $this->halaman($kustom)
            ->assertSeeInOrder(['Tahap 3 dari 4', 'Kirim Penawaran via WhatsApp'])
            ->assertSee(route('admin.pesanan-kustom.penawaran-terkirim', $kustom), false);

        $this->actingAs(User::factory()->create())->post(route('admin.pesanan-kustom.penawaran-terkirim', $kustom))->assertSessionHasNoErrors();
        $this->assertSame(StatusPesananKustom::MenungguJawaban, $kustom->fresh()->status);
    }

    public function test_tahap_4_jadikan_pesanan_resmi_hijau_di_atas_dan_kontak_tamu_disembunyikan(): void
    {
        $kustom = $this->kustom([
            'status' => StatusPesananKustom::MenungguJawaban,
            'rencana_perjalanan' => 'Hari 1 Dieng',
            'harga_final' => 1_500_000,
            'penawaran_dikirim_pada' => now(),
        ]);

        $this->halaman($kustom)
            ->assertSeeInOrder(['Tahap 4 dari 4', 'Jadikan Pesanan Resmi', 'Aksi lainnya', 'Kirim Ulang Penawaran'])
            ->assertDontSee('Hubungi via WhatsApp')
            ->assertDontSee('Kontak Tamu')
            ->assertSee('form-jadikan-resmi-'.$kustom->id, false);
    }

    public function test_penawaran_kedaluwarsa_menutup_tombol_kirim_dan_resmikan_dengan_alasan(): void
    {
        $kirim = $this->kustom(['status' => StatusPesananKustom::Diproses, 'rencana_perjalanan' => 'Hari 1 Dieng', 'harga_final' => 1_500_000, 'batas_penawaran' => today()->subDay()]);

        $html = $this->halaman($kirim)
            ->assertSee('Masa berlaku penawaran ('.today()->subDay()->translatedFormat('d M Y').') sudah lewat.')
            ->assertDontSee('Penawaran trip kustom')
            ->getContent();
        // Tombol kirim tampil nonaktif (bukan tautan WhatsApp), jadi tamu tidak menerima penawaran yang sudah lewat.
        $this->assertMatchesRegularExpression('/<button[^>]*disabled[^>]*>(?:(?!<\/button>).)*Kirim Penawaran via WhatsApp/s', $html);

        $resmikan = $this->kustom(['status' => StatusPesananKustom::MenungguJawaban, 'rencana_perjalanan' => 'Hari 1 Dieng', 'harga_final' => 1_500_000, 'batas_penawaran' => today()->subDay(), 'penawaran_dikirim_pada' => now()->subDays(3)]);

        $this->halaman($resmikan)
            ->assertSeeInOrder(['Tahap 4 dari 4', 'Perpanjang Masa Berlaku', 'belum bisa diresmikan'])
            ->assertSee('Penawaran kedaluwarsa sejak');
    }

    public function test_tanggal_sepakat_kurang_dari_h7_meminta_tanggal_baru_bukan_jadikan_resmi(): void
    {
        $kustom = $this->kustom(['tanggal_diinginkan' => today()->addDays(3), 'status' => StatusPesananKustom::MenungguJawaban, 'rencana_perjalanan' => 'Hari 1 Dieng', 'harga_final' => 1_500_000, 'penawaran_dikirim_pada' => now()]);

        $this->halaman($kustom)
            ->assertSeeInOrder(['Tahap 4 dari 4', 'Sepakati Tanggal Baru', 'kurang dari 7 hari lagi']);
    }

    public function test_kartu_susun_penawaran_menampilkan_status_dan_total_grup(): void
    {
        $draf = $this->kustom(['status' => StatusPesananKustom::Diproses, 'jumlah_orang' => 4, 'harga_final' => 1_250_000, 'rencana_perjalanan' => 'Hari 1 Dieng']);
        $this->halaman($draf)
            ->assertSee('Draf, belum pernah dikirim ke tamu')
            ->assertSee('Rp 5.000.000');

        $this->actingAs(User::factory()->create())->post(route('admin.pesanan-kustom.penawaran-terkirim', $draf))->assertSessionHasNoErrors();
        $this->halaman($draf->fresh())
            ->assertSee('Versi 1 terkirim')
            ->assertSee('menunggu jawaban tamu')
            ->assertSee('Terbaru');
    }

    public function test_pesan_penawaran_merinci_harga_per_orang_dan_cara_menyetujui(): void
    {
        $kustom = $this->kustom(['jumlah_orang' => 4, 'harga_final' => 1_250_000, 'rencana_perjalanan' => 'Hari 1 Dieng', 'rincian_penawaran' => 'Termasuk: mobil + guide', 'batas_penawaran' => today()->addDays(5)]);

        $teks = app(PesanWhatsAppLayanan::class)->penawaranTripKustom($kustom);

        $this->assertStringContainsString('Rp 1.250.000 per orang × 4 orang = Rp 5.000.000 (total grup)', $teks);
        $this->assertStringContainsString("*Rencana perjalanan*\nHari 1 Dieng", $teks);
        $this->assertStringContainsString("*Termasuk & tidak termasuk*\nTermasuk: mobil + guide", $teks);
        $this->assertStringContainsString('balas "SETUJU"', $teks);
        $this->assertStringContainsString('berlaku sampai '.today()->addDays(5)->translatedFormat('d F Y'), $teks);
    }

    public function test_setelah_diresmikan_aksi_cepat_menyatakan_pesanan_disetujui(): void
    {
        $pesanan = Pesanan::factory()->create();
        $kustom = $this->kustom(['status' => StatusPesananKustom::Disetujui, 'pesanan_id' => $pesanan->id]);

        $this->halaman($kustom)
            ->assertSeeInOrder(['Aksi Cepat', 'Pesanan disetujui', 'Buka Pesanan Resmi'])
            ->assertSee(route('admin.pesanan.detail', $pesanan), false)
            ->assertDontSee('Aksi lainnya');
    }

    public function test_tandai_tidak_jadi_wajib_alasan_lalu_bisa_dibuka_kembali(): void
    {
        $kustom = $this->kustom(['status' => StatusPesananKustom::Diproses]);
        $admin = User::factory()->create();

        $this->actingAs($admin)->put(route('admin.pesanan-kustom.perbarui-tindak-lanjut', $kustom), ['status' => 'tidak_jadi'])
            ->assertSessionHasErrors(['alasan_tidak_jadi' => 'Isi alasan sebelum menutup permintaan sebagai Tidak Jadi.']);
        $this->actingAs($admin)->put(route('admin.pesanan-kustom.perbarui-tindak-lanjut', $kustom), ['status' => 'tidak_jadi', 'alasan_tidak_jadi' => 'Tamu batal berangkat'])
            ->assertSessionHasNoErrors();
        $this->assertSame(StatusPesananKustom::TidakJadi, $kustom->fresh()->status);

        $this->halaman($kustom->fresh())->assertSee('Permintaan ditutup (Tidak jadi)')->assertSee('Buka Kembali Permintaan');

        $this->actingAs($admin)->put(route('admin.pesanan-kustom.perbarui-tindak-lanjut', $kustom), ['status' => 'diproses'])->assertSessionHasNoErrors();
        $this->assertSame(StatusPesananKustom::Diproses, $kustom->fresh()->status);
        $this->assertNull($kustom->fresh()->alasan_tidak_jadi);
    }
}
