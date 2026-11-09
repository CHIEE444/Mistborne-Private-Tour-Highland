@props(['pesanan'])

<x-admin.kartu :padding="false">
    <div class="flex items-center justify-between p-5 pb-4">
        <div>
            <h2 class="text-lg font-bold text-ink">Pesanan Terbaru</h2>
            <p class="text-xs text-muted">Pesanan yang baru masuk/diperbarui</p>
        </div>
        <a href="{{ route('admin.pesanan.indeks') }}" class="flex items-center gap-1 text-sm font-medium text-hijau-aksi hover:underline">
            Lihat semua pesanan
            <span class="material-symbols-outlined text-base" aria-hidden="true">arrow_forward</span>
        </a>
    </div>

    @if ($pesanan->isEmpty())
        <div class="px-5 pb-5">
            <x-admin.keadaan-kosong icon="receipt_long" judul="Belum ada pesanan" />
        </div>
    @else
        <div class="overflow-x-auto">
            <x-admin.tabel :kolom="['Tamu', 'Kode', 'Journey', 'Tanggal Trip', 'Jumlah', 'Status']">
                @foreach ($pesanan as $item)
                    <x-admin.baris-tabel>
                        <x-admin.kolom-tabel>
                            <a href="{{ route('admin.pesanan.detail', $item) }}" class="flex items-center gap-2.5 font-medium text-ink hover:text-hijau-aksi">
                                <x-admin.avatar :nama="$item->nama_lengkap" size="sm" />
                                {{ $item->nama_lengkap }}
                            </a>
                        </x-admin.kolom-tabel>
                        <x-admin.kolom-tabel class="font-mono text-xs">{{ $item->kode }}</x-admin.kolom-tabel>
                        <x-admin.kolom-tabel>{{ $item->nama_perjalanan }}</x-admin.kolom-tabel>
                        <x-admin.kolom-tabel>{{ $item->rentangTanggalTeks() }}</x-admin.kolom-tabel>
                        <x-admin.kolom-tabel>{{ $item->jumlah_wisatawan }} orang</x-admin.kolom-tabel>
                        <x-admin.kolom-tabel><x-admin.lencana-status :status="$item->status" :pesanan="$item" /></x-admin.kolom-tabel>
                    </x-admin.baris-tabel>
                @endforeach
            </x-admin.tabel>
        </div>
    @endif
</x-admin.kartu>
