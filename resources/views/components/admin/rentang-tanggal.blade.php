@props([
    'namaDari' => 'dari',
    'namaSampai' => 'sampai',
    'dari' => null,
    'sampai' => null,
])

<div {{ $attributes->class(['flex max-w-full items-center gap-2 rounded-full border border-surface-border bg-white px-3 py-2 text-sm']) }}>
    <span class="material-symbols-outlined text-lg text-muted" aria-hidden="true">calendar_month</span>
    <input type="date" name="{{ $namaDari }}" value="{{ $dari }}" class="w-28 min-w-0 shrink border-0 p-0 text-sm text-ink focus:outline-none focus:ring-0" />
    <span class="text-muted">—</span>
    <input type="date" name="{{ $namaSampai }}" value="{{ $sampai }}" class="w-28 min-w-0 shrink border-0 p-0 text-sm text-ink focus:outline-none focus:ring-0" />
</div>
