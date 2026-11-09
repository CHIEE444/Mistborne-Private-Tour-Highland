<?php

namespace App\Models;

use App\Bantuan\FormatRupiah;
use App\Enums\StatusPenginapan;
use App\Enums\StatusPesananKustom;
use Database\Factories\PesananKustomFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'nama', 'whatsapp', 'destinasi_diinginkan', 'jadwal_diinginkan', 'tanggal_diinginkan',
    'tanggal_fleksibel', 'durasi_hari', 'lokasi_penjemputan', 'status_penginapan', 'nama_hotel', 'jumlah_orang', 'catatan',
    'minat_perjalanan', 'tempo_perjalanan', 'rencana_perjalanan', 'rincian_penawaran', 'batas_penawaran', 'catatan_admin',
    'harga_final', 'nama_pendamping', 'status', 'pesanan_id',
    'penawaran_dikirim_pada', 'alasan_tidak_jadi',
])]
class PesananKustom extends Model
{
    public const BATAS_HARGA_PER_ORANG = 1_000_000_000_000_000;

    /** @use HasFactory<PesananKustomFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'pesanan_kustom';

    protected $attributes = [
        'status' => 'baru',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jumlah_orang' => 'integer',
            'status_penginapan' => StatusPenginapan::class,
            'minat_perjalanan' => 'array',
            'tanggal_diinginkan' => 'date',
            'tanggal_fleksibel' => 'boolean',
            'durasi_hari' => 'integer',
            'batas_penawaran' => 'date',
            'penawaran_dikirim_pada' => 'datetime',
            'harga_final' => 'integer',
            'status' => StatusPesananKustom::class,
        ];
    }

    /** @return BelongsTo<Pesanan, $this> */
    public function pesanan(): BelongsTo
    {
        return $this->belongsTo(Pesanan::class);
    }

    /** @return HasMany<RiwayatPenawaranPesananKustom, $this> */
    public function riwayatPenawaran(): HasMany
    {
        return $this->hasMany(RiwayatPenawaranPesananKustom::class)->orderByDesc('versi');
    }

    /** Penawaran sudah dikirim tetapi masa berlakunya lewat tanpa jawaban tamu. */
    public function penawaranKedaluwarsa(): bool
    {
        return $this->status === StatusPesananKustom::MenungguJawaban
            && $this->batas_penawaran !== null
            && $this->batas_penawaran->isBefore(today());
    }

    public function jadwalDiinginkanFormat(): string
    {
        if ($this->tanggal_fleksibel) {
            return 'Fleksibel'.($this->jadwal_diinginkan && $this->jadwal_diinginkan !== 'Tanggal fleksibel' ? ' · '.$this->jadwal_diinginkan : '');
        }

        if ($this->tanggal_diinginkan === null) {
            return $this->jadwal_diinginkan ?: 'Belum ditentukan';
        }

        return $this->tanggal_diinginkan->translatedFormat('d M Y');
    }

    /**
     * Kode tampilan turunan CST-YYMMDD-NN (bukan kolom tersimpan).
     * NN = urutan ke-berapa pesanan kustom ini dibuat pada tanggal yang sama.
     */
    public function kodeTampilan(): string
    {
        $urutan = static::query()
            ->withTrashed()
            ->whereDate('created_at', $this->created_at->toDateString())
            ->where('id', '<=', $this->id)
            ->count();

        return 'CST-'.$this->created_at->format('ymd').'-'.str_pad((string) $urutan, 2, '0', STR_PAD_LEFT);
    }

    /** Total tagihan grup: harga_final per orang (D3) dikali jumlah_orang. */
    public function hargaTotal(): ?int
    {
        return $this->harga_final !== null ? $this->harga_final * $this->jumlah_orang : null;
    }

    public function hargaTotalFormat(): ?string
    {
        return FormatRupiah::dari($this->hargaTotal());
    }

    /**
     * Data permintaan yang wajib terisi sebelum admin menghubungi tamu (popup validasi "Hubungi via WhatsApp").
     *
     * @return list<string> label kolom yang masih kosong
     */
    public function dataBelumLengkap(): array
    {
        return array_keys(array_filter([
            'Nama tamu' => blank($this->nama),
            'Nomor WhatsApp' => blank($this->whatsapp),
            'Destinasi' => blank($this->destinasi_diinginkan),
            'Tanggal perjalanan (atau centang tanggal fleksibel)' => ! $this->tanggal_fleksibel && $this->tanggal_diinginkan === null,
            'Durasi (hari)' => ! $this->durasi_hari,
            'Jumlah orang' => ! $this->jumlah_orang,
            'Area penjemputan' => blank($this->lokasi_penjemputan),
            'Penginapan tamu' => $this->status_penginapan === null,
        ]));
    }

    /** Penawaran siap dikirim: rencana perjalanan dan harga per orang sudah diisi. */
    public function penawaranLengkap(): bool
    {
        return filled($this->rencana_perjalanan) && $this->harga_final !== null && $this->harga_final > 0;
    }

    /**
     * Tahap alur admin "Hubungi – Susun – Kirim – Resmikan": 1 Hubungi tamu, 2 Susun penawaran, 3 Kirim penawaran,
     * 4 Resmikan (menunggu jawaban), 5 selesai karena sudah menjadi pesanan resmi.
     */
    public function tahapAlur(): int
    {
        return match (true) {
            $this->pesanan_id !== null => 5,
            $this->status === StatusPesananKustom::Baru => 1,
            $this->status === StatusPesananKustom::MenungguJawaban => 4,
            $this->penawaranLengkap() => 3,
            default => 2,
        };
    }
}
