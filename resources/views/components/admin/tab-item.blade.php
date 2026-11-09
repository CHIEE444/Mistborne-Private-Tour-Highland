@props([
    'href' => '#',
    'active' => false,
    'badge' => null,
])

<a
    href="{{ $href }}"
    {{ $attributes->class([
        'flex items-center gap-2 border-b-2 px-3 py-3 text-sm font-medium transition-colors',
        'border-admin-sidebar text-ink' => $active,
        'border-transparent text-muted hover:text-ink' => ! $active,
    ]) }}
>
    {{ $slot }}

    @if (! is_null($badge))
        <span @class([
            'rounded-full px-1.5 py-0.5 text-[11px] font-semibold',
            'bg-admin-sidebar text-white' => $active,
            'bg-page text-muted' => ! $active,
        ])>
            {{ $badge }}
        </span>
    @endif
</a>
