<x-tata-letak.admin :title="$perjalanan->adalahPaket() ? 'Ubah Paket' : 'Ubah Journey'" group="{{ __('admin.sidebar.grup_konten') }}">
    <x-admin.kepala-halaman :title="$perjalanan->nama" subtitle="Semua isian bertanda * wajib terisi. Tanggal keberangkatan diatur lewat tombol Kelola Tanggal.">
        <x-slot:actions>
            <x-admin.tombol as="a" href="{{ route('admin.perjalanan.tanggal.indeks', $perjalanan) }}" variant="secondary" icon="calendar_month">
                Kelola Tanggal
            </x-admin.tombol>
            <x-admin.tombol
                type="button"
                variant="danger"
                icon="delete"
                x-on:click="window.dispatchEvent(new CustomEvent('buka-modal', { detail: { nama: 'hapus-perjalanan-{{ $perjalanan->id }}' } }))"
            >
                Hapus
            </x-admin.tombol>
        </x-slot:actions>
    </x-admin.kepala-halaman>

    <form
        method="POST"
        action="{{ route('admin.perjalanan.perbarui', $perjalanan) }}"
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

        <x-admin.perjalanan.formulir :jenis="$perjalanan->jenis" :perjalanan="$perjalanan" :daftar-kendaraan="$daftarKendaraan ?? []" />

        <div class="flex justify-end gap-3">
            <x-admin.tombol as="a" href="{{ route('admin.perjalanan.indeks', ['jenis' => $perjalanan->jenis]) }}" variant="secondary">Batal</x-admin.tombol>
            <x-admin.tombol type="submit">Simpan Perubahan</x-admin.tombol>
        </div>
    </form>

    <x-admin.perjalanan.dialog-hapus :perjalanan="$perjalanan" />
</x-tata-letak.admin>
