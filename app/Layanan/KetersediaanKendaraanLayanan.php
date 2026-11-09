<?php

namespace App\Layanan;

use App\Bantuan\PilihanKendaraan;
use App\Enums\BookingStatus;
use App\Models\Kendaraan;
use App\Models\Perjalanan;
use App\Models\Pesanan;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Menentukan kendaraan mana yang boleh ditugaskan ke sebuah pesanan: berstatus siap, cukup kursi
 * untuk jumlah wisatawan ditambah guide yang merangkap driver (config mistborne.kursi_staf), dan tidak dipakai
 * pesanan aktif lain pada rentang tanggal yang sama.
 */
class KetersediaanKendaraanLayanan
{
    /** Kursi yang dibutuhkan pesanan: tamu + guide (merangkap driver) yang ikut di kendaraan. */
    public function kursiDibutuhkan(Pesanan $pesanan): int
    {
        return (int) $pesanan->jumlah_wisatawan + (int) config('mistborne.kursi_staf', 1);
    }

    /** Apakah unit ini model yang dipilih tamu di formulir (nilai tersimpan "Kategori — Nama unit tahun"). */
    public function cocokPilihanTamu(Kendaraan $kendaraan, Pesanan $pesanan): bool
    {
        if (blank($pesanan->kendaraan_pilihan)) {
            return false;
        }

        return $pesanan->kendaraan_pilihan === PilihanKendaraan::nilai($kendaraan->kategoriTamu(), $kendaraan->namaUntukTamu());
    }

    /** @return Collection<int, string> id kendaraan => label pilihan */
    public function daftarTersedia(Pesanan $pesanan): Collection
    {
        $idSaatIni = $pesanan->kendaraan_id ? (int) $pesanan->kendaraan_id : null;
        $idBentrok = $this->idYangBentrok($pesanan->tanggal_trip, $pesanan->tanggal_selesai ?? $pesanan->tanggal_trip, $pesanan->id);

        return Kendaraan::query()
            ->where(function ($query) use ($pesanan, $idBentrok, $idSaatIni): void {
                $query->where('status', 'siap')
                    ->where('kapasitas', '>=', $this->kursiDibutuhkan($pesanan))
                    ->whereNotIn('id', $idBentrok);
                $query->when($idSaatIni, fn ($sub) => $sub->orWhere('id', $idSaatIni));
            })
            ->orderBy('nama')
            ->get()
            ->sortByDesc(fn (Kendaraan $kendaraan): bool => $this->cocokPilihanTamu($kendaraan, $pesanan))
            ->mapWithKeys(function (Kendaraan $kendaraan) use ($idSaatIni, $pesanan, $idBentrok): array {
                $label = "{$kendaraan->namaUntukTamu()} · {$kendaraan->plat_nomor} · {$kendaraan->kapasitas} kursi";
                if ($this->cocokPilihanTamu($kendaraan, $pesanan)) {
                    $label .= ' · Mobil destinasi';
                }

                if ((int) $kendaraan->id === $idSaatIni) {
                    $masalah = $this->alasanTidakLayak($kendaraan, $pesanan, in_array((int) $kendaraan->id, $idBentrok, true));
                    $label .= $masalah ? " · Penugasan saat ini ({$masalah})" : '';
                }

                return [$kendaraan->id => $label];
            });
    }

    /**
     * Kendaraan yang otomatis ditugaskan ke pesanan baru: mobil utama journey bila layak dan kosong pada tanggal itu,
     * kalau tidak unit lain yang siap, muat tamu ditambah guide, dan tidak bentrok (kursi paling pas lebih dulu).
     * Null bila tidak ada satu pun, supaya admin menentukannya sendiri.
     */
    public function pilihOtomatis(Pesanan $pesanan): ?Kendaraan
    {
        $idBentrok = $this->idYangBentrok($pesanan->tanggal_trip, $pesanan->tanggal_selesai ?? $pesanan->tanggal_trip, $pesanan->id);
        $layak = fn (Kendaraan $kendaraan): bool => $kendaraan->status === 'siap'
            && $kendaraan->kapasitas >= $this->kursiDibutuhkan($pesanan)
            && ! in_array((int) $kendaraan->id, $idBentrok, true);

        $utama = $pesanan->perjalanan?->kendaraan;
        if ($utama !== null && $layak($utama)) {
            return $utama;
        }

        return Kendaraan::query()
            ->where('status', 'siap')
            ->where('kapasitas', '>=', $this->kursiDibutuhkan($pesanan))
            ->whereNotIn('id', $idBentrok)
            ->orderBy('kapasitas')
            ->orderBy('nama')
            ->first();
    }

