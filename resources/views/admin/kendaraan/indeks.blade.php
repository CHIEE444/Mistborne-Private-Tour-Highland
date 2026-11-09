@php
    $statusTab = request('status', 'semua');
    $tabStatus = [
        'semua' => ['label' => 'Semua', 'badge' => $jumlah['semua']],
        'siap' => ['label' => 'Siap jalan', 'badge' => $jumlah['siap']],
        'servis' => ['label' => 'Dalam servis', 'badge' => $jumlah['servis']],
        'nonaktif' => ['label' => 'Nonaktif', 'badge' => $jumlah['nonaktif']],
    ];
@endphp
<x-tata-letak.admin title="Kendaraan" group="Operasional">
    <x-admin.kepala-halaman title="Kendaraan" subtitle="Kelola armada MISTBORNE: data, kapasitas, dan status tiap kendaraan.">
        <x-slot:actions>
            <x-admin.tombol as="a" href="{{ route('admin.kendaraan.buat') }}" icon="add">Tambah Kendaraan</x-admin.tombol>
        </x-slot:actions>
    </x-admin.kepala-halaman>

    @if (session('peringatan'))
        <x-admin.peringatan type="warning" class="mb-6">{{ session('peringatan') }}</x-admin.peringatan>
    @endif

    @if ($errors->has('kendaraan'))
        <x-admin.peringatan type="danger" class="mb-6">{{ $errors->first('kendaraan') }}</x-admin.peringatan>
    @endif

    <x-admin.tab class="mb-6">
        @foreach ($tabStatus as $nilai => $info)
            <x-admin.tab-item
                href="{{ route('admin.kendaraan.indeks', ['status' => $nilai, 'cari' => request('cari')]) }}"
                :active="$statusTab === $nilai"
                :badge="$info['badge']"
            >
                {{ $info['label'] }}
            </x-admin.tab-item>
        @endforeach
    </x-admin.tab>

    <x-admin.bilah-filter as="form" method="GET" action="{{ route('admin.kendaraan.indeks') }}" class="mb-6">
        <input type="hidden" name="status" value="{{ $statusTab }}" />

        <div class="relative flex-1">
            <span class="material-symbols-outlined pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 text-lg text-muted" aria-hidden="true">search</span>
            <input
                type="search"
                name="cari"
                value="{{ request('cari') }}"
                placeholder="Cari nama atau plat nomor..."
                class="w-full rounded-full border border-surface-border bg-white py-2.5 ps-10 pe-4 text-sm text-ink placeholder:text-muted focus:border-hijau-aksi focus:outline-none focus:ring-1 focus:ring-hijau-aksi"
            />
        </div>

        <x-admin.tombol type="submit" variant="secondary" icon="search">Cari</x-admin.tombol>
    </x-admin.bilah-filter>

    @if ($daftar->isEmpty())
        <x-admin.keadaan-kosong
            icon="directions_car"
            judul="Belum ada kendaraan"
            keterangan="Tambahkan kendaraan armada pertama Anda agar bisa ditugaskan ke pesanan."
        >
            <x-slot:aksi>
                <x-admin.tombol as="a" href="{{ route('admin.kendaraan.buat') }}" icon="add">Tambah Kendaraan</x-admin.tombol>
            </x-slot:aksi>
        </x-admin.keadaan-kosong>
    @else
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($daftar as $kendaraan)
                <x-admin.kendaraan.kartu :kendaraan="$kendaraan" />
            @endforeach
        </div>
    @endif
</x-tata-letak.admin>
