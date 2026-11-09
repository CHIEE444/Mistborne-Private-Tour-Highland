<?php

namespace App\Http\Requests\Admin\Hotel;

use App\Bantuan\NomorWhatsApp;
use App\Models\Hotel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\Validator;

/**
 * Aturan & pesan validasi bersama form Hotel (tambah & ubah). Isian wajib: nama, bintang, area,
 * estimasi perjalanan, dan jam check-in/out (kapasitas kamar mengikuti journey, bukan isian admin); kontak dan tarif opsional tetapi diperiksa formatnya.
 */
abstract class HotelRequest extends FormRequest
{
    /** Nama unik di antara hotel aktif; saat ubah, hotel itu sendiri dikecualikan. */
    abstract protected function aturanNamaUnik(): Unique;

    protected function prepareForValidation(): void
    {
        $whatsapp = trim((string) $this->input('whatsapp'));

        $this->merge([
            'nama' => preg_replace('/\s+/u', ' ', trim((string) $this->input('nama'))),
            'area' => preg_replace('/\s+/u', ' ', trim((string) $this->input('area'))),
            'whatsapp' => $whatsapp === '' ? null : NomorWhatsApp::lokal($whatsapp),
            'tersedia_extra_bed' => $this->boolean('tersedia_extra_bed'),
            'aktif' => $this->boolean('aktif'),
            'sorotan' => $this->boolean('sorotan'),
            'fasilitas' => array_values((array) $this->input('fasilitas', [])),
            'tipe_kamar' => $this->barisTipeKamarTerisi(),
            'perjalanan_ids' => array_values(array_filter((array) $this->input('perjalanan_ids', []))),
        ]);
    }

