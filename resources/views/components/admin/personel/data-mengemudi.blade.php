@props(['awalan' => '', 'personel' => null])

{{-- Guide merangkap driver: data kemampuan mengemudi wajib lengkap (SIM, golongan, masa berlaku, pengalaman, kendaraan dikuasai).
     $awalan = "personel.{id}." untuk form ubah (draf bertingkat), kosong untuk form tambah. --}}
@php
    $nama = fn (string $kolom): string => $awalan.$kolom;
    $namaHtml = $awalan === '' ? 'kendaraan_dikuasai[]' : 'personel['.rtrim(explode('.', $awalan)[1] ?? '', '.').'][kendaraan_dikuasai][]';
    $dikuasaiAwal = old($nama('kendaraan_dikuasai'), $personel?->kendaraan_dikuasai ? array_map('trim', explode(',', $personel->kendaraan_dikuasai)) : []);
    $dikuasaiAwal = is_array($dikuasaiAwal) ? $dikuasaiAwal : [];
@endphp

<div class="col-span-full space-y-3 rounded-xl border border-surface-border bg-white p-3">
    <p class="text-xs font-semibold text-ink">Kemampuan mengemudi <span class="text-status-dibatalkan-teks">*</span> <span class="font-normal text-muted">(guide merangkap driver)</span></p>
    <div class="grid gap-3 sm:grid-cols-2">
        <x-admin.formulir.masukan :name="$nama('nomor_sim')" label="Nomor SIM" :value="$personel?->nomor_sim" wajib placeholder="Nomor SIM yang berlaku, 6–40 karakter" maxlength="40" />
        <x-admin.formulir.pilihan :name="$nama('jenis_sim')" label="Golongan SIM" :pilihan="\App\Models\PersonelOperasional::JENIS_SIM" :value="$personel?->jenis_sim" placeholder="Pilih golongan SIM" wajib />
        <x-admin.formulir.masukan :name="$nama('masa_berlaku_sim')" label="SIM berlaku sampai (tanggal)" type="date" :value="$personel?->masa_berlaku_sim?->toDateString()" wajib :min="now()->toDateString()" />
        <x-admin.formulir.masukan :name="$nama('pengalaman_mengemudi_tahun')" label="Pengalaman mengemudi (tahun)" type="number" min="1" max="60" :value="$personel?->pengalaman_mengemudi_tahun" wajib placeholder="Minimal 1 tahun. Contoh: 5" />
    </div>
    <fieldset>
        <legend class="mb-1.5 text-sm font-medium text-ink">Kendaraan yang dikuasai <span class="text-status-dibatalkan-teks">*</span></legend>
        <div class="flex flex-wrap gap-2">
            @foreach (\App\Models\Kendaraan::daftarKategoriTamu() as $kategori)
                <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-surface-border bg-white px-3 py-2 text-xs text-ink has-[:checked]:border-hijau-aksi has-[:checked]:bg-[#EAF3EE]">
                    <input type="checkbox" name="{{ $namaHtml }}" value="{{ $kategori }}" @checked(in_array($kategori, $dikuasaiAwal, true)) class="rounded border-surface-border text-hijau-aksi focus:ring-hijau-aksi" />
                    {{ $kategori }}
                </label>
            @endforeach
        </div>
        @error($nama('kendaraan_dikuasai')) <p class="mt-1.5 text-xs text-status-dibatalkan-teks">{{ $message }}</p> @enderror
    </fieldset>
</div>
