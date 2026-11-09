@props([
    'name',
    'label' => null,
    'pilihan' => [],
    'value' => null,
    'placeholder' => null,
    'wajib' => false,
    'petunjuk' => null,
    'idInput' => null,
])

@php [$kelasKolom, $atributInput] = \App\Bantuan\KelasKolom::pisah($attributes); $idHtml = $idInput ?? $name; @endphp

<x-admin.formulir.kolom :label="$label" :for="$idHtml" :wajib="$wajib" :petunjuk="$petunjuk" :name="$name" :class="$kelasKolom">
    @php $namaHtml = str_contains($name, '.') ? preg_replace('/\.([^.]+)/', '[$1]', $name) : $name; @endphp
    <select
        id="{{ $idHtml }}"
        name="{{ $namaHtml }}"
        @if ($wajib) required aria-required="true" @endif
        @if ($wajib) data-wajib="{{ $label ? $label.' wajib diisi.' : 'Isian ini wajib diisi.' }}" @endif data-kunci="{{ $name }}"
        {{ $atributInput->class([
            'w-full rounded-lg border bg-white py-2.5 ps-3.5 pe-9 text-sm text-ink focus:outline-none focus:ring-1',
            'border-status-dibatalkan-teks focus:border-status-dibatalkan-teks focus:ring-status-dibatalkan-teks' => $errors->has($name),
            'border-surface-border focus:border-hijau-aksi focus:ring-hijau-aksi' => ! $errors->has($name),
        ]) }}
    >
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif

        @foreach ($pilihan as $kunci => $teks)
            <option value="{{ $kunci }}" @selected((string) old($name, $value) === (string) $kunci)>{{ $teks }}</option>
        @endforeach
    </select>
</x-admin.formulir.kolom>
