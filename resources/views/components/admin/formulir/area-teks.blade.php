@props([
    'name',
    'label' => null,
    'value' => null,
    'wajib' => false,
    'petunjuk' => null,
    'rows' => 4,
    'idInput' => null,
])

@php $idHtml = $idInput ?? $name; @endphp

@php [$kelasKolom, $atributInput] = \App\Bantuan\KelasKolom::pisah($attributes); @endphp

<x-admin.formulir.kolom :label="$label" :for="$idHtml" :wajib="$wajib" :petunjuk="$petunjuk" :name="$name" :class="$kelasKolom">
    @php $namaHtml = str_contains($name, '.') ? preg_replace('/\.([^.]+)/', '[$1]', $name) : $name; @endphp
    <textarea
        id="{{ $idHtml }}"
        name="{{ $namaHtml }}"
        rows="{{ $rows }}"
        @if ($wajib) data-wajib="{{ $label ? $label.' wajib diisi.' : 'Isian ini wajib diisi.' }}" @endif data-kunci="{{ $name }}"
        {{ $atributInput->class([
            'w-full rounded-lg border bg-white px-3.5 py-2.5 text-sm text-ink placeholder:text-muted focus:outline-none focus:ring-1',
            'border-status-dibatalkan-teks focus:border-status-dibatalkan-teks focus:ring-status-dibatalkan-teks' => $errors->has($name),
            'border-surface-border focus:border-hijau-aksi focus:ring-hijau-aksi' => ! $errors->has($name),
        ]) }}
    >{{ old($name, $value) }}</textarea>
</x-admin.formulir.kolom>
