@php
    $bulanSebelumnya = $bulan->copy()->subMonthNoOverflow()->format('Y-m');
    $bulanBerikutnya = $bulan->copy()->addMonthNoOverflow()->format('Y-m');
    $namaHari = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
@endphp
<x-tata-letak.admin title="{{ __('admin.sidebar.kalender_trip') }}" group="{{ __('admin.sidebar.grup_operasional') }}">
    <x-admin.kepala-halaman
        :title="__('admin.sidebar.kalender_trip')"
        subtitle="Pantau jadwal keberangkatan, ketersediaan kuota, dan pesanan per hari. Perubahan jadwal dilakukan melalui modul Pesanan."
    >
        <x-slot:actions>
            {{-- Rekap PDF bulan yang sedang dibuka (pengganti cetak manifest harian), ikut filter journey. --}}
            <x-admin.tombol as="a" href="{{ route('admin.kalender-trip.rekap-pdf', array_filter(['bulan' => $bulan->format('Y-m'), 'journey' => request('journey')])) }}" size="sm" icon="download">
                Rekap PDF {{ $bulan->translatedFormat('F Y') }}
            </x-admin.tombol>
            <x-admin.tombol as="a" href="{{ route('admin.kalender-trip.indeks', array_filter(['journey' => request('journey'), 'tanggal' => now()->toDateString()])) }}" variant="secondary" size="sm" icon="today">
                Hari Ini
            </x-admin.tombol>

            <div class="flex items-center gap-1 rounded-full border border-surface-border bg-white p-1">
                <x-admin.tombol-ikon as="a" href="{{ route('admin.kalender-trip.indeks', array_filter(['bulan' => $bulanSebelumnya, 'journey' => request('journey')])) }}" icon="chevron_left" label="Bulan sebelumnya" />
                <span class="min-w-[132px] px-2 text-center text-sm font-semibold capitalize text-ink">{{ $bulan->translatedFormat('F Y') }}</span>
                <x-admin.tombol-ikon as="a" href="{{ route('admin.kalender-trip.indeks', array_filter(['bulan' => $bulanBerikutnya, 'journey' => request('journey')])) }}" icon="chevron_right" label="Bulan berikutnya" />
            </div>
        </x-slot:actions>
    </x-admin.kepala-halaman>

    <x-admin.bilah-filter as="form" method="GET" action="{{ route('admin.kalender-trip.indeks') }}" class="mb-6">
        <input type="hidden" name="bulan" value="{{ $bulan->format('Y-m') }}" />
        <label for="filter-journey" class="text-sm font-medium text-ink">Journey</label>
        <select id="filter-journey" name="journey" class="min-w-0 flex-1 rounded-lg border border-surface-border bg-white px-4 py-2.5 text-sm text-ink focus:border-hijau-aksi focus:outline-none focus:ring-1 focus:ring-hijau-aksi sm:max-w-md">
            <option value="">Semua Journey</option>
            @foreach ($daftarJourney as $journey)
                <option value="{{ $journey->id }}" @selected((string) request('journey') === (string) $journey->id)>{{ $journey->nama }}</option>
            @endforeach
        </select>
        <x-admin.tombol type="submit" variant="secondary" icon="filter_alt">Terapkan</x-admin.tombol>
        @if (request()->filled('journey'))
            <x-admin.tombol as="a" href="{{ route('admin.kalender-trip.indeks', ['bulan' => $bulan->format('Y-m')]) }}" variant="reset" icon="restart_alt">Reset</x-admin.tombol>
        @endif
    </x-admin.bilah-filter>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            {{-- overflow-x-auto + min-w: di layar sempit, 7 kolom discroll ke samping
                 daripada dipaksa muat (sel jadi terlalu sempit untuk disentuh/dibaca). --}}
            <div class="overflow-x-auto rounded-admin-kartu border border-surface-border bg-white shadow-admin-kartu">
                <div class="min-w-[560px]">
                    <div class="grid grid-cols-7 border-b border-surface-border bg-page text-center text-[11px] font-semibold uppercase tracking-wide text-muted sm:text-xs">
                        @foreach ($namaHari as $hari)
                            <div class="border-e border-surface-border py-2">{{ $hari }}</div>
                        @endforeach
                    </div>

                    @foreach ($minggu as $pekan)
                        <div class="grid grid-cols-7">
                            @foreach ($pekan as $hari)
                                <x-admin.kalender-trip.sel-hari :hari="$hari" />
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-4 rounded-admin-kartu border border-surface-border bg-white p-4 shadow-admin-kartu">
                <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-muted">Keterangan Kuota</p>
                <p class="mb-3 text-[11px] text-muted">Angka di pojok tanggal (mis. 2/3) = trip yang sedang memegang guide pada hari itu dibanding jumlah guide siap. 1 guide memegang 1 trip sampai trip selesai, jadi saat semua guide bertugas tidak ada journey yang bisa dipesan di hari itu. Kuota tiap journey diatur di Journey → Tanggal Tersedia.</p>
                <div class="flex flex-wrap gap-x-5 gap-y-2 text-xs text-ink sm:text-sm">
                    <span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-status-completed-teks"></span>Tersedia · lebih dari 1 guide bebas</span>
                    <span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-status-payment-teks"></span>Hampir penuh · 1 guide bebas</span>
                    <span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-status-dibatalkan-teks"></span>Penuh · semua guide bertugas</span>
                    <span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-gray-300"></span>Diblokir</span>
                    <span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-sage"></span>Hari ini</span>
                </div>
            </div>
        </div>

        @if ($tanggalDipilih)
            <div>
                <x-admin.kalender-trip.panel-hari :tanggal="$tanggalDipilih" :pesanan="$pesananHariDipilih" :journey-terpilih="$journeyTerpilih" />
            </div>
        @endif
    </div>
</x-tata-letak.admin>
