@props(['pesanan'])

<x-admin.kartu :padding="false">
    <div class="flex items-center justify-between p-5 pb-0">
        <h2 class="text-sm font-semibold text-muted">Riwayat Ganti Jadwal</h2>
        <span class="text-xs text-muted">{{ $pesanan->penggantian->count() }} Perubahan</span>
    </div>

    @if ($pesanan->penggantian->isEmpty())
        <div class="p-5">
            <x-admin.keadaan-kosong icon="calendar_month" judul="Belum ada penggantian jadwal" />
        </div>
    @else
        <div class="mt-4 overflow-x-auto">
            <x-admin.tabel :kolom="['Perubahan Jadwal', 'Biaya Tambahan', 'Keterangan Aturan']">
                @foreach ($pesanan->penggantian as $index => $riwayat)
                    <x-admin.baris-tabel>
                        <x-admin.kolom-tabel>
                            {{ $riwayat->tanggal_lama->translatedFormat('d M Y') }}
                            <span class="text-muted">&rarr;</span>
                            {{ $riwayat->tanggal_baru->translatedFormat('d M Y') }}
                        </x-admin.kolom-tabel>
                        <x-admin.kolom-tabel>
                            @if ($riwayat->charge > 0)
                                {{ \App\Bantuan\FormatRupiah::dari($riwayat->charge) }}
                            @else
                                <span class="text-status-completed-teks">Rp 0</span>
                            @endif
                        </x-admin.kolom-tabel>
                        <x-admin.kolom-tabel class="text-muted">
                            @if ($index === $pesanan->penggantian->count() - 1 && $riwayat->charge > 0)
                                Reschedule pertama (dikenakan biaya operasional)
                            @else
                                Reschedule berikutnya (bebas biaya)
                            @endif
                        </x-admin.kolom-tabel>
                    </x-admin.baris-tabel>
                @endforeach
            </x-admin.tabel>
        </div>
    @endif
</x-admin.kartu>
