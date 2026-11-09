@props([
    'name',
    'label' => null,
    'value' => null,
    'wajib' => false,
    'petunjuk' => null,
    'nullable' => false,
])

{{-- Blade hanya meneruskan bag atribut ke komponen anak lewat variabel bernama persis $attributes, jadi bag yang sudah
     dipisah (tanpa kelas grid) dipasang kembali ke $attributes. --}}
@php [$kelasKolom, $attributes] = \App\Bantuan\KelasKolom::pisah($attributes); @endphp

<x-admin.formulir.kolom :label="$label" :for="$name" :wajib="$wajib" :petunjuk="$petunjuk" :name="$name" :class="$kelasKolom">
    <x-admin.formulir.input-rupiah
        :name="$name"
        :value="old($name, $value)"
        :nullable="$nullable"
        :invalid="$errors->has($name)"
        data-kunci="{{ $name }}"
        {{ $attributes->merge($wajib ? ['data-wajib' => $label ? $label.' wajib diisi.' : 'Isian ini wajib diisi.'] : []) }}
    />
</x-admin.formulir.kolom>
