@php
    $judulHalaman = 'Tanggal Tersedia — '.$perjalanan->nama;
    $hariIni = now()->startOfDay();
    $mendatang = $jadwal->filter(fn ($item) => $item->tanggal->greaterThanOrEqualTo($hariIni))->values();
    $lewat = $jadwal->reject(fn ($item) => $item->tanggal->greaterThanOrEqualTo($hariIni))->sortByDesc('tanggal')->values();
    // Ringkasan memakai status yang sama dengan kolom Status (kuota journey dan guide bebas); tanggal < H-7 tidak dihitung tersedia.
    $statusMendatang = $mendatang->map(fn ($item) => match (true) {
        $item->diblok => 'diblok',
        $item->penuh() => 'penuh',
        default => $kapasitas->get($item->tanggal->toDateString())['status'] ?? null,
    });
    $jumlahTersedia = $statusMendatang->filter(fn ($status) => $status === 'tersedia')->count();
    $jumlahPenuh = $statusMendatang->filter(fn ($status) => in_array($status, ['penuh', 'guide_penuh'], true))->count();
    $jumlahDiblokir = $statusMendatang->filter(fn ($status) => $status === 'diblok')->count();
    $kuotaDefault = \App\Models\Pengaturan::current()->kuota_default;
    $mode = old('mode', 'satu');
    $maksRentang = \App\Http\Requests\Admin\JadwalKeberangkatan\SimpanJadwalRequest::MAKS_HARI_RENTANG;
