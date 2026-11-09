<x-tata-letak.admin title="Tulis Artikel" group="{{ __('admin.sidebar.grup_konten') }}">
    <x-admin.kepala-halaman title="Tulis Artikel" subtitle="Semua isian bertanda * wajib diisi dan sesuai format sebelum artikel bisa disimpan" />

    <form
        method="POST"
        action="{{ route('admin.artikel.simpan') }}" enctype="multipart/form-data"
        class="space-y-6"
        novalidate
        x-data="validasiFormulir"
        x-on:submit="periksa($event)"
        x-on:input="bersihkan($event)"
        x-on:change="bersihkan($event)"
    >
        @csrf

        <x-admin.artikel.formulir />

        <div class="flex justify-end gap-3">
            <x-admin.tombol as="a" href="{{ route('admin.artikel.indeks') }}" variant="secondary">Batal</x-admin.tombol>
            <x-admin.tombol type="submit" name="aksi" value="draft" variant="secondary">Simpan Draft</x-admin.tombol>
            <x-admin.tombol type="submit" name="aksi" value="terbitkan">Terbitkan Artikel</x-admin.tombol>
        </div>
    </form>
</x-tata-letak.admin>
