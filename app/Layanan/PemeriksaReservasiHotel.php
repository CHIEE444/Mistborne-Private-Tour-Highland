<?php

namespace App\Layanan;

use App\Enums\BookingStatus;
use App\Enums\StatusPenginapan;
use App\Models\Hotel;
use App\Models\Pesanan;
use Carbon\CarbonInterface;

/**
 * Memeriksa kecocokan reservasi hotel dengan pesanan sebelum draft WhatsApp dikirim. Satu sumber
 * untuk validasi form (item "keras" = wajib terpenuhi) dan panel "Periksa sebelum kirim" (semua item).
 */
class PemeriksaReservasiHotel
{
    /** Selisih hari maksimal check-in sebelum tanggal trip (tamu bisa menginap malam sebelumnya, mis. sunrise trip). */
    public const MAKS_HARI_SEBELUM_TRIP = 3;

    /** Selisih hari maksimal check-out setelah trip berakhir sebelum dianggap janggal. */
    public const MAKS_HARI_SETELAH_TRIP = 2;

    /**
     * @return list<array{kunci: string, label: string, ok: bool, keras: bool, keterangan: string, field: string}>
     */
    public function periksa(Pesanan $pesanan, ?Hotel $hotel, ?CarbonInterface $checkIn, ?CarbonInterface $checkOut, ?int $jumlahKamar, ?string $tipeKamar = null): array
    {
        $hasil = [];
        $tambah = function (string $kunci, string $label, bool $ok, bool $keras, string $keterangan, string $field) use (&$hasil): void {
            $hasil[] = compact('kunci', 'label', 'ok', 'keras', 'keterangan', 'field');
        };

        $statusPesananAktif = in_array($pesanan->status, [BookingStatus::Dihubungi, BookingStatus::Payment, BookingStatus::HoldSchedule], true);
        $tambah('pesanan', 'Pesanan masih aktif', $statusPesananAktif, true, $statusPesananAktif
            ? 'Status pesanan memungkinkan reservasi hotel.'
            : 'Pesanan yang sudah selesai atau dibatalkan tidak bisa dipesankan hotel.', 'hotel_id');

        $statusTamuCocok = $pesanan->status_penginapan === null || $pesanan->status_penginapan !== StatusPenginapan::Sudah;
        $tambah('penginapan', 'Tamu meminta MISTBORNE yang mengurus hotel', $statusTamuCocok, true, $statusTamuCocok
            ? 'Status penginapan tamu: '.($pesanan->status_penginapan?->label() ?? 'belum dipilih').'.'
            : 'Tamu sudah punya penginapan sendiri. Ubah status penginapannya dulu bila tamu ingin dipesankan.', 'hotel_id');

        $hotelAda = $hotel !== null;
        $hotelAktif = $hotelAda && $hotel->aktif && ! $hotel->trashed();
        $tambah('hotel', 'Hotel dipilih dan aktif', $hotelAktif, true, match (true) {
            ! $hotelAda => 'Pilih hotel dari daftar.',
            ! $hotelAktif => 'Hotel ini nonaktif. Pilih hotel lain atau aktifkan dulu di menu Hotel.',
            default => $hotel->nama.' · '.$hotel->area.'.',
        }, 'hotel_id');

        $punyaWa = $hotelAda && filled($hotel->whatsapp);
        $tambah('whatsapp', 'Hotel punya nomor WhatsApp', $punyaWa, false, $punyaWa
            ? 'Draft pesan akan dikirim ke '.$hotel->whatsapp.'.'
            : 'Nomor WhatsApp hotel belum diisi, jadi draft tidak bisa dikirim. Lengkapi di menu Hotel.', 'hotel_id');

        $mulaiTrip = $pesanan->tanggal_trip;
        $akhirTrip = $pesanan->tanggal_selesai ?? $pesanan->tanggal_trip;

        $checkInOk = $checkIn !== null
            && $checkIn->startOfDay()->lte($mulaiTrip->copy()->startOfDay())
            && $checkIn->startOfDay()->gte($mulaiTrip->copy()->subDays(self::MAKS_HARI_SEBELUM_TRIP)->startOfDay());
        $tambah('check_in', 'Tanggal check-in sesuai trip', $checkInOk, true, $checkIn === null
            ? 'Isi tanggal check-in.'
            : ($checkInOk
                ? 'Check-in '.$checkIn->translatedFormat('d M Y').' (trip mulai '.$mulaiTrip->translatedFormat('d M Y').').'
                : 'Check-in harus antara '.$mulaiTrip->copy()->subDays(self::MAKS_HARI_SEBELUM_TRIP)->translatedFormat('d M').' dan hari trip ('.$mulaiTrip->translatedFormat('d M Y').').'), 'hotel_check_in');

        $minimalCheckOut = $akhirTrip->copy()->startOfDay();
        if ($checkIn !== null && $minimalCheckOut->lte($checkIn->copy()->startOfDay())) {
            $minimalCheckOut = $checkIn->copy()->startOfDay()->addDay();
        }
        $checkOutOk = $checkOut !== null && $checkIn !== null
            && $checkOut->startOfDay()->gt($checkIn->copy()->startOfDay())
            && $checkOut->startOfDay()->gte($minimalCheckOut);
        $tambah('check_out', 'Tanggal check-out sesuai trip', $checkOutOk, true, $checkOut === null
            ? 'Isi tanggal check-out.'
            : ($checkOutOk
                ? 'Check-out '.$checkOut->translatedFormat('d M Y').' ('.$checkIn->diffInDays($checkOut).' malam).'
                : 'Check-out harus setelah check-in dan tidak sebelum trip berakhir ('.$akhirTrip->translatedFormat('d M Y').').'), 'hotel_check_out');

        if ($checkOut !== null && $checkOutOk && $checkOut->startOfDay()->gt($akhirTrip->copy()->addDays(self::MAKS_HARI_SETELAH_TRIP)->startOfDay())) {
            $tambah('check_out_lama', 'Check-out tidak terlalu lama setelah trip', false, false, 'Check-out lebih dari '.self::MAKS_HARI_SETELAH_TRIP.' hari setelah trip berakhir. Pastikan ini memang diminta tamu.', 'hotel_check_out');
        }

        $peserta = max(1, (int) $pesanan->jumlah_wisatawan);

        $perluTipe = $hotelAda && $hotel->punyaTipeKamarTerdaftar();
        $tipeOk = ! $perluTipe || ($tipeKamar !== null && $hotel->kapasitasTipe($tipeKamar) !== null);
        $tambah('tipe_kamar', 'Tipe kamar sesuai hotel', $tipeOk, true, match (true) {
            ! $perluTipe => 'Hotel ini belum punya daftar tipe kamar; kapasitas standar '.($hotelAda ? $hotel->kapasitas_kamar : '-').' orang per kamar dipakai.',
            $tipeOk => $tipeKamar.' menampung '.$hotel->kapasitasTipe($tipeKamar).' orang per kamar.',
            default => 'Pilih tipe kamar dari daftar milik '.$hotel->nama.'.',
        }, 'tipe_kamar');

        $perKamar = $hotelAda ? ($hotel->kapasitasTipe($tipeKamar) ?? $hotel->kapasitas_kamar + ($hotel->tersedia_extra_bed ? 1 : 0)) : 0;
        $kamarOk = $hotelAda && $jumlahKamar !== null && $jumlahKamar >= 1 && $jumlahKamar * $perKamar >= $peserta;
        $tambah('kamar', 'Kamar cukup untuk semua peserta', $kamarOk, true, match (true) {
            ! $hotelAda || $jumlahKamar === null => 'Isi jumlah kamar.',
            $kamarOk => $jumlahKamar.' kamar × '.$perKamar.' orang = '.($jumlahKamar * $perKamar).' kursi untuk '.$peserta.' peserta.',
            default => $jumlahKamar.' kamar hanya menampung '.($jumlahKamar * $perKamar).' orang, padahal peserta '.$peserta.'. Tambah jumlah kamar atau pilih tipe kamar yang lebih besar.',
        }, 'jumlah_kamar');

        $tamuPerKamar = max(1, (int) config('mistborne.biaya_perjalanan.tamu_per_kamar'));
        $kamarNyaman = (int) ceil($peserta / max(1, min($perKamar, $tamuPerKamar)));
        if ($kamarOk && $jumlahKamar < $kamarNyaman) {
            $tambah('kenyamanan', 'Standar nyaman '.$tamuPerKamar.' orang per kamar', false, false, "Dengan {$jumlahKamar} kamar, ada kamar yang diisi lebih dari {$tamuPerKamar} orang. Standar MISTBORNE: {$kamarNyaman} kamar untuk {$peserta} peserta agar tamu nyaman.", 'jumlah_kamar');
        }

        if ($hotelAda && $jumlahKamar !== null && $jumlahKamar > $peserta) {
            $tambah('kamar_berlebih', 'Jumlah kamar tidak berlebihan', false, false, "Kamar ({$jumlahKamar}) lebih banyak dari peserta ({$peserta}). Pastikan tamu memang ingin kamar sendiri-sendiri.", 'jumlah_kamar');
        }

        if ($hotelAda && $perKamar > 0 && $peserta % $perKamar !== 0 && $kamarOk) {
            $tambah('ganjil', 'Susunan kamar sesuai jumlah peserta', true, false, 'Jumlah peserta tidak habis dibagi isi kamar; satu kamar akan terisi lebih sedikit.', 'jumlah_kamar');
        }

        $tambah('tarif', 'Tarif hotel sudah diisi', $hotelAda && (bool) $hotel->tarif_mulai, false, $hotelAda && $hotel->tarif_mulai
            ? 'Tarif '.$hotel->rentangTarif().' per kamar per malam (dibayar tamu langsung ke hotel).'
            : 'Tarif hotel belum diisi di menu Hotel, jadi tamu belum bisa diberi perkiraan biaya.', 'hotel_id');

        return $hasil;
    }

    /**
     * Item wajib yang belum terpenuhi (menghalangi penyimpanan).
     *
     * @param  list<array{kunci: string, label: string, ok: bool, keras: bool, keterangan: string, field: string}>  $hasil
     * @return list<array{kunci: string, label: string, ok: bool, keras: bool, keterangan: string, field: string}>
     */
    public function kegagalanKeras(array $hasil): array
    {
        return array_values(array_filter($hasil, fn (array $item): bool => $item['keras'] && ! $item['ok']));
    }
}
