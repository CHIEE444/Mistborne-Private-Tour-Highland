@props(['tanggal', 'pesanan', 'journeyTerpilih' => null])

@php
    $terkonfirmasi = $pesanan->filter(fn ($item) => in_array($item->status->value, ['payment', 'completed'], true))->values();
    $menunggu = $pesanan->reject(fn ($item) => in_array($item->status->value, ['payment', 'completed'], true))->values();
    $kelompok = [
        ['judul' => 'Terkonfirmasi', 'keterangan' => 'Pembayaran atau Selesai', 'daftar' => $terkonfirmasi],
        ['judul' => 'Menunggu pembayaran', 'keterangan' => 'Dihubungi atau Jadwal Ditahan, tetap menahan kuota', 'daftar' => $menunggu],
    ];
@endphp

<x-admin.kartu class="lg:sticky lg:top-6">
    <div class="mb-4 flex items-start justify-between gap-3">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-muted">Ringkasan Hari</p>
            <p class="mt-1 font-semibold text-ink">{{ $tanggal->translatedFormat('d F Y') }}</p>
            <p class="mt-1 text-xs leading-5 text-muted">{{ $journeyTerpilih ? $journeyTerpilih->nama.' · ' : '' }}{{ $terkonfirmasi->count() }} terkonfirmasi · {{ $menunggu->count() }} menunggu pembayaran</p>
        </div>
        <x-admin.tombol-ikon as="a" href="{{ route('admin.kalender-trip.indeks', request()->only('bulan', 'journey')) }}" icon="close" label="Tutup ringkasan tanggal" />
    </div>

    @if ($pesanan->isEmpty())
        <x-admin.keadaan-kosong icon="event_busy" judul="Belum ada pesanan" keterangan="Pesanan aktif pada tanggal ini akan muncul di sini, termasuk yang masih menunggu pembayaran." />
    @else
        <div class="space-y-5">
            @foreach ($kelompok as $grup)
                @continue($grup['daftar']->isEmpty())
                <section>
                    <div class="mb-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted">{{ $grup['judul'] }} · {{ $grup['daftar']->count() }}</p>
                        <p class="text-[11px] text-muted">{{ $grup['keterangan'] }}</p>
                    </div>
                    <div class="space-y-3">
                        @foreach ($grup['daftar'] as $item)
                            <div class="rounded-admin-kartu border border-surface-border p-4">
                                <div class="mb-2 flex items-center justify-between gap-2">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-muted">{{ $item->nama_perjalanan }}</p>
                                    <x-admin.lencana-status :status="$item->status" :pesanan="$item" />
                                </div>

                                <p class="text-sm font-medium text-ink">{{ $item->nama_lengkap }} ({{ $item->jumlah_wisatawan }} orang)</p>
                                @php
                                    $akhirTrip = $item->tanggal_selesai ?? $item->tanggal_trip;
                                    $totalHari = (int) $item->tanggal_trip->diffInDays($akhirTrip) + 1;
                                    $hariKe = (int) $item->tanggal_trip->diffInDays($tanggal->copy()->startOfDay()) + 1;
                                @endphp
                                @if ($totalHari > 1)
                                    <p class="mt-0.5 text-xs font-medium text-admin-sidebar">Hari ke-{{ $hariKe }} dari {{ $totalHari }} · {{ $item->tanggal_trip->translatedFormat('d M') }} – {{ $akhirTrip->translatedFormat('d M Y') }}@if ($hariKe === 1) · Berangkat @elseif ($hariKe === $totalHari) · Hari terakhir @endif</p>
                                @endif
                                <p class="font-mono text-xs text-muted">{{ $item->kode }}@if ($item->waktu_pickup) · Jemput {{ substr((string) $item->waktu_pickup, 0, 5) }} WIB @endif</p>

                                @if ($item->kendaraan)
                                    <p class="mt-1 text-xs text-muted">Kendaraan: <span class="text-ink">{{ $item->kendaraan->nama }} · {{ $item->kendaraan->plat_nomor }}</span></p>
                                @endif

                                @if ($item->hotel_id && $item->status_reservasi_hotel)
                                    <p class="mt-1 text-xs text-muted">Hotel: <span class="text-ink">{{ $item->hotel?->nama }} · {{ $item->status_reservasi_hotel->label() }}</span></p>
                                @endif

                                @if ($item->nama_guide)
                                    <p class="mt-1 text-xs text-muted">Guide &amp; Driver: <span class="text-ink">{{ $item->nama_guide }}</span></p>
                                @endif

                                <x-admin.tombol as="a" href="{{ route('admin.pesanan.detail', $item) }}" variant="secondary" size="sm" class="mt-2 w-full justify-center">
                                    Buka Pesanan
                                </x-admin.tombol>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>

    @endif

    <p class="mt-3 text-center text-[11px] text-muted">
        Kalender ini hanya untuk pemantauan. Perubahan pesanan dilakukan melalui modul Pesanan.
    </p>
</x-admin.kartu>
