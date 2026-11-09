@props(['pesanan', 'jenis'])

{{-- Satu pengingat ke tamu (H-7/H-3/H-2) di dashboard dan popup pengingat. Tombol dan draft sama persis dengan
     "Pengingat ke tamu" di detail pesanan: sekali klik WhatsApp terbuka dan langsung tercatat terkirim. --}}
@php
    use App\Enums\StatusReservasiHotel;

    $layanan = app(\App\Layanan\PesanWhatsAppLayanan::class);
    $label = ['h7' => 'H-7', 'h3' => 'H-3', 'h2' => 'H-2'][$jenis];
    $teks = match ($jenis) {
        'h7' => $layanan->pengingatH7($pesanan),
        'h3' => $layanan->pengingatH3($pesanan),
        default => $layanan->pengingatH2($pesanan),
    };
    $terkirim = $pesanan->pengingatSudahTerkirim($jenis);
    $sisaHari = (int) today()->diffInDays($pesanan->tanggal_trip->copy()->startOfDay(), false);
    $perluKonfirmasiUlang = $jenis === 'h3' && $pesanan->hotel !== null
        && $pesanan->status_reservasi_hotel === StatusReservasiHotel::Terkonfirmasi && $pesanan->konfirmasi_ulang_hotel_at === null;
@endphp

<div @class([
    'rounded-xl border p-3 text-sm',
    'border-surface-border bg-page/60' => $terkirim,
    'border-hijau-aksi/40 bg-white shadow-sm' => ! $terkirim,
])>
    <div class="flex flex-wrap items-start justify-between gap-2">
        <div class="min-w-0">
            <a href="{{ route('admin.pesanan.detail', $pesanan) }}" class="font-semibold text-ink hover:text-hijau-aksi hover:underline">{{ $pesanan->nama_lengkap }}</a>
            <p class="text-xs text-muted">{{ $pesanan->nama_perjalanan }} · {{ $pesanan->tanggal_trip->translatedFormat('d M Y') }} · <span class="font-mono">{{ $pesanan->kode }}</span></p>
        </div>
        @if ($terkirim)
            <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-status-completed-bg px-2.5 py-1 text-[11px] font-semibold text-status-completed-teks">
                <span class="material-symbols-outlined text-[14px]!" aria-hidden="true">check_circle</span>{{ $label }} terkirim
            </span>
        @else
            <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-amber-700 px-2.5 py-1 text-[11px] font-semibold text-white">
                <span class="material-symbols-outlined text-[14px]!" aria-hidden="true">schedule</span>{{ $label }} · {{ $sisaHari === 1 ? 'besok berangkat' : 'sisa '.$sisaHari.' hari' }}
            </span>
        @endif
    </div>

    @if ($terkirim)
        <form method="POST" action="{{ route('admin.pesanan.pengingat', [$pesanan, $jenis]) }}" class="mt-1">
            @csrf
            @method('PATCH')
            <input type="hidden" name="terkirim" value="0">
            <button type="submit" class="text-[11px] font-medium text-muted underline hover:text-ink">Batalkan tanda</button>
        </form>
    @else
        <x-admin.pesanan.kirim-whatsapp
            class="mt-2"
            :aksi="route('admin.pesanan.pengingat', [$pesanan, $jenis])"
            :isian="['terkirim' => 1]"
            :teks="$teks"
            :nomor="$pesanan->whatsapp"
            penerima="tamu"
            :label="'Kirim pengingat '.$label"
        />
        @if ($perluKonfirmasiUlang)
            <p class="mt-3 text-xs font-semibold text-ink">Konfirmasi ulang ke hotel</p>
            <x-admin.pesanan.kirim-whatsapp
                class="mt-1.5"
                :aksi="route('admin.pesanan.hotel.langkah.tandai', [$pesanan, 'ulang'])"
                :teks="$layanan->konfirmasiUlangKeHotel($pesanan)"
                :nomor="$pesanan->hotel->whatsapp"
                label="Konfirmasi ulang ke hotel"
            />
        @endif
    @endif
</div>
