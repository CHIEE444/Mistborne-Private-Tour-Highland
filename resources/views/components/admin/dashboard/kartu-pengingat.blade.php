@props(['pengingatH7', 'pengingatH3', 'pengingatH2'])

{{-- Pengingat ke tamu per tahap. Tahap dihitung Pesanan::tahapPengingatBerlaku() (H-7 = sisa 4–7 hari, H-3 = sisa 3 hari,
     H-2 = sisa 1–2 hari), aturan yang sama dengan "Pengingat ke tamu" di detail pesanan, jadi tanda terkirim selalu sinkron.
     Tab pertama yang dibuka adalah tahap yang masih punya pengingat belum terkirim. --}}
@php
    $tahap = [
        'h7' => ['label' => 'H-7', 'daftar' => $pengingatH7, 'kosong' => 'Tidak ada trip yang berangkat 4–7 hari lagi.'],
        'h3' => ['label' => 'H-3', 'daftar' => $pengingatH3, 'kosong' => 'Tidak ada trip yang berangkat 3 hari lagi.'],
        'h2' => ['label' => 'H-2', 'daftar' => $pengingatH2, 'kosong' => 'Tidak ada trip yang berangkat 1–2 hari lagi.'],
    ];
    $belum = fn (string $jenis): int => $tahap[$jenis]['daftar']->reject(fn ($pesanan) => $pesanan->pengingatSudahTerkirim($jenis))->count();
    $tabAwal = collect(array_keys($tahap))->first(fn (string $jenis): bool => $belum($jenis) > 0) ?? 'h7';
@endphp

<x-admin.kartu id="pengingat-tamu" class="scroll-mt-6" x-data="{ tab: '{{ $tabAwal }}' }">
    <div class="mb-4 flex items-start justify-between gap-3">
        <div>
            <h2 class="text-lg font-bold text-ink">Pengingat ke Tamu</h2>
            <p class="text-xs text-muted">Sama dengan Aksi Cepat di detail pesanan.</p>
        </div>
        @php $totalBelum = $belum('h7') + $belum('h3') + $belum('h2'); @endphp
        <span @class([
            'shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold',
            'bg-amber-700 text-white' => $totalBelum > 0,
            'bg-status-completed-bg text-status-completed-teks' => $totalBelum === 0,
        ])>{{ $totalBelum > 0 ? $totalBelum.' belum terkirim' : 'Semua terkirim' }}</span>
    </div>

    <x-admin.tab class="mb-4">
        @foreach ($tahap as $jenis => $info)
            <button type="button" x-on:click="tab = '{{ $jenis }}'" :class="tab === '{{ $jenis }}' ? 'border-admin-sidebar text-ink' : 'border-transparent text-muted'" class="flex items-center gap-1.5 border-b-2 px-3 py-2 text-sm font-medium">
                {{ $info['label'] }}
                @if ($belum($jenis) > 0)
                    <span class="rounded-full bg-amber-700 px-1.5 text-[10px] font-bold tabular-nums text-white">{{ $belum($jenis) }}</span>
                @else
                    <span class="text-xs text-muted">({{ $info['daftar']->count() }})</span>
                @endif
            </button>
        @endforeach
    </x-admin.tab>

    @foreach ($tahap as $jenis => $info)
        <div x-show="tab === '{{ $jenis }}'" @if ($jenis !== $tabAwal) x-cloak @endif class="max-h-[28rem] space-y-2 overflow-y-auto pe-1">
            {{-- Yang belum terkirim di atas. --}}
            @forelse ($info['daftar']->sortBy(fn ($pesanan) => $pesanan->pengingatSudahTerkirim($jenis)) as $item)
                <x-admin.dashboard.baris-pengingat :pesanan="$item" :jenis="$jenis" />
            @empty
                <p class="py-6 text-center text-xs text-muted">{{ $info['kosong'] }}</p>
            @endforelse
        </div>
    @endforeach
</x-admin.kartu>
