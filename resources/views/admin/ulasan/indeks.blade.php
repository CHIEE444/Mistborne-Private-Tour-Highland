@php
    $statusTab = request('status', 'menunggu');
    $tabStatus = [
        'menunggu' => ['label' => 'Menunggu Persetujuan', 'badge' => $jumlahMenunggu],
        'disetujui' => ['label' => 'Disetujui', 'badge' => $jumlahDisetujui],
        'arsip' => ['label' => 'Arsip', 'badge' => $jumlahArsip],
    ];
@endphp
<x-tata-letak.admin title="{{ __('admin.sidebar.ulasan') }}" group="{{ __('admin.sidebar.grup_konten') }}">
    <x-admin.kepala-halaman
        :title="__('admin.sidebar.ulasan')"
        subtitle="Kurasi ulasan resmi dan testimoni video pelanggan trip. Hanya ulasan disetujui yang tampil di website publik."
    />

    <x-admin.tab class="mb-6">
        @foreach ($tabStatus as $nilai => $info)
            <x-admin.tab-item
                href="{{ route('admin.ulasan.indeks', ['status' => $nilai, 'cari' => request('cari')]) }}"
                :active="$statusTab === $nilai"
                :badge="$info['badge']"
            >
                {{ $info['label'] }}
            </x-admin.tab-item>
        @endforeach
    </x-admin.tab>

    <x-admin.bilah-filter as="form" method="GET" action="{{ route('admin.ulasan.indeks') }}" class="mb-6">
        <input type="hidden" name="status" value="{{ $statusTab }}" />

        <div class="relative flex-1">
            <span class="material-symbols-outlined pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 text-lg text-muted" aria-hidden="true">search</span>
            <input
                type="search"
                name="cari"
                value="{{ request('cari') }}"
                placeholder="Cari nama tamu atau kode pesanan..."
                class="w-full rounded-full border border-surface-border bg-white py-2.5 ps-10 pe-4 text-sm text-ink placeholder:text-muted focus:border-hijau-aksi focus:outline-none focus:ring-1 focus:ring-hijau-aksi"
            />
        </div>

        <x-admin.tombol type="submit" variant="secondary" icon="search">Cari</x-admin.tombol>
    </x-admin.bilah-filter>

    @if ($daftar->isEmpty())
        <x-admin.keadaan-kosong
            icon="rate_review"
            judul="Belum ada ulasan"
            keterangan="Ulasan tamu akan muncul di sini setelah mereka mengisi formulir rating lewat tautan yang dikirim admin."
        />
    @else
        <div class="space-y-4">
            @foreach ($daftar as $ulasan)
                <x-admin.ulasan.kartu :ulasan="$ulasan" />
            @endforeach
        </div>

        <x-admin.paginasi :paginator="$daftar" class="mt-6" />
    @endif
</x-tata-letak.admin>
