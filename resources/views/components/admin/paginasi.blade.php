@props(['paginator'])

@if ($paginator instanceof \Illuminate\Contracts\Pagination\Paginator && $paginator->hasPages())
    <div {{ $attributes->class(['flex flex-col items-center justify-between gap-3 border-t border-surface-border px-4 py-3 text-sm text-muted sm:flex-row']) }}>
        <p>
            Menampilkan <span class="font-medium text-ink">{{ $paginator->count() }}</span>
            dari <span class="font-medium text-ink">{{ $paginator->total() }}</span> data
        </p>

        <div class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="rounded-full px-3 py-1.5 text-muted/50">Sebelumnya</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="rounded-full px-3 py-1.5 hover:bg-page">Sebelumnya</a>
            @endif

            @foreach ($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $halaman => $url)
                <a
                    href="{{ $url }}"
                    @class([
                        'h-8 w-8 rounded-full text-center leading-8',
                        'bg-admin-sidebar text-white' => $halaman === $paginator->currentPage(),
                        'hover:bg-page' => $halaman !== $paginator->currentPage(),
                    ])
                >
                    {{ $halaman }}
                </a>
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="rounded-full px-3 py-1.5 hover:bg-page">Selanjutnya</a>
            @else
                <span class="rounded-full px-3 py-1.5 text-muted/50">Selanjutnya</span>
            @endif
        </div>
    </div>
@endif
