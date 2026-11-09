<?php

namespace App\Http\Requests\Admin\JadwalKeberangkatan;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PerbaruiJadwalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('perjalanan')) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'kuota' => ['nullable', 'integer', 'min:1', 'max:50', function (string $atribut, mixed $nilai, Closure $gagal): void {
                $terisi = $this->route('tanggal')->terisi();

                if (filled($nilai) && (int) $nilai < $terisi) {
                    $gagal("Kuota tidak boleh di bawah jumlah pesanan aktif di tanggal ini ({$terisi}). Pindahkan atau batalkan pesanan dulu.");
                }
            }],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kuota.integer' => 'Kuota harus berupa angka bulat.',
            'kuota.min' => 'Kuota minimal 1 grup.',
            'kuota.max' => 'Kuota maksimal 50 grup.',
        ];
    }
}
