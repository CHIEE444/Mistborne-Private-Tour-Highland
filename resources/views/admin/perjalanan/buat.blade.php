@php $namaJenis = ($jenis ?? 'journey') === 'paket' ? 'Paket' : 'Journey'; @endphp
<x-tata-letak.admin :title="'Tambah '.$namaJenis" group="{{ __('admin.sidebar.grup_konten') }}">
    <x-admin.kepala-halaman :title="'Tambah '.$namaJenis" :subtitle="'Semua isian bertanda * wajib diisi sebelum '.strtolower($namaJenis).' bisa disimpan'" />

    <form
        method="POST"
        action="{{ route('admin.perjalanan.simpan') }}"
        enctype="multipart/form-data"
        class="space-y-6"
        novalidate
        x-data="validasiFormulir"
        x-on:submit="periksa($event)"
        x-on:input="bersihkan($event)"
        x-on:change="bersihkan($event)"
    >
        @csrf

        @php $kuotaDefault = \App\Models\Pengaturan::current()->kuota_default; @endphp
        <x-admin.kartu>
            <h2 class="flex items-center gap-2 text-sm font-semibold text-ink">
                <span class="material-symbols-outlined text-lg" aria-hidden="true">event_available</span>
                Tanggal Tersedia Pertama
            </h2>
            <p class="mb-4 text-xs text-muted">Tamu hanya bisa memesan di Tanggal Tersedia. {{ $namaJenis }} baru wajib punya satu tanggal; tanggal lain ditambah lewat &ldquo;Kelola Tanggal&rdquo;.</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-admin.formulir.tanggal name="tanggal_pertama" label="Tanggal Keberangkatan (mulai hari ini)" wajib :min="now()->toDateString()" />
                <x-admin.formulir.masukan name="kuota_pertama" type="number" label="Kuota Tanggal Ini" min="1" max="50" :placeholder="'Kosongkan = default '.$kuotaDefault.' grup (dibatasi juga oleh guide bebas)'" />
            </div>
        </x-admin.kartu>

        <x-admin.perjalanan.formulir :jenis="$jenis ?? 'journey'" :daftar-kendaraan="$daftarKendaraan ?? []" />

        <div class="flex justify-end gap-3">
            <x-admin.tombol as="a" href="{{ route('admin.perjalanan.indeks', ['jenis' => $jenis ?? 'journey']) }}" variant="secondary">Batal</x-admin.tombol>
            <x-admin.tombol type="submit">Simpan {{ $namaJenis }}</x-admin.tombol>
        </div>
    </form>
</x-tata-letak.admin>
