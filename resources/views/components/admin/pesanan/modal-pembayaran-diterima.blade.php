@props(['pesanan'])

@php
    $minimalUangMuka = $pesanan->harga !== null ? (int) ceil($pesanan->harga * 0.3) : null;
    $minimalTransferSekarang = $minimalUangMuka !== null
        ? max(0, $minimalUangMuka - (int) ($pesanan->nominal_dibayar ?? 0))
        : null;
    $sisaTagihanSekarang = $pesanan->sisaTagihan();
    $tombolBayarSebagian = $pesanan->status->value === 'payment' ? 'Catat Pembayaran' : 'Catat Uang Muka';
    $nominalLama = filled(old('nominal_dibayar')) ? (int) old('nominal_dibayar') : null;
@endphp

<x-admin.modal :nama="'pembayaran-diterima-'.$pesanan->id">
    <x-slot:header>
        <p class="font-semibold text-ink">{{ $pesanan->status->value === 'payment' ? 'Catat Pembayaran Berikutnya' : 'Catat Transfer Diterima' }}</p>
        <p class="text-xs text-muted">Gunakan setelah dana benar-benar masuk dan diverifikasi di rekening.</p>
    </x-slot:header>

    @if ($pesanan->harga === null)
        <x-admin.peringatan type="warning">
            Tentukan total harga pesanan terlebih dahulu sebelum mencatat pembayaran.
        </x-admin.peringatan>
    @else
        {{-- Tombol mengikuti nominal: hanya nominal yang persis sama dengan sisa tagihan yang bisa dicatat lunas. Server tetap memeriksa ulang. --}}
        <form id="form-pembayaran-diterima" method="POST" action="{{ route('admin.pesanan.pembayaran-diterima', $pesanan) }}" class="space-y-4"
            x-data="{
                nominal: @js($nominalLama),
                nominalLama: @js($nominalLama),
                galatServer: @js($errors->has('nominal_dibayar')),
                sisa: @js($sisaTagihanSekarang),
                minimal: @js($pesanan->status->value === 'payment' ? 1 : max(1, (int) $minimalTransferSekarang)),
                rupiah(angka) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(angka); },
                baca(event) {
                    if (event.target.id === 'nominal_dibayar') {
                        this.nominal = parseInt(event.target.value.replace(/[^0-9]/g, ''), 10) || null;
                    }
                },
                get bolehLunas() { return this.nominal !== null && this.nominal === this.sisa; },
                get bolehSebagian() { return this.nominal !== null && this.nominal < this.sisa && this.nominal >= this.minimal; },
                get keterangan() {
                    if (this.nominal === null) return { kelas: 'text-muted', ikon: 'info', teks: 'Ketik nominal transfer, tombol yang sesuai akan aktif otomatis.' };
                    if (this.nominal > this.sisa) return { kelas: 'text-status-dibatalkan-teks', ikon: 'error', teks: 'Nominal melebihi sisa tagihan ' + this.rupiah(this.sisa) + '.' };
                    if (this.bolehLunas) return { kelas: 'text-hijau-aksi', ikon: 'task_alt', teks: 'Nominal sama dengan sisa tagihan. Gunakan tombol Tamu Sudah Lunas.' };
                    if (this.nominal < this.minimal) return { kelas: 'text-status-dibatalkan-teks', ikon: 'error', teks: 'Belum memenuhi uang muka 30%: transfer minimal ' + this.rupiah(this.minimal) + '.' };
                    return { kelas: 'text-amber-800', ikon: 'payments', teks: 'Nominal kurang dari sisa tagihan. Gunakan tombol {{ $tombolBayarSebagian }}; sisa setelah ini ' + this.rupiah(this.sisa - this.nominal) + '.' };
                },
                {{-- Galat server sudah tampil di bawah field; keterangan langsung baru muncul lagi setelah nominal diubah. --}}
                get tampilkanKeterangan() { return ! this.galatServer || this.nominal !== this.nominalLama; },
            }"
            x-on:input="baca($event)"
            @if ($errors->hasAny(['nominal_dibayar', 'jenis_pembayaran', 'metode_pembayaran'])) x-init="buka()" @endif>
            @csrf
            @method('PATCH')

            <div class="rounded-lg bg-page p-4 text-sm">
                <div class="flex justify-between gap-4"><span class="text-muted">Total tagihan termasuk biaya ganti jadwal</span><strong>{{ \App\Bantuan\FormatRupiah::dari($pesanan->totalTagihan()) }}</strong></div>
                @if ($pesanan->status->value !== 'payment')
                    <div class="mt-2 flex justify-between gap-4"><span class="text-muted">Sisa minimal uang muka yang perlu dicatat</span><strong>{{ $minimalTransferSekarang === 0 ? 'Sudah terpenuhi' : \App\Bantuan\FormatRupiah::dari($minimalTransferSekarang) }}</strong></div>
                @else
                    <div class="mt-2 flex justify-between gap-4"><span class="text-muted">Sudah dibayar</span><strong>{{ \App\Bantuan\FormatRupiah::dari($pesanan->nominal_dibayar ?? 0) }}</strong></div>
                @endif
                <div class="mt-2 flex justify-between gap-4"><span class="text-muted">Sisa tagihan saat ini (maksimal transfer baru)</span><strong>{{ \App\Bantuan\FormatRupiah::dari($sisaTagihanSekarang) }}</strong></div>
            </div>

            <div>
                <x-admin.formulir.uang
                    name="nominal_dibayar"
                    label="Nominal transfer baru yang diterima"
                    wajib
                    nullable
                    :value="old('nominal_dibayar')"
                    :placeholder="'Angka saja, sesuai bukti transfer (lunas = '.number_format((int) $sisaTagihanSekarang, 0, ',', '.').')'"
                />
                <p x-cloak x-show="tampilkanKeterangan" class="mt-1.5 flex items-start gap-1 text-xs" x-bind:class="keterangan.kelas" aria-live="polite">
                    <span class="material-symbols-outlined text-sm" aria-hidden="true" x-text="keterangan.ikon"></span>
                    <span x-text="keterangan.teks"></span>
                </p>
            </div>

            <x-admin.formulir.pilihan
                name="metode_pembayaran"
                label="Metode pembayaran"
                wajib
                :pilihan="['transfer' => 'Transfer bank', 'qris' => 'QRIS', 'tunai' => 'Tunai', 'lainnya' => 'Lainnya']"
                :value="old('metode_pembayaran', $pesanan->metode_pembayaran ?? 'transfer')"
            />

            <x-admin.peringatan type="info">
                {{ $tombolBayarSebagian }} hanya untuk nominal yang kurang dari sisa tagihan{{ $pesanan->status->value === 'payment' ? '' : ' (minimal memenuhi uang muka 30%)' }}. Tamu Sudah Lunas hanya untuk nominal yang sama persis dengan sisa tagihan {{ \App\Bantuan\FormatRupiah::dari($sisaTagihanSekarang) }}.
            </x-admin.peringatan>

            <div class="flex flex-wrap justify-end gap-3 border-t border-surface-border pt-4">
                <x-admin.tombol type="button" variant="secondary" x-on:click="tutup()">Batal</x-admin.tombol>
                <x-admin.tombol type="submit" name="jenis_pembayaran" value="uang_muka" variant="secondary" icon="payments" x-bind:disabled="! bolehSebagian">{{ $tombolBayarSebagian }}</x-admin.tombol>
                <x-admin.tombol type="submit" name="jenis_pembayaran" value="lunas" icon="task_alt" x-bind:disabled="! bolehLunas">Tamu Sudah Lunas</x-admin.tombol>
            </div>
        </form>
    @endif

    @if ($pesanan->harga === null)
        <x-slot:footer>
            <x-admin.tombol variant="secondary" x-on:click="tutup()">Tutup</x-admin.tombol>
        </x-slot:footer>
    @endif
</x-admin.modal>
