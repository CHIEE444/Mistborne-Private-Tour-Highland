@props(['pesananKustom'])

{{-- "Tandai Tidak Jadi" dari Aksi lainnya: pengganti pilihan status manual, alasan wajib diisi. --}}
<x-admin.modal :nama="'tidak-jadi-'.$pesananKustom->id" size="sm">
    <x-slot:header>
        <p class="font-semibold text-ink">Tandai permintaan tidak jadi?</p>
        <p class="text-xs text-muted">Permintaan ditutup dan bisa dibuka kembali dari Aksi Cepat.</p>
    </x-slot:header>

    <form id="form-tidak-jadi-{{ $pesananKustom->id }}" method="POST" action="{{ route('admin.pesanan-kustom.perbarui-tindak-lanjut', $pesananKustom) }}" @if ($errors->has('alasan_tidak_jadi')) x-init="buka()" @endif>
        @csrf
        @method('PUT')
        <input type="hidden" name="status" value="tidak_jadi">
        <x-admin.formulir.area-teks name="alasan_tidak_jadi" label="Alasan tidak jadi" wajib rows="3" placeholder="Contoh: tamu membatalkan, atau tidak cocok dengan jadwal/harga." required />
    </form>

    <x-slot:footer>
        <x-admin.tombol variant="secondary" x-on:click="tutup()">Batal</x-admin.tombol>
        <x-admin.tombol type="submit" variant="danger" form="form-tidak-jadi-{{ $pesananKustom->id }}">Ya, Tidak Jadi</x-admin.tombol>
    </x-slot:footer>
</x-admin.modal>
