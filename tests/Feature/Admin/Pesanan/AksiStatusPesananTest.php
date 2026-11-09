<?php

namespace Tests\Feature\Admin\Pesanan;

use App\Aksi\Pesanan\BatalkanPesanan;
use App\Enums\BookingStatus;
use App\Models\Pengaturan;
use App\Models\Perjalanan;
use App\Models\PersonelOperasional;
use App\Models\Pesanan;
use App\Models\User;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class AksiStatusPesananTest extends TestCase
{
    public function test_konfirmasi_mengisi_dikonfirmasi_at_tanpa_ubah_status(): void
    {
        $user = User::factory()->create();
        $pesanan = Pesanan::factory()->create(['status' => BookingStatus::Dihubungi]);

        $response = $this->actingAs($user)->patch(route('admin.pesanan.konfirmasi', $pesanan));

        $response->assertRedirect();
        $pesanan->refresh();
        $this->assertNotNull($pesanan->dikonfirmasi_at);
        $this->assertSame(BookingStatus::Dihubungi, $pesanan->status);
    }

    public function test_konfirmasi_ulang_tidak_memulai_ulang_tenggat_bayar(): void
    {
        $user = User::factory()->create();
        $dikonfirmasiPada = now()->subDays(2)->startOfSecond();
        $pesanan = Pesanan::factory()->create([
            'status' => BookingStatus::Dihubungi,
            'dikonfirmasi_at' => $dikonfirmasiPada,
        ]);

        $this->actingAs($user)->patch(route('admin.pesanan.konfirmasi', $pesanan))->assertRedirect();

        $this->assertTrue($pesanan->fresh()->dikonfirmasi_at->equalTo($dikonfirmasiPada));
    }

    public function test_konfirmasi_ditolak_jika_tagihan_sudah_lunas(): void
    {
        $user = User::factory()->create();
        $pesanan = Pesanan::factory()->create([
            'status' => BookingStatus::Dihubungi,
            'harga' => 1_000_000,
            'nominal_dibayar' => 1_000_000,
        ]);

        $this->actingAs($user)
            ->patch(route('admin.pesanan.konfirmasi', $pesanan))
            ->assertSessionHasErrors('status');

        $this->assertNull($pesanan->fresh()->dikonfirmasi_at);
        $this->assertSame(BookingStatus::Dihubungi, $pesanan->fresh()->status);
    }

    public function test_ubah_ke_payment(): void
    {
        $user = User::factory()->create();
        $pesanan = Pesanan::factory()->create([
            'status' => BookingStatus::Dihubungi,
            'harga' => 2_000_000,
            'nominal_dibayar' => 600_000,
        ]);

        $response = $this->actingAs($user)->patch(route('admin.pesanan.payment', $pesanan));

        $response->assertRedirect();
        $this->assertSame(BookingStatus::Payment, $pesanan->fresh()->status);
    }

    public function test_ubah_ke_payment_tidak_dicek_kuota_lagi_karena_slot_dipegang_sejak_pesanan_dibuat(): void
    {
        $user = User::factory()->create();
        Pengaturan::current()->update(['kuota_default' => 1]);
        $tanggal = now()->addDays(20);
        Pesanan::factory()->payment()->create(['tanggal_trip' => $tanggal]);
        $menunggu = Pesanan::factory()->create([
            'status' => BookingStatus::Dihubungi,
            'tanggal_trip' => $tanggal,
            'harga' => 2_000_000,
            'nominal_dibayar' => 600_000,
        ]);

        $this->actingAs($user)
            ->patch(route('admin.pesanan.payment', $menunggu))
            ->assertSessionHasNoErrors();

        $this->assertSame(BookingStatus::Payment, $menunggu->fresh()->status);
    }

    public function test_catat_pembayaran_tidak_dicek_kuota_lagi_karena_slot_dipegang_sejak_pesanan_dibuat(): void
    {
        $user = User::factory()->create();
        Pengaturan::current()->update(['kuota_default' => 1]);
        $tanggal = now()->addDays(20);
        Pesanan::factory()->payment()->create(['tanggal_trip' => $tanggal]);
        $menunggu = Pesanan::factory()->create([
            'status' => BookingStatus::Dihubungi,
            'tanggal_trip' => $tanggal,
            'harga' => 2_000_000,
        ]);

        $this->actingAs($user)
            ->patch(route('admin.pesanan.pembayaran-diterima', $menunggu), [
                'jenis_pembayaran' => 'uang_muka',
                'nominal_dibayar' => 600_000,
                'metode_pembayaran' => 'transfer',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(BookingStatus::Payment, $menunggu->fresh()->status);
        $this->assertSame(600_000, $menunggu->fresh()->nominal_dibayar);
    }

    public function test_nominal_cicilan_baru_ditambahkan_ke_pembayaran_yang_sudah_tercatat(): void
    {
        $user = User::factory()->create();
        $pesanan = Pesanan::factory()->create([
            'status' => BookingStatus::Dihubungi,
            'harga' => 9_000_000,
            'nominal_dibayar' => 5_000_000,
        ]);

        $this->actingAs($user)
            ->patch(route('admin.pesanan.pembayaran-diterima', $pesanan), [
                'jenis_pembayaran' => 'uang_muka',
                'nominal_dibayar' => 3_000_000,
                'metode_pembayaran' => 'transfer',
            ])
            ->assertRedirect();

        $this->assertSame(8_000_000, $pesanan->fresh()->nominal_dibayar);
        $this->assertSame(BookingStatus::Payment, $pesanan->fresh()->status);
    }

    public function test_catat_uang_muka_ditolak_jika_nominal_sudah_melunasi_sisa_tagihan(): void
    {
        $user = User::factory()->create();
        $pesanan = Pesanan::factory()->create([
            'status' => BookingStatus::Dihubungi,
            'harga' => 9_000_000,
            'nominal_dibayar' => 5_000_000,
        ]);

        $this->actingAs($user)
            ->patch(route('admin.pesanan.pembayaran-diterima', $pesanan), [
                'jenis_pembayaran' => 'uang_muka',
                'nominal_dibayar' => 4_000_000,
                'metode_pembayaran' => 'transfer',
            ])
            ->assertSessionHasErrors(['nominal_dibayar' => 'Nominal Rp 4.000.000 sudah melunasi sisa tagihan. Gunakan tombol Tamu Sudah Lunas.']);

        $this->assertSame(5_000_000, $pesanan->fresh()->nominal_dibayar);
        $this->assertSame(BookingStatus::Dihubungi, $pesanan->fresh()->status);
    }

    #[TestWith(['dihubungi', 'Catat Uang Muka'])]
    #[TestWith(['payment', 'Catat Pembayaran'])]
    public function test_tamu_sudah_lunas_ditolak_dan_diarahkan_ke_pembayaran_sebagian_jika_nominal_kurang(string $status, string $tombolArahan): void
    {
        $user = User::factory()->create();
        $pesanan = Pesanan::factory()->create([
            'status' => BookingStatus::from($status),
            'harga' => 9_000_000,
            'nominal_dibayar' => 5_000_000,
        ]);

        $this->actingAs($user)
            ->patch(route('admin.pesanan.pembayaran-diterima', $pesanan), [
                'jenis_pembayaran' => 'lunas',
                'nominal_dibayar' => 3_000_000,
                'metode_pembayaran' => 'transfer',
            ])
            ->assertSessionHasErrors(['nominal_dibayar' => "Nominal Rp 3.000.000 kurang dari sisa tagihan Rp 4.000.000. Untuk pembayaran sebagian, gunakan tombol {$tombolArahan}."]);

        $this->assertSame(5_000_000, $pesanan->fresh()->nominal_dibayar);
        $this->assertSame(BookingStatus::from($status), $pesanan->fresh()->status);
    }

    public function test_tamu_sudah_lunas_dicatat_jika_nominal_sama_dengan_sisa_tagihan(): void
    {
        $user = User::factory()->create();
        $pesanan = Pesanan::factory()->create([
            'status' => BookingStatus::Dihubungi,
            'harga' => 9_000_000,
            'nominal_dibayar' => 5_000_000,
        ]);

        $this->actingAs($user)
            ->patch(route('admin.pesanan.pembayaran-diterima', $pesanan), [
                'jenis_pembayaran' => 'lunas',
                'nominal_dibayar' => 4_000_000,
                'metode_pembayaran' => 'qris',
            ])
            ->assertRedirect(route('admin.pesanan.detail', $pesanan).'#operasional-pesanan');

        $this->assertSame(9_000_000, $pesanan->fresh()->nominal_dibayar);
        $this->assertSame(BookingStatus::Payment, $pesanan->fresh()->status);
        $this->assertSame(0, $pesanan->fresh()->sisaTagihan());
    }

    #[TestWith(['', 'Isi nominal transfer yang diterima sesuai bukti transfer.'])]
    #[TestWith(['4jt', 'Nominal pembayaran harus berupa angka bulat dalam rupiah.'])]
    public function test_tamu_sudah_lunas_ditolak_tanpa_nominal_angka(string $nominal, string $pesan): void
    {
        $user = User::factory()->create();
        $pesanan = Pesanan::factory()->create([
            'status' => BookingStatus::Dihubungi,
            'harga' => 9_000_000,
            'nominal_dibayar' => 5_000_000,
        ]);

        $this->actingAs($user)
            ->patch(route('admin.pesanan.pembayaran-diterima', $pesanan), [
                'jenis_pembayaran' => 'lunas',
                'nominal_dibayar' => $nominal,
                'metode_pembayaran' => 'transfer',
            ])
            ->assertSessionHasErrors(['nominal_dibayar' => $pesan]);

        $this->assertSame(5_000_000, $pesanan->fresh()->nominal_dibayar);
        $this->assertSame(BookingStatus::Dihubungi, $pesanan->fresh()->status);
    }

    public function test_nominal_cicilan_baru_tidak_boleh_melebihi_sisa_tagihan(): void
    {
        $user = User::factory()->create();
        $pesanan = Pesanan::factory()->create([
            'status' => BookingStatus::Dihubungi,
            'harga' => 9_000_000,
            'nominal_dibayar' => 5_000_000,
        ]);

        $this->actingAs($user)
            ->patch(route('admin.pesanan.pembayaran-diterima', $pesanan), [
                'jenis_pembayaran' => 'uang_muka',
                'nominal_dibayar' => 4_000_001,
                'metode_pembayaran' => 'transfer',
            ])
            ->assertSessionHasErrors('nominal_dibayar');

        $this->assertSame(5_000_000, $pesanan->fresh()->nominal_dibayar);
        $this->assertSame(BookingStatus::Dihubungi, $pesanan->fresh()->status);
    }

    public function test_lepas_hold_payment_tidak_dicek_kuota_lagi_karena_slot_tetap_dipegang_selama_ditahan(): void
    {
        $user = User::factory()->create();
        Pengaturan::current()->update(['kuota_default' => 1]);
        $tanggal = now()->addDays(20);
        Pesanan::factory()->payment()->create(['tanggal_trip' => $tanggal]);
        $hold = Pesanan::factory()->create([
            'status' => BookingStatus::HoldSchedule,
            'status_sebelum_hold' => BookingStatus::Payment->value,
            'tanggal_trip' => $tanggal,
        ]);

        $this->actingAs($user)
            ->patch(route('admin.pesanan.hold', $hold))
            ->assertSessionHasNoErrors();

        $this->assertSame(BookingStatus::Payment, $hold->fresh()->status);
    }

    public function test_ubah_ke_payment_ditolak_dari_completed(): void
    {
        $user = User::factory()->create();
        $pesanan = Pesanan::factory()->create(['status' => BookingStatus::Completed]);

        $this->actingAs($user)->patch(route('admin.pesanan.payment', $pesanan));

        $this->assertSame(BookingStatus::Completed, $pesanan->fresh()->status);
    }

    public function test_selesaikan_pesanan(): void
    {
        $user = User::factory()->create();
        $guide = PersonelOperasional::query()->create(['nama' => 'Guide Test', 'peran' => 'guide']);
        $pesanan = Pesanan::factory()->create([
            'status' => BookingStatus::Payment,
            'tanggal_trip' => now()->subDay(),
            'harga' => 2_000_000,
            'nominal_dibayar' => 2_000_000,
            'guide_personel_id' => $guide->id,
            'guide_trip_selesai_at' => now()->subHour(),
        ]);

        $this->actingAs($user)->patch(route('admin.pesanan.selesaikan', $pesanan))->assertRedirect();

        $this->assertSame(BookingStatus::Completed, $pesanan->fresh()->status);
    }

    public function test_selesaikan_ditolak_jika_trip_belum_berjalan(): void
    {
        $user = User::factory()->create();
        $pesanan = Pesanan::factory()->create(['status' => BookingStatus::Payment, 'tanggal_trip' => now()->addDays(3)]);

        $this->actingAs($user)->patch(route('admin.pesanan.selesaikan', $pesanan));

        $this->assertSame(BookingStatus::Payment, $pesanan->fresh()->status);
    }

    public function test_selesaikan_ditolak_dari_status_selain_payment(): void
    {
        $user = User::factory()->create();
        $pesanan = Pesanan::factory()->create(['status' => BookingStatus::Dihubungi, 'tanggal_trip' => now()->subDay()]);

        $this->actingAs($user)->patch(route('admin.pesanan.selesaikan', $pesanan));

        $this->assertSame(BookingStatus::Dihubungi, $pesanan->fresh()->status);
    }

    public function test_toggle_hold_schedule_lalu_lepas(): void
    {
        $user = User::factory()->create();
        $pesanan = Pesanan::factory()->create(['status' => BookingStatus::Dihubungi, 'tanggal_trip' => now()->addDays(20)]);

        $this->actingAs($user)->patch(route('admin.pesanan.hold', $pesanan));
        $pesanan->refresh();
        $this->assertSame(BookingStatus::HoldSchedule, $pesanan->status);
        $this->assertSame('dihubungi', $pesanan->status_sebelum_hold);

        $this->actingAs($user)->patch(route('admin.pesanan.hold', $pesanan));
        $pesanan->refresh();
        $this->assertSame(BookingStatus::Dihubungi, $pesanan->status);
        $this->assertNull($pesanan->status_sebelum_hold);
    }

    public function test_tahan_jadwal_ditolak_saat_sisa_h7_atau_kurang(): void
    {
        $user = User::factory()->create();
        $pesanan = Pesanan::factory()->create(['status' => BookingStatus::Dihubungi, 'tanggal_trip' => now()->addDays(5)]);

        $this->actingAs($user)->patch(route('admin.pesanan.hold', $pesanan));

        $this->assertSame(BookingStatus::Dihubungi, $pesanan->fresh()->status);
    }

    public function test_aksi_status_ditolak_untuk_pesanan_terkunci(): void
    {
        $user = User::factory()->create();
        $completed = Pesanan::factory()->create(['status' => BookingStatus::Completed, 'tanggal_trip' => now()->subDay()]);
        $dibatalkan = Pesanan::factory()->create(['status' => BookingStatus::Dibatalkan]);

        $this->actingAs($user)->patch(route('admin.pesanan.selesaikan', $completed))->assertForbidden();
        $this->actingAs($user)->patch(route('admin.pesanan.hold', $completed))->assertForbidden();
        $this->actingAs($user)->patch(route('admin.pesanan.selesaikan', $dibatalkan))->assertForbidden();
        $this->actingAs($user)->patch(route('admin.pesanan.hold', $dibatalkan))->assertForbidden();
    }

    public function test_ganti_jadwal_gratis_saat_belum_payment(): void
    {
        $user = User::factory()->create();
        $journey = Perjalanan::factory()->create(['durasi_hari' => 2]);
        $pesanan = Pesanan::factory()->create([
            'perjalanan_id' => $journey->id,
            'status' => BookingStatus::Dihubungi,
            'tanggal_trip' => now()->addDays(20),
        ]);
        $tanggalBaru = now()->addDays(30)->toDateString();
        $this->bukaTanggal($journey, $tanggalBaru);

        $response = $this->actingAs($user)->post(route('admin.pesanan.ganti-jadwal', $pesanan), [
            'tanggal_trip' => $tanggalBaru,
        ]);

        $response->assertRedirect();
        $pesanan->refresh();
        $this->assertSame($tanggalBaru, $pesanan->tanggal_trip->toDateString());
        $this->assertSame(0, $pesanan->charge_ganti_jadwal);
        $this->assertSame(1, $pesanan->penggantian()->count());
    }

    public function test_ganti_jadwal_hanya_ke_tanggal_yang_dibuka_di_tanggal_tersedia(): void
    {
        $user = User::factory()->create();
        $journey = Perjalanan::factory()->create(['durasi_hari' => 2]);
        $pesanan = Pesanan::factory()->create([
            'perjalanan_id' => $journey->id,
            'status' => BookingStatus::Dihubungi,
            'tanggal_trip' => now()->addDays(20),
        ]);
        $tanggalLama = $pesanan->tanggal_trip->toDateString();

        $this->actingAs($user)
            ->post(route('admin.pesanan.ganti-jadwal', $pesanan), ['tanggal_trip' => now()->addDays(30)->toDateString()])
            ->assertSessionHasErrors(['tanggal_trip' => 'Tanggal ini belum dibuka di Tanggal Tersedia journey ini. Pilih tanggal yang tersedia.']);

        $this->assertSame($tanggalLama, $pesanan->fresh()->tanggal_trip->toDateString());
        $this->assertSame(0, $pesanan->penggantian()->count());
    }

    public function test_ganti_jadwal_ditolak_jika_melebihi_satu_tahun_dari_batas_minimal(): void
    {
        $user = User::factory()->create();
        $pesanan = Pesanan::factory()->create([
            'status' => BookingStatus::Dihubungi,
            'tanggal_trip' => now()->addDays(20),
        ]);
        $tanggalLama = $pesanan->tanggal_trip->toDateString();

        $this->actingAs($user)
            ->post(route('admin.pesanan.ganti-jadwal', $pesanan), [
                'tanggal_trip' => now()->addDays(7)->addYear()->addDay()->toDateString(),
            ])
            ->assertSessionHasErrors('tanggal_trip');

        $this->assertSame($tanggalLama, $pesanan->fresh()->tanggal_trip->toDateString());
        $this->assertSame(0, $pesanan->penggantian()->count());
    }

    public function test_ganti_jadwal_pertama_kena_charge_saat_payment_sisa_lebih_dari_h7(): void
    {
        $user = User::factory()->create();
        $journey = Perjalanan::factory()->create(['harga' => 2_000_000]);
        $pesanan = Pesanan::factory()->payment()->create([
            'perjalanan_id' => $journey->id,
            'harga' => 2_000_000,
            'tanggal_trip' => now()->addDays(20),
        ]);
        $this->bukaTanggal($journey, now()->addDays(30));

        $this->actingAs($user)->post(route('admin.pesanan.ganti-jadwal', $pesanan), [
            'tanggal_trip' => now()->addDays(30)->toDateString(),
        ]);

        $this->assertSame(100_000, $pesanan->fresh()->charge_ganti_jadwal);
    }

    public function test_ganti_jadwal_kedua_gratis(): void
    {
        $user = User::factory()->create();
        $journey = Perjalanan::factory()->create(['harga' => 2_000_000]);
        $pesanan = Pesanan::factory()->payment()->create([
            'perjalanan_id' => $journey->id,
            'harga' => 2_000_000,
            'tanggal_trip' => now()->addDays(20),
        ]);
        $this->bukaTanggal($journey, now()->addDays(30));
        $this->bukaTanggal($journey, now()->addDays(40));

        $this->actingAs($user)->post(route('admin.pesanan.ganti-jadwal', $pesanan), ['tanggal_trip' => now()->addDays(30)->toDateString()]);
        $this->actingAs($user)->post(route('admin.pesanan.ganti-jadwal', $pesanan), ['tanggal_trip' => now()->addDays(40)->toDateString()]);

        $pesanan->refresh();
        $this->assertSame(0, $pesanan->charge_ganti_jadwal);
        $this->assertSame(2, $pesanan->penggantian()->count());
    }

    public function test_ganti_jadwal_ditolak_saat_payment_sisa_h7_atau_kurang(): void
    {
        $user = User::factory()->create();
        $pesanan = Pesanan::factory()->payment()->create(['tanggal_trip' => now()->addDays(5)]);
        $tanggalLama = $pesanan->tanggal_trip->toDateString();

        $response = $this->actingAs($user)->post(route('admin.pesanan.ganti-jadwal', $pesanan), [
            'tanggal_trip' => now()->addDays(30)->toDateString(),
        ]);

        $response->assertSessionHasErrors('tanggal');
        $this->assertSame($tanggalLama, $pesanan->fresh()->tanggal_trip->toDateString());
    }

    public function test_batalkan_pesanan(): void
    {
        $user = User::factory()->create();
        $pesanan = Pesanan::factory()->create(['status' => BookingStatus::Dihubungi]);

        $response = $this->actingAs($user)->post(route('admin.pesanan.batalkan', $pesanan), [
            'pihak_pembatal' => 'pelanggan',
            'alasan_batal' => 'Berubah rencana',
            'nominal_refund' => 0,
        ]);

        $response->assertRedirect();
        $pesanan->refresh();
        $this->assertSame(BookingStatus::Dibatalkan, $pesanan->status);
        $this->assertSame('dihubungi', $pesanan->status_sebelum_batal);
        $this->assertSame('pelanggan', $pesanan->pihak_pembatal);
    }

    public function test_saran_refund_pelanggan_mengikuti_jendela_hari(): void
    {
        $aksi = app(BatalkanPesanan::class);

        foreach ([[10, 1_000_000], [7, 1_000_000], [6, 500_000], [2, 500_000], [1, 0]] as [$hari, $diharapkan]) {
            $pesanan = Pesanan::factory()->payment()->create([
                'tanggal_trip' => now()->addDays($hari),
                'harga' => 1_000_000,
                'nominal_dibayar' => 1_000_000,
            ]);

            $this->assertSame($diharapkan, $aksi->saranRefund($pesanan, 'pelanggan'), "H-{$hari}");
        }
    }

    public function test_saran_refund_mistborne_selalu_penuh(): void
    {
        $pesanan = Pesanan::factory()->payment()->create([
            'tanggal_trip' => now()->addDay(),
            'harga' => 1_000_000,
            'nominal_dibayar' => 1_000_000,
        ]);

        $this->assertSame(1_000_000, app(BatalkanPesanan::class)->saranRefund($pesanan, 'mistborne'));
    }

    public function test_indeks_pesanan_bisa_difilter_per_hari_dan_aturan(): void
    {
        $user = User::factory()->create();
        $h7 = Pesanan::factory()->payment()->create(['tanggal_trip' => now()->addDays(7)]);
        $h3 = Pesanan::factory()->payment()->create(['tanggal_trip' => now()->addDays(3)]);
        $h20 = Pesanan::factory()->payment()->create(['tanggal_trip' => now()->addDays(20)]);

        $this->actingAs($user)->get(route('admin.pesanan.indeks', ['hari' => '3']))
            ->assertSee($h3->kode)->assertDontSee($h7->kode)->assertDontSee($h20->kode);

        $this->actingAs($user)->get(route('admin.pesanan.indeks', ['aturan' => 'terkunci']))
            ->assertSee($h7->kode)->assertSee($h3->kode)->assertDontSee($h20->kode);

        $this->actingAs($user)->get(route('admin.pesanan.indeks', ['aturan' => 'batal_separuh']))
            ->assertSee($h3->kode)->assertDontSee($h7->kode)->assertDontSee($h20->kode);

        $this->actingAs($user)->get(route('admin.pesanan.indeks', ['aturan' => 'ganti_jadwal']))
            ->assertSee($h20->kode)->assertDontSee($h7->kode);

        $this->actingAs($user)->get(route('admin.pesanan.indeks', ['hari' => '5']))->assertSessionHasErrors('hari');
    }

    public function test_pesanan_completed_bersifat_final_dan_tidak_bisa_dibatalkan(): void
    {
        $user = User::factory()->create();

        foreach (['pelanggan', 'mistborne'] as $pihak) {
            $pesanan = Pesanan::factory()->completed()->create(['harga' => 2_000_000, 'nominal_dibayar' => 2_000_000]);

            $this->actingAs($user)->post(route('admin.pesanan.batalkan', $pesanan), [
                'pihak_pembatal' => $pihak,
                'alasan_batal' => 'Coba batal',
                'nominal_refund' => 0,
            ])->assertForbidden();

            $this->assertSame(BookingStatus::Completed, $pesanan->fresh()->status);
        }
    }

    public function test_batalkan_dibatalkan_tidak_bisa_dibatalkan_lagi(): void
    {
        $user = User::factory()->create();
        $pesanan = Pesanan::factory()->dibatalkan()->create();

        $response = $this->actingAs($user)->post(route('admin.pesanan.batalkan', $pesanan), [
            'pihak_pembatal' => 'pelanggan',
            'alasan_batal' => 'Coba lagi',
            'nominal_refund' => 0,
        ]);

        $response->assertForbidden();
    }

    public function test_tamu_tidak_bisa_mengakses_aksi_status(): void
    {
        $pesanan = Pesanan::factory()->create();

        $this->patch(route('admin.pesanan.payment', $pesanan))->assertRedirect(route('admin.autentikasi.masuk'));
    }
}
