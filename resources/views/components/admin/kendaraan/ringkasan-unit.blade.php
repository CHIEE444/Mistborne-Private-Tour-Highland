@props(['kendaraan'])

@php
    $spesifikasi = [
        ['icon' => 'directions_car', 'label' => 'Jenis', 'nilai' => $kendaraan->labelJenis()],
        ['icon' => 'airline_seat_recline_normal', 'label' => 'Kapasitas', 'nilai' => $kendaraan->kapasitas.' penumpang'],
        ['icon' => 'palette', 'label' => 'Warna', 'nilai' => $kendaraan->warna ?: '–'],
        ['icon' => 'local_gas_station', 'label' => 'Bahan bakar', 'nilai' => $kendaraan->labelBahanBakar() ?? '–'],
        ['icon' => 'speed', 'label' => 'Odometer', 'nilai' => $kendaraan->odometer !== null ? number_format($kendaraan->odometer, 0, ',', '.').' km' : 'Belum diisi'],
        ['icon' => 'calendar_month', 'label' => 'Terdaftar di admin', 'nilai' => $kendaraan->created_at?->translatedFormat('d F Y') ?? '–'],
    ];
@endphp

<x-admin.kartu {{ $attributes }}>
    <div class="flex flex-col gap-6 md:flex-row md:items-center">
        <div class="aspect-[4/3] w-full shrink-0 overflow-hidden rounded-2xl bg-page md:w-72">
            @if ($kendaraan->fotoUrl())
                <img src="{{ $kendaraan->fotoUrl() }}" alt="{{ $kendaraan->nama }}" class="h-full w-full bg-white object-contain" />
            @else
                <div class="flex h-full w-full items-center justify-center text-muted/50">
                    <span class="material-symbols-outlined text-7xl" aria-hidden="true">directions_car</span>
                </div>
            @endif
        </div>

        <div class="min-w-0 flex-1">
            <h2 class="text-3xl font-semibold tracking-tight text-ink">{{ $kendaraan->plat_nomor }}</h2>
            <p class="mt-1 text-sm text-muted">
                {{ $kendaraan->nama }}
                @if ($kendaraan->tahun)
                    <span aria-hidden="true">·</span> {{ $kendaraan->tahun }}
                @endif
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
