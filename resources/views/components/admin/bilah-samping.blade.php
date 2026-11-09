@php
    // Journey dan Paket Wisata memakai modul admin yang sama (admin.perjalanan.*); menu aktif ditentukan jenisnya.
    $perjalananDibuka = request()->route('perjalanan');
    $jenisAktif = request()->routeIs('admin.perjalanan.*', 'admin.perjalanan.tanggal.*')
        ? ($perjalananDibuka instanceof \App\Models\Perjalanan ? $perjalananDibuka->jenis : (request('jenis') === 'paket' ? 'paket' : 'journey'))
        : null;
    $grupMenu = [
        [
            'label' => __('admin.sidebar.grup_operasional'),
            'tautan' => [
                ['route' => 'admin.dashboard.indeks', 'label' => __('admin.sidebar.dashboard'), 'icon' => 'grid_view'],
                ['route' => 'admin.pesanan.indeks', 'label' => __('admin.sidebar.pesanan'), 'icon' => 'receipt_long'],
                ['route' => 'admin.permintaan-pesanan.indeks', 'label' => 'Permintaan Form', 'icon' => 'assignment', 'badgeKunci' => 'permintaan'],
                ['route' => 'admin.pertanyaan-tamu.indeks', 'label' => 'Pertanyaan Tamu', 'icon' => 'forum', 'badgeKunci' => 'pertanyaan'],
                ['route' => 'admin.pesanan-kustom.indeks', 'label' => __('admin.sidebar.pesanan_kustom'), 'icon' => 'tune', 'badgeKunci' => 'kustom'],
                ['route' => 'admin.kalender-trip.indeks', 'label' => __('admin.sidebar.kalender_trip'), 'icon' => 'calendar_month'],
                ['route' => 'admin.guide.indeks', 'label' => 'Guide', 'icon' => 'badge'],
                ['route' => 'admin.kendaraan.indeks', 'label' => 'Kendaraan', 'icon' => 'directions_car'],
                ['route' => 'admin.hotel.indeks', 'label' => 'Hotel', 'icon' => 'hotel'],
            ],
        ],
        [
            'label' => __('admin.sidebar.grup_konten'),
            'tautan' => [
                ['route' => 'admin.perjalanan.indeks', 'label' => __('admin.sidebar.perjalanan'), 'icon' => 'explore', 'jenis' => 'journey'],
                ['route' => 'admin.perjalanan.indeks', 'label' => 'Paket Wisata', 'icon' => 'landscape', 'jenis' => 'paket', 'params' => ['jenis' => 'paket']],
                ['route' => 'admin.artikel.indeks', 'label' => __('admin.sidebar.artikel'), 'icon' => 'auto_stories'],
                ['route' => 'admin.ulasan.indeks', 'label' => __('admin.sidebar.ulasan'), 'icon' => 'rate_review'],
            ],
        ],
        [
            'label' => __('admin.sidebar.grup_sistem'),
            'tautan' => [
                ['route' => 'admin.pengaturan.indeks', 'label' => __('admin.sidebar.pengaturan'), 'icon' => 'settings'],
            ],
        ],
    ];
@endphp

{{-- Nilai awal angka badge menu; selanjutnya diperbarui lonceng notifikasi (alpine/notifikasi.js). --}}
<script>window.badgeAdminAwal = { permintaan: {{ app(\App\Layanan\NotifikasiAdminLayanan::class)->jumlahPermintaanBaru() }}, pertanyaan: {{ app(\App\Layanan\NotifikasiAdminLayanan::class)->jumlahPertanyaanBaru() }}, kustom: {{ app(\App\Layanan\NotifikasiAdminLayanan::class)->jumlahKustomBaru() }} };</script>

{{-- Overlay drawer untuk tablet/handphone --}}
<div
    x-show="terbuka"
    x-cloak
    x-on:click="tutup()"
    class="fixed inset-0 z-40 bg-black/40 lg:hidden"
></div>

@php
    $penggunaRail = auth()->user();
    $grupBawah = collect($grupMenu)->pop();
    $grupUtama = array_slice($grupMenu, 0, -1);
    $urutRail = 0;
    $inisialRail = collect(explode(' ', trim((string) $penggunaRail?->name)))->filter()->take(2)->map(fn (string $kata) => mb_strtoupper(mb_substr($kata, 0, 1)))->implode('');
