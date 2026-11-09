<?php

namespace App\Http\Requests\Admin\Pesanan;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/** Tidak ada input — request ini murni gerbang otorisasi untuk aksi "Konfirmasi, tinggal bayar". */
class KonfirmasiPesananRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('pesanan')) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
