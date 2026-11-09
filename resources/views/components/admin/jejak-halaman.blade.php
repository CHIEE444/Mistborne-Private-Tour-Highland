@props([
    'group' => null,
    'title' => null,
])

@if ($title)
    <nav {{ $attributes->class(['items-center gap-2 overflow-hidden text-sm']) }} aria-label="Jejak halaman">
        @if ($group)
            <span class="text-muted">{{ $group }}</span>
            <span class="material-symbols-outlined text-sm text-muted" aria-hidden="true">chevron_right</span>
        @endif
        <span class="truncate font-medium text-ink">{{ $title }}</span>
    </nav>
@endif
