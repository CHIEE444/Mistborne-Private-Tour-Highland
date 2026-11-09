@php
    // Tab mengikuti tahap alur pesanan (Pesanan::TAHAP), bukan status mentah di database.
    $tahapTab = request('tahap');
    $tabTahap = [['nilai' => null, 'label' => 'Semua'], ...collect(\App\Models\Pesanan::TAHAP)->map(fn (string $label, string $nilai): array => ['nilai' => $nilai, 'label' => $label])->values()->all()];
@endphp
<x-tata-letak.admin title="{{ __('admin.sidebar.pesanan') }}" group="{{ __('admin.sidebar.grup_operasional') }}">
    <x-admin.kepala-halaman
        :title="__('admin.sidebar.pesanan')"
        subtitle="Kelola semua booking dari form dan input manual"
    >
        <x-slot:actions>
            <x-admin.tombol as="a" href="{{ route('admin.permintaan-pesanan.indeks') }}" variant="secondary" icon="assignment">
                Permintaan Form
                <span
                    x-cloak
                    x-show="$store.badge.permintaan > 0"
                    x-text="$store.badge.permintaan > 99 ? '99+' : $store.badge.permintaan"
                    :aria-label="$store.badge.permintaan + ' permintaan baru'"
                    class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1.5 text-[11px] font-bold leading-none text-white"
                ></span>
            </x-admin.tombol>
            <x-admin.pesanan.kirim-formulir />
            <x-admin.tombol as="a" href="{{ route('admin.pesanan.buat') }}" icon="add">Pesanan Baru (manual)</x-admin.tombol>
        </x-slot:actions>
    </x-admin.kepala-halaman>

    <x-admin.tab class="mb-6 overflow-x-auto">
        @foreach ($tabTahap as $tab)
            <x-admin.tab-item
                href="{{ route('admin.pesanan.indeks', $tab['nilai'] ? ['tahap' => $tab['nilai']] : []) }}"
                :active="($tahapTab ?: null) === $tab['nilai']"
                :badge="$tab['nilai'] ? ($jumlahPerTahap[$tab['nilai']] ?? 0) : array_sum($jumlahPerTahap)"
                class="whitespace-nowrap"
            >
                {{ $tab['label'] }}
            </x-admin.tab-item>
        @endforeach
    </x-admin.tab>

    <x-admin.bilah-filter as="form" method="GET" action="{{ route('admin.pesanan.indeks') }}" class="mb-6">
        <input type="hidden" name="tahap" value="{{ $tahapTab }}" />

        <div class="relative flex-1">
            <span class="material-symbols-outlined pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 text-lg text-muted" aria-hidden="true">search</span>
            <input
                type="search"
                name="cari"
                value="{{ request('cari') }}"
                placeholder="Cari kode, nama, WhatsApp..."
                class="w-full rounded-full border border-surface-border bg-white py-2.5 ps-10 pe-4 text-sm text-ink placeholder:text-muted focus:border-hijau-aksi focus:outline-none focus:ring-1 focus:ring-hijau-aksi"
            />
        </div>

        <select name="journey" class="rounded-full border border-surface-border bg-white px-4 py-2.5 text-sm text-ink">
            <option value="">Semua Journey</option>
            @foreach ($daftarJourney as $journey)
                <option value="{{ $journey->id }}" @selected((string) request('journey') === (string) $journey->id)>{{ $journey->nama }}</option>
            @endforeach
        </select>

        <select name="sumber" aria-label="Sumber pesanan" class="rounded-full border border-surface-border bg-white py-2.5 ps-4 pe-10 text-sm text-ink">
            <option value="">Semua Sumber</option>
            <option value="form" @selected(request('sumber') === 'form')>Form</option>
            <option value="manual" @selected(request('sumber') === 'manual')>Manual</option>
            <option value="kustom" @selected(request('sumber') === 'kustom')>Kustom</option>
        </select>

        <select name="penugasan" aria-label="Penugasan Guide" class="rounded-full border border-surface-border bg-white py-2.5 ps-4 pe-10 text-sm text-ink">
            <option value="">Semua Penugasan</option>
            <option value="ditolak" @selected(request('penugasan') === 'ditolak')>Ada yang menolak</option>
            <option value="menunggu" @selected(request('penugasan') === 'menunggu')>Menunggu konfirmasi</option>
            <option value="belum" @selected(request('penugasan') === 'belum')>Belum lengkap (sudah bayar)</option>
        </select>

        <select name="penginapan" aria-label="Penginapan tamu" class="rounded-full border border-surface-border bg-white py-2.5 ps-4 pe-10 text-sm text-ink">
            <option value="">Semua Penginapan</option>
            <option value="belum" @selected(request('penginapan') === 'belum')>Butuh rekomendasi penginapan</option>
            <option value="hotel" @selected(request('penginapan') === 'hotel')>Reservasi hotel belum terkonfirmasi</option>
        </select>

        <select name="hari" aria-label="Sisa hari ke keberangkatan" class="rounded-full border border-surface-border bg-white py-2.5 ps-4 pe-10 text-sm text-ink">
            <option value="">Semua Sisa Hari</option>
            <option value="7" @selected(request('hari') === '7')>H-7</option>
            <option value="3" @selected(request('hari') === '3')>H-3</option>
            <option value="2" @selected(request('hari') === '2')>H-2</option>
        </select>

        <select name="aturan" aria-label="Aturan batal dan ganti jadwal" class="rounded-full border border-surface-border bg-white py-2.5 ps-4 pe-10 text-sm text-ink">
            <option value="">Semua Aturan</option>
            <option value="batal_gratis" @selected(request('aturan') === 'batal_gratis')>Batal gratis (refund 100%, ≥H-7)</option>
            <option value="batal_separuh" @selected(request('aturan') === 'batal_separuh')>Batal refund 50% (H-6 s/d H-2)</option>
            <option value="ganti_jadwal" @selected(request('aturan') === 'ganti_jadwal')>Bisa ganti jadwal</option>
            <option value="terkunci" @selected(request('aturan') === 'terkunci')>Jadwal terkunci (sudah bayar, ≤H-7)</option>
        </select>

        <x-admin.rentang-tanggal :dari="request('dari')" :sampai="request('sampai')" />

        <x-admin.tombol type="submit" variant="secondary">Terapkan</x-admin.tombol>

        @if (request()->anyFilled(['cari', 'journey', 'sumber', 'penugasan', 'penginapan', 'hari', 'aturan', 'dari', 'sampai']))
            <x-admin.tombol as="a" href="{{ route('admin.pesanan.indeks', array_filter(['tahap' => $tahapTab])) }}" variant="reset" icon="restart_alt">Reset</x-admin.tombol>
        @endif
    </x-admin.bilah-filter>

    @if ($pesanan->isEmpty())
        <x-admin.keadaan-kosong
            icon="receipt_long"
            judul="Belum ada pesanan"
            keterangan="Pesanan dari form publik atau input manual akan muncul di sini."
        >
            <x-slot:aksi>
                <x-admin.tombol as="a" href="{{ route('admin.pesanan.buat') }}" icon="add">Pesanan Baru (manual)</x-admin.tombol>
            </x-slot:aksi>
        </x-admin.keadaan-kosong>
    @else
        <x-admin.tabel :kolom="['Kode', 'Tamu', 'Journey', 'Tanggal Trip', 'Jumlah', 'Status', 'Sumber', 'Penanda', 'Aksi']">
            @foreach ($pesanan as $item)
                <x-admin.pesanan.baris-pesanan :pesanan="$item" :penanda="$penanda[$item->id] ?? []" />
            @endforeach
        </x-admin.tabel>

        <x-admin.paginasi :paginator="$pesanan" class="mt-6" />
    @endif
</x-tata-letak.admin>
