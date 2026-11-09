@props(['pesananKustom'])

{{--
    Aksi Cepat pesanan kustom, polanya sama dengan pesanan biasa: setiap tahap hanya punya satu tombol hijau dan statusnya
    berubah mengikuti tombol (Hubungi → Diproses, Kirim penawaran → Menunggu jawaban, Resmikan → Disetujui).
    Aksi tambahan (kirim ulang, batalkan tanda, tidak jadi, arsip) ada di "Aksi lainnya".
--}}
@php
    use App\Enums\StatusPesananKustom;

    $layanan = app(\App\Layanan\PesanWhatsAppLayanan::class);
    $tahap = $pesananKustom->tahapAlur();
    $tidakJadi = $pesananKustom->status === StatusPesananKustom::TidakJadi;
    $belumLengkap = $pesananKustom->dataBelumLengkap();
    $tanggalPasti = ! $pesananKustom->tanggal_fleksibel && $pesananKustom->tanggal_diinginkan !== null;
    $kurangPenawaran = array_keys(array_filter([
        'rencana yang akan ditawarkan' => blank($pesananKustom->rencana_perjalanan),
        'harga per orang' => ! $pesananKustom->harga_final,
    ]));
    $bukaUbahData = "const d = document.getElementById('ubah-permintaan'); if (d) { d.open = true; d.scrollIntoView({ behavior: 'smooth', block: 'start' }); }";
    // Masa berlaku yang lewat ditolak server saat menandai terkirim / meresmikan, jadi tombolnya ditutup sebelum WhatsApp terbuka.
    $masaBerlakuLewat = $pesananKustom->batas_penawaran?->isBefore(today()) ?? false;
    $alasanTutupKirim = $masaBerlakuLewat
        ? 'Masa berlaku penawaran ('.$pesananKustom->batas_penawaran->translatedFormat('d M Y').') sudah lewat. Ubah tanggal "berlaku sampai" di Susun Penawaran lalu simpan.'
        : null;
    $tripKurangDariH7 = $tanggalPasti && $pesananKustom->tanggal_diinginkan->lt(today()->addDays(7));
    $bukaBatasPenawaran = "const k = document.getElementById('batas_penawaran'); if (k) { k.scrollIntoView({ behavior: 'smooth', block: 'center' }); k.focus(); }";
@endphp

