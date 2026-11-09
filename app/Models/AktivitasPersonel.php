<?php

namespace App\Models;

use Database\Factories\AktivitasPersonelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Jejak aktivitas guide (penugasan, tanggapan, perubahan jadwal, trip selesai, login) yang dapat dilihat guide sendiri. */
#[Fillable(['personel_id', 'pesanan_id', 'jenis', 'keterangan'])]
class AktivitasPersonel extends Model
{
    /** @use HasFactory<AktivitasPersonelFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    public const JENIS = [
        'ditugaskan' => 'Ditugaskan',
        'dilepas' => 'Penugasan dilepas',
        'jadwal_diganti' => 'Jadwal diganti',
        'catatan_admin' => 'Catatan admin',
        'diterima' => 'Tugas diterima',
        'ditolak' => 'Tugas ditolak',
        'trip_selesai' => 'Trip selesai',
        'dibatalkan' => 'Pesanan dibatalkan',
        'masuk' => 'Masuk panel',
        'sandi' => 'Kata sandi diganti',
    ];

    protected $table = 'aktivitas_personel';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    /** Mencatat satu aktivitas untuk guide; pesanan boleh kosong untuk aktivitas akun. */
    public static function catat(PersonelOperasional|int $personel, string $jenis, string $keterangan, Pesanan|int|null $pesanan = null): self
    {
        return self::query()->create([
            'personel_id' => $personel instanceof PersonelOperasional ? $personel->id : $personel,
            'pesanan_id' => $pesanan instanceof Pesanan ? $pesanan->id : $pesanan,
            'jenis' => $jenis,
            'keterangan' => $keterangan,
        ]);
    }

    public function labelJenis(): string
    {
        return self::JENIS[$this->jenis] ?? $this->jenis;
    }

    /**
     * @return BelongsTo<PersonelOperasional, $this>
     */
    public function personel(): BelongsTo
    {
        return $this->belongsTo(PersonelOperasional::class, 'personel_id');
    }

    /**
     * @return BelongsTo<Pesanan, $this>
     */
    public function pesanan(): BelongsTo
    {
        return $this->belongsTo(Pesanan::class);
    }
}
