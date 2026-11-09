@props(['perjalanan', 'tanggal', 'jumlahPesanan' => 0])

{{-- Dialog konfirmasi hapus satu tanggal keberangkatan (pengganti confirm() bawaan browser).
     Tanggal yang sudah punya pesanan journey ini ditolak server, jadi dialog langsung menjelaskannya dan menawarkan blokir. --}}
@php
    $lewat = $tanggal->tanggal->lt(now()->startOfDay());
    $bisaDihapus = $jumlahPesanan === 0;
    $statusLabel = match (true) {
        $lewat => 'Sudah lewat',
        $tanggal->diblok => 'Diblokir',
        $tanggal->penuh() => 'Penuh',
        default => 'Tersedia',
    };
@endphp

<x-admin.modal :nama="'hapus-tanggal-'.$tanggal->id" size="sm">
    <x-slot:header>
        <div class="flex items-center gap-3">
            <span @class([
                'flex h-11 w-11 shrink-0 items-center justify-center rounded-full ring-4',
                'bg-status-dibatalkan-bg text-status-dibatalkan-teks ring-status-dibatalkan-bg/50' => $bisaDihapus,
                'bg-status-payment-bg text-status-payment-teks ring-status-payment-bg/50' => ! $bisaDihapus,
            ])>
                <span class="material-symbols-outlined" aria-hidden="true">{{ $bisaDihapus ? 'event_busy' : 'lock' }}</span>
            </span>
            <div>
                <p class="font-semibold text-ink">{{ $bisaDihapus ? 'Hapus tanggal keberangkatan?' : 'Tanggal ini tidak bisa dihapus' }}</p>
                <p class="mt-0.5 text-xs text-muted">{{ $perjalanan->nama }}</p>
            </div>
        </div>
    </x-slot:header>

    <div class="flex items-stretch overflow-hidden rounded-xl border border-surface-border">
        <div class="flex w-20 shrink-0 flex-col items-center justify-center bg-admin-sidebar px-2 py-3 text-white">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-emas">{{ $tanggal->tanggal->translatedFormat('M') }}</span>
            <span class="text-3xl font-semibold leading-none tabular-nums">{{ $tanggal->tanggal->format('d') }}</span>
            <span class="mt-1 text-[11px] text-white/70 tabular-nums">{{ $tanggal->tanggal->format('Y') }}</span>
        </div>
        <div class="min-w-0 flex-1 space-y-2 p-3 text-sm">
            <p class="font-semibold text-ink">{{ $tanggal->tanggal->translatedFormat('l, d F Y') }}</p>
            <div class="flex flex-wrap gap-2 text-xs">
                <span class="inline-flex items-center gap-1 rounded-full bg-page px-2.5 py-1 text-muted">
                    <span class="material-symbols-outlined text-[14px]!" aria-hidden="true">groups</span>
                    Terisi {{ $tanggal->terisi() }}/{{ $tanggal->kuotaEfektif() }} grup
                </span>
                <span @class([
                    'rounded-full px-2.5 py-1 font-semibold',
                    'bg-page text-muted' => $lewat,
                    'bg-status-dibatalkan-bg text-status-dibatalkan-teks' => ! $lewat && $tanggal->diblok,
                    'bg-status-payment-bg text-status-payment-teks' => ! $lewat && ! $tanggal->diblok && $tanggal->penuh(),
                    'bg-status-completed-bg text-status-completed-teks' => ! $lewat && ! $tanggal->diblok && ! $tanggal->penuh(),
                ])>{{ $statusLabel }}</span>
            </div>
        </div>
    </div>

    @if ($bisaDihapus)
        <ul class="mt-4 space-y-2 text-sm text-muted">
            <li class="flex gap-2"><span class="material-symbols-outlined text-[18px]! text-status-dibatalkan-teks" aria-hidden="true">remove_circle</span>Tanggal ini hilang dari pilihan tanggal di situs publik, jadi tamu tidak bisa memesannya lagi.</li>
            <li class="flex gap-2"><span class="material-symbols-outlined text-[18px]! text-muted" aria-hidden="true">info</span>Belum ada pesanan di tanggal ini. Bila perlu, tanggal bisa ditambahkan lagi kapan saja.</li>
        </ul>
    @else
        <div class="mt-4 rounded-xl border border-status-payment-teks/25 bg-status-payment-bg p-3 text-sm leading-6 text-status-payment-teks">
            <p class="font-semibold">Sudah ada {{ $jumlahPesanan }} pesanan di tanggal ini.</p>
            <p>Data pesanan harus tetap utuh, jadi tanggalnya tidak boleh dihapus.
                @if (! $lewat && ! $tanggal->diblok)
                    Untuk menutup pemesanan baru, pakai tombol <span class="font-semibold">Blokir tanggal</span> (ikon gembok) paling lambat H-14.
                @endif
            </p>
        </div>
    @endif

    <x-slot:footer>
        <x-admin.tombol variant="secondary" x-on:click="tutup()">{{ $bisaDihapus ? 'Batal' : 'Mengerti' }}</x-admin.tombol>
        @if ($bisaDihapus)
            <form method="POST" action="{{ route('admin.perjalanan.tanggal.hapus', [$perjalanan, $tanggal]) }}">
                @csrf
                @method('DELETE')
                <x-admin.tombol type="submit" variant="danger" icon="delete">Ya, hapus tanggal</x-admin.tombol>
            </form>
        @endif
    </x-slot:footer>
</x-admin.modal>
