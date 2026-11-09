@php
    $statusTab = request('status', 'semua');
    $tabStatus = [
        'semua' => ['label' => 'Semua', 'badge' => null],
        'terbit' => ['label' => 'Terbit', 'badge' => $jumlahTerbit],
        'draft' => ['label' => 'Draft', 'badge' => $jumlahDraft],
        'dijadwalkan' => ['label' => 'Dijadwalkan', 'badge' => $jumlahDijadwalkan],
    ];
@endphp
<x-tata-letak.admin title="{{ __('admin.sidebar.artikel') }}" group="{{ __('admin.sidebar.grup_konten') }}">
    <x-admin.kepala-halaman
        :title="__('admin.sidebar.artikel')"
        subtitle="Kelola jurnal ekspedisi, panduan, dan protokol keselamatan untuk situs publik"
    >
        <x-slot:actions>
            <x-admin.tombol as="a" href="{{ route('admin.artikel.buat') }}" icon="add">Tulis Artikel</x-admin.tombol>
        </x-slot:actions>
    </x-admin.kepala-halaman>

    <x-admin.tab class="mb-6">
        @foreach ($tabStatus as $nilai => $info)
            <x-admin.tab-item
                href="{{ route('admin.artikel.indeks', ['status' => $nilai, 'cari' => request('cari')]) }}"
                :active="$statusTab === $nilai"
                :badge="$info['badge']"
            >
                {{ $info['label'] }}
            </x-admin.tab-item>
        @endforeach
    </x-admin.tab>

    <x-admin.bilah-filter as="form" method="GET" action="{{ route('admin.artikel.indeks') }}" class="mb-6">
        <input type="hidden" name="status" value="{{ $statusTab }}" />

        <div class="relative flex-1">
            <span class="material-symbols-outlined pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 text-lg text-muted" aria-hidden="true">search</span>
            <input
                type="search"
                name="cari"
                value="{{ request('cari') }}"
                placeholder="Cari judul artikel..."
                class="w-full rounded-full border border-surface-border bg-white py-2.5 ps-10 pe-4 text-sm text-ink placeholder:text-muted focus:border-hijau-aksi focus:outline-none focus:ring-1 focus:ring-hijau-aksi"
            />
        </div>

        <x-admin.tombol type="submit" variant="secondary" icon="search">Cari</x-admin.tombol>

        @if (request()->filled('cari'))
            <x-admin.tombol as="a" href="{{ route('admin.artikel.indeks', ['status' => $statusTab]) }}" variant="reset" icon="restart_alt">Reset</x-admin.tombol>
        @endif
    </x-admin.bilah-filter>

    @if ($daftar->isEmpty())
        <x-admin.keadaan-kosong
            icon="article"
            judul="Belum ada artikel"
            keterangan="Tulis artikel pertama Anda untuk mengisi jurnal ekspedisi di situs publik."
        >
            <x-slot:aksi>
                <x-admin.tombol as="a" href="{{ route('admin.artikel.buat') }}" icon="add">Tulis Artikel</x-admin.tombol>
            </x-slot:aksi>
        </x-admin.keadaan-kosong>
    @else
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($daftar as $artikel)
                <x-admin.artikel.kartu :artikel="$artikel" />
            @endforeach
        </div>

        <x-admin.paginasi :paginator="$daftar" class="mt-6" />
    @endif
</x-tata-letak.admin>
