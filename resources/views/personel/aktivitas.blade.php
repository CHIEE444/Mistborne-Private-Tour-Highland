<x-tata-letak.personel :personel="$personel">
    <div class="mb-8">
        <p class="text-sm font-medium text-muted">Panel Guide</p>
        <h1 class="mt-1 text-2xl font-semibold text-ink">Log Aktivitas</h1>
        <p class="mt-2 text-sm text-muted">Jejak penugasan, tanggapan, perubahan jadwal, dan aktivitas akun Anda. Yang terbaru di atas.</p>
    </div>

    <x-admin.kartu>
        @forelse ($aktivitas as $item)
            <div @class(['flex gap-4 py-4', 'border-t border-surface-border' => ! $loop->first])>
                <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full bg-hijau-aksi"></span>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                        <p class="text-sm font-semibold text-ink">{{ $item->labelJenis() }}</p>
                        @if ($item->pesanan)<span class="text-xs text-muted">{{ $item->pesanan->kode }}</span>@endif
                    </div>
                    <p class="mt-0.5 text-sm text-ink">{{ $item->keterangan }}</p>
                    <p class="mt-1 text-xs text-muted">{{ $item->created_at->timezone(config('app.timezone'))->translatedFormat('d M Y, H:i') }} WIB</p>
                </div>
            </div>
        @empty
            <x-admin.keadaan-kosong icon="history" judul="Belum ada aktivitas" keterangan="Penugasan dan aktivitas akun Anda akan tercatat di sini." />
        @endforelse
    </x-admin.kartu>

    <div class="mt-4">{{ $aktivitas->links() }}</div>
</x-tata-letak.personel>
