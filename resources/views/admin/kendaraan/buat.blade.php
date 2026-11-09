<x-tata-letak.admin title="Tambah Kendaraan" group="Operasional">
    <x-admin.kepala-halaman title="Tambah Kendaraan" subtitle="Daftarkan kendaraan armada MISTBORNE untuk ditugaskan ke pesanan" />

    <form
        method="POST"
        action="{{ route('admin.kendaraan.simpan') }}"
        enctype="multipart/form-data"
        class="space-y-6"
        novalidate
        x-data="validasiFormulir"
        x-on:submit="periksa($event)"
        x-on:input="bersihkan($event)"
        x-on:change="bersihkan($event)"
    >
        @csrf

        <x-admin.kendaraan.formulir />

        <div class="flex justify-end gap-3">
            <x-admin.tombol as="a" href="{{ route('admin.kendaraan.indeks') }}" variant="secondary">Batal</x-admin.tombol>
            <x-admin.tombol type="submit">Simpan Kendaraan</x-admin.tombol>
        </div>
    </form>
</x-tata-letak.admin>
