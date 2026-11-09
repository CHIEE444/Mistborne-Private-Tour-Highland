@props(['pesanan'])

{{--
    Terbuka otomatis setelah admin menyimpan reservasi baru atau membatalkan reservasi (session `modal_reservasi_hotel`).
    Isinya hanya draft untuk admin hotel; pesan ke tamu ada di tahap "Kabari tamu" dan grup "Pesan Awal ke Tamu".
--}}
@php
    use App\Enums\StatusReservasiHotel;

    $statusBaru = session('modal_reservasi_hotel');
    $hotel = $pesanan->hotel;
    $layanan = app(\App\Layanan\PesanWhatsAppLayanan::class);
    $namaModal = 'draft-hotel-'.$pesanan->id;
    $permintaan = $hotel && $statusBaru === StatusReservasiHotel::Diminta->value && $pesanan->permintaan_hotel_dikirim_at === null;
    $pembatalan = $hotel && $statusBaru === StatusReservasiHotel::Dibatalkan->value;

    $pemeriksa = app(\App\Layanan\PemeriksaReservasiHotel::class);
    $adaMasalahKeras = $permintaan && $pemeriksa->kegagalanKeras($pemeriksa->periksa($pesanan, $hotel, $pesanan->hotel_check_in, $pesanan->hotel_check_out, $pesanan->jumlah_kamar, $pesanan->tipe_kamar)) !== [];
@endphp

@if ($permintaan || $pembatalan)
    <x-admin.modal :nama="$namaModal" size="lg">
        <x-slot:header>
            <p class="font-semibold text-ink">{{ $permintaan ? 'Reservasi tersimpan. Kirim permintaan ke hotel sekarang' : 'Reservasi dibatalkan. Kabari hotel sekarang' }}</p>
            <p class="text-xs text-muted">Pesan untuk admin {{ $hotel->nama }} sudah terisi otomatis dan bisa dibuka lagi dari kartu Pesan WhatsApp.</p>
        </x-slot:header>

        <section class="rounded-xl border border-surface-border p-4" aria-labelledby="draft-hotel-judul-{{ $pesanan->id }}">
            @if ($permintaan)
                <h3 id="draft-hotel-judul-{{ $pesanan->id }}" class="text-sm font-semibold text-ink">Kirim permintaan ke hotel</h3>
                <p class="mb-3 mt-0.5 text-xs text-muted">Minta hotel mengonfirmasi ketersediaan dan tarif. Setelah hotel membalas setuju, isi kode booking di tahap &ldquo;Hotel setuju&rdquo;.</p>
                <x-admin.pesanan.kirim-whatsapp
                    :aksi="route('admin.pesanan.hotel.langkah.tandai', [$pesanan, 'permintaan'])"
                    :teks="$layanan->permintaanReservasiHotel($pesanan)"
                    :nomor="$hotel->whatsapp"
                    label="Kirim ke Hotel via WhatsApp"
                    :tutup="$adaMasalahKeras ? 'Perbaiki item merah pada daftar Periksa sebelum kirim di kartu Reservasi Hotel dulu.' : null"
                />
            @else
                @php $teksBatal = $layanan->pembatalanReservasiHotel($pesanan); @endphp
                <h3 id="draft-hotel-judul-{{ $pesanan->id }}" class="text-sm font-semibold text-ink">Kabari hotel: reservasi dibatalkan</h3>
                <p class="mb-3 mt-0.5 text-xs text-muted">Hotel diminta mengonfirmasi pembatalan dan biaya bila ada.</p>
                <x-admin.pratinjau-whatsapp :pesan="$teksBatal" :nomor-tujuan="$hotel->whatsapp" />
                <div class="mt-3 flex flex-wrap gap-2">
                    @if (filled($hotel->whatsapp))
                        <x-admin.tombol as="a" href="{{ app(\App\Kontrak\PengirimWhatsApp::class)->buatTautan($hotel->whatsapp, $teksBatal) }}" target="_blank" rel="noopener noreferrer" icon="send">Kirim ke Hotel via WhatsApp</x-admin.tombol>
                    @else
                        <x-admin.tombol type="button" disabled icon="send" title="Nomor WhatsApp hotel belum diisi. Lengkapi di menu Hotel.">Kirim ke Hotel via WhatsApp</x-admin.tombol>
                    @endif
                    <x-admin.tombol type="button" variant="secondary" icon="content_copy" x-on:click="salinTeks({{ \Illuminate\Support\Js::from($teksBatal) }})">Salin Pesan</x-admin.tombol>
                </div>
            @endif
        </section>

        <x-slot:footer>
            <x-admin.tombol type="button" variant="secondary" x-on:click="tutup()">{{ $permintaan ? 'Nanti saja' : 'Tutup' }}</x-admin.tombol>
        </x-slot:footer>
    </x-admin.modal>

    <script>
        window.addEventListener('alpine:initialized', () => window.dispatchEvent(new CustomEvent('buka-modal', { detail: { nama: @js($namaModal) } })));
    </script>
@endif
