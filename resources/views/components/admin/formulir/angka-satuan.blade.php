@props([
    'name',
    'label' => null,
    'value' => null,
    'satuan' => null,
    'wajib' => false,
    'petunjuk' => null,
])

@php [$kelasKolom, $atributInput] = \App\Bantuan\KelasKolom::pisah($attributes); @endphp

<x-admin.formulir.kolom :label="$label" :for="$name" :wajib="$wajib" :petunjuk="$petunjuk" :name="$name" :class="$kelasKolom">
    <div class="relative">
        <input
            type="number"
            id="{{ $name }}"
            name="{{ $name }}"
            value="{{ old($name, $value) }}"
            {{ $atributInput->class([
                'w-full rounded-lg border bg-white py-2.5 ps-3.5 text-sm text-ink focus:outline-none focus:ring-1',
                'pe-14' => $satuan,
                'pe-3.5' => ! $satuan,
                'border-status-dibatalkan-teks focus:border-status-dibatalkan-teks focus:ring-status-dibatalkan-teks' => $errors->has($name),
                'border-surface-border focus:border-hijau-aksi focus:ring-hijau-aksi' => ! $errors->has($name),
            ]) }}
        />

        @if ($satuan)
            <span class="pointer-events-none absolute end-3.5 top-1/2 -translate-y-1/2 text-sm text-muted">{{ $satuan }}</span>
        @endif
    </div>
</x-admin.formulir.kolom>
