@props(['pesanan', 'charge' => 0])

@php
    $mulai = now()->addDays(7)->startOfDay();
    $akhir = $mulai->copy()->addYear();
    $durasiHari = max(1, (int) ($pesanan->durasi_hari ?? $pesanan->perjalanan?->durasi_hari ?? 1));
    $penugasanAktif = collect();
    if ($pesanan->guide_personel_id) {
        $penugasanAktif = \App\Models\Pesanan::query()
            ->whereIn('status', [\App\Enums\BookingStatus::Dihubungi, \App\Enums\BookingStatus::Payment, \App\Enums\BookingStatus::HoldSchedule])
            ->whereKeyNot($pesanan->id)
            ->whereDate('tanggal_trip', '<=', $akhir->copy()->addDays($durasiHari - 1)->toDateString())
            ->whereRaw('COALESCE(tanggal_selesai, tanggal_trip) >= ?', [$mulai->toDateString()])
            ->where(function ($query) use ($pesanan): void {
                $query->where('guide_personel_id', $pesanan->guide_personel_id)
                    ->where(fn ($status) => $status->whereNull('status_konfirmasi_guide')->orWhereIn('status_konfirmasi_guide', ['menunggu', 'diterima']));
            })
            ->get([
                'id', 'kode', 'nama_perjalanan', 'tanggal_trip', 'tanggal_selesai',
                'guide_personel_id', 'nama_guide', 'status_konfirmasi_guide',
            ]);
    }
    // Pilihan = Tanggal Tersedia journey ini dengan sisa slot nyata (kuota journey + guide bebas); pesanan ini sendiri tidak dihitung.
    $opsiKapasitas = $pesanan->perjalanan
        ? app(\App\Layanan\KapasitasTripLayanan::class)->daftarTanggal($pesanan->perjalanan, $pesanan->id, $durasiHari)
        : collect();
    $tanggalTersedia = $opsiKapasitas
        ->map(function (array $opsi) use ($pesanan, $durasiHari, $penugasanAktif) {
            $tanggal = \Illuminate\Support\Carbon::parse($opsi['tanggal']);
            $iso = $opsi['tanggal'];
            $tanggalSelesaiBaru = $tanggal->copy()->addDays($durasiHari - 1)->toDateString();
            $bentrok = [];

            foreach ($penugasanAktif as $tugas) {
                $mulaiTugas = $tugas->tanggal_trip->toDateString();
                $akhirTugas = ($tugas->tanggal_selesai ?? $tugas->tanggal_trip)->toDateString();
                if ($mulaiTugas > $tanggalSelesaiBaru || $akhirTugas < $iso) {
                    continue;
                }

                $tanggalTugas = $tugas->tanggal_trip->translatedFormat('l, d F Y');
                if ($pesanan->guide_personel_id && $tugas->guide_personel_id === $pesanan->guide_personel_id
                    && in_array($tugas->status_konfirmasi_guide, [null, 'menunggu', 'diterima'], true)) {
                    $nama = $tugas->nama_guide ?: 'Guide #'.$tugas->guide_personel_id;
                    $bentrok[] = "{$nama} sudah bertugas pada {$tanggalTugas} untuk pesanan {$tugas->kode} ({$tugas->nama_perjalanan}).";
                }
            }
            $bentrok = array_values(array_unique($bentrok));
            $penandaBentrok = [];
            if ($bentrok !== []) {
                $penandaBentrok[] = 'Guide bentrok';
            }

            return [
                'tanggal' => $iso,
                'label' => $tanggal->translatedFormat('l, d F Y').' · '.($iso === $pesanan->tanggal_trip->toDateString() ? 'Jadwal saat ini' : $opsi['label']).($penandaBentrok ? ' · '.implode(', ', $penandaBentrok) : ''),
                'tersedia' => $opsi['tersedia'] && $iso !== $pesanan->tanggal_trip->toDateString(),
                'bentrok' => $bentrok,
            ];
        });
    $pilihanPertama = $tanggalTersedia->firstWhere('tersedia', true)['tanggal'] ?? '';
    $bentrokPerTanggal = $tanggalTersedia->mapWithKeys(fn ($opsi) => [$opsi['tanggal'] => $opsi['bentrok']]);
    $totalTagihanBaru = $pesanan->totalTagihan() !== null ? $pesanan->totalTagihan() + $charge : null;
    $sisaTagihanBaru = $pesanan->sisaTagihan() !== null ? $pesanan->sisaTagihan() + $charge : null;
