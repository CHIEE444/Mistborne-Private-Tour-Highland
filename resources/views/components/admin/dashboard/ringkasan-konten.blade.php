@props(['jumlahJourney'])

<x-admin.kartu>
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-lg font-bold text-ink">Ringkasan Konten</h2>
        <span class="text-[11px] text-muted">CMS Website</span>
    </div>

    <div class="divide-y divide-surface-border text-sm">
        <a href="{{ route('admin.perjalanan.indeks') }}" class="flex items-center justify-between py-2.5 hover:text-hijau-aksi">
            <span class="flex items-center gap-2"><span class="material-symbols-outlined text-lg text-muted" aria-hidden="true">explore</span> Journey</span>
            <span class="flex items-center gap-1 font-medium">{{ $jumlahJourney }} <span class="material-symbols-outlined text-base text-muted" aria-hidden="true">chevron_right</span></span>
        </a>

        <a href="{{ route('admin.artikel.indeks') }}" class="flex items-center justify-between py-2.5 hover:text-hijau-aksi">
            <span class="flex items-center gap-2"><span class="material-symbols-outlined text-lg text-muted" aria-hidden="true">auto_stories</span> Artikel</span>
            <span class="material-symbols-outlined text-base text-muted" aria-hidden="true">chevron_right</span>
        </a>

        <a href="{{ route('admin.ulasan.indeks') }}" class="flex items-center justify-between py-2.5 hover:text-hijau-aksi">
            <span class="flex items-center gap-2"><span class="material-symbols-outlined text-lg text-muted" aria-hidden="true">rate_review</span> Ulasan</span>
            <span class="material-symbols-outlined text-base text-muted" aria-hidden="true">chevron_right</span>
        </a>
    </div>
</x-admin.kartu>
