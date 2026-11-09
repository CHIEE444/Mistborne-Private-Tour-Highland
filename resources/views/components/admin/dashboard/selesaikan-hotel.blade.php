@props(['pesanan'])

{{-- "Selesaikan Hotel" (pindahan Urus Hotel dari menu Hotel): pesanan Pembayaran yang hotelnya belum tuntas, trip terdekat
     di atas. Jumlah tahap sama dengan stepper kartu Reservasi Hotel; tombol Selesaikan membuka kartu itu di detail pesanan.
     Dashboard cukup menampilkan yang paling mendesak; sisanya lewat tombol "Lihat semua". --}}
@php
    $alur = app(\App\Layanan\AlurReservasiHotel::class);
    $batasTampil = 5;
@endphp

<x-admin.kartu :padding="false" id="selesaikan-hotel" {{ $attributes->class(['scroll-mt-24']) }}>
    <div class="flex flex-wrap items-center justify-between gap-2 p-5 pb-4">
        <div>
            <h2 class="flex items-center gap-2 text-lg font-bold text-ink">
                <span class="material-symbols-outlined text-lg text-status-payment-teks" aria-hidden="true">hotel</span>
                Selesaikan Hotel
                @if ($pesanan->isNotEmpty())
                    <span class="rounded-full bg-status-payment-bg px-2 py-0.5 text-[11px] font-semibold text-status-payment-teks">{{ $pesanan->count() }}</span>
                @endif
            </h2>
            <p class="text-xs text-muted">Reservasi hotel tamu yang sudah bayar dan belum tuntas, trip terdekat paling atas.</p>
        </div>
        <x-admin.tombol as="a" href="{{ route('admin.selesaikan-hotel.indeks') }}" variant="secondary" size="sm" icon="arrow_forward">
            Lihat semua{{ $pesanan->isNotEmpty() ? ' ('.$pesanan->count().')' : '' }}
        </x-admin.tombol>
    </div>

    @if ($pesanan->isEmpty())
        <div class="px-5 pb-5">
            <x-admin.keadaan-kosong icon="task_alt" judul="Semua hotel sudah selesai diurus" />
        </div>
    @else
        <div class="overflow-x-auto">
        <x-admin.tabel :kolom="['Kode', 'Nama Tamu', 'Tahap Belum Selesai', '']">
            @foreach ($pesanan->take($batasTampil) as $item)
                @php
                    $ringkasan = $alur->ringkasan($item);
                    $sisa = $ringkasan['total'] - $ringkasan['selesai'];
                    $berikutnya = $alur->langkahBerikutnya($item);
                    $dibatalkan = $alur->dibatalkan($item);
                @endphp
                <x-admin.baris-tabel>
                    <x-admin.kolom-tabel class="font-mono text-xs">
                        <a href="{{ route('admin.pesanan.detail', $item) }}" class="font-semibold text-ink hover:text-hijau-aksi">{{ $item->kode }}</a>
                    </x-admin.kolom-tabel>
                    <x-admin.kolom-tabel>
                        <span class="block font-medium text-ink">{{ $item->nama_lengkap }}</span>
                        <span class="block text-xs text-muted">Trip {{ $item->tanggal_trip->translatedFormat('d M Y') }} · {{ $item->labelJarakTrip() }}</span>
                    </x-admin.kolom-tabel>
                    <x-admin.kolom-tabel>
                        @if ($dibatalkan)
                            <span class="rounded-full bg-status-dibatalkan-bg px-2.5 py-1 text-[11px] font-semibold text-status-dibatalkan-teks">Reservasi dibatalkan, pilih hotel lagi</span>
                        @else
                            <span class="rounded-full bg-status-payment-bg px-2.5 py-1 text-[11px] font-semibold text-status-payment-teks">Kurang {{ $sisa }} dari {{ $ringkasan['total'] }} tahap</span>
                            @if ($berikutnya)
                                <span class="mt-1 block text-xs text-muted">Berikutnya: {{ $berikutnya['nomor'] }}. {{ $berikutnya['judul'] }}</span>
                            @endif
                        @endif
                    </x-admin.kolom-tabel>
                    <x-admin.kolom-tabel class="text-end">
                        <x-admin.tombol as="a" href="{{ route('admin.pesanan.detail', $item) }}#reservasi-hotel" size="sm" icon="arrow_forward">Selesaikan</x-admin.tombol>
                    </x-admin.kolom-tabel>
                </x-admin.baris-tabel>
            @endforeach
        </x-admin.tabel>
        </div>
        @if ($pesanan->count() > $batasTampil)
            <a href="{{ route('admin.selesaikan-hotel.indeks') }}" class="flex items-center justify-center gap-1.5 border-t border-surface-border px-5 py-3 text-sm font-semibold text-hijau-aksi hover:bg-page">
                {{ $pesanan->count() - $batasTampil }} reservasi lainnya belum tuntas · Lihat semua
                <span class="material-symbols-outlined text-[18px]!" aria-hidden="true">arrow_forward</span>
            </a>
        @endif
    @endif
</x-admin.kartu>
