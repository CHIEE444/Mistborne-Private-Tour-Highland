<?php

namespace App\Models;

use App\Bantuan\PembersihHtml;
use App\Bantuan\UrlGambar;
use Database\Factories\ArtikelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable(['judul', 'slug', 'kategori', 'penulis', 'waktu_baca_menit', 'ringkasan', 'isi', 'img', 'published_at'])]
class Artikel extends Model
{
    /** @use HasFactory<ArtikelFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'artikel';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'waktu_baca_menit' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $artikel): void {
            if (blank($artikel->slug) && filled($artikel->judul)) {
                $artikel->slug = static::slugUnik($artikel->judul, $artikel->id);
            }

            if (filled($artikel->isi)) {
                $jumlahKata = str_word_count(strip_tags($artikel->isi));
                $artikel->waktu_baca_menit = max(1, (int) ceil($jumlahKata / 200));
            }
        });
    }

    protected static function slugUnik(string $judul, ?int $kecualiId = null): string
    {
        $dasar = Str::slug($judul);
        $slug = $dasar;
        $angka = 1;

        while (static::withTrashed()->where('slug', $slug)->when($kecualiId, fn ($q) => $q->where('id', '!=', $kecualiId))->exists()) {
            $slug = "{$dasar}-".++$angka;
        }

        return $slug;
    }

    /**
     * @param  Builder<Artikel>  $query
     * @return Builder<Artikel>
     */
    public function scopeTerbit(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    /** Status ditentukan dari published_at (bukan kolom tersimpan). */
    public function status(): string
    {
        return match (true) {
            $this->published_at === null => 'draft',
            $this->published_at->isFuture() => 'dijadwalkan',
            default => 'terbit',
        };
    }

    public function labelStatus(): string
    {
        return match ($this->status()) {
            'draft' => 'Draft',
            'dijadwalkan' => 'Dijadwalkan',
            'terbit' => 'Terbit',
        };
    }

    /**
     * Data artikel untuk halaman publik. Sampul bisa berupa path statis "/images/..." atau path unggahan disk "public".
     *
     * @return array<string, mixed>
     */
    public function kePublik(): array
    {
        return [
            'img' => $this->imgUrl(),
            'alt' => $this->judul,
            'category' => $this->kategori,
            'title' => $this->judul,
            'excerpt' => $this->ringkasan,
            'author' => $this->penulis,
            'date' => $this->published_at?->translatedFormat('d M Y'),
            // Frasa utuh (bukan placeholder) karena lang/*.json menerjemahkan per kalimat.
            'read' => __("{$this->waktu_baca_menit} menit baca"),
            'slug' => $this->slug,
            'url' => route('articles.show', $this->slug),
        ];
    }

    /** HTML badan artikel yang aman ditampilkan publik (lihat PembersihHtml). */
    public function isiAman(): string
    {
        return PembersihHtml::artikel((string) $this->isi);
    }

    /**
     * Kategori untuk saran di form admin: bawaan config + yang sudah dipakai artikel lain.
     *
     * @return list<string>
     */
    public static function saranKategori(): array
    {
        return collect(config('mistborne.kategori_artikel'))
            ->merge(static::query()->distinct()->pluck('kategori'))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function imgUrl(): ?string
    {
        return UrlGambar::dari($this->img);
    }
}
