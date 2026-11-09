<x-tata-letak.personel :personel="$personel">
    <div class="mb-8">
        <p class="text-sm font-medium text-muted">Panel {{ ucfirst($personel->peran) }}</p>
        <h1 class="mt-1 text-2xl font-semibold text-ink">Halo, {{ $personel->nama }}</h1>
        <p class="mt-2 text-sm text-muted">Berikut jadwal perjalanan yang ditugaskan kepada Anda.</p>
    </div>

    <div class="mb-6 grid gap-3 sm:grid-cols-3">
        @foreach ([['Menunggu konfirmasi', $ringkasan['menunggu'], 'schedule'], ['Trip mendatang', $ringkasan['mendatang'], 'event_upcoming'], ['Trip selesai', $ringkasan['selesai'], 'task_alt']] as [$label, $jumlah, $ikon])
            <x-admin.kartu>
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-2xl text-admin-sidebar" aria-hidden="true">{{ $ikon }}</span>
                    <div><p class="text-2xl font-semibold text-ink">{{ $jumlah }}</p><p class="text-xs text-muted">{{ $label }}</p></div>
                </div>
            </x-admin.kartu>
        @endforeach
    </div>

    @if (session('sukses'))
        <x-admin.peringatan type="success" class="mb-5">{{ session('sukses') }}</x-admin.peringatan>
    @endif
    @error('trip')
        <x-admin.peringatan type="warning" class="mb-5">{{ $message }}</x-admin.peringatan>
    @enderror

    @forelse ($pesanan as $item)
        @php
            $statusTugas = $item->{$statusKolom};
            $kolomTripSelesai = 'guide_trip_selesai_at';
            $waktuTripSelesai = $item->{$kolomTripSelesai};
        @endphp
        <article class="mb-4 rounded-2xl border border-surface-border bg-white p-5 shadow-sm sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted">{{ $item->kode }}</p>
                    <h2 class="mt-1 text-lg font-semibold text-ink">{{ $item->nama_perjalanan }}</h2>
                </div>
                <span @class([
                    'rounded-full px-3 py-1 text-xs font-semibold',
                    'bg-status-payment-bg text-status-payment-teks' => $statusTugas === 'menunggu',
                    'bg-status-completed-bg text-status-completed-teks' => $statusTugas === 'diterima' && $waktuTripSelesai,
                    'bg-status-dihubungi-bg text-status-dihubungi-teks' => $statusTugas === 'diterima' && ! $waktuTripSelesai,
                    'bg-status-dibatalkan-bg text-status-dibatalkan-teks' => $statusTugas === 'ditolak',
                ])>{{ match (true) { $statusTugas === 'menunggu' => 'Menunggu konfirmasi', $statusTugas === 'diterima' && $waktuTripSelesai => 'Trip selesai', $statusTugas === 'diterima' => 'Tugas diterima', $statusTugas === 'ditolak' => 'Tugas ditolak', default => 'Belum dikonfirmasi' } }}</span>
            </div>

            <dl class="mt-5 grid gap-4 border-t border-surface-border pt-4 sm:grid-cols-2 lg:grid-cols-3">
                <div><dt class="text-xs text-muted">Tanggal perjalanan</dt><dd class="mt-1 text-sm font-medium text-ink">{{ $item->tanggal_trip->translatedFormat('d M Y') }}@if ($item->tanggal_selesai && ! $item->tanggal_selesai->isSameDay($item->tanggal_trip)) — {{ $item->tanggal_selesai->translatedFormat('d M Y') }} @endif</dd></div>
                <div><dt class="text-xs text-muted">Penjemputan</dt><dd class="mt-1 text-sm font-medium text-ink">{{ $item->waktu_pickup ? substr((string) $item->waktu_pickup, 0, 5) : 'Waktu belum ditentukan' }} · {{ $item->lokasi_pickup }}</dd></div>
                <div><dt class="text-xs text-muted">Jumlah tamu</dt><dd class="mt-1 text-sm font-medium text-ink">{{ $item->jumlah_wisatawan }} orang</dd></div>
                <div><dt class="text-xs text-muted">Nama pemesan</dt><dd class="mt-1 text-sm font-medium text-ink">{{ $item->nama_lengkap }}</dd></div>
                <div><dt class="text-xs text-muted">Kontak tamu</dt><dd class="mt-1 text-sm font-medium text-ink"><a href="https://wa.me/{{ preg_replace('/\D+/', '', $item->whatsapp) }}" target="_blank" rel="noopener" class="text-hijau-aksi hover:underline">{{ $item->whatsapp }}</a></dd></div>
                <div><dt class="text-xs text-muted">Penginapan tamu</dt><dd class="mt-1 text-sm font-medium text-ink">{{ $item->nama_hotel ?: ($item->status_penginapan === \App\Enums\StatusPenginapan::Belum ? 'Belum punya — tamu akan memberi kabar' : '—') }}</dd></div>
                @if ($item->permintaan_khusus)<div class="sm:col-span-2 lg:col-span-3"><dt class="text-xs text-muted">Catatan perjalanan</dt><dd class="mt-1 text-sm text-ink">{{ $item->permintaan_khusus }}</dd></div>@endif
            </dl>

            @if ($statusTugas === 'menunggu')
                <div class="mt-5 flex flex-wrap justify-end gap-3 border-t border-surface-border pt-4">
                    <form method="POST" action="{{ route('personel.pesanan.tanggapi', $item) }}">@csrf<input type="hidden" name="jawaban" value="ditolak"><x-admin.tombol type="submit" variant="secondary">Tolak tugas</x-admin.tombol></form>
                    <form method="POST" action="{{ route('personel.pesanan.tanggapi', $item) }}">@csrf<input type="hidden" name="jawaban" value="diterima"><x-admin.tombol type="submit">Terima tugas</x-admin.tombol></form>
                </div>
            @elseif ($statusTugas === 'diterima' && $waktuTripSelesai)
                <p class="mt-5 rounded-lg border border-status-completed-teks/20 bg-status-completed-bg px-4 py-3 text-sm text-status-completed-teks">
                    Trip ditandai selesai pada {{ $waktuTripSelesai->timezone(config('app.timezone'))->translatedFormat('d M Y, H:i:s') }} WIB.
                </p>
            @elseif ($statusTugas === 'diterima' && ($item->tanggal_selesai ?? $item->tanggal_trip)->isFuture())
                <p class="mt-5 border-t border-surface-border pt-4 text-xs text-muted">Tombol "Trip sudah selesai" aktif mulai {{ ($item->tanggal_selesai ?? $item->tanggal_trip)->translatedFormat('d M Y') }}.</p>
            @elseif ($statusTugas === 'diterima')
                <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-surface-border pt-4">
                    <p class="text-xs text-muted">Gunakan setelah trip dan rombongan benar-benar selesai.</p>
                    <form method="POST" action="{{ route('personel.pesanan.trip-selesai', $item) }}" onsubmit="return confirm('Konfirmasi bahwa trip {{ $item->kode }} sudah selesai? Admin akan menerima notifikasi.')">
                        @csrf
                        <x-admin.tombol type="submit">Trip sudah selesai</x-admin.tombol>
                    </form>
                </div>
            @endif
        </article>
    @empty
        <x-admin.kartu>
            <x-admin.keadaan-kosong icon="event_available" judul="Belum ada penugasan" keterangan="Tugas perjalanan yang diberikan admin akan muncul di sini." />
        </x-admin.kartu>
    @endforelse
</x-tata-letak.personel>
