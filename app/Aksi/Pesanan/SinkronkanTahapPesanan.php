<?php

namespace App\Aksi\Pesanan;

use App\Enums\BookingStatus;
use App\Layanan\BatasBayarLayanan;
use App\Layanan\EmailPenugasanPersonelLayanan;
use App\Models\Pesanan;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Perpindahan tahap berbasis waktu: trip yang usai dan syaratnya lengkap → Trip Selesai; pesanan deal tanpa bayar
 * dibatalkan setelah batas pembatalan. Jalan tiap 00:01 dan saat admin membuka Dashboard/Pesanan (maks. 5 menit sekali).
 */
class SinkronkanTahapPesanan
{
    private const KUNCI_JEDA = 'sinkron-tahap-pesanan';

    public function __construct(
        private readonly BatasBayarLayanan $batasBayar,
        private readonly BatalkanPesanan $batalkan,
        private readonly EmailPenugasanPersonelLayanan $email,
    ) {}

    /** @return array{selesai: int, dibatalkan: int} */
    public function handle(): array
    {
        return [
            'selesai' => $this->simpanTripSelesai(),
            'dibatalkan' => $this->batalkanYangTidakMembayar(),
        ];
    }

    /** Versi berjeda untuk halaman admin: tidak lebih dari sekali per 5 menit. */
    public function handleBilaPerlu(): void
    {
        if (Cache::add(self::KUNCI_JEDA, true, now()->addMinutes(5))) {
            $this->handle();
        }
    }

    private function simpanTripSelesai(): int
    {
        $jumlah = 0;

        Pesanan::query()
            ->with('penggantian')
            ->where('status', BookingStatus::Payment)
            ->whereDate('tanggal_trip', '<=', today()->toDateString())
            ->get()
            ->filter(fn (Pesanan $pesanan): bool => $pesanan->tahap() === 'selesai')
            ->each(function (Pesanan $pesanan) use (&$jumlah): void {
                $pesanan->update(['status' => BookingStatus::Completed]);
                $jumlah++;
            });

        return $jumlah;
    }

    private function batalkanYangTidakMembayar(): int
    {
        $jumlah = 0;

        Pesanan::query()
            ->where('status', BookingStatus::Dihubungi)
            ->whereNotNull('dikonfirmasi_at')
            ->where(fn ($query) => $query->whereNull('nominal_dibayar')->orWhere('nominal_dibayar', 0))
            ->get()
            ->filter(fn (Pesanan $pesanan): bool => $this->batasBayar->lewatBatasPembatalan($pesanan))
            ->each(function (Pesanan $pesanan) use (&$jumlah): void {
                try {
                    $hasil = $this->batalkan->handle(
                        $pesanan,
                        'mistborne',
                        "Dibatalkan otomatis: tidak ada pembayaran sampai {$this->batasBayar->hariPembatalan()} hari setelah konfirmasi (batas bayar {$this->batasBayar->jumlahHari()} hari).",
                        0,
                    );
                    $jumlah++;

                    if ($hasil->guide_personel_id !== null) {
                        $this->email->kirimPembatalan($hasil);
                    }
                } catch (Throwable $exception) {
                    logger()->warning('Pembatalan otomatis lewat tenggat bayar gagal.', ['pesanan_id' => $pesanan->id, 'exception' => $exception->getMessage()]);
                }
            });

        return $jumlah;
    }
}
