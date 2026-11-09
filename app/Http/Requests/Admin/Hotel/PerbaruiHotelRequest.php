<?php

namespace App\Http\Requests\Admin\Hotel;

use App\Models\Hotel;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class PerbaruiHotelRequest extends HotelRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('hotel')) ?? false;
    }

    protected function aturanNamaUnik(): Unique
    {
        /** @var Hotel $hotel */
        $hotel = $this->route('hotel');

        return Rule::unique('hotel', 'nama')->ignore($hotel->id)->whereNull('deleted_at');
    }
}
