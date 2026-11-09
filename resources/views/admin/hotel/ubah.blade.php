<x-tata-letak.admin title="Ubah Hotel" group="Operasional">
    <x-admin.kepala-halaman :title="$hotel->nama" subtitle="Perbarui data, kontak, tarif, atau journey rekomendasi hotel" />

    <form
        method="POST"
        action="{{ route('admin.hotel.perbarui', $hotel) }}"
        class="space-y-6"
        enctype="multipart/form-data"
        novalidate
        x-data="validasiFormulir"
        x-on:submit="periksa($event)"
        x-on:input="bersihkan($event)"
        x-on:change="bersihkan($event)"
    >
        @csrf
        @method('PUT')

        <x-admin.hotel.formulir :hotel="$hotel" :daftar-journey="$daftarJourney" />

        <div class="flex justify-end gap-3">
            <x-admin.tombol as="a" href="{{ route('admin.hotel.indeks') }}" variant="secondary">Batal</x-admin.tombol>
            <x-admin.tombol type="submit">Simpan Perubahan</x-admin.tombol>
        </div>
    </form>
</x-tata-letak.admin>