@endphp

<x-admin.modal :nama="'ganti-jadwal-'.$pesanan->id" size="sm">
    <x-slot:header>
        <p class="font-semibold text-ink">Ganti Jadwal Pesanan</p>
        <p class="text-xs text-muted">{{ $pesanan->kode }} — {{ $pesanan->nama_perjalanan }}</p>
    </x-slot:header>

    <form id="form-ganti-jadwal-{{ $pesanan->id }}" method="POST" action="{{ route('admin.pesanan.ganti-jadwal', $pesanan) }}" class="space-y-4">
        @csrf

        <div x-data="{ tanggalDipilih: @js(old('tanggal_trip', $pilihanPertama)), bentrokPerTanggal: @js($bentrokPerTanggal) }">
            <x-admin.formulir.label for="tanggal_trip_ganti" wajib>Tanggal Baru</x-admin.formulir.label>
            <select
                id="tanggal_trip_ganti"
                name="tanggal_trip"
                required
                x-model="tanggalDipilih"
                class="w-full rounded-lg border border-surface-border bg-white px-3.5 py-2.5 text-sm text-ink focus:border-hijau-aksi focus:outline-none focus:ring-1 focus:ring-hijau-aksi"
            >
                <option value="">Pilih tanggal baru</option>
                @foreach ($tanggalTersedia as $opsi)
                    <option
                        value="{{ $opsi['tanggal'] }}"
                        @disabled(! $opsi['tersedia'])
                        @selected($opsi['tersedia'] && old('tanggal_trip', $pilihanPertama) === $opsi['tanggal'])
                    >
                        {{ $opsi['label'] }}
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-muted">Hanya Tanggal Tersedia journey ini. Tanggal penuh (kuota journey atau semua guide bertugas) atau ditutup tidak dapat dipilih.</p>
            <div x-show="tanggalDipilih && (bentrokPerTanggal[tanggalDipilih] ?? []).length > 0" x-cloak class="mt-2 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs leading-5 text-amber-900">
                <p class="font-semibold">Personel pada tanggal ini memiliki tugas yang bertabrakan:</p>
                <ul class="mt-1 list-inside list-disc">
                    <template x-for="(rincian, indeks) in (bentrokPerTanggal[tanggalDipilih] ?? [])" :key="indeks">
                        <li x-text="rincian"></li>
                    </template>
                </ul>
                <p class="mt-1">Atur ulang Guide pada pesanan ini sebelum menyimpan jadwal tersebut.</p>
            </div>
            @error('tanggal_trip')
                <x-admin.formulir.galat :pesan="$message" />
            @enderror
        </div>

        <x-admin.peringatan :type="$charge > 0 ? 'warning' : 'success'">
            @if ($charge > 0)
                Biaya ganti jadwal pertama: <strong>{{ \App\Bantuan\FormatRupiah::dari($charge) }}</strong> ({{ \App\Models\Pengaturan::current()->persen_charge_ganti_jadwal }}% dari harga).
            @else
                Ganti jadwal ini tidak dikenakan biaya tambahan.
            @endif
            @if ($totalTagihanBaru !== null)
                <p class="mt-2">Total tagihan setelah perubahan: <strong>{{ \App\Bantuan\FormatRupiah::dari($totalTagihanBaru) }}</strong></p>
                <p>Sisa tagihan setelah perubahan: <strong>{{ \App\Bantuan\FormatRupiah::dari($sisaTagihanBaru) }}</strong></p>
            @endif
        </x-admin.peringatan>
    </form>

    <x-slot:footer>
        <x-admin.tombol variant="secondary" x-on:click="tutup()">Batal</x-admin.tombol>
        <x-admin.tombol type="submit" form="form-ganti-jadwal-{{ $pesanan->id }}" :disabled="! $tanggalTersedia->contains(fn ($opsi) => $opsi['tersedia'])">Simpan Jadwal Baru</x-admin.tombol>
    </x-slot:footer>
</x-admin.modal>
