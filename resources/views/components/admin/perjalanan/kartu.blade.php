@props(['perjalanan'])

@php
    $jadwalBerikutnya = $perjalanan->jadwalBerikutnya();
@endphp

<x-admin.kartu :padding="false" class="flex flex-col overflow-hidden">
    <div class="relative aspect-[16/10] w-full bg-page">
        <img src="{{ $perjalanan->sampul() }}" alt="{{ $perjalanan->nama }}" class="h-full w-full object-cover" />

        @if ($perjalanan->rating_label)
            <span class="absolute end-3 top-3 flex items-center gap-1 rounded-full bg-white/95 px-2.5 py-1 text-xs font-semibold text-ink shadow">
                <span class="material-symbols-outlined text-sm text-emas" aria-hidden="true">star</span>
                {{ $perjalanan->rating_label }}
            </span>
        @endif

        @if ($perjalanan->tampil_beranda)
            <span class="absolute bottom-3 start-3 flex items-center gap-1 rounded-full bg-admin-sidebar/90 px-2.5 py-1 text-[11px] font-medium text-white">
                <span class="h-1.5 w-1.5 rounded-full bg-emas"></span>
                Tampil di beranda
            </span>
        @endif
    </div>

    <div class="flex flex-1 flex-col gap-3 p-5">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-wide text-muted">
                {{ \App\Models\Perjalanan::labelRegion($perjalanan->region) ?? 'Tanpa region' }}
            </p>
            <h3 class="mt-1 font-semibold text-ink">{{ $perjalanan->nama }}</h3>
            <p class="mt-1 line-clamp-2 text-sm text-muted">{{ $perjalanan->tagline }}</p>
        </div>

        <div class="grid grid-cols-3 gap-2 rounded-lg bg-page p-3 text-center text-xs">
            <div>
                <p class="text-muted">Durasi</p>
                <p class="font-semibold text-ink">{{ $perjalanan->labelDurasi() }}</p>
            </div>
            <div>
                <p class="text-muted">Kapasitas</p>
                <p class="font-semibold text-ink">Maks. {{ $perjalanan->maks_orang }} org</p>
            </div>
            <div>
                <p class="text-muted">Mulai Dari</p>
                <p class="font-semibold text-ink">{{ $perjalanan->hargaFormat() ?? 'Belum ada' }}</p>
            </div>
        </div>

        <div class="flex items-center justify-between rounded-lg border border-surface-border px-3 py-2 text-xs">
            @if ($jadwalBerikutnya)
                <span class="flex items-center gap-1 text-muted">
                    <span class="material-symbols-outlined text-sm" aria-hidden="true">event</span>
                    Trip Berikutnya: {{ $jadwalBerikutnya->tanggal->translatedFormat('d M Y') }}
                </span>
                <span @class([
                    'rounded-full px-2 py-0.5 font-semibold',
                    'bg-status-dibatalkan-bg text-status-dibatalkan-teks' => $jadwalBerikutnya->penuh(),
                    'bg-status-payment-bg text-status-payment-teks' => ! $jadwalBerikutnya->penuh() && $jadwalBerikutnya->terisi() === $jadwalBerikutnya->kuotaEfektif() - 1,
                    'bg-status-completed-bg text-status-completed-teks' => ! $jadwalBerikutnya->penuh() && $jadwalBerikutnya->terisi() < $jadwalBerikutnya->kuotaEfektif() - 1,
                ])>
                    {{ $jadwalBerikutnya->penuh() ? 'Penuh' : "Terisi {$jadwalBerikutnya->terisi()}/{$jadwalBerikutnya->kuotaEfektif()}" }}
                </span>
            @else
                <span class="flex items-center gap-1 text-muted">
                    <span class="material-symbols-outlined text-sm" aria-hidden="true">event_busy</span>
                    Jadwal menyusul — belum ada tanggal tersedia
                </span>
            @endif
        </div>

        <div class="mt-auto flex flex-wrap items-center gap-2 border-t border-surface-border pt-3 text-sm">
            <x-admin.tombol as="a" href="{{ route('admin.perjalanan.ubah', $perjalanan) }}" variant="secondary" size="sm" icon="edit">Edit</x-admin.tombol>
            <x-admin.tombol as="a" href="{{ route('admin.perjalanan.tanggal.indeks', $perjalanan) }}" variant="secondary" size="sm" icon="calendar_month">Tanggal</x-admin.tombol>
            <x-admin.tombol-ikon as="a" href="{{ route('journeys.show', ['slug' => $perjalanan->slug]) }}" target="_blank" icon="visibility" label="Lihat di situs publik" />
            <x-admin.tombol
                type="button"
                variant="danger"
                size="sm"
                icon="delete"
                class="ms-auto"
                x-on:click="window.dispatchEvent(new CustomEvent('buka-modal', { detail: { nama: 'hapus-perjalanan-{{ $perjalanan->id }}' } }))"
            >
                Hapus
            </x-admin.tombol>
        </div>
    </div>

    <x-admin.perjalanan.dialog-hapus :perjalanan="$perjalanan" />
</x-admin.kartu>
