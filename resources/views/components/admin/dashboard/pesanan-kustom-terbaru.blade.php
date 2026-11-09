@props(['data'])

<x-admin.kartu>
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-lg font-bold text-ink">Pesanan Kustom Terbaru</h2>
        @if ($data->isNotEmpty())
            <span class="rounded-full bg-status-payment-bg px-2 py-0.5 text-[11px] font-semibold text-status-payment-teks">{{ $data->count() }} Permintaan</span>
        @endif
    </div>

    @if ($data->isEmpty())
        <x-admin.keadaan-kosong icon="tune" judul="Belum ada pesanan kustom" />
    @else
        <div class="space-y-3">
            @foreach ($data as $item)
                <a href="{{ route('admin.pesanan-kustom.detail', $item->id) }}" class="block rounded-lg border border-surface-border p-3 hover:bg-page">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-ink">{{ $item->nama }}</p>
                        <x-admin.lencana-status :status="$item->status" />
                    </div>
                    <p class="mt-0.5 text-xs text-muted">Destinasi: {{ $item->destinasi_diinginkan }}</p>
                </a>
            @endforeach
        </div>

        <a href="{{ route('admin.pesanan-kustom.indeks') }}" class="mt-4 flex items-center justify-center gap-1 text-sm font-medium text-hijau-aksi hover:underline">
            Lihat semua kustom
            <span class="material-symbols-outlined text-base" aria-hidden="true">arrow_forward</span>
        </a>
    @endif
</x-admin.kartu>
