<?php

namespace App\Layanan;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Cadangan database PostgreSQL lewat pg_dump format custom (terkompresi, dipulihkan dengan pg_restore).
 * Berkas ditulis ke berkas sementara dan baru diganti namanya setelah lolos pemeriksaan pg_restore --list,
 * jadi cadangan yang rusak atau setengah jadi tidak pernah muncul sebagai cadangan yang valid.
 */
class CadanganDatabaseLayanan
{
    /** Awalan dan ekstensi nama berkas cadangan; pemangkasan hanya menyentuh berkas yang cocok pola ini. */
    private const AWALAN = 'mistborne-';

    private const EKSTENSI = '.dump';

    /**
     * Buat cadangan baru dan kembalikan path absolutnya.
     *
     * @throws RuntimeException bila driver bukan PostgreSQL, pg_dump tidak ditemukan, atau dump gagal/tidak terbaca.
     */
    public function cadangkan(): string
    {
        $koneksi = $this->koneksi();
        $pgDump = $this->binariPgDump();
        $disk = Storage::disk('local');
        $folder = config('mistborne.cadangan.folder');
        $disk->makeDirectory($folder);

        $nama = self::AWALAN.now()->format('Ymd-His').self::EKSTENSI;
        $tujuan = $disk->path("{$folder}/{$nama}");
        $sementara = $tujuan.'.sementara';

        $hasil = Process::timeout(600)
            ->env(['PGPASSWORD' => (string) ($koneksi['password'] ?? '')])
            ->run([
                $pgDump, '--format=custom', '--no-owner', '--no-privileges',
                '--host', (string) $koneksi['host'], '--port', (string) $koneksi['port'],
                '--username', (string) $koneksi['username'], '--dbname', (string) $koneksi['database'],
                '--file', $sementara,
            ]);

        if ($hasil->failed()) {
            @unlink($sementara);

            throw new RuntimeException('pg_dump gagal: '.trim($hasil->errorOutput() ?: 'tanpa pesan galat').'.');
        }

        $periksa = Process::timeout(120)->run([dirname($pgDump).'/pg_restore', '--list', $sementara]);

        if ($periksa->failed() || ! is_file($sementara) || filesize($sementara) === 0) {
            @unlink($sementara);

            throw new RuntimeException('Cadangan dibuat tetapi tidak lolos pemeriksaan pg_restore, jadi dibuang: '.trim($periksa->errorOutput() ?: 'berkas kosong').'.');
        }

        rename($sementara, $tujuan);

        return $tujuan;
    }

    /**
     * Hapus cadangan yang lebih tua dari batas simpan, tetapi selalu sisakan beberapa yang terbaru
     * (supaya jam server yang salah atau cadangan yang terhenti tidak mengosongkan folder).
     *
     * @return int jumlah berkas yang dihapus
     */
    public function pangkas(): int
    {
        $disk = Storage::disk('local');
        $batas = now()->subDays(max(1, (int) config('mistborne.cadangan.simpan_hari')))->getTimestamp();
        $minimal = max(1, (int) config('mistborne.cadangan.minimal_disimpan'));

        $kandidat = collect($this->daftar())
            ->slice($minimal)
            ->filter(fn (array $berkas): bool => $berkas['diubah'] < $batas);

        foreach ($kandidat as $berkas) {
            $disk->delete($berkas['path']);
        }

        return $kandidat->count();
    }

    /**
     * Cadangan yang ada, terbaru lebih dulu.
     *
     * @return list<array{path: string, nama: string, ukuran: int, diubah: int}>
     */
    public function daftar(): array
    {
        $disk = Storage::disk('local');

        return collect($disk->files(config('mistborne.cadangan.folder')))
            ->filter(fn (string $path): bool => str_starts_with(basename($path), self::AWALAN) && str_ends_with($path, self::EKSTENSI))
            ->map(fn (string $path): array => [
                'path' => $path,
                'nama' => basename($path),
                'ukuran' => $disk->size($path),
                'diubah' => $disk->lastModified($path),
            ])
            ->sortByDesc('nama')
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function koneksi(): array
    {
        $nama = config('mistborne.cadangan.koneksi') ?: config('database.default');
        $koneksi = config("database.connections.{$nama}");

        if (($koneksi['driver'] ?? null) !== 'pgsql') {
            throw new RuntimeException("Cadangan ini hanya mendukung PostgreSQL, sedangkan koneksi \"{$nama}\" memakai driver \"".($koneksi['driver'] ?? '-').'".');
        }

        return $koneksi;
    }

    private function binariPgDump(): string
    {
        $diatur = config('mistborne.cadangan.pg_dump');

        if (filled($diatur)) {
            return $diatur;
        }

        $dariPath = trim(Process::run(['sh', '-c', 'command -v pg_dump'])->output());
        $kandidat = array_merge(
            $dariPath !== '' ? [$dariPath] : [],
            glob('/Library/PostgreSQL/*/bin/pg_dump') ?: [],
            glob('/opt/homebrew/opt/postgresql*/bin/pg_dump') ?: [],
            ['/opt/homebrew/bin/pg_dump', '/usr/local/bin/pg_dump', '/usr/bin/pg_dump'],
        );

        foreach ($kandidat as $path) {
            if (is_file($path) && is_executable($path)) {
                return $path;
            }
        }

        throw new RuntimeException('pg_dump tidak ditemukan. Pasang klien PostgreSQL, atau isi PG_DUMP_PATH di .env dengan lokasi pg_dump.');
    }
}
