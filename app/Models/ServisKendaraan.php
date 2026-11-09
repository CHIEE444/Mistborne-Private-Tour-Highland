<?php

namespace App\Models;

use Database\Factories\ServisKendaraanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['jenis', 'status', 'jatuh_tempo_tanggal', 'jatuh_tempo_odometer', 'tanggal_servis', 'odometer_servis', 'biaya', 'catatan'])]
class ServisKendaraan extends Model
{
    /** @use HasFactory<ServisKendaraanFactory> */
    use HasFactory;

    protected $table = 'servis_kendaraan';

    public const TERJADWAL = 'terjadwal';

    public const SELESAI = 'selesai';

    /** Status servis (kunci => label). */
    public const STATUS = [
        self::TERJADWAL => 'Terjadwal',
        self::SELESAI => 'Sudah dikerjakan',
    ];

    /** Jenis pekerjaan yang lazim; hanya saran di isian, admin bebas menulis sendiri. */
    public const JENIS_UMUM = [
        'Ganti oli mesin',
        'Servis berkala',
        'Tune-up & ganti filter',
        'Rotasi & cek ban',
        'Ganti ban',
        'Ganti kampas rem',
        'Servis AC',
        'Ganti aki',
        'Perbaikan lainnya',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jatuh_tempo_tanggal' => 'date',
            'jatuh_tempo_odometer' => 'integer',
            'tanggal_servis' => 'date',
            'odometer_servis' => 'integer',
            'biaya' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Kendaraan, $this>
     */
    public function kendaraan(): BelongsTo
    {
        return $this->belongsTo(Kendaraan::class);
    }

    /**
     * @param  Builder<ServisKendaraan>  $query
     * @return Builder<ServisKendaraan>
     */
    public function scopeTerjadwal(Builder $query): Builder
    {
        return $query->where('status', self::TERJADWAL);
    }

    /**
     * @param  Builder<ServisKendaraan>  $query
     * @return Builder<ServisKendaraan>
     */
    public function scopeSelesai(Builder $query): Builder
    {
        return $query->where('status', self::SELESAI);
    }

    public function sudahSelesai(): bool
    {
        return $this->status === self::SELESAI;
    }

    /** Servis terjadwal yang sudah lewat tanggal atau batas kilometernya (odometer kendaraan belum tentu terisi). */
    public function terlambat(?int $odometerKendaraan): bool
    {
        if ($this->sudahSelesai()) {
            return false;
        }

        return ($this->jatuh_tempo_tanggal !== null && $this->jatuh_tempo_tanggal->lt(today()))
            || ($this->jatuh_tempo_odometer !== null && $odometerKendaraan !== null && $odometerKendaraan >= $this->jatuh_tempo_odometer);
    }

    /** Keterangan jatuh tempo untuk tampilan, mis. "Jatuh tempo 15 Jul 2026 · 820 km lagi". */
    public function keteranganJatuhTempo(?int $odometerKendaraan): string
    {
        $bagian = [];

        if ($this->jatuh_tempo_tanggal !== null) {
            $bagian[] = ($this->jatuh_tempo_tanggal->lt(today()) ? 'Lewat sejak ' : 'Jatuh tempo ').$this->jatuh_tempo_tanggal->translatedFormat('d M Y');
        }

        if ($this->jatuh_tempo_odometer !== null) {
            $sisa = $odometerKendaraan === null ? null : $this->jatuh_tempo_odometer - $odometerKendaraan;
            $bagian[] = match (true) {
                $sisa === null => 'di '.number_format($this->jatuh_tempo_odometer, 0, ',', '.').' km',
                $sisa > 0 => number_format($sisa, 0, ',', '.').' km lagi',
                default => 'lewat '.number_format(abs($sisa), 0, ',', '.').' km',
            };
        }

        return implode(' · ', $bagian);
    }
}
