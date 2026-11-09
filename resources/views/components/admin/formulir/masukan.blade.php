@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'wajib' => false,
    'petunjuk' => null,
    'icon' => null,
    'idInput' => null,
])

{{--
    $name boleh dot-notation ("praktis.pickup") supaya old()/$errors (yang baca array
    bersarang dengan titik) tetap benar; atribut HTML name= butuh notasi kurung
    ("praktis[pickup]") supaya PHP merakitnya jadi array saat form dikirim.
--}}
@php $namaHtml = str_contains($name, '.') ? preg_replace('/\.([^.]+)/', '[$1]', $name) : $name; @endphp

@php [$kelasKolom, $atributInput] = \App\Bantuan\KelasKolom::pisah($attributes); $idHtml = $idInput ?? $name; @endphp

<x-admin.formulir.kolom :label="$label" :for="$idHtml" :wajib="$wajib" :petunjuk="$petunjuk" :name="$name" :class="$kelasKolom">
    <div class="relative">
        @if ($icon)
            <span class="material-symbols-outlined pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 text-lg text-muted" aria-hidden="true">{{ $icon }}</span>
        @endif

        <input
            type="{{ $type }}"
            id="{{ $idHtml }}"
            name="{{ $namaHtml }}"
            value="{{ old($name, $value) }}"
            @if ($wajib) data-wajib="{{ $label ? $label.' wajib diisi.' : 'Isian ini wajib diisi.' }}" @endif
            data-kunci="{{ $name }}"
            @if ($wajib) required aria-required="true" @endif
            {{ $atributInput->class([
                'w-full rounded-lg border bg-white py-2.5 text-sm text-ink placeholder:text-muted focus:outline-none focus:ring-1',
                'ps-10' => $icon,
                'px-3.5' => ! $icon,
                'border-status-dibatalkan-teks focus:border-status-dibatalkan-teks focus:ring-status-dibatalkan-teks' => $errors->has($name),
                'border-surface-border focus:border-hijau-aksi focus:ring-hijau-aksi' => ! $errors->has($name),
            ]) }}
        />
    </div>
</x-admin.formulir.kolom>
