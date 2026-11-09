@props([
    // $segmen: [['label' => 'Dihubungi', 'nilai' => 12, 'warna' => '#2563AA'], ...]
    'segmen' => [],
    'total' => null,
    'labelTengah' => 'Pesanan',
])

@php
    $jumlah = $total ?? collect($segmen)->sum('nilai');
    $lingkaran = 100; // keliling dalam persen (stroke-dasharray berbasis 100)
    $offset = 0;
@endphp

<div {{ $attributes->class(['flex items-center gap-6']) }}>
    <div class="relative h-32 w-32 shrink-0">
        <svg viewBox="0 0 36 36" class="h-full w-full -rotate-90">
            <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#EDEDED" stroke-width="3.6"></circle>

            @foreach ($segmen as $bagian)
                @php
                    $persen = $jumlah > 0 ? ($bagian['nilai'] / $jumlah) * 100 : 0;
                @endphp

                <circle
                    cx="18" cy="18" r="15.9155" fill="none"
                    stroke="{{ $bagian['warna'] }}" stroke-width="3.6"
                    stroke-dasharray="{{ $persen }} {{ 100 - $persen }}"
                    stroke-dashoffset="{{ -$offset }}"
                ></circle>

                @php $offset += $persen; @endphp
            @endforeach
        </svg>

        <div class="absolute inset-0 flex flex-col items-center justify-center">
            <span class="text-xl font-semibold text-ink">{{ $jumlah }}</span>
            <span class="text-[11px] text-muted">{{ $labelTengah }}</span>
        </div>
    </div>

    <ul class="space-y-2 text-sm">
        @foreach ($segmen as $bagian)
            <li class="flex items-center gap-2">
                <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $bagian['warna'] }}"></span>
                <span class="text-muted">{{ $bagian['label'] }}</span>
                <span class="font-medium text-ink">{{ $bagian['nilai'] }}</span>
            </li>
        @endforeach
    </ul>
</div>
