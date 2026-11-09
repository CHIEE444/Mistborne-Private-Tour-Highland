@props([
    // $butir: [['judul' => '...', 'keterangan' => '...', 'waktu' => '...', 'selesai' => true], ...]
    'butir' => [],
])

<ol {{ $attributes->class(['space-y-5']) }}>
    @forelse ($butir as $item)
        <li class="relative ps-6">
            <span @class([
                'absolute start-0 top-1 h-2.5 w-2.5 rounded-full',
                'bg-hijau-aksi' => $item['selesai'] ?? true,
                'bg-gray-300' => ! ($item['selesai'] ?? true),
            ])></span>

            @if (! $loop->last)
                <span class="absolute start-[4px] top-4 h-full w-px bg-surface-border"></span>
            @endif

            <p class="text-sm font-medium text-ink">{{ $item['judul'] }}</p>

            @if (! empty($item['keterangan']))
                <p class="text-sm text-muted">{{ $item['keterangan'] }}</p>
            @endif

            @if (! empty($item['waktu']))
                <p class="mt-0.5 text-xs text-muted">{{ $item['waktu'] }}</p>
            @endif
        </li>
    @empty
        <x-admin.keadaan-kosong icon="history" judul="Belum ada riwayat" />
    @endforelse
</ol>
