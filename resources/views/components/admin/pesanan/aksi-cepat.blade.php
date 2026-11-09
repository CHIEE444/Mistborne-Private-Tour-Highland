@props(['pesanan', 'bolehGantiJadwal' => false, 'chargeGantiJadwal' => 0])

@php
    $status = $pesanan->status->value;
    $terkunciTotal = in_array($status, ['completed', 'dibatalkan'], true);
    $aksiPembatalan = app(\App\Aksi\Pesanan\BatalkanPesanan::class);
    $bolehBatal = $aksiPembatalan->bolehDibatalkan($pesanan);
    $minimalUangMuka = $pesanan->harga !== null ? (int) ceil($pesanan->harga * 0.3) : null;
    $pembayaranLunas = $pesanan->sisaTagihan() === 0;
    $paymentSiap = $minimalUangMuka !== null && ($pesanan->nominal_dibayar ?? 0) >= $minimalUangMuka;
    $tripSudahMulai = ! now()->startOfDay()->lt($pesanan->tanggal_trip->copy()->startOfDay());
    $alurHotel = app(\App\Layanan\AlurReservasiHotel::class);
    $hotelBelumTuntas = $alurHotel->menghambatCompleted($pesanan);
    $tripSiapCompleted = $status === 'payment'
        && $pembayaranLunas
        && $pesanan->jumlahPersonelDitugaskan() === 1
        && $pesanan->tripOperasionalTuntas()
        && ! $hotelBelumTuntas;
    $holdBolehDipasang = in_array($status, ['dihubungi', 'payment'], true) && $pesanan->sisaHari() > 7;
    $aksiPembayaranTersedia = in_array($status, ['dihubungi', 'hold_schedule', 'payment'], true) && ! $pembayaranLunas;
    // Setelah Guide ditugaskan, langkah berikutnya adalah hotel: tombol hijau pindah ke "Atur Jadwal Hotel" bila kartu Reservasi Hotel tampil.
    $guideSiap = ! $pesanan->penugasanDitolak() && $pesanan->jumlahPersonelDitugaskan() >= 1;
    $adaKartuHotel = $pesanan->status_penginapan !== null && $pesanan->status_penginapan !== \App\Enums\StatusPenginapan::Sudah;
    $hotelJadiAksiUtama = $status === 'payment' && $guideSiap && $adaKartuHotel;
    // Angka tahap sama dengan stepper di kartu Reservasi Hotel.
    $ringkasanHotel = $hotelJadiAksiUtama ? $alurHotel->ringkasan($pesanan) : null;
    $sisaLangkahHotel = $ringkasanHotel ? $ringkasanHotel['total'] - $ringkasanHotel['selesai'] : null;
    $langkahHotelBerikutnya = $hotelJadiAksiUtama ? $alurHotel->langkahBerikutnya($pesanan) : null;
    // Setelah hotel tuntas (atau tamu punya penginapan sendiri), aksi berikutnya adalah pengingat H-7/H-3/H-2 ke tamu.
    $hotelTuntas = $hotelJadiAksiUtama && $alurHotel->sudahDiurus($pesanan);
    $tampilkanPengingat = $status === 'payment' && $guideSiap && ! $tripSudahMulai && ($hotelTuntas || ! $adaKartuHotel);
@endphp

