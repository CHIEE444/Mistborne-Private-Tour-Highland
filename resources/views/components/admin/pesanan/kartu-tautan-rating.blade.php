@props(['pesanan'])

@php
    $tautan = url('/rating/'.$pesanan->token_rating);
    $qr = (new \chillerlan\QRCode\QRCode(new \chillerlan\QRCode\QROptions(['version' => 5, 'imageTransparent' => false])))->render($tautan);
    $pesanUlasan = app(\App\Layanan\PesanWhatsAppLayanan::class)->ajakanUlasan($pesanan, $tautan);
    $tautanWa = app(\App\Kontrak\PengirimWhatsApp::class)->buatTautan($pesanan->whatsapp, $pesanUlasan);
    $sudahAdaUlasan = $pesanan->ulasan()->exists();
@endphp

<x-admin.kartu>
    <div class="mb-3 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-muted">Tautan Rating</h2>
        @if ($sudahAdaUlasan)
            <span class="rounded-full bg-status-completed-bg px-2 py-0.5 text-[11px] font-semibold text-status-completed-teks">Sudah Diisi</span>
        @elseif ($pesanan->tautanRatingAktif())
            <span class="rounded-full bg-status-payment-bg px-2 py-0.5 text-[11px] font-semibold text-status-payment-teks">Aktif</span>
        @else
            <span class="rounded-full bg-page px-2 py-0.5 text-[11px] font-semibold text-muted">Kedaluwarsa</span>
        @endif
    </div>

    <p class="mb-2 text-xs text-muted">Bagikan tautan atau QR ini ke tamu lewat WhatsApp untuk minta ulasan.</p>

    <div class="mb-3 flex items-center gap-2 rounded-lg bg-page p-2">
        <input
            type="text"
            readonly
            value="{{ $tautan }}"
            class="w-full truncate border-0 bg-transparent text-xs text-ink focus:ring-0"
        />
        <x-admin.tombol-ikon
            type="button"
            icon="content_copy"
            label="Salin tautan"
            x-on:click="salinTeks({{ \Illuminate\Support\Js::from($tautan) }}, 'Tautan sudah tersimpan di clipboard.', 'Tautan gagal disalin. Silakan salin secara manual.')"
        />
    </div>

    <div class="flex flex-col items-center gap-2 rounded-lg border border-surface-border p-4">
        <img src="{{ $qr }}" alt="QR tautan rating {{ $pesanan->kode }}" class="h-36 w-36" />
        <a href="{{ $qr }}" download="qr-rating-{{ $pesanan->kode }}.svg" class="flex items-center gap-1 text-xs font-medium text-hijau-aksi hover:underline">
            <span class="material-symbols-outlined text-sm" aria-hidden="true">download</span>
            Unduh QR Code
        </a>
    </div>

    @php $batasUlasanHari = \App\Models\Pengaturan::current()->batas_ulasan_hari; @endphp
    <p class="mt-2 text-[11px] text-muted">
        Aktif sampai {{ ($pesanan->tanggal_selesai ?? $pesanan->tanggal_trip)->copy()->addDays($batasUlasanHari)->translatedFormat('d M Y') }}
        ({{ $batasUlasanHari }} hari setelah selesai)
    </p>

    @unless ($sudahAdaUlasan)
        <div class="mt-4 border-t border-surface-border pt-4">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted">Kirim Ulasan WhatsApp</p>
            <x-admin.pratinjau-whatsapp :pesan="$pesanUlasan" :nomor-tujuan="$pesanan->whatsapp" />
            <x-admin.tombol as="a" href="{{ $tautanWa }}" target="_blank" icon="send" class="mt-3 w-full justify-center">
                Buka WhatsApp
            </x-admin.tombol>
        </div>
    @endunless
</x-admin.kartu>
