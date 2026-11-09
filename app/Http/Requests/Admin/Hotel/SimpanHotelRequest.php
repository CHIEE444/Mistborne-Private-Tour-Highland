<?php

namespace App\Http\Requests\Admin\Hotel;

use App\Models\Hotel;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class SimpanHotelRequest extends HotelRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Hotel::class) ?? false;
    }

    protected function aturanNamaUnik(): Unique
    {
        return Rule::unique('hotel', 'nama')->whereNull('deleted_at');
    }
}
