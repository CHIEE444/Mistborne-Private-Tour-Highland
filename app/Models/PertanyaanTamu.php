<?php

namespace App\Models;

use Database\Factories\PertanyaanTamuFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sumber', 'status', 'nama', 'whatsapp', 'journey', 'lokasi_pickup', 'tanggal_perjalanan',
    'durasi', 'jenis_perjalanan', 'pesan', 'catatan_admin', 'sidik_pengirim', 'permintaan_pesanan_id',
])]
class PertanyaanTamu extends Model
{
    /** @use HasFactory<PertanyaanTamuFactory> */
    use HasFactory;

    /** Label semua sumber, termasuk "cek_ketersediaan" dari filter beranda yang sudah dihapus (data lama tetap terbaca). */
    public const SUMBER = [
        'cek_ketersediaan' => 'Cek ketersediaan',
        'kontak' => 'Form kontak',
        'cta_whatsapp' => 'Tombol WhatsApp',
    ];

    /** Sumber yang masih bisa mengirim pertanyaan baru dari website. */
    public const SUMBER_AKTIF = ['kontak', 'cta_whatsapp'];

    public const STATUS = [
        'baru' => 'Baru',
        'dibalas' => 'Sudah dibalas',
        'jadi_booking' => 'Jadi booking',
        'tidak_lanjut' => 'Tidak lanjut',
    ];

    protected $table = 'pertanyaan_tamu';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['tanggal_perjalanan' => 'date'];
    }

    /** @return BelongsTo<PermintaanPesanan, $this> */
    public function permintaan(): BelongsTo
    {
        return $this->belongsTo(PermintaanPesanan::class, 'permintaan_pesanan_id');
    }

    public function labelSumber(): string
    {
        return self::SUMBER[$this->sumber] ?? $this->sumber;
    }

    public function labelStatus(): string
    {
        return self::STATUS[$this->status] ?? $this->status;
    }
}
