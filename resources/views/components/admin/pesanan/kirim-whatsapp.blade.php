@props([
    'aksi',
    'teks',
    'label',
    'nomor' => null,
    'penerima' => 'hotel',
    'tutup' => null,
    // Isian tersembunyi tambahan yang ikut terkirim bersama tanda, mis. ['terkirim' => 1].
    'isian' => [],
    'metode' => 'PATCH',
])

{{--
    Tombol kirim satu klik: tautan WhatsApp berisi draft terbuka di tab baru sekaligus mengirim form penanda ke `aksi`
    (PATCH bawaan, atau `metode` lain), jadi admin tidak perlu menekan "Sudah dikirim" lagi. Dipakai alur Reservasi Hotel,
    pengingat tamu di Aksi Cepat, dan alur pesanan kustom. Bila nomor tujuan kosong, pesan disalin dan dikirim manual lalu ditandai dengan tombol terpisah.
--}}
@php
    $tautan = filled($nomor) ? app(\App\Kontrak\PengirimWhatsApp::class)->buatTautan($nomor, $teks) : null;
@endphp

<div {{ $attributes }} x-data>
    <form x-ref="formTandai" method="POST" action="{{ $aksi }}" class="flex flex-wrap items-center gap-2">
        @csrf
        @if ($metode !== 'POST')
            @method($metode)
        @endif
        @foreach ($isian as $namaIsian => $nilaiIsian)
            <input type="hidden" name="{{ $namaIsian }}" value="{{ $nilaiIsian }}">
        @endforeach

        @if ($tutup)
            <x-admin.tombol type="button" size="sm" icon="send" disabled title="{{ $tutup }}">{{ $label }}</x-admin.tombol>
        @elseif ($tautan)
            <x-admin.tombol as="a" href="{{ $tautan }}" target="_blank" rel="noopener noreferrer" size="sm" icon="send" x-on:click="$refs.formTandai.requestSubmit()">{{ $label }}</x-admin.tombol>
        @else
            <x-admin.tombol type="submit" size="sm" icon="done">Tandai sudah dikirim</x-admin.tombol>
        @endif

        <x-admin.tombol type="button" variant="secondary" size="sm" icon="content_copy" x-on:click="salinTeks({{ \Illuminate\Support\Js::from($teks) }})">Salin Pesan</x-admin.tombol>
    </form>

    @if ($tutup)
        <p class="mt-1.5 text-xs text-status-dibatalkan-teks">{{ $tutup }}</p>
    @elseif (! $tautan)
        <p class="mt-1.5 text-xs text-status-payment-teks">Nomor WhatsApp {{ $penerima }} belum diisi{{ $penerima === 'hotel' ? ' di menu Hotel' : '' }}. Salin pesan, kirim manual, lalu klik Tandai sudah dikirim.</p>
    @else
        <p class="mt-1.5 text-[11px] text-muted">Sekali klik: WhatsApp {{ $penerima }} terbuka dan langsung tercatat terkirim.</p>
    @endif

    <details class="mt-2 rounded-lg border border-surface-border bg-white">
        <summary class="cursor-pointer list-none px-3 py-2 text-xs font-semibold text-muted hover:text-ink">Lihat isi pesan</summary>
        <x-admin.pratinjau-whatsapp :pesan="$teks" :nomor-tujuan="$nomor" class="m-2 mt-0" />
    </details>
</div>
