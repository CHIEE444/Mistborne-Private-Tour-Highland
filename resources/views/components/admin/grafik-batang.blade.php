@props([
    // $data: [['label' => 'Jan', 'nilai' => 10, 'nilaiKedua' => 2], ...]
    'data' => [],
    'warna' => 'bg-sage',
    'warnaKedua' => 'bg-status-dibatalkan-teks',
])

@php
    $maksimum = max(1, collect($data)->max(fn ($baris) => max($baris['nilai'] ?? 0, $baris['nilaiKedua'] ?? 0)));
@endphp

{{-- overflow-x-auto + min-width per kolom: di layar sempit, batang discroll ke samping
     daripada dipaksa muat (flex-1 akan membuat tiap batang jadi terlalu tipis/berimpit). --}}
<div {{ $attributes->class(['overflow-x-auto']) }} x-data="{ aktif: null }">
    <div class="flex items-end gap-4" style="min-width: {{ max(count($data), 1) * 2.5 }}rem">
        @forelse ($data as $i => $baris)
            <div
                class="relative flex flex-1 flex-col items-center gap-2"
                x-on:mouseenter="aktif = {{ $i }}"
                x-on:mouseleave="aktif = null"
            >
                {{-- Tooltip nilai pasti saat hover — data asli, bukan sekadar tinggi batang relatif --}}
                <div
                    x-show="aktif === {{ $i }}"
                    x-cloak
                    x-transition.opacity
                    class="pointer-events-none absolute -top-1 left-1/2 z-10 w-max -translate-x-1/2 -translate-y-full whitespace-nowrap rounded-lg bg-admin-sidebar px-3 py-2 text-xs text-white shadow-admin-kartu"
                >
                    <p class="font-semibold">{{ $baris['label'] }}</p>
                    <p class="text-admin-sidebar-teks">
                        Dipesan {{ $baris['nilai'] ?? 0 }}{{ isset($baris['nilaiKedua']) ? ', Dibatalkan '.$baris['nilaiKedua'] : '' }}
                    </p>
                </div>

                <div class="flex h-40 w-full items-end justify-center gap-1">
                    <div
                        class="{{ $warna }} w-3 rounded-t-sm transition-opacity"
                        :class="aktif !== null && aktif !== {{ $i }} ? 'opacity-40' : 'opacity-100'"
                        style="height: {{ ($baris['nilai'] ?? 0) / $maksimum * 100 }}%"
                    ></div>

                    @if (isset($baris['nilaiKedua']))
                        <div
                            class="{{ $warnaKedua }} w-3 rounded-t-sm transition-opacity"
                            :class="aktif !== null && aktif !== {{ $i }} ? 'opacity-40' : 'opacity-100'"
                            style="height: {{ $baris['nilaiKedua'] / $maksimum * 100 }}%"
                        ></div>
                    @endif
                </div>
                <span class="text-xs text-muted">{{ $baris['label'] }}</span>
            </div>
        @empty
            <x-admin.keadaan-kosong icon="bar_chart" judul="Belum ada data" class="w-full" />
        @endforelse
    </div>
</div>
