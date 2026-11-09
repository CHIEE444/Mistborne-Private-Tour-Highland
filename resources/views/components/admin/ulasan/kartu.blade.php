@props(['ulasan'])

@php
    $pesanan = $ulasan->pesanan;
    $inisial = collect(explode(' ', trim($pesanan?->nama_lengkap ?? '?')))->filter()->take(2)->map(fn ($k) => mb_strtoupper(mb_substr($k, 0, 1)))->implode('');
@endphp

<x-admin.kartu class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
    <div class="flex gap-3">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-admin-sidebar text-sm font-semibold text-white">
            {{ $inisial ?: '?' }}
        </span>

        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <p class="font-medium text-ink">{{ $pesanan?->nama_lengkap ?? 'Pesanan dihapus' }}</p>
                <span class="font-mono text-xs text-muted">{{ $pesanan?->kode }}</span>
                @if ($pesanan)
                    <span class="text-xs text-muted">&middot; {{ $pesanan->nama_perjalanan }} (Trip {{ $pesanan->tanggal_trip->translatedFormat('d M Y') }})</span>
                @endif
            </div>

            <div class="mt-1 flex items-center gap-1 text-emas">
                @for ($i = 1; $i <= 5; $i++)
                    <span class="material-symbols-outlined text-base" aria-hidden="true">{{ $i <= $ulasan->rating ? 'star' : 'star_border' }}</span>
                @endfor
                <span class="ms-1 text-xs text-muted">{{ number_format($ulasan->rating, 1) }} / 5.0</span>
            </div>

            <p class="mt-2 max-w-xl rounded-lg bg-page p-3 text-sm italic text-ink">&ldquo;{{ $ulasan->komentar }}&rdquo;</p>

            <div class="mt-2 flex flex-wrap items-center gap-2">
                @if ($ulasan->izin_tampil)
                    <span class="rounded-full bg-status-completed-bg px-2 py-0.5 text-[11px] font-medium text-status-completed-teks">Izin tampil: Ya</span>
                @else
                    <span class="rounded-full bg-status-dibatalkan-bg px-2 py-0.5 text-[11px] font-medium text-status-dibatalkan-teks">Izin tampil: Tidak (tamu menolak publikasi)</span>
                @endif

                @if ($ulasan->video_path)
                    <span class="rounded-full bg-status-payment-bg px-2 py-0.5 text-[11px] font-medium text-status-payment-teks">Video: Ada</span>
                @else
                    <span class="rounded-full bg-page px-2 py-0.5 text-[11px] font-medium text-muted">Video: Belum</span>
                @endif

                @if (! $ulasan->trashed())
                    <span @class([
                        'rounded-full px-2 py-0.5 text-[11px] font-medium',
                        'bg-status-completed-bg text-status-completed-teks' => $ulasan->disetujui,
                        'bg-status-hold-schedule-bg text-status-hold-schedule-teks' => ! $ulasan->disetujui,
                    ])>
                        {{ $ulasan->disetujui ? 'Disetujui' : 'Menunggu' }}
                    </span>
                @endif
            </div>

            <p class="mt-2 text-[11px] text-muted">Diterima: {{ $ulasan->created_at->translatedFormat('d M Y') }}</p>
        </div>
    </div>

    <div class="flex shrink-0 flex-wrap items-center gap-2">
        @if ($ulasan->trashed())
            <form method="POST" action="{{ route('admin.ulasan.pulihkan', $ulasan->id) }}">
                @csrf
                @method('PATCH')
                <x-admin.tombol type="submit" variant="secondary" size="sm" icon="restore">Pulihkan</x-admin.tombol>
            </form>
        @else
            @unless ($ulasan->disetujui)
                <form method="POST" action="{{ route('admin.ulasan.setujui', $ulasan) }}">
                    @csrf
                    @method('PATCH')
                    <x-admin.tombol type="submit" size="sm" icon="check">Setujui</x-admin.tombol>
                </form>
            @endunless

            <x-admin.tombol
                type="button"
                variant="secondary"
                size="sm"
                icon="upload"
                x-on:click="window.dispatchEvent(new CustomEvent('buka-modal', { detail: { nama: 'unggah-video-{{ $ulasan->id }}' } }))"
            >
                {{ $ulasan->video_path ? 'Ganti Video' : 'Unggah Video' }}
            </x-admin.tombol>

            <x-admin.tombol-ikon
                type="button"
                icon="visibility_off"
                variant="danger"
                label="Tolak/Sembunyikan"
                x-on:click="window.dispatchEvent(new CustomEvent('buka-modal', { detail: { nama: 'tolak-ulasan-{{ $ulasan->id }}' } }))"
            />
        @endif
    </div>
</x-admin.kartu>

@unless ($ulasan->trashed())
    <x-admin.ulasan.modal-unggah-video :ulasan="$ulasan" />
    <x-admin.ulasan.dialog-tolak :ulasan="$ulasan" />
@endunless
