<?php

namespace Tests\Feature\Console;

use App\Enums\BookingStatus;
use App\Models\Pesanan;
use Tests\TestCase;

class SchedulerCommandsTest extends TestCase
{
    public function test_dihubungi_dengan_tanggal_trip_lewat_dipindah_ke_hold_schedule(): void
    {
        $pesanan = Pesanan::factory()->create([
            'status' => BookingStatus::Dihubungi,
            'tanggal_trip' => now()->subDays(2),
        ]);

        $this->artisan('pesanan:hold-schedule-otomatis')->assertExitCode(0);

        $pesanan->refresh();
        $this->assertSame(BookingStatus::HoldSchedule, $pesanan->status);
        $this->assertSame('dihubungi', $pesanan->status_sebelum_hold);
    }

    public function test_dihubungi_dengan_tanggal_trip_masa_depan_tidak_disentuh(): void
    {
        $pesanan = Pesanan::factory()->create([
            'status' => BookingStatus::Dihubungi,
            'tanggal_trip' => now()->addDays(5),
        ]);

        $this->artisan('pesanan:hold-schedule-otomatis');

        $this->assertSame(BookingStatus::Dihubungi, $pesanan->fresh()->status);
    }

    public function test_payment_dengan_tanggal_trip_lewat_tidak_disentuh(): void
    {
        $pesanan = Pesanan::factory()->payment()->create(['tanggal_trip' => now()->subDays(2)]);

        $this->artisan('pesanan:hold-schedule-otomatis');

        $this->assertSame(BookingStatus::Payment, $pesanan->fresh()->status);
    }

    public function test_idempotent_dijalankan_dua_kali_tidak_mengubah_apa_apa_lagi(): void
    {
        $pesanan = Pesanan::factory()->create([
            'status' => BookingStatus::Dihubungi,
            'tanggal_trip' => now()->subDays(5),
        ]);

        $this->artisan('pesanan:hold-schedule-otomatis');
        $pesanan->refresh();
        $statusSebelumHoldPertama = $pesanan->status_sebelum_hold;

        $this->artisan('pesanan:hold-schedule-otomatis');
        $pesanan->refresh();

        $this->assertSame(BookingStatus::HoldSchedule, $pesanan->status);
        $this->assertSame($statusSebelumHoldPertama, $pesanan->status_sebelum_hold);
    }

    public function test_perintah_pengingat_hari_h_berjalan_sukses(): void
    {
        Pesanan::factory()->payment()->create(['tanggal_trip' => now()->addDays(7)]);
        Pesanan::factory()->payment()->create(['tanggal_trip' => now()->addDays(2)]);

        $this->artisan('pesanan:catat-pengingat-hari-h')->assertExitCode(0);
    }

    public function test_perintah_peringatan_h1_berjalan_sukses(): void
    {
        Pesanan::factory()->create(['status' => BookingStatus::Dihubungi, 'tanggal_trip' => now()->addDay()]);

        $this->artisan('pesanan:catat-peringatan-h1')->assertExitCode(0);
    }

    public function test_tahap_pengingat_mengikuti_sisa_hari_dan_hanya_untuk_payment(): void
    {
        $tahap = fn (int $hari, BookingStatus $status = BookingStatus::Payment): ?string => Pesanan::factory()
            ->create(['status' => $status, 'tanggal_trip' => today()->addDays($hari)])->tahapPengingatBerlaku();

        $this->assertNull($tahap(8));
        $this->assertSame('h7', $tahap(7));
        $this->assertSame('h7', $tahap(4));
        $this->assertSame('h3', $tahap(3));
        $this->assertSame('h2', $tahap(2));
        $this->assertSame('h2', $tahap(1));
        $this->assertNull($tahap(0));
        $this->assertNull($tahap(7, BookingStatus::Dihubungi));
        // Rentang pengingat = Pembayaran yang berangkat 1–7 hari lagi.
        $this->assertSame(5, Pesanan::query()->pengingatBerjalan()->count());
    }

    public function test_pengingat_belum_terkirim_mengabaikan_tahap_yang_sudah_ditandai(): void
    {
        $belum = Pesanan::factory()->payment()->create(['tanggal_trip' => today()->addDays(5)]);
        Pesanan::factory()->payment()->create(['tanggal_trip' => today()->addDays(6), 'pengingat_h7_terkirim' => true]);
        // H-7 terlewat tidak menahan H-3: yang dihitung tahap yang berlaku sekarang.
        $h3 = Pesanan::factory()->payment()->create(['tanggal_trip' => today()->addDays(3), 'pengingat_h7_terkirim' => false]);

        $this->assertEqualsCanonicalizing([$belum->id, $h3->id], Pesanan::pengingatBelumTerkirim()->modelKeys());
    }

    public function test_scope_peringatan_h1_memuat_semua_pesanan_aktif_yang_berangkat_besok(): void
    {
        $besok = now()->addDay();
        $dihubungi = Pesanan::factory()->create(['status' => BookingStatus::Dihubungi, 'tanggal_trip' => $besok]);
        $pembayaran = Pesanan::factory()->payment()->create(['tanggal_trip' => $besok]);
        $ditahan = Pesanan::factory()->create(['status' => BookingStatus::HoldSchedule, 'tanggal_trip' => $besok]);
        Pesanan::factory()->completed()->create(['tanggal_trip' => $besok]);
        Pesanan::factory()->dibatalkan()->create(['tanggal_trip' => $besok]);
        Pesanan::factory()->payment()->create(['tanggal_trip' => now()->addDays(2)]);

        $this->assertEqualsCanonicalizing([$dihubungi->id, $pembayaran->id, $ditahan->id], Pesanan::query()->peringatanH1()->pluck('id')->all());
    }
}
