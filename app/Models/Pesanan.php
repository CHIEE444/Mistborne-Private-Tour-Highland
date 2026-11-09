<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\StatusPenginapan;
use App\Enums\StatusReservasiHotel;
use App\Enums\SumberPesanan;
use App\Layanan\AlurReservasiHotel;
use Database\Factories\PesananFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use RuntimeException;

#[Fillable([
    'perjalanan_id', 'nama_perjalanan', 'tanggal_trip', 'tanggal_selesai', 'durasi_hari',
    'nama_lengkap', 'whatsapp', 'paket_opsional', 'nama_hotel', 'alamat_hotel', 'kendaraan_pilihan', 'status_penginapan', 'lokasi_pickup',
    'waktu_pickup', 'jumlah_wisatawan', 'permintaan_khusus', 'harga', 'nominal_dibayar',
    'metode_pembayaran', 'status', 'sumber',
    'dikonfirmasi_at', 'pembayaran_diterima_at', 'status_sebelum_hold', 'status_sebelum_batal', 'tanggal_lama',
    'charge_ganti_jadwal', 'pihak_pembatal', 'alasan_batal', 'nominal_refund',
    'nama_guide', 'guide_personel_id', 'kendaraan_id',
    'hotel_id', 'hotel_check_in', 'hotel_check_out', 'jumlah_kamar', 'tipe_kamar', 'kode_booking_hotel', 'status_reservasi_hotel', 'catatan_reservasi_hotel',
    'status_konfirmasi_guide', 'guide_dikonfirmasi_at', 'guide_trip_selesai_at',
    'email_penugasan_status', 'email_penugasan_alamat', 'email_penugasan_at', 'email_penugasan_galat',
    'permintaan_hotel_dikirim_at', 'bayar_hotel_nominal', 'bayar_hotel_tanggal', 'bayar_hotel_bukti', 'bukti_bayar_hotel_dikirim_at',
    'bayar_hotel_dikonfirmasi_at', 'tamu_dikabari_hotel_at', 'konfirmasi_ulang_hotel_at',
    'bukti_pembayaran', 'catatan_admin',
    'user_web_id', 'token_booking', 'pengingat_h7_terkirim', 'pengingat_h3_terkirim', 'pengingat_h2_terkirim',
])]
class Pesanan extends Model
{
    /** @use HasFactory<PesananFactory> */
    use HasFactory;

    protected $table = 'pesanan';

