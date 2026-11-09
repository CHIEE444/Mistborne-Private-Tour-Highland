@props([
    'pesan',
    'nomorTujuan' => null,
])

<div {{ $attributes->class(['rounded-admin-kartu border border-surface-border bg-page p-4']) }}>
    @if ($nomorTujuan)
        <p class="mb-2 text-xs text-muted">Kirim ke: <span class="font-medium text-ink">{{ $nomorTujuan }}</span></p>
    @endif

    {{-- wrap-anywhere: tautan panjang tanpa spasi (mis. wa.me/?text=...) dipatahkan agar tidak melebarkan halaman. --}}
    <pre class="whitespace-pre-wrap wrap-anywhere font-sans text-sm leading-relaxed text-ink">{{ $pesan }}</pre>
</div>
