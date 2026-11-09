<?php

namespace App\Http\Requests\Admin\Kendaraan;

use App\Models\Kendaraan;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class PerbaruiKendaraanRequest extends KendaraanRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('kendaraan')) ?? false;
    }

    /**
     * Odometer hanya boleh naik: nilai di bawah yang sudah tercatat, atau dikosongkan, hampir pasti salah ketik
     * dan merusak patokan jatuh tempo servis.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Kendaraan $kendaraan */
        $kendaraan = $this->route('kendaraan');
        $rules = parent::rules();

        if ($kendaraan->odometer !== null) {
            $rules['odometer'] = ['required', 'integer', 'min:'.$kendaraan->odometer, 'max:9999999'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        /** @var Kendaraan $kendaraan */
        $kendaraan = $this->route('kendaraan');

        return [
            ...parent::messages(),
            'odometer.required' => 'Odometer tidak boleh dikosongkan karena sudah tercatat '.number_format((int) $kendaraan->odometer, 0, ',', '.').' km.',
            'odometer.min' => 'Odometer hanya boleh naik: tidak boleh lebih kecil dari yang tercatat ('.number_format((int) $kendaraan->odometer, 0, ',', '.').' km).',
        ];
    }

    protected function aturanPlatUnik(): Unique
    {
        /** @var Kendaraan $kendaraan */
        $kendaraan = $this->route('kendaraan');

        return Rule::unique('kendaraan', 'plat_nomor')->ignore($kendaraan->id)->whereNull('deleted_at');
    }
}
