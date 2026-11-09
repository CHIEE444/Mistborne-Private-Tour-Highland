{{--
    Editor rich text ringan (Trix — dari Basecamp, satu file JS+CSS, tanpa dependency build
    tambahan) dimuat lewat CDN, bukan paket npm/composer baru. @once memastikan asetnya
    hanya dimuat sekali per halaman walau komponen ini dipakai berkali-kali.
--}}
@props([
    'name',
    'label' => null,
    'value' => null,
    'wajib' => false,
    'petunjuk' => null,
])

@once
    @push('scripts')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/trix@2.1.5/dist/trix.css">
        <script src="https://cdn.jsdelivr.net/npm/trix@2.1.5/dist/trix.umd.min.js"></script>
        <style>
            trix-toolbar .trix-button-group { border-color: var(--color-surface-border, #DFE3D8); }
            trix-editor {
                min-height: 260px;
                border-radius: 0 0 0.75rem 0.75rem;
                border: 1px solid var(--color-surface-border, #DFE3D8);
                border-top: 0;
                padding: 0.75rem 1rem;
                font-size: 0.875rem;
            }
            /* Lampiran file tidak didukung (isi artikel hanya teks berformat) */
            trix-toolbar .trix-button-group--file-tools { display: none; }
            /* Reset Tailwind menghapus gaya ini; dipulihkan supaya tombol toolbar terlihat bekerja */
            trix-editor strong, trix-editor b { font-weight: 700; }
            trix-editor em, trix-editor i { font-style: italic; }
            trix-editor del { text-decoration: line-through; }
            trix-editor h1 { font-size: 1.35rem; font-weight: 700; line-height: 1.3; margin: .5rem 0; }
            trix-editor blockquote { border-inline-start: 3px solid #1D7A56; padding-inline-start: .9rem; margin: .5rem 0; font-style: italic; }
            trix-editor ul { list-style: disc; padding-inline-start: 1.5rem; }
            trix-editor ol { list-style: decimal; padding-inline-start: 1.5rem; }
            trix-editor pre { background: #F1F5EF; border-radius: .5rem; padding: .6rem .8rem; font-family: ui-monospace, monospace; }
            trix-editor a { color: #1D7A56; text-decoration: underline; }
            trix-toolbar { border: 1px solid var(--color-surface-border, #DFE3D8); border-bottom: 0; border-radius: 0.75rem 0.75rem 0 0; background: var(--color-page, #F5F6F2); padding: 0.5rem; }
        </style>
        <script>
            document.addEventListener('trix-file-accept', (event) => event.preventDefault());
        </script>
    @endpush
@endonce

@php [$kelasKolom, $atributInput] = \App\Bantuan\KelasKolom::pisah($attributes); @endphp

<x-admin.formulir.kolom :label="$label" :for="$name" :wajib="$wajib" :petunjuk="$petunjuk" :name="$name" :class="$kelasKolom">
    <input
        id="{{ $name }}" type="hidden" name="{{ $name }}" value="{{ old($name, $value) }}"
        data-kunci="{{ $name }}"
        @if ($wajib) data-wajib="{{ $label ? $label.' wajib diisi.' : 'Isian ini wajib diisi.' }}" @endif
    />
    <trix-editor input="{{ $name }}" class="trix-content" x-on:trix-change="$data.bersihkan?.({ target: $el.inputElement })" {{ $atributInput }}></trix-editor>
</x-admin.formulir.kolom>
