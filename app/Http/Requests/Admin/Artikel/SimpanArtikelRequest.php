<?php

namespace App\Http\Requests\Admin\Artikel;

use App\Models\Artikel;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class SimpanArtikelRequest extends ArtikelRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Artikel::class) ?? false;
    }

    protected function aturanSampul(): array
    {
        return ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:min_width=800,min_height=420,max_width=8000,max_height=8000'];
    }

    protected function aturanJudulUnik(): Unique
    {
        return Rule::unique('artikel', 'judul')->whereNull('deleted_at');
    }
}
