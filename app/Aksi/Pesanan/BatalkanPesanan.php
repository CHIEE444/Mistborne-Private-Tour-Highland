<?php

namespace App\Aksi\Pesanan;

use App\Enums\BookingStatus;
use App\Models\AktivitasPersonel;
use App\Models\Pesanan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Pembatalan pesanan. Hanya untuk pesanan aktif (dihubungi/payment/hold_schedule).
 * Selesai berarti trip sudah dijalankan sehingga berstatus final dan tidak bisa dibatalkan.
 */
class BatalkanPesanan
{
    /** Pilihan pihak_pembatal → label tampilan. */
    public const PIHAK = [
        'pelanggan' => 'Pelanggan',
        'mistborne' => 'MISTBORNE',
    ];

    public function bolehDibatalkan(Pesanan $pesanan): bool
    {
        return in_array($pesanan->status, [BookingStatus::Dihubungi, BookingStatus::Payment, BookingStatus::HoldSchedule], true);
    }

    /** Persen refund untuk pembatalan pelanggan: >=H-7 100%, H-6 s/d H-2 50%, setelah itu 0%. */
    public function persenRefundPelanggan(Pesanan $pesanan): int
    {
        $sisaHari = $pesanan->sisaHari();

        return match (true) {
            $sisaHari >= 7 => 100,
            $sisaHari >= 2 => 50,
            default => 0,
        };
    }

    /** Saran nominal refund otomatis (bisa diedit admin sebelum disimpan) F.3. */
    public function saranRefund(Pesanan $pesanan, string $pihakPembatal): int
    {
        $sudahDibayar = (int) ($pesanan->nominal_dibayar ?? 0);
        $totalTagihan = $pesanan->totalTagihan() ?? 0;

        if ($pihakPembatal === 'mistborne') {
            return min($totalTagihan, $sudahDibayar);
        }

        return min((int) round($totalTagihan * $this->persenRefundPelanggan($pesanan) / 100), $sudahDibayar);
    }

    public function handle(Pesanan $pesanan, string $pihakPembatal, string $alasan, int $nominalRefund): Pesanan
    {
        return DB::transaction(function () use ($pesanan, $pihakPembatal, $alasan, $nominalRefund): Pesanan {
            $pesanan = Pesanan::query()->whereKey($pesanan->id)->lockForUpdate()->firstOrFail();

            if (! $this->bolehDibatalkan($pesanan)) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya pesanan aktif yang bisa dibatalkan. Pesanan Selesai atau yang sudah dibatalkan bersifat final.',
                ]);
            }

            if ($nominalRefund > (int) ($pesanan->nominal_dibayar ?? 0)) {
                throw ValidationException::withMessages([
                    'nominal_refund' => 'Nominal refund tidak boleh melebihi pembayaran yang sudah diterima.',
                ]);
            }

            $pesanan->update([
                'status' => BookingStatus::Dibatalkan,
                'status_sebelum_batal' => $pesanan->status->value,
                'pihak_pembatal' => $pihakPembatal,
                'alasan_batal' => $alasan,
                'nominal_refund' => $nominalRefund,
            ]);

            if ($pesanan->guide_personel_id) {
                AktivitasPersonel::catat((int) $pesanan->guide_personel_id, 'dibatalkan', "Pesanan {$pesanan->kode} ({$pesanan->nama_perjalanan}) dibatalkan. Tugas Anda untuk trip ini berakhir.", $pesanan);
            }

            return $pesanan;
        });
    }
}
