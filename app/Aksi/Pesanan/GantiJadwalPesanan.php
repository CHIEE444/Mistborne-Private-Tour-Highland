<?php

namespace App\Aksi\Pesanan;

use App\Enums\BookingStatus;
use App\Enums\StatusReservasiHotel;
use App\Layanan\BiayaJadwalLayanan;
use App\Layanan\KapasitasTripLayanan;
use App\Layanan\KetersediaanKendaraanLayanan;
use App\Models\AktivitasPersonel;
use App\Models\Perjalanan;
use App\Models\Pesanan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Ganti tanggal trip pesanan. Charge dihitung BiayaJadwalLayanan (persen×harga hanya pada
 * ganti jadwal pertama saat sudah Pembayaran & sisa >H-7; selain itu gratis). Setiap perubahan
 * dicatat sebagai baris baru di penggantian_jadwal dan mereset flag pengingat H-7/H-2.
 */
class GantiJadwalPesanan
{
    public function __construct(
        private readonly BiayaJadwalLayanan $biayaJadwal,
        private readonly KapasitasTripLayanan $kapasitas,
    ) {}

    public function handle(Pesanan $pesanan, Carbon $tanggalBaru): Pesanan
    {
        $tanggalBaru = $tanggalBaru->copy()->startOfDay();

        if ($tanggalBaru->lt(now()->addDays(7)->startOfDay())) {
            throw ValidationException::withMessages([
                'tanggal_trip' => 'Tanggal trip harus minimal tujuh hari dari hari ini (H-7).',
            ]);
        }

        if ($tanggalBaru->gt(now()->addDays(7)->addYear()->startOfDay())) {
            throw ValidationException::withMessages([
                'tanggal_trip' => 'Tanggal baru hanya dapat dipilih sampai satu tahun sejak batas minimal H-7.',
            ]);
        }

        return DB::transaction(function () use ($pesanan, $tanggalBaru): Pesanan {
            // Pesanan dikunci lebih dulu agar aturan boleh-ganti dan biaya ganti pertama
            // dihitung dari data terbaru, bukan dari salinan yang bisa sudah berubah.
            $pesanan = Pesanan::query()->whereKey($pesanan->id)->lockForUpdate()->firstOrFail();

            if ($tanggalBaru->isSameDay($pesanan->tanggal_trip)) {
                throw ValidationException::withMessages([
                    'tanggal_trip' => 'Pilih tanggal yang berbeda dari jadwal saat ini.',
                ]);
            }

            if (! $this->biayaJadwal->bolehGantiJadwal($pesanan)) {
                throw ValidationException::withMessages([
                    'tanggal' => 'Perubahan jadwal tidak dapat diajukan mulai H-7 atau karena status pesanan ini.',
                ]);
            }

            $durasiHari = $pesanan->durasi_hari ?? $pesanan->perjalanan?->durasi_hari;

            // Tanggal baru harus ada di Tanggal Tersedia, kuotanya belum penuh, dan masih ada guide bebas (pesanan ini sendiri tidak dihitung).
            $perjalanan = Perjalanan::withTrashed()->findOrFail($pesanan->perjalanan_id);
            $this->kapasitas->pastikanBisaDipesan($perjalanan, $tanggalBaru->toDateString(), $durasiHari ? (int) $durasiHari : null, $pesanan->id);

            $tanggalAkhirBaru = $durasiHari
                ? $tanggalBaru->copy()->addDays($durasiHari - 1)
                : $tanggalBaru->copy();
            $this->validasiBentrokPersonel($pesanan, 'guide_personel_id', 'status_konfirmasi_guide', $tanggalBaru, $tanggalAkhirBaru);

            if ($pesanan->guide && ! $pesanan->guide->simBerlakuSampai($tanggalAkhirBaru)) {
                throw ValidationException::withMessages([
                    'tanggal_trip' => "SIM guide {$pesanan->guide->nama} tidak berlaku sampai {$tanggalAkhirBaru->translatedFormat('d M Y')}. Pilih tanggal sebelum SIM habis, atau ganti/perbarui data guide dulu.",
                ]);
            }

            if ($pesanan->kendaraan_id && app(KetersediaanKendaraanLayanan::class)->bentrok((int) $pesanan->kendaraan_id, $tanggalBaru, $tanggalAkhirBaru, $pesanan->id)) {
                throw ValidationException::withMessages([
                    'tanggal_trip' => 'Jadwal baru bentrok dengan penugasan kendaraan yang sudah ada. Pilih tanggal lain atau ganti kendaraannya dulu.',
                ]);
            }

            $tanggalLama = $pesanan->tanggal_trip->copy();
            $charge = $this->biayaJadwal->hitungCharge($pesanan);

            $pesanan->penggantian()->create([
                'tanggal_lama' => $tanggalLama,
                'tanggal_baru' => $tanggalBaru,
                'charge' => $charge,
            ]);

            $pesanan->update([
                ...$this->geserReservasiHotel($pesanan, $tanggalLama->diffInDays($tanggalBaru, false)),
                'tanggal_lama' => $tanggalLama,
                'charge_ganti_jadwal' => $charge,
                'tanggal_trip' => $tanggalBaru,
                'tanggal_selesai' => $tanggalAkhirBaru,
                'status_konfirmasi_guide' => $pesanan->guide_personel_id ? 'menunggu' : null,
                'guide_dikonfirmasi_at' => null,
                'guide_trip_selesai_at' => null,
                'pengingat_h7_terkirim' => false,
                'pengingat_h3_terkirim' => false,
                'pengingat_h2_terkirim' => false,
            ]);

            if ($pesanan->guide_personel_id) {
                AktivitasPersonel::catat(
                    (int) $pesanan->guide_personel_id,
                    'jadwal_diganti',
                    "Jadwal {$pesanan->kode} diganti: {$tanggalLama->translatedFormat('d M Y')} → {$tanggalBaru->translatedFormat('d M Y')}. Konfirmasi tugas perlu diberikan ulang.",
                    $pesanan,
                );
            }

            return $pesanan->refresh();
        });
    }

