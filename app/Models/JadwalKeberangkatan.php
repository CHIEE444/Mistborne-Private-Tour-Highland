<?php

namespace App\Models;

use Database\Factories\JadwalKeberangkatanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['perjalanan_id', 'tanggal', 'kuota', 'diblok'])]
class JadwalKeberangkatan extends Model
{
    /** @use HasFactory<JadwalKeberangkatanFactory> */
    use HasFactory;

    protected $table = 'jadwal_keberangkatan';

    /** Diisi KuotaLayanan::terapkanKeDaftar() untuk hindari query terisi() per baris (N+1). */
    private ?int $terisiCache = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'kuota' => 'integer',
            'diblok' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Perjalanan, $this>
     */
    public function perjalanan(): BelongsTo
    {
        return $this->belongsTo(Perjalanan::class);
    }

    /**
     * Tanggal yang masih boleh dipesan: tidak diblok dan belum lewat.
     *
     * @param  Builder<JadwalKeberangkatan>  $query
     * @return Builder<JadwalKeberangkatan>
     */
    public function scopeTersedia(Builder $query): Builder
    {
        return $query->where('diblok', false)->whereDate('tanggal', '>=', now()->toDateString());
    }

    /** Kuota efektif: kuota khusus tanggal ini, atau default global bila kosong. */
    public function kuotaEfektif(): int
    {
        return $this->kuota ?? Pengaturan::current()->kuota_default;
    }

    /** Dipakai KuotaLayanan setelah hitung massal, supaya terisi() di bawah tidak query ulang. */
    public function isiTerisiCache(int $nilai): void
    {
        $this->terisiCache = $nilai;
    }

    /** Jumlah pesanan aktif yang menahan kuota pada tanggal ini, lintas journey. */
    public function terisi(): int
    {
        if ($this->terisiCache !== null) {
            return $this->terisiCache;
        }

        // Kuota per journey: hanya pesanan journey ini yang berangkat di tanggal ini.
        return Pesanan::query()
            ->terhitungKuota()
            ->where('perjalanan_id', $this->perjalanan_id)
            ->whereDate('tanggal_trip', $this->tanggal)
            ->count();
    }

    public function penuh(): bool
    {
        return $this->terisi() >= $this->kuotaEfektif();
    }

    /**
     * Status kuota untuk pewarnaan Kalender Trip: diblokir, penuh,
     * hampir penuh (kurang 1 slot), atau tersedia. Relatif terhadap kuotaEfektif(),
     * bukan angka tetap, supaya legenda ikut berubah bila kuota per tanggal beda.
     */
    public function statusKuota(): string
    {
        if ($this->diblok) {
            return 'diblokir';
        }

        $terisi = $this->terisi();
        $kuota = $this->kuotaEfektif();

        return match (true) {
            $terisi >= $kuota => 'penuh',
            $terisi > 0 && $terisi === $kuota - 1 => 'hampir_penuh',
            default => 'tersedia',
        };
    }

    /** Admin hanya boleh memblokir tanggal paling lambat H-14 dari hari ini. */
    public function bolehDiblokir(): bool
    {
        return now()->toDateString() <= $this->tanggal->copy()->subDays(14)->toDateString();
    }
}
