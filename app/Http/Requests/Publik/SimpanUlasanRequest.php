<?php

namespace App\Http\Requests\Publik;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SimpanUlasanRequest extends FormRequest
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
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'komentar' => ['required', 'string', 'min:5', 'max:2000'],
            'izin_tampil' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rating.required' => 'Mohon beri rating bintang.',
            'komentar.required' => 'Mohon tulis komentar/ulasan Anda.',
            'komentar.min' => 'Komentar minimal 5 karakter.',
        ];
    }
}
