@props(['pesanan', 'lewatBatasBayar' => collect(), 'tripBelumSiap' => collect(), 'kelebihanGuide' => collect()])

{{-- Popup "Perlu perhatian": lewat batas bayar, trip H-3 yang belum siap, kekurangan guide, dan pengingat H-7/H-3/H-2.
     Terbuka otomatis sekali per sesi (tidak saat ada toast); "Nanti saja" menunda sampai besok. --}}
@php
    $batasBayar = app(\App\Layanan\BatasBayarLayanan::class);
    $layanan = app(\App\Layanan\PesanWhatsAppLayanan::class);
    $pengirim = app(\App\Kontrak\PengirimWhatsApp::class);
    $kunciTampil = collect()
        ->concat($lewatBatasBayar->map(fn ($item): string => 'bayar-'.$item->id))
        ->concat($tripBelumSiap->map(fn ($item): string => 'siap-'.$item->id.'-'.count($item->syaratTripBelumTerpenuhi())))
        ->concat($kelebihanGuide->map(fn (array $rentang): string => 'guide-'.$rentang['mulai']->toDateString().'-'.$rentang['trip'].'-'.$rentang['guide']))
        ->concat($pesanan->map(fn ($item): string => $item->id.'-'.$item->tahapPengingatBerlaku()))
        ->values();
    $total = $kunciTampil->count();
@endphp

