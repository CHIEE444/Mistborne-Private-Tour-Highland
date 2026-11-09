@props(['pesananKustom'])

{{-- Popup validasi tombol "Hubungi via WhatsApp": daftar data permintaan yang masih kosong, lalu langsung ke form Edit data. --}}
<x-admin.modal :nama="'data-belum-lengkap-'.$pesananKustom->id" size="sm">
    <x-slot:header>
        <p class="font-semibold text-ink">Data permintaan belum lengkap</p>
        <p class="text-xs text-muted">Lengkapi dulu sebelum menghubungi tamu lewat WhatsApp.</p>
    </x-slot:header>

    <ul class="space-y-2">
        @foreach ($pesananKustom->dataBelumLengkap() as $label)
            <li class="flex items-center gap-2 rounded-lg bg-status-dibatalkan-bg px-3 py-2 text-sm font-medium text-status-dibatalkan-teks">
                <span class="material-symbols-outlined text-lg" aria-hidden="true">error</span>
                {{ $label }}
            </li>
        @endforeach
    </ul>

    <x-slot:footer>
        <x-admin.tombol variant="secondary" x-on:click="tutup()">Nanti saja</x-admin.tombol>
        <x-admin.tombol type="button" icon="edit_note" x-on:click="tutup(); const d = document.getElementById('ubah-permintaan'); if (d) { d.open = true; d.scrollIntoView({ behavior: 'smooth', block: 'start' }); }">Lengkapi Data</x-admin.tombol>
    </x-slot:footer>
</x-admin.modal>
