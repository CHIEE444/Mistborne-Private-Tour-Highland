<?php

namespace App\Aksi\Pesanan;

use App\Enums\BookingStatus;
use App\Enums\SumberPesanan;
use App\Layanan\KapasitasTripLayanan;
use App\Layanan\KetersediaanKendaraanLayanan;
use App\Models\Perjalanan;
use App\Models\Pesanan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Admin membuat pesanan langsung (tamu yang deal di WhatsApp/tanpa form publik). */
class BuatPesananManual
{
    public function __construct(private readonly KapasitasTripLayanan $kapasitas, private readonly KetersediaanKendaraanLayanan $ketersediaanKendaraan) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Perjalanan $perjalanan, array $data): Pesanan
    {
        return DB::transaction(function () use ($perjalanan, $data): Pesanan {
            if (Carbon::parse($data['tanggal_trip'])->startOfDay()->lt(now()->addDays(7)->startOfDay())) {
                throw ValidationException::withMessages([
                    'tanggal_trip' => 'Tanggal trip harus minimal tujuh hari dari hari ini (H-7).',
                ]);
            }

            // Tanggal harus ada di Tanggal Tersedia (kecuali trip kustom), kuota journey belum penuh, dan masih ada guide bebas.
            $this->kapasitas->pastikanBisaDipesan($perjalanan, $data['tanggal_trip'], isset($data['durasi_hari']) ? (int) $data['durasi_hari'] : null);

            $pesanan = Pesanan::create([
                ...$data,
                'perjalanan_id' => $perjalanan->id,
                'nama_perjalanan' => $perjalanan->nama,
                'harga' => $data['harga'] ?? $perjalanan->hargaDefault($data['status_penginapan'] ?? null, (int) ($data['jumlah_wisatawan'] ?? 1)),
                'sumber' => $data['sumber'] ?? SumberPesanan::Manual,
                'status' => $data['status'] ?? BookingStatus::Dihubungi,
            ]);

            // Mobil ditentukan sistem: mobil utama journey bila kosong, kalau tidak unit lain yang tersedia. Admin masih bisa menggantinya.
            if (blank($pesanan->kendaraan_id)) {
                $pesanan->setRelation('perjalanan', $perjalanan->loadMissing('kendaraan'));
                $kendaraan = $this->ketersediaanKendaraan->pilihOtomatis($pesanan);

                if ($kendaraan !== null) {
                    $pesanan->update(['kendaraan_id' => $kendaraan->id]);
                }
            }

            return $pesanan;
        });
    }
}
