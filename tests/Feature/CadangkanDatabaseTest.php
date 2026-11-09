<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CadangkanDatabaseTest extends TestCase
{
    private const PG_DUMP = '/fake/bin/pg_dump';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        config([
            'database.connections.pgsql_cadangan' => ['driver' => 'pgsql', 'host' => 'db.test', 'port' => 5432, 'database' => 'mistborne', 'username' => 'admin', 'password' => 'rahasia'],
            'mistborne.cadangan.koneksi' => 'pgsql_cadangan',
            'mistborne.cadangan.pg_dump' => self::PG_DUMP,
            'mistborne.cadangan.simpan_hari' => 14,
            'mistborne.cadangan.minimal_disimpan' => 3,
        ]);
    }

    /**
     * Pura-pura pg_dump menulis berkas ke --file; pg_restore --list dijawab sesuai $restoreBerhasil.
     */
    private function fakePostgres(bool $dumpBerhasil = true, bool $restoreBerhasil = true, string $isi = 'DATA'): void
    {
        Process::fake(function (PendingProcess $proses) use ($dumpBerhasil, $restoreBerhasil, $isi) {
            $perintah = (array) $proses->command;

            if (basename($perintah[0]) === 'pg_dump') {
                if (! $dumpBerhasil) {
                    return Process::result(errorOutput: 'connection refused', exitCode: 1);
                }

                file_put_contents($perintah[array_search('--file', $perintah, true) + 1], $isi);

                return Process::result();
            }

            return $restoreBerhasil ? Process::result('; Archive created') : Process::result(errorOutput: 'bukan arsip', exitCode: 1);
        });
    }

    private function buatCadangan(string $nama, int $umurHari): void
    {
        Storage::disk('local')->put("cadangan/{$nama}", 'x');
        touch(Storage::disk('local')->path("cadangan/{$nama}"), now()->subDays($umurHari)->getTimestamp());
    }

    /**
     * @return list<string>
     */
    private function namaCadangan(): array
    {
        return array_map('basename', Storage::disk('local')->files('cadangan'));
    }

    public function test_membuat_berkas_cadangan_dan_memeriksanya_dengan_pg_restore(): void
    {
        $this->fakePostgres();

        $this->artisan('database:cadangkan')->assertSuccessful();

        $berkas = $this->namaCadangan();
        $this->assertCount(1, $berkas);
        $this->assertMatchesRegularExpression('/^mistborne-\d{8}-\d{6}\.dump$/', $berkas[0]);
        Process::assertRan(function (PendingProcess $proses): bool {
            $perintah = (array) $proses->command;

            return $perintah[0] === self::PG_DUMP
                && in_array('--format=custom', $perintah, true)
                && in_array('db.test', $perintah, true)
                && in_array('mistborne', $perintah, true)
                && $proses->environment['PGPASSWORD'] === 'rahasia';
        });
        Process::assertRan(fn (PendingProcess $proses): bool => (array) $proses->command !== [] && ((array) $proses->command)[0] === '/fake/bin/pg_restore' && in_array('--list', (array) $proses->command, true));
    }

    public function test_pg_dump_gagal_menghasilkan_galat_dan_tidak_meninggalkan_berkas(): void
    {
        $this->fakePostgres(dumpBerhasil: false);

        $this->artisan('database:cadangkan')
            ->expectsOutputToContain('pg_dump gagal: connection refused')
            ->assertFailed();

        $this->assertSame([], $this->namaCadangan());
    }

    public function test_cadangan_yang_tidak_lolos_pemeriksaan_dibuang(): void
    {
        $this->fakePostgres(restoreBerhasil: false);

        $this->artisan('database:cadangkan')
            ->expectsOutputToContain('tidak lolos pemeriksaan pg_restore')
            ->assertFailed();

        $this->assertSame([], $this->namaCadangan());
    }

    public function test_cadangan_kosong_dianggap_gagal_walau_pg_restore_menjawab_berhasil(): void
    {
        $this->fakePostgres(isi: '');

        $this->artisan('database:cadangkan')->assertFailed();

        $this->assertSame([], $this->namaCadangan());
    }

    public function test_menolak_koneksi_yang_bukan_postgresql(): void
    {
        if (config('database.default') === 'pgsql') {
            $this->markTestSkipped('Tes ini mengandaikan koneksi default bukan PostgreSQL.');
        }

        config(['mistborne.cadangan.koneksi' => null]);
        $this->fakePostgres();

        $this->artisan('database:cadangkan')
            ->expectsOutputToContain('hanya mendukung PostgreSQL')
            ->assertFailed();

        Process::assertNothingRan();
    }

    public function test_cadangan_lama_dihapus_tetapi_yang_terbaru_dan_berkas_lain_dipertahankan(): void
    {
        $this->fakePostgres();
        $this->buatCadangan('mistborne-20260101-020000.dump', 60);
        $this->buatCadangan('mistborne-20260201-020000.dump', 40);
        $this->buatCadangan('mistborne-20260301-020000.dump', 20);
        $this->buatCadangan('mistborne-20260401-020000.dump', 5);
        Storage::disk('local')->put('cadangan/catatan.txt', 'jangan dihapus');

        $this->artisan('database:cadangkan')->assertSuccessful();

        $sisa = $this->namaCadangan();
        $this->assertNotContains('mistborne-20260101-020000.dump', $sisa);
        $this->assertNotContains('mistborne-20260201-020000.dump', $sisa);
        $this->assertContains('mistborne-20260301-020000.dump', $sisa);
        $this->assertContains('mistborne-20260401-020000.dump', $sisa);
        $this->assertContains('catatan.txt', $sisa);
    }

    public function test_cadangan_baru_gagal_tidak_menghapus_cadangan_lama(): void
    {
        $this->fakePostgres(dumpBerhasil: false);
        $this->buatCadangan('mistborne-20260101-020000.dump', 90);

        $this->artisan('database:cadangkan')->assertFailed();

        $this->assertSame(['mistborne-20260101-020000.dump'], $this->namaCadangan());
    }

    public function test_semua_cadangan_tua_tetap_menyisakan_tiga_yang_terbaru(): void
    {
        $this->fakePostgres();
        foreach ([100, 90, 80, 70, 60] as $i => $umur) {
            $this->buatCadangan(sprintf('mistborne-2026010%d-020000.dump', $i + 1), $umur);
        }

        $this->artisan('database:cadangkan')->assertSuccessful();

        $sisa = $this->namaCadangan();
        $this->assertCount(3, $sisa);
        $this->assertContains('mistborne-20260105-020000.dump', $sisa);
        $this->assertContains('mistborne-20260104-020000.dump', $sisa);
        $this->assertNotContains('mistborne-20260101-020000.dump', $sisa);
    }

    public function test_cadangan_dijadwalkan_setiap_hari_pukul_dua_dini_hari_waktu_jakarta(): void
    {
        $acara = collect(app(Schedule::class)->events())->first(fn (Event $event): bool => str_contains($event->command, 'database:cadangkan'));

        $this->assertNotNull($acara);
        $this->assertSame('0 2 * * *', $acara->expression);
        $this->assertSame('Asia/Jakarta', (string) $acara->timezone);
    }
}
