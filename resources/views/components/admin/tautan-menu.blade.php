@props([
    'href',
    'label',
    'icon' => null,
    'active' => false,
    'badge' => null,
    'badgeKunci' => null,
    'urut' => 0,
])

<a
    href="{{ $href }}"
    data-gelembung="{{ $label }}"
    @if ($active) aria-current="page" @endif
    {{ $attributes->class(['rail-item']) }}
    style="--i: {{ $urut }}"
>
    <span class="rail-ikon">
        @if ($icon)
            <span class="material-symbols-outlined" aria-hidden="true">{{ $icon }}</span>
        @endif

        @if ($badgeKunci)
            <span
                x-cloak
                x-show="$store.badge.{{ $badgeKunci }} > 0"
                x-text="$store.badge.{{ $badgeKunci }} > 99 ? '99+' : $store.badge.{{ $badgeKunci }}"
                :aria-label="$store.badge.{{ $badgeKunci }} + ' baru'"
                class="rail-lencana"
            ></span>
        @elseif (! is_null($badge) && $badge !== 0)
            <span class="rail-lencana" style="background:#C8A24A">{{ $badge }}</span>
        @endif
    </span>

    <span class="rail-teks">{{ $label }}</span>
</a>
