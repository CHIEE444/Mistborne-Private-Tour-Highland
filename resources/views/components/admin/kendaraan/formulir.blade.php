@props(['kendaraan' => null])

<div class="space-y-6">
    <x-admin.kartu>
        <h2 class="flex items-center gap-2 text-sm font-semibold text-ink">
            <span class="material-symbols-outlined text-lg" aria-hidden="true">directions_car</span>
            Data Kendaraan
        </h2>
        <p class="mb-4 text-xs text-muted">Identitas dan kapasitas kendaraan. Semua isian bertanda * wajib diisi.</p>

        <div class="grid gap-4 sm:grid-cols-2">
            <x-admin.formulir.masukan name="nama" label="Nama Kendaraan" :value="$kendaraan?->nama" wajib placeholder="Contoh: Toyota Hiace Commuter" class="sm:col-span-2" maxlength="100" />

            <x-admin.formulir.pilihan name="jenis" label="Jenis" wajib placeholder="Pilih jenis" :pilihan="\App\Models\Kendaraan::JENIS" :value="$kendaraan?->jenis" />

            <x-admin.formulir.masukan name="plat_nomor" label="Plat Nomor" :value="$kendaraan?->plat_nomor" wajib placeholder="Huruf, angka, huruf. Contoh: B 1234 XYZ" maxlength="15" />

            <x-admin.formulir.masukan name="kapasitas" type="number" label="Kapasitas Penumpang" :value="$kendaraan?->kapasitas" wajib min="1" max="60" placeholder="Kursi penumpang, tidak termasuk sopir. Contoh: 6" />

            <x-admin.formulir.pilihan name="status" label="Status (hanya Siap jalan yang bisa ditugaskan)" wajib :pilihan="\App\Models\Kendaraan::STATUS" :value="$kendaraan?->status ?? 'siap'" />

            <x-admin.formulir.masukan name="tahun" type="number" label="Tahun" :value="$kendaraan?->tahun" min="1990" :max="now()->year + 1" placeholder="Contoh: 2022" />

            <x-admin.formulir.masukan name="warna" label="Warna" :value="$kendaraan?->warna" placeholder="Contoh: Putih" maxlength="30" />

            <x-admin.formulir.area-teks name="catatan" label="Catatan" :value="$kendaraan?->catatan" rows="3" class="sm:col-span-2" placeholder="Opsional: kondisi, fasilitas (charger, selimut), jadwal servis, dan lainnya." />
        </div>
    </x-admin.kartu>

    <x-admin.kartu>
        <h2 class="flex items-center gap-2 text-sm font-semibold text-ink">
            <span class="material-symbols-outlined text-lg" aria-hidden="true">build</span>
            Perawatan &amp; Dokumen
        </h2>
        <p class="mb-4 text-xs text-muted">Opsional, tetapi dipakai untuk skor kesiapan dan pengingat di halaman detail kendaraan.</p>

        <div class="grid gap-4 sm:grid-cols-2">
            <x-admin.formulir.pilihan name="bahan_bakar" label="Bahan Bakar" placeholder="Pilih bahan bakar (opsional)" :pilihan="\App\Models\Kendaraan::BAHAN_BAKAR" :value="$kendaraan?->bahan_bakar" />

            <x-admin.formulir.masukan name="odometer" type="number" label="Odometer (km)" :value="$kendaraan?->odometer" :min="$kendaraan?->odometer ?? 0" max="9999999" placeholder="Kilometer di spidometer saat ini, contoh: 87420" />

            <x-admin.formulir.tanggal name="stnk_berlaku_sampai" label="STNK Berlaku Sampai (opsional)" :value="$kendaraan?->stnk_berlaku_sampai?->format('Y-m-d')" />

            <x-admin.formulir.tanggal name="pajak_berlaku_sampai" label="Pajak Berlaku Sampai (opsional)" :value="$kendaraan?->pajak_berlaku_sampai?->format('Y-m-d')" />
        </div>
    </x-admin.kartu>

    <x-admin.kartu>
        <h2 class="flex items-center gap-2 text-sm font-semibold text-ink">
            <span class="material-symbols-outlined text-lg" aria-hidden="true">image</span>
            Foto Kendaraan
        </h2>
        <p class="mb-3 text-xs text-muted">Opsional. Otomatis dipotong &amp; dikonversi ke WebP. Detail file tampil setelah foto dipilih.</p>

        @if ($kendaraan?->fotoUrl())
            <div class="mb-3 flex items-center gap-4 rounded-lg border border-surface-border p-3">
                <img src="{{ $kendaraan->fotoUrl() }}" alt="Foto saat ini" class="h-20 w-32 rounded-md object-cover" />
                <p class="text-xs text-muted">Foto saat ini. Unggah foto baru di bawah untuk menggantinya.</p>
            </div>
        @endif

        <x-admin.unggah-berkas name="foto" accept="image/jpeg,image/png,image/webp" :pratinjau-gambar="true" keterangan="JPG, PNG, atau WebP — maksimal 4 MB." />
    </x-admin.kartu>
</div>
