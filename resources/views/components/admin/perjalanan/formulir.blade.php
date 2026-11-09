@props(['perjalanan' => null, 'jenis' => 'journey', 'daftarKendaraan' => []])

@php
    // Baris kosong dibiarkan satu supaya admin langsung melihat tempat mengisi.
    $highlightAwal = array_values(old('highlights', $perjalanan?->highlights ?: ['']));
    $itineraryAwal = array_values(old('itinerary', $perjalanan?->itinerary ?: [['t' => '', 'x' => '']]));
    $praktisAwal = old('praktis', $perjalanan?->praktis ?? []);

    // Galat server per baris (highlights.0, itinerary.1.t, …) dikirim ke Alpine supaya tampil di dalam barisnya.
    $galatServer = collect($errors->getMessages())->map(fn ($pesan) => $pesan[0])->all();

    $paket = ($perjalanan?->jenis ?? $jenis) === 'paket';
    $namaJenis = $paket ? 'Paket' : 'Journey';
    $kelasInput = 'w-full rounded-lg border bg-white px-3.5 py-2.5 text-sm text-ink placeholder:text-muted focus:outline-none focus:ring-1';
@endphp

<div class="space-y-6" x-data="{ galatServer: {{ \Illuminate\Support\Js::from($galatServer) }} }">
    <input type="hidden" name="jenis" value="{{ $paket ? 'paket' : 'journey' }}" />

    <x-admin.kartu>
        <h2 class="flex items-center gap-2 text-sm font-semibold text-ink">
            <span class="material-symbols-outlined text-lg" aria-hidden="true">badge</span>
            Informasi Utama
        </h2>
        <p class="mb-4 text-xs text-muted">Judul, wilayah, dan cerita yang tampil di kartu serta halaman detail {{ strtolower($namaJenis) }}. Isian bertanda * wajib diisi.</p>
        <div class="grid gap-4 sm:grid-cols-2">
            <x-admin.formulir.masukan name="nama" label="Nama {{ $namaJenis }}" :value="$perjalanan?->nama" wajib maxlength="255" placeholder="Judul kartu di situs publik, harus unik. Contoh: {{ $paket ? 'Bukit Sikunir' : 'DIENG HIGHLAND ESCAPE' }}" class="sm:col-span-2" />
            @if ($paket)
                <x-admin.formulir.masukan name="chip" label="Kategori Paket" :value="$perjalanan?->chip" wajib maxlength="60" placeholder="Label kecil di kartu. Contoh: Sunrise, Kawah &amp; Telaga" />
                <x-admin.formulir.masukan name="rating_label" label="Rating Tampilan" :value="$perjalanan?->rating_label" maxlength="4" placeholder="Angka 0 sampai 5, contoh 4.95 (opsional)" />
                <x-admin.formulir.masukan name="label_unggulan" label="Label Unggulan" :value="$perjalanan?->label_unggulan" maxlength="120" placeholder="Teks sorotan di slider paket (opsional). Contoh: FAVORIT KELUARGA • SUNRISE DIENG" class="sm:col-span-2" />
            @endif
            <x-admin.formulir.pilihan name="region" label="Region" :pilihan="\App\Models\Perjalanan::daftarRegion()" :value="$perjalanan?->region" wajib placeholder="Pilih region (menentukan tab wilayah di situs publik)" />
            <x-admin.formulir.masukan name="tempat" label="Lokasi Destinasi" :value="$perjalanan?->tempat" wajib maxlength="255" placeholder="Tampil dengan ikon pin. Contoh: Dieng, Jawa Tengah" />
            <x-admin.formulir.masukan name="tagline" label="Tagline" :value="$perjalanan?->tagline" wajib maxlength="255" placeholder="Satu kalimat di bawah judul kartu. Contoh: Sunrise berkabut di atas telaga warna" class="sm:col-span-2" />
            <x-admin.formulir.area-teks name="deskripsi" label="Cerita {{ $namaJenis }}" :value="$perjalanan?->deskripsi" wajib rows="6" placeholder="Kisah di balik destinasi (asal-usul, legenda, suasana) supaya tamu tergugah. Rangkaian kegiatan cukup ditulis di Itinerary." class="sm:col-span-2" />
        </div>
    </x-admin.kartu>

    <x-admin.kartu>
        <h2 class="flex items-center gap-2 text-sm font-semibold text-ink">
            <span class="material-symbols-outlined text-lg" aria-hidden="true">payments</span>
            Harga, Durasi &amp; Kapasitas
        </h2>
        <p class="mb-4 text-xs text-muted">Dipakai di halaman detail, formulir booking, dan perhitungan slot.</p>
        <div class="grid gap-4 sm:grid-cols-3">
            <x-admin.formulir.uang name="harga" label="Harga per Booking" :value="$perjalanan?->harga" nullable wajib placeholder="Harga all-inclusive. Contoh: 4.550.000" />
            <x-admin.formulir.masukan name="durasi_hari" type="number" label="Durasi (hari)" :value="$perjalanan?->durasi_hari" wajib min="2" max="30" placeholder="Min. 2 (2 = 2 Hari · 1 Malam)" />
            <x-admin.formulir.masukan name="maks_orang" type="number" label="Maksimal Tamu" :value="$perjalanan?->maks_orang" wajib min="1" max="10" placeholder="1–10, guide-sopir tidak dihitung" />
        </div>
        <div class="mt-4 flex gap-3 rounded-lg bg-surface-muted px-4 py-3 text-xs leading-relaxed text-muted">
            <span class="material-symbols-outlined text-lg text-hijau-aksi" aria-hidden="true">verified</span>
            <p><strong class="text-ink">All-inclusive:</strong> harga sudah memuat tiket masuk, hotel bintang 4 (4 orang per kamar), mobil, BBM, tol, parkir, dan guide yang merangkap sopir. Tamu cukup membayar sekali.</p>
        </div>
    </x-admin.kartu>

    <x-admin.kartu>
        <h2 class="flex items-center gap-2 text-sm font-semibold text-ink">
            <span class="material-symbols-outlined text-lg" aria-hidden="true">directions_car</span>
            Mobil Destinasi
        </h2>
        <p class="mb-4 text-xs text-muted">Tamu tidak memilih mobil sendiri: mobil dipasang sesuai medan destinasi dan tampil di kartu serta formulir booking.</p>

        @if ($perjalanan?->exists && ! $perjalanan->semuaTermasuk())
            <p class="rounded-lg border border-dashed border-surface-border px-4 py-3 text-sm text-muted">Journey kustom memakai kendaraan yang disesuaikan dengan permintaan tamu, jadi tidak punya mobil tetap.</p>
        @else
            <div class="grid gap-4">
                <x-admin.formulir.pilihan name="kendaraan_id" label="Mobil" :pilihan="$daftarKendaraan" :value="$perjalanan?->kendaraan_id" placeholder="Belum dipasang (tamu hanya melihat info umum). Pilih mobil dari armada" />
                <x-admin.formulir.area-teks name="alasan_kendaraan" label="Alasan Memilih Mobil" :value="$perjalanan?->alasan_kendaraan" rows="2" maxlength="300" placeholder="Wajib bila mobil dipilih, dibaca tamu. Contoh: SUV 4x4 bertenaga untuk tanjakan menuju Paltuding dan perjalanan dini hari." />
            </div>
        @endif
    </x-admin.kartu>

    <x-admin.kartu>
        <h2 class="flex items-center gap-2 text-sm font-semibold text-ink">
            <span class="material-symbols-outlined text-lg" aria-hidden="true">hotel</span>
            Hotel &amp; Penginapan
        </h2>
        @if ($perjalanan?->exists)
            @php($hotelJourney = $perjalanan->hotel()->aktif()->orderByDesc('hotel.sorotan')->get())
            <p class="mb-3 mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-ink">
                <span class="font-semibold">Hotel yang tampil ke tamu:</span>
                @forelse ($hotelJourney as $hotelItem)
                    <a href="{{ route('admin.hotel.ubah', $hotelItem) }}" class="font-medium text-hijau-aksi hover:underline">{{ $hotelItem->nama }}</a>@if (! $loop->last)<span class="text-muted">·</span>@endif
                @empty
                    <span class="text-status-dibatalkan-teks">belum ada, tautkan lewat menu Hotel</span>
                @endforelse
            </p>
        @else
            <p class="mb-3 mt-1 text-xs text-muted">Hotel yang tampil ke tamu ditautkan lewat menu Hotel setelah {{ strtolower($namaJenis) }} ini disimpan.</p>
        @endif
        <x-admin.formulir.area-teks
            name="rekomendasi_penginapan"
            label="Hotel Cadangan Terdekat"
            :value="$perjalanan?->rekomendasi_penginapan"
            rows="4"
            placeholder="Rujukan admin bila hotel utama penuh (opsional; kosong = daftar umum di Pengaturan). Satu hotel per baris, contoh:&#10;• Hotel Contoh Satu — Ngadisari, dekat pintu taman nasional&#10;• Hotel Contoh Dua — Probolinggo kota, ± 1,5 jam ke kawah"
        />
    </x-admin.kartu>

    <x-admin.kartu x-data="{ daftar: {{ \Illuminate\Support\Js::from($highlightAwal) }} }">
        <div class="mb-4 flex items-start justify-between gap-4">
            <div>
                <h2 class="flex items-center gap-2 text-sm font-semibold text-ink">
                    <span class="material-symbols-outlined text-lg" aria-hidden="true">star</span>
                    Highlight <span class="text-status-dibatalkan-teks">*</span>
                </h2>
                <p class="text-xs text-muted">Poin unggulan berupa daftar centang di halaman detail. Isi minimal satu.</p>
            </div>
            <x-admin.tombol type="button" variant="secondary" size="sm" icon="add" x-on:click="daftar.push('')" class="shrink-0">Tambah</x-admin.tombol>
        </div>

        @error('highlights')
            <x-admin.formulir.galat :pesan="$message" class="mb-3" />
        @enderror

        <div class="space-y-3">
            <template x-for="(butir, index) in daftar" :key="index">
                <div>
                    <div class="flex items-center gap-2">
                        <input
                            type="text"
                            :name="'highlights[' + index + ']'"
                            :data-kunci="'highlights.' + index"
                            data-wajib="Highlight tidak boleh kosong. Isi atau hapus barisnya."
                            x-model="daftar[index]"
                            placeholder="Satu poin unggulan. Contoh: Sunrise Golden Hour di Sikunir"
                            maxlength="255"
                            :class="(galatServer['highlights.' + index] || $data.galat?.['highlights.' + index]) ? 'border-status-dibatalkan-teks' : 'border-surface-border'"
                            class="{{ $kelasInput }} focus:border-hijau-aksi focus:ring-hijau-aksi"
                        />
                        <x-admin.tombol-ikon type="button" icon="close" variant="danger" label="Hapus highlight" x-on:click="daftar.splice(index, 1)" x-show="daftar.length > 1" />
                    </div>
                    <p x-cloak x-show="galatServer['highlights.' + index] || $data.galat?.['highlights.' + index]" class="mt-1.5 flex items-center gap-1 text-xs text-status-dibatalkan-teks" role="alert">
                        <span class="material-symbols-outlined text-sm" aria-hidden="true">error</span>
                        <span x-text="galatServer['highlights.' + index] || $data.galat?.['highlights.' + index]"></span>
                    </p>
                </div>
            </template>
        </div>
    </x-admin.kartu>

    <x-admin.kartu x-data="{ daftar: {{ \Illuminate\Support\Js::from($itineraryAwal) }} }">
        <div class="mb-4 flex items-start justify-between gap-4">
            <div>
                <h2 class="flex items-center gap-2 text-sm font-semibold text-ink">
                    <span class="material-symbols-outlined text-lg" aria-hidden="true">route</span>
                    Itinerary <span class="text-status-dibatalkan-teks">*</span>
                </h2>
                <p class="text-xs text-muted">Garis waktu di halaman detail: tiap baris satu hari dan satu kegiatan, tidak melebihi durasi. Isi minimal satu baris.</p>
            </div>
            <x-admin.tombol type="button" variant="secondary" size="sm" icon="add" x-on:click="daftar.push({ t: '', x: '' })" class="shrink-0">Tambah Baris</x-admin.tombol>
        </div>

        @error('itinerary')
            <x-admin.formulir.galat :pesan="$message" class="mb-3" />
        @enderror

        <div class="space-y-3">
            <template x-for="(baris, index) in daftar" :key="index">
                <div class="rounded-lg border border-surface-border p-3">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start">
                        <div class="sm:w-48 sm:shrink-0">
                            <label class="mb-1 block text-xs font-medium text-muted" :for="'itinerary-t-' + index">Hari</label>
                            <input
                                type="text"
                                :id="'itinerary-t-' + index"
                                :name="'itinerary[' + index + '][t]'"
                                :data-kunci="'itinerary.' + index + '.t'"
                                data-wajib="Hari itinerary wajib diisi, mis. &quot;Hari 1&quot;."
                                x-model="baris.t"
                                placeholder="Contoh: Hari 1"
                                maxlength="255"
                                :class="(galatServer['itinerary.' + index + '.t'] || $data.galat?.['itinerary.' + index + '.t']) ? 'border-status-dibatalkan-teks' : 'border-surface-border'"
                                class="{{ $kelasInput }} focus:border-hijau-aksi focus:ring-hijau-aksi"
                            />
                            <p x-cloak x-show="galatServer['itinerary.' + index + '.t'] || $data.galat?.['itinerary.' + index + '.t']" class="mt-1.5 flex items-center gap-1 text-xs text-status-dibatalkan-teks" role="alert">
                                <span class="material-symbols-outlined text-sm" aria-hidden="true">error</span>
                                <span x-text="galatServer['itinerary.' + index + '.t'] || $data.galat?.['itinerary.' + index + '.t']"></span>
                            </p>
                        </div>
                        <div class="min-w-0 flex-1">
                            <label class="mb-1 block text-xs font-medium text-muted" :for="'itinerary-x-' + index">Kegiatan</label>
                            <input
                                type="text"
                                :id="'itinerary-x-' + index"
                                :name="'itinerary[' + index + '][x]'"
                                :data-kunci="'itinerary.' + index + '.x'"
                                data-wajib="Kegiatan itinerary wajib diisi."
                                x-model="baris.x"
                                placeholder="Contoh: Jemput di hotel, perjalanan menuju dataran tinggi"
                                maxlength="500"
                                :class="(galatServer['itinerary.' + index + '.x'] || $data.galat?.['itinerary.' + index + '.x']) ? 'border-status-dibatalkan-teks' : 'border-surface-border'"
                                class="{{ $kelasInput }} focus:border-hijau-aksi focus:ring-hijau-aksi"
                            />
                            <p x-cloak x-show="galatServer['itinerary.' + index + '.x'] || $data.galat?.['itinerary.' + index + '.x']" class="mt-1.5 flex items-center gap-1 text-xs text-status-dibatalkan-teks" role="alert">
                                <span class="material-symbols-outlined text-sm" aria-hidden="true">error</span>
                                <span x-text="galatServer['itinerary.' + index + '.x'] || $data.galat?.['itinerary.' + index + '.x']"></span>
                            </p>
                        </div>
                        <x-admin.tombol-ikon type="button" icon="close" variant="danger" label="Hapus baris" class="sm:mt-6" x-on:click="daftar.splice(index, 1)" x-show="daftar.length > 1" />
                    </div>
                </div>
            </template>
        </div>
    </x-admin.kartu>

    <x-admin.kartu>
        <h2 class="flex items-center gap-2 text-sm font-semibold text-ink">
            <span class="material-symbols-outlined text-lg" aria-hidden="true">info</span>
            Info Praktis
        </h2>
        <p class="mb-4 text-xs text-muted">Kotak informasi di halaman detail: hal yang perlu diketahui tamu sebelum berangkat.</p>
        <div class="grid gap-4 sm:grid-cols-2">
            <x-admin.formulir.masukan name="praktis.pickup" label="Titik Jemput" :value="$praktisAwal['pickup'] ?? null" wajib maxlength="255" placeholder="Area jemput tamu. Contoh: Hotel di Wonosobo, Semarang, atau Yogyakarta" />
            <x-admin.formulir.masukan name="praktis.start" label="Jam Penjemputan" :value="$praktisAwal['start'] ?? null" wajib maxlength="255" placeholder="Contoh: Fleksibel, dipilih tamu di formulir booking" />
            <x-admin.formulir.masukan name="praktis.capacity" label="Keterangan Kapasitas" :value="$praktisAwal['capacity'] ?? null" wajib maxlength="255" placeholder="Contoh: Maks. 4 tamu per booking, di luar guide yang merangkap sopir" />
            {{-- Teks rekomendasi lama dibawa terus agar tidak hilang saat disimpan; tampil ke tamu hanya bila destinasi belum punya mobil tetap. --}}
            <input type="hidden" name="praktis[vehicle]" value="{{ $praktisAwal['vehicle'] ?? '' }}" />
            <x-admin.formulir.masukan name="praktis.tips" label="Tips" :value="$praktisAwal['tips'] ?? null" wajib maxlength="500" placeholder="Persiapan tamu. Contoh: Bawa jaket tebal, suhu pagi bisa mencapai 4°C." />
        </div>
    </x-admin.kartu>

    <x-admin.kartu>
        <h2 class="flex items-center gap-2 text-sm font-semibold text-ink">
            <span class="material-symbols-outlined text-lg" aria-hidden="true">image</span>
            Tampilan di Website
        </h2>
        <p class="mb-4 text-xs text-muted">Gambar sampul dipakai pada kartu dan banner halaman detail.</p>
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2" x-data="{ sampulLama: @js($perjalanan?->img), hapusSampul: false }">
                <x-admin.formulir.label :wajib="! $perjalanan?->img">Gambar Sampul</x-admin.formulir.label>

                @if ($perjalanan?->img)
                    <div x-show="! hapusSampul" class="mb-3 flex flex-wrap items-center gap-4 rounded-lg border border-surface-border p-3">
                        <img src="{{ $perjalanan->img }}" alt="Sampul saat ini" class="h-24 w-36 max-w-full shrink-0 rounded-md object-cover" />
                        <div class="min-w-0 flex-1 basis-40">
                            <p class="text-sm font-medium text-ink">Sampul saat ini</p>
                            <p class="text-xs text-muted">Unggah gambar baru di bawah untuk menggantinya, atau hapus sampul ini.</p>
                        </div>
                        <x-admin.tombol type="button" variant="danger-outline" size="sm" icon="delete" x-on:click="hapusSampul = true">Hapus</x-admin.tombol>
                    </div>
                    <div x-show="hapusSampul" x-cloak class="mb-3 flex items-center justify-between gap-3 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900">
                        <span>Sampul akan dihapus saat disimpan. Situs publik memakai gambar bawaan sampai Anda mengunggah yang baru.</span>
                        <x-admin.tombol type="button" variant="secondary" size="sm" x-on:click="hapusSampul = false">Batalkan</x-admin.tombol>
                    </div>
                    <input type="hidden" name="hapus_img" :value="hapusSampul ? '1' : '0'" />
                @endif

                <x-admin.unggah-berkas
                    name="img"
                    accept="image/jpeg,image/png,image/webp"
                    :pratinjau-gambar="true"
                    :pesan-wajib="$perjalanan?->img ? null : 'Gambar sampul wajib diunggah.'"
                    keterangan="JPG, PNG, atau WebP — maksimal 4 MB, disarankan rasio potret 4:5."
                />
            </div>

            <x-admin.formulir.masukan name="urutan" type="number" label="Urutan Tampil" :value="$perjalanan?->urutan ?? 0" min="0" max="9999" placeholder="0 = paling awal; angka kecil tampil lebih dulu" />
            <x-admin.formulir.sakelar
                name="tampil_beranda"
                :checked="$perjalanan?->tampil_beranda ?? true"
                class="self-end pb-2.5"
            >
                Tampilkan di beranda (nonaktif: hanya muncul di halaman daftar {{ strtolower($namaJenis) }})
            </x-admin.formulir.sakelar>
        </div>
    </x-admin.kartu>
</div>
