@props(['pesananKustom'])

<x-admin.modal :nama="'jadikan-resmi-'.$pesananKustom->id" size="sm">
    <x-slot:header>
        <p class="font-semibold text-ink">Jadikan Pesanan Resmi</p>
        <p class="text-xs text-muted">{{ $pesananKustom->nama }} — {{ $pesananKustom->destinasi_diinginkan }}</p>
    </x-slot:header>

    <form id="form-jadikan-resmi-{{ $pesananKustom->id }}" method="POST" action="{{ route('admin.pesanan-kustom.jadikan-resmi', $pesananKustom) }}" class="space-y-4">
        @csrf

        <x-admin.peringatan type="info">
            Pesanan resmi akan memakai jadwal dan kuota operasional khusus trip kustom. Nama destinasi, durasi, harga, serta rencana tamu tetap tersimpan pada pesanan ini.
        </x-admin.peringatan>

        {{-- Tanggal wajib sama dengan yang disepakati di penawaran (diperiksa server), jadi tidak diketik ulang di sini. --}}
        <input type="hidden" name="tanggal_trip" value="{{ $pesananKustom->tanggal_diinginkan?->toDateString() }}" />
        <div class="rounded-xl border border-surface-border bg-page p-3 text-sm">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted">Penawaran yang diresmikan</p>
            <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1.5">
                <dt class="text-muted">Tanggal trip</dt>
                <dd class="font-medium text-ink">{{ $pesananKustom->tanggal_diinginkan?->translatedFormat('l, d F Y') }}{{ $pesananKustom->durasi_hari ? ' · '.$pesananKustom->durasi_hari.' hari' : '' }}</dd>
                <dt class="text-muted">Peserta</dt>
                <dd class="font-medium text-ink">{{ $pesananKustom->jumlah_orang }} orang</dd>
                <dt class="text-muted">Harga</dt>
                <dd class="font-medium text-ink">{{ \App\Bantuan\FormatRupiah::dari($pesananKustom->harga_final) }} × {{ $pesananKustom->jumlah_orang }} = {{ $pesananKustom->hargaTotalFormat() }}</dd>
                @if ($pesananKustom->riwayatPenawaran->isNotEmpty())
                    <dt class="text-muted">Versi</dt>
                    <dd class="font-medium text-ink">Versi {{ $pesananKustom->riwayatPenawaran->max('versi') }} · dikirim {{ $pesananKustom->penawaran_dikirim_pada?->translatedFormat('d M Y, H:i') }} WIB</dd>
                @endif
            </dl>
            <p class="mt-2 text-xs leading-5 text-muted">Tanggal mengikuti penawaran yang disetujui. Untuk mengubahnya, ubah data permintaan lalu kirim ulang penawaran.</p>
        </div>
        @error('tanggal_trip') <x-admin.formulir.galat :pesan="$message" /> @enderror

        <x-admin.formulir.penginapan awalan-id="resmi_" :all-in="false" :status="$pesananKustom->status_penginapan" :nama="$pesananKustom->nama_hotel" />
        <x-admin.formulir.masukan name="lokasi_pickup" label="Lokasi Pick-up" wajib :value="$pesananKustom->lokasi_penjemputan" placeholder="Titik jemput final untuk pesanan resmi" />
        <label class="flex items-start gap-2 text-sm text-muted">
            <input type="checkbox" name="tamu_menyetujui" value="1" required class="mt-0.5 rounded border-surface-border text-hijau-aksi focus:ring-hijau-aksi" />
            <span>Saya memastikan tamu sudah menyetujui rencana, harga, dan tanggal final pada penawaran terakhir.</span>
        </label>
        @error('tamu_menyetujui') <x-admin.formulir.galat :pesan="$message" /> @enderror
    </form>

    <x-slot:footer>
        <x-admin.tombol variant="secondary" x-on:click="tutup()">Batal</x-admin.tombol>
        <x-admin.tombol type="submit" form="form-jadikan-resmi-{{ $pesananKustom->id }}">Buat Pesanan Resmi</x-admin.tombol>
    </x-slot:footer>
</x-admin.modal>
