@props(['hotel' => null, 'daftarJourney' => collect()])

@php
    $fasilitasTerpilih = (array) old('fasilitas', $hotel?->fasilitas ?? []);
    $tipeKamarAwal = collect(old('tipe_kamar', $hotel?->tipe_kamar ?? []))
        ->map(fn ($baris): array => ['nama' => (string) ($baris['nama'] ?? ''), 'kapasitas' => (int) ($baris['kapasitas'] ?? 2)])->values()->all();
    $journeyTerpilih = array_map('intval', (array) old('perjalanan_ids', $hotel?->perjalanan?->pluck('id')->all() ?? []));
@endphp

<div class="space-y-6">
    <x-admin.kartu>
        <h2 class="flex items-center gap-2 text-sm font-semibold text-ink">
            <span class="material-symbols-outlined text-lg" aria-hidden="true">hotel</span>
            Data Hotel
        </h2>
        <p class="mb-4 text-xs text-muted">Identitas dan lokasi hotel. Isian bertanda * wajib diisi.</p>

        <div class="grid gap-4 sm:grid-cols-2">
            <x-admin.formulir.masukan name="nama" label="Nama Hotel" :value="$hotel?->nama" wajib maxlength="150" placeholder="Contoh: Bromo Permai 1" class="sm:col-span-2" />
            <x-admin.formulir.pilihan name="bintang" label="Bintang" wajib :pilihan="[4 => '4 bintang (standar MISTBORNE)', 5 => '5 bintang (premium)']" :value="$hotel?->bintang ?? 4" />
            <x-admin.formulir.masukan name="area" label="Area" :value="$hotel?->area" wajib maxlength="100" placeholder="Kawasan wisata atau kota. Contoh: Ngadisari, Bromo" />
            <x-admin.formulir.area-teks name="alamat" label="Alamat" :value="$hotel?->alamat" rows="2" class="sm:col-span-2" placeholder="Alamat lengkap hotel (opsional)" />
            <x-admin.formulir.masukan name="tautan_peta" label="Lokasi Google Maps" :value="$hotel?->tautan_peta" maxlength="500" placeholder="Salin tautan Google Maps hotel, diawali https:// (kosong: otomatis pencarian nama hotel di Google Maps)" class="sm:col-span-2" />
            <x-admin.formulir.masukan name="jarak_destinasi" label="Estimasi Perjalanan ke Destinasi" :value="$hotel?->jarak_destinasi" wajib maxlength="150" placeholder="Estimasi waktu destinasi–hotel, tampil di kartu hotel journey. Contoh: 1 jam berkendara ke Dieng" class="sm:col-span-2" />
            <x-admin.formulir.area-teks name="deskripsi" label="Deskripsi Singkat" :value="$hotel?->deskripsi" rows="3" maxlength="1000" class="sm:col-span-2" placeholder="Gambaran hotel untuk tamu: suasana, keunggulan, pemandangan, hal yang membuatnya cocok untuk journey ini (opsional)" />
        </div>
    </x-admin.kartu>

    <x-admin.kartu>
        <h2 class="flex items-center gap-2 text-sm font-semibold text-ink">
            <span class="material-symbols-outlined text-lg" aria-hidden="true">image</span>
            Foto Hotel
        </h2>
        <p class="mb-3 text-xs text-muted">Tampil di halaman journey supaya tamu bisa melihat hotel yang akan MISTBORNE urus. Otomatis dipotong &amp; dikonversi ke WebP.</p>

        @if ($hotel?->fotoUrl())
            <div class="mb-3 flex items-center gap-4 rounded-lg border border-surface-border p-3">
                <img src="{{ $hotel->fotoUrl() }}" alt="Foto saat ini" class="h-20 w-32 rounded-md object-cover" />
                <p class="text-xs text-muted">Foto saat ini. Unggah foto baru di bawah untuk menggantinya.</p>
            </div>
        @endif

        <x-admin.unggah-berkas name="foto" accept="image/jpeg,image/png,image/webp" :pratinjau-gambar="true" keterangan="JPG, PNG, atau WebP — maksimal 4 MB." />
    </x-admin.kartu>

    <x-admin.kartu>
        <h2 class="flex items-center gap-2 text-sm font-semibold text-ink">
            <span class="material-symbols-outlined text-lg" aria-hidden="true">photo_library</span>
            Galeri Foto
        </h2>
        <p class="mb-3 text-xs text-muted">Foto tambahan yang tampil di bawah foto utama, misalnya kamar, kolam renang, restoran, dan lobi, supaya tamu melihat hotelnya nyaman. Maksimal {{ \App\Models\Hotel::MAKS_GALERI }} foto, tiap foto maksimal 4 MB. Pakai foto beresolusi besar (lebar minimal 1600 px) supaya tajam.</p>

        @php($galeriLama = array_values($hotel?->galeri ?? []))
        @if ($galeriLama !== [])
            <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach ($galeriLama as $indeks => $baris)
                    <div class="space-y-2 rounded-lg border border-surface-border p-2">
                        <img src="{{ \App\Bantuan\UrlGambar::dari($baris['foto']) }}" alt="Foto galeri {{ $indeks + 1 }}" class="aspect-[4/3] w-full rounded-md object-cover" loading="lazy" />
                        <input type="hidden" name="galeri_lama[{{ $indeks }}][foto]" value="{{ $baris['foto'] }}" />
                        <select name="galeri_lama[{{ $indeks }}][label]" aria-label="Kategori foto galeri {{ $indeks + 1 }}" class="w-full rounded-lg border border-surface-border bg-white px-2.5 py-2 text-xs text-ink focus:border-hijau-aksi focus:outline-none focus:ring-1 focus:ring-hijau-aksi">
                            @foreach (\App\Models\Hotel::LABEL_GALERI as $kunci => $label)
                                <option value="{{ $kunci }}" @selected(($baris['label'] ?? '') === $kunci)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <label class="flex items-center gap-2 text-xs text-ink">
                            <input type="checkbox" name="galeri_lama[{{ $indeks }}][hapus]" value="1" class="h-4 w-4 rounded border-surface-border text-status-dibatalkan-teks focus:ring-status-dibatalkan-teks" />
                            Hapus foto ini
                        </label>
                    </div>
                @endforeach
            </div>
        @endif

        <div x-data="{ baru: [], sisa: {{ max(0, \App\Models\Hotel::MAKS_GALERI - count($galeriLama)) }} }">
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <template x-for="(baris, indeks) in baru" :key="baris.id">
                    <div class="space-y-2 rounded-lg border border-dashed border-surface-border p-2">
                        <div class="flex aspect-[4/3] w-full items-center justify-center overflow-hidden rounded-md bg-surface-muted">
                            <img x-show="baris.pratinjau" :src="baris.pratinjau" alt="Pratinjau foto baru" class="h-full w-full object-cover" />
                            <span x-show="!baris.pratinjau" class="material-symbols-outlined text-3xl text-muted" aria-hidden="true">add_photo_alternate</span>
                        </div>
                        <input type="file" :name="`galeri_baru[${indeks}][berkas]`" accept="image/jpeg,image/png,image/webp" aria-label="File foto galeri" x-on:change="baris.pratinjau = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null" class="w-full text-xs text-ink file:mr-2 file:rounded-md file:border-0 file:bg-hijau-aksi file:px-2.5 file:py-1.5 file:text-xs file:font-semibold file:text-white" />
                        <select :name="`galeri_baru[${indeks}][label]`" aria-label="Kategori foto baru" class="w-full rounded-lg border border-surface-border bg-white px-2.5 py-2 text-xs text-ink focus:border-hijau-aksi focus:outline-none focus:ring-1 focus:ring-hijau-aksi">
                            @foreach (\App\Models\Hotel::LABEL_GALERI as $kunci => $label)
                                <option value="{{ $kunci }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <button type="button" x-on:click="baru.splice(indeks, 1)" class="inline-flex items-center gap-1 text-xs font-medium text-status-dibatalkan-teks hover:underline"><span class="material-symbols-outlined text-sm" aria-hidden="true">delete</span>Hapus baris</button>
                    </div>
                </template>
            </div>
            <button type="button" x-show="baru.length < sisa" x-on:click="baru.push({ id: Date.now() + baru.length, pratinjau: null })" class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-hijau-aksi hover:underline"><span class="material-symbols-outlined text-base" aria-hidden="true">add_photo_alternate</span>Tambah foto galeri</button>
            <p x-show="sisa === 0" x-cloak class="mt-2 text-xs text-muted">Galeri sudah penuh. Hapus foto lama untuk menambah yang baru.</p>
        </div>
        @error('galeri_baru') <x-admin.formulir.galat :pesan="$message" /> @enderror
        @error('galeri_baru.*.berkas') <x-admin.formulir.galat :pesan="$message" /> @enderror
        @error('galeri_baru.*.label') <x-admin.formulir.galat :pesan="$message" /> @enderror
        @error('galeri_lama') <x-admin.formulir.galat :pesan="$message" /> @enderror
    </x-admin.kartu>

    <x-admin.kartu>
        <h2 class="flex items-center gap-2 text-sm font-semibold text-ink">
            <span class="material-symbols-outlined text-lg" aria-hidden="true">call</span>
            Kontak Reservasi
        </h2>
        <p class="mb-4 text-xs text-muted">Nomor WhatsApp dipakai untuk mengirim draft permintaan reservasi. Tanpa nomor, tombol kirim dinonaktifkan.</p>

        <div class="grid gap-4 sm:grid-cols-2">
            <x-admin.formulir.masukan name="whatsapp" label="WhatsApp Hotel" :value="$hotel ? $hotel->whatsapp : config('mistborne.kontak_uji.whatsapp')" maxlength="20" inputmode="tel" placeholder="Diawali 08 atau +62. Contoh: 081234567890" />
            <x-admin.formulir.masukan name="nama_pic" label="Nama PIC" :value="$hotel?->nama_pic" maxlength="100" placeholder="Petugas reservasi yang dihubungi (opsional)" />
            <x-admin.formulir.masukan name="email" type="email" label="Email Hotel" :value="$hotel?->email" maxlength="255" placeholder="reservasi@hotel.com (opsional)" class="sm:col-span-2" />
        </div>
    </x-admin.kartu>

    <x-admin.kartu>
        <h2 class="flex items-center gap-2 text-sm font-semibold text-ink">
            <span class="material-symbols-outlined text-lg" aria-hidden="true">payments</span>
            Kamar, Tarif &amp; Aturan
        </h2>
        <p class="mb-4 text-xs text-muted">Dipakai untuk memeriksa kecukupan kamar dan melengkapi pesan ke tamu.</p>

        <div class="grid gap-4 sm:grid-cols-2">
            <x-admin.formulir.uang name="tarif_mulai" label="Tarif Mulai (per kamar per malam)" :value="$hotel?->tarif_mulai" nullable placeholder="Mis. 450.000 (opsional)" />
            <x-admin.formulir.uang name="tarif_sampai" label="Tarif Sampai (per kamar per malam)" :value="$hotel?->tarif_sampai" nullable placeholder="Mis. 650.000 (opsional, tidak kurang dari tarif mulai)" />
            <x-admin.formulir.masukan name="kapasitas_kamar" type="number" label="Kapasitas Standar per Kamar" :value="$hotel?->kapasitas_kamar ?? 2" wajib min="1" max="10" placeholder="Dipakai bila tipe kamar di bawah belum diisi. Biasanya 2" />
            <div class="self-end">
                <x-admin.formulir.sakelar name="tersedia_extra_bed" :checked="$hotel?->tersedia_extra_bed ?? false">Extra bed tersedia (+1 orang per kamar)</x-admin.formulir.sakelar>
            </div>
            <x-admin.formulir.masukan name="jam_check_in" type="time" label="Jam Check-in" :value="$hotel?->jamCheckIn() ?? '14:00'" wajib />
            <x-admin.formulir.masukan name="jam_check_out" type="time" label="Jam Check-out" :value="$hotel?->jamCheckOut() ?? '12:00'" wajib />
            <x-admin.formulir.area-teks name="kebijakan_pembatalan" label="Kebijakan Pembatalan" :value="$hotel?->kebijakan_pembatalan" rows="2" class="sm:col-span-2" placeholder="Contoh: gratis batal sampai H-3, setelah itu dikenakan biaya 1 malam (opsional)" />

            <fieldset class="sm:col-span-2" x-data="{ tipe: @js($tipeKamarAwal) }">
                <legend class="mb-1 text-sm font-medium text-ink">Tipe Kamar &amp; Kapasitas</legend>
                <p class="mb-2 text-xs text-muted">Tipe kamar berbeda di tiap hotel. Admin memilihnya dari dropdown saat reservasi, jadi cukup cocokkan jumlah tamu dengan kapasitas tipe.</p>
                <div class="space-y-2">
                    <template x-for="(baris, indeks) in tipe" :key="indeks">
                        <div class="grid grid-cols-[minmax(0,1fr)_7rem_auto] items-center gap-2">
                            <input type="text" :name="`tipe_kamar[${indeks}][nama]`" x-model="baris.nama" maxlength="60" placeholder="Nama tipe. Contoh: Deluxe, Family Room" class="w-full rounded-lg border border-surface-border bg-white px-3.5 py-2.5 text-sm text-ink placeholder:text-muted focus:border-hijau-aksi focus:outline-none focus:ring-1 focus:ring-hijau-aksi" />
                            <input type="number" :name="`tipe_kamar[${indeks}][kapasitas]`" x-model="baris.kapasitas" min="1" max="10" placeholder="Orang" aria-label="Kapasitas orang" class="w-full rounded-lg border border-surface-border bg-white px-3.5 py-2.5 text-sm text-ink placeholder:text-muted focus:border-hijau-aksi focus:outline-none focus:ring-1 focus:ring-hijau-aksi" />
                            <button type="button" x-on:click="tipe.splice(indeks, 1)" class="rounded-lg p-2 text-muted hover:text-status-dibatalkan-teks" aria-label="Hapus tipe kamar"><span class="material-symbols-outlined text-lg" aria-hidden="true">delete</span></button>
                        </div>
                    </template>
                </div>
                <button type="button" x-on:click="tipe.push({ nama: '', kapasitas: 2 })" x-show="tipe.length < 10" class="mt-2 inline-flex items-center gap-1 text-sm font-semibold text-hijau-aksi hover:underline"><span class="material-symbols-outlined text-base" aria-hidden="true">add</span>Tambah tipe kamar</button>
                @error('tipe_kamar') <x-admin.formulir.galat :pesan="$message" /> @enderror
                @error('tipe_kamar.*.nama') <x-admin.formulir.galat :pesan="$message" /> @enderror
                @error('tipe_kamar.*.kapasitas') <x-admin.formulir.galat :pesan="$message" /> @enderror
            </fieldset>

            <fieldset class="sm:col-span-2">
                <legend class="mb-2 text-sm font-medium text-ink">Fasilitas</legend>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                    @foreach (\App\Models\Hotel::FASILITAS as $kunci => $label)
                        <label class="inline-flex items-center gap-2 text-sm text-ink">
                            <input type="checkbox" name="fasilitas[]" value="{{ $kunci }}" @checked(in_array($kunci, $fasilitasTerpilih, true)) class="h-4 w-4 rounded border-surface-border text-hijau-aksi focus:ring-hijau-aksi" />
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                @error('fasilitas.*') <x-admin.formulir.galat :pesan="$message" /> @enderror
            </fieldset>
        </div>
    </x-admin.kartu>

    <x-admin.kartu>
        <h2 class="flex items-center gap-2 text-sm font-semibold text-ink">
            <span class="material-symbols-outlined text-lg" aria-hidden="true">explore</span>
            Rekomendasi untuk Journey
        </h2>
        <p class="mb-3 text-xs text-muted">Hotel ini otomatis masuk pesan rekomendasi untuk journey yang dicentang. Hotel nonaktif tidak direkomendasikan.</p>

        @if ($daftarJourney->isEmpty())
            <p class="text-sm text-muted">Belum ada journey.</p>
        @else
            <div class="grid gap-2 sm:grid-cols-2">
                @foreach ($daftarJourney as $journey)
                    <label class="inline-flex items-center gap-2 text-sm text-ink">
                        <input type="checkbox" name="perjalanan_ids[]" value="{{ $journey->id }}" @checked(in_array($journey->id, $journeyTerpilih, true)) class="h-4 w-4 rounded border-surface-border text-hijau-aksi focus:ring-hijau-aksi" />
                        {{ $journey->nama }}
                    </label>
                @endforeach
            </div>
        @endif
        @error('perjalanan_ids.*') <x-admin.formulir.galat :pesan="$message" /> @enderror

        <div class="mt-5 border-t border-surface-border pt-4">
            <x-admin.formulir.area-teks name="catatan" label="Catatan Internal" :value="$hotel?->catatan" rows="2" placeholder="Hanya untuk tim: kualitas, pengalaman tamu sebelumnya, tips negosiasi (opsional)" />
        </div>

        <div class="mt-4">
            <x-admin.formulir.sakelar name="aktif" :checked="$hotel?->aktif ?? true">Hotel aktif (boleh direkomendasikan dan dipesan)</x-admin.formulir.sakelar>
            <x-admin.formulir.sakelar name="sorotan" :checked="$hotel?->sorotan ?? false">Sorot di halaman journey dan formulir pemesanan (satu hotel per journey; tanpa sorotan dipakai hotel tertua yang ditautkan)</x-admin.formulir.sakelar>
        </div>
    </x-admin.kartu>
</div>
