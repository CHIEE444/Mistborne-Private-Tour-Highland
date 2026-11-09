@props(['artikel' => null])

@php
    $jadwalAwal = old('published_at', $artikel?->published_at?->format('Y-m-d\TH:i'));
    $batasMin = $artikel ? null : now()->format('Y-m-d\TH:i');
@endphp
<div
    class="space-y-6"
    x-data="{
        ringkasan: @js(old('ringkasan', $artikel?->ringkasan ?? '')),
        jadwal: @js($jadwalAwal ?? ''),
        sekarang: Date.now(),
        init() { setInterval(() => this.sekarang = Date.now(), 15000); },
        isiSekarang() {
            const d = new Date();
            d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
            this.jadwal = d.toISOString().slice(0, 16);
        },
        get labelJadwal() {
            if (! this.jadwal) return 'Belum diatur. Pilih tanggal &amp; jam untuk menerbitkan, atau simpan sebagai draft.';
            const d = new Date(this.jadwal);
            if (isNaN(d)) return '';
            const teks = d.toLocaleString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' });
            return d.getTime() <= this.sekarang
                ? 'Waktu ini sudah tercapai: artikel langsung tayang saat disimpan (' + teks + ').'
                : 'Terbit otomatis pada ' + teks + '. Sebelum itu artikel tidak terlihat publik.';
        },
    }"