    private function validasiBentrokPersonel(Pesanan $pesanan, string $kolomPenugasan, string $kolomKonfirmasi, Carbon $tanggalMulai, Carbon $tanggalSelesai): void
    {
        $personelId = $pesanan->{$kolomPenugasan};

        if (! $personelId) {
            return;
        }

        $bentrok = Pesanan::query()
            ->whereIn('status', [BookingStatus::Dihubungi, BookingStatus::Payment, BookingStatus::HoldSchedule])
            ->where('id', '<>', $pesanan->id)
            ->where($kolomPenugasan, $personelId)
            ->whereDate('tanggal_trip', '<=', $tanggalSelesai->toDateString())
            ->whereRaw('COALESCE(tanggal_selesai, tanggal_trip) >= ?', [$tanggalMulai->toDateString()])
            ->where(function ($query) use ($kolomKonfirmasi): void {
                $query->whereNull($kolomKonfirmasi)->orWhereIn($kolomKonfirmasi, ['menunggu', 'diterima']);
            })
            ->exists();

        if ($bentrok) {
            $peran = 'Guide';
            throw ValidationException::withMessages([
                'tanggal_trip' => "Jadwal baru bentrok dengan penugasan {$peran} yang sudah ada. Pilih tanggal lain atau atur ulang personelnya.",
            ]);
        }
    }

    /**
     * Reservasi hotel ikut berpindah sejauh selisih hari jadwal, supaya tanggal check-in/out tetap pada posisi yang sama
     * terhadap trip. Reservasi yang sudah terkonfirmasi kembali ke "diminta" karena hotel belum menyetujui tanggal baru;
     * reservasi yang dibatalkan dibiarkan.
     *
     * @return array<string, mixed>
     */
    private function geserReservasiHotel(Pesanan $pesanan, int $selisihHari): array
    {
        if ($pesanan->hotel_id === null || $pesanan->hotel_check_in === null || $pesanan->status_reservasi_hotel === StatusReservasiHotel::Dibatalkan) {
            return [];
        }

        return [
            'hotel_check_in' => $pesanan->hotel_check_in->copy()->addDays($selisihHari)->toDateString(),
            'hotel_check_out' => $pesanan->hotel_check_out?->copy()->addDays($selisihHari)->toDateString(),
            'status_reservasi_hotel' => StatusReservasiHotel::Diminta,
        ];
    }
}
