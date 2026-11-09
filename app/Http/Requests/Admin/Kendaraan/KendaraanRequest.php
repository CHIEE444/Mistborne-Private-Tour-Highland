<?php

namespace App\Http\Requests\Admin\Kendaraan;

use App\Models\Kendaraan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/**
 * Aturan & pesan validasi bersama form Kendaraan (tambah & ubah). Semua isian wajib diisi
 * kecuali tahun, warna, bahan bakar, odometer, masa berlaku STNK/pajak, foto, dan catatan.
 */
abstract class KendaraanRequest extends FormRequest
{
    /** Plat unik di antara kendaraan aktif; saat ubah, kendaraan itu sendiri dikecualikan. */
    abstract protected function aturanPlatUnik(): Unique;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nama' => preg_replace('/\s+/u', ' ', trim((string) $this->input('nama'))),
            'plat_nomor' => Kendaraan::rapikanPlat((string) $this->input('plat_nomor')),
            'warna' => trim((string) $this->input('warna')) ?: null,
            'bahan_bakar' => $this->input('bahan_bakar') ?: null,
            'odometer' => $this->input('odometer') === '' ? null : $this->input('odometer'),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'min:3', 'max:100', 'not_regex:/[<>]/'],
            'jenis' => ['required', Rule::in(array_keys(Kendaraan::JENIS))],
            'plat_nomor' => ['required', 'string', 'regex:/^[A-Z]{1,2} \d{1,4}( [A-Z]{1,3})?$/', $this->aturanPlatUnik()],
            'kapasitas' => ['required', 'integer', 'min:1', 'max:60'],
            'tahun' => ['nullable', 'integer', 'min:1990', 'max:'.(now()->year + 1)],
            'warna' => ['nullable', 'string', 'max:30', 'not_regex:/[<>]/'],
            'bahan_bakar' => ['nullable', Rule::in(array_keys(Kendaraan::BAHAN_BAKAR))],
            'odometer' => ['nullable', 'integer', 'min:0', 'max:9999999'],
            'stnk_berlaku_sampai' => ['nullable', 'date'],
            'pajak_berlaku_sampai' => ['nullable', 'date'],
            'status' => ['required', Rule::in(array_keys(Kendaraan::STATUS))],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:max_width=8000,max_height=8000'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nama' => 'nama kendaraan',
            'jenis' => 'jenis kendaraan',
            'plat_nomor' => 'plat nomor',
            'kapasitas' => 'kapasitas',
            'tahun' => 'tahun',
            'warna' => 'warna',
            'bahan_bakar' => 'bahan bakar',
            'odometer' => 'odometer',
            'stnk_berlaku_sampai' => 'masa berlaku STNK',
            'pajak_berlaku_sampai' => 'masa berlaku pajak',
            'status' => 'status',
            'foto' => 'foto kendaraan',
            'catatan' => 'catatan',
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
            'jenis.in' => 'Pilih jenis kendaraan dari daftar.',
            'status.in' => 'Pilih status dari daftar.',
            'bahan_bakar.in' => 'Pilih bahan bakar dari daftar.',
            'odometer.min' => 'Odometer tidak boleh negatif.',
            'odometer.max' => 'Odometer terlalu besar. Periksa kembali angkanya.',
            'date' => ':Attribute harus berupa tanggal yang valid.',
            'plat_nomor.regex' => 'Format plat nomor tidak valid. Contoh: B 1234 XYZ atau N 987 AB.',
            'plat_nomor.unique' => 'Plat nomor ini sudah terdaftar pada kendaraan lain.',
            'kapasitas.min' => 'Kapasitas minimal 1 penumpang.',
            'kapasitas.max' => 'Kapasitas maksimal 60 penumpang.',
            'tahun.min' => 'Tahun kendaraan minimal 1990.',
            'tahun.max' => 'Tahun kendaraan tidak boleh melebihi tahun depan.',
            'foto.image' => 'Foto harus berupa file gambar.',
            'foto.mimes' => 'Foto harus berformat JPG, PNG, atau WebP.',
            'foto.max' => 'Ukuran foto maksimal 4 MB.',
            'foto.dimensions' => 'Resolusi foto maksimal 8000 × 8000 piksel.',
        ];
    }
}
