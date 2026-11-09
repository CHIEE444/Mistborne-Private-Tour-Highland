@props(['ulasan'])

<x-admin.modal :nama="'tolak-ulasan-'.$ulasan->id" size="sm">
    <x-slot:header>
        <p class="font-semibold text-ink">Tolak ulasan ini?</p>
    </x-slot:header>

    <p class="text-sm text-muted">
        Ulasan akan disembunyikan dari daftar dan situs publik (bisa dipulihkan lewat tab Arsip).
    </p>

    <x-slot:footer>
        <x-admin.tombol variant="secondary" x-on:click="tutup()">Batal</x-admin.tombol>

        <form method="POST" action="{{ route('admin.ulasan.tolak', $ulasan) }}">
            @csrf
            @method('DELETE')
            <x-admin.tombol type="submit" variant="danger">Ya, Tolak</x-admin.tombol>
        </form>
    </x-slot:footer>
</x-admin.modal>