>
    <x-admin.kartu>
        <h2 class="flex items-center gap-2 text-sm font-semibold text-ink">
            <span class="material-symbols-outlined text-lg" aria-hidden="true">edit_note</span>
            Seksi 1: Konten Utama
        </h2>
        <p class="mb-4 text-xs text-muted">Judul, ringkasan, dan isi lengkap yang dibaca pengunjung di halaman artikel.</p>

        <div class="space-y-4">
            <x-admin.formulir.masukan name="judul" label="Judul Artikel" :value="$artikel?->judul" wajib />

            @if ($artikel)
                <div>
                    <x-admin.formulir.label>Permalink URL (Slug Otomatis)</x-admin.formulir.label>
                    <div class="flex items-center gap-2 rounded-lg border border-surface-border bg-page px-3.5 py-2.5 text-sm text-muted">
                        <span class="material-symbols-outlined text-base" aria-hidden="true">lock</span>
                        mistborne.id/artikel/{{ $artikel->slug }}
                    </div>
                </div>
            @else
                <x-admin.peringatan type="info">Permalink dibuat otomatis dari judul setelah artikel disimpan.</x-admin.peringatan>
            @endif

            <div>
                <div class="mb-1.5 flex items-center justify-between">
                    <x-admin.formulir.label for="ringkasan" wajib>Ringkasan Eksekutif</x-admin.formulir.label>
                    <span class="text-xs text-muted" x-text="ringkasan.length + ' / 500 karakter'"></span>
                </div>
                <textarea
                    name="ringkasan" id="ringkasan" rows="3" x-model="ringkasan" maxlength="500"
                    data-kunci="ringkasan" data-wajib="Ringkasan wajib diisi."
                    class="w-full rounded-lg border border-surface-border bg-white px-3.5 py-2.5 text-sm text-ink focus:border-hijau-aksi focus:outline-none focus:ring-1 focus:ring-hijau-aksi"
                >{{ old('ringkasan', $artikel?->ringkasan) }}</textarea>
                @error('ringkasan') <x-admin.formulir.galat :pesan="$message" /> @enderror
                <p x-cloak x-show="$data.galat?.ringkasan" class="mt-1.5 flex items-center gap-1 text-xs text-status-dibatalkan-teks" role="alert">
                    <span class="material-symbols-outlined text-sm" aria-hidden="true">error</span>
                    <span x-text="$data.galat?.ringkasan"></span>
                </p>
            </div>

            <x-admin.formulir.editor-teks name="isi" label="Badan Artikel" :value="$artikel?->isi" wajib />
        </div>
    </x-admin.kartu>

    <x-admin.kartu>
        <h2 class="flex items-center gap-2 text-sm font-semibold text-ink">
            <span class="material-symbols-outlined text-lg" aria-hidden="true">image</span>
            Seksi 2: Gambar Sampul <span class="text-status-dibatalkan-teks">*</span>
        </h2>
        <p class="mb-3 text-xs text-muted">
            Gambar unggulan di kartu artikel dan bagian atas halaman artikel. Rasio optimal 1200 × 630 px —
            otomatis dipotong &amp; dikonversi ke WebP. Detail file (format, ukuran, dimensi) tampil setelah gambar dipilih.
        </p>

        @if ($artikel?->imgUrl())
            <div class="mb-3 flex flex-wrap items-center gap-4 rounded-lg border border-surface-border p-3">
                <img src="{{ $artikel->imgUrl() }}" alt="Sampul saat ini" class="h-24 w-44 max-w-full shrink-0 rounded-md object-cover" />
                <div class="min-w-0 flex-1 basis-40">
                    <p class="text-sm font-medium text-ink">Sampul saat ini</p>
                    <p class="text-xs text-muted">Unggah gambar baru di bawah untuk menggantinya.</p>
                </div>
            </div>
        @endif

        <x-admin.unggah-berkas
            name="img"
            accept="image/jpeg,image/png,image/webp"
            :pratinjau-gambar="true"
            :pesan-wajib="$artikel?->imgUrl() ? null : 'Gambar sampul wajib diunggah.'"
            keterangan="JPG, PNG, atau WebP — maksimal 4 MB, minimal 800 × 420 px."
        />
    </x-admin.kartu>

    <x-admin.kartu>
        <h2 class="flex items-center gap-2 text-sm font-semibold text-ink">
            <span class="material-symbols-outlined text-lg" aria-hidden="true">event_available</span>
            Seksi 3: Jadwal Terbit &amp; Kategori
        </h2>
        <p class="mb-4 text-xs text-muted">Atur hari, bulan, dan jam terbit. Artikel tayang otomatis tepat pada waktunya, tanpa perlu menekan tombol lagi.</p>

        <div class="grid gap-4 sm:grid-cols-2">
            <x-admin.formulir.kolom label="Jadwal Tanggal &amp; Jam Terbit" for="published_at" name="published_at">
                <div class="flex flex-wrap gap-2">
                    <input
                        type="datetime-local" name="published_at" id="published_at" x-model="jadwal"
                        @if ($batasMin) min="{{ $batasMin }}" @endif
                        data-kunci="published_at"
                        class="min-w-0 flex-1 basis-48 rounded-lg border bg-white px-3.5 py-2.5 text-sm text-ink focus:outline-none focus:ring-1 {{ $errors->has('published_at') ? 'border-status-dibatalkan-teks focus:border-status-dibatalkan-teks focus:ring-status-dibatalkan-teks' : 'border-surface-border focus:border-hijau-aksi focus:ring-hijau-aksi' }}"
                    />
                    <x-admin.tombol type="button" variant="secondary" x-on:click="isiSekarang()">Sekarang</x-admin.tombol>
                </div>
                <p class="mt-1.5 text-xs text-muted" x-text="labelJadwal"></p>
            </x-admin.formulir.kolom>

            <div>
                <x-admin.formulir.kolom label="Kategori" for="kategori" name="kategori" wajib>
                    <input
                        type="text" name="kategori" id="kategori" list="saran-kategori" maxlength="40" autocomplete="off" placeholder="Pilih dari saran atau ketik kategori baru (3–40 karakter)"
                        value="{{ old('kategori', $artikel?->kategori) }}"
                        data-kunci="kategori" data-wajib="Kategori wajib diisi."
                        required aria-required="true"
                        class="w-full rounded-lg border bg-white px-3.5 py-2.5 text-sm text-ink focus:outline-none focus:ring-1 {{ $errors->has('kategori') ? 'border-status-dibatalkan-teks focus:border-status-dibatalkan-teks focus:ring-status-dibatalkan-teks' : 'border-surface-border focus:border-hijau-aksi focus:ring-hijau-aksi' }}"
                    />
                    <datalist id="saran-kategori">
                        @foreach (\App\Models\Artikel::saranKategori() as $saran)
                            <option value="{{ $saran }}"></option>
                        @endforeach
                    </datalist>
                </x-admin.formulir.kolom>
            </div>
        </div>
    </x-admin.kartu>
</div>
