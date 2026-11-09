@props(['artikel'])

<x-admin.modal :nama="'hapus-artikel-'.$artikel->id" size="sm">
    <x-slot:header>
        <p class="font-semibold text-ink">Hapus &ldquo;{{ $artikel->judul }}&rdquo;?</p>
    </x-slot:header>

    <p class="text-sm text-muted">
        Tindakan ini akan mengarsipkan artikel (soft delete) &mdash; bisa dipulihkan kembali oleh pengembang
        bila diperlukan, tapi akan langsung hilang dari daftar admin dan situs publik.
    </p>

    <x-slot:footer>
        <x-admin.tombol variant="secondary" x-on:click="tutup()">Batal</x-admin.tombol>

        <form method="POST" action="{{ route('admin.artikel.hapus', $artikel) }}">
            @csrf
            @method('DELETE')
            <x-admin.tombol type="submit" variant="danger">Ya, Hapus</x-admin.tombol>
        </form>
    </x-slot:footer>
</x-admin.modal>
