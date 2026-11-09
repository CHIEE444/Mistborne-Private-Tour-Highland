<?php

namespace App\Models;

use App\Bantuan\BatasUnggah;
use Database\Factories\PengaturanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Tabel satu baris (singleton) — parameter operasional global yang bisa diubah admin
 * lewat menu Pengaturan, menggantikan nilai hardcode di Layanan/Request terkait.
 */
#[Fillable([
    'batas_bayar_hari',
    'persen_charge_ganti_jadwal',
    'kuota_default',
    'batas_ulasan_hari',
    'batas_video_mb',
    'nomor_whatsapp_admin',
    'rekomendasi_penginapan',
])]
class Pengaturan extends Model
{
    /** @use HasFactory<PengaturanFactory> */
    use HasFactory;

    protected $table = 'pengaturan';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'batas_bayar_hari' => 'integer',
            'persen_charge_ganti_jadwal' => 'integer',
            'kuota_default' => 'integer',
            'batas_ulasan_hari' => 'integer',
            'batas_video_mb' => 'integer',
        ];
    }

    /**
     * Batas video yang benar-benar berlaku: nilai terkecil antara pengaturan admin dan batas unggah PHP server.
     */
    public function batasVideoEfektifMb(): int
    {
        return BatasUnggah::efektifMb($this->batas_video_mb);
    }

    /**
     * Baris pengaturan pertama (id belum tentu 1), dibaca langsung dari DB agar perubahan admin langsung berlaku.
     */
    public static function current(): self
    {
        return static::query()->first() ?? static::query()->create()->fresh();
    }
}
