<?php

namespace App\Models;

use App\Bantuan\FormatRupiah;
use App\Bantuan\UrlGambar;
use Database\Factories\HotelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use InvalidArgumentException;

#[Fillable([
    'nama', 'slug', 'bintang', 'area', 'alamat', 'deskripsi', 'jarak_destinasi', 'tautan_peta', 'google_place_id', 'google_rating', 'google_jumlah_ulasan', 'google_diperbarui', 'foto', 'galeri', 'whatsapp', 'email', 'nama_pic', 'tarif_mulai', 'tarif_sampai',
    'kapasitas_kamar', 'tersedia_extra_bed', 'tipe_kamar', 'fasilitas', 'jam_check_in', 'jam_check_out', 'kebijakan_pembatalan', 'catatan', 'aktif', 'sorotan',
])]
class Hotel extends Model
{
    /** @use HasFactory<HotelFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'hotel';

    /** Kelas bintang standar (minimum) hotel mitra MISTBORNE. */
    public const BINTANG_STANDAR = 4;

    /** Kelas bintang yang boleh dipakai hotel mitra: standar bintang 4, atau bintang 5 untuk hotel premium. */
    public const BINTANG_DIIZINKAN = [4, 5];

    /** @var array<string, mixed> */
    protected $attributes = ['bintang' => self::BINTANG_STANDAR];

    protected static function booted(): void
    {
        static::saving(function (Hotel $hotel): void {
            if (blank($hotel->slug) || $hotel->isDirty('nama') && ! $hotel->isDirty('slug') && $hotel->exists) {
                $hotel->slug = self::slugUnik((string) $hotel->nama, $hotel->id);
            }

            if (! in_array((int) $hotel->bintang, self::BINTANG_DIIZINKAN, true)) {
                throw new InvalidArgumentException('Hotel mitra MISTBORNE minimal bintang '.self::BINTANG_STANDAR.' (bintang '.implode(' atau ', self::BINTANG_DIIZINKAN).').');
            }
        });
    }

    /** Maksimal foto galeri per hotel (di luar foto utama). */
    public const MAKS_GALERI = 8;

    /** Kategori foto galeri (kunci => label). */
    public const LABEL_GALERI = [
        'kamar' => 'Kamar',
        'kolam_renang' => 'Kolam renang',
        'restoran' => 'Restoran',
        'lobi' => 'Lobi',
        'fasilitas' => 'Fasilitas',
        'pemandangan' => 'Pemandangan',
        'eksterior' => 'Eksterior',
    ];

    /** Fasilitas yang bisa dicentang admin (kunci => label). */
    public const FASILITAS = [
        'sarapan' => 'Sarapan',
        'ac' => 'AC',
        'air_panas' => 'Air panas',
        'wifi' => 'Wi-Fi',
        'parkir' => 'Parkir',
        'restoran' => 'Restoran',
        'kolam_renang' => 'Kolam renang',
        'antar_jemput' => 'Antar-jemput',
        'spa' => 'Spa',
        'pusat_kebugaran' => 'Pusat kebugaran',
        'kafe_bar' => 'Kafe / bar',
        'room_service' => 'Room service',
        'resepsionis_24' => 'Resepsionis 24 jam',
        'lift' => 'Lift',
        'ruang_pertemuan' => 'Ruang pertemuan',
    ];

