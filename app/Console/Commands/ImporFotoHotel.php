<?php

namespace App\Console\Commands;

use App\Bantuan\KonversiGambar;
use App\Models\Hotel;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Memasang foto hotel sekaligus dari satu folder. Nama berkas menentukan hotelnya: id hotel (mis. 4.jpg)
 * atau nama hotel (mis. bromo-permai-1.jpg). Foto dikonversi ke WebP dan menggantikan foto lama.
 */
#[Signature('hotel:impor-foto {folder : Folder berisi foto (jpg, png, webp)}')]
#[Description('Pasang foto hotel dari satu folder; nama berkas = id atau nama hotel')]
class ImporFotoHotel extends Command
{
    public function handle(): int
    {
        $folder = (string) $this->argument('folder');

        if (! is_dir($folder)) {
            $this->error("Folder tidak ditemukan: {$folder}");

            return self::FAILURE;
        }

        $berkas = collect(glob(rtrim($folder, '/').'/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}', GLOB_BRACE) ?: []);
        $hotel = Hotel::query()->get();
        $terpasang = 0;
        $tidakCocok = [];

        foreach ($berkas as $path) {
            $nama = pathinfo($path, PATHINFO_FILENAME);
            $cocok = ctype_digit($nama)
                ? $hotel->firstWhere('id', (int) $nama)
                : $hotel->first(fn (Hotel $item): bool => Str::slug($item->nama) === Str::slug($nama));

            if (! $cocok) {
                $tidakCocok[] = basename($path);

                continue;
            }

            if ($cocok->foto && str_starts_with($cocok->foto, '/storage/')) {
                Storage::disk('public')->delete(ltrim(str_replace('/storage/', '', $cocok->foto), '/'));
            }

            $unggahan = new UploadedFile($path, basename($path), null, null, true);
            $cocok->update(['foto' => '/storage/'.KonversiGambar::sampul($unggahan, 'hotel', lebar: 1600, tinggi: 1200, kualitas: 90)]);
            $this->info("✓ {$cocok->nama} ← ".basename($path));
            $terpasang++;
        }

        foreach ($tidakCocok as $nama) {
            $this->warn("Tidak cocok dengan hotel mana pun: {$nama}");
        }

        $tanpaFoto = Hotel::query()->whereNull('foto')->pluck('nama');
        $this->line("{$terpasang} foto terpasang. Hotel yang masih tanpa foto: ".($tanpaFoto->isEmpty() ? 'tidak ada' : $tanpaFoto->implode(', ')).'.');

        return self::SUCCESS;
    }
}
