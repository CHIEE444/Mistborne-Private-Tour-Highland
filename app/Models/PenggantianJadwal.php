<?php

namespace App\Models;

use Database\Factories\PenggantianJadwalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['pesanan_id', 'tanggal_lama', 'tanggal_baru', 'charge'])]
class PenggantianJadwal extends Model
{
    /** @use HasFactory<PenggantianJadwalFactory> */
    use HasFactory;

    protected $table = 'penggantian_jadwal';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_lama' => 'date',
            'tanggal_baru' => 'date',
            'charge' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Pesanan, $this>
     */
    public function pesanan(): BelongsTo
    {
        return $this->belongsTo(Pesanan::class);
    }
}
