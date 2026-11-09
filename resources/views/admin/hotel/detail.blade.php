@php
    $galeri = $hotel->galeriPublik();
    $tipeKamar = $hotel->punyaTipeKamarTerdaftar() ? $hotel->daftarTipeKamar() : [];
    $kontak = [
        ['icon' => 'chat', 'label' => 'WhatsApp', 'nilai' => $hotel->whatsapp],
        ['icon' => 'mail', 'label' => 'Email', 'nilai' => $hotel->email],
        ['icon' => 'person', 'label' => 'Nama PIC', 'nilai' => $hotel->nama_pic],
    ];
@endphp
<x-tata-letak.admin :title="$hotel->nama" group="Operasional">
    <x-admin.kepala-halaman :title="$hotel->nama" :subtitle="$hotel->area">
        <x-slot:actions>
            <span @class([
                'rounded-full px-3 py-1.5 text-xs font-semibold',
                'bg-status-completed-bg text-status-completed-teks' => $hotel->aktif,
                'bg-page text-muted' => ! $hotel->aktif,
            ])>{{ $hotel->aktif ? 'Aktif' : 'Nonaktif' }}</span>
            <x-admin.tombol as="a" href="{{ $hotel->petaUrl() }}" target="_blank" rel="noopener" variant="secondary" icon="map">Lihat Peta</x-admin.tombol>
            <x-admin.tombol as="a" href="{{ route('admin.hotel.ubah', $hotel) }}" icon="edit">Ubah</x-admin.tombol>
        </x-slot:actions>
    </x-admin.kepala-halaman>

    <div class="grid gap-6 xl:grid-cols-3">
        <x-admin.hotel.ringkasan-hotel :hotel="$hotel" class="xl:col-span-2" />

        <div class="grid grid-cols-2 gap-4">
            <x-admin.kartu-statistik label="Reservasi mendatang" :value="$statistik['reservasi_mendatang'].' reservasi'" icon="event" warna="dihubungi" />
            <x-admin.kartu-statistik label="Reservasi selesai" :value="$statistik['reservasi_selesai'].' reservasi'" icon="task_alt" warna="completed" />
            <x-admin.kartu-statistik label="Tipe kamar" :value="count($tipeKamar).' tipe'" icon="hotel" warna="payment" />
            <x-admin.kartu-statistik label="Journey tertaut" :value="$hotel->perjalanan->count().' journey'" icon="route" warna="hold-schedule" />
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <x-admin.kartu class="xl:col-span-2">
            <h2 class="flex items-center gap-2 text-base font-semibold text-ink">
                <span class="material-symbols-outlined text-xl text-muted" aria-hidden="true">info</span>
                Tentang Hotel
            </h2>
            <p class="mt-3 text-sm leading-relaxed text-ink">{{ $hotel->deskripsi ?: 'Deskripsi belum diisi.' }}</p>

            <dl class="mt-5 grid gap-4 border-t border-surface-border pt-5 sm:grid-cols-2">
                <div>
                    <dt class="text-xs text-muted">Alamat</dt>
                    <dd class="mt-0.5 text-sm font-medium text-ink">{{ $hotel->alamat ?: '–' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-muted">Kebijakan pembatalan</dt>
                    <dd class="mt-0.5 text-sm font-medium text-ink">{{ $hotel->kebijakan_pembatalan ?: 'Belum diisi' }}</dd>
                </div>
                @if ($hotel->catatan)
                    <div class="sm:col-span-2">
                        <dt class="text-xs text-muted">Catatan internal</dt>
                        <dd class="mt-0.5 text-sm text-ink">{{ $hotel->catatan }}</dd>
                    </div>
                @endif
            </dl>
        </x-admin.kartu>

        <x-admin.kartu>
            <h2 class="flex items-center gap-2 text-base font-semibold text-ink">
                <span class="material-symbols-outlined text-xl text-muted" aria-hidden="true">call</span>
                Kontak Hotel
            </h2>
            <dl class="mt-4 space-y-4">
                @foreach ($kontak as $butir)
                    <div class="flex items-start gap-2.5">
                        <span class="material-symbols-outlined mt-0.5 text-[22px] text-muted" aria-hidden="true">{{ $butir['icon'] }}</span>
                        <div class="min-w-0">
                            <dt class="text-xs text-muted">{{ $butir['label'] }}</dt>
                            <dd class="mt-0.5 break-words text-sm font-medium text-ink">{{ $butir['nilai'] ?: 'Belum diisi' }}</dd>
                        </div>
                    </div>
                @endforeach
            </dl>
        </x-admin.kartu>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <x-admin.kartu>
            <h2 class="flex items-center gap-2 text-base font-semibold text-ink">
                <span class="material-symbols-outlined text-xl text-muted" aria-hidden="true">hotel</span>
                Tipe Kamar
            </h2>
            @if ($tipeKamar === [])
                <p class="mt-3 text-sm text-muted">Belum ada tipe kamar terdaftar. Reservasi memakai kamar standar.</p>
            @else
                <ul class="mt-3 space-y-2">
                    @foreach ($tipeKamar as $tipe)
                        <li class="flex items-center justify-between gap-3 rounded-xl border border-surface-border px-3.5 py-3">
                            <span class="text-sm font-medium text-ink">{{ $tipe['nama'] }}</span>
                            <span class="shrink-0 text-xs text-muted">{{ $tipe['kapasitas'] }} orang</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-admin.kartu>

        <x-admin.kartu>
            <h2 class="flex items-center gap-2 text-base font-semibold text-ink">
                <span class="material-symbols-outlined text-xl text-muted" aria-hidden="true">concierge</span>
                Fasilitas
            </h2>
            @if ($hotel->labelFasilitas() === [])
                <p class="mt-3 text-sm text-muted">Fasilitas belum diisi.</p>
            @else
                <ul class="mt-3 flex flex-wrap gap-2">
                    @foreach ($hotel->labelFasilitas() as $fasilitas)
                        <li class="rounded-full bg-page px-3 py-1.5 text-xs font-medium text-ink">{{ $fasilitas }}</li>
                    @endforeach
                </ul>
            @endif
        </x-admin.kartu>

        <x-admin.kartu>
            <h2 class="flex items-center gap-2 text-base font-semibold text-ink">
                <span class="material-symbols-outlined text-xl text-muted" aria-hidden="true">route</span>
                Journey Tertaut
            </h2>
            @if ($hotel->perjalanan->isEmpty())
                <p class="mt-3 text-sm text-muted">Belum ditautkan ke journey, jadi tamu belum melihat hotel ini.</p>
            @else
                <ul class="mt-3 space-y-2">
                    @foreach ($hotel->perjalanan as $journey)
                        <li class="rounded-xl border border-surface-border px-3.5 py-3">
                            <a href="{{ route('admin.perjalanan.ubah', $journey) }}" class="text-sm font-medium text-hijau-aksi hover:underline">{{ \Illuminate\Support\Str::title(mb_strtolower($journey->nama)) }}</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-admin.kartu>
    </div>

    @if ($galeri !== [])
        <x-admin.kartu class="mt-6">
            <h2 class="flex items-center gap-2 text-base font-semibold text-ink">
                <span class="material-symbols-outlined text-xl text-muted" aria-hidden="true">photo_library</span>
                Galeri
            </h2>
            <ul class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
                @foreach ($galeri as $foto)
                    <li>
                        <img src="{{ $foto['url'] }}" alt="{{ $hotel->nama }} — {{ $foto['label'] }}" loading="lazy" class="aspect-[4/3] w-full rounded-xl object-cover" />
                        <p class="mt-1 text-xs text-muted">{{ $foto['label'] }}</p>
                    </li>
                @endforeach
            </ul>
        </x-admin.kartu>
    @endif
</x-tata-letak.admin>
