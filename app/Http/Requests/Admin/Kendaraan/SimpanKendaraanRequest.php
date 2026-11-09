<?php

namespace App\Http\Requests\Admin\Kendaraan;

use App\Models\Kendaraan;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class SimpanKendaraanRequest extends KendaraanRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Kendaraan::class) ?? false;
    }

    protected function aturanPlatUnik(): Unique
    {
        return Rule::unique('kendaraan', 'plat_nomor')->whereNull('deleted_at');
    }
}
