<?php

namespace App\Http\Requests\Admin\JadwalKeberangkatan;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpanJadwalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('perjalanan')) ?? false;
    }

    /** Batas jumlah hari dalam satu kali tambah rentang, supaya tidak tak sengaja membuat ratusan tanggal. */
    public const MAKS_HARI_RENTANG = 60;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mode' => ['required', Rule::in(['satu', 'rentang'])],
            'tanggal' => ['required_if:mode,satu', 'nullable', 'date', 'after_or_equal:today'],
            'tanggal_mulai' => ['required_if:mode,rentang', 'nullable', 'date', 'after_or_equal:today'],
            'tanggal_selesai' => [
                'required_if:mode,rentang',
                'nullable',
                'date',
                'after_or_equal:tanggal_mulai',
                function (string $atribut, mixed $nilai, Closure $gagal): void {
                    $mulai = $this->date('tanggal_mulai');

                    if ($this->input('mode') === 'rentang' && $mulai && $nilai && $mulai->diffInDays($this->date('tanggal_selesai')) >= self::MAKS_HARI_RENTANG) {
                        $gagal('Rentang tanggal maksimal '.self::MAKS_HARI_RENTANG.' hari sekali tambah.');
                    }
                },
            ],
            'kuota' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'mode.required' => 'Pilih cara menambah tanggal: satu tanggal atau rentang tanggal.',
            'mode.in' => 'Cara menambah tanggal tidak dikenal.',
            'tanggal.required_if' => 'Pilih tanggal keberangkatan.',
            'tanggal.date' => 'Tanggal keberangkatan tidak valid.',
            'tanggal.after_or_equal' => 'Tanggal tidak boleh sebelum hari ini.',
            'tanggal_mulai.required_if' => 'Pilih tanggal mulai.',
            'tanggal_mulai.date' => 'Tanggal mulai tidak valid.',
            'tanggal_mulai.after_or_equal' => 'Tanggal mulai tidak boleh sebelum hari ini.',
            'tanggal_selesai.required_if' => 'Pilih tanggal selesai.',
            'tanggal_selesai.date' => 'Tanggal selesai tidak valid.',
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'kuota.integer' => 'Kuota harus berupa angka bulat.',
            'kuota.min' => 'Kuota minimal 1 grup.',
            'kuota.max' => 'Kuota maksimal 50 grup.',
        ];
    }
}
