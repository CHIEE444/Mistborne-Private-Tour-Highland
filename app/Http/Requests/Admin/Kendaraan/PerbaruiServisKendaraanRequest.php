<?php

namespace App\Http\Requests\Admin\Kendaraan;

class PerbaruiServisKendaraanRequest extends ServisKendaraanRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('kendaraan')) ?? false;
    }
}
