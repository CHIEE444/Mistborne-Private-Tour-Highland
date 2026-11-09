@php
    $paket = $jenis === \App\Models\Perjalanan::JENIS_PAKET;
    $judulHalaman = $paket ? 'Paket Wisata' : __('admin.sidebar.perjalanan');
    $namaJenis = $paket ? 'Paket' : 'Journey';
@endphp
<x-tata-letak.admin :title="$judulHalaman" group="{{ __('admin.sidebar.grup_konten') }}">
    <x-admin.kepala-halaman
        :title="$judulHalaman"
        :subtitle="$paket ? 'Kelola Paket Relaksasi Alam di beranda: halaman detail, itinerary, harga, jadwal tanggal, dan foto. Dipesan lewat alur booking yang sama dengan journey.' : 'Kelola paket destinasi highland ekspedisi dan ketersediaan kuota grup'"
    >
        <x-slot:actions>
            <x-admin.tombol as="a" href="{{ route('admin.perjalanan.buat', ['jenis' => $jenis]) }}" icon="add">Tambah {{ $namaJenis }}</x-admin.tombol>
        </x-slot:actions>
    </x-admin.kepala-halaman>

    <x-admin.bilah-filter as="form" method="GET" action="{{ route('admin.perjalanan.indeks') }}" class="mb-6">
        <input type="hidden" name="jenis" value="{{ $jenis }}" />
        <div class="relative flex-1">
            <span class="material-symbols-outlined pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 text-lg text-muted" aria-hidden="true">search</span>
            <input
                type="search"
                name="cari"
                value="{{ request('cari') }}"
                placeholder="Cari {{ strtolower($namaJenis) }} atau destinasi..."
                class="w-full rounded-full border border-surface-border bg-white py-2.5 ps-10 pe-4 text-sm text-ink placeholder:text-muted focus:border-hijau-aksi focus:outline-none focus:ring-1 focus:ring-hijau-aksi"
            />
        </div>

        <select name="region" class="rounded-full border border-surface-border bg-white py-2.5 ps-4 pe-10 text-sm text-ink">
            <option value="">Semua Region</option>
            @foreach ($daftarRegion as $slug => $label)
                <option value="{{ $slug }}" @selected(request('region') === $slug)>{{ $label }}</option>
            @endforeach
        </select>

        <select name="beranda" class="rounded-full border border-surface-border bg-white py-2.5 ps-4 pe-10 text-sm text-ink">
            <option value="">Beranda: Semua</option>
            <option value="ya" @selected(request('beranda') === 'ya')>Tampil di Beranda</option>
            <option value="tidak" @selected(request('beranda') === 'tidak')>Tidak Tampil</option>
        </select>

        <x-admin.tombol type="submit" variant="secondary" icon="search">Cari</x-admin.tombol>

        @if (request()->anyFilled(['cari', 'region', 'beranda']))
            <x-admin.tombol as="a" href="{{ route('admin.perjalanan.indeks', ['jenis' => $jenis]) }}" variant="reset" icon="restart_alt">Reset</x-admin.tombol>
        @endif
    </x-admin.bilah-filter>

    @if ($perjalanan->isEmpty())
        <x-admin.keadaan-kosong
            icon="explore"
            :judul="'Belum ada '.strtolower($namaJenis)"
            :keterangan="'Tambahkan '.strtolower($namaJenis).' pertama Anda untuk mulai menerima pesanan.'"
        >
            <x-slot:aksi>
                <x-admin.tombol as="a" href="{{ route('admin.perjalanan.buat', ['jenis' => $jenis]) }}" icon="add">Tambah {{ $namaJenis }}</x-admin.tombol>
            </x-slot:aksi>
        </x-admin.keadaan-kosong>
    @else
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($perjalanan as $item)
                <x-admin.perjalanan.kartu :perjalanan="$item" />
            @endforeach
        </div>

        <x-admin.paginasi :paginator="$perjalanan" class="mt-6" />
    @endif
</x-tata-letak.admin>
