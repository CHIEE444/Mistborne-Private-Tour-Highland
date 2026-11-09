@props([
    'user' => null,
    // Alternatif :user — dipakai saat yang tersedia cuma nama (mis. tamu di tabel Pesanan,
    // bukan akun User sungguhan).
    'nama' => null,
    'size' => 'md',
])

@php
    $ukuran = match ($size) {
        'sm' => 'h-8 w-8 text-xs',
        'lg' => 'h-14 w-14 text-lg',
        'xl' => 'h-24 w-24 text-3xl',
        default => 'h-9 w-9 text-sm',
    };

    $nama = $user?->name ?? $nama ?? '?';
    $inisial = collect(explode(' ', trim($nama)))
        ->filter()
        ->take(2)
        ->map(fn (string $kata) => mb_strtoupper(mb_substr($kata, 0, 1)))
        ->implode('');

    // Warna avatar dari palet status yang sama (bg lembut + teks pekat) — dipilih deterministik
    // dari nama supaya satu orang selalu dapat warna yang sama, hanya untuk membedakan orang
    // secara visual (tidak menyiratkan status pesanan apa pun).
    $paletAvatar = [
        'bg-status-dihubungi-bg text-status-dihubungi-teks',
        'bg-status-payment-bg text-status-payment-teks',
        'bg-status-hold-schedule-bg text-status-hold-schedule-teks',
        'bg-status-completed-bg text-status-completed-teks',
        'bg-status-dibatalkan-bg text-status-dibatalkan-teks',
    ];
    $gayaWarna = $paletAvatar[crc32($nama) % count($paletAvatar)];
@endphp

@if ($user?->foto)
    <img
        src="{{ asset('storage/'.$user->foto) }}"
        alt="{{ $nama }}"
        {{ $attributes->class(["rounded-full object-cover {$ukuran}"]) }}
    />
@else
    <span
        {{ $attributes->class(["flex shrink-0 items-center justify-center rounded-full font-semibold {$gayaWarna} {$ukuran}"]) }}
    >
        {{ $inisial ?: '?' }}
    </span>
@endif