    /**
     * Baris tipe kamar yang namanya terisi (baris kosong dari tombol "Tambah tipe" dibuang).
     *
     * @return list<array{nama: string, kapasitas: mixed}>
     */
    private function barisTipeKamarTerisi(): array
    {
        return collect((array) $this->input('tipe_kamar', []))
            ->filter(fn ($baris): bool => is_array($baris))
            ->map(fn (array $baris): array => ['nama' => preg_replace('/\s+/u', ' ', trim((string) ($baris['nama'] ?? ''))), 'kapasitas' => filter_var($baris['kapasitas'] ?? null, FILTER_VALIDATE_INT) ?: ($baris['kapasitas'] ?? null)])
            ->filter(fn (array $baris): bool => $baris['nama'] !== '')
            ->values()
            ->all();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'min:3', 'max:150', 'not_regex:/[<>]/', $this->aturanNamaUnik()],
            'bintang' => ['required', 'integer', Rule::in(Hotel::BINTANG_DIIZINKAN)],
            'area' => ['required', 'string', 'min:3', 'max:100', 'not_regex:/[<>]/'],
            'alamat' => ['nullable', 'string', 'max:500', 'not_regex:/[<>]/'],
            'deskripsi' => ['nullable', 'string', 'max:1000', 'not_regex:/[<>]/'],
            'jarak_destinasi' => ['required', 'string', 'min:3', 'max:150', 'not_regex:/[<>]/'],
            'tautan_peta' => ['nullable', 'url:http,https', 'max:500'],
            'google_place_id' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9_-]+$/'],
            'google_rating' => ['nullable', 'numeric', 'between:1,5'],
            'google_jumlah_ulasan' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:max_width=8000,max_height=8000'],
            'galeri_lama' => ['array', 'max:'.Hotel::MAKS_GALERI],
            'galeri_lama.*.foto' => ['required', 'string', 'max:500'],
            'galeri_lama.*.label' => ['required', Rule::in(array_keys(Hotel::LABEL_GALERI))],
            'galeri_lama.*.hapus' => ['boolean'],
            'galeri_baru' => ['array', 'max:'.Hotel::MAKS_GALERI],
            'galeri_baru.*.berkas' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:max_width=8000,max_height=8000'],
            'galeri_baru.*.label' => ['required', Rule::in(array_keys(Hotel::LABEL_GALERI))],
            'whatsapp' => ['nullable', 'regex:/^08[0-9]{7,12}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'nama_pic' => ['nullable', 'string', 'max:100', 'not_regex:/[<>]/'],
            'tarif_mulai' => ['nullable', 'integer', 'min:50000', 'max:50000000'],
            'tarif_sampai' => ['nullable', 'integer', 'min:50000', 'max:50000000', 'gte:tarif_mulai'],
            'kapasitas_kamar' => ['nullable', 'integer', 'min:1', 'max:10'],
            'tersedia_extra_bed' => ['boolean'],
            'tipe_kamar' => ['array', 'max:10'],
            'tipe_kamar.*.nama' => ['required', 'string', 'max:60', 'not_regex:/[<>]/', 'distinct:ignore_case'],
            'tipe_kamar.*.kapasitas' => ['required', 'integer', 'min:1', 'max:10'],
            'fasilitas' => ['array'],
            'fasilitas.*' => ['string', Rule::in(array_keys(Hotel::FASILITAS))],
            'jam_check_in' => ['required', 'date_format:H:i'],
            'jam_check_out' => ['required', 'date_format:H:i'],
            'kebijakan_pembatalan' => ['nullable', 'string', 'max:1000', 'not_regex:/[<>]/'],
            'catatan' => ['nullable', 'string', 'max:1000', 'not_regex:/[<>]/'],
            'aktif' => ['boolean'],
            'sorotan' => ['boolean'],
            'perjalanan_ids' => ['array'],
            'perjalanan_ids.*' => ['integer', Rule::exists('perjalanan', 'id')->where('template_pesanan_kustom', false)->whereNull('deleted_at')],
        ];
    }

    /**
     * Satu journey hanya punya satu hotel aktif: hotel aktif tidak boleh ditautkan ke journey yang sudah punya hotel aktif lain.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $dipertahankan = collect((array) $this->input('galeri_lama'))->reject(fn ($baris): bool => ! empty($baris['hapus']))->count();

            if ($dipertahankan + count((array) $this->input('galeri_baru')) > Hotel::MAKS_GALERI) {
                $validator->errors()->add('galeri_baru', 'Galeri maksimal '.Hotel::MAKS_GALERI.' foto per hotel. Hapus foto lama atau kurangi foto baru.');
            }

            if ($validator->errors()->isNotEmpty() || ! $this->boolean('aktif') || $this->input('perjalanan_ids') === []) {
                return;
            }

            $sudahPunya = DB::table('hotel_perjalanan')
                ->join('hotel', 'hotel.id', '=', 'hotel_perjalanan.hotel_id')
                ->join('perjalanan', 'perjalanan.id', '=', 'hotel_perjalanan.perjalanan_id')
                ->where('hotel.aktif', true)
                ->whereNull('hotel.deleted_at')
                ->where('hotel.id', '<>', $this->route('hotel')?->id)
                ->whereIn('hotel_perjalanan.perjalanan_id', $this->input('perjalanan_ids'))
                ->pluck('perjalanan.nama');

            if ($sudahPunya->isNotEmpty()) {
                $validator->errors()->add('perjalanan_ids', 'Satu journey hanya boleh punya satu hotel aktif. Journey ini sudah punya hotel aktif: '.$sudahPunya->unique()->implode(', ').'. Lepas atau nonaktifkan hotel itu dulu.');
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nama' => 'nama hotel',
            'bintang' => 'bintang',
            'area' => 'area',
            'alamat' => 'alamat',
            'tautan_peta' => 'tautan peta',
            'foto' => 'foto hotel',
            'google_place_id' => 'Place ID Google',
            'google_rating' => 'rating Google',
            'google_jumlah_ulasan' => 'jumlah ulasan Google',
            'galeri_baru.*.berkas' => 'foto galeri',
            'galeri_baru.*.label' => 'kategori foto',
            'galeri_lama.*.label' => 'kategori foto',
            'whatsapp' => 'nomor WhatsApp hotel',
            'email' => 'email hotel',
            'nama_pic' => 'nama PIC',
            'tarif_mulai' => 'tarif mulai',
            'tarif_sampai' => 'tarif sampai',
            'kapasitas_kamar' => 'kapasitas kamar',
            'jam_check_in' => 'jam check-in',
            'jam_check_out' => 'jam check-out',
            'kebijakan_pembatalan' => 'kebijakan pembatalan',
            'catatan' => 'catatan',
            'fasilitas.*' => 'fasilitas',
            'deskripsi' => 'deskripsi hotel',
            'jarak_destinasi' => 'estimasi perjalanan ke destinasi',
            'tipe_kamar.*.nama' => 'nama tipe kamar',
            'tipe_kamar.*.kapasitas' => 'kapasitas tipe kamar',
            'perjalanan_ids.*' => 'journey',
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
            'integer' => ':Attribute harus berupa angka bulat.',
            'min.string' => ':Attribute minimal :min karakter.',
            'max.string' => ':Attribute maksimal :max karakter.',
            'not_regex' => ':Attribute tidak boleh memuat karakter < atau >.',
            'nama.unique' => 'Nama hotel ini sudah terdaftar. Gunakan nama yang berbeda, misalnya tambahkan areanya.',
            'google_place_id.regex' => 'Place ID Google hanya boleh berisi huruf, angka, strip, dan garis bawah.',
            'google_rating.between' => 'Rating Google harus antara 1,0 dan 5,0.',
            'google_rating.numeric' => 'Rating Google harus berupa angka, contoh 4.6.',
            'foto.image' => 'Foto harus berupa file gambar.',
            'foto.mimes' => 'Foto harus berformat JPG, PNG, atau WebP.',
            'foto.max' => 'Ukuran foto maksimal 4 MB.',
            'foto.dimensions' => 'Resolusi foto maksimal 8000 × 8000 piksel.',
            'galeri_baru.*.berkas.required' => 'Pilih file untuk setiap baris foto galeri, atau hapus barisnya.',
            'galeri_baru.*.berkas.image' => 'Foto galeri harus berupa file gambar.',
            'galeri_baru.*.berkas.mimes' => 'Foto galeri harus berformat JPG, PNG, atau WebP.',
            'galeri_baru.*.berkas.max' => 'Ukuran tiap foto galeri maksimal 4 MB.',
            'galeri_baru.*.berkas.dimensions' => 'Resolusi tiap foto galeri maksimal 8000 × 8000 piksel.',
            'galeri_*.*.label.in' => 'Kategori foto tidak dikenal.',
            'galeri_lama.max' => 'Galeri maksimal '.Hotel::MAKS_GALERI.' foto per hotel.',
            'galeri_baru.max' => 'Galeri maksimal '.Hotel::MAKS_GALERI.' foto per hotel.',
            'bintang.in' => 'Hotel mitra MISTBORNE minimal bintang 4 (pilih bintang 4 atau 5).',
            'tautan_peta.url' => 'Tautan peta harus berupa alamat web yang diawali http:// atau https://.',
            'whatsapp.regex' => 'Masukkan nomor WhatsApp hotel dengan angka saja, diawali 08 atau +62, contoh 081234567890.',
            'email.email' => 'Format email hotel tidak valid.',
            'tarif_mulai.min' => 'Tarif minimal Rp 50.000 per kamar per malam.',
            'tarif_mulai.max' => 'Tarif terlalu besar. Periksa kembali angkanya.',
            'tarif_sampai.min' => 'Tarif minimal Rp 50.000 per kamar per malam.',
            'tarif_sampai.max' => 'Tarif terlalu besar. Periksa kembali angkanya.',
            'tarif_sampai.gte' => 'Tarif sampai tidak boleh lebih kecil dari tarif mulai.',
            'kapasitas_kamar.min' => 'Kapasitas kamar minimal 1 orang.',
            'kapasitas_kamar.max' => 'Kapasitas kamar maksimal 10 orang.',
            'jam_check_in.date_format' => 'Jam check-in tidak valid. Gunakan format jam:menit.',
            'jam_check_out.date_format' => 'Jam check-out tidak valid. Gunakan format jam:menit.',
            'tipe_kamar.*.nama.distinct' => 'Nama tipe kamar tidak boleh kembar dalam satu hotel.',
            'tipe_kamar.*.kapasitas.min' => 'Kapasitas tipe kamar minimal 1 orang.',
            'tipe_kamar.*.kapasitas.max' => 'Kapasitas tipe kamar maksimal 10 orang.',
            'tipe_kamar.max' => 'Tipe kamar maksimal 10 per hotel.',
            'fasilitas.*.in' => 'Fasilitas yang dipilih tidak dikenal.',
            'perjalanan_ids.*.exists' => 'Journey yang dipilih tidak ditemukan.',
        ];
    }
}
