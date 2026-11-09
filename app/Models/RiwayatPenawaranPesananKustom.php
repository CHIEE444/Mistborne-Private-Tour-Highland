<?php

namespace App\Models;

use Database\Factories\RiwayatPenawaranPesananKustomFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['pesanan_kustom_id', 'user_id', 'versi', 'snapshot', 'terkirim_pada'])]
class RiwayatPenawaranPesananKustom extends Model
{
    /** @use HasFactory<RiwayatPenawaranPesananKustomFactory> */
    use HasFactory;

    protected $table = 'riwayat_penawaran_pesanan_kustom';

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'terkirim_pada' => 'datetime',
        ];
    }

    /** @return BelongsTo<PesananKustom, $this> */
    public function pesananKustom(): BelongsTo
    {
        return $this->belongsTo(PesananKustom::class);
    }

    /** @return BelongsTo<User, $this> */
    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
