@props(['pesanan'])

{{-- Cek akhir H-1: daftar periksa admin (bukan pesan WhatsApp) untuk setiap trip yang berangkat besok. --}}
@php
    $kurangPerPesanan = $pesanan->mapWithKeys(fn ($item) => [$item->id => $item->belumSiapBerangkat()]);
    $jumlahBelumSiap = $kurangPerPesanan->filter()->count();
@endphp

<x-admin.kartu>
    <h2 class="mb-1 text-lg font-bold text-ink">Cek Akhir Berangkat Besok</h2>
    <p class="mb-4 text-xs leading-5 text-muted">H-1: pastikan lunas, guide menerima tugas, kendaraan dan hotel siap, serta info keberangkatan (H-2) sudah dikirim ke tamu.</p>

    @if ($pesanan->isEmpty())
        <p class="py-6 text-center text-xs text-muted">Tidak ada trip yang berangkat besok.</p>
    @else
        @if ($jumlahBelumSiap > 0)
            <x-admin.peringatan type="warning" class="mb-3">{{ $jumlahBelumSiap }} dari {{ $pesanan->count() }} trip besok belum siap berangkat.</x-admin.peringatan>
        @else
            <x-admin.peringatan type="success" class="mb-3">Semua {{ $pesanan->count() }} trip besok siap berangkat.</x-admin.peringatan>
        @endif

        <div class="space-y-2">
            @foreach ($pesanan as $item)
                @php $kurang = $kurangPerPesanan[$item->id]; @endphp
                <a href="{{ route('admin.pesanan.detail', $item) }}" class="block rounded-lg border p-3 text-sm hover:bg-page {{ $kurang ? 'border-status-dibatalkan-teks/30' : 'border-surface-border' }}">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="truncate font-medium text-ink">{{ $item->nama_lengkap }}</p>
                            <p class="truncate text-xs text-muted">{{ $item->nama_perjalanan }} · {{ $item->kode }}</p>
                        </div>
                        <span @class([
                            'shrink-0 rounded-full px-2 py-0.5 text-[11px] font-semibold',
                            'bg-status-completed-bg text-status-completed-teks' => ! $kurang,
                            'bg-status-dibatalkan-bg text-status-dibatalkan-teks' => $kurang,
                        ])>{{ $kurang ? 'Belum siap' : 'Siap berangkat' }}</span>
                    </div>
                    @if ($kurang)
                        <ul class="mt-2 space-y-0.5 text-xs text-status-dibatalkan-teks">
                            @foreach ($kurang as $hal)
                                <li class="flex items-center gap-1"><span class="material-symbols-outlined text-[14px]!" aria-hidden="true">error</span>{{ $hal }}</li>
                            @endforeach
                        </ul>
                    @endif
                </a>
            @endforeach
        </div>
    @endif
</x-admin.kartu>
