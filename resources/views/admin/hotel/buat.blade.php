<x-tata-letak.admin title="Tambah Hotel" group="Operasional">
    <x-admin.kepala-halaman title="Tambah Hotel" subtitle="Daftarkan hotel rekanan untuk direkomendasikan dan dipesankan ke tamu" />

    <form
        method="POST"
        action="{{ route('admin.hotel.simpan') }}"
        class="space-y-6"
        enctype="multipart/form-data"
        novalidate
        x-data="validasiFormulir"
        x-on:submit="periksa($event)"
        x-on:input="bersihkan($event)"
        x-on:change="bersihkan($event)"
    >
        @csrf

        <x-admin.hotel.formulir :daftar-journey="$daftarJourney" />

        <div class="flex justify-end gap-3">
            <x-admin.tombol as="a" href="{{ route('admin.hotel.indeks') }}" variant="secondary">Batal</x-admin.tombol>
            <x-admin.tombol type="submit">Simpan Hotel</x-admin.tombol>
        </div>
    </form>
</x-tata-letak.admin>