@endphp
<x-tata-letak.admin :title="$judulHalaman" group="{{ __('admin.sidebar.grup_konten') }}">
    <x-admin.kepala-halaman :title="$judulHalaman" subtitle="Atur tanggal keberangkatan yang bisa dipesan tamu di situs publik">
        <x-slot:actions>
            <x-admin.tombol as="a" href="{{ route('admin.perjalanan.ubah', $perjalanan) }}" variant="secondary" icon="arrow_back">
                Kembali ke Journey
            </x-admin.tombol>
        </x-slot:actions>
    </x-admin.kepala-halaman>

    <div class="mb-6 grid grid-cols-3 gap-3">
        <div class="rounded-admin-kartu border border-surface-border bg-white p-4">
            <p class="text-xs text-muted">Tersedia</p>
            <p class="mt-1 text-2xl font-semibold text-status-completed-teks">{{ $jumlahTersedia }}</p>
        </div>
        <div class="rounded-admin-kartu border border-surface-border bg-white p-4">
            <p class="text-xs text-muted">Penuh</p>
            <p class="mt-1 text-2xl font-semibold text-status-payment-teks">{{ $jumlahPenuh }}</p>
        </div>
        <div class="rounded-admin-kartu border border-surface-border bg-white p-4">
            <p class="text-xs text-muted">Diblokir</p>
            <p class="mt-1 text-2xl font-semibold text-status-dibatalkan-teks">{{ $jumlahDiblokir }}</p>
        </div>
    </div>

    <x-admin.kartu class="mb-6">
        <h2 class="text-sm font-semibold text-ink">Tambah Tanggal</h2>
        <p class="mb-4 text-xs text-muted">Tanggal yang sudah ada otomatis dilewati. Tamu hanya bisa memesan tanggal yang berstatus Tersedia.</p>

        <form
            method="POST"
            action="{{ route('admin.perjalanan.tanggal.simpan', $perjalanan) }}"
            novalidate
            x-data="{
                mode: @js($mode),
                tanggal: @js(old('tanggal', '')),
                mulai: @js(old('tanggal_mulai', '')),
                selesai: @js(old('tanggal_selesai', '')),
                maks: {{ $maksRentang }},
                get jumlah() {
                    if (this.mode === 'satu') return this.tanggal ? 1 : 0;
                    if (! this.mulai || ! this.selesai) return 0;
                    const hari = Math.round((new Date(this.selesai) - new Date(this.mulai)) / 86400000) + 1;
                    return hari > 0 ? hari : 0;
                },
            }"
            class="space-y-5"
        >
            @csrf
            <input type="hidden" name="mode" :value="mode" />

            <div class="grid gap-3 sm:grid-cols-2" role="radiogroup" aria-label="Cara menambah tanggal">
                <button type="button" role="radio" :aria-checked="mode === 'satu'" x-on:click="mode = 'satu'"
                    :class="mode === 'satu' ? 'border-hijau-aksi bg-status-completed-bg' : 'border-surface-border bg-white hover:bg-page'"
                    class="flex items-start gap-3 rounded-lg border p-3 text-start transition-colors">
                    <span class="material-symbols-outlined mt-0.5 text-xl text-hijau-aksi" aria-hidden="true">event</span>
                    <span>
                        <span class="block text-sm font-semibold text-ink">Satu tanggal</span>
                        <span class="block text-xs text-muted">Untuk keberangkatan di satu hari tertentu.</span>
                    </span>
                </button>
                <button type="button" role="radio" :aria-checked="mode === 'rentang'" x-on:click="mode = 'rentang'"
                    :class="mode === 'rentang' ? 'border-hijau-aksi bg-status-completed-bg' : 'border-surface-border bg-white hover:bg-page'"
                    class="flex items-start gap-3 rounded-lg border p-3 text-start transition-colors">
                    <span class="material-symbols-outlined mt-0.5 text-xl text-hijau-aksi" aria-hidden="true">calendar_month</span>
                    <span>
                        <span class="block text-sm font-semibold text-ink">Rentang tanggal</span>
                        <span class="block text-xs text-muted">Satu kali tambah untuk beberapa hari berturut-turut (maks. {{ $maksRentang }} hari).</span>
                    </span>
                </button>
            </div>
            @error('mode')
                <x-admin.formulir.galat :pesan="$message" />
            @enderror

            <div class="grid gap-4 sm:grid-cols-3">
                <div x-show="mode === 'satu'" class="sm:col-span-2">
                    <x-admin.formulir.tanggal name="tanggal" label="Tanggal keberangkatan (mulai hari ini)" :min="now()->toDateString()" x-model="tanggal" />
                </div>
                <div x-show="mode === 'rentang'" x-cloak>
                    <x-admin.formulir.tanggal name="tanggal_mulai" label="Dari tanggal" :min="now()->toDateString()" x-model="mulai" />
                </div>
                <div x-show="mode === 'rentang'" x-cloak>
                    <x-admin.formulir.tanggal name="tanggal_selesai" label="Sampai tanggal (ikut ditambahkan)" :min="now()->toDateString()" x-model="selesai" />
                </div>
                <x-admin.formulir.masukan name="kuota" type="number" label="Kuota per tanggal (opsional)" min="1" max="50" :placeholder="'Kosongkan = default '.$kuotaDefault.' grup'" />
            </div>

            <div class="flex flex-col gap-3 border-t border-surface-border pt-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-muted" aria-live="polite">
                    <span x-show="jumlah === 0">Pilih tanggal untuk melihat berapa yang akan ditambahkan.</span>
                    <span x-show="jumlah > 0" x-cloak>Akan menambahkan <strong class="text-ink" x-text="jumlah"></strong> tanggal.</span>
                </p>
                <x-admin.tombol type="submit" icon="add" x-bind:disabled="jumlah === 0 || (mode === 'rentang' && jumlah > maks)">Tambah Tanggal</x-admin.tombol>
            </div>
        </form>
    </x-admin.kartu>

    @if ($jadwal->isEmpty())
        <x-admin.keadaan-kosong icon="calendar_month" judul="Belum ada tanggal tersedia" keterangan="Tambahkan tanggal pertama lewat formulir di atas agar journey ini bisa dipesan." />
    @else
        <h2 class="mb-3 text-sm font-semibold text-ink">Tanggal Mendatang <span class="font-normal text-muted">({{ $mendatang->count() }})</span></h2>

        @if ($mendatang->isEmpty())
            <p class="mb-6 rounded-lg border border-dashed border-surface-border p-4 text-sm text-muted">Tidak ada tanggal mendatang. Tambahkan tanggal baru di atas.</p>
        @else
            <x-admin.tabel :kolom="['Tanggal', 'Terisi', 'Kuota', 'Status', 'Aksi']" class="mb-8">
                @foreach ($mendatang as $tanggal)
                    @include('admin.perjalanan._baris-tanggal', ['tanggal' => $tanggal, 'perjalanan' => $perjalanan, 'lewat' => false, 'slot' => $kapasitas->get($tanggal->tanggal->toDateString())])
                @endforeach
            </x-admin.tabel>
        @endif

        @if ($lewat->isNotEmpty())
            <h2 class="mb-3 text-sm font-semibold text-muted">Sudah Lewat <span class="font-normal">({{ $lewat->count() }})</span></h2>
            <x-admin.tabel :kolom="['Tanggal', 'Terisi', 'Kuota', 'Status', 'Aksi']">
                @foreach ($lewat as $tanggal)
                    @include('admin.perjalanan._baris-tanggal', ['tanggal' => $tanggal, 'perjalanan' => $perjalanan, 'lewat' => true, 'slot' => null])
                @endforeach
            </x-admin.tabel>
        @endif

        <p class="mt-4 text-xs text-muted">Tamu hanya bisa memesan tanggal di daftar ini. Sisa slot = yang paling kecil dari kuota journey ini dan guide yang masih bebas (1 guide memegang 1 trip sampai selesai). Tanggal hanya bisa diblokir paling lambat H-14 sebelum keberangkatan, dan tanggal yang sudah punya pesanan tidak bisa dihapus.</p>

        {{-- Dialog hapus dirender di luar tabel (satu per tanggal), dibuka tombol ikon hapus di tiap baris. --}}
        @foreach ($jadwal as $tanggal)
            <x-admin.perjalanan.dialog-hapus-tanggal :perjalanan="$perjalanan" :tanggal="$tanggal" :jumlah-pesanan="(int) ($pesananPerTanggal[$tanggal->tanggal->toDateString()] ?? 0)" />
        @endforeach
    @endif
</x-tata-letak.admin>
