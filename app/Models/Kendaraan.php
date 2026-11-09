<?php

namespace App\Models;

use App\Bantuan\UrlGambar;
use Database\Factories\KendaraanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['nama', 'jenis', 'plat_nomor', 'kapasitas', 'tahun', 'warna', 'bahan_bakar', 'odometer', 'stnk_berlaku_sampai', 'pajak_berlaku_sampai', 'status', 'foto', 'catatan'])]
class Kendaraan extends Model
{
    /** @use HasFactory<KendaraanFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'kendaraan';

    /** Jenis kendaraan yang bisa dipilih admin. */
    public const JENIS = [
        'minibus' => 'Minibus (Hiace, Elf)',
        'mpv' => 'MPV (Innova, Alphard)',
        'suv' => 'SUV',
        'jeep' => 'Jeep 4x4',
        'double_cabin' => 'Double Cabin 4x4 (Hilux)',
        'bus' => 'Bus',
    ];

    /**
     * Kategori yang tampil ke tamu (formulir booking dan permintaan) untuk tiap jenis kendaraan.
     * Satu sumber dengan pilihan kendaraan tamu dan daftar kendaraan yang dikuasai guide.
     */
    public const KATEGORI_TAMU = [
        'jeep' => 'Jeep klasik (petualangan dataran tinggi)',
        'double_cabin' => 'Double cabin 4x4',
        'suv' => 'SUV',
        'mpv' => 'MPV',
        'minibus' => 'Minibus',
        'bus' => 'Bus',
    ];

    /** Status operasional kendaraan. */
    public const STATUS = [
        'siap' => 'Siap jalan',
        'servis' => 'Dalam servis',
        'nonaktif' => 'Nonaktif',
    ];

    /** Bahan bakar yang bisa dipilih admin (kunci => label). */
    public const BAHAN_BAKAR = [
        'bensin' => 'Bensin',
        'diesel' => 'Diesel',
        'hybrid' => 'Hybrid',
        'listrik' => 'Listrik',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kapasitas' => 'integer',
            'tahun' => 'integer',
            'odometer' => 'integer',
            'stnk_berlaku_sampai' => 'date',
            'pajak_berlaku_sampai' => 'date',
        ];
    }

    /** Plat dirapikan: huruf besar, satu spasi antar bagian (mis. "b 1234 xyz" → "B 1234 XYZ"). */
    public static function rapikanPlat(string $plat): string
    {
        $bersih = mb_strtoupper(preg_replace('/\s+/', '', trim($plat)) ?? '');

        if (preg_match('/^([A-Z]{1,2})(\d{1,4})([A-Z]{0,3})$/', $bersih, $bagian) === 1) {
            return trim($bagian[1].' '.$bagian[2].' '.$bagian[3]);
        }

        return mb_strtoupper(trim(preg_replace('/\s+/', ' ', $plat) ?? ''));
    }

    /**
     * @return HasMany<Pesanan, $this>
     */
    public function pesanan(): HasMany
    {
        return $this->hasMany(Pesanan::class);
    }

    /**
     * Destinasi yang memakai kendaraan ini sebagai mobil tetapnya.
     *
     * @return HasMany<Perjalanan, $this>
     */
    public function perjalanan(): HasMany
    {
        return $this->hasMany(Perjalanan::class);
    }

    /**
     * @return HasMany<ServisKendaraan, $this>
     */
    public function servis(): HasMany
    {
        return $this->hasMany(ServisKendaraan::class);
    }

    /** @return list<string> */
    public static function daftarKategoriTamu(): array
    {
        return array_values(self::KATEGORI_TAMU);
    }

    public function kategoriTamu(): string
    {
        return self::KATEGORI_TAMU[$this->jenis] ?? $this->labelJenis();
    }

    /** Nama unit untuk tamu, mis. "Toyota Fortuner 2020" (tahun bila diisi admin). */
    public function namaUntukTamu(): string
    {
        return trim($this->nama.' '.($this->tahun ?: ''));
    }

    public function labelJenis(): string
    {
        return static::JENIS[$this->jenis] ?? ucfirst($this->jenis);
    }

    public function labelStatus(): string
    {
        return static::STATUS[$this->status] ?? ucfirst($this->status);
    }

    public function labelBahanBakar(): ?string
    {
        return static::BAHAN_BAKAR[$this->bahan_bakar] ?? null;
    }

    public function fotoUrl(): ?string
    {
        return UrlGambar::dari($this->foto);
    }

    /**
     * Kendaraan yang boleh ditugaskan: berstatus siap.
     *
     * @param  Builder<Kendaraan>  $query
     * @return Builder<Kendaraan>
     */
    public function scopeSiap(Builder $query): Builder
    {
        return $query->where('status', 'siap');
    }
}
