<?php

namespace App\Http\Requests\Admin\Perjalanan;

use Illuminate\Validation\Rule;

class PerbaruiPerjalananRequest extends PerjalananRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('perjalanan')) ?? false;
    }

    protected function aturanNama(): array
    {
        return ['required', 'string', 'max:255', Rule::unique('perjalanan', 'nama')->ignore($this->route('perjalanan'))];
    }

    protected function aturanSampul(): array
    {
        return ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'];
    }
}
