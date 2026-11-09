@props(['pesan' => null])

@if ($pesan)
    <p {{ $attributes->class(['mt-1.5 flex items-center gap-1 text-xs text-status-dibatalkan-teks']) }}>
        <span class="material-symbols-outlined text-sm" aria-hidden="true">error</span>
        {{ $pesan }}
    </p>
@endif