    /** Alasan singkat kendaraan tidak layak untuk pesanan ini, atau null bila layak. */
    public function alasanTidakLayak(Kendaraan $kendaraan, Pesanan $pesanan, ?bool $bentrok = null): ?string
    {
        if ($kendaraan->trashed()) {
            return 'kendaraan sudah dihapus';
        }
        if ($kendaraan->status !== 'siap') {
            return 'status '.mb_strtolower($kendaraan->labelStatus());
        }
        if ($kendaraan->kapasitas < $this->kursiDibutuhkan($pesanan)) {
            return "kapasitas {$kendaraan->kapasitas} kursi kurang dari {$this->kursiDibutuhkan($pesanan)} kursi ({$pesanan->jumlah_wisatawan} peserta + guide yang merangkap driver)";
        }
        if ($bentrok ?? $this->bentrok((int) $kendaraan->id, $pesanan->tanggal_trip, $pesanan->tanggal_selesai ?? $pesanan->tanggal_trip, $pesanan->id)) {
            return 'jadwal bentrok';
        }

        return null;
    }

    /**
     * Pesanan yang masih menahan kendaraan ini tetapi kendaraannya tidak lagi layak (nonaktif, servis, dihapus,
     * atau kapasitas kurang). Bentrok jadwal tidak dihitung di sini karena punya pemeriksaan sendiri.
     *
     * @return Collection<int, array{pesanan: Pesanan, alasan: string}>
     */
    public function penugasanBermasalah(Kendaraan $kendaraan): Collection
    {
        return $kendaraan->pesanan()->menahanKendaraan()->orderBy('tanggal_trip')->orderBy('id')->get()
            ->map(fn (Pesanan $pesanan): array => ['pesanan' => $pesanan, 'alasan' => $this->alasanTidakLayak($kendaraan, $pesanan, false)])
            ->filter(fn (array $butir): bool => $butir['alasan'] !== null)
            ->values();
    }

    /**
     * Destinasi yang memakai kendaraan ini sebagai mobil tetap tetapi kendaraannya tidak lagi layak: status bukan Siap,
     * atau kursinya tidak lagi muat kapasitas maksimal destinasi ditambah guide yang merangkap driver.
     *
     * @return Collection<int, array{perjalanan: Perjalanan, alasan: string}>
     */
    public function destinasiBermasalah(Kendaraan $kendaraan): Collection
    {
        $kursiStaf = (int) config('mistborne.kursi_staf', 1);

        return $kendaraan->perjalanan()->orderBy('nama')->get(['id', 'nama', 'maks_orang', 'kendaraan_id'])
            ->map(function (Perjalanan $perjalanan) use ($kendaraan, $kursiStaf): array {
                $butuh = (int) $perjalanan->maks_orang + $kursiStaf;

                return ['perjalanan' => $perjalanan, 'alasan' => match (true) {
                    $kendaraan->status !== 'siap' => 'status '.mb_strtolower($kendaraan->labelStatus()),
                    $kendaraan->kapasitas < $butuh => "kapasitas {$kendaraan->kapasitas} kursi kurang dari {$butuh} kursi ({$perjalanan->maks_orang} tamu + guide)",
                    default => '',
                }];
            })
            ->filter(fn (array $butir): bool => $butir['alasan'] !== '')
            ->values();
    }

    public function bentrok(int $kendaraanId, CarbonInterface $mulai, CarbonInterface $selesai, ?int $kecualiPesananId = null): bool
    {
        return in_array($kendaraanId, $this->idYangBentrok($mulai, $selesai, $kecualiPesananId, $kendaraanId), true);
    }

    /** @return list<int> */
    private function idYangBentrok(CarbonInterface $mulai, CarbonInterface $selesai, ?int $kecualiPesananId, ?int $hanyaKendaraanId = null): array
    {
        return Pesanan::query()
            ->whereIn('status', [BookingStatus::Dihubungi->value, BookingStatus::Payment->value, BookingStatus::HoldSchedule->value])
            ->whereNotNull('kendaraan_id')
            ->when($kecualiPesananId, fn ($query, int $id) => $query->where('id', '<>', $id))
            ->when($hanyaKendaraanId, fn ($query, int $id) => $query->where('kendaraan_id', $id))
            ->whereDate('tanggal_trip', '<=', $selesai->toDateString())
            ->whereRaw('COALESCE(tanggal_selesai, tanggal_trip) >= ?', [$mulai->toDateString()])
            ->pluck('kendaraan_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
