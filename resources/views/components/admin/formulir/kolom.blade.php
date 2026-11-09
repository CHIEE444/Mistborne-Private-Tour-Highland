@props([
    'label' => null,
    'for' => null,
    'wajib' => false,
    'petunjuk' => null,
    'name' => null,
])

@php
    $namaError = $name ?? $for;
@endphp

<div {{ $attributes->class(['w-full']) }}>
    @if ($label)
        <x-admin.formulir.label :for="$for" :wajib="$wajib">{{ $label }}</x-admin.formulir.label>
    @endif

    {{ $slot }}

    @if ($petunjuk)
        <p class="mt-1.5 text-xs text-muted">{{ $petunjuk }}</p>
    @endif

    @if ($namaError)
        @error($namaError)
            <x-admin.formulir.galat :pesan="$message" />
        @enderror

        {{-- Galat sisi klien dari Alpine validasiFormulir (tidak aktif bila form tidak memakainya) --}}
        <p
            x-cloak
            x-show="$data.galat?.['{{ $namaError }}']"
            class="mt-1.5 flex items-center gap-1 text-xs text-status-dibatalkan-teks"
            role="alert"
        >
            <span class="material-symbols-outlined text-sm" aria-hidden="true">error</span>
            <span x-text="$data.galat?.['{{ $namaError }}']"></span>
        </p>
    @endif
</div>
