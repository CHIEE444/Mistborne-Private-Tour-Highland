<?php

namespace App\Models;

use App\Bantuan\FormatRupiah;
use App\Enums\BookingStatus;
use Database\Factories\PerjalananFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'jenis', 'label_unggulan', 'nama', 'slug', 'region', 'chip', 'tempat', 'tagline', 'deskripsi', 'harga',
    'durasi_hari', 'maks_orang', 'kendaraan_id', 'alasan_kendaraan', 'img', 'highlights', 'itinerary', 'praktis',
    'tone', 'rating_label', 'reviews_count',
    'tampil_beranda', 'urutan', 'template_pesanan_kustom', 'rekomendasi_penginapan',
])]
class Perjalanan extends Model
{
    /** @use HasFactory<PerjalananFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'perjalanan';

    /** Jenis produk: journey biasa atau Paket Relaksasi Alam (dipesan dengan alur yang sama). */
    public const JENIS_JOURNEY = 'journey';

    public const JENIS_PAKET = 'paket';

    /** Gambar cadangan bila sampul journey dihapus admin. */
    public const SAMPUL_CADANGAN = '/images/hero-2.jpg';

    /**
     * Region yang bisa dipilih admin (slug => label). Slug dipakai filter tab di situs publik.
     *
     * @return array<string, string>
     */
    public static function daftarRegion(): array
    {
        return [
            'jawa-tengah' => 'Jawa Tengah',
            'jawa-timur' => 'Jawa Timur',
            'jawa-barat' => 'Jawa Barat',
        ];
    }

    /**
     * Nama ikon highlight (lihat komponen bersama.ikon-highlight) berdasarkan topik teks aslinya.
     * Dicocokkan dari awal kata pada teks sebelum diterjemahkan agar ikon sama di semua bahasa
     * ("Madakaripura" tidak dianggap "pura"); aturan yang lebih spesifik ditaruh lebih dulu.
     */
    public static function ikonHighlight(string $teks): string
    {
        $aturan = [
            'matahari' => ['sunrise', 'sunset', 'matahari', 'golden'],
            'air-panas' => ['air panas', 'pemandian'],
            'sarapan' => ['sarapan', 'kuliner', 'makan', 'kopi'],
            'hotel' => ['hotel', 'penginapan'],
            'perahu' => ['perahu', 'kapal'],
            'satwa' => ['rusa', 'satwa'],
            'belanja' => ['market', 'pasar', 'oleh', 'belanja'],
            'museum' => ['museum', 'alun-alun'],
            'desa' => ['desa', 'kampung'],
            'candi' => ['candi', 'pura', 'kuil', 'vihara'],
            'kawah' => ['kawah', 'api biru', 'belerang', 'uap'],
            'air-terjun' => ['air terjun', 'curug'],
            'air' => ['telaga', 'danau', 'situ', 'sungai', 'aliran', 'turquoise'],
            'pantai' => ['pantai'],
            'teh' => ['kebun teh', 'teh', 'perkebunan'],
            'bunga' => ['bunga', 'anggrek', 'edelweis'],
            'savana' => ['savana', 'padang', 'teletubbies'],
            'hutan' => ['hutan', 'pinus', 'mangrove', 'cagar'],
            'foto' => ['foto', 'spot', 'titik pandang'],
            'trekking' => ['trekking', 'pendakian', 'berjalan', 'jalur'],
            'pemandu' => ['sopir', 'pengemudi', 'pemandu', 'konsultasi', 'tim lokal'],
            'rute' => ['destinasi', 'rute', 'gabungan'],
            'udara' => ['udara', 'sejuk', 'kabut'],
            'gunung' => ['tebing', 'karang', 'gunung', 'kaldera', 'pasir', 'merapi', 'merbabu'],
        ];
        foreach ($aturan as $ikon => $kataKunci) {
            $pola = '/\b(?:'.implode('|', array_map(fn (string $kata) => preg_quote($kata, '/'), $kataKunci)).')/iu';

            if (preg_match($pola, $teks)) {
                return $ikon;
            }
        }

        return 'kilau';
    }

