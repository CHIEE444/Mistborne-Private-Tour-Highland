<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\PersonelOperasionalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'nama', 'peran', 'whatsapp', 'email', 'wilayah_operasi', 'pengalaman_tahun', 'keahlian', 'nomor_sim', 'jenis_sim', 'masa_berlaku_sim', 'pengalaman_mengemudi_tahun', 'kendaraan_dikuasai', 'status_operasional', 'foto_profil', 'catatan', 'aktif'])]
class PersonelOperasional extends Model
{
    /** @use HasFactory<PersonelOperasionalFactory> */
    use HasFactory;

    protected $table = 'personel_operasional';

    /** Satu-satunya peran: Guide yang merangkap Driver. */
    public const PERAN = 'guide';

    /** Golongan SIM yang sah untuk mengemudikan kendaraan penumpang MISTBORNE. */
    public const JENIS_SIM = [
        'A' => 'SIM A (mobil penumpang)',
        'B1' => 'SIM B1 (kendaraan besar)',
        'B2' => 'SIM B2 (kendaraan berat)',
    ];

    protected function casts(): array
    {
        return ['aktif' => 'boolean', 'masa_berlaku_sim' => 'date'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Apakah data mengemudi guide lengkap (SIM, golongan, dan masa berlaku terisi). */
    public function dataMengemudiLengkap(): bool
    {
        return filled($this->nomor_sim) && filled($this->jenis_sim) && $this->masa_berlaku_sim !== null;
    }

    /** SIM masih berlaku sampai tanggal tertentu (mis. hari terakhir trip). */
    public function simBerlakuSampai(CarbonInterface $tanggal): bool
    {
        return $this->masa_berlaku_sim !== null && $this->masa_berlaku_sim->startOfDay()->gte($tanggal->copy()->startOfDay());
    }
}
