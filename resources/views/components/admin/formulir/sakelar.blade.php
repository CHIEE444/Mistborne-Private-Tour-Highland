@props([
    'name',
    'value' => '1',
    'checked' => false,
    'petunjuk' => null,
])

{{-- Sakelar dari <input type="checkbox"> asli: bisa diklik di mana saja (termasuk teks), bisa dengan
     keyboard (Spasi), dan nilainya selalu terkirim — "0" bila mati, "{{ $value }}" bila hidup. --}}
<div {{ $attributes }}>
    <label class="inline-flex cursor-pointer items-center gap-3">
        <input type="hidden" name="{{ $name }}" value="0" />
        <input type="checkbox" name="{{ $name }}" value="{{ $value }}" @checked(old($name, $checked)) class="peer sr-only" />

        <span
            class="relative h-6 w-11 shrink-0 rounded-full bg-gray-300 transition-colors after:absolute after:start-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow after:transition-transform after:content-[''] peer-checked:bg-hijau-aksi peer-checked:after:translate-x-5 peer-focus-visible:ring-2 peer-focus-visible:ring-hijau-aksi peer-focus-visible:ring-offset-2 rtl:peer-checked:after:-translate-x-5"
            aria-hidden="true"
        ></span>

        @if ($slot->isNotEmpty())
            <span class="text-sm font-medium text-ink">{{ $slot }}</span>
        @endif
    </label>

    @if ($petunjuk)
        <p class="mt-1.5 text-xs text-muted">{{ $petunjuk }}</p>
    @endif
</div>