    /**
     * Tahap alur pesanan yang dilihat admin, berurutan. Dihitung dari status tersimpan, pembayaran, tanggal trip,
     * dan syarat trip (lihat tahap()), jadi tidak perlu kolom baru dan selalu tepat waktu walau penjadwal tidak menyala.
     */
    public const TAHAP = [
        'dihubungi' => 'Dihubungi',
        'menunggu_pembayaran' => 'Menunggu Konfirmasi Pembayaran',
        'sudah_bayar' => 'Sudah Bayar',
        'lunas' => 'Lunas (Menunggu Trip)',
        'berlangsung' => 'Trip Berlangsung',
        'selesai' => 'Trip Selesai',
        'jadwal_ditahan' => 'Jadwal Ditahan',
        'dibatalkan' => 'Dibatalkan',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_trip' => 'date',
            'tanggal_selesai' => 'date',
            'dikonfirmasi_at' => 'datetime',
            'pembayaran_diterima_at' => 'datetime',
            'guide_dikonfirmasi_at' => 'datetime',
            'email_penugasan_at' => 'datetime',
            'guide_trip_selesai_at' => 'datetime',
            'permintaan_hotel_dikirim_at' => 'datetime',
            'bukti_bayar_hotel_dikirim_at' => 'datetime',
            'bayar_hotel_dikonfirmasi_at' => 'datetime',
            'tamu_dikabari_hotel_at' => 'datetime',
            'konfirmasi_ulang_hotel_at' => 'datetime',
            'bayar_hotel_tanggal' => 'date',
            'bayar_hotel_nominal' => 'integer',
            'tanggal_lama' => 'date',
            'harga' => 'integer',
            'nominal_dibayar' => 'integer',
            'jumlah_wisatawan' => 'integer',
            'charge_ganti_jadwal' => 'integer',
            'durasi_hari' => 'integer',
            'nominal_refund' => 'integer',
            'status' => BookingStatus::class,
            'sumber' => SumberPesanan::class,
            'status_penginapan' => StatusPenginapan::class,
            'status_reservasi_hotel' => StatusReservasiHotel::class,
            'hotel_check_in' => 'date',
            'hotel_check_out' => 'date',
            'pengingat_h7_terkirim' => 'boolean',
            'pengingat_h3_terkirim' => 'boolean',
            'pengingat_h2_terkirim' => 'boolean',
        ];
    }

    public function guide(): BelongsTo
    {
        return $this->belongsTo(PersonelOperasional::class, 'guide_personel_id');
    }

    /**
     * Hotel yang dipesankan MISTBORNE; tetap terbaca walau hotelnya sudah dihapus (soft delete).
     *
     * @return BelongsTo<Hotel, $this>
     */
    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class)->withTrashed();
    }

    /**
     * Kendaraan yang bertugas; tetap terbaca walau kendaraannya sudah dihapus (soft delete).
     *
     * @return BelongsTo<Kendaraan, $this>
     */
    public function kendaraan(): BelongsTo
    {
        return $this->belongsTo(Kendaraan::class)->withTrashed();
    }

    protected static function booted(): void
    {
        static::creating(function (self $pesanan): void {
            if (blank($pesanan->kode)) {
                $pesanan->kode = static::kodeUnik();
            }

            if (blank($pesanan->token_rating)) {
                $pesanan->token_rating = static::tokenUnik();
            }

            // Durasi pesanan mengikuti journey (2 hari 1 malam, dst.) supaya rentang tanggal tidak berbeda dari yang tertulis di journey.
            $pesanan->durasi_hari ??= $pesanan->perjalanan?->durasi_hari;

            if (blank($pesanan->tanggal_selesai) && $pesanan->tanggal_trip) {
                $durasiHari = $pesanan->durasi_hari;
                $pesanan->tanggal_selesai = $durasiHari
                    ? $pesanan->tanggal_trip->copy()->addDays($durasiHari - 1)
                    : $pesanan->tanggal_trip->copy();
            }
        });
    }

    protected static function kodeUnik(): string
    {
        for ($percobaan = 0; $percobaan < 5; $percobaan++) {
            $kode = 'MST-'.now()->format('ymd').'-'.Str::upper(Str::random(4));

            if (! static::where('kode', $kode)->exists()) {
                return $kode;
            }
        }

        throw new RuntimeException('Gagal membuat kode pesanan unik setelah 5 percobaan.');
    }

    protected static function tokenUnik(): string
    {
        do {
            $token = Str::random(40);
        } while (static::where('token_rating', $token)->exists());

        return $token;
    }

    /**
     * @return BelongsTo<Perjalanan, $this>
     */
    public function perjalanan(): BelongsTo
    {
        return $this->belongsTo(Perjalanan::class);
    }

    /**
     * @return HasMany<PenggantianJadwal, $this>
     */
    public function penggantian(): HasMany
    {
        return $this->hasMany(PenggantianJadwal::class)->latest();
    }

    /**
     * @return HasOne<Ulasan, $this>
     */
    public function ulasan(): HasOne
    {
        return $this->hasOne(Ulasan::class);
    }

    /**
     * Tautan rating aktif sejak tanggal_trip sampai tanggal_selesai + batas_ulasan_hari (D5).
     */
    public function tautanRatingAktif(): bool
    {
        $batasAkhir = ($this->tanggal_selesai ?? $this->tanggal_trip)->copy()->addDays(Pengaturan::current()->batas_ulasan_hari)->endOfDay();

        return $this->status === BookingStatus::Completed
            && now()->lessThanOrEqualTo($batasAkhir)
            && ! $this->ulasan()->exists();
    }

    /**
     * @param  Builder<Pesanan>  $query
     * @return Builder<Pesanan>
     */
    public function scopeTerhitungKuota(Builder $query): Builder
    {
        $statusAktif = collect(BookingStatus::cases())
            ->filter(fn (BookingStatus $status): bool => $status->menghitungKuota())
            ->map(fn (BookingStatus $status): string => $status->value)
            ->all();

        return $query->whereIn('status', $statusAktif);
    }

    /**
     * Pesanan yang pasti berangkat (Pembayaran + Selesai) — untuk daftar tamu admin
     * (panel hari Kalender Trip + rekap PDF bulanan). Beda dari terhitungKuota():
     * Dihubungi/Jadwal Ditahan belum dikonfirmasi bayar, jadi tidak masuk daftar tamu.
     *
     * @param  Builder<Pesanan>  $query
     * @return Builder<Pesanan>
     */
    public function scopePastiBerangkat(Builder $query): Builder
    {
        return $query->whereIn('status', [BookingStatus::Payment->value, BookingStatus::Completed->value]);
    }

    /** Durasi perjalanan sebagai teks ("2 hari 1 malam"); jumlah hari mengikuti rentang tanggal bila durasi belum tercatat. */
    public function labelDurasi(): string
    {
        $hari = (int) ($this->durasi_hari ?: $this->tanggal_trip->diffInDays($this->tanggal_selesai ?? $this->tanggal_trip) + 1);

        return $hari > 1 ? $hari.' hari '.($hari - 1).' malam' : '1 hari';
    }

    /** Rentang tanggal untuk tabel admin: "28 Okt – 29 Okt 2026", atau satu tanggal bila trip sehari. */
    public function rentangTanggalTeks(): string
    {
        $akhir = $this->tanggal_selesai ?? $this->tanggal_trip;

        if ($akhir->isSameDay($this->tanggal_trip)) {
            return $this->tanggal_trip->translatedFormat('d M Y');
        }

        return $this->tanggal_trip->translatedFormat($akhir->isSameYear($this->tanggal_trip) ? 'd M' : 'd M Y').' – '.$akhir->translatedFormat('d M Y');
    }

    /** Sisa hari ke tanggal trip (negatif bila sudah lewat). "H-7" = sisaHari() === 7. */
    public function sisaHari(): int
    {
        return (int) now()->startOfDay()->diffInDays($this->tanggal_trip->copy()->startOfDay(), false);
    }

    /**
     * Status penugasan guide untuk panel Operasional (dipakai saat halaman dimuat dan saat diperbarui otomatis):
     * konfirmasi dari guide dan hasil email penugasan terakhir.
     *
     * @return array{guide: ?string, konfirmasi: ?string, konfirmasi_waktu: ?string, email: array{status: ?string, alamat: ?string, waktu: ?string, galat: ?string}}
     */
    public function ringkasanPenugasanGuide(): array
    {
        $waktu = fn ($nilai): ?string => $nilai?->timezone(config('app.timezone'))->translatedFormat('d M Y, H:i:s').' WIB';

        return [
            'guide' => $this->guide_personel_id ? $this->nama_guide : null,
            'konfirmasi' => $this->guide_personel_id ? ($this->status_konfirmasi_guide ?? 'menunggu') : null,
            'konfirmasi_waktu' => $this->guide_dikonfirmasi_at ? $waktu($this->guide_dikonfirmasi_at) : null,
            'email' => [
                'status' => $this->email_penugasan_status,
                'alamat' => $this->email_penugasan_alamat,
                'waktu' => $this->email_penugasan_at ? $waktu($this->email_penugasan_at) : null,
                'galat' => $this->email_penugasan_galat,
            ],
        ];
    }

    /** Label singkat jarak ke keberangkatan; trip beberapa hari yang sudah dimulai tertulis "sedang berlangsung", bukan "sisa -1 hari". */
    public function labelJarakTrip(): string
    {
        $sisa = $this->sisaHari();

        return match (true) {
            $sisa > 1 => "sisa {$sisa} hari",
            $sisa === 1 => 'berangkat besok',
            $sisa === 0 => 'berangkat hari ini',
            default => 'sedang berlangsung',
        };
    }

    /** Total tagihan menggabungkan harga saat dipesan dan semua biaya ganti jadwal. */
    public function totalTagihan(): ?int
    {
        if ($this->harga === null) {
            return null;
        }

        return $this->harga + $this->totalBiayaGantiJadwal();
    }

    public function totalBiayaGantiJadwal(): int
    {
        return $this->relationLoaded('penggantian')
            ? (int) $this->penggantian->sum('charge')
            : (int) $this->penggantian()->sum('charge');
    }

    /** Sisa tagihan setelah semua biaya ganti jadwal dikurangi pembayaran yang tercatat. */
    public function sisaTagihan(): ?int
    {
        $totalTagihan = $this->totalTagihan();

        return $totalTagihan === null ? null : max(0, $totalTagihan - ($this->nominal_dibayar ?? 0));
    }

    /** Jumlah guide (merangkap driver) yang saat ini ditugaskan ke pesanan ini: 0 atau 1. */
    public function jumlahPersonelDitugaskan(): int
    {
        return (int) ($this->guide_personel_id !== null);
    }

    /** Jumlah personel yang ditugaskan dan sudah mencatat waktu selesai trip. */
    public function jumlahPersonelSelesai(): int
    {
        return (int) ($this->guide_personel_id !== null && $this->guide_trip_selesai_at !== null);
    }

    /** Status trip terpisah dari status pembayaran pesanan. */
    public function statusTripOperasional(): string
    {
        if ($this->jumlahPersonelDitugaskan() === 0) {
            return 'belum_ditugaskan';
        }

        return $this->jumlahPersonelSelesai() === 1 ? 'tuntas' : 'menunggu';
    }

    /** Guide yang ditugaskan menolak, sehingga penugasannya harus diganti admin. */
    public function penugasanDitolak(): bool
    {
        return $this->peranPenolak() !== [];
    }

    /** @return list<string> ['Guide'] bila guide menolak tugas, selain itu kosong */
    public function peranPenolak(): array
    {
        return $this->guide_personel_id !== null && $this->status_konfirmasi_guide === 'ditolak' ? ['Guide'] : [];
    }

    /** Pesanan Pembayaran yang trip-nya dalam 7 hari ke depan tetapi Guide belum ditugaskan. */
    public function penugasanMendesak(): bool
    {
        return $this->status === BookingStatus::Payment
            && $this->jumlahPersonelDitugaskan() < 1
            && $this->sisaHari() >= 0
            && $this->sisaHari() <= 7;
    }

    public function tripOperasionalTuntas(): bool
    {
        return $this->statusTripOperasional() === 'tuntas';
    }

    /** Selesai/Dibatalkan = status akhir, tidak ada aksi mutasi apa pun lagi. */
    /** Pesanan yang sudah menerima uang, berstatus Pembayaran, atau Selesai adalah catatan keuangan: tetap boleh dihapus, tetapi dialog hapus memberi peringatan keras. */
    public function punyaCatatanKeuangan(): bool
    {
        return (int) ($this->nominal_dibayar ?? 0) > 0
            || in_array($this->status, [BookingStatus::Payment, BookingStatus::Completed], true);
    }

    /**
     * Tahap alur: Dihubungi → Menunggu Konfirmasi Pembayaran → Sudah Bayar → Lunas (Menunggu Trip) → Trip Berlangsung
     * → Trip Selesai, ditambah Jadwal Ditahan dan Dibatalkan. Trip hanya berjalan bila semua syaratnya terpenuhi.
     */
    public function tahap(): string
    {
        return match ($this->status) {
            BookingStatus::Dibatalkan => 'dibatalkan',
            BookingStatus::HoldSchedule => 'jadwal_ditahan',
            BookingStatus::Completed => 'selesai',
            BookingStatus::Dihubungi => $this->dikonfirmasi_at ? 'menunggu_pembayaran' : 'dihubungi',
            BookingStatus::Payment => match (true) {
                $this->tripSudahDimulai() && $this->syaratTripBelumTerpenuhi() === [] => $this->tripSudahBerakhir() || $this->guide_trip_selesai_at !== null ? 'selesai' : 'berlangsung',
                $this->sisaTagihan() === 0 => 'lunas',
                default => 'sudah_bayar',
            },
        };
    }

    /** Label tahap untuk lencana: pembatal disebut, tagihan yang belum ditentukan ditandai. */
    public function labelTahap(): string
    {
        $tahap = $this->tahap();

        return match (true) {
            $tahap === 'dibatalkan' => match ($this->pihak_pembatal) {
                'mistborne' => 'Dibatalkan oleh MISTBORNE',
                'pelanggan' => 'Dibatalkan oleh Tamu',
                default => 'Dibatalkan',
            },
            $tahap === 'sudah_bayar' && $this->sisaTagihan() === null => 'Sudah Bayar (Cek Tagihan)',
            default => self::TAHAP[$tahap],
        };
    }

    /**
     * Semua syarat yang wajib beres sebelum trip boleh dilaksanakan. Kosong = trip siap.
     *
     * @return list<string>
     */
    public function syaratTripBelumTerpenuhi(): array
    {
        return array_keys(array_filter([
            'Pembayaran belum lunas' => $this->sisaTagihan() !== 0,
            'Guide belum menerima tugas' => $this->guide_personel_id === null || $this->status_konfirmasi_guide !== 'diterima',
            'Kendaraan belum ditetapkan' => $this->kendaraan_id === null,
            'Reservasi hotel belum tuntas' => app(AlurReservasiHotel::class)->menghambatCompleted($this),
        ]));
    }

    public function tripSudahDimulai(): bool
    {
        return $this->tanggal_trip !== null && $this->tanggal_trip->copy()->startOfDay()->lte(today());
    }

    public function tripSudahBerakhir(): bool
    {
        return $this->tanggal_trip !== null && ($this->tanggal_selesai ?? $this->tanggal_trip)->copy()->startOfDay()->lt(today());
    }

    /** Hari keberangkatan sudah tiba (atau lewat) padahal ada syarat yang belum beres: trip tidak bisa dilaksanakan. */
    public function tripTidakBisaDilaksanakan(): bool
    {
        return $this->status === BookingStatus::Payment && $this->tripSudahDimulai() && $this->syaratTripBelumTerpenuhi() !== [];
    }

    /**
     * Filter daftar pesanan per tahap. Tahap yang bergantung pada pembayaran, tanggal, dan syarat trip
     * (Sudah Bayar, Lunas, Trip Berlangsung) dihitung lewat tahap() pada pesanan berstatus Pembayaran.
     *
     * @param  Builder<Pesanan>  $query
     * @return Builder<Pesanan>
     */
    public function scopeTahap(Builder $query, string $tahap): Builder
    {
        return match ($tahap) {
            'dihubungi' => $query->where('status', BookingStatus::Dihubungi)->whereNull('dikonfirmasi_at'),
            'menunggu_pembayaran' => $query->where('status', BookingStatus::Dihubungi)->whereNotNull('dikonfirmasi_at'),
            'jadwal_ditahan' => $query->where('status', BookingStatus::HoldSchedule),
            'dibatalkan' => $query->where('status', BookingStatus::Dibatalkan),
            'selesai' => $query->where(fn (Builder $q) => $q->where('status', BookingStatus::Completed)->orWhereKey(static::idTahapPembayaran('selesai'))),
            default => $query->whereKey(static::idTahapPembayaran($tahap)),
        };
    }

    /**
     * Jumlah pesanan per tahap untuk lencana tab.
     *
     * @return array<string, int>
     */
    public static function jumlahPerTahap(): array
    {
        $perStatus = static::query()->selectRaw('status, (dikonfirmasi_at IS NOT NULL) as dikonfirmasi, count(*) as jumlah')
            ->groupBy('status', 'dikonfirmasi')->get();
        $hitung = fn (BookingStatus $status, ?bool $dikonfirmasi = null): int => (int) $perStatus
            ->filter(fn ($baris): bool => $baris->status === $status && ($dikonfirmasi === null || (bool) $baris->dikonfirmasi === $dikonfirmasi))
            ->sum('jumlah');
        $pembayaran = static::query()->with('penggantian')->where('status', BookingStatus::Payment)->get()->countBy(fn (Pesanan $pesanan): string => $pesanan->tahap());

        return [
            'dihubungi' => $hitung(BookingStatus::Dihubungi, false),
            'menunggu_pembayaran' => $hitung(BookingStatus::Dihubungi, true),
            'sudah_bayar' => $pembayaran->get('sudah_bayar', 0),
            'lunas' => $pembayaran->get('lunas', 0),
            'berlangsung' => $pembayaran->get('berlangsung', 0),
            'selesai' => $hitung(BookingStatus::Completed) + $pembayaran->get('selesai', 0),
            'jadwal_ditahan' => $hitung(BookingStatus::HoldSchedule),
            'dibatalkan' => $hitung(BookingStatus::Dibatalkan),
        ];
    }

    /** @return list<int> id pesanan berstatus Pembayaran yang tahapnya sama dengan $tahap */
    protected static function idTahapPembayaran(string $tahap): array
    {
        return static::query()->with('penggantian')->where('status', BookingStatus::Payment)->get()
            ->filter(fn (Pesanan $pesanan): bool => $pesanan->tahap() === $tahap)
            ->modelKeys();
    }

    public function terkunci(): bool
    {
        return in_array($this->status, [BookingStatus::Completed, BookingStatus::Dibatalkan], true);
    }

    /**
     * Filter daftar pesanan aktif menurut aturan batal / ganti jadwal yang sedang berlaku.
     *
     * @param  Builder<Pesanan>  $query
     * @return Builder<Pesanan>
     */
    public function scopeAturanBerlaku(Builder $query, string $aturan): Builder
    {
        $aktif = [BookingStatus::Dihubungi->value, BookingStatus::Payment->value, BookingStatus::HoldSchedule->value];
        $hariIni = today();

        return match ($aturan) {
            'batal_gratis' => $query->whereIn('status', $aktif)
                ->whereDate('tanggal_trip', '>=', $hariIni->copy()->addDays(7)->toDateString()),
            'batal_separuh' => $query->whereIn('status', $aktif)
                ->whereDate('tanggal_trip', '>=', $hariIni->copy()->addDays(2)->toDateString())
                ->whereDate('tanggal_trip', '<', $hariIni->copy()->addDays(7)->toDateString()),
            'ganti_jadwal' => $query->where(function (Builder $q) use ($hariIni): void {
                $q->whereIn('status', [BookingStatus::Dihubungi->value, BookingStatus::HoldSchedule->value])
                    ->orWhere(fn (Builder $q) => $q->where('status', BookingStatus::Payment->value)
                        ->whereDate('tanggal_trip', '>', $hariIni->copy()->addDays(7)->toDateString()));
            }),
            default => $query->where('status', BookingStatus::Payment->value)
                ->whereDate('tanggal_trip', '>=', $hariIni->toDateString())
                ->whereDate('tanggal_trip', '<=', $hariIni->copy()->addDays(7)->toDateString()),
        };
    }

    /**
     * Pesanan Pembayaran yang berangkat 1–7 hari lagi, yaitu rentang pengingat H-7, H-3, dan H-2.
     * Tahap tiap pesanan ditentukan tahapPengingatBerlaku() — dipakai dashboard & command BuatPengingatHariH.
     *
     * @param  Builder<Pesanan>  $query
     * @return Builder<Pesanan>
     */
    public function scopePengingatBerjalan(Builder $query): Builder
    {
        return $query->where('status', BookingStatus::Payment)
            ->whereDate('tanggal_trip', '>=', now()->addDay()->toDateString())
            ->whereDate('tanggal_trip', '<=', now()->addDays(7)->toDateString());
    }

    /**
     * Tahap pengingat ke tamu yang berlaku hari ini menurut sisa hari ke trip: H-7 (sisa 4–7 hari), H-3 (sisa 3 hari),
     * H-2 (sisa 1–2 hari). Satu aturan untuk dashboard, popup pengingat, dan detail pesanan supaya ketiganya selalu sama.
     * Tahap yang lewat tanpa dikirim tidak ditawarkan lagi karena isi pesannya ("tinggal 7 hari") sudah tidak benar.
     */
    public function tahapPengingatBerlaku(): ?string
    {
        if ($this->status !== BookingStatus::Payment || $this->tanggal_trip === null) {
            return null;
        }

        $sisaHari = (int) today()->diffInDays($this->tanggal_trip->copy()->startOfDay(), false);

        return match (true) {
            $sisaHari >= 4 && $sisaHari <= 7 => 'h7',
            $sisaHari === 3 => 'h3',
            $sisaHari >= 1 && $sisaHari <= 2 => 'h2',
            default => null,
        };
    }

    /** Pengingat tahap ini (h7/h3/h2) sudah ditandai terkirim ke tamu. */
    public function pengingatSudahTerkirim(string $tahap): bool
    {
        return (bool) $this->getAttribute("pengingat_{$tahap}_terkirim");
    }

    /**
     * Pesanan yang pengingat tahap berlakunya belum dikirim (isi popup dashboard, titik lonceng, dan log harian).
     *
     * @return Collection<int, Pesanan>
     */
    public static function pengingatBelumTerkirim(): Collection
    {
        return static::query()->pengingatBerjalan()->orderBy('tanggal_trip')->get()
            ->reject(fn (Pesanan $pesanan): bool => $pesanan->pengingatSudahTerkirim((string) $pesanan->tahapPengingatBerlaku()))
            ->values();
    }

    /**
     * Pesanan aktif (belum selesai/dibatalkan) yang berangkat besok, untuk cek akhir H-1 admin.
     *
     * @param  Builder<Pesanan>  $query
     * @return Builder<Pesanan>
     */
    public function scopePeringatanH1(Builder $query): Builder
    {
        return $query->whereIn('status', [BookingStatus::Dihubungi, BookingStatus::Payment, BookingStatus::HoldSchedule])
            ->whereDate('tanggal_trip', now()->addDay()->toDateString());
    }

    /**
     * Hal yang belum beres untuk berangkat: syarat trip ditambah info keberangkatan (pengingat H-2) ke tamu.
     *
     * @return list<string>
     */
    public function belumSiapBerangkat(): array
    {
        return [
            ...$this->syaratTripBelumTerpenuhi(),
            ...($this->pengingat_h2_terkirim ? [] : ['Info keberangkatan (H-2) belum dikirim ke tamu']),
        ];
    }

    /**
     * Pesanan aktif yang trip-nya belum lewat dan Guide-nya menolak tugas.
     *
     * @param  Builder<Pesanan>  $query
     * @return Builder<Pesanan>
     */
    public function scopeAdaPenolakanPersonel(Builder $query): Builder
    {
        return $query->tugasAktifBelumLewat()->where(function (Builder $query): void {
            $query->whereNotNull('guide_personel_id')->where('status_konfirmasi_guide', 'ditolak');
        });
    }

    /**
     * @param  Builder<Pesanan>  $query
     * @return Builder<Pesanan>
     */
    public function scopeMenungguKonfirmasiPersonel(Builder $query): Builder
    {
        return $query->tugasAktifBelumLewat()->where(function (Builder $query): void {
            $query->where('status_konfirmasi_guide', 'menunggu');
        });
    }

    /**
     * Pesanan aktif yang belum berangkat dan tamunya butuh rekomendasi penginapan (belum ada nama penginapan).
     *
     * @param  Builder<Pesanan>  $query
     * @return Builder<Pesanan>
     */
    public function scopeButuhRekomendasiPenginapan(Builder $query): Builder
    {
        return $query
            ->whereIn('status', [BookingStatus::Dihubungi->value, BookingStatus::Payment->value, BookingStatus::HoldSchedule->value])
            ->whereDate('tanggal_trip', '>=', today()->toDateString())
            ->where('status_penginapan', StatusPenginapan::Belum->value)
            ->where(fn (Builder $sub) => $sub->whereNull('nama_hotel')->orWhere('nama_hotel', ''));
    }

    /**
     * Pesanan yang hotelnya dipesankan MISTBORNE (dipesankan atau All-in), belum berangkat, dan reservasinya
     * belum terkonfirmasi hotel (belum ada reservasi, masih menunggu, atau dibatalkan).
     *
     * @param  Builder<Pesanan>  $query
     * @return Builder<Pesanan>
     */
    public function scopeHotelBelumTerkonfirmasi(Builder $query): Builder
    {
        return $query
            ->whereIn('status', [BookingStatus::Dihubungi->value, BookingStatus::Payment->value, BookingStatus::HoldSchedule->value])
            ->whereDate('tanggal_trip', '>=', today()->toDateString())
            ->whereIn('status_penginapan', [StatusPenginapan::Dipesankan->value, StatusPenginapan::Diurus->value])
            ->where(fn (Builder $sub) => $sub->whereNull('status_reservasi_hotel')->orWhere('status_reservasi_hotel', '<>', StatusReservasiHotel::Terkonfirmasi->value));
    }

    /**
     * @param  Builder<Pesanan>  $query
     * @return Builder<Pesanan>
     */
    public function scopePenugasanBelumLengkap(Builder $query): Builder
    {
        return $query->tugasAktifBelumLewat()->where('status', BookingStatus::Payment)->whereNull('guide_personel_id');
    }

    /**
     * Pesanan yang masih menahan kendaraan: status aktif dan trip belum berakhir. Trip multi-hari yang sedang
     * berjalan ikut dihitung (tanggal selesai belum lewat), bukan hanya yang mulainya masih di depan.
     *
     * @param  Builder<Pesanan>  $query
     * @return Builder<Pesanan>
     */
    public function scopeMenahanKendaraan(Builder $query): Builder
    {
        return $query
            ->whereIn('status', [BookingStatus::Dihubungi->value, BookingStatus::Payment->value, BookingStatus::HoldSchedule->value])
            ->whereRaw('COALESCE(tanggal_selesai, tanggal_trip) >= ?', [today()->toDateString()]);
    }

    /**
     * @param  Builder<Pesanan>  $query
     * @return Builder<Pesanan>
     */
    public function scopeTugasAktifBelumLewat(Builder $query): Builder
    {
        return $query
            ->whereIn('status', [BookingStatus::Dihubungi->value, BookingStatus::Payment->value, BookingStatus::HoldSchedule->value])
            ->whereDate('tanggal_trip', '>=', today()->toDateString());
    }
}
