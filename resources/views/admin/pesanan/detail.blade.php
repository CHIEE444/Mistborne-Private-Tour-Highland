@php
    // Stepper 6 tahap (Pesanan::TAHAP). Jadwal Ditahan memakai posisi status sebelum ditahan.
    $urutanTahap = ['dihubungi', 'menunggu_pembayaran', 'sudah_bayar', 'lunas', 'berlangsung', 'selesai'];
    $tahapSekarang = $pesanan->tahap();
    if ($tahapSekarang === 'jadwal_ditahan') {
        $tahapSekarang = $pesanan->status_sebelum_hold === 'payment' ? ($pesanan->sisaTagihan() === 0 ? 'lunas' : 'sudah_bayar') : ($pesanan->dikonfirmasi_at ? 'menunggu_pembayaran' : 'dihubungi');
    }
    $langkahSekarang = array_search($tahapSekarang, $urutanTahap, true) + 1;
@endphp
<x-tata-letak.admin title="{{ $pesanan->kode }}" group="{{ __('admin.sidebar.grup_operasional') }}">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('admin.pesanan.indeks') }}" class="flex items-center gap-1 text-sm text-muted hover:text-ink">
                <span class="material-symbols-outlined text-lg" aria-hidden="true">arrow_back</span>
                Pesanan
            </a>
            <span class="text-muted">/</span>
            <h1 class="text-xl font-semibold text-ink">{{ $pesanan->kode }}</h1>
            <x-admin.lencana-status :status="$pesanan->status" :pesanan="$pesanan" />
        </div>

        <p class="text-xs text-muted">
            Dibuat {{ $pesanan->created_at->translatedFormat('d M Y') }} &middot; Sumber: {{ $pesanan->sumber->label() }}
            @if ($kustomAsal)
                &middot; <a href="{{ route('admin.pesanan-kustom.detail', $kustomAsal->id) }}" class="font-semibold text-hijau-aksi hover:underline">Dari pesanan kustom #{{ $kustomAsal->kodeTampilan() }}</a>
            @elseif ($dariPermintaan)
                &middot; <a href="{{ route('admin.permintaan-pesanan.indeks') }}" class="font-semibold text-hijau-aksi hover:underline">Dari Permintaan Form</a>
            @endif
        </p>
    </div>

    @unless (in_array($pesanan->status->value, ['dibatalkan'], true))
        <x-admin.kartu class="mb-6">
            <ol class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6" aria-label="Tahap pesanan">
                @foreach ($urutanTahap as $indeks => $kunci)
                    @php $langkah = $indeks + 1; @endphp
                    <li class="flex items-center gap-2">
                        <span @class([
                            'flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold',
                            'bg-hijau-aksi text-white' => $langkah < $langkahSekarang,
                            'bg-admin-sidebar text-white ring-4 ring-hijau-aksi/20' => $langkah === $langkahSekarang,
                            'bg-page text-muted' => $langkah > $langkahSekarang,
                        ])>
                            @if ($langkah < $langkahSekarang)
                                <span class="material-symbols-outlined text-sm" aria-hidden="true">check</span>
                            @else
                                {{ $langkah }}
                            @endif
                        </span>
                        <span @class(['text-xs font-medium leading-4', 'text-ink' => $langkah <= $langkahSekarang, 'text-muted' => $langkah > $langkahSekarang])>
                            {{ \App\Models\Pesanan::TAHAP[$kunci] }}
                        </span>
                    </li>
                @endforeach
            </ol>

            @if ($pesanan->tripTidakBisaDilaksanakan())
                <p class="mt-3 rounded-lg bg-status-dibatalkan-bg px-3 py-2 text-center text-xs font-semibold text-status-dibatalkan-teks">
                    Trip tidak bisa dilaksanakan: {{ implode(', ', $pesanan->syaratTripBelumTerpenuhi()) }}.
                </p>
            @endif

            @if ($pesanan->status->value === 'hold_schedule')
                <p class="mt-3 text-center text-xs font-medium text-status-hold-schedule-teks">
                    Status saat ini: Jadwal Ditahan (sebelumnya {{ \App\Enums\BookingStatus::tryFrom($pesanan->status_sebelum_hold)?->label() ?? '—' }})
                </p>
            @endif
        </x-admin.kartu>
    @endunless

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-admin.pesanan.kartu-tamu :pesanan="$pesanan" />
            <x-admin.pesanan.kartu-perjalanan :pesanan="$pesanan" :batas-bayar-waktu="$batasBayarWaktu" />
            <x-admin.pesanan.kartu-operasional :pesanan="$pesanan" />
            <x-admin.pesanan.kartu-hotel :pesanan="$pesanan" />
            <x-admin.pesanan.riwayat-jadwal :pesanan="$pesanan" />
            <x-admin.pesanan.riwayat-status :pesanan="$pesanan" />
        </div>

        <div class="space-y-6">
            <x-admin.pesanan.aksi-cepat :pesanan="$pesanan" :boleh-ganti-jadwal="$bolehGantiJadwal" :charge-ganti-jadwal="$chargeGantiJadwal" />
            <x-admin.pesanan.pesan-whatsapp :pesanan="$pesanan" :charge-ganti-jadwal="$chargeGantiJadwal" :dari-permintaan="$dariPermintaan" />

            @if ($pesanan->status->value === 'completed')
                <x-admin.pesanan.kartu-tautan-rating :pesanan="$pesanan" />
            @endif
        </div>
    </div>

    <x-admin.pesanan.dialog-hapus-data-tamu :pesanan="$pesanan" />

    @if ($pesanan->status->value === 'dihubungi' && ! $pesanan->dikonfirmasi_at && $pesanan->sisaTagihan() !== 0)
        <x-admin.pesanan.modal-konfirmasi-bayar :pesanan="$pesanan" />
    @endif
    @if ((in_array($pesanan->status->value, ['dihubungi', 'hold_schedule'], true) && $pesanan->sisaTagihan() !== 0)
        || ($pesanan->status->value === 'payment' && $pesanan->sisaTagihan() > 0))
        <x-admin.pesanan.modal-pembayaran-diterima :pesanan="$pesanan" />
    @endif

    @if ($bolehGantiJadwal)
        <x-admin.pesanan.modal-ganti-jadwal-diterima :pesanan="$pesanan" :charge="$chargeGantiJadwal" />
    @else
        <x-admin.pesanan.modal-ganti-jadwal-terkunci :pesanan="$pesanan" />
    @endif

    <x-admin.pesanan.modal-batal-sebelum-trip :pesanan="$pesanan" />
    <x-admin.pesanan.modal-batal-bencana :pesanan="$pesanan" />
</x-tata-letak.admin>