@endphp

<aside
    class="rail"
    :class="terbuka ? 'rail-terbuka' : ''"
    aria-label="Menu admin"
>
    <div class="rail-profil">
        <div class="rail-avatar">
            <a href="{{ route('admin.profil.ubah') }}" class="rail-avatar-bulat" data-gelembung="{{ $penggunaRail?->name }}" data-gelembung-sub="{{ $penggunaRail?->jabatan ?? 'Admin Operasional' }}" aria-label="Profil {{ $penggunaRail?->name }}">
                @if ($penggunaRail?->foto)
                    <img src="{{ asset('storage/'.$penggunaRail->foto) }}" alt="{{ $penggunaRail->name }}" />
                @else
                    <span aria-hidden="true">{{ $inisialRail ?: 'A' }}</span>
                @endif
            </a>
            <a href="{{ route('admin.profil.ubah') }}" class="rail-avatar-aksi" aria-label="Ubah profil" title="Ubah profil" tabindex="-1">
                <span class="material-symbols-outlined" aria-hidden="true">edit</span>
            </a>
        </div>
        <div class="rail-profil-teks" aria-hidden="false">
            <p class="truncate text-[15px] font-bold leading-tight text-ink">{{ $penggunaRail?->name }}</p>
            <p class="mt-0.5 truncate text-xs text-admin-sidebar-teks">{{ $penggunaRail?->jabatan ?? 'Admin Operasional' }}</p>
        </div>
    </div>
    <div class="rail-garis" aria-hidden="true"></div>

    <nav class="rail-nav pb-2" aria-label="Navigasi admin">
        @foreach ($grupUtama as $grup)
            <p class="rail-grup">{{ $grup['label'] }}</p>
            @foreach ($grup['tautan'] as $item)
                <x-admin.tautan-menu
                    :href="route($item['route'], $item['params'] ?? [])"
                    :label="$item['label']"
                    :icon="$item['icon']"
                    :active="isset($item['jenis']) ? $jenisAktif === $item['jenis'] : request()->routeIs($item['route'])"
                    :badge="$item['badge'] ?? null"
                    :badge-kunci="$item['badgeKunci'] ?? null"
                    :urut="$urutRail++"
                />
            @endforeach
        @endforeach
    </nav>

    <div class="rail-bawah">
        @foreach ($grupBawah['tautan'] as $item)
            <x-admin.tautan-menu
                :href="route($item['route'], $item['params'] ?? [])"
                :label="$item['label']"
                :icon="$item['icon']"
                :active="request()->routeIs($item['route'])"
                :urut="$urutRail++"
            />
        @endforeach
        <x-admin.tautan-menu :href="route('home')" label="Lihat Situs Publik" icon="open_in_new" target="_blank" rel="noopener noreferrer" :urut="$urutRail++" />
        <button
            type="button"
            x-on:click="window.dispatchEvent(new CustomEvent('buka-modal', { detail: { nama: 'konfirmasi-keluar' } }))"
            class="rail-item rail-item--bahaya"
            style="--i: {{ $urutRail }}"
            data-gelembung="{{ __('admin.sidebar.keluar') }}"
        >
            <span class="rail-ikon"><span class="material-symbols-outlined" aria-hidden="true">logout</span></span>
            <span class="rail-teks">{{ __('admin.sidebar.keluar') }}</span>
        </button>
    </div>
</aside>

<x-admin.modal nama="konfirmasi-keluar" size="sm">
    <x-slot:header>
        <p class="font-semibold text-ink">Yakin ingin keluar?</p>
    </x-slot:header>

    <p class="text-sm text-muted">Anda akan keluar dari admin panel dan perlu masuk lagi untuk melanjutkan.</p>

    <x-slot:footer>
        <x-admin.tombol variant="secondary" x-on:click="tutup()">Batal</x-admin.tombol>

        <form method="POST" action="{{ route('admin.autentikasi.keluar') }}">
            @csrf
            <x-admin.tombol type="submit" variant="danger" icon="logout">Ya, Keluar</x-admin.tombol>
        </form>
    </x-slot:footer>
</x-admin.modal>