    /** Slug dari nama hotel; diberi akhiran angka bila sudah dipakai hotel lain (termasuk yang sudah dihapus). */
    public static function slugUnik(string $nama, ?int $kecualiId = null): string
    {
        $dasar = Str::slug($nama) ?: 'hotel';
        $slug = $dasar;

        for ($i = 2; self::withTrashed()->where('slug', $slug)->when($kecualiId, fn ($query) => $query->whereKeyNot($kecualiId))->exists(); $i++) {
            $slug = "{$dasar}-{$i}";
        }

        return $slug;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bintang' => 'integer',
            'tarif_mulai' => 'integer',
            'tarif_sampai' => 'integer',
            'kapasitas_kamar' => 'integer',
            'tersedia_extra_bed' => 'boolean',
            'fasilitas' => 'array',
            'galeri' => 'array',
            'google_rating' => 'decimal:1',
            'google_jumlah_ulasan' => 'integer',
            'google_diperbarui' => 'datetime',
            'tipe_kamar' => 'array',
            'aktif' => 'boolean',
            'sorotan' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<Perjalanan, $this>
     */
    public function perjalanan(): BelongsToMany
    {
        return $this->belongsToMany(Perjalanan::class, 'hotel_perjalanan');
    }

    /**
     * @return HasMany<Pesanan, $this>
     */
    public function pesanan(): HasMany
    {
        return $this->hasMany(Pesanan::class);
    }

    /** Tautan lokasi di Google Maps: tautan yang diisi admin, atau pencarian berdasarkan nama dan area hotel. */
    public function petaUrl(): string
    {
        return filled($this->tautan_peta)
            ? $this->tautan_peta
            : 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($this->nama.' '.$this->area);
    }

    /** Tautan ke halaman Google Maps hotel (ulasan ada di sana): lewat Place ID bila ada, kalau tidak lewat tautan peta biasa. */
    public function tautanUlasanGoogle(): string
    {
        return filled($this->google_place_id)
            ? 'https://www.google.com/maps/place/?q=place_id:'.rawurlencode($this->google_place_id)
            : $this->petaUrl();
    }

    /**
     * Estimasi perjalanan destinasi–hotel untuk tampilan publik: tanpa tanda "±" dan rentang waktu
     * diringkas ke satu angka (batas atasnya), misalnya "± 1–1,5 jam" menjadi "1,5 jam".
     */
    public function estimasiPerjalanan(): ?string
    {
        if (blank($this->jarak_destinasi)) {
            return null;
        }

        $teks = preg_replace('/\s*(?:±|\+\/?-)\s*/u', ' ', $this->jarak_destinasi);
        $teks = preg_replace('/\d+(?:[.,]\d+)?\s*[–—-]\s*(\d+(?:[.,]\d+)?)/u', '$1', $teks);

        return trim(preg_replace('/\s{2,}/u', ' ', $teks));
    }

    public function fotoUrl(): ?string
    {
        return UrlGambar::dari($this->foto);
    }

    /**
     * Foto galeri untuk tampilan publik: URL dan label kategori yang sudah diterjemahkan.
     *
     * @return list<array{url: string, label: string}>
     */
    public function galeriPublik(): array
    {
        return collect($this->galeri ?? [])
            ->map(fn (array $baris): ?array => ($url = UrlGambar::dari($baris['foto'] ?? null)) === null ? null : [
                'url' => $url,
                'label' => __(self::LABEL_GALERI[$baris['label'] ?? ''] ?? 'Fasilitas'),
            ])
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Label fasilitas untuk tampilan publik (kunci tak dikenal dibuang).
     *
     * @return list<string>
     */
    public function labelFasilitas(): array
    {
        return collect($this->fasilitas ?? [])->map(fn (string $kunci): ?string => self::FASILITAS[$kunci] ?? null)->filter()->values()->all();
    }

    /**
     * @param  Builder<Hotel>  $query
     * @return Builder<Hotel>
     */
    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true);
    }

    /**
     * Tipe kamar hotel ini beserta kapasitasnya. Hotel tanpa tipe terdaftar memakai satu tipe standar
     * dari kapasitas standar per kamar, supaya admin selalu punya pilihan di dropdown reservasi.
     *
     * @return list<array{nama: string, kapasitas: int}>
     */
    public function daftarTipeKamar(): array
    {
        $tipe = collect($this->tipe_kamar ?? [])
            ->filter(fn ($baris): bool => is_array($baris) && filled($baris['nama'] ?? null))
            ->map(fn (array $baris): array => ['nama' => (string) $baris['nama'], 'kapasitas' => max(1, (int) ($baris['kapasitas'] ?? 2))])
            ->values()
            ->all();

        return $tipe !== [] ? $tipe : [['nama' => 'Kamar standar', 'kapasitas' => max(1, (int) $this->kapasitas_kamar + ($this->tersedia_extra_bed ? 1 : 0))]];
    }

