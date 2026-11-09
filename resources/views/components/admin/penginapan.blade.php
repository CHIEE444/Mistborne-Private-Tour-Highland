@props(['status' => null, 'nama' => null])

{{-- Ringkasan penginapan tamu: nama hotel bila sudah punya, atau lencana "butuh rekomendasi" bila belum. --}}
@if ($status === \App\Enums\StatusPenginapan::Belum)
    <span {{ $attributes->class(['inline-flex items-center gap-1.5']) }}>
        <span class="rounded-full bg-status-payment-bg px-2 py-0.5 text-[11px] font-semibold text-status-payment-teks">Butuh rekomendasi</span>
        @if (filled($nama))<span>{{ $nama }}</span>@endif
    </span>
@elseif ($status === \App\Enums\StatusPenginapan::Dipesankan)
    <span {{ $attributes->class(['inline-flex flex-wrap items-center gap-1.5']) }}>
        <span class="rounded-full bg-status-completed-bg px-2 py-0.5 text-[11px] font-semibold text-status-completed-teks">Dipesankan MISTBORNE</span>
        <span>{{ filled($nama) ? $nama : 'Hotel belum dipesankan' }}</span>
    </span>
@elseif ($status === \App\Enums\StatusPenginapan::Diurus)
    <span {{ $attributes->class(['inline-flex flex-wrap items-center gap-1.5']) }}>
        <span class="rounded-full bg-status-completed-bg px-2 py-0.5 text-[11px] font-semibold text-status-completed-teks">All-in · diurus MISTBORNE</span>
        <span>{{ filled($nama) ? $nama : 'Hotel belum dipesankan' }}</span>
    </span>
@elseif ($status === \App\Enums\StatusPenginapan::Sudah)
    <span {{ $attributes }}>{{ $nama ?: 'Sudah punya (nama belum diisi)' }}</span>
@else
    <span {{ $attributes }}>{{ $nama ?: '—' }}</span>
@endif
