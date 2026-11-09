<?php

namespace App\Http\Requests\Admin\Perjalanan;

use App\Models\Perjalanan;
use Illuminate\Contracts\Validation\ValidationRule;

class SimpanPerjalananRequest extends PerjalananRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Perjalanan::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            // Journey baru wajib punya minimal satu tanggal tersedia.
            'tanggal_pertama' => ['required', 'date', 'after_or_equal:today'],
            'kuota_pertama' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            ...parent::attributes(),
            'tanggal_pertama' => 'tanggal keberangkatan pertama',
            'kuota_pertama' => 'kuota tanggal pertama',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'tanggal_pertama.required' => 'Pilih tanggal keberangkatan pertama. Journey baru wajib punya minimal satu tanggal tersedia.',
            'tanggal_pertama.date' => 'Tanggal keberangkatan pertama tidak valid.',
            'tanggal_pertama.after_or_equal' => 'Tanggal keberangkatan pertama tidak boleh sebelum hari ini.',
            'kuota_pertama.min' => 'Kuota minimal 1 grup.',
            'kuota_pertama.max' => 'Kuota maksimal 50 grup.',
        ];
    }

    protected function aturanNama(): array
    {
        return ['required', 'string', 'max:255', 'unique:perjalanan,nama'];
    }

    protected function aturanSampul(): array
    {
        return ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'];
    }
}
