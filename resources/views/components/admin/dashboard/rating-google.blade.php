@props(['rating', 'ratingTamu'])

{{-- Dua sumber rating: Google Maps (Places API, butuh kunci di .env) dan formulir ulasan tamu MISTBORNE sendiri,
     jadi kartu tetap berisi data walau Google belum tersambung. --}}
@php
    $bintang = static fn (?float $nilai): array => collect(range(1, 5))->map(fn (int $ke): string => $nilai !== null && $ke <= round($nilai) ? 'star' : 'star_border')->all();
    $terbanyak = max(1, max($ratingTamu['sebaran']));
@endphp

<x-admin.kartu>
    <h2 class="mb-4 text-lg font-bold text-ink">Rating &amp; Ulasan</h2>

    <section aria-labelledby="judul-rating-google" class="rounded-xl border border-surface-border p-3">
        <div class="flex items-center justify-between gap-2">
            <h3 id="judul-rating-google" class="text-sm font-semibold text-ink">Google Maps</h3>
            @if ($rating['tersedia'])
                <span class="rounded-full bg-status-completed-bg px-2 py-0.5 text-[11px] font-semibold text-status-completed-teks">Tersambung</span>
            @else
                <span class="rounded-full bg-status-payment-bg px-2 py-0.5 text-[11px] font-semibold text-status-payment-teks">Belum tersambung</span>
            @endif
        </div>

        @if ($rating['tersedia'])
            <div class="mt-2 flex items-center gap-3">
                <span class="text-3xl font-semibold text-ink">{{ number_format($rating['rating'], 1) }}</span>
                <div>
                    <div class="flex items-center gap-0.5 text-emas">
                        @foreach ($bintang($rating['rating']) as $ikon)
                            <span class="material-symbols-outlined text-lg" aria-hidden="true">{{ $ikon }}</span>
                        @endforeach
                    </div>
                    <p class="text-xs text-muted">{{ number_format($rating['jumlah_ulasan'], 0, ',', '.') }} ulasan Google</p>
                </div>
            </div>
            @if ($rating['tautan'])
                <a href="{{ $rating['tautan'] }}" target="_blank" rel="noopener noreferrer" class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-hijau-aksi hover:underline">
                    Buka di Google Maps <span class="material-symbols-outlined text-[16px]!" aria-hidden="true">open_in_new</span>
                </a>
            @endif
        @else
            {{-- Alasan teknis (kunci .env) sengaja tidak ditampilkan ke admin; lihat RatingGoogleLayanan dan log server. --}}
            <p class="mt-2 text-sm text-muted">Rating Google belum tersambung.</p>
            <p class="mt-1 text-xs text-muted">Rating dari Google Maps tampil otomatis di sini setelah profil Google Bisnis MISTBORNE dihubungkan oleh pengembang.</p>
        @endif
    </section>

    <section aria-labelledby="judul-rating-tamu" class="mt-3 rounded-xl border border-surface-border p-3">
        <div class="flex items-center justify-between gap-2">
            <h3 id="judul-rating-tamu" class="text-sm font-semibold text-ink">Ulasan tamu MISTBORNE</h3>
            <a href="{{ route('admin.ulasan.indeks') }}" class="text-xs font-semibold text-hijau-aksi hover:underline">Lihat semua</a>
        </div>

        @if ($ratingTamu['jumlah'] === 0)
            <p class="mt-2 text-sm text-muted">Belum ada ulasan. Tautan rating dikirim ke tamu setelah trip selesai.</p>
        @else
            <div class="mt-2 flex items-center gap-3">
                <span class="text-3xl font-semibold text-ink">{{ number_format($ratingTamu['rata_rata'], 1) }}</span>
                <div>
                    <div class="flex items-center gap-0.5 text-emas">
                        @foreach ($bintang($ratingTamu['rata_rata']) as $ikon)
                            <span class="material-symbols-outlined text-lg" aria-hidden="true">{{ $ikon }}</span>
                        @endforeach
                    </div>
                    <p class="text-xs text-muted">{{ $ratingTamu['jumlah'] }} ulasan tamu{{ $ratingTamu['menunggu'] > 0 ? ' · '.$ratingTamu['menunggu'].' menunggu persetujuan' : '' }}</p>
                </div>
            </div>

            <dl class="mt-3 space-y-1">
                @foreach ($ratingTamu['sebaran'] as $nilai => $banyak)
                    <div class="flex items-center gap-2 text-xs">
                        <dt class="w-6 shrink-0 text-muted">{{ $nilai }}★</dt>
                        <dd class="h-1.5 flex-1 overflow-hidden rounded-full bg-page"><span class="block h-full rounded-full bg-emas" style="width: {{ round($banyak / $terbanyak * 100) }}%"></span></dd>
                        <dd class="w-5 shrink-0 text-end tabular-nums text-muted">{{ $banyak }}</dd>
                    </div>
                @endforeach
            </dl>

            @if ($ratingTamu['terbaru'])
                <blockquote class="mt-3 border-s-2 border-emas ps-3 text-xs leading-5 text-ink">
                    “{{ \Illuminate\Support\Str::limit($ratingTamu['terbaru']->komentar, 120) }}”
                    <footer class="mt-1 text-muted">{{ $ratingTamu['terbaru']->pesanan?->nama_lengkap ?? 'Tamu' }} · {{ $ratingTamu['terbaru']->rating }}★</footer>
                </blockquote>
            @endif
        @endif
    </section>
</x-admin.kartu>