    public static function labelRegion(?string $slug): ?string
    {
        if (blank($slug)) {
            return null;
        }

        return static::daftarRegion()[$slug] ?? Str::headline($slug);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'harga' => 'integer',
            'durasi_hari' => 'integer',
            'maks_orang' => 'integer',
            'highlights' => 'array',
            'itinerary' => 'array',
            'praktis' => 'array',
            'reviews_count' => 'integer',
            'tampil_beranda' => 'boolean',
            'template_pesanan_kustom' => 'boolean',
            'urutan' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $perjalanan): void {
            if (blank($perjalanan->slug)) {
                $perjalanan->slug = static::slugUnik($perjalanan->nama);
            }
        });
    }

    protected static function slugUnik(string $nama): string
    {
        $dasar = Str::slug($nama);
        $slug = $dasar;
        $angka = 1;

        while (static::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$dasar}-".++$angka;
        }

        return $slug;
    }

    /**
     * @return HasMany<JadwalKeberangkatan, $this>
     */
    public function jadwal(): HasMany
    {
        return $this->hasMany(JadwalKeberangkatan::class);
    }

    /**
     * @return HasMany<Pesanan, $this>
     */
    public function pesanan(): HasMany
    {
        return $this->hasMany(Pesanan::class);
    }

    /**
     * @param  Builder<Perjalanan>  $query
     * @return Builder<Perjalanan>
     */
    public function scopeJourney(Builder $query): Builder
    {
        return $query->where('jenis', self::JENIS_JOURNEY);
    }

    /**
     * @param  Builder<Perjalanan>  $query
     * @return Builder<Perjalanan>
     */
    public function scopePaket(Builder $query): Builder
    {
        return $query->where('jenis', self::JENIS_PAKET);
    }

    public function adalahPaket(): bool
    {
        return $this->jenis === self::JENIS_PAKET;
    }

    /**
     * @param  Builder<Perjalanan>  $query
     * @return Builder<Perjalanan>
     */
    public function scopeTampilBeranda(Builder $query): Builder
    {
        return $query->where('tampil_beranda', true);
    }

    /** Apakah journey ini punya pesanan aktif (belum selesai/batal) sehingga tidak boleh dihapus. */
    public function punyaPesananAktif(): bool
    {
        return $this->pesanan()
            ->whereNotIn('status', [BookingStatus::Completed, BookingStatus::Dibatalkan])
            ->exists();
    }

    /** Jadwal keberangkatan berikutnya yang masih tersedia (dipakai kartu katalog admin & publik). */
    public function jadwalBerikutnya(): ?JadwalKeberangkatan
    {
        return $this->jadwal()->tersedia()->orderBy('tanggal')->first();
    }

    /**
     * Label durasi untuk tampilan. Dibangun sebagai frasa utuh (bukan placeholder)
     * karena file lang/*.json menerjemahkan per-kalimat, bukan per-variabel — lihat
     * "2 Hari · 1 Malam" dan "Durasi fleksibel" di lang/{locale}.json.
     */
    public function labelDurasi(): string
    {
        if (! $this->durasi_hari) {
            return __('Durasi fleksibel');
        }

        $frasa = "{$this->durasi_hari} Hari · ".max(0, $this->durasi_hari - 1).' Malam';

        return __($frasa);
    }

    /**
     * Label kapasitas untuk tampilan ("Maks. 6 orang"), frasa utuh (lihat catatan labelDurasi()).
     */
    public function labelKapasitas(): string
    {
        return __("Maks. {$this->maks_orang} orang");
    }

    /** Durasi untuk kartu paket tanpa titik tengah ("2 Hari 1 Malam"), sesuai kunci terjemahan kartu paket. */
    public function labelDurasiKartu(): string
    {
        return __($this->durasi_hari.' Hari '.max(0, $this->durasi_hari - 1).' Malam');
    }

    /** Kapasitas tamu untuk kartu paket, frasa utuh agar cocok dengan kunci terjemahan (mis. "Maks. 4 Tamu"). */
    public function labelKapasitasTamu(): string
    {
        return __("Maks. {$this->maks_orang} Tamu");
    }

    /** Hapus berkas sampul dari disk publik bila diunggah admin (gambar bawaan di /images tidak disentuh). */
    public function hapusBerkasSampul(): void
    {
        if ($this->img && str_contains($this->img, '/storage/')) {
            Storage::disk('public')->delete(Str::after($this->img, '/storage/'));
        }
    }

    public function sampul(): string
    {
        return $this->img ?: static::SAMPUL_CADANGAN;
    }

    public function hargaFormat(): ?string
    {
        return FormatRupiah::dari($this->harga);
    }

    /**
     * Hotel rekomendasi untuk journey ini (dikelola di menu Hotel).
     *
     * @return BelongsToMany<Hotel, $this>
     */
    public function hotel(): BelongsToMany
    {
        return $this->belongsToMany(Hotel::class, 'hotel_perjalanan');
    }

    /**
     * Mobil utama destinasi ini, dipilih admin sesuai medannya (jalan, tanjakan, rute). Tidak ditampilkan ke tamu: saat pesanan dibuat sistem memakainya bila kosong, kalau tidak unit lain yang tersedia.
     *
     * @return BelongsTo<Kendaraan, $this>
     */
    public function kendaraan(): BelongsTo
    {
        return $this->belongsTo(Kendaraan::class);
    }

    /**
     * Sistem all-inclusive: semua journey dan paket baku sudah termasuk tiket masuk, penginapan (4 orang per kamar),
     * transportasi, BBM, dan pemandu. Hanya journey kustom yang disusun lewat penawaran tersendiri.
     */
    public function semuaTermasuk(): bool
    {
        return ! $this->template_pesanan_kustom && ! $this->jadwalFleksibel();
    }

    /** Nama lama (paket All-in sebagai tambahan opsional) dipertahankan untuk kode yang masih memakainya. */
    public function menawarkanAllIn(): bool
    {
        return $this->semuaTermasuk();
    }

    /** Harga bawaan pesanan baru: harga all-inclusive per booking sudah memuat tiket, penginapan, dan layanan. */
    public function hargaDefault(?string $statusPenginapan, int $jumlahPeserta): ?int
    {
        return $this->harga;
    }

    /**
     * Satu hotel sorotan yang diurus MISTBORNE untuk journey ini (sistem all-inclusive), untuk tamu di halaman detail dan formulir:
     * hotel bertanda sorotan, atau hotel aktif tertua yang ditautkan bila belum ada yang ditandai.
     *
     * @return list<array{nama: string, url: string, bintang: int, area: string, alamat: ?string, deskripsi: ?string, jarak: ?string, foto: ?string, galeri: list<array{url: string, label: string}>, peta: string, fasilitas: list<string>, fasilitas_kunci: list<string>}>
     */
    public function hotelPublik(): array
    {
        if (! $this->semuaTermasuk()) {
            return [];
        }

        return $this->hotel()->aktif()->orderByDesc('hotel.sorotan')->orderBy('hotel.id')->limit(1)->get()
            ->map(fn (Hotel $hotel): array => [
                'nama' => $hotel->nama,
                'url' => route('hotel.show', ['hotel' => $hotel->slug, 'dari' => $this->slug]),
                'bintang' => (int) $hotel->bintang,
                'area' => $hotel->area,
                'alamat' => $hotel->alamat,
                'deskripsi' => $hotel->deskripsi,
                'jarak' => $hotel->estimasiPerjalanan(),
                'foto' => $hotel->fotoUrl(),
                'galeri' => $hotel->galeriPublik(),
                'peta' => $hotel->petaUrl(),
                'fasilitas' => array_map(fn (string $label): string => __($label), $hotel->labelFasilitas()),
                'fasilitas_kunci' => array_values(array_filter((array) $hotel->fasilitas, fn ($kunci): bool => isset(Hotel::FASILITAS[$kunci]))),
            ])->all();
    }

    /**
     * Data journey dalam bentuk array yang dipakai Blade situs publik (kartu journey, detail, JSON-LD).
     *
     * @return array<string, mixed>
     */
    public function kePublik(): array
    {
        return [
            'jadwal_fleksibel' => $this->jadwalFleksibel(),
            'jenis' => $this->jenis,
            'kategori' => $this->chip ? __($this->chip) : null,
            'label' => $this->label_unggulan ? __($this->label_unggulan) : null,
            'duration' => $this->labelDurasi(),
            'durasi_hari' => max(1, (int) $this->durasi_hari),
            'id' => $this->id,
            'slug' => $this->slug,
            'region' => $this->region,
            'region_label' => static::labelRegion($this->region),
            'name' => $this->nama,
            'tone' => $this->tone,
            'capacity' => $this->labelKapasitas(),
            'maks_orang' => (int) ($this->maks_orang ?? config('mistborne.tamu_maks_standar')),
            'place' => $this->tempat ? __($this->tempat) : null,
            'img' => $this->sampul(),
            'rating' => $this->rating_label,
            'reviews' => $this->reviews_count,
            'price' => $this->hargaFormat(),
            'harga_angka' => $this->harga,
            'all_in' => null,
            'semua_termasuk' => $this->semuaTermasuk(),
            'tagline' => $this->tagline ? __($this->tagline) : null,
            'desc' => $this->deskripsi ? __($this->deskripsi) : null,
            'highlights' => collect($this->highlights ?? [])->filter()->map(fn (string $baris) => __($baris))->values()->all(),
            'highlight_ikon' => collect($this->highlights ?? [])->filter()->map(fn (string $baris) => static::ikonHighlight($baris))->values()->all(),
            'itinerary' => collect($this->itinerary ?? [])->map(fn (array $baris) => static::barisItineraryPublik($baris))->values()->all(),
            'practical' => collect($this->praktis ?? [])->filter()->map(fn (string $nilai) => __($nilai))->all(),
            'specs' => [
                ['icon' => 'schedule', 'text' => $this->labelDurasi()],
                ['icon' => 'support_agent', 'text' => __('Sopir Privat')],
                ['icon' => 'hotel', 'text' => __('Jemput Hotel')],
            ],
            'home' => (bool) $this->tampil_beranda,
        ];
    }

    /**
     * Satu baris itinerary untuk tampilan publik. Penanda "Hari 1" menentukan kelompok hari
     * di halaman detail; jam tidak ditampilkan karena keberangkatan fleksibel mengikuti tamu.
     *
     * @param  array{t?: string, x?: string}  $baris
     * @return array{t: string, x: string, hari: int|null, jam: string|null}
     */
    protected static function barisItineraryPublik(array $baris): array
    {
        $penanda = (string) ($baris['t'] ?? '');
        $hari = null;
        $jam = null;
        $label = __($penanda);

        // "Hari 2" (tanpa jam) adalah format baku: jam perjalanan fleksibel mengikuti tamu. Format lama "Hari 2 · 04.30" tetap terbaca.
        if (preg_match('/^Hari\s+(\d+)(?:\s*·\s*(.+))?$/u', $penanda, $bagian) === 1) {
            $hari = (int) $bagian[1];
            $jam = isset($bagian[2]) ? (preg_match('/^\d{1,2}[.:]\d{2}$/', $bagian[2]) === 1 ? $bagian[2] : __($bagian[2])) : null;
            $label = __('Hari :nomor', ['nomor' => $hari]).($jam ? ' · '.$jam : '');
        }

        return ['t' => $label, 'x' => __((string) ($baris['x'] ?? '')), 'hari' => $hari, 'jam' => $jam];
    }

    /**
     * Journey kustom: jadwalnya mengikuti keinginan tamu, jadi tidak ada daftar tanggal tersedia
     * dan pemesanan dilakukan lewat form Rancang Trip Kustom.
     */
    public function jadwalFleksibel(): bool
    {
        return $this->tone === 'custom';
    }
}
