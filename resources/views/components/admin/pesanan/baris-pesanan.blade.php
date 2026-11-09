@props(['pesanan', 'penanda' => []])

<x-admin.baris-tabel>
    <x-admin.kolom-tabel class="font-mono text-xs font-medium">{{ $pesanan->kode }}</x-admin.kolom-tabel>
    <x-admin.kolom-tabel>
        <div class="flex items-center gap-2.5">
            <x-admin.avatar :nama="$pesanan->nama_lengkap" size="sm" />
            <div>
                <p class="font-medium text-ink">{{ $pesanan->nama_lengkap }}</p>
                <p class="text-xs text-muted">{{ $pesanan->whatsapp }}</p>
            </div>
        </div>
    </x-admin.kolom-tabel>
    <x-admin.kolom-tabel>{{ $pesanan->nama_perjalanan }}</x-admin.kolom-tabel>
    <x-admin.kolom-tabel>{{ $pesanan->rentangTanggalTeks() }}</x-admin.kolom-tabel>
    <x-admin.kolom-tabel>{{ $pesanan->jumlah_wisatawan }} orang</x-admin.kolom-tabel>
    <x-admin.kolom-tabel>
        <div class="flex flex-col items-start gap-1.5">
            @php
                $hariMenujuTrip = $pesanan->sisaHari();
                $statusPayment = $pesanan->status === \App\Enums\BookingStatus::Payment;
                $pengingatHariH = $statusPayment && (
                    $hariMenujuTrip === 7
                    || ($pesanan->sisaTagihan() === 0 && in_array($hariMenujuTrip, [3, 2], true))
                );
                $jenisPengingat = match ($hariMenujuTrip) {
                    7 => 'H-7 · Cek jadwal',
                    3 => 'H-3 · Kirim persiapan',
                    2 => 'H-2 · Kirim keberangkatan',
                    default => null,
                };
                $judulPengingat = match ($hariMenujuTrip) {
                    7 => 'Pengingat H-7 · pastikan tanggal dan data perjalanan sudah benar; perubahan jadwal tidak dapat diajukan mulai H-7',
                    3 => 'Pembayaran lunas · kirim pesan persiapan kepada tamu',
                    2 => 'Pembayaran lunas · kirim informasi keberangkatan kepada tamu',
                    default => null,
                };
                $tampilanPengingat = match ($hariMenujuTrip) {
                    7 => ['bg-amber-100', 'text-amber-800'],
                    3 => ['bg-emerald-100', 'text-emerald-800'],
                    2 => ['bg-sky-100', 'text-sky-800'],
                    default => ['bg-gray-100', 'text-gray-700'],
                };
            @endphp
            @if ($pengingatHariH)
                <span
                    @class([
                        'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold',
                        $tampilanPengingat[0] => true,
                        $tampilanPengingat[1] => true,
                    ])
                    title="{{ $judulPengingat }}"
                >
                    {{ $jenisPengingat }}
                </span>
            @else
                <x-admin.lencana-status :status="$pesanan->status" :pesanan="$pesanan" />
            @endif
            @if ($pesanan->jumlahPersonelDitugaskan() > 0)
                <span @class([
                    'text-[11px] font-medium',
                    'text-emerald-700' => $pesanan->statusTripOperasional() === 'tuntas',
                    'text-amber-800' => $pesanan->statusTripOperasional() === 'sebagian',
                    'text-muted' => in_array($pesanan->statusTripOperasional(), ['menunggu', 'belum_ditugaskan'], true),
                ])>
                    @if ($pesanan->statusTripOperasional() === 'tuntas')
                        Trip tuntas
                    @else
                        Trip {{ $pesanan->jumlahPersonelSelesai() }}/{{ $pesanan->jumlahPersonelDitugaskan() }} selesai
                    @endif
                </span>
            @endif
        </div>
    </x-admin.kolom-tabel>
    <x-admin.kolom-tabel>
        <span class="inline-flex w-fit rounded-md border border-surface-border bg-white px-2.5 py-1 text-xs font-medium text-ink">{{ $pesanan->sumber->label() }}</span>
    </x-admin.kolom-tabel>
    <x-admin.kolom-tabel>
        <div class="flex flex-col gap-1">
            @if ($penanda['batas_bayar_lewat'] ?? false)
                <span class="flex items-center gap-1 text-xs font-medium text-status-dibatalkan-teks">
                    <span class="h-1.5 w-1.5 rounded-full bg-status-dibatalkan-teks"></span>
                    Batas bayar lewat
                </span>
            @endif
            @if ($penanda['penugasan_ditolak'] ?? false)
                <span class="flex items-center gap-1 text-xs font-medium text-status-dibatalkan-teks" title="Guide menolak tugas ini. Buka pesanan untuk menggantinya.">
                    <span class="h-1.5 w-1.5 rounded-full bg-status-dibatalkan-teks"></span>
                    Guide menolak
                </span>
            @endif
            @if ($penanda['penugasan_mendesak'] ?? false)
                <span class="flex items-center gap-1 text-xs font-medium text-status-payment-teks" title="Trip dalam 7 hari ke depan tetapi Guide belum ditugaskan.">
                    <span class="h-1.5 w-1.5 rounded-full bg-status-payment-teks"></span>
                    Guide belum ditugaskan
                </span>
            @endif
            @if ($penanda['guide_tidak_cukup'] ?? false)
                <span class="flex items-center gap-1 text-xs font-medium text-status-dibatalkan-teks" title="Di tanggal trip ini jumlah trip melebihi jumlah guide: ada pesanan yang tidak kebagian guide.">
                    <span class="h-1.5 w-1.5 rounded-full bg-status-dibatalkan-teks"></span>
                    Guide tidak cukup
                </span>
            @endif
            @if ($penanda['duplikat_nomor'] ?? false)
                <span class="flex items-center gap-1 text-xs font-medium text-muted">
                    <span class="h-1.5 w-1.5 rounded-full bg-muted"></span>
                    Nomor telepon sama pada tanggal ini
                </span>
            @endif
            @if ($penanda['duplikat_destinasi'] ?? false)
                <span class="flex items-center gap-1 text-xs font-medium text-status-payment-teks" title="Nomor telepon, destinasi, dan tanggal trip sama dengan pesanan aktif lain.">
                    <span class="h-1.5 w-1.5 rounded-full bg-status-payment-teks"></span>
                    Destinasi sama untuk nomor ini
                </span>
            @endif
        </div>
    </x-admin.kolom-tabel>
    <x-admin.kolom-tabel>
        <div class="flex items-center gap-1">
            <x-admin.tombol as="a" href="{{ route('admin.pesanan.detail', $pesanan) }}" variant="secondary" size="sm">Buka</x-admin.tombol>
            <x-admin.tombol-ikon icon="delete" variant="danger" :label="'Hapus pesanan '.$pesanan->kode" x-on:click="window.dispatchEvent(new CustomEvent('buka-modal', { detail: { nama: 'hapus-data-tamu-{{ $pesanan->id }}' } }))" />
        </div>
        <x-admin.pesanan.dialog-hapus-data-tamu :pesanan="$pesanan" />
    </x-admin.kolom-tabel>
</x-admin.baris-tabel>
