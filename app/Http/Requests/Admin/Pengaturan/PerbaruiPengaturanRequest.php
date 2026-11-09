<?php

namespace App\Http\Requests\Admin\Pengaturan;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PerbaruiPengaturanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nomor_whatsapp_admin' => ['required', 'regex:/^62[0-9]{8,13}$/'],
            'persen_charge_ganti_jadwal' => ['required', 'integer', 'min:0', 'max:100'],
            'batas_bayar_hari' => ['required', 'integer', 'min:1', 'max:30'],
            'kuota_default' => ['required', 'integer', 'min:1', 'max:50'],
            'batas_ulasan_hari' => ['required', 'integer', 'min:1', 'max:60'],
            'batas_video_mb' => ['required', 'integer', 'min:10', 'max:1000'],
            'rekomendasi_penginapan' => ['nullable', 'string', 'max:3000', 'not_regex:/[<>]/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nomor_whatsapp_admin.regex' => 'Format nomor harus diawali 62, contoh: 6281234567890.',
            'rekomendasi_penginapan.max' => 'Daftar rekomendasi penginapan maksimal 3.000 karakter.',
            'rekomendasi_penginapan.not_regex' => 'Daftar rekomendasi penginapan tidak boleh memuat karakter < atau >.',
            'rekomendasi_penginapan.string' => 'Daftar rekomendasi penginapan harus berupa teks.',
        ];
    }
}
