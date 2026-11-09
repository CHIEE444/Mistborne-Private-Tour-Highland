<?php

namespace App\Models;

use Database\Factories\UlasanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['pesanan_id', 'rating', 'komentar', 'video_path', 'izin_tampil', 'disetujui'])]
class Ulasan extends Model
{
    /** @use HasFactory<UlasanFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'ulasan';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'izin_tampil' => 'boolean',
            'disetujui' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Pesanan, $this>
     */
    public function pesanan(): BelongsTo
    {
        return $this->belongsTo(Pesanan::class);
    }

    /** Tampil di situs publik hanya bila disetujui admin DAN tamu mengizinkan. */
    public function tampilPublik(): bool
    {
        return $this->disetujui && $this->izin_tampil;
    }

    /**
     * @param  Builder<Ulasan>  $query
     * @return Builder<Ulasan>
     */
    public function scopeTampilPublik(Builder $query): Builder
    {
        return $query->where('disetujui', true)->where('izin_tampil', true);
    }
}
