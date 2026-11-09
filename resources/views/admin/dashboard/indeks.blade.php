<x-tata-letak.admin title="{{ __('admin.sidebar.dashboard') }}">
    <x-admin.kepala-halaman
        :title="__('admin.sidebar.dashboard')"
        subtitle="Ringkasan jadwal dan pesanan yang perlu ditangani."
    >
        <x-slot:actions>
            @php $jumlahPerluPerhatian = $lewatBatasBayar->count() + $tripBelumSiap->count() + $kelebihanGuide->count() + $pengingatBelumTerkirim->count(); @endphp
            {{-- Membuka lagi popup pengingat (mis. setelah "Nanti saja") tanpa meninggalkan dashboard; toast yang masih tampil ditutup agar tidak menimpa popup. --}}
            <x-admin.tombol
                :variant="$jumlahPerluPerhatian > 0 ? 'reset' : 'secondary'"
                icon="notifications"
                x-data
                x-on:click="window.dispatchEvent(new CustomEvent('notifikasi-bersihkan')); window.dispatchEvent(new CustomEvent('buka-modal', { detail: { nama: 'popup-pengingat' } }))"
            >
                Pengingat Hari Ini
                @if ($jumlahPerluPerhatian > 0)
                    <span class="rounded-full bg-white px-2 py-0.5 text-xs font-bold text-amber-800 tabular-nums">{{ $jumlahPerluPerhatian }}</span>
                @endif
            </x-admin.tombol>
        </x-slot:actions>
    </x-admin.kepala-halaman>

    <x-admin.dashboard.popup-pengingat :pesanan="$pengingatBelumTerkirim" :lewat-batas-bayar="$lewatBatasBayar" :trip-belum-siap="$tripBelumSiap" :kelebihan-guide="$kelebihanGuide" />

    <x-admin.dashboard.sambutan class="mb-4" />

    <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-admin.kartu-statistik label="Pesanan Baru" pembanding="vs minggu lalu" :value="$pesananBaru['jumlah']" icon="person_add" :tren="$pesananBaru['tren']" :tren-positif="$pesananBaru['positif']" :sparkline="$pesananBaru['sparkline']" warna="dihubungi" />
        <x-admin.kartu-statistik label="Sudah Bayar" pembanding="vs minggu lalu" :value="$sudahPayment['jumlah']" icon="check_circle" :tren="$sudahPayment['tren']" :tren-positif="$sudahPayment['positif']" :sparkline="$sudahPayment['sparkline']" warna="payment" />
        <x-admin.kartu-statistik label="Pesanan Kustom Baru" pembanding="vs minggu lalu" :value="$pesananKustomBaru['jumlah']" icon="tune" :tren="$pesananKustomBaru['tren']" :tren-positif="$pesananKustomBaru['positif']" :sparkline="$pesananKustomBaru['sparkline']" warna="hold-schedule" />
        <x-admin.kartu-statistik sorot label="Pendapatan Bulan Ini" pembanding="vs bulan lalu" :value="\App\Bantuan\FormatRupiah::dari($pendapatanBulanIni['jumlah'])" icon="payments" :tren="$pendapatanBulanIni['tren']" :tren-positif="$pendapatanBulanIni['positif']" :sparkline="$pendapatanBulanIni['sparkline']" warna="completed" />
    </div>

    {{-- Tugas yang harus dituntaskan admin diletakkan tepat di bawah statistik agar langsung terlihat. --}}
    <x-admin.dashboard.selesaikan-hotel :pesanan="$selesaikanHotel" class="mb-4" />

    <div class="mb-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <x-admin.kartu class="lg:col-span-2">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-ink">Pesanan per Bulan</h2>
                    <p class="text-xs text-muted">Perbandingan volume pesanan diterima vs pembatalan</p>
                </div>
                <form method="GET">
                    <select aria-label="Pilih tahun grafik" name="tahun" onchange="this.form.submit()" class="min-w-24 appearance-none rounded-lg border border-surface-border bg-white px-3 py-2 text-sm font-medium leading-5 text-ink">
                        @foreach (range(now()->year, now()->year - 2) as $tahun)
                            <option value="{{ $tahun }}" @selected($tahunGrafik === $tahun)>{{ $tahun }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="mb-3 flex items-center gap-4 text-xs text-muted">
                <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-sage"></span> Total Dipesan: {{ $grafikPesanan->sum('nilai') }}</span>
                <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-status-dibatalkan-teks"></span> Total Dibatalkan: {{ $grafikPesanan->sum('nilaiKedua') }}</span>
            </div>

            <x-admin.grafik-batang :data="$grafikPesanan" />
        </x-admin.kartu>

        <x-admin.kartu>
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-bold text-ink">Status Pesanan</h2>
                <span class="text-[11px] text-muted">Bulan Ini</span>
            </div>

            @if ($statusBulanIni->isEmpty())
                <x-admin.keadaan-kosong icon="donut_large" judul="Belum ada pesanan bulan ini" />
            @else
                <x-admin.donat-kuota :segmen="$statusBulanIni->all()" :total="$statusBulanIni->sum('nilai')" labelTengah="Pesanan" />
            @endif
        </x-admin.kartu>
    </div>

    <div class="mb-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <x-admin.dashboard.kartu-pengingat :pengingat-h7="$pengingatH7" :pengingat-h3="$pengingatH3" :pengingat-h2="$pengingatH2" />
        <x-admin.dashboard.peringatan-h1 :pesanan="$peringatanH1" />
        <x-admin.dashboard.rating-google :rating="$ratingGoogle" :rating-tamu="$ratingTamu" />
    </div>

    <div class="mb-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="lg:col-span-2 [&>*]:h-full">
            <x-admin.dashboard.pesanan-terbaru :pesanan="$pesananTerbaru" />
        </div>
        <x-admin.dashboard.journey-terlaris :data="$journeyTerlaris" />
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <x-admin.dashboard.perlu-ditindaklanjuti :data="$perluDitindaklanjuti" />
        <x-admin.dashboard.pesanan-kustom-terbaru :data="$pesananKustomTerbaru" />
        <x-admin.dashboard.ringkasan-konten :jumlah-journey="\App\Models\Perjalanan::count()" />
    </div>
</x-tata-letak.admin>
