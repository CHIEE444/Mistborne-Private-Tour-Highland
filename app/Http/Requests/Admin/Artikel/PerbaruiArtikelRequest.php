<?php

namespace App\Http\Requests\Admin\Artikel;

use App\Models\Artikel;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class PerbaruiArtikelRequest extends ArtikelRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('artikel')) ?? false;
    }

    /** Sampul wajib hanya bila artikel belum punya sampul. */
    protected function aturanSampul(): array
    {
        $aturan = ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:min_width=800,min_height=420,max_width=8000,max_height=8000'];

        return filled($this->artikel()->img)
            ? ['nullable', ...$aturan]
            : ['required', ...$aturan];
    }

    protected function aturanJudulUnik(): Unique
    {
        return Rule::unique('artikel', 'judul')->ignore($this->artikel()->id)->whereNull('deleted_at');
    }

    protected function jadwalLama(): ?string
    {
        return $this->artikel()->published_at?->format(self::FORMAT_JADWAL);
    }

    private function artikel(): Artikel
    {
        /** @var Artikel */
        return $this->route('artikel');
    }
}
