<?php

namespace App\Layanan;

use App\Enums\BookingStatus;
use App\Enums\StatusPenginapan;
use App\Enums\StatusReservasiHotel;
use App\Models\Pesanan;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Empat tahap reservasi hotel: pesan kamar → hotel setuju → bayar hotel (khusus all-inclusive) → kabari tamu.
 * Satu sumber untuk stepper kartu hotel, antrean Selesaikan Hotel, dan aturan selesai.
 */
class AlurReservasiHotel
{
    /** Pengiriman yang ditandai admin (tombol kirim satu klik atau tombol tanda) => kolom waktunya di tabel pesanan. */
    public const TANDA = [
        'permintaan' => 'permintaan_hotel_dikirim_at',
        'bukti' => 'bukti_bayar_hotel_dikirim_at',
        'konfirmasi_bayar' => 'bayar_hotel_dikonfirmasi_at',
        'kabari_tamu' => 'tamu_dikabari_hotel_at',
        'ulang' => 'konfirmasi_ulang_hotel_at',
    ];

    /**
     * Pesanan yang hotelnya harus diurus admin (fitur Hotel > Urus Hotel): sudah Pembayaran, butuh hotel dari MISTBORNE,
     * dan trip belum selesai. Yang paling dekat tanggal trip tampil lebih dulu.
     *
     * @return Collection<int, Pesanan>
     */
    public function antrean(): Collection
    {
        return Pesanan::query()
            ->with(['hotel', 'perjalanan'])
            ->where('status', BookingStatus::Payment->value)
            ->whereIn('status_penginapan', [StatusPenginapan::Diurus->value, StatusPenginapan::Dipesankan->value, StatusPenginapan::Belum->value])
            ->whereRaw('COALESCE(tanggal_selesai, tanggal_trip) >= ?', [today()->toDateString()])
            ->orderBy('tanggal_trip')
            ->orderBy('id')
            ->get();
    }

    /** Semua langkah yang berlaku sudah selesai (stepper dan antrean sama-sama memakai ini). */
    public function sudahDiurus(Pesanan $pesanan): bool
    {
        return $this->adaReservasi($pesanan) && ! $this->dibatalkan($pesanan) && $this->langkahBerikutnya($pesanan) === null;
    }

    /**
     * Hotel yang dipesankan MISTBORNE (all-inclusive atau bayar ke hotel) adalah bagian dari pesanan: selama langkah
     * reservasinya belum tuntas, pesanan belum boleh ditandai Trip Selesai.
     */
    public function menghambatCompleted(Pesanan $pesanan): bool
    {
        return (bool) $pesanan->status_penginapan?->dipesankanMistborne() && ! $this->sudahDiurus($pesanan);
    }

    public function adaReservasi(Pesanan $pesanan): bool
    {
        return $pesanan->hotel_id !== null;
    }

    public function dibatalkan(Pesanan $pesanan): bool
    {
        return $pesanan->status_reservasi_hotel === StatusReservasiHotel::Dibatalkan;
    }

    /** MISTBORNE yang membayar hotel di muka hanya pada paket all-inclusive. */
    public function perluBayarKeHotel(Pesanan $pesanan): bool
    {
        return $pesanan->status_penginapan === StatusPenginapan::Diurus;
    }

    /**
     * Tahap yang berlaku untuk pesanan ini, bernomor urut. Tahap "Bayar hotel" hanya ada pada paket all-inclusive;
     * tamu yang membayar langsung di hotel cukup tiga tahap.
     *
     * @return list<array{kunci: string, nomor: int, judul: string, keterangan: string, status: string, waktu: ?CarbonInterface}>
     */
    public function langkah(Pesanan $pesanan): array
    {
        $selesai = $this->daftarSelesai($pesanan);

        $daftar = [
            ['kunci' => 'pesan', 'judul' => 'Pesan kamar ke hotel', 'keterangan' => 'Pilih hotel dan kamar, simpan, lalu kirim permintaan ke hotel lewat WhatsApp.', 'waktu' => $pesanan->permintaan_hotel_dikirim_at],
            ['kunci' => 'setuju', 'judul' => 'Hotel setuju', 'keterangan' => 'Setelah hotel membalas setuju, isi kode booking dari hotel.', 'waktu' => null],
            ...($this->perluBayarKeHotel($pesanan) ? [
                ['kunci' => 'bayar', 'judul' => 'Bayar hotel', 'keterangan' => 'Catat transfer dan unggah bukti, kirim buktinya ke hotel, lalu tandai setelah hotel menerima pembayaran.', 'waktu' => $pesanan->bayar_hotel_dikonfirmasi_at],
            ] : []),
            ['kunci' => 'kabari_tamu', 'judul' => 'Kabari tamu', 'keterangan' => 'Kirim info hotel, jam check-in dan check-out, serta kode booking ke tamu.', 'waktu' => $pesanan->tamu_dikabari_hotel_at],
        ];

        $berikutnyaDitemukan = false;

        return array_map(function (array $tahap, int $indeks) use ($selesai, &$berikutnyaDitemukan): array {
            if ($selesai[$tahap['kunci']]) {
                $status = 'selesai';
            } elseif (! $berikutnyaDitemukan) {
                $status = 'berikutnya';
                $berikutnyaDitemukan = true;
            } else {
                $status = 'menunggu';
            }

            return $tahap + ['nomor' => $indeks + 1, 'status' => $status];
        }, $daftar, array_keys($daftar));
    }

