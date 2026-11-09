@props(['data'])

@php
    $daftar = [
        ['label' => 'Batas bayar lewat', 'jumlah' => $data['batas_bayar_lewat'], 'href' => route('admin.pesanan.indeks', ['status' => 'dihubungi']), 'icon' => 'schedule'],
        ['label' => 'Guide tidak cukup', 'jumlah' => $data['guide_tidak_cukup'], 'href' => route('admin.kalender-trip.indeks'), 'icon' => 'group'],
        ['label' => 'Guide menolak tugas', 'jumlah' => $data['penugasan_ditolak'], 'href' => route('admin.pesanan.indeks', ['penugasan' => 'ditolak']), 'icon' => 'person_off'],
        ['label' => 'Trip ≤ 7 hari, Guide belum ditugaskan', 'jumlah' => $data['penugasan_mendesak'], 'href' => route('admin.pesanan.indeks', ['penugasan' => 'belum']), 'icon' => 'groups'],
        ['label' => 'Tamu butuh rekomendasi penginapan', 'jumlah' => $data['butuh_rekomendasi_penginapan'], 'href' => route('admin.pesanan.indeks', ['penginapan' => 'belum']), 'icon' => 'hotel'],
        ['label' => 'Reservasi hotel belum terkonfirmasi', 'jumlah' => $data['hotel_belum_terkonfirmasi'], 'href' => route('admin.pesanan.indeks', ['penginapan' => 'hotel']), 'icon' => 'luggage'],
        ['label' => 'Pesanan duplikat', 'jumlah' => $data['duplikat'], 'href' => route('admin.pesanan.indeks'), 'icon' => 'content_copy'],
        ['label' => 'Penawaran kustom kedaluwarsa', 'jumlah' => $data['penawaran_kedaluwarsa'], 'href' => route('admin.pesanan-kustom.indeks', ['status' => 'menunggu_jawaban']), 'icon' => 'event_busy'],
        ['label' => 'Pesanan kustom perlu ditindaklanjuti', 'jumlah' => $data['pesanan_kustom_dihubungi'], 'href' => route('admin.pesanan-kustom.indeks'), 'icon' => 'tune'],
    ];
@endphp

<x-admin.kartu>
    <h2 class="mb-4 text-lg font-bold text-ink">Perlu Ditindaklanjuti</h2>

    <div class="space-y-1">
        @foreach ($daftar as $baris)
            <a href="{{ $baris['href'] }}" class="flex items-center justify-between gap-3 rounded-lg px-2 py-2 text-sm hover:bg-page">
                <span class="flex items-center gap-2 text-ink">
                    <span class="material-symbols-outlined text-lg text-muted" aria-hidden="true">{{ $baris['icon'] }}</span>
                    {{ $baris['label'] }}
                </span>
                <span @class([
                    'rounded-full px-2 py-0.5 text-xs font-semibold',
                    'bg-status-dibatalkan-bg text-status-dibatalkan-teks' => $baris['jumlah'] > 0,
                    'bg-page text-muted' => $baris['jumlah'] === 0,
                ])>
                    {{ $baris['jumlah'] }}
                </span>
            </a>
        @endforeach
    </div>
</x-admin.kartu>
