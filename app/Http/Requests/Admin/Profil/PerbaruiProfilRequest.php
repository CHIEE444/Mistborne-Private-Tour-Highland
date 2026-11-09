<?php

namespace App\Http\Requests\Admin\Profil;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PerbaruiProfilRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'jabatan' => ['sometimes', 'required', 'string', 'max:255', function (string $atribut, mixed $nilai, Closure $gagal): void {
                if (! in_array(mb_strtolower(trim((string) $nilai)), ['admin', 'admin operasional'], true)) {
                    $gagal('Jabatan akun admin harus "Admin" atau "Admin Operasional". Jabatan lain mengunci akun ini keluar dari panel admin.');
                }
            }],
            'foto' => ['nullable', 'image', 'max:2048'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }
}
