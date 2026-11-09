@php
    $statusTab = request('status', 'semua');
    $tabStatus = [
        'semua' => ['label' => 'Semua', 'badge' => $jumlah['semua']],
        'aktif' => ['label' => 'Aktif', 'badge' => $jumlah['aktif']],
        'nonaktif' => ['label' => 'Nonaktif', 'badge' => $jumlah['nonaktif']],
    ];
@endphp
<x-tata-letak.admin title="Hotel" group="Operasional">
    <x-admin.kepala-halaman title="Hotel" subtitle="Hotel rekanan untuk direkomendasikan dan dipesankan ke tamu yang belum punya penginapan.">
        <x-slot:actions>
            <x-admin.tombol as="a" href="{{ route('admin.hotel.buat') }}" icon="add">Tambah Hotel</x-admin.tombol>
        </x-slot:actions>
    </x-admin.kepala-halaman>

    @if ($errors->has('hotel'))
        <x-admin.peringatan type="danger" class="mb-6">{{ $errors->first('hotel') }}</x-admin.peringatan>
    @endif

    @if ($journeyTanpaHotel->isNotEmpty())
        <x-admin.peringatan type="warning" class="mb-6">Journey ini belum punya hotel aktif, jadi tamu belum melihat hotelnya: <strong>{{ $journeyTanpaHotel->implode(', ') }}</strong>. Tambahkan hotel bintang 4 dan centang journeynya di form hotel.</x-admin.peringatan>
    @endif

    <x-admin.tab class="mb-6">
        @foreach ($tabStatus as $nilai => $info)
            <x-admin.tab-item
                href="{{ route('admin.hotel.indeks', array_filter(['status' => $nilai, 'cari' => request('cari'), 'journey' => request('journey')])) }}"
                :active="$statusTab === $nilai"
                :badge="$info['badge']"
            >
                {{ $info['label'] }}
            </x-admin.tab-item>
        @endforeach
    </x-admin.tab>

    <x-admin.bilah-filter as="form" method="GET" action="{{ route('admin.hotel.indeks') }}" class="mb-6">
        <input type="hidden" name="status" value="{{ $statusTab }}" />

        <div class="relative flex-1">
            <span class="material-symbols-outlined pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 text-lg text-muted" aria-hidden="true">search</span>
            <input
                type="search"
                name="cari"
                value="{{ request('cari') }}"
                placeholder="Cari nama hotel atau area..."
                class="w-full rounded-full border border-surface-border bg-white py-2.5 ps-10 pe-4 text-sm text-ink placeholder:text-muted focus:border-hijau-aksi focus:outline-none focus:ring-1 focus:ring-hijau-aksi"
            />
        </div>

        <select name="journey" aria-label="Filter journey" class="rounded-full border border-surface-border bg-white py-2.5 ps-4 pe-10 text-sm text-ink">
            <option value="">Semua Journey</option>
            @foreach ($daftarJourney as $journey)
                <option value="{{ $journey->id }}" @selected((string) request('journey') === (string) $journey->id)>{{ \Illuminate\Support\Str::title(mb_strtolower($journey->nama)) }}</option>
            @endforeach
        </select>

        <x-admin.tombol type="submit" variant="secondary" icon="search">Cari</x-admin.tombol>
    </x-admin.bilah-filter>

    @if ($daftar->isEmpty())
        <x-admin.keadaan-kosong icon="hotel" judul="Belum ada hotel" keterangan="Tambahkan hotel rekanan agar bisa direkomendasikan dan dipesankan untuk tamu.">
            <x-slot:aksi>
                <x-admin.tombol as="a" href="{{ route('admin.hotel.buat') }}" icon="add">Tambah Hotel</x-admin.tombol>
            </x-slot:aksi>
        </x-admin.keadaan-kosong>
    @else
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($daftar as $hotel)
                <x-admin.hotel.kartu :hotel="$hotel" />
            @endforeach
        </div>
    @endif
</x-tata-letak.admin>
