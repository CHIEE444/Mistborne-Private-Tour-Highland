@props(['ringkasan'])

@php
    $grafik = $ringkasan['grafik'];
    $puncak = max(collect($grafik)->max('jumlah'), 1);
    $batasAtas = max(2, (int) (ceil($puncak / 2) * 2));
    $lebar = 480;
    $tinggi = 200;
    $kiri = 32;
    $kanan = 12;
    $atas = 16;
    $dasar = 170;
    $jumlahTitik = count($grafik);
    $titik = collect($grafik)->map(fn (array $minggu, int $i): array => [
        'x' => round($kiri + $i * (($lebar - $kiri - $kanan) / max($jumlahTitik - 1, 1)), 1),
        'y' => round($dasar - $minggu['jumlah'] / $batasAtas * ($dasar - $atas), 1),
        'minggu' => $minggu,
    ]);
    $garis = $titik->map(fn (array $t): string => $t['x'].','.$t['y'])->implode(' ');
    $totalGrafik = collect($grafik)->sum('jumlah');
@endphp

<x-admin.kartu class="flex min-w-0 flex-col gap-5">
    <h2 class="flex items-center gap-2 text-base font-semibold text-ink">
        <span class="material-symbols-outlined text-xl text-muted" aria-hidden="true">bar_chart</span>
        Kinerja Trip
    </h2>

    <section aria-labelledby="judul-grafik-trip">
        <h3 id="judul-grafik-trip" class="text-sm font-semibold text-ink">Trip per minggu ({{ \App\Layanan\RingkasanKendaraanLayanan::MINGGU_GRAFIK }} minggu terakhir)</h3>
        <p class="mt-0.5 text-xs text-muted">Dihitung dari tanggal mulai trip pada pesanan yang tidak dibatalkan.</p>

        @if ($totalGrafik === 0)
            <p class="mt-3 rounded-xl border border-dashed border-surface-border px-3.5 py-8 text-center text-sm text-muted">
                Belum ada trip pada {{ \App\Layanan\RingkasanKendaraanLayanan::MINGGU_GRAFIK }} minggu terakhir.
            </p>
        @else
            <svg viewBox="0 0 {{ $lebar }} {{ $tinggi }}" class="mt-3 h-auto w-full text-status-payment-teks" role="img" aria-label="Grafik jumlah trip per minggu, total {{ $totalGrafik }} trip">
                @foreach ([0, $batasAtas / 2, $batasAtas] as $nilaiGaris)
                    @php $y = round($dasar - $nilaiGaris / $batasAtas * ($dasar - $atas), 1); @endphp
                    <line x1="{{ $kiri }}" y1="{{ $y }}" x2="{{ $lebar - $kanan }}" y2="{{ $y }}" class="stroke-surface-border" stroke-dasharray="3 4" />
                    <text x="{{ $kiri - 8 }}" y="{{ $y + 4 }}" text-anchor="end" class="fill-muted" font-size="11">{{ $nilaiGaris }}</text>
                @endforeach

                <polygon points="{{ $garis }} {{ $titik->last()['x'] }},{{ $dasar }} {{ $titik->first()['x'] }},{{ $dasar }}" fill="currentColor" opacity="0.08" />
                <polyline points="{{ $garis }}" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />

                @foreach ($titik as $t)
                    <circle cx="{{ $t['x'] }}" cy="{{ $t['y'] }}" r="4.5" fill="white" stroke="currentColor" stroke-width="2.5">
                        <title>Minggu {{ $t['minggu']['label'] }}: {{ $t['minggu']['jumlah'] }} trip</title>
                    </circle>
                    <text x="{{ $t['x'] }}" y="{{ $dasar + 20 }}" text-anchor="middle" class="fill-muted" font-size="10">{{ $t['minggu']['label'] }}</text>
                @endforeach
            </svg>
        @endif
    </section>

    @foreach ($ringkasan['bentrok'] as $bentrok)
        <x-admin.peringatan type="warning">
            <p class="font-semibold">Jadwal bertabrakan</p>
            <p>Pesanan {{ $bentrok['kode'] }} dan {{ $bentrok['lawan'] }} memakai kendaraan ini pada tanggal yang saling tumpang tindih. Ganti kendaraan pada salah satunya.</p>
        </x-admin.peringatan>
    @endforeach

    @if ($ringkasan['penugasanBermasalah'] !== [])
        <x-admin.peringatan type="danger">
            <p class="font-semibold">Penugasan tidak lagi layak</p>
            <ul class="list-disc ps-5">
                @foreach ($ringkasan['penugasanBermasalah'] as $masalah)
                    <li>{{ $masalah['kode'] }}: {{ $masalah['alasan'] }}</li>
                @endforeach
            </ul>
            <p class="mt-1">Ganti kendaraan pada pesanan tersebut.</p>
        </x-admin.peringatan>
    @endif

    <section aria-labelledby="judul-trip-terakhir">
        <h3 id="judul-trip-terakhir" class="mb-3 text-sm font-semibold text-ink">Trip terakhir &amp; mendatang</h3>

        @if ($ringkasan['perjalanan']->isEmpty())
            <p class="rounded-xl border border-dashed border-surface-border px-3.5 py-6 text-center text-sm text-muted">
                Kendaraan ini belum pernah ditugaskan ke pesanan.
            </p>
        @else
            <x-admin.tabel :kolom="['Tanggal', 'Trip', 'Tamu', 'Guide', 'Status']">
                @foreach ($ringkasan['perjalanan'] as $pesanan)
                    <x-admin.baris-tabel>
                        <x-admin.kolom-tabel class="whitespace-nowrap">{{ $pesanan->tanggal_trip->translatedFormat('d M Y') }}</x-admin.kolom-tabel>
                        <x-admin.kolom-tabel>
                            <a href="{{ route('admin.pesanan.detail', $pesanan) }}" class="font-medium text-hijau-aksi hover:underline">{{ $pesanan->nama_perjalanan }}</a>
                            <p class="text-xs text-muted">{{ $pesanan->kode }}</p>
                        </x-admin.kolom-tabel>
                        <x-admin.kolom-tabel>{{ $pesanan->nama_lengkap }}</x-admin.kolom-tabel>
                        <x-admin.kolom-tabel>{{ $pesanan->nama_guide ?: '–' }}</x-admin.kolom-tabel>
                        <x-admin.kolom-tabel><x-admin.lencana-status :status="$pesanan->status" :pesanan="$pesanan" /></x-admin.kolom-tabel>
                    </x-admin.baris-tabel>
                @endforeach
            </x-admin.tabel>
        @endif
    </section>
</x-admin.kartu>
