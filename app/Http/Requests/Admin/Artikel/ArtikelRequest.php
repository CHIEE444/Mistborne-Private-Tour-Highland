<?php

namespace App\Http\Requests\Admin\Artikel;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/**
 * Aturan & pesan validasi bersama form Artikel (tulis & ubah). Semua isian wajib diisi dan
 * harus sesuai format; satu-satunya pengecualian: sampul saat ubah (sampul lama dipertahankan)
 * dan jadwal terbit saat disimpan sebagai draft.
 */
abstract class ArtikelRequest extends FormRequest
{
    /** Format nilai input datetime-local. */
    public const FORMAT_JADWAL = 'Y-m-d\TH:i';

    /** Toleransi menit agar "terbitkan sekarang" tidak ditolak karena selisih waktu mengisi form. */
    private const TOLERANSI_MENIT = 5;

    /**
     * @return array<int, mixed>
     */
    abstract protected function aturanSampul(): array;

    /** Judul unik di antara artikel aktif; saat ubah, artikel itu sendiri dikecualikan. */
    abstract protected function aturanJudulUnik(): Unique;

    /** Pada ubah artikel: jadwal yang tidak diubah boleh sudah lewat. */
    protected function jadwalLama(): ?string
    {
        return null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'judul' => preg_replace('/\s+/u', ' ', trim((string) $this->input('judul'))),
            'kategori' => preg_replace('/\s+/u', ' ', trim((string) $this->input('kategori'))),
            'ringkasan' => trim((string) $this->input('ringkasan')),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $terbitkan = $this->input('aksi') === 'terbitkan';

        return [
            'judul' => ['required', 'string', 'min:5', 'max:150', $this->aturanJudulUnik()],
            'kategori' => ['required', 'string', 'min:3', 'max:40', 'regex:/^[\pL\pN][\pL\pN\s&,.\/\-]*$/u'],
            'ringkasan' => ['required', 'string', 'min:20', 'max:500'],
            'isi' => ['required', 'string', function (string $atribut, mixed $nilai, Closure $gagal): void {
                $teks = trim(html_entity_decode(strip_tags((string) $nilai), ENT_QUOTES | ENT_HTML5));

                if (mb_strlen($teks) < 100) {
                    $gagal('Badan artikel minimal 100 karakter teks.');
                }
            }],
            'img' => $this->aturanSampul(),
            'aksi' => ['required', Rule::in(['draft', 'terbitkan'])],
            'published_at' => [
                'bail',
                Rule::requiredIf($terbitkan),
                'nullable',
                'date_format:'.self::FORMAT_JADWAL,
                function (string $atribut, mixed $nilai, Closure $gagal) use ($terbitkan): void {
                    if (! $terbitkan || blank($nilai) || $nilai === $this->jadwalLama()) {
                        return;
                    }

                    $jadwal = Carbon::createFromFormat(self::FORMAT_JADWAL, (string) $nilai, config('app.timezone'));

                    if ($jadwal !== false && $jadwal->lt(now()->subMinutes(self::TOLERANSI_MENIT))) {
                        $gagal('Jadwal terbit sudah lewat. Pilih tanggal & jam sekarang atau yang akan datang.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'judul' => 'judul artikel',
            'kategori' => 'kategori',
            'ringkasan' => 'ringkasan',
            'isi' => 'badan artikel',
            'img' => 'gambar sampul',
            'aksi' => 'aksi simpan',
            'published_at' => 'jadwal terbit',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => ':Attribute wajib diisi.',
            'string' => ':Attribute harus berupa teks.',
            'min.string' => ':Attribute minimal :min karakter.',
            'max.string' => ':Attribute maksimal :max karakter.',
            'judul.unique' => 'Judul ini sudah dipakai artikel lain. Gunakan judul yang berbeda.',
            'kategori.regex' => 'Kategori hanya boleh berisi huruf, angka, spasi, dan tanda & , . / -.',
            'img.required' => 'Gambar sampul wajib diunggah.',
            'img.image' => 'Gambar sampul harus berupa file gambar.',
            'img.mimes' => 'Gambar sampul harus berformat JPG, PNG, atau WebP.',
            'img.max' => 'Ukuran gambar sampul maksimal 4 MB.',
            'img.dimensions' => 'Resolusi gambar sampul harus antara 800 × 420 dan 8000 × 8000 piksel.',
            'aksi.in' => 'Aksi simpan tidak dikenali.',
            'published_at.required' => 'Jadwal terbit wajib diisi. Pilih tanggal & jam, atau simpan sebagai draft.',
            'published_at.date_format' => 'Format jadwal terbit tidak valid. Pilih tanggal dan jam dari kalender.',
        ];
    }
}
