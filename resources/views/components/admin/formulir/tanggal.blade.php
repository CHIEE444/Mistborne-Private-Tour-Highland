@props([
    'name',
    'label' => null,
    'value' => null,
    'wajib' => false,
    'petunjuk' => null,
    'min' => null,
    'max' => null,
])

@php [$kelasKolom, $atributInput] = \App\Bantuan\KelasKolom::pisah($attributes); @endphp

<x-admin.formulir.kolom :label="$label" :for="$name" :wajib="$wajib" :petunjuk="$petunjuk" :name="$name" :class="$kelasKolom">
    <input
        type="date"
        id="{{ $name }}"
        name="{{ $name }}"
        @if ($wajib) data-wajib="{{ $label ? $label.' wajib diisi.' : 'Isian ini wajib diisi.' }}" @endif data-kunci="{{ $name }}"
        value="{{ old($name, $value) }}"
        @if ($min) min="{{ $min }}" @endif
        @if ($max) max="{{ $max }}" @endif
        {{ $atributInput->class([
            'w-full rounded-lg border bg-white px-3.5 py-2.5 text-sm text-ink focus:outline-none focus:ring-1',
            'border-status-dibatalkan-teks focus:border-status-dibatalkan-teks focus:ring-status-dibatalkan-teks' => $errors->has($name),
            'border-surface-border focus:border-hijau-aksi focus:ring-hijau-aksi' => ! $errors->has($name),
        ]) }}
    />
</x-admin.formulir.kolom>
