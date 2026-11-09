@props(['hari'])

@php
    $tanggal = $hari['tanggal'];
    $iniHariIni = $tanggal->isToday();
    $dipilih = request('tanggal') === $tanggal->toDateString();

    $adaIsi = $hari['terisi'] > 0 || $hari['jadwal']->isNotEmpty();
    $berlangsung = $hari['berlangsung'] ?? 0;
    $warnaRingkas = [
        'tersedia' => 'bg-status-completed-bg text-status-completed-teks',
        'hampir_penuh' => 'bg-status-payment-bg text-status-payment-teks',
        'penuh' => 'bg-status-dibatalkan-bg text-status-dibatalkan-teks',
        'diblokir' => 'bg-gray-100 text-muted',
    ];
    $labelStatus = ['tersedia' => 'Tersedia', 'hampir_penuh' => 'Hampir penuh', 'penuh' => 'Penuh', 'diblokir' => 'Diblokir'];

    $warnaTag = [
        'tersedia' => 'bg-status-completed-bg text-status-completed-teks',
        'hampir_penuh' => 'bg-status-payment-bg text-status-payment-teks',
        'penuh' => 'bg-status-dibatalkan-bg text-status-dibatalkan-teks',
        'diblokir' => 'bg-gray-100 text-muted line-through',
    ];
@endphp

<a
    href="{{ route('admin.kalender-trip.indeks', array_merge(request()->only('bulan', 'journey'), ['tanggal' => $tanggal->toDateString()])) }}"
    aria-label="{{ $tanggal->translatedFormat('l, d F Y') }}{{ $adaIsi ? ', '.$hari['terisi'].' trip berjalan dari '.$hari['kuota'].' guide siap, '.$labelStatus[$hari['status']] : ', belum ada jadwal' }}{{ $berlangsung ? ', '.$berlangsung.' trip sedang berlangsung' : '' }}"
    @if ($dipilih) aria-current="date" @endif
    @class([
        'flex min-h-[96px] flex-col gap-1 border-b border-e border-surface-border p-2 text-start transition-colors hover:bg-page focus-visible:z-10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-hijau-aksi sm:min-h-[108px] sm:p-2.5',
        'bg-page/60 text-muted' => ! $hari['dalamBulanIni'],
        'ring-2 ring-inset ring-admin-sidebar' => $dipilih,
    ])
>
    <div class="flex items-center justify-between gap-1">
        <span @class([
            'flex h-6 w-6 items-center justify-center rounded-full text-xs font-semibold',
            'bg-admin-sidebar text-white' => $iniHariIni,
            'text-ink' => ! $iniHariIni && $hari['dalamBulanIni'],
            'text-muted' => ! $iniHariIni && ! $hari['dalamBulanIni'],
        ])>
            {{ $tanggal->day }}
        </span>
        @if ($adaIsi)
            <span title="{{ $hari['terisi'] }} trip berjalan dari {{ $hari['kuota'] }} guide siap (1 guide memegang 1 trip sampai selesai) · {{ $labelStatus[$hari['status']] }}" class="rounded-full px-1.5 py-0.5 text-[10px] font-semibold tabular-nums {{ $warnaRingkas[$hari['status']] }}">{{ $hari['terisi'] }}/{{ $hari['kuota'] }}</span>
        @endif
    </div>

    <div class="flex flex-col gap-0.5">
        @foreach ($hari['jadwal']->take(3) as $jadwal)
            @php
                // Saat semua guide bertugas, journey yang kuotanya masih ada pun tidak bisa dipesan di hari itu.
                $guideHabis = $hari['status'] === 'penuh' && $jadwal->statusKuota() !== 'diblokir';
                $statusTag = $guideHabis ? 'penuh' : $jadwal->statusKuota();
            @endphp
            <span title="{{ $jadwal->perjalanan?->nama }} · {{ $guideHabis ? 'Penuh, semua guide bertugas' : str_replace('_', ' ', ucfirst($jadwal->statusKuota())) }} (kuota journey: terisi {{ $jadwal->terisi() }} dari {{ $jadwal->kuotaEfektif() }})" class="truncate rounded px-1.5 py-0.5 text-[10px] font-medium {{ $warnaTag[$statusTag] }}">
                {{ \Illuminate\Support\Str::limit($jadwal->perjalanan?->nama, 10, '') }}
            </span>
        @endforeach

        @if ($berlangsung > 0)
            <span title="{{ $berlangsung }} trip berangkat di hari sebelumnya dan masih berlangsung pada tanggal ini" class="truncate rounded border border-dashed border-hijau-aksi bg-[#EAF3EE] px-1.5 py-0.5 text-[10px] font-medium text-admin-sidebar">{{ $berlangsung }} trip berjalan</span>
        @endif

        @if ($hari['jadwal']->count() > 3)
            <span class="text-[10px] text-muted" title="{{ $hari['jadwal']->skip(3)->map(fn ($j) => $j->perjalanan?->nama)->implode(', ') }}">+{{ $hari['jadwal']->count() - 3 }} journey lain</span>
        @endif
    </div>
</a>
