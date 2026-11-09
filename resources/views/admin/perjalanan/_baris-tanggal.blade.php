<x-admin.baris-tabel class="{{ $lewat ? 'opacity-60' : '' }}">
    <x-admin.kolom-tabel class="font-medium">{{ $tanggal->tanggal->translatedFormat('l, d F Y') }}</x-admin.kolom-tabel>
    <x-admin.kolom-tabel>{{ $tanggal->terisi() }} grup</x-admin.kolom-tabel>
    <x-admin.kolom-tabel>
        @if ($lewat)
            {{ $tanggal->kuotaEfektif() }} grup
        @else
            <form method="POST" action="{{ route('admin.perjalanan.tanggal.perbarui', [$perjalanan, $tanggal]) }}" class="flex items-center gap-1">
                @csrf
                @method('PUT')
                <input
                    type="number"
                    name="kuota"
                    min="1"
                    max="50"
                    value="{{ $tanggal->kuota }}"
                    placeholder="{{ $tanggal->kuotaEfektif() }}"
                    aria-label="Kuota tanggal {{ $tanggal->tanggal->translatedFormat('d F Y') }}"
                    class="w-20 rounded-lg border border-surface-border px-2 py-1 text-sm"
                />
                <x-admin.tombol-ikon type="submit" icon="save" label="Simpan kuota" title="Simpan kuota (kosongkan untuk default)" />
            </form>
        @endif
    </x-admin.kolom-tabel>
    <x-admin.kolom-tabel>
        @if ($lewat)
            <span class="rounded-full bg-page px-2.5 py-1 text-xs font-semibold text-muted">Lewat</span>
        @elseif ($tanggal->diblok)
            <span class="rounded-full bg-status-dibatalkan-bg px-2.5 py-1 text-xs font-semibold text-status-dibatalkan-teks">Diblokir</span>
        @elseif ($tanggal->penuh() || ($slot['status'] ?? null) === 'penuh')
            <span class="rounded-full bg-status-payment-bg px-2.5 py-1 text-xs font-semibold text-status-payment-teks">Penuh</span>
        @elseif (($slot['status'] ?? null) === 'guide_penuh')
            <span class="rounded-full bg-status-dibatalkan-bg px-2.5 py-1 text-xs font-semibold text-status-dibatalkan-teks" title="Semua guide sudah bertugas di rentang tanggal ini.">Guide penuh</span>
        @elseif ($slot)
            <span class="rounded-full bg-status-completed-bg px-2.5 py-1 text-xs font-semibold text-status-completed-teks">Tersedia · sisa {{ $slot['sisa'] }}</span>
        @else
            <span class="rounded-full bg-page px-2.5 py-1 text-xs font-semibold text-muted" title="Kurang dari H-7: tidak bisa dipesan lagi.">Tutup pemesanan (&lt; H-7)</span>
        @endif
    </x-admin.kolom-tabel>
    <x-admin.kolom-tabel>
        <div class="flex items-center gap-1">
            @unless ($lewat)
                @php $terkunciH14 = ! $tanggal->diblok && ! $tanggal->bolehDiblokir(); @endphp
                <form method="POST" action="{{ route('admin.perjalanan.tanggal.blokir', [$perjalanan, $tanggal]) }}">
                    @csrf
                    @method('PATCH')
                    <x-admin.tombol-ikon
                        type="submit"
                        :icon="$tanggal->diblok ? 'lock_open' : 'lock'"
                        :label="$tanggal->diblok ? 'Buka blokir' : 'Blokir tanggal'"
                        :disabled="$terkunciH14"
                        title="{{ $terkunciH14 ? 'Kurang dari H-14, tidak bisa diblokir' : ($tanggal->diblok ? 'Buka blokir' : 'Blokir tanggal') }}"
                    />
                </form>
            @endunless

            <x-admin.tombol-ikon type="button" icon="delete" variant="danger" label="Hapus tanggal" title="Hapus tanggal" x-on:click="window.dispatchEvent(new CustomEvent('buka-modal', { detail: { nama: 'hapus-tanggal-{{ $tanggal->id }}' } }))" />
        </div>
    </x-admin.kolom-tabel>
</x-admin.baris-tabel>
