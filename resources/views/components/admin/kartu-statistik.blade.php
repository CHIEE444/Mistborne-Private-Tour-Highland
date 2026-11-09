@props([
    'label',
    'value',
    'icon' => null,
    'tren' => null,
    'trenPositif' => true,
    // Aksen warna ikon — dari palet status yang sama dipakai komponen lencana-status,
    // dipakai ulang di sini supaya tiap kartu statistik punya warna sendiri (bukan abu-abu
    // seragam semua) sesuai desain dashboard.png.
    'warna' => 'dihubungi',
    // 7 angka harian (H-6..hari ini) dari data nyata — lihat StatistikDashboardLayanan::sparklineHarian().
    // Bukan garis dekoratif acak; kosongkan prop ini kalau kartu tidak punya data tren harian.
    'sparkline' => [],
    // Keterangan pembanding di samping lencana tren, mis. "vs minggu lalu".
    'pembanding' => null,
    // Kartu sorotan: latar gradasi sage dengan teks putih (seperti kartu "Activity" pada referensi desain admin).
    'sorot' => false,
])

@php
    // Semua kartu memakai aksen sage yang sama (referensi desain admin); prop $warna dipertahankan agar pemanggil lama tetap valid.
    $gayaWarna = $sorot ? 'bg-white/15 text-white ring-1 ring-white/25' : 'bg-sage-muda text-sage-tua';
    $teksSparkline = $sorot ? 'text-white' : 'text-sage-tua';

    $titikSparkline = null;
    if (count($sparkline) > 1 && max($sparkline) > 0) {
        $maksimum = max($sparkline);
        $jumlahTitik = count($sparkline);
        $titikSparkline = collect($sparkline)
            ->map(fn ($nilai, $i) => round($i / ($jumlahTitik - 1) * 100, 1).','.round(32 - ($nilai / $maksimum * 28), 1))
            ->implode(' ');
    }
@endphp

<x-admin.kartu {{ $attributes->class(['kartu-angkat flex flex-col', 'bg-linear-to-br from-sage to-sage-tua text-white' => $sorot]) }}>
    <div class="flex items-start justify-between gap-3">
        <p @class(['min-w-0 pt-1 text-base font-bold', 'text-white/90' => $sorot, 'text-ink' => ! $sorot])>{{ $label }}</p>

        @if ($icon)
            <span class="flex size-11 shrink-0 items-center justify-center rounded-full {{ $gayaWarna }}">
                <span class="material-symbols-outlined text-[22px]" aria-hidden="true">{{ $icon }}</span>
            </span>
        @endif
    </div>

    <p class="mt-1 text-[22px] font-semibold sm:text-[28px] leading-tight tabular-nums tracking-tight {{ $sorot ? 'text-white' : 'text-ink' }}">{{ $value }}</p>

    @if ($tren || $titikSparkline)
        <div class="mt-auto flex items-end justify-between gap-3 pt-5">
            @if ($tren)
                <div class="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1">
                    <p @class([
                        'inline-flex shrink-0 items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold',
                        'bg-white/20 text-white' => $sorot,
                        'bg-status-completed-bg text-status-completed-teks' => $trenPositif && ! $sorot,
                        'bg-status-dibatalkan-bg text-status-dibatalkan-teks' => ! $trenPositif && ! $sorot,
                    ])>
                        <span class="material-symbols-outlined text-sm" aria-hidden="true">
                            {{ $trenPositif ? 'trending_up' : 'trending_down' }}
                        </span>
                        {{ $tren }}
                    </p>
                    @if ($pembanding)
                        <span class="text-xs {{ $sorot ? 'text-white/75' : 'text-muted' }}">{{ $pembanding }}</span>
                    @endif
                </div>
            @endif

            @if ($titikSparkline)
                <svg viewBox="0 0 100 32" preserveAspectRatio="none" class="h-9 w-20 shrink-0 overflow-hidden {{ $teksSparkline }}" aria-hidden="true">
                    <polygon points="{{ $titikSparkline }} 100,32 0,32" fill="currentColor" opacity="0.1" />
                    <polyline points="{{ $titikSparkline }}" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
                </svg>
            @endif
        </div>
    @endif
</x-admin.kartu>
