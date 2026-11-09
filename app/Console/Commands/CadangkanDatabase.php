<?php

namespace App\Console\Commands;

use App\Layanan\CadanganDatabaseLayanan;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Number;
use RuntimeException;

/**
 * Cadangan harian database. Gagal berarti kode keluar bukan 0 dan galatnya masuk log, jadi kegagalan
 * jadwal tidak diam-diam dianggap berhasil.
 */
#[Signature('database:cadangkan')]
#[Description('Buat cadangan database (pg_dump) dan hapus cadangan yang sudah lama')]
class CadangkanDatabase extends Command
{
    public function handle(CadanganDatabaseLayanan $layanan): int
    {
        try {
            $path = $layanan->cadangkan();
        } catch (RuntimeException $galat) {
            logger()->error('Cadangan database gagal', ['pesan' => $galat->getMessage()]);
            $this->error($galat->getMessage());

            return self::FAILURE;
        }

        $this->info('Cadangan dibuat: '.$path.' ('.Number::fileSize(filesize($path)).')');

        $dihapus = $layanan->pangkas();
        $this->line("{$dihapus} cadangan lama dihapus; ".count($layanan->daftar()).' cadangan tersimpan.');
        $this->line('Pulihkan dengan: pg_restore --clean --if-exists --no-owner --dbname <nama_database> "'.$path.'"');
        $this->warn('Cadangan ini masih di komputer yang sama dengan database. Salin juga ke tempat lain (flashdisk, cloud) secara berkala.');

        return self::SUCCESS;
    }
}