    /** Apakah admin sudah mendaftarkan tipe kamar hotel ini (bukan tipe standar bawaan). */
    public function punyaTipeKamarTerdaftar(): bool
    {
        return collect($this->tipe_kamar ?? [])->contains(fn ($baris): bool => is_array($baris) && filled($baris['nama'] ?? null));
    }

    /**
     * Susunan kamar yang disarankan untuk sejumlah tamu, sama dengan aturan form reservasi admin:
     * satu kamar diisi paling banyak tamu_per_kamar orang (atau kapasitas tipenya bila lebih kecil),
     * lalu dipilih tipe dengan jumlah kamar paling sedikit; bila seri, tipe yang kapasitasnya terkecil.
     *
     * @return array{tipe: string, kapasitas: int, kamar: int}
     */
    public function susunanKamar(int $tamu): array
    {
        $tamu = max(1, $tamu);
        $tamuPerKamar = max(1, (int) config('mistborne.biaya_perjalanan.tamu_per_kamar'));

        return collect($this->daftarTipeKamar())
            ->map(fn (array $tipe): array => [
                'tipe' => $tipe['nama'],
                'kapasitas' => $tipe['kapasitas'],
                'kamar' => (int) ceil($tamu / max(1, min($tipe['kapasitas'], $tamuPerKamar))),
            ])
            ->sortBy([['kamar', 'asc'], ['kapasitas', 'asc']])
            ->first();
    }

    /** Kapasitas orang per kamar untuk tipe tertentu; null bila tipe tidak dikenal di hotel ini. */
    public function kapasitasTipe(?string $nama): ?int
    {
        $baris = collect($this->daftarTipeKamar())->firstWhere('nama', $nama);

        return $baris['kapasitas'] ?? null;
    }

    /** Teks rentang tarif per kamar per malam, mis. "Rp 450.000 – Rp 600.000"; null bila tarif belum diisi. */
    public function rentangTarif(): ?string
    {
        if (! $this->tarif_mulai) {
            return null;
        }

        $awal = FormatRupiah::dari($this->tarif_mulai);

        return $this->tarif_sampai && $this->tarif_sampai !== $this->tarif_mulai
            ? $awal.' – '.FormatRupiah::dari($this->tarif_sampai)
            : $awal;
    }

    /** Fasilitas sebagai teks pendek, mis. "Sarapan, AC, Air panas". */
    public function teksFasilitas(): string
    {
        return collect($this->fasilitas ?? [])
            ->map(fn (string $kunci): string => self::FASILITAS[$kunci] ?? $kunci)
            ->implode(', ');
    }

    /** Kapasitas standar per kamar mengikuti kapasitas maksimal (maks_orang) journey terbesar yang ditautkan, maksimal 10. */
    public function sinkronkanKapasitasDenganJourney(): void
    {
        $maks = (int) $this->perjalanan()->max('maks_orang');

        if ($maks > 0 && $this->kapasitas_kamar !== min(10, $maks)) {
            $this->update(['kapasitas_kamar' => min(10, $maks)]);
        }
    }

    public function labelBintang(): string
    {
        return str_repeat('★', max(1, min(5, (int) $this->bintang)));
    }

    /** Jam dalam format HH:MM (kolom time menyimpan detik). */
    public function jamCheckIn(): string
    {
        return substr((string) $this->jam_check_in, 0, 5);
    }

    public function jamCheckOut(): string
    {
        return substr((string) $this->jam_check_out, 0, 5);
    }
}
