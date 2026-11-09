<?php

namespace App\Models;

use App\Enums\StatusPenginapan;
use Database\Factories\PermintaanPesananFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable([
    'token', 'token_booking', 'user_web_id', 'sumber', 'whatsapp', 'status', 'perjalanan_id', 'nama_lengkap', 'tanggal_trip',
    'jumlah_wisatawan', 'nama_hotel', 'alamat_hotel', 'kendaraan_pilihan', 'status_penginapan', 'lokasi_pickup', 'waktu_pickup', 'paket_opsional',
    'permintaan_khusus', 'catatan_admin', 'alasan_tidak_jadi', 'token_berlaku_sampai', 'pesanan_id',
])]
class PermintaanPesanan extends Model
{
    /** @use HasFactory<PermintaanPesananFactory> */
    use HasFactory;

    protected $table = 'permintaan_pesanan';

    /** Tautan formulir tamu hanya berlaku 1 hari; bila ada perubahan, admin mengirim tautan baru. */
    public const MASA_BERLAKU_JAM = 24;

    public static function batasBerlakuBaru(): Carbon
    {
        return now()->addHours(self::MASA_BERLAKU_JAM);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'tanggal_trip' => 'date',
            'token_berlaku_sampai' => 'datetime',
            'jumlah_wisatawan' => 'integer',
            'status_penginapan' => StatusPenginapan::class,
        ];
    }

    /** @return BelongsTo<Perjalanan, $this> */
    public function perjalanan(): BelongsTo
    {
        return $this->belongsTo(Perjalanan::class);
    }

    /** @return BelongsTo<Pesanan, $this> */
    public function pesanan(): BelongsTo
    {
        return $this->belongsTo(Pesanan::class);
    }

    /** Permintaan yang dikirim tamu sendiri lewat form booking website. */
    public function dariBookingWebsite(): bool
    {
        return $this->sumber === 'booking';
    }

    public function menungguTamu(): bool
    {
        return $this->status === 'menunggu_tamu'
            && $this->token_berlaku_sampai instanceof Carbon
            && $this->token_berlaku_sampai->isFuture();
    }

    public function dapatDiubahTamu(): bool
    {
        return in_array($this->status, ['menunggu_tamu', 'menunggu_tinjauan', 'ditindaklanjuti'], true)
            && $this->token_berlaku_sampai instanceof Carbon
            && $this->token_berlaku_sampai->isFuture();
    }
}
