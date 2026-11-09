<?php

namespace Tests\Feature\Admin\Pesanan;

use App\Enums\BookingStatus;
use App\Mail\PembatalanPenugasanPersonel;
use App\Models\Pengaturan;
use App\Models\PersonelOperasional;
use App\Models\Pesanan;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Tests\Support\DataGuide;
use Tests\TestCase;

class PembatalanOtomatisDanEmailGuideTest extends TestCase
{
    private function guideBerEmail(string $email = 'guide.batal@example.com'): PersonelOperasional
    {
        $akun = User::factory()->create(['jabatan' => 'Guide', 'email' => 'akun.'.$email]);

        return $akun->personel()->create([...DataGuide::sim(), 'nama' => 'Guide Batal', 'peran' => 'guide', 'email' => $email, 'status_operasional' => 'siap', 'aktif' => true]);
    }

    private function dataBatal(): array
    {
        return ['pihak_pembatal' => 'pelanggan', 'alasan_batal' => 'Tamu berhalangan hadir', 'nominal_refund' => 0];
    }

    public function test_guide_dikabari_lewat_email_saat_pesanan_dibatalkan(): void
    {
        Mail::fake();
        $guide = $this->guideBerEmail();
        $pesanan = Pesanan::factory()->payment()->create(['harga' => 2_000_000, 'nominal_dibayar' => 2_000_000, 'guide_personel_id' => $guide->id, 'nama_guide' => $guide->nama, 'status_konfirmasi_guide' => 'diterima']);

        $this->actingAs(User::factory()->create())
            ->post(route('admin.pesanan.batalkan', $pesanan), $this->dataBatal())
            ->assertSessionHasNoErrors()
            ->assertSessionHas('sukses', fn (string $pesan): bool => str_contains($pesan, 'guide sudah dikabari'));

        Mail::assertSent(PembatalanPenugasanPersonel::class, function (PembatalanPenugasanPersonel $mail) use ($pesanan): bool {
            $html = $mail->render();

            return $mail->hasTo('guide.batal@example.com')
                && str_contains($mail->envelope()->subject, $pesanan->kode)
                && str_contains($html, $pesanan->kode)
                && str_contains($html, 'Tamu berhalangan hadir');
        });
    }

    public function test_pembatalan_tetap_berhasil_dan_admin_diperingatkan_bila_guide_tanpa_email(): void
    {
        Mail::fake();
        $akun = User::factory()->create(['jabatan' => 'Guide', 'email' => 'guide@uji.test']);
        $guide = $akun->personel()->create([...DataGuide::sim(), 'nama' => 'Guide Tanpa Email', 'peran' => 'guide', 'email' => 'guide@uji.test', 'status_operasional' => 'siap', 'aktif' => true]);
        $pesanan = Pesanan::factory()->payment()->create(['harga' => 2_000_000, 'nominal_dibayar' => 2_000_000, 'guide_personel_id' => $guide->id]);

        $this->actingAs(User::factory()->create())
            ->post(route('admin.pesanan.batalkan', $pesanan), $this->dataBatal())
            ->assertSessionHas('gagal');

        Mail::assertNothingSent();
        $this->assertSame(BookingStatus::Dibatalkan, $pesanan->fresh()->status);
    }

    public function test_pesanan_tanpa_guide_dibatalkan_tanpa_email(): void
    {
        Mail::fake();
        $pesanan = Pesanan::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('admin.pesanan.batalkan', $pesanan), $this->dataBatal())
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('gagal');

        Mail::assertNothingSent();
    }

    public function test_command_membatalkan_pesanan_yang_lewat_tenggat_bayar_dan_membebaskan_kuota(): void
    {
        Mail::fake();
        $guide = $this->guideBerEmail();
        $hari = Pengaturan::current()->batas_bayar_hari;
        // Lewat batas bayar (hari ke-4) baru diperingatkan; pembatalan otomatis setelah hari ke-5 (batas 3 + tenggang 2).
        $diperingatkan = Pesanan::factory()->create(['dikonfirmasi_at' => now()->subDays($hari + 1), 'nominal_dibayar' => null]);
        $lewat = Pesanan::factory()->create(['dikonfirmasi_at' => now()->subDays($hari + 3), 'nominal_dibayar' => null, 'guide_personel_id' => $guide->id]);

        $this->artisan('pesanan:batalkan-lewat-tenggat-bayar')->assertSuccessful();

        $this->assertSame(BookingStatus::Dihubungi, $diperingatkan->fresh()->status);
        $lewat->refresh();
        $this->assertSame(BookingStatus::Dibatalkan, $lewat->status);
        $this->assertSame('mistborne', $lewat->pihak_pembatal);
        $this->assertSame(0, (int) $lewat->nominal_refund);
        $this->assertStringContainsString('otomatis', $lewat->alasan_batal);
        Mail::assertSent(PembatalanPenugasanPersonel::class, fn ($mail) => $mail->hasTo('guide.batal@example.com'));
    }

    public function test_command_tidak_menyentuh_pesanan_yang_belum_lewat_belum_dikonfirmasi_atau_sudah_membayar(): void
    {
        $hari = Pengaturan::current()->batas_bayar_hari;
        $masihAman = Pesanan::factory()->create(['dikonfirmasi_at' => now()->subDays(max(0, $hari - 1)), 'nominal_dibayar' => null]);
        $belumDikonfirmasi = Pesanan::factory()->create(['dikonfirmasi_at' => null, 'nominal_dibayar' => null]);
        $sudahMembayarSebagian = Pesanan::factory()->create(['dikonfirmasi_at' => now()->subDays($hari + 5), 'harga' => 2_000_000, 'nominal_dibayar' => 200_000]);
        $payment = Pesanan::factory()->payment()->create(['dikonfirmasi_at' => now()->subDays($hari + 5), 'harga' => 2_000_000, 'nominal_dibayar' => 2_000_000]);

        $this->artisan('pesanan:batalkan-lewat-tenggat-bayar')->assertSuccessful();

        foreach ([$masihAman, $belumDikonfirmasi, $sudahMembayarSebagian] as $pesanan) {
            $this->assertSame(BookingStatus::Dihubungi, $pesanan->fresh()->status);
        }
        $this->assertSame(BookingStatus::Payment, $payment->fresh()->status);
    }
}