<x-admin.kartu>
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-muted">Aksi Cepat</h2>
        @if (! $tidakJadi && $tahap <= 4)
            <span class="rounded-full bg-page px-2 py-0.5 text-[11px] text-muted">Tahap {{ $tahap }} dari 4</span>
        @endif
    </div>

    <div class="space-y-3">
        @if ($tidakJadi)
            <p class="rounded-lg bg-status-dibatalkan-bg px-3 py-2 text-center text-sm font-medium text-status-dibatalkan-teks">Permintaan ditutup (Tidak jadi)</p>
            <form method="POST" action="{{ route('admin.pesanan-kustom.perbarui-tindak-lanjut', $pesananKustom) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="status" value="diproses">
                <x-admin.tombol type="submit" icon="restart_alt" class="w-full justify-center">Buka Kembali Permintaan</x-admin.tombol>
            </form>
        @elseif ($tahap === 1)
            @if ($belumLengkap !== [])
                <x-admin.tombol type="button" icon="chat" class="w-full justify-center" x-on:click="window.dispatchEvent(new CustomEvent('buka-modal', { detail: { nama: 'data-belum-lengkap-{{ $pesananKustom->id }}' } }))">Hubungi via WhatsApp</x-admin.tombol>
                <p class="px-2 text-xs leading-5 text-status-payment-teks">{{ count($belumLengkap) }} data permintaan belum lengkap. Klik tombol untuk melihat yang perlu dilengkapi.</p>
            @else
                <x-admin.pesanan.kirim-whatsapp
                    :aksi="route('admin.pesanan-kustom.hubungi', $pesananKustom)"
                    :teks="$layanan->pembukaTripKustom($pesananKustom)"
                    :nomor="$pesananKustom->whatsapp"
                    penerima="tamu"
                    label="Hubungi via WhatsApp"
                />
            @endif
            <p class="px-2 text-xs leading-5 text-muted">Setelah tamu dihubungi, status menjadi Diproses. Berikutnya: 2. Susun penawaran.</p>
        @elseif ($tahap === 2)
            <x-admin.tombol as="a" href="#susun-penawaran" icon="edit_note" class="w-full justify-center">Susun Penawaran</x-admin.tombol>
            <p class="px-2 text-xs leading-5 text-status-payment-teks">Isi {{ implode(' dan ', $kurangPenawaran) }} di kartu Susun Penawaran, lalu klik Simpan penawaran.</p>
        @elseif ($tahap === 3)
            <x-admin.pesanan.kirim-whatsapp
                :aksi="route('admin.pesanan-kustom.penawaran-terkirim', $pesananKustom)"
                metode="POST"
                :teks="$layanan->penawaranTripKustom($pesananKustom)"
                :nomor="$pesananKustom->whatsapp"
                penerima="tamu"
                label="Kirim Penawaran via WhatsApp"
                :tutup="$alasanTutupKirim"
            />
            <p class="px-2 text-xs leading-5 text-muted">{{ \App\Bantuan\FormatRupiah::dari($pesananKustom->harga_final) }} × {{ $pesananKustom->jumlah_orang }} orang = <span class="font-semibold text-ink">{{ $pesananKustom->hargaTotalFormat() }}</span> total grup{{ $pesananKustom->batas_penawaran ? ', berlaku sampai '.$pesananKustom->batas_penawaran->translatedFormat('d M Y') : '' }}. Setelah terkirim, status menjadi Menunggu jawaban.</p>
        @elseif ($tahap === 4)
            @if ($masaBerlakuLewat)
                <x-admin.tombol type="button" icon="event_busy" class="w-full justify-center" x-on:click="{{ $bukaBatasPenawaran }}">Perpanjang Masa Berlaku</x-admin.tombol>
                <p class="px-2 text-xs leading-5 text-status-dibatalkan-teks">Penawaran kedaluwarsa sejak {{ $pesananKustom->batas_penawaran->translatedFormat('d M Y') }}, jadi belum bisa diresmikan. Ubah tanggal "berlaku sampai", simpan, lalu kirim ulang penawaran ke tamu.</p>
            @elseif (! $tanggalPasti)
                <x-admin.tombol type="button" icon="event" class="w-full justify-center" x-on:click="{{ $bukaUbahData }}">Sepakati Tanggal Pasti</x-admin.tombol>
                <p class="px-2 text-xs leading-5 text-status-payment-teks">Tanggal tamu masih fleksibel. Simpan tanggal pasti di Edit data permintaan, lalu kirim ulang penawaran sebelum meresmikan.</p>
            @elseif ($tripKurangDariH7)
                <x-admin.tombol type="button" icon="event" class="w-full justify-center" x-on:click="{{ $bukaUbahData }}">Sepakati Tanggal Baru</x-admin.tombol>
                <p class="px-2 text-xs leading-5 text-status-payment-teks">Tanggal yang disepakati ({{ $pesananKustom->tanggal_diinginkan->translatedFormat('d M Y') }}) kurang dari 7 hari lagi. Pesanan resmi butuh minimal H-7 untuk persiapan guide, mobil, dan pembayaran.</p>
            @else
                <x-admin.tombol type="button" icon="task_alt" class="w-full justify-center" x-on:click="window.dispatchEvent(new CustomEvent('buka-modal', { detail: { nama: 'jadikan-resmi-{{ $pesananKustom->id }}' } }))">Jadikan Pesanan Resmi</x-admin.tombol>
                <p class="px-2 text-xs leading-5 text-muted">Klik setelah tamu membalas setuju. Pesanan resmi lalu mengikuti alur pesanan biasa (Konfirmasi, Pembayaran, Guide, Pengingat).</p>
            @endif
        @else
            <p class="flex items-center justify-center gap-1.5 rounded-lg bg-status-completed-bg px-3 py-2 text-sm font-semibold text-status-completed-teks">
                <span class="material-symbols-outlined text-lg" aria-hidden="true">task_alt</span>
                Pesanan disetujui
            </p>
            <x-admin.tombol as="a" href="{{ route('admin.pesanan.detail', $pesananKustom->pesanan) }}" icon="open_in_new" class="w-full justify-center">Buka Pesanan Resmi</x-admin.tombol>
            <p class="px-2 text-xs leading-5 text-muted">Lanjutkan Konfirmasi, Pembayaran, Guide, dan Pengingat di pesanan {{ $pesananKustom->pesanan->kode }}.</p>
        @endif

        @if ($tahap <= 4)
            <details class="group rounded-xl border border-surface-border">
                <summary class="flex cursor-pointer list-none items-center justify-between px-4 py-3 text-sm font-semibold text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-hijau-aksi">
                    Aksi lainnya
                    <span class="material-symbols-outlined text-lg transition-transform group-open:rotate-180" aria-hidden="true">expand_more</span>
                </summary>

                <div class="space-y-3 border-t border-surface-border p-3">
                    @if (! $tidakJadi && $tahap === 4)
                        <p class="text-xs font-semibold text-ink">Kirim ulang penawaran terbaru</p>
                        <x-admin.pesanan.kirim-whatsapp
                            :aksi="route('admin.pesanan-kustom.penawaran-terkirim', $pesananKustom)"
                            metode="POST"
                            :teks="$layanan->penawaranTripKustom($pesananKustom)"
                            :nomor="$pesananKustom->whatsapp"
                            penerima="tamu"
                            label="Kirim Ulang Penawaran"
                            :tutup="$alasanTutupKirim"
                        />
                        <form method="POST" action="{{ route('admin.pesanan-kustom.perbarui-tindak-lanjut', $pesananKustom) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="diproses">
                            <button type="submit" class="text-[11px] font-medium text-muted underline hover:text-ink">Batalkan tanda penawaran terkirim</button>
                        </form>
                    @endif

                    @unless ($tidakJadi)
                        <x-admin.tombol type="button" variant="danger-outline" icon="cancel" class="w-full justify-center" x-on:click="window.dispatchEvent(new CustomEvent('buka-modal', { detail: { nama: 'tidak-jadi-{{ $pesananKustom->id }}' } }))">Tandai Tidak Jadi</x-admin.tombol>
                    @endunless

                    <x-admin.tombol type="button" variant="danger" icon="archive" class="w-full justify-center" x-on:click="window.dispatchEvent(new CustomEvent('buka-modal', { detail: { nama: 'arsipkan-kustom-{{ $pesananKustom->id }}' } }))">Arsipkan Permintaan</x-admin.tombol>
                </div>
            </details>
        @endif
    </div>
</x-admin.kartu>