<x-admin.modal nama="popup-pengingat" size="lg">
    <x-slot:header>
        <div class="flex items-center gap-3">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-amber-700 text-white ring-4 ring-amber-700/15">
                <span class="material-symbols-outlined" aria-hidden="true">notifications</span>
            </span>
            <div>
                <p class="font-semibold text-ink">{{ $total > 0 ? "Perlu perhatian hari ini ({$total})" : 'Pengingat hari ini' }}</p>
                <p class="mt-0.5 text-xs text-muted">{{ $total > 0 ? 'Tangani dari atas: pembayaran yang lewat batas, trip yang belum siap, lalu pengingat ke tamu.' : 'Semua sudah ditangani.' }}</p>
            </div>
        </div>
    </x-slot:header>

    <div class="space-y-6">
        @if ($total === 0)
            <x-admin.keadaan-kosong icon="task_alt" judul="Tidak ada yang perlu ditangani hari ini" keterangan="Pembayaran, kesiapan trip, guide, dan pengingat H-7/H-3/H-2 ke tamu sudah beres." />
        @endif

        @if ($lewatBatasBayar->isNotEmpty())
            <section aria-labelledby="judul-lewat-bayar">
                <h3 id="judul-lewat-bayar" class="mb-2 flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide text-status-dibatalkan-teks">
                    <span class="material-symbols-outlined text-[16px]!" aria-hidden="true">error</span>
                    {{ $lewatBatasBayar->count() }} tamu lewat batas bayar
                </h3>
                <div class="space-y-2">
                    @foreach ($lewatBatasBayar as $item)
                        @php $batasBatal = $batasBayar->batasPembatalan($item); @endphp
                        <div class="rounded-xl border border-status-dibatalkan-teks/30 bg-status-dibatalkan-bg/40 p-3 text-sm">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <a href="{{ route('admin.pesanan.detail', $item) }}" class="font-semibold text-ink hover:underline">{{ $item->nama_lengkap }}</a>
                                    <p class="text-xs text-muted">{{ $item->nama_perjalanan }} · {{ $item->tanggal_trip->translatedFormat('d M Y') }} · <span class="font-mono">{{ $item->kode }}</span></p>
                                </div>
                                <span class="shrink-0 rounded-full bg-status-dibatalkan-teks px-2.5 py-1 text-[11px] font-semibold text-white">Batal otomatis {{ $batasBatal->translatedFormat('d M') }}</span>
                            </div>
                            <p class="mt-1.5 text-xs text-muted">Dikonfirmasi {{ $item->dikonfirmasi_at->translatedFormat('d M Y') }}, batas bayar {{ $batasBayar->batasWaktu($item)->translatedFormat('d M Y') }}. Belum ada transfer sama sekali.</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <x-admin.tombol as="a" href="{{ $pengirim->buatTautan($item->whatsapp, $layanan->peringatanLewatBatasBayar($item)) }}" target="_blank" rel="noopener noreferrer" size="sm" icon="chat">Tagih via WhatsApp</x-admin.tombol>
                                <x-admin.tombol as="a" href="{{ route('admin.pesanan.detail', $item) }}" variant="secondary" size="sm" icon="payments">Catat pembayaran</x-admin.tombol>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($tripBelumSiap->isNotEmpty())
            <section aria-labelledby="judul-trip-belum-siap">
                <h3 id="judul-trip-belum-siap" class="mb-2 flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide text-status-dibatalkan-teks">
                    <span class="material-symbols-outlined text-[16px]!" aria-hidden="true">event_busy</span>
                    {{ $tripBelumSiap->count() }} trip belum bisa dilaksanakan
                </h3>
                <div class="space-y-2">
                    @foreach ($tripBelumSiap as $item)
                        <div class="rounded-xl border border-status-dibatalkan-teks/30 bg-white p-3 text-sm">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <a href="{{ route('admin.pesanan.detail', $item) }}" class="font-semibold text-ink hover:underline">{{ $item->nama_lengkap }}</a>
                                    <p class="text-xs text-muted">{{ $item->nama_perjalanan }} · {{ $item->tanggal_trip->translatedFormat('d M Y') }} · <span class="font-mono">{{ $item->kode }}</span></p>
                                </div>
                                <span class="shrink-0 rounded-full bg-amber-700 px-2.5 py-1 text-[11px] font-semibold text-white">{{ $item->tanggal_trip->isToday() ? 'Hari keberangkatan' : ($item->tripSudahDimulai() ? 'Hari H sudah lewat' : $item->labelJarakTrip()) }}</span>
                            </div>
                            <ul class="mt-2 flex flex-wrap gap-1.5">
                                @foreach ($item->syaratTripBelumTerpenuhi() as $syarat)
                                    <li class="inline-flex items-center gap-1 rounded-full bg-status-dibatalkan-bg px-2 py-0.5 text-[11px] font-semibold text-status-dibatalkan-teks">
                                        <span class="material-symbols-outlined text-[13px]!" aria-hidden="true">cancel</span>{{ $syarat }}
                                    </li>
                                @endforeach
                            </ul>
                            <x-admin.tombol as="a" href="{{ route('admin.pesanan.detail', $item) }}" variant="secondary" size="sm" icon="arrow_forward" class="mt-2">Bereskan di detail pesanan</x-admin.tombol>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($kelebihanGuide->isNotEmpty())
            <section aria-labelledby="judul-guide-kurang">
                <h3 id="judul-guide-kurang" class="mb-2 flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide text-status-dibatalkan-teks">
                    <span class="material-symbols-outlined text-[16px]!" aria-hidden="true">group</span>
                    Guide tidak cukup di {{ $kelebihanGuide->count() }} rentang tanggal
                </h3>
                <div class="space-y-2">
                    @foreach ($kelebihanGuide as $rentang)
                        <div class="rounded-xl border border-status-dibatalkan-teks/30 bg-white p-3 text-sm">
                            <p class="font-semibold text-ink">{{ $rentang['mulai']->translatedFormat('d M Y') }}{{ $rentang['akhir']->isSameDay($rentang['mulai']) ? '' : ' – '.$rentang['akhir']->translatedFormat('d M Y') }}</p>
                            <p class="mt-0.5 text-xs text-status-dibatalkan-teks">{{ $rentang['trip'] }} trip berjalan, guide siap hanya {{ $rentang['guide'] }}. Tambah atau aktifkan guide, perbarui SIM, atau pindahkan jadwal salah satu pesanan.</p>
                            <x-admin.tombol as="a" href="{{ route('admin.kalender-trip.indeks', ['bulan' => $rentang['mulai']->format('Y-m'), 'tanggal' => $rentang['mulai']->toDateString()]) }}" variant="secondary" size="sm" icon="calendar_month" class="mt-2">Lihat di Kalender Trip</x-admin.tombol>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($pesanan->isNotEmpty())
            <section aria-labelledby="judul-pengingat-tamu">
                <h3 id="judul-pengingat-tamu" class="mb-2 flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide text-amber-800">
                    <span class="material-symbols-outlined text-[16px]!" aria-hidden="true">schedule</span>
                    {{ $pesanan->count() }} pengingat ke tamu belum dikirim
                </h3>
                <div class="space-y-2">
                    @foreach ($pesanan as $item)
                        <x-admin.dashboard.baris-pengingat :pesanan="$item" :jenis="$item->tahapPengingatBerlaku()" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    <x-slot:footer>
        @if ($total > 0)
            {{-- Atribut komponen tidak memproses @js(), jadi data disiapkan sebagai JSON lalu dicetak lewat {{ }} (di-escape, aman di atribut). --}}
            @php $nantiSaja = json_encode(['tanggal' => today()->toDateString(), 'kunci' => $kunciTampil->all()]); @endphp
            <x-admin.tombol variant="secondary" data-nanti-saja x-on:click="tutup(); try { localStorage.setItem('mistborne-pengingat-ditutup', '{{ $nantiSaja }}'); } catch (e) {}">Nanti saja</x-admin.tombol>
            <x-admin.tombol as="a" href="#pengingat-tamu" icon="arrow_forward" x-on:click="tutup()">Lihat di kartu pengingat</x-admin.tombol>
        @else
            <x-admin.tombol variant="secondary" x-on:click="tutup()">Tutup</x-admin.tombol>
        @endif
    </x-slot:footer>
</x-admin.modal>

@if ($total > 0 && ! session()->has('sukses') && ! session()->has('gagal') && ! $errors->any())
    {{-- Buka otomatis sekali per sesi untuk isi yang sama, kecuali "Nanti saja" hari ini. --}}
    <div x-data x-init="
        const kunciSekarang = @js($kunciTampil);
        const tercakup = (simpanan) => simpanan && kunciSekarang.every((kunci) => (simpanan.kunci || []).includes(kunci));
        let ditutup = null;
        let dilihat = null;
        try { ditutup = JSON.parse(localStorage.getItem('mistborne-pengingat-ditutup') || 'null'); } catch (e) {}
        try { dilihat = JSON.parse(sessionStorage.getItem('mistborne-pengingat-dilihat') || 'null'); } catch (e) {}
        const sudahDitutup = ditutup && ditutup.tanggal === @js(today()->toDateString()) && tercakup(ditutup);
        if (! sudahDitutup && ! tercakup(dilihat)) {
            try { sessionStorage.setItem('mistborne-pengingat-dilihat', JSON.stringify({ kunci: kunciSekarang })); } catch (e) {}
            setTimeout(() => window.dispatchEvent(new CustomEvent('buka-modal', { detail: { nama: 'popup-pengingat' } })), 600);
        }
    "></div>
@endif
