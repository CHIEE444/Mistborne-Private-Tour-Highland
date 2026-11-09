@props(['pesanan'])

@php $terkunci = in_array($pesanan->status->value, ['completed', 'dibatalkan'], true); @endphp

<x-admin.kartu id="operasional-pesanan" class="scroll-mt-24">
    <h2 class="mb-4 text-sm font-semibold text-muted">Operasional</h2>

    <form
        method="POST"
        action="{{ route('admin.pesanan.operasional', $pesanan) }}"
        enctype="multipart/form-data"
        class="space-y-4"
    >
        @csrf
        @method('PUT')

        @php
            $layananKetersediaan = app(\App\Layanan\KetersediaanPersonelOperasionalLayanan::class);
            $daftarGuide = $layananKetersediaan->daftarTersedia($pesanan)->all();
            $guideBentrok = $pesanan->guide_personel_id && $layananKetersediaan->memilikiJadwalBentrok((int) $pesanan->guide_personel_id, $pesanan);
            $simGuideBermasalah = $pesanan->guide && ! $pesanan->guide->simBerlakuSampai($pesanan->tanggal_selesai ?? $pesanan->tanggal_trip);
        @endphp
        <x-admin.formulir.pilihan name="guide_personel_id" label="Guide (sekaligus Driver)" :pilihan="$daftarGuide" :value="$pesanan->guide_personel_id" placeholder="Belum ditentukan. Hanya guide aktif dengan SIM berlaku sampai trip selesai yang tampil." :disabled="$terkunci" />
        @php
            $layananKendaraan = app(\App\Layanan\KetersediaanKendaraanLayanan::class);
            $daftarKendaraan = $layananKendaraan->daftarTersedia($pesanan)->all();
            $masalahKendaraan = $pesanan->kendaraan ? $layananKendaraan->alasanTidakLayak($pesanan->kendaraan, $pesanan) : null;
        @endphp
        @if (! $terkunci && ! $pesanan->kendaraan_id)
            <x-admin.peringatan type="warning">Belum ada mobil yang tersedia di tanggal ini (siap, cukup kursi, dan tidak bentrok). Tambah armada di menu Kendaraan atau tentukan mobil di bawah.</x-admin.peringatan>
        @elseif ($pesanan->kendaraan && $pesanan->perjalanan?->kendaraan_id && (int) $pesanan->perjalanan->kendaraan_id !== (int) $pesanan->kendaraan_id)
            <x-admin.peringatan type="info">Mobil utama journey ini ({{ $pesanan->perjalanan->kendaraan?->namaUntukTamu() }}) tidak tersedia di tanggal ini, jadi sistem menugaskan {{ $pesanan->kendaraan->namaUntukTamu() }}. Anda bisa menggantinya di bawah.</x-admin.peringatan>
        @endif
        <div>
            <x-admin.formulir.pilihan
                name="kendaraan_id"
                label="Kendaraan"
                :pilihan="$daftarKendaraan"
                :value="$pesanan->kendaraan_id"
                :placeholder="'Belum ditentukan (daftar: mobil siap jalan, min. '.$layananKendaraan->kursiDibutuhkan($pesanan).' kursi termasuk guide-sopir, tidak bentrok)'"
                :disabled="$terkunci"
            />
            @if (empty($daftarKendaraan))
                <p class="mt-1.5 text-xs text-muted">Belum ada kendaraan yang tersedia. <a href="{{ route('admin.kendaraan.indeks') }}" class="font-medium text-hijau-aksi hover:underline">Kelola kendaraan</a></p>
            @endif
            @if ($masalahKendaraan && ! $terkunci)
                <x-admin.peringatan type="warning" class="mt-2">Kendaraan yang dipilih saat ini bermasalah: {{ $masalahKendaraan }}. Pilih kendaraan lain.</x-admin.peringatan>
            @endif
        </div>
        @if ($pesanan->guide_personel_id)
            <x-admin.pesanan.status-penugasan :pesanan="$pesanan" :terkunci="$terkunci" />
        @endif
        @if ($pesanan->penugasanDitolak() && ! $terkunci)
            <x-admin.peringatan type="danger">
                <p class="font-semibold">Guide menolak tugas ini.</p>
                <p class="mt-1 text-xs leading-5">
                    Pilih guide pengganti pada kolom di atas lalu simpan; email penugasan akan dikirim ke guide baru.
                    Pesanan belum bisa ditandai Trip Selesai sebelum penugasan diganti.
                </p>
            </x-admin.peringatan>
        @endif
        @if ($guideBentrok)
            <x-admin.peringatan type="warning">Guide yang saat ini dipilih memiliki pesanan lain pada rentang tanggal yang sama. Pilih guide lain yang tersedia.</x-admin.peringatan>
        @endif
        @if ($simGuideBermasalah && ! $terkunci)
            <x-admin.peringatan type="danger">SIM guide {{ $pesanan->guide->nama }} {{ $pesanan->guide->masa_berlaku_sim ? 'habis pada '.$pesanan->guide->masa_berlaku_sim->translatedFormat('d M Y').', sebelum trip selesai' : 'belum diisi' }}. Perbarui data SIM di menu Guide atau pilih guide lain.</x-admin.peringatan>
        @endif

        <x-admin.formulir.area-teks name="catatan_admin" label="Catatan Admin" :value="$pesanan->catatan_admin" :disabled="$terkunci" />

        <div>
            <x-admin.formulir.label>Bukti Pembayaran</x-admin.formulir.label>

            @if ($pesanan->bukti_pembayaran)
                @php($ekstensiBukti = strtolower(pathinfo($pesanan->bukti_pembayaran, PATHINFO_EXTENSION)))
                <div class="mb-3 flex items-center justify-between gap-3 rounded-lg border border-surface-border px-4 py-3">
                    @if (in_array($ekstensiBukti, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true))
                        <button
                            type="button"
                            x-on:click="window.dispatchEvent(new CustomEvent('buka-modal', { detail: { nama: 'lihat-bukti-{{ $pesanan->id }}' } }))"
                            class="flex min-w-0 items-center gap-3 text-left"
                        >
                            <img src="{{ route('admin.pesanan.bukti.lihat', $pesanan) }}" alt="Pratinjau bukti pembayaran" class="h-14 w-14 shrink-0 rounded-md border border-surface-border object-cover" />
                            <span class="text-sm font-medium text-hijau-aksi hover:underline">Klik untuk melihat bukti pembayaran</span>
                        </button>
                    @else
                        <a href="{{ route('admin.pesanan.bukti.lihat', $pesanan) }}" target="_blank" rel="noopener" class="flex items-center gap-2 text-sm font-medium text-hijau-aksi hover:underline">
                            <span class="material-symbols-outlined text-lg" aria-hidden="true">description</span>
                            Lihat bukti pembayaran
                        </a>
                    @endif

                    <x-admin.tombol-ikon
                        type="button"
                        icon="delete"
                        variant="danger"
                        label="Hapus bukti"
                        x-on:click="window.dispatchEvent(new CustomEvent('buka-modal', { detail: { nama: 'hapus-bukti-{{ $pesanan->id }}' } }))"
                    />
                </div>
            @endif

            @unless ($terkunci)
                <x-admin.unggah-berkas name="bukti_pembayaran" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf" keterangan="JPG/PNG/PDF, maksimal 5 MB" maksimal-mb="5" pratinjau-gambar />
            @endunless
        </div>

        @unless ($terkunci)
            <div class="flex justify-end">
                <x-admin.tombol type="submit">Simpan Operasional</x-admin.tombol>
            </div>
        @endunless
    </form>

    @if ($pesanan->guide_personel_id && ! $terkunci)
        {{-- Dipakai tombol "Kirim ulang email" di panel status penugasan (di dalam form Operasional, jadi form-nya terpisah). --}}
        <form id="kirim-ulang-penugasan-{{ $pesanan->id }}" method="POST" action="{{ route('admin.pesanan.penugasan.kirim-ulang', $pesanan) }}" class="hidden">
            @csrf
        </form>
    @endif

    @if (session('popup_email_penugasan') && $pesanan->guide_personel_id)
        <x-admin.pesanan.popup-email-penugasan :pesanan="$pesanan" />
    @endif

    @if ($pesanan->bukti_pembayaran)
        @if (in_array($ekstensiBukti, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true))
            <x-admin.modal nama="lihat-bukti-{{ $pesanan->id }}" size="xl">
                <div class="flex justify-center">
                    <img src="{{ route('admin.pesanan.bukti.lihat', $pesanan) }}" alt="Bukti pembayaran" class="max-h-[80vh] max-w-full rounded-md object-contain" />
                </div>
            </x-admin.modal>
        @endif
        <x-admin.pesanan.dialog-hapus-bukti :pesanan="$pesanan" />
    @endif
</x-admin.kartu>
