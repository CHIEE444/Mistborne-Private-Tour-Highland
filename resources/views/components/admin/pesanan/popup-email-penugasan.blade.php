@props(['pesanan'])

{{-- Popup hasil email penugasan guide: muncul sekali setelah admin menugaskan guide atau mengirim ulang email. --}}
@php
    $email = $pesanan->ringkasanPenugasanGuide()['email'];
    $berhasil = $email['status'] === 'terkirim';
    $nomorGuide = $pesanan->guide?->whatsapp;
    $tautanWaGuide = filled($nomorGuide)
        ? app(\App\Kontrak\PengirimWhatsApp::class)->buatTautan($nomorGuide, "Halo {$pesanan->nama_guide}, ada penugasan baru untuk Anda: {$pesanan->kode} ({$pesanan->nama_perjalanan}) tanggal {$pesanan->tanggal_trip->translatedFormat('d F Y')}. Mohon buka Panel Guide untuk menerima atau menolak tugas ini.")
        : null;
@endphp

<x-admin.modal nama="popup-email-penugasan" size="sm" x-init="$nextTick(() => buka())" data-popup-email-penugasan>
    <div class="text-center">
        <span @class([
            'mx-auto flex h-14 w-14 items-center justify-center rounded-full',
            'bg-status-completed-bg text-status-completed-teks' => $berhasil,
            'bg-status-dibatalkan-bg text-status-dibatalkan-teks' => ! $berhasil,
        ])>
            <span class="material-symbols-outlined text-3xl" aria-hidden="true">{{ $berhasil ? 'mark_email_read' : 'unsubscribe' }}</span>
        </span>
        <p class="mt-3 text-xs text-muted">{{ session('popup_email_penugasan') }}</p>
        <h2 class="mt-1 text-lg font-bold text-ink">{{ $berhasil ? 'Email penugasan terkirim ke guide' : 'Email penugasan belum sampai ke guide' }}</h2>

        @if ($berhasil)
            <p class="mt-2 text-sm leading-6 text-muted">
                Email untuk <strong class="text-ink">{{ $pesanan->nama_guide }}</strong> sudah diterima server email
                <strong class="break-all text-ink">{{ $email['alamat'] }}</strong> pada {{ $email['waktu'] }}.
            </p>
            <p class="mt-2 rounded-lg bg-page px-3 py-2 text-xs leading-5 text-muted">Bila guide belum melihatnya, minta cek folder Spam atau Promosi. Status konfirmasi guide terpantau di kartu Operasional.</p>
        @else
            <p class="mt-2 text-sm leading-6 text-status-dibatalkan-teks">{{ $email['galat'] ?? 'Email penugasan gagal dikirim.' }}</p>
            <p class="mt-2 rounded-lg bg-page px-3 py-2 text-xs leading-5 text-muted">Guide tetap tercatat bertugas. Kabari lewat WhatsApp atau perbaiki masalahnya lalu kirim ulang email.</p>
        @endif
    </div>

    <x-slot:footer>
        @unless ($berhasil)
            @if ($tautanWaGuide)
                <x-admin.tombol as="a" href="{{ $tautanWaGuide }}" target="_blank" rel="noopener noreferrer" variant="secondary" icon="chat">Kabari lewat WhatsApp</x-admin.tombol>
            @endif
            @if ($email['status'] === 'gagal')
                <x-admin.tombol type="submit" form="kirim-ulang-penugasan-{{ $pesanan->id }}" variant="secondary" icon="send">Kirim ulang</x-admin.tombol>
            @endif
        @endunless
        <x-admin.tombol type="button" x-on:click="tutup()">Mengerti</x-admin.tombol>
    </x-slot:footer>
</x-admin.modal>
