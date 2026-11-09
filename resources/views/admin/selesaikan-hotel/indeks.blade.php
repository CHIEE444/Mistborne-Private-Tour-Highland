@php
    $kelompok = \App\Http\Controllers\Admin\SelesaikanHotelController::KELOMPOK;
    $gayaKelompok = [
        'belum' => ['label' => 'Belum diurus', 'kelas' => 'bg-status-dibatalkan-bg text-status-dibatalkan-teks', 'ikon' => 'error'],
        'sedang' => ['label' => 'Sedang diurus', 'kelas' => 'bg-status-payment-bg text-status-payment-teks', 'ikon' => 'schedule'],
        'dibatalkan' => ['label' => 'Hotel membatalkan', 'kelas' => 'bg-status-dibatalkan-bg text-status-dibatalkan-teks', 'ikon' => 'cancel'],
    ];
@endphp
<x-tata-letak.admin title="Selesaikan Hotel" group="{{ __('admin.sidebar.grup_operasional') }}">
    <x-admin.kepala-halaman
        title="Selesaikan Hotel"
        subtitle="Semua reservasi hotel tamu yang sudah bayar dan belum tuntas. Trip terdekat paling atas."
    >
        <x-slot:actions>
            <x-admin.tombol as="a" href="{{ route('admin.dashboard.indeks') }}#selesaikan-hotel" variant="secondary" icon="grid_view">Ke Dashboard</x-admin.tombol>
        </x-slot:actions>
    </x-admin.kepala-halaman>

    <div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['kunci' => 'semua', 'label' => 'Belum tuntas', 'ikon' => 'hotel', 'warna' => 'bg-page text-ink'],
            ['kunci' => 'belum', 'label' => 'Belum diurus sama sekali', 'ikon' => 'error', 'warna' => 'bg-status-dibatalkan-bg text-status-dibatalkan-teks'],
            ['kunci' => 'sedang', 'label' => 'Sedang diurus', 'ikon' => 'schedule', 'warna' => 'bg-status-payment-bg text-status-payment-teks'],
            ['kunci' => 'mendesak', 'label' => 'Trip ≤ 7 hari lagi', 'ikon' => 'event_busy', 'warna' => 'bg-amber-700 text-white'],
        ] as $kartu)
            <a href="{{ route('admin.selesaikan-hotel.indeks', ['kelompok' => $kartu['kunci']]) }}" @class([
                'flex items-center gap-4 rounded-admin-kartu border bg-white p-4 shadow-admin-kartu transition hover:border-admin-sidebar/40',
                'border-admin-sidebar ring-1 ring-admin-sidebar' => $kelompokAktif === $kartu['kunci'],
                'border-surface-border' => $kelompokAktif !== $kartu['kunci'],
            ])>
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $kartu['warna'] }}"><span class="material-symbols-outlined" aria-hidden="true">{{ $kartu['ikon'] }}</span></span>
                <span>
                    <span class="block text-xs text-muted">{{ $kartu['label'] }}</span>
                    <span class="mt-0.5 block text-xl font-semibold tabular-nums text-ink">{{ $jumlah[$kartu['kunci']] }}</span>
                </span>
            </a>
        @endforeach
    </div>

    <x-admin.tab class="mb-4 overflow-x-auto">
        @foreach ($kelompok as $kunci => $label)
            <x-admin.tab-item
                href="{{ route('admin.selesaikan-hotel.indeks', array_filter(['kelompok' => $kunci === 'semua' ? null : $kunci, 'cari' => request('cari')])) }}"
                :active="$kelompokAktif === $kunci"
                :badge="$jumlah[$kunci]"
                class="whitespace-nowrap"
            >{{ $label }}</x-admin.tab-item>
        @endforeach
    </x-admin.tab>

    <x-admin.bilah-filter as="form" method="GET" action="{{ route('admin.selesaikan-hotel.indeks') }}" class="mb-6">
        @if ($kelompokAktif !== 'semua')
            <input type="hidden" name="kelompok" value="{{ $kelompokAktif }}" />
        @endif
        <div class="relative flex-1">
            <span class="material-symbols-outlined pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 text-lg text-muted" aria-hidden="true">search</span>
            <input type="search" name="cari" value="{{ request('cari') }}" maxlength="100" placeholder="Cari kode, nama tamu, journey, atau hotel..." class="w-full rounded-full border border-surface-border bg-white py-2.5 ps-10 pe-4 text-sm text-ink placeholder:text-muted focus:border-hijau-aksi focus:outline-none focus:ring-1 focus:ring-hijau-aksi" />
        </div>
        <x-admin.tombol type="submit" variant="secondary" icon="search">Cari</x-admin.tombol>
        @if (request()->filled('cari'))
            <x-admin.tombol as="a" href="{{ route('admin.selesaikan-hotel.indeks', array_filter(['kelompok' => $kelompokAktif === 'semua' ? null : $kelompokAktif])) }}" variant="reset" icon="restart_alt">Reset</x-admin.tombol>
        @endif
    </x-admin.bilah-filter>

    @if ($daftar->isEmpty())
        <x-admin.keadaan-kosong
            icon="task_alt"
            :judul="$jumlah['semua'] === 0 ? 'Semua hotel sudah selesai diurus' : 'Tidak ada yang cocok'"
            :keterangan="$jumlah['semua'] === 0 ? 'Reservasi hotel tamu yang sudah bayar dan belum tuntas akan muncul di sini.' : 'Coba kata kunci lain atau pilih tab Semua.'"
        />
    @else
        <x-admin.kartu :padding="false" class="overflow-hidden">
            <div class="overflow-x-auto">
                <x-admin.tabel :kolom="['Kode & tamu', 'Trip', 'Hotel', 'Progres reservasi', '']">
                    @foreach ($daftar as $baris)
                        @php
                            $item = $baris['pesanan'];
                            $persen = $baris['ringkasan']['total'] > 0 ? (int) round($baris['ringkasan']['selesai'] / $baris['ringkasan']['total'] * 100) : 0;
                            $gaya = $gayaKelompok[$baris['kelompok']];
                        @endphp
                        <x-admin.baris-tabel>
                            <x-admin.kolom-tabel>
                                <a href="{{ route('admin.pesanan.detail', $item) }}" class="font-mono text-xs font-semibold text-ink hover:text-hijau-aksi">{{ $item->kode }}</a>
                                <span class="block font-medium text-ink">{{ $item->nama_lengkap }}</span>
                                <span class="block text-xs text-muted">{{ $item->jumlah_wisatawan }} tamu · {{ $item->status_penginapan?->label() ?? 'Penginapan' }}</span>
                            </x-admin.kolom-tabel>
                            <x-admin.kolom-tabel>
                                <span class="block text-sm text-ink">{{ $item->nama_perjalanan }}</span>
                                <span class="block text-xs text-muted">{{ $item->tanggal_trip->translatedFormat('d M Y') }}</span>
                                <span @class([
                                    'mt-1 inline-block rounded-full px-2 py-0.5 text-[11px] font-semibold',
                                    'bg-amber-700 text-white' => $baris['mendesak'],
                                    'bg-page text-muted' => ! $baris['mendesak'],
                                ])>{{ $item->labelJarakTrip() }}</span>
                            </x-admin.kolom-tabel>
                            <x-admin.kolom-tabel>
                                @if ($item->hotel)
                                    <span class="block text-sm text-ink">{{ $item->hotel->nama }}</span>
                                    @if ($item->status_reservasi_hotel)
                                        <span class="block text-xs text-muted">{{ $item->status_reservasi_hotel->label() }}</span>
                                    @endif
                                @else
                                    <span class="text-xs font-medium text-status-dibatalkan-teks">Belum pilih hotel</span>
                                @endif
                            </x-admin.kolom-tabel>
                            <x-admin.kolom-tabel class="min-w-56">
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $gaya['kelas'] }}">
                                    <span class="material-symbols-outlined text-[14px]!" aria-hidden="true">{{ $gaya['ikon'] }}</span>{{ $gaya['label'] }}
                                </span>
                                <div class="mt-2 flex items-center gap-2">
                                    <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-page" role="progressbar" aria-valuemin="0" aria-valuemax="{{ $baris['ringkasan']['total'] }}" aria-valuenow="{{ $baris['ringkasan']['selesai'] }}" aria-label="Tahap reservasi selesai">
                                        <div class="h-full rounded-full bg-hijau-aksi" style="width: {{ $persen }}%"></div>
                                    </div>
                                    <span class="text-[11px] font-semibold tabular-nums text-muted">{{ $baris['ringkasan']['selesai'] }}/{{ $baris['ringkasan']['total'] }}</span>
                                </div>
                                @if ($baris['kelompok'] === 'dibatalkan')
                                    <span class="mt-1 block text-xs text-status-dibatalkan-teks">Hotel membatalkan, pilih hotel lain.</span>
                                @elseif ($baris['berikutnya'])
                                    <span class="mt-1 block text-xs text-muted">Berikutnya: {{ $baris['berikutnya']['nomor'] }}. {{ $baris['berikutnya']['judul'] }}</span>
                                @endif
                            </x-admin.kolom-tabel>
                            <x-admin.kolom-tabel class="text-end">
                                <x-admin.tombol as="a" href="{{ route('admin.pesanan.detail', $item) }}#reservasi-hotel" size="sm" icon="arrow_forward">Selesaikan</x-admin.tombol>
                            </x-admin.kolom-tabel>
                        </x-admin.baris-tabel>
                    @endforeach
                </x-admin.tabel>
            </div>
        </x-admin.kartu>
    @endif
</x-tata-letak.admin>
