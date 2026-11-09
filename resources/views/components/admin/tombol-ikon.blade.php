@props([
    'icon',
    'variant' => 'ghost',
    'label' => null,
    'as' => 'button',
    'type' => 'button',
])

@php
    $gayaVarian = match ($variant) {
        'danger' => 'text-status-dibatalkan-teks hover:bg-status-dibatalkan-bg',
        'secondary' => 'border border-surface-border text-ink hover:bg-page',
        default => 'text-ink hover:bg-page',
    };
    $kelas = "flex h-9 w-9 items-center justify-center rounded-full transition-colors {$gayaVarian}";
@endphp

@if ($as === 'a')
    <a {{ $attributes->class([$kelas]) }} @if ($label) aria-label="{{ $label }}" @endif>
        <span class="material-symbols-outlined text-[20px]" aria-hidden="true">{{ $icon }}</span>
    </a>
@else
    <button
        type="{{ $type }}"
        {{ $attributes->class([$kelas]) }}
        @if ($label) aria-label="{{ $label }}" @endif
    >
        <span class="material-symbols-outlined text-[20px]" aria-hidden="true">{{ $icon }}</span>
    </button>
@endif
