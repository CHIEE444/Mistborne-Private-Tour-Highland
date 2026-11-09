@props(['type' => 'info'])

@php
    $gaya = match ($type) {
        'success' => ['bg' => 'bg-status-completed-bg', 'teks' => 'text-status-completed-teks', 'icon' => 'check_circle'],
        'warning' => ['bg' => 'bg-status-payment-bg', 'teks' => 'text-status-payment-teks', 'icon' => 'warning'],
        'danger' => ['bg' => 'bg-status-dibatalkan-bg', 'teks' => 'text-status-dibatalkan-teks', 'icon' => 'error'],
        default => ['bg' => 'bg-status-dihubungi-bg', 'teks' => 'text-status-dihubungi-teks', 'icon' => 'info'],
    };
@endphp

<div {{ $attributes->class(["flex items-start gap-3 rounded-admin-kartu px-4 py-3 text-sm {$gaya['bg']} {$gaya['teks']}"]) }}>
    <span class="material-symbols-outlined text-lg" aria-hidden="true">{{ $gaya['icon'] }}</span>
    <div class="flex-1">{{ $slot }}</div>
</div>