    /**
     * Isi tahap "Bayar hotel": catat transfer, kirim bukti, lalu hotel konfirmasi. Hanya satu yang bisa dikerjakan
     * sekarang (status "berikutnya"), sisanya menunggu urutan.
     *
     * @return list<array{kunci: string, judul: string, status: string, waktu: ?CarbonInterface}>
     */
    public function langkahBayar(Pesanan $pesanan): array
    {
        $daftar = [
            ['kunci' => 'transfer', 'judul' => 'Catat transfer dan unggah bukti', 'selesai' => $this->transferTercatat($pesanan), 'waktu' => $pesanan->bayar_hotel_tanggal],
            ['kunci' => 'bukti', 'judul' => 'Kirim bukti transfer ke hotel', 'selesai' => $pesanan->bukti_bayar_hotel_dikirim_at !== null, 'waktu' => $pesanan->bukti_bayar_hotel_dikirim_at],
            ['kunci' => 'konfirmasi_bayar', 'judul' => 'Hotel sudah terima pembayaran', 'selesai' => $pesanan->bayar_hotel_dikonfirmasi_at !== null, 'waktu' => $pesanan->bayar_hotel_dikonfirmasi_at],
        ];

        $berikutnyaDitemukan = false;

        return array_map(function (array $langkah) use (&$berikutnyaDitemukan): array {
            $status = $langkah['selesai'] ? 'selesai' : ($berikutnyaDitemukan ? 'menunggu' : 'berikutnya');
            $berikutnyaDitemukan = $berikutnyaDitemukan || ! $langkah['selesai'];

            return ['kunci' => $langkah['kunci'], 'judul' => $langkah['judul'], 'status' => $status, 'waktu' => $langkah['waktu']];
        }, $daftar);
    }

    /** @return array{kunci: string, nomor: int, judul: string, keterangan: string, status: string, waktu: ?CarbonInterface}|null */
    public function langkahBerikutnya(Pesanan $pesanan): ?array
    {
        foreach ($this->langkah($pesanan) as $langkah) {
            if ($langkah['status'] === 'berikutnya') {
                return $langkah;
            }
        }

        return null;
    }

    /** @return array{selesai: int, total: int} */
    public function ringkasan(Pesanan $pesanan): array
    {
        $tahap = $this->langkah($pesanan);

        return [
            'selesai' => count(array_filter($tahap, fn (array $item): bool => $item['status'] === 'selesai')),
            'total' => count($tahap),
        ];
    }

    /** Apakah tombol hijau untuk langkah ini boleh dipakai: prasyaratnya sudah terpenuhi dan langkahnya memang berlaku. */
    public function bolehDitandai(Pesanan $pesanan, string $kunci): bool
    {
        if (! array_key_exists($kunci, self::TANDA) || ! $this->adaReservasi($pesanan) || $this->dibatalkan($pesanan)) {
            return false;
        }

        $terkonfirmasi = $pesanan->status_reservasi_hotel === StatusReservasiHotel::Terkonfirmasi;

        return match ($kunci) {
            'permintaan' => true,
            'bukti' => $this->perluBayarKeHotel($pesanan) && $this->transferTercatat($pesanan),
            'konfirmasi_bayar' => $this->perluBayarKeHotel($pesanan) && $pesanan->bukti_bayar_hotel_dikirim_at !== null,
            'kabari_tamu', 'ulang' => $terkonfirmasi,
            default => false,
        };
    }

    private function transferTercatat(Pesanan $pesanan): bool
    {
        return $pesanan->bayar_hotel_nominal !== null && $pesanan->bayar_hotel_tanggal !== null;
    }

    /**
     * Hotel yang sudah setuju pasti sudah menerima permintaan, jadi tahap pesan dianggap selesai walau tanda kirimnya kosong.
     *
     * @return array<string, bool>
     */
    private function daftarSelesai(Pesanan $pesanan): array
    {
        $terkonfirmasi = $pesanan->status_reservasi_hotel === StatusReservasiHotel::Terkonfirmasi;

        return [
            'pesan' => $this->adaReservasi($pesanan) && ($pesanan->permintaan_hotel_dikirim_at !== null || $terkonfirmasi),
            'setuju' => $terkonfirmasi,
            'bayar' => $this->transferTercatat($pesanan) && $pesanan->bukti_bayar_hotel_dikirim_at !== null && $pesanan->bayar_hotel_dikonfirmasi_at !== null,
            'kabari_tamu' => $pesanan->tamu_dikabari_hotel_at !== null,
        ];
    }
}
