<?php

namespace App\Http\Requests\Admin\Kendaraan;

class SimpanServisKendaraanRequest extends ServisKendaraanRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('kendaraan')) ?? false;
    }
}
