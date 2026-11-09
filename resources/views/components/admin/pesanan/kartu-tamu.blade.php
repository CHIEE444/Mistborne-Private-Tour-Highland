@props(['pesanan'])

<x-admin.kartu>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <h2 class="text-sm font-semibold text-muted">Data Tamu</h2>
        <div class="flex items-center gap-2">
            <x-admin.tombol as="a" href="{{ route('admin.pesanan.ubah', $pesanan) }}" variant="secondary" size="sm" icon="edit">Edit data</x-admin.tombol>
            <x-admin.tombol type="button" variant="danger" size="sm" icon="delete" x-on:click="window.dispatchEvent(new CustomEvent('buka-modal', { detail: { nama: 'hapus-data-tamu-{{ $pesanan->id }}' } }))">Hapus data</x-admin.tombol>
        </div>
    </div>

    <div class="grid gap-4 text-sm sm:grid-cols-2">
        <div>
            <p class="text-muted">Nama Lengkap</p>
            <p class="font-medium text-ink">{{ $pesanan->nama_lengkap }}</p>
        </div>
        <div>
            <p class="text-muted">WhatsApp</p>
            <a
                href="https://wa.me/{{ preg_replace('/\D/', '', $pesanan->whatsapp) }}"
                target="_blank"
                class="inline-flex items-center gap-1 font-medium text-hijau-aksi hover:underline"
            >
                {{ $pesanan->whatsapp }}
                <span class="material-symbols-outlined text-sm" aria-hidden="true">chat</span>
            </a>
        </div>
        <div>
            <p class="text-muted">Penginapan</p>
            <p class="font-medium text-ink"><x-admin.penginapan :status="$pesanan->status_penginapan" :nama="$pesanan->nama_hotel" /></p>
            @if ($pesanan->alamat_hotel)<p class="text-xs text-muted">{{ $pesanan->alamat_hotel }}</p>@endif
        </div>
        @if ($pesanan->kendaraan_pilihan)
            <div>
                <p class="text-muted">Mobil Destinasi</p>
                <p class="font-medium text-ink">{{ $pesanan->kendaraan_pilihan }}</p>
            </div>
        @endif
        <div>
            <p class="text-muted">Lokasi Pick-up</p>
            <p class="font-medium text-ink">{{ $pesanan->lokasi_pickup }}</p>
        </div>
        <div>
            <p class="text-muted">Jumlah Wisatawan</p>
            <p class="font-medium text-ink">{{ $pesanan->jumlah_wisatawan }} orang</p>
        </div>
        <div>
            <p class="text-muted">Catatan tambahan</p>
            <p class="font-medium text-ink">{{ $pesanan->paket_opsional ?: '—' }}</p>
        </div>
        @if ($pesanan->permintaan_khusus)
            <div class="sm:col-span-2">
                <p class="text-muted">Permintaan Khusus</p>
                <p class="mt-1 rounded-lg bg-page p-3 text-ink">{{ $pesanan->permintaan_khusus }}</p>
            </div>
        @endif
    </div>

    @php
        $statusTrip = $pesanan->statusTripOperasional();
        $jumlahPersonelDitugaskan = $pesanan->jumlahPersonelDitugaskan();
        $jumlahPersonelSelesai = $pesanan->jumlahPersonelSelesai();
    @endphp
    <div class="mt-5 border-t border-surface-border pt-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h3 class="text-sm font-semibold text-ink">Status Trip</h3>
                <p class="mt-1 text-xs text-muted">
                    @if ($jumlahPersonelDitugaskan > 0)
                        {{ $jumlahPersonelSelesai ? 'Guide sudah menyelesaikan trip.' : 'Guide belum menandai trip selesai.' }}
                    @else
                        Trip akan dipantau setelah Guide ditugaskan.
                    @endif
                </p>
            </div>
            <span @class([
                'inline-flex rounded-full px-3 py-1 text-xs font-semibold',
                'bg-emerald-50 text-emerald-700' => $statusTrip === 'tuntas',
                'bg-amber-50 text-amber-800' => $statusTrip === 'sebagian',
                'bg-page text-muted' => in_array($statusTrip, ['belum_ditugaskan', 'menunggu'], true),
            ])>
                {{ match ($statusTrip) {
                    'tuntas' => 'Trip tuntas',
                    'sebagian' => 'Menunggu personel lain',
                    'menunggu' => 'Menunggu trip selesai',
                    default => 'Belum ada penugasan',
                } }}
            </span>
        </div>

        @if ($jumlahPersonelDitugaskan > 0)
            <div class="mt-3 grid gap-2 text-xs sm:grid-cols-2">
                @foreach ([
                    'guide' => ['ditugaskan' => $pesanan->guide_personel_id, 'nama' => $pesanan->nama_guide, 'selesai' => $pesanan->guide_trip_selesai_at],
                ] as $peran => $personel)
                    @if ($personel['ditugaskan'])
                        <div class="rounded-lg bg-page px-3 py-2">
                            <p class="font-medium text-ink">Guide &amp; Driver · {{ $personel['nama'] ?: 'Personel ditugaskan' }}</p>
                            @if ($personel['selesai'])
                                <p class="mt-1 text-emerald-700">Selesai {{ $personel['selesai']->timezone(config('app.timezone'))->translatedFormat('d M Y, H:i') }} WIB</p>
                            @else
                                <p class="mt-1 text-muted">Belum mengonfirmasi trip selesai</p>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</x-admin.kartu>
