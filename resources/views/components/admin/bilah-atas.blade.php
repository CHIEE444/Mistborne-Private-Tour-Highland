@props([
    'title' => null,
    'group' => null,
])

<header class="bilah-atas-admin flex items-center gap-3 px-4 py-4 sm:gap-4 sm:px-6 lg:px-8">
    <button
        type="button"
        x-on:click="toggle()"
        :aria-expanded="terbuka.toString()"
        aria-label="Buka/tutup menu navigasi"
        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-white text-ink shadow-sm hover:bg-rail-hover lg:hidden"
    >
        <span class="material-symbols-outlined" aria-hidden="true">menu</span>
    </button>

    <x-admin.jejak-halaman :group="$group" :title="$title" class="hidden min-w-0 xl:flex" />

    <div class="ms-auto flex min-w-0 flex-1 items-center justify-end gap-3 sm:flex-none sm:justify-start">
        <form method="GET" action="{{ route('admin.pesanan.indeks') }}" class="relative hidden w-full max-w-sm sm:block md:w-72 lg:w-96">
            <input
                type="search"
                name="cari"
                value="{{ request()->routeIs('admin.pesanan.indeks') ? request('cari') : '' }}"
                placeholder="{{ __('admin.umum.cari_placeholder') }}"
                aria-label="Cari pesanan"
                class="h-12 w-full rounded-full border-0 bg-white ps-5 pe-14 text-sm text-ink shadow-sm placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-admin-sidebar"
            />
            <button type="submit" aria-label="Cari" class="absolute end-1.5 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-admin-sidebar text-white transition hover:bg-admin-sidebar-active">
                <span class="material-symbols-outlined text-xl" aria-hidden="true">search</span>
            </button>
        </form>
    </div>

    @php
        $jumlahPeringatan = \App\Models\Pesanan::query()->peringatanH1()->get()->filter(fn ($pesanan) => $pesanan->belumSiapBerangkat() !== [])->count();
        $jumlahPengingat = \App\Models\Pesanan::pengingatBelumTerkirim()->count();
        $notifikasiAwal = app(\App\Layanan\NotifikasiAdminLayanan::class)->ringkasan(auth()->user());
    @endphp
    <div
        class="relative shrink-0"
        x-data="notifikasiAdmin({{ \Illuminate\Support\Js::from($notifikasiAwal) }}, { ringkasan: '{{ route('admin.notifikasi.ringkasan') }}', bacaSemua: '{{ route('admin.notifikasi.baca-semua') }}' })"
        x-on:click.outside="buka = false"
        x-on:keydown.escape.window="buka = false"
    >
        <button
            type="button"
            aria-haspopup="true"
            x-on:click="buka = !buka"
            :aria-expanded="buka.toString()"
            :aria-label="jumlah > 0 ? 'Notifikasi, ' + jumlah + ' belum dibaca' : 'Notifikasi'"
            class="relative flex h-11 w-11 items-center justify-center rounded-full bg-white text-ink shadow-sm transition hover:bg-rail-hover"
        >
            <span class="material-symbols-outlined text-xl" aria-hidden="true">notifications</span>
            <span
                x-cloak
                x-show="jumlah > 0"
                x-text="label"
                class="absolute -end-1 -top-1 flex h-[18px] min-w-[18px] items-center justify-center rounded-full border-2 border-white bg-red-600 px-1 text-[10px] font-bold leading-none text-white"
                aria-hidden="true"
            >{{ $notifikasiAwal['jumlah_belum_dibaca'] > 99 ? '99+' : $notifikasiAwal['jumlah_belum_dibaca'] }}</span>
            @if ($notifikasiAwal['jumlah_belum_dibaca'] === 0 && $jumlahPeringatan + $jumlahPengingat > 0)
                <span x-show="jumlah === 0" class="absolute right-0 top-0 h-2.5 w-2.5 rounded-full border-2 border-white bg-amber-500" aria-hidden="true"></span>
            @endif
        </button>

        <div x-cloak x-show="buka" x-transition class="absolute end-0 top-12 z-30 flex max-h-[min(78vh,42rem)] w-[min(24rem,95vw)] flex-col overflow-hidden rounded-2xl border border-surface-border bg-white shadow-xl">
            <div class="border-b border-surface-border px-5 py-4">
                <div class="flex items-center justify-between gap-3">
                    <p class="font-semibold text-ink">Notifikasi</p>
                    <span x-cloak x-show="jumlah > 0" x-text="jumlah + ' baru'" class="rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700"></span>
                </div>
                <div class="mt-1 flex items-start justify-between gap-3">
                    <p class="text-xs leading-5 text-muted">Permintaan tamu, respons operasional, dan pengingat perjalanan.</p>
                    <button type="button" x-cloak x-show="jumlah > 0" x-on:click="tandaiSemuaDibaca()" class="shrink-0 text-xs font-semibold text-hijau-aksi hover:underline">Tandai semua dibaca</button>
                </div>
            </div>
            <div class="min-h-0 flex-1 overflow-y-auto px-5">
                <div class="divide-y divide-surface-border text-sm">
                    <template x-for="item in daftar" :key="item.id">
                        <form method="POST" :action="item.url_baca">
                            <input type="hidden" name="_token" :value="token" />
                            <button type="submit" class="group w-full py-4 text-start" :class="item.dibaca ? 'text-muted' : 'text-ink'">
                                <span class="flex items-center justify-between gap-3">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide"
                                        :class="{ permintaan: 'bg-amber-50 text-amber-800', pertanyaan: 'bg-violet-50 text-violet-700', kustom: 'bg-rose-50 text-rose-700', guide: 'bg-emerald-50 text-emerald-700' }[item.jenis] ?? 'bg-sky-50 text-sky-700'"
                                    >
                                        <span x-show="!item.dibaca" class="h-1.5 w-1.5 rounded-full bg-red-600" aria-label="Belum dibaca"></span>
                                        <span x-text="item.label"></span>
                                    </span>
                                    <span class="text-[11px] font-normal tabular-nums text-muted" x-text="item.waktu"></span>
                                </span>
                                <span class="mt-2 block text-sm leading-5 group-hover:text-hijau-aksi" :class="item.dibaca ? 'font-medium' : 'font-semibold'" x-text="item.judul"></span>
                                <template x-for="(baris, indeks) in item.baris" :key="indeks">
                                    <span class="mt-1 block text-xs leading-5 text-muted" x-text="baris"></span>
                                </template>
                            </button>
                        </form>
                    </template>
                    <p x-show="daftar.length === 0" class="py-3 text-sm text-muted">Belum ada notifikasi terbaru.</p>
                </div>
                <div class="border-t border-surface-border py-3">
                    <p class="pb-1 text-[11px] font-semibold uppercase tracking-wide text-muted">Pengingat</p>
                    <a href="{{ route('admin.pesanan.indeks', ['dari' => now()->addDay()->toDateString(), 'sampai' => now()->addDay()->toDateString()]) }}" class="flex items-center justify-between rounded-lg px-2 py-2 text-sm text-ink hover:bg-page hover:text-hijau-aksi">
                        <span>Trip besok belum siap · H-1</span><span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs text-amber-800">{{ $jumlahPeringatan }}</span>
                    </a>
                    <a href="{{ route('admin.pesanan.indeks', ['status' => 'payment']) }}" class="flex items-center justify-between rounded-lg px-2 py-2 text-sm text-ink hover:bg-page hover:text-hijau-aksi">
                        <span>Pengingat H-7 / H-3 / H-2</span><span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs text-amber-700">{{ $jumlahPengingat }}</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <a href="{{ route('admin.profil.ubah') }}" class="flex shrink-0 items-center gap-2.5 lg:hidden">
        <x-admin.avatar :user="auth()->user()" />
        <span class="hidden text-start leading-tight sm:block">
            <span class="block text-sm font-semibold text-ink">{{ auth()->user()?->name }}</span>
            <span class="block text-xs text-muted">{{ auth()->user()?->jabatan ?? __('admin.umum.profil') }}</span>
        </span>
    </a>
</header>
