@php
    $statusTab = request('status');
    $tabStatus = [
        null => 'Semua',
        'baru' => 'Baru',
        'diproses' => 'Diproses',
        'menunggu_jawaban' => 'Menunggu jawaban',
        'disetujui' => 'Disetujui',
        'tidak_jadi' => 'Tidak jadi',
        'arsip' => 'Arsip',
    ];
@endphp
<x-tata-letak.admin title="{{ __('admin.sidebar.pesanan_kustom') }}" group="{{ __('admin.sidebar.grup_operasional') }}">
    <x-admin.kepala-halaman
        :title="__('admin.sidebar.pesanan_kustom')"
        subtitle="Tinjau rencana tamu, tindak lanjuti, dan susun penawaran perjalanan."
    >
        <x-slot:actions>
            <x-admin.tombol as="a" href="{{ route('admin.pesanan-kustom.buat') }}" icon="add">
                Input Manual
            </x-admin.tombol>
        </x-slot:actions>
    </x-admin.kepala-halaman>

    <x-admin.tab class="mb-6 overflow-x-auto">
        @foreach ($tabStatus as $nilai => $label)
            <x-admin.tab-item
                href="{{ route('admin.pesanan-kustom.indeks', array_filter(['status' => $nilai, 'cari' => request('cari')])) }}"
                :active="$statusTab === $nilai"
                :badge="$nilai === 'arsip' ? $jumlahArsip : ($nilai ? ($jumlahPerStatus[$nilai] ?? 0) : $jumlahPerStatus->sum())"
            >
                {{ $label }}
            </x-admin.tab-item>
        @endforeach
    </x-admin.tab>

    <x-admin.bilah-filter as="form" method="GET" action="{{ route('admin.pesanan-kustom.indeks') }}" class="mb-6">
        <input type="hidden" name="status" value="{{ $statusTab }}" />

        <div class="relative flex-1">
            <span class="material-symbols-outlined pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 text-lg text-muted" aria-hidden="true">search</span>
            <input
                type="search"
                name="cari"
                value="{{ request('cari') }}"
                placeholder="Cari nama, WhatsApp, destinasi, atau area jemput..."
                class="w-full rounded-full border border-surface-border bg-white py-2.5 ps-10 pe-4 text-sm text-ink placeholder:text-muted focus:border-hijau-aksi focus:outline-none focus:ring-1 focus:ring-hijau-aksi"
            />
        </div>

        <x-admin.tombol type="submit" variant="secondary" icon="search">Cari</x-admin.tombol>
    </x-admin.bilah-filter>

    @if ($daftar->isEmpty())
        <x-admin.keadaan-kosong
            icon="tune"
            judul="Belum ada pesanan kustom"
            keterangan="Permintaan dari form Rancang Trip Kustom situs publik akan muncul di sini."
        >
            <x-slot:aksi>
                <x-admin.tombol as="a" href="{{ route('admin.pesanan-kustom.buat') }}" icon="add">Input Manual</x-admin.tombol>
            </x-slot:aksi>
        </x-admin.keadaan-kosong>
    @else
        <x-admin.tabel
            layout="fixed"
            :lebar-kolom="['200px', '160px', '190px', '80px', '120px', '120px']"
            :kolom="['Pelanggan', 'Perjalanan', 'Tanggal & Jemput', 'Peserta', 'Status', 'Aksi']"
        >
            @foreach ($daftar as $item)
                <x-admin.pesanan-kustom.baris :item="$item" />
            @endforeach
        </x-admin.tabel>

        <x-admin.paginasi :paginator="$daftar" class="mt-6" />
    @endif
</x-tata-letak.admin>
