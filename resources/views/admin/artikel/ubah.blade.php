<x-tata-letak.admin title="Ubah Artikel" group="{{ __('admin.sidebar.grup_konten') }}">
    <x-admin.kepala-halaman :title="$artikel->judul" subtitle="Ubah konten, sampul, atau jadwal terbit artikel">
        <x-slot:actions>
            <x-admin.tombol
                type="button"
                variant="danger"
                icon="delete"
                x-on:click="window.dispatchEvent(new CustomEvent('buka-modal', { detail: { nama: 'hapus-artikel-{{ $artikel->id }}' } }))"
            >
                Hapus
            </x-admin.tombol>
        </x-slot:actions>
    </x-admin.kepala-halaman>

    <form
        method="POST"
        action="{{ route('admin.artikel.perbarui', $artikel) }}" enctype="multipart/form-data"
        class="space-y-6"
        novalidate
        x-data="validasiFormulir"
        x-on:submit="periksa($event)"
        x-on:input="bersihkan($event)"
        x-on:change="bersihkan($event)"
    >
        @csrf
        @method('PUT')

        <x-admin.artikel.formulir :artikel="$artikel" />

        <div class="flex justify-end gap-3">
            <x-admin.tombol as="a" href="{{ route('admin.artikel.indeks') }}" variant="secondary">Batal</x-admin.tombol>
            <x-admin.tombol type="submit" name="aksi" value="draft" variant="secondary">Simpan Draft</x-admin.tombol>
            <x-admin.tombol type="submit" name="aksi" value="terbitkan">Terbitkan Artikel</x-admin.tombol>
        </div>
    </form>

    <x-admin.artikel.dialog-hapus :artikel="$artikel" />
</x-tata-letak.admin>
