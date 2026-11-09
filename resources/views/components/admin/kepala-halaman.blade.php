@props([
    'title',
    'subtitle' => null,
])

<div {{ $attributes->class(['mb-6 flex flex-col gap-4 sm:flex-row sm:flex-wrap sm:items-start sm:justify-between']) }}>
    <div class="min-w-0 max-w-full">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.dashboard.indeks') }}" onclick="if (history.length > 1 && document.referrer.startsWith(location.origin)) { event.preventDefault(); history.back(); }" class="group inline-flex shrink-0 items-center gap-1.5 text-sm font-medium text-muted transition hover:text-hijau-aksi" aria-label="Kembali">
                <span class="material-symbols-outlined text-xl transition-transform group-hover:-translate-x-0.5" aria-hidden="true">arrow_back</span>
                <span class="hidden sm:inline">Kembali</span>
            </a>
            <span class="h-6 w-px bg-surface-border" aria-hidden="true"></span>
            <h1 class="text-2xl font-semibold text-ink">{{ $title }}</h1>
        </div>

        @if ($subtitle)
            <p class="mt-1 text-sm text-muted">{{ $subtitle }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex min-w-0 flex-wrap items-center gap-3">
            {{ $actions }}
        </div>
    @endisset
</div>
