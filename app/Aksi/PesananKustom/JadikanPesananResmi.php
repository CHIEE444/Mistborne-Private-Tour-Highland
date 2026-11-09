<?php

namespace App\Aksi\PesananKustom;

use App\Aksi\Pesanan\BuatPesananManual;
use App\Enums\StatusPesananKustom;
use App\Enums\SumberPesanan;
use App\Models\Perjalanan;
use App\Models\Pesanan;
use App\Models\PesananKustom;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Mengubah permintaan kustom yang disepakati menjadi pesanan resmi dan menjaga tautannya.
 */
class JadikanPesananResmi
{
    public function __construct(private readonly BuatPesananManual $buatManual) {}

    /**
     * @param  array<string, mixed>  $dataTambahan  nama_hotel, lokasi_pickup, tanggal_trip
     */
    public function handle(PesananKustom $kustom, array $dataTambahan): Pesanan
    {
        return DB::transaction(function () use ($kustom, $dataTambahan): Pesanan {
            $kustom = PesananKustom::query()->whereKey($kustom->id)->lockForUpdate()->firstOrFail();

            if ($kustom->pesanan_id !== null) {
                throw ValidationException::withMessages([
                    'pesanan_kustom' => 'Permintaan ini sudah memiliki pesanan resmi.',
                ]);
            }

            if ($kustom->harga_final === null || $kustom->harga_final < 1 || $kustom->harga_final > PesananKustom::BATAS_HARGA_PER_ORANG) {
                throw ValidationException::withMessages([
                    'harga_final' => 'Isi harga final yang valid sebelum membuat pesanan resmi.',
                ]);
            }

            if ($kustom->status !== StatusPesananKustom::MenungguJawaban || $kustom->penawaran_dikirim_pada === null) {
                throw ValidationException::withMessages([
                    'status' => 'Kirim penawaran dan tandai menunggu jawaban tamu sebelum membuat pesanan resmi.',
                ]);
            }

            $penawaranAktif = $kustom->riwayatPenawaran()->first();
            if (! $penawaranAktif || $penawaranAktif->terkirim_pada->format('Y-m-d H:i:s.u') !== $kustom->penawaran_dikirim_pada->format('Y-m-d H:i:s.u')) {
                throw ValidationException::withMessages([
                    'status' => 'Tidak ditemukan riwayat penawaran aktif. Kirim ulang penawaran sebelum membuat pesanan resmi.',
                ]);
            }

            if (blank($kustom->rencana_perjalanan)) {
                throw ValidationException::withMessages([
                    'rencana_perjalanan' => 'Lengkapi rencana perjalanan sebelum membuat pesanan resmi.',
                ]);
            }

            if ($kustom->tanggal_fleksibel || $kustom->tanggal_diinginkan === null) {
                throw ValidationException::withMessages([
                    'tanggal_trip' => 'Sepakati dan simpan tanggal final pada data permintaan, lalu kirim ulang penawaran sebelum membuat pesanan resmi.',
                ]);
            }

            if ($kustom->batas_penawaran?->isBefore(today())) {
                throw ValidationException::withMessages([
                    'batas_penawaran' => 'Penawaran sudah kedaluwarsa. Perbarui dan kirim kembali penawarannya.',
                ]);
            }

            $tanggalTrip = Carbon::parse($dataTambahan['tanggal_trip'])->startOfDay();
            if (! $tanggalTrip->isSameDay($kustom->tanggal_diinginkan)) {
                throw ValidationException::withMessages([
                    'tanggal_trip' => 'Tanggal pesanan resmi harus sama dengan tanggal yang sudah disepakati pada permintaan kustom.',
                ]);
            }

            // Pesanan memiliki foreign key journey wajib untuk jadwal, kuota, dan kalender.
            // Gunakan satu journey internal sebagai jangkar operasional; nama tujuan dan durasi
            // kustom tetap disimpan sebagai snapshot pada pesanan masing-masing.
            $perjalanan = Perjalanan::withTrashed()->firstOrNew(['slug' => 'trip-kustom-operasional']);
            if ($perjalanan->exists && ! $perjalanan->template_pesanan_kustom) {
                throw ValidationException::withMessages([
                    'pesanan_kustom' => 'Kode journey internal trip kustom sedang dipakai data lain. Hubungi administrator sistem sebelum konversi.',
                ]);
            }
            if (! $perjalanan->exists) {
                $perjalanan->nama = 'Trip Kustom (Internal)';
                $perjalanan->slug = 'trip-kustom-operasional';
                $perjalanan->maks_orang = 50;
                $perjalanan->urutan = 0;
            }
            $perjalanan->template_pesanan_kustom = true;
            $perjalanan->tampil_beranda = false;
            if ($perjalanan->trashed()) {
                $perjalanan->restore();
            } else {
                $perjalanan->save();
            }

            $rincian = collect([
                $kustom->catatan ? 'Catatan pelanggan: '.$kustom->catatan : null,
                $kustom->rencana_perjalanan ? 'Rencana perjalanan: '.$kustom->rencana_perjalanan : null,
                $kustom->rincian_penawaran ? 'Rincian penawaran: '.$kustom->rincian_penawaran : null,
            ])->filter()->implode("\n\n");

            $pesanan = $this->buatManual->handle($perjalanan, [
                ...$dataTambahan,
                'nama_lengkap' => $kustom->nama,
                'whatsapp' => $kustom->whatsapp,
                'jumlah_wisatawan' => $kustom->jumlah_orang,
                'durasi_hari' => $kustom->durasi_hari,
                'permintaan_khusus' => $rincian ?: null,
                'harga' => $kustom->hargaTotal(),
                'sumber' => SumberPesanan::Kustom,
            ]);

            $pesanan->update([
                'nama_perjalanan' => 'Trip Kustom — '.$kustom->destinasi_diinginkan,
            ]);

            $kustom->update([
                'pesanan_id' => $pesanan->id,
                'status' => StatusPesananKustom::Disetujui,
                'tanggal_diinginkan' => $tanggalTrip,
                'tanggal_fleksibel' => false,
                'jadwal_diinginkan' => $tanggalTrip->toDateString(),
                'lokasi_penjemputan' => $dataTambahan['lokasi_pickup'],
            ]);

            return $pesanan;
        });
    }
}
