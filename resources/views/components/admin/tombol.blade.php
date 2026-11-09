@props([
    'variant' => 'primary',
    'size' => 'md',
    'icon' => null,
    'loading' => false,
    'as' => 'button',
    'type' => 'button',
])

@php
    $gayaVarian = match ($variant) {
        'secondary' => 'border border-surface-border bg-white text-ink hover:border-sage/50 hover:bg-sage-muda/60',
        'danger' => 'border border-status-dibatalkan-teks bg-status-dibatalkan-teks text-white hover:opacity-90',
        'danger-outline' => 'border border-status-dibatalkan-teks/40 bg-white text-status-dibatalkan-teks hover:bg-status-dibatalkan-bg',
        // Solid agar jelas terlihat di samping Terapkan; amber-700 menjaga kontras teks putih di atas 4,5:1.
        'reset' => 'border border-amber-700 bg-amber-700 text-white shadow-[0_8px_18px_-10px_rgba(180,83,9,0.9)] hover:border-amber-800 hover:bg-amber-800',
        'ghost' => 'text-ink hover:bg-page',
        default => 'bg-linear-to-b from-sage to-sage-tua text-white shadow-[0_8px_18px_-10px_rgba(78,112,89,0.9)] hover:from-sage-tua hover:to-admin-sidebar-active',
    };

    $gayaUkuran = match ($size) {
        'sm' => 'px-3 py-1.5 text-xs',
        'lg' => 'px-5 py-3 text-base',
        default => 'px-4 py-2.5 text-sm',
    };

    $kelas = "inline-flex items-center justify-center gap-2 rounded-full font-semibold transition-colors disabled:cursor-not-allowed disabled:opacity-50 {$gayaVarian} {$gayaUkuran}";
@endphp

@if ($as === 'a')
    <a {{ $attributes->class([$kelas]) }}>
        @if ($icon && ! $loading)
            <span class="material-symbols-outlined text-[18px]" aria-hidden="true">{{ $icon }}</span>
        @endif

        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" @disabled($loading) {{ $attributes->class([$kelas]) }}>
        @if ($loading)
            <span class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-current border-t-transparent" aria-hidden="true"></span>
        @elseif ($icon)
            <span class="material-symbols-outlined text-[18px]" aria-hidden="true">{{ $icon }}</span>
        @endif

        {{ $slot }}
    </button>
@endif
