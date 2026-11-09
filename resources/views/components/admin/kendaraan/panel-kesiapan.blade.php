@props(['kesiapan'])

@php
    $panjangBusur = 251.3;
    $warnaGauge = match ($kesiapan['tingkat']) {
        'ok' => 'text-status-completed-teks',
        'perhatian' => 'text-status-payment-teks',
        default => 'text-status-dibatalkan-teks',
    };
    $ikonTingkat = ['ok' => 'check_circle', 'perhatian' => 'warning', 'bahaya' => 'error'];
@endphp

<x-admin.kartu class="flex min-w-0 flex-col gap-5">
    <h2 class="flex items-center gap-2 text-base font-semibold text-ink">
        <span class="material-symbols-outlined text-xl text-muted" aria-hidden="true">monitor_heart</span>
        Skor Kesiapan
    </h2>

    <div class="flex flex-col items-center">
        <svg viewBox="0 0 200 120" class="w-full max-w-[16rem] {{ $warnaGauge }}" role="img" aria-label="Skor kesiapan {{ $kesiapan['skor'] }} dari 100, {{ $kesiapan['label'] }}">
            <path d="M 20 100 A 80 80 0 0 1 180 100" fill="none" class="stroke-surface-border" stroke-width="14" stroke-linecap="round" />
            @if ($kesiapan['skor'] > 0)
                <path d="M 20 100 A 80 80 0 0 1 180 100" fill="none" stroke="currentColor" stroke-width="14" stroke-linecap="round" stroke-dasharray="{{ round($kesiapan['skor'] / 100 * $panjangBusur, 1) }} {{ $panjangBusur }}" />
            @endif
            <text x="100" y="92" text-anchor="middle" class="fill-ink" font-size="40" font-weight="600">{{ $kesiapan['skor'] }}</text>
            <text x="100" y="112" text-anchor="middle" class="fill-muted" font-size="12">dari 100</text>
        </svg>
        <p class="-mt-1 text-sm font-semibold {{ $warnaGauge }}">{{ $kesiapan['label'] }}</p>
    </div>

    <section aria-labelledby="judul-hasil-pemeriksaan">
        <h3 id="judul-hasil-pemeriksaan" class="mb-3 text-sm font-semibold text-ink">Hasil pemeriksaan</h3>
        <ul class="space-y-2">
            @foreach ($kesiapan['pemeriksaan'] as $hasil)
                <li class="flex items-start gap-3 rounded-xl border border-surface-border px-3.5 py-3">
                    <span @class([
                        'material-symbols-outlined mt-0.5 text-xl',
                        'text-status-completed-teks' => $hasil['tingkat'] === 'ok',
                        'text-status-payment-teks' => $hasil['tingkat'] === 'perhatian',
                        'text-status-dibatalkan-teks' => $hasil['tingkat'] === 'bahaya',
                    ]) aria-hidden="true">{{ $ikonTingkat[$hasil['tingkat']] }}</span>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-ink">{{ $hasil['label'] }}</p>
                        <p class="text-xs text-muted">{{ $hasil['keterangan'] }}</p>
                    </div>
                </li>
            @endforeach
        </ul>
    </section>
</x-admin.kartu>
