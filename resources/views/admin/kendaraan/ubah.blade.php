<x-tata-letak.admin title="Ubah Kendaraan" group="Operasional">
    <x-admin.kepala-halaman :title="$kendaraan->nama" subtitle="Perbarui data, status, atau foto kendaraan" />

    <form
        method="POST"
        action="{{ route('admin.kendaraan.perbarui', $kendaraan) }}"
        enctype="multipart/form-data"
        class="space-y-6"
        novalidate
        x-data="validasiFormulir"
        x-on:submit="periksa($event)"
        x-on:input="bersihkan($event)"
        x-on:change="bersihkan($event)"
    >
        @csrf
        @method('PUT')

        <x-admin.kendaraan.formulir :kendaraan="$kendaraan" />

        <div class="flex justify-end gap-3">
            <x-admin.tombol as="a" href="{{ route('admin.kendaraan.indeks') }}" variant="secondary">Batal</x-admin.tombol>
            <x-admin.tombol type="submit">Simpan Perubahan</x-admin.tombol>
        </div>
    </form>
</x-tata-letak.admin>
