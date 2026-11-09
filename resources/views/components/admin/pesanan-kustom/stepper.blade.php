@props(['pesananKustom'])

{{-- Stepper alur kustom "Hubungi – Susun – Kirim – Resmikan"; tampilannya sama dengan stepper status di detail pesanan biasa. --}}
@php
    $tahap = $pesananKustom->tahapAlur();
    $tidakJadi = $pesananKustom->status === \App\Enums\StatusPesananKustom::TidakJadi;
    $daftar = [1 => 'Hubungi tamu', 2 => 'Susun penawaran', 3 => 'Kirim penawaran', 4 => 'Resmikan pesanan'];
@endphp

<x-admin.kartu {{ $attributes }}>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        @foreach ($daftar as $nomor => $label)
            <div class="flex flex-1 items-center">
                <div class="flex items-center gap-2">
                    <span @class([
                        'flex h-7 w-7 items-center justify-center rounded-full text-xs font-semibold',
                        'bg-hijau-aksi text-white' => ! $tidakJadi && $nomor <= $tahap,
                        'bg-page text-muted' => $tidakJadi || $nomor > $tahap,
                    ])>
                        @if (! $tidakJadi && $nomor < $tahap)
                            <span class="material-symbols-outlined text-sm" aria-hidden="true">check</span>
                        @else
                            {{ $nomor }}
                        @endif
                    </span>
                    <span @class(['text-sm font-medium', 'text-ink' => ! $tidakJadi && $nomor <= $tahap, 'text-muted' => $tidakJadi || $nomor > $tahap])>{{ $label }}</span>
                </div>

                @if ($nomor < 4)
                    <div @class(['mx-3 hidden h-px min-w-6 flex-1 sm:block', 'bg-hijau-aksi' => ! $tidakJadi && $nomor < $tahap, 'bg-surface-border' => $tidakJadi || $nomor >= $tahap])></div>
                @endif
            </div>
        @endforeach
    </div>

    @if ($tahap === 5)
        <p class="mt-3 text-center text-xs font-medium text-status-completed-teks">Pesanan disetujui. Lanjutkan Konfirmasi, Pembayaran, Guide, dan Pengingat di Pesanan resmi seperti pesanan biasa.</p>
    @elseif ($tidakJadi)
        <p class="mt-3 text-center text-xs font-medium text-status-dibatalkan-teks">Permintaan ditutup (Tidak jadi). Buka kembali dari Aksi Cepat bila tamu berubah pikiran.</p>
    @endif
</x-admin.kartu>
