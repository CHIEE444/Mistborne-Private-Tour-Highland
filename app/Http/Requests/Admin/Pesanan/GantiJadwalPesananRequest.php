<?php

namespace App\Http\Requests\Admin\Pesanan;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class GantiJadwalPesananRequest extends FormRequest
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
        return [
            'tanggal_trip' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:'.now()->addDays(7)->toDateString(),
                'before_or_equal:'.now()->addDays(7)->addYear()->toDateString(),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'tanggal_trip.required' => 'Pilih tanggal trip yang baru.',
            'tanggal_trip.date_format' => 'Tanggal trip harus dalam format yang valid.',
            'tanggal_trip.after_or_equal' => 'Tanggal baru harus minimal tujuh hari dari hari ini (H-7).',
            'tanggal_trip.before_or_equal' => 'Tanggal baru hanya dapat dipilih sampai satu tahun sejak batas minimal H-7.',
        ];
    }
}
