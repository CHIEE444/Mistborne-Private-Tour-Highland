<?php

namespace App\Http\Requests\Admin\Kendaraan;

use App\Models\ServisKendaraan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Aturan & pesan validasi bersama form servis kendaraan (tambah & ubah). Servis terjadwal wajib punya
 * tanggal atau batas kilometer jatuh tempo; servis yang sudah dikerjakan wajib punya tanggal pengerjaan.
 */
abstract class ServisKendaraanRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'jenis' => preg_replace('/\s+/u', ' ', trim((string) $this->input('jenis'))),
            ...collect(['jatuh_tempo_odometer', 'odometer_servis', 'biaya', 'jatuh_tempo_tanggal', 'tanggal_servis'])
                ->mapWithKeys(fn (string $kolom): array => [$kolom => $this->input($kolom) === '' ? null : $this->input($kolom)])
                ->all(),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $selesai = $this->input('status') === ServisKendaraan::SELESAI;

        return [
            'jenis' => ['required', 'string', 'min:3', 'max:80', 'not_regex:/[<>]/'],
            'status' => ['required', Rule::in(array_keys(ServisKendaraan::STATUS))],
            'jatuh_tempo_tanggal' => ['nullable', 'date', Rule::requiredIf(! $selesai && blank($this->input('jatuh_tempo_odometer')))],
            'jatuh_tempo_odometer' => ['nullable', 'integer', 'min:0', 'max:9999999'],
            'tanggal_servis' => ['nullable', 'date', 'before_or_equal:today', Rule::requiredIf($selesai)],
            'odometer_servis' => ['nullable', 'integer', 'min:0', 'max:9999999'],
            'biaya' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'catatan' => ['nullable', 'string', 'max:1000', 'not_regex:/[<>]/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'jenis' => 'jenis servis',
            'status' => 'status servis',
            'jatuh_tempo_tanggal' => 'tanggal jatuh tempo',
            'jatuh_tempo_odometer' => 'batas kilometer',
            'tanggal_servis' => 'tanggal servis',
            'odometer_servis' => 'odometer saat servis',
            'biaya' => 'biaya servis',
            'catatan' => 'catatan servis',
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
            'date' => ':Attribute harus berupa tanggal yang valid.',
            'min.string' => ':Attribute minimal :min karakter.',
            'max.string' => ':Attribute maksimal :max karakter.',
            'not_regex' => ':Attribute tidak boleh memuat karakter < atau >.',
            'status.in' => 'Pilih status servis dari daftar.',
            'jatuh_tempo_tanggal.required' => 'Isi tanggal jatuh tempo atau batas kilometer servis.',
            'tanggal_servis.required' => 'Isi tanggal servis dikerjakan.',
            'tanggal_servis.before_or_equal' => 'Tanggal servis tidak boleh di masa depan. Gunakan servis terjadwal untuk jadwal mendatang.',
            'min.numeric' => ':Attribute tidak boleh negatif.',
        ];
    }
}