<x-admin.kartu>
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-muted">Aksi Cepat</h2>
        @if (! $terkunciTotal)
            <span class="rounded-full bg-page px-2 py-0.5 text-[11px] text-muted">{{ ucfirst($pesanan->labelJarakTrip()) }}</span>
        @endif
    </div>

    {{-- Syarat trip: semuanya wajib beres sebelum hari keberangkatan, kalau tidak trip tidak bisa dilaksanakan. --}}
    @if ($status === 'payment' && ! $pesanan->tripSudahBerakhir())
        @php $syaratKurang = $pesanan->syaratTripBelumTerpenuhi(); @endphp
        <div @class([
            'mb-4 rounded-xl border p-3',
            'border-status-completed-teks/25 bg-status-completed-bg/60' => $syaratKurang === [],
            'border-status-dibatalkan-teks/30 bg-status-dibatalkan-bg/50' => $syaratKurang !== [] && $pesanan->sisaHari() <= 3,
            'border-surface-border bg-page/60' => $syaratKurang !== [] && $pesanan->sisaHari() > 3,
        ])>
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted">Syarat trip {{ $syaratKurang === [] ? '· siap berangkat' : '· '.count($syaratKurang).' belum beres' }}</p>
            <ul class="space-y-1 text-xs">
                @foreach (['Pembayaran belum lunas' => 'Pembayaran lunas', 'Guide belum menerima tugas' => 'Guide menerima tugas', 'Kendaraan belum ditetapkan' => 'Kendaraan ditetapkan', 'Reservasi hotel belum tuntas' => 'Reservasi hotel tuntas'] as $kurang => $beres)
                    @php $belum = in_array($kurang, $syaratKurang, true); @endphp
                    <li @class(['flex items-center gap-1.5', 'text-status-dibatalkan-teks font-semibold' => $belum, 'text-ink' => ! $belum])>
                        <span class="material-symbols-outlined text-[16px]!" aria-hidden="true">{{ $belum ? 'cancel' : 'check_circle' }}</span>{{ $belum ? $kurang : $beres }}
                    </li>
                @endforeach
            </ul>
            @if ($syaratKurang !== [] && $pesanan->tripSudahDimulai())
                <p class="mt-2 text-xs font-semibold text-status-dibatalkan-teks">Hari keberangkatan sudah tiba: trip tidak bisa dilaksanakan sampai semua syarat beres.</p>
            @endif
        </div>
    @endif

    <div class="space-y-3">
        @if ($status === 'dihubungi' && ! $paymentSiap)
            @if (! $pesanan->dikonfirmasi_at)
                <x-admin.tombol
                    type="button"
                    icon="task_alt"
                    class="w-full justify-center"
                    x-on:click="window.dispatchEvent(new CustomEvent('buka-modal', { detail: { nama: 'konfirmasi-bayar-{{ $pesanan->id }}' } }))"
                >
                    Konfirmasi Pesanan
                </x-admin.tombol>
                <p class="px-2 text-xs leading-5 text-muted">Mulai tenggat pembayaran; status tetap Dihubungi sampai uang muka minimal 30% tercatat.</p>
            @else
                <x-admin.tombol
                    type="button"
                    icon="price_check"
                    class="w-full justify-center"
                    x-on:click="window.dispatchEvent(new CustomEvent('buka-modal', { detail: { nama: 'pembayaran-diterima-{{ $pesanan->id }}' } }))"
                >
                    Catat Pembayaran
                </x-admin.tombol>
                <p class="px-2 text-xs leading-5 text-muted">Catat pembayaran yang diterima untuk masuk tahap Sudah Bayar setelah minimal 30% terpenuhi.</p>
            @endif
        @elseif (in_array($status, ['dihubungi', 'hold_schedule'], true) && $paymentSiap)
            <form method="POST" action="{{ route('admin.pesanan.payment', $pesanan) }}">
                @csrf
                @method('PATCH')
                <x-admin.tombol type="submit" icon="payments" class="w-full justify-center">Tandai Sudah Bayar</x-admin.tombol>
            </form>
            <p class="px-2 text-xs leading-5 text-muted">Uang muka minimal 30% sudah tercatat. Sisa tagihan: {{ \App\Bantuan\FormatRupiah::dari($pesanan->sisaTagihan()) }}.</p>
        @elseif ($status === 'hold_schedule')
            <form method="POST" action="{{ route('admin.pesanan.hold', $pesanan) }}" onsubmit="return confirm('Lepas penahanan jadwal untuk pesanan ini?')">
                @csrf
                @method('PATCH')
                <x-admin.tombol type="submit" icon="play_circle" class="w-full justify-center">Lepas Penahanan Jadwal</x-admin.tombol>
            </form>
        @elseif ($status === 'payment' && ! $pembayaranLunas)
            <x-admin.tombol
                type="button"
                icon="price_check"
                class="w-full justify-center"
                x-on:click="window.dispatchEvent(new CustomEvent('buka-modal', { detail: { nama: 'pembayaran-diterima-{{ $pesanan->id }}' } }))"
            >
                Catat Pembayaran Berikutnya
            </x-admin.tombol>
            <p class="px-2 text-xs leading-5 text-muted">Sisa tagihan {{ \App\Bantuan\FormatRupiah::dari($pesanan->sisaTagihan()) }}.</p>
        @elseif ($status === 'payment' && $tripSudahMulai && $tripSiapCompleted)
            <form method="POST" action="{{ route('admin.pesanan.selesaikan', $pesanan) }}" onsubmit="return confirm('Tandai pesanan {{ $pesanan->kode }} sebagai Trip Selesai? Pastikan perjalanan sudah dimulai atau selesai.')">
                @csrf
                @method('PATCH')
                <x-admin.tombol type="submit" icon="check_circle" class="w-full justify-center">Tandai Trip Selesai</x-admin.tombol>
            </form>
        @elseif ($status === 'payment')
            @if ($hotelJadiAksiUtama)
                {{-- Hotel yang sudah tuntas tidak lagi jadi aksi utama: warna hijau pindah ke pengingat tamu di bawah. --}}
                <x-admin.tombol as="a" href="#reservasi-hotel" icon="hotel" :variant="$hotelTuntas ? 'secondary' : 'primary'" class="w-full justify-center">
                    Atur Jadwal Hotel
                    <span @class(['rounded-full px-2 py-0.5 text-xs tabular-nums', 'bg-white/20' => ! $hotelTuntas, 'bg-status-completed-bg text-status-completed-teks' => $hotelTuntas])>{{ $ringkasanHotel['selesai'] }}/{{ $ringkasanHotel['total'] }}</span>
                </x-admin.tombol>
                @if ($alurHotel->dibatalkan($pesanan))
                    <p class="px-2 text-xs leading-5 text-status-dibatalkan-teks">Reservasi hotel dibatalkan. Simpan reservasi baru di kartu Reservasi Hotel.</p>
                @elseif ($sisaLangkahHotel > 0)
                    <p class="px-2 text-xs leading-5 text-status-payment-teks">
                        Kurang <strong>{{ $sisaLangkahHotel }} tahap</strong> lagi, segera selesaikan.
                        @if ($langkahHotelBerikutnya)
                            Berikutnya: {{ $langkahHotelBerikutnya['nomor'] }}. {{ $langkahHotelBerikutnya['judul'] }}.
                        @endif
                    </p>
                @else
                    <p class="px-2 text-xs leading-5 text-status-completed-teks">Semua {{ $ringkasanHotel['total'] }} tahap reservasi hotel sudah selesai.</p>
                @endif
            @else
                <x-admin.tombol as="a" href="#operasional-pesanan" icon="groups" class="w-full justify-center">
                    {{ $pesanan->penugasanDitolak() ? 'Ganti '.implode(' & ', $pesanan->peranPenolak()).' yang Menolak' : ($pesanan->jumlahPersonelDitugaskan() < 1 ? 'Tugaskan Guide' : 'Lihat Operasional') }}
                </x-admin.tombol>
            @endif
            @if ($pesanan->penugasanDitolak())
                <p class="px-2 text-xs leading-5 text-status-dibatalkan-teks">{{ implode(' dan ', $pesanan->peranPenolak()) }} menolak tugas. Pilih pengganti agar trip bisa berjalan.</p>
            @endif
            @if ($tampilkanPengingat)
                <x-admin.pesanan.pengingat-tamu :pesanan="$pesanan" />
            @endif
            @if ($pembayaranLunas && $pesanan->tripOperasionalTuntas() && $hotelBelumTuntas)
                <p class="px-2 text-xs leading-5 text-status-dibatalkan-teks">Reservasi hotel belum tuntas. Selesaikan semua tahap di kartu Reservasi Hotel sebelum menandai Trip Selesai.</p>
            @endif
            @if (! $tripSudahMulai)
                <p class="px-2 text-xs leading-5 text-muted">Trip dijadwalkan {{ $pesanan->tanggal_trip->translatedFormat('d M Y') }}.</p>
            @endif
        @elseif ($status === 'completed')
            <p class="rounded-lg bg-page px-3 py-2 text-center text-sm font-medium text-muted">Trip sudah selesai.</p>
        @elseif ($status === 'dibatalkan')
            <p class="rounded-lg bg-status-dibatalkan-bg px-3 py-2 text-center text-sm font-medium text-status-dibatalkan-teks">
                {{ match ($pesanan->pihak_pembatal) {
                    'mistborne' => 'Dibatalkan oleh MISTBORNE',
                    'pelanggan' => 'Dibatalkan atas permintaan tamu',
                    default => 'Pesanan dibatalkan',
                } }}
            </p>
            @if ($pesanan->alasan_batal)
                <p class="rounded-lg bg-page px-3 py-2 text-sm text-muted">Alasan: {{ $pesanan->alasan_batal }}</p>
            @endif
        @endif

        @unless ($terkunciTotal)
            <details class="group rounded-xl border border-surface-border">
                <summary class="flex cursor-pointer list-none items-center justify-between px-4 py-3 text-sm font-semibold text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-hijau-aksi">
                    Aksi lainnya
                    <span class="material-symbols-outlined text-lg transition-transform group-open:rotate-180" aria-hidden="true">expand_more</span>
                </summary>

                <div class="space-y-3 border-t border-surface-border p-3">
                    @if ($hotelJadiAksiUtama)
                        <x-admin.tombol as="a" href="#operasional-pesanan" variant="secondary" icon="groups" class="w-full justify-center">Lihat Operasional</x-admin.tombol>
                    @endif

                    @if ($aksiPembayaranTersedia && ! ($status === 'dihubungi' && ! $paymentSiap && $pesanan->dikonfirmasi_at) && ! ($status === 'payment' && ! $pembayaranLunas) && ! ($status === 'hold_schedule' && $paymentSiap))
                            <x-admin.tombol
                                type="button"
                                variant="secondary"
                                icon="price_check"
                                class="w-full justify-center"
                                x-on:click="window.dispatchEvent(new CustomEvent('buka-modal', { detail: { nama: 'pembayaran-diterima-{{ $pesanan->id }}' } }))"
                            >
                                Catat Pembayaran
                            </x-admin.tombol>
                        @endif

                        @if ($status === 'dihubungi' && ! $pesanan->dikonfirmasi_at && $paymentSiap && ! $pembayaranLunas)
                            <x-admin.tombol
                                type="button"
                                variant="secondary"
                                icon="task_alt"
                                class="w-full justify-center"
                                x-on:click="window.dispatchEvent(new CustomEvent('buka-modal', { detail: { nama: 'konfirmasi-bayar-{{ $pesanan->id }}' } }))"
                            >
                                Mulai Tenggat Pembayaran
                            </x-admin.tombol>
                        @endif

                        @if ($status === 'payment')
                            @if (! $tripSudahMulai)
                                <p class="px-2 text-center text-xs text-muted">Trip Selesai bisa ditandai mulai {{ $pesanan->tanggal_trip->translatedFormat('d M Y') }}.</p>
                            @elseif (! $tripSiapCompleted)
                                <p class="px-2 text-center text-xs text-muted">Trip Selesai bisa ditandai setelah lunas dan Guide menandai trip selesai.</p>
                            @endif
                        @endif

                        @if ($bolehGantiJadwal)
                            <x-admin.tombol
                                type="button"
                                variant="secondary"
                                icon="event_repeat"
                                class="w-full justify-center"
                                x-on:click="window.dispatchEvent(new CustomEvent('buka-modal', { detail: { nama: 'ganti-jadwal-{{ $pesanan->id }}' } }))"
                            >
                                Ganti Jadwal {{ $chargeGantiJadwal === 0 ? '(Gratis)' : '' }}
                            </x-admin.tombol>
                        @else
                            <p class="rounded-lg bg-page px-3 py-2 text-center text-xs text-muted">Perubahan jadwal tidak dapat diajukan mulai H-7.</p>
                        @endif

                        @if ($status === 'hold_schedule')
                            <form method="POST" action="{{ route('admin.pesanan.hold', $pesanan) }}" onsubmit="return confirm('Lepas penahanan jadwal untuk pesanan ini?')">
                                @csrf
                                @method('PATCH')
                                <x-admin.tombol type="submit" variant="secondary" icon="play_circle" class="w-full justify-center">Lepas Penahanan Jadwal</x-admin.tombol>
                            </form>
                        @elseif ($holdBolehDipasang)
                            <form method="POST" action="{{ route('admin.pesanan.hold', $pesanan) }}" onsubmit="return confirm('Pindahkan pesanan ini ke Jadwal Ditahan?')">
                                @csrf
                                @method('PATCH')
                                <x-admin.tombol type="submit" variant="secondary" icon="pause_circle" class="w-full justify-center">Tahan Jadwal</x-admin.tombol>
                            </form>
                        @endif

                    @if ($bolehBatal)
                        <x-admin.tombol
                            type="button"
                            variant="danger"
                            icon="cancel"
                            class="w-full justify-center"
                            x-on:click="window.dispatchEvent(new CustomEvent('buka-modal', { detail: { nama: 'batal-sebelum-trip-{{ $pesanan->id }}' } }))"
                        >
                            Batalkan Pesanan
                        </x-admin.tombol>

                        <x-admin.tombol
                            type="button"
                            variant="danger-outline"
                            icon="thunderstorm"
                            class="w-full justify-center"
                            x-on:click="window.dispatchEvent(new CustomEvent('buka-modal', { detail: { nama: 'batal-bencana-{{ $pesanan->id }}' } }))"
                        >
                            Batal Cuaca/Bencana (MISTBORNE)
                        </x-admin.tombol>
                    @endif
                </div>
            </details>
        @endunless
    </div>
</x-admin.kartu>
