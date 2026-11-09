@props(['data'])

@php
    $warnaBatang = ['bg-[#1FB57B]', 'bg-[#F5B83D]', 'bg-[#1AB5B8]', 'bg-[#EF5B5B]'];
@endphp

<x-admin.kartu>
    <div class="mb-5 flex items-center justify-between">
        <div>
            <h2 class="text-lg font-bold text-ink">Journey Terlaris</h2>
            <span class="text-[11px] text-muted">Berdasarkan reservasi</span>
        </div>
        <a href="{{ route('admin.perjalanan.indeks') }}" class="flex items-center gap-1 text-sm font-medium text-hijau-aksi hover:underline">
            Lihat semua
            <span class="material-symbols-outlined text-base" aria-hidden="true">arrow_forward</span>
        </a>
    </div>

    @if ($data->isEmpty())
        <x-admin.keadaan-kosong icon="explore" judul="Belum ada data" />
    @else
        <ul class="space-y-5">
            @foreach ($data as $baris)
                <li class="flex items-center gap-4">
                    <img
                        src="{{ $baris['journey']->sampul() }}"
                        alt="{{ $baris['journey']->nama }}"
                        width="56" height="56" loading="lazy" decoding="async"
                        class="size-14 shrink-0 rounded-xl object-cover"
                    />
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-ink">{{ $baris['journey']->nama }}</p>
                                <p class="truncate text-xs text-muted">{{ $baris['journey']->tempat ?? \App\Models\Perjalanan::labelRegion($baris['journey']->region) }}</p>
                            </div>
                            <span class="shrink-0 text-sm font-semibold tabular-nums text-ink">{{ $baris['jumlah'] }} <span class="text-xs font-normal text-muted">pesanan</span></span>
                        </div>
                        <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-page">
                            <div class="h-full rounded-full {{ $warnaBatang[$loop->index % count($warnaBatang)] }}" style="width: {{ $baris['persen'] }}%"></div>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</x-admin.kartu>
