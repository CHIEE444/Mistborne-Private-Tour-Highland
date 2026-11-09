@props(['hotel'])

@php
    $spesifikasi = [
        ['icon' => 'location_on', 'label' => 'Area', 'nilai' => $hotel->area ?: '–'],
        ['icon' => 'route', 'label' => 'Jarak ke destinasi', 'nilai' => $hotel->jarak_destinasi ?: '–'],
        ['icon' => 'payments', 'label' => 'Tarif / kamar / malam', 'nilai' => $hotel->rentangTarif() ?? 'Belum diisi'],
        ['icon' => 'groups', 'label' => 'Kapasitas kamar', 'nilai' => $hotel->kapasitas_kamar.' orang'.($hotel->tersedia_extra_bed ? ' (+extra bed)' : '')],
        ['icon' => 'schedule', 'label' => 'Check-in / check-out', 'nilai' => $hotel->jamCheckIn().' / '.$hotel->jamCheckOut()],
        ['icon' => 'star', 'label' => 'Rating Google', 'nilai' => $hotel->google_rating ? $hotel->google_rating.' ('.number_format((int) $hotel->google_jumlah_ulasan, 0, ',', '.').' ulasan)' : 'Belum diisi'],
    ];
@endphp

<x-admin.kartu {{ $attributes }}>
    <div class="flex flex-col gap-6 md:flex-row md:items-center">
        <div class="aspect-[4/3] w-full shrink-0 overflow-hidden rounded-2xl bg-page md:w-72">
            @if ($hotel->fotoUrl())
                <img src="{{ $hotel->fotoUrl() }}" alt="{{ $hotel->nama }}" class="h-full w-full object-cover" />
            @else
                <div class="flex h-full w-full items-center justify-center text-muted/50">
                    <span class="material-symbols-outlined text-7xl" aria-hidden="true">hotel</span>
                </div>
            @endif
        </div>

        <div class="min-w-0 flex-1">
            <h2 class="text-3xl font-semibold tracking-tight text-ink">{{ $hotel->nama }}</h2>
            <p class="mt-1 text-sm text-muted">
                <span class="text-[#B08D3A]" aria-label="{{ $hotel->bintang }} bintang">{{ $hotel->labelBintang() }}</span>
                <span aria-hidden="true">·</span> {{ $hotel->area }}
            </p>

            <dl class="mt-6 grid grid-cols-2 gap-x-6 gap-y-5 sm:grid-cols-3">
                @foreach ($spesifikasi as $butir)
                    <div class="flex items-start gap-2.5">
                        <span class="material-symbols-outlined mt-0.5 text-[22px] text-muted" aria-hidden="true">{{ $butir['icon'] }}</span>
                        <div class="min-w-0">
                            <dt class="text-xs text-muted">{{ $butir['label'] }}</dt>
                            <dd class="mt-0.5 text-sm font-medium text-ink">{{ $butir['nilai'] }}</dd>
                        </div>
                    </div>
                @endforeach
            </dl>
        </div>
    </div>
</x-admin.kartu>
