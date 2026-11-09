@php
    $gayaStatus = match ($kendaraan->status) {
        'siap' => 'bg-status-completed-bg text-status-completed-teks',
        'servis' => 'bg-status-payment-bg text-status-payment-teks',
        default => 'bg-page text-muted',
    };
    $statistik = $ringkasan['statistik'];
    $nilaiTrip = $statistik['nilai_trip'] >= 1_000_000
        ? 'Rp '.number_format($statistik['nilai_trip'] / 1_000_000, 1, ',', '.').' jt'
        : \App\Bantuan\FormatRupiah::dari($statistik['nilai_trip']);
@endphp
<x-tata-letak.admin :title="$kendaraan->plat_nomor" group="Operasional">
    <x-admin.kepala-halaman :title="$kendaraan->plat_nomor" :subtitle="$kendaraan->nama">
        <x-slot:actions>
            <span class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $gayaStatus }}">{{ $kendaraan->labelStatus() }}</span>
            <x-admin.tombol as="a" href="{{ route('admin.kendaraan.ubah', $kendaraan) }}" variant="secondary" icon="edit">Ubah</x-admin.tombol>
            <x-admin.tombol type="button" icon="add" x-on:click="window.dispatchEvent(new CustomEvent('buka-modal', { detail: { nama: 'servis-tambah' } }))">Tambah Servis</x-admin.tombol>
        </x-slot:actions>
    </x-admin.kepala-halaman>

    @if ($errors->has('kendaraan'))
        <x-admin.peringatan type="danger" class="mb-6">{{ $errors->first('kendaraan') }}</x-admin.peringatan>
    @endif

    <div class="grid gap-6 xl:grid-cols-3">
        <x-admin.kendaraan.ringkasan-unit :kendaraan="$kendaraan" class="xl:col-span-2" />

        <div class="grid grid-cols-2 gap-4">
            <x-admin.kartu-statistik label="Trip selesai" :value="$statistik['total_trip'].' trip'" icon="route" warna="completed" />
            <x-admin.kartu-statistik label="Trip aktif & mendatang" :value="$statistik['trip_mendatang'].' trip'" icon="event" warna="dihubungi" />
            <x-admin.kartu-statistik label="Keterpakaian 30 hari" :value="$statistik['keterpakaian'].'%'" icon="schedule" warna="payment" />
            <x-admin.kartu-statistik label="Nilai trip selesai" :value="$nilaiTrip" icon="payments" warna="hold-schedule" />
        </div>
    </div>

    @if ($ringkasan['destinasi']->isNotEmpty())
        <x-admin.kartu class="mt-6">
            <h2 class="flex items-center gap-2 text-base font-semibold text-ink">
                <span class="material-symbols-outlined text-xl text-muted" aria-hidden="true">route</span>
                Mobil Tetap untuk Destinasi
            </h2>
            <p class="mb-3 mt-1 text-xs text-muted">Tamu melihat kendaraan ini sebagai mobil resmi destinasi berikut. Ganti lewat menu Journey atau Paket Wisata.</p>
            <ul class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($ringkasan['destinasi'] as $destinasi)
                    <li class="rounded-xl border border-surface-border px-3.5 py-3">
                        <a href="{{ route('admin.perjalanan.ubah', $destinasi) }}" class="text-sm font-medium text-hijau-aksi hover:underline">{{ $destinasi->nama }}</a>
                        @if ($destinasi->alasan_kendaraan)
                            <p class="mt-0.5 text-xs text-muted">{{ $destinasi->alasan_kendaraan }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        </x-admin.kartu>
    @endif

    <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,4fr)_minmax(0,5fr)_minmax(0,3fr)]">
        <x-admin.kendaraan.panel-perawatan :kendaraan="$kendaraan" :ringkasan="$ringkasan" />
        <x-admin.kendaraan.panel-kinerja :ringkasan="$ringkasan" />
        <x-admin.kendaraan.panel-kesiapan :kesiapan="$ringkasan['kesiapan']" />
    </div>

    <x-admin.kendaraan.modal-servis nama="servis-tambah" :kendaraan="$kendaraan" mode="tambah" />
    <x-admin.kendaraan.modal-servis nama="servis-selesai" :kendaraan="$kendaraan" mode="selesai" />

    {{-- Buka ulang modal yang baru dikirim bila validasinya gagal, supaya galat terlihat. --}}
    @if ($errors->any() && old('_modal'))
        <div x-data x-init="$nextTick(() => window.dispatchEvent(new CustomEvent('buka-modal', { detail: { nama: @js(old('_modal')) } })))"></div>
    @endif
</x-tata-letak.admin>
