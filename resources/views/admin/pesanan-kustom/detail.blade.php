<x-tata-letak.admin title="Pesanan Kustom #{{ $pesananKustom->kodeTampilan() }}" group="{{ __('admin.sidebar.grup_operasional') }}">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <a href="{{ route('admin.pesanan-kustom.indeks', $pesananKustom->trashed() ? ['status' => 'arsip'] : []) }}" class="mb-3 inline-flex items-center gap-1 text-sm text-muted hover:text-ink">
                <span class="material-symbols-outlined text-lg" aria-hidden="true">arrow_back</span>
                Pesanan Kustom
            </a>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-xl font-semibold text-ink">#{{ $pesananKustom->kodeTampilan() }}</h1>
                <x-admin.lencana-status :status="$pesananKustom->status" />
                @if ($pesananKustom->penawaranKedaluwarsa())
                    <span class="rounded-full bg-status-dibatalkan-bg px-2.5 py-1 text-xs font-semibold text-status-dibatalkan-teks">Penawaran kedaluwarsa {{ $pesananKustom->batas_penawaran->translatedFormat('d M Y') }}</span>
                @endif
            </div>
            <p class="mt-1 text-sm text-muted">Permintaan dari {{ $pesananKustom->created_at->translatedFormat('d M Y, H:i') }}</p>
        </div>
        @if (! $pesananKustom->trashed())
            <x-admin.tombol as="a" href="{{ route('admin.pesanan-kustom.buat') }}" variant="secondary" icon="add">Input Manual</x-admin.tombol>
        @endif
    </div>

    @if ($pesananKustom->trashed())
        <x-admin.peringatan type="warning" class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <span>Permintaan ini berada di Arsip.</span>
            <form method="POST" action="{{ route('admin.pesanan-kustom.pulihkan', $pesananKustom->id) }}">
                @csrf
                @method('PATCH')
                <x-admin.tombol type="submit" variant="secondary" icon="restore">Pulihkan</x-admin.tombol>
            </form>
        </x-admin.peringatan>
    @else
        {{-- Alur "Hubungi – Susun – Kirim – Resmikan"; sesudah diresmikan, pesanan mengikuti alur pesanan biasa. --}}
        <x-admin.pesanan-kustom.stepper :pesanan-kustom="$pesananKustom" class="mb-6" />
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-admin.kartu>
                <div class="mb-5 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-ink">Gambaran Trip</h2>
                        <p class="mt-1 text-sm text-muted">Rencana awal dan preferensi yang disampaikan tamu.</p>
                    </div>
                    @if (! $pesananKustom->trashed() && ! $pesananKustom->pesanan_id)
                        <button type="button" class="text-sm font-medium text-hijau-aksi hover:underline" onclick="document.getElementById('ubah-permintaan').open = !document.getElementById('ubah-permintaan').open">Ubah data</button>
                    @endif
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-xl bg-page p-4">
                        <p class="text-xs font-medium text-muted">Destinasi yang diinginkan</p>
                        <p class="mt-1 font-medium text-ink">{{ $pesananKustom->destinasi_diinginkan }}</p>
                    </div>
                    <div class="rounded-xl bg-page p-4">
                        <p class="text-xs font-medium text-muted">Jadwal & durasi</p>
                        <p class="mt-1 font-medium text-ink">{{ $pesananKustom->jadwalDiinginkanFormat() }}{{ $pesananKustom->durasi_hari ? ' · '.$pesananKustom->durasi_hari.' hari' : '' }}</p>
                    </div>
                    <div class="rounded-xl bg-page p-4">
                        <p class="text-xs font-medium text-muted">Peserta</p>
                        <p class="mt-1 font-medium text-ink">{{ $pesananKustom->jumlah_orang }} orang</p>
                    </div>
                    <div class="rounded-xl bg-page p-4">
                        <p class="text-xs font-medium text-muted">Area penjemputan</p>
                        <p class="mt-1 font-medium text-ink">{{ $pesananKustom->lokasi_penjemputan ?: 'Belum ditentukan' }}</p>
                    </div>
                    <div class="rounded-xl bg-page p-4">
                        <p class="text-xs font-medium text-muted">Penginapan</p>
                        <p class="mt-1 font-medium text-ink"><x-admin.penginapan :status="$pesananKustom->status_penginapan" :nama="$pesananKustom->nama_hotel" /></p>
                    </div>
                </div>

                @if ($pesananKustom->minat_perjalanan || $pesananKustom->tempo_perjalanan)
                    @php
                        $labelMinat = ['alam' => 'Alam', 'budaya' => 'Budaya', 'kuliner' => 'Kuliner', 'fotografi' => 'Fotografi', 'keluarga' => 'Ramah keluarga', 'petualangan' => 'Petualangan', 'sejarah' => 'Sejarah', 'belanja' => 'Belanja'];
                    @endphp
                    <div class="mt-4">
                        <p class="mb-2 text-xs font-medium text-muted">Preferensi tamu</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($pesananKustom->minat_perjalanan ?? [] as $minat)
                                <span class="rounded-full border border-surface-border bg-white px-3 py-1 text-xs font-medium text-ink">{{ $labelMinat[$minat] ?? ucfirst($minat) }}</span>
                            @endforeach
                            @if ($pesananKustom->tempo_perjalanan)
                                <span class="rounded-full border border-surface-border bg-white px-3 py-1 text-xs font-medium text-ink">Suasana: {{ ucfirst($pesananKustom->tempo_perjalanan) }}</span>
                            @endif
                        </div>
                    </div>
                @endif

                @if ($pesananKustom->catatan)
                    <div class="mt-5 rounded-xl border border-surface-border p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted">Gambaran dari tamu</p>
                        <p class="mt-2 whitespace-pre-line wrap-anywhere text-sm leading-relaxed text-ink">{{ $pesananKustom->catatan }}</p>
                    </div>
                @endif

                @if ($pesananKustom->alasan_tidak_jadi)
                    <div class="mt-4 rounded-xl border border-status-dibatalkan-teks/20 bg-status-dibatalkan-bg p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-status-dibatalkan-teks">Alasan tidak jadi</p>
                        <p class="mt-2 whitespace-pre-line wrap-anywhere text-sm leading-relaxed text-ink">{{ $pesananKustom->alasan_tidak_jadi }}</p>
                    </div>
                @endif

                @if (! $pesananKustom->trashed() && ! $pesananKustom->pesanan_id)
                    <details id="ubah-permintaan" class="mt-5 rounded-xl border border-surface-border" @if ($errors->any()) open @endif>
                        <summary class="cursor-pointer px-4 py-3 text-sm font-medium text-ink">Edit data permintaan</summary>
                        <form method="POST" action="{{ route('admin.pesanan-kustom.perbarui', $pesananKustom) }}" class="space-y-4 border-t border-surface-border p-4" x-data="{ tanggalFleksibel: @js((bool) old('tanggal_fleksibel', $pesananKustom->tanggal_fleksibel)) }">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="minat_perjalanan_present" value="1" />
                            <input type="hidden" name="status" value="{{ $pesananKustom->pesanan_id ? 'disetujui' : $pesananKustom->status->value }}" />
                            <x-admin.formulir.masukan name="nama" label="Nama Tamu" :value="$pesananKustom->nama" placeholder="Nama orang yang menjadi kontak utama pesanan" wajib />
                            <x-admin.formulir.masukan name="whatsapp" label="WhatsApp" :value="$pesananKustom->whatsapp" placeholder="Diawali 08 atau +62, contoh 081234567890" inputmode="numeric" pattern="(08[0-9]{7,12}|628[0-9]{7,12})" maxlength="15" autocomplete="tel" wajib />
                            <x-admin.formulir.masukan name="destinasi_diinginkan" label="Destinasi" :value="$pesananKustom->destinasi_diinginkan" placeholder="Tempat atau kawasan tujuan; rute detail menyusul. Contoh: Dieng dan Bromo" wajib />
                            <x-admin.formulir.kolom label="Tanggal Perjalanan (belum pasti? centang fleksibel)" for="tanggal_diinginkan" name="tanggal_diinginkan" class="max-w-sm">
                                <input type="date" id="tanggal_diinginkan" name="tanggal_diinginkan" value="{{ old('tanggal_diinginkan', $pesananKustom->tanggal_diinginkan?->toDateString()) }}" min="{{ now()->addDays(7)->toDateString() }}" :required="!tanggalFleksibel" :disabled="tanggalFleksibel" class="w-full rounded-lg border border-surface-border bg-white px-3.5 py-2.5 text-sm text-ink focus:border-hijau-aksi focus:outline-none focus:ring-1 focus:ring-hijau-aksi disabled:bg-page" />
                            </x-admin.formulir.kolom>
                            <label class="inline-flex items-center gap-2 text-sm text-muted"><input type="checkbox" name="tanggal_fleksibel" value="1" x-model="tanggalFleksibel" @checked(old('tanggal_fleksibel', $pesananKustom->tanggal_fleksibel)) />Tanggal masih fleksibel</label>
                            <p class="-mt-2 text-xs text-muted">Centang jika tamu belum memiliki tanggal pasti.</p>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <x-admin.formulir.masukan name="durasi_hari" type="number" label="Durasi (hari)" :value="$pesananKustom->durasi_hari" min="1" max="30" placeholder="Jumlah hari, bukan malam" wajib />
                                <x-admin.formulir.masukan name="jumlah_orang" type="number" label="Jumlah orang" :value="$pesananKustom->jumlah_orang" min="1" max="50" placeholder="Total peserta termasuk anak-anak" wajib />
                            </div>
                            <x-admin.formulir.masukan name="lokasi_penjemputan" label="Area penjemputan" :value="$pesananKustom->lokasi_penjemputan" placeholder="Kota, hotel, atau titik temu yang diminta tamu" wajib />
                            <x-admin.formulir.penginapan :all-in="false" :status="$pesananKustom->status_penginapan" :nama="$pesananKustom->nama_hotel" />
                            <fieldset>
                                <legend class="mb-2 text-sm font-medium text-ink">Minat perjalanan</legend>
                                <p class="mb-2 text-xs text-muted">Pilih minat yang disebutkan tamu untuk membantu menyusun rute.</p>
                                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                    @foreach ($labelMinat ?? ['alam' => 'Alam', 'budaya' => 'Budaya', 'kuliner' => 'Kuliner', 'fotografi' => 'Fotografi', 'keluarga' => 'Ramah keluarga', 'petualangan' => 'Petualangan', 'sejarah' => 'Sejarah', 'belanja' => 'Belanja'] as $nilai => $label)
                                        <label class="inline-flex items-center gap-2 text-xs text-muted"><input type="checkbox" name="minat_perjalanan[]" value="{{ $nilai }}" @checked(in_array($nilai, (array) old('minat_perjalanan', $pesananKustom->minat_perjalanan ?? []), true)) />{{ $label }}</label>
                                    @endforeach
                                </div>
                            </fieldset>
                            <x-admin.formulir.pilihan name="tempo_perjalanan" label="Suasana perjalanan" :pilihan="['' => 'Belum ditentukan', 'santai' => 'Santai (banyak waktu di tiap tempat)', 'normal' => 'Seimbang', 'aktif' => 'Padat (ingin banyak tempat)']" :value="$pesananKustom->tempo_perjalanan" />
                            <x-admin.formulir.area-teks name="catatan" label="Gambaran trip dari tamu" :value="$pesananKustom->catatan" placeholder="Pengalaman yang dicari, tempat wajib, aktivitas, dan hal yang ingin dihindari" rows="4" />
                            <x-admin.tombol type="submit">Simpan perubahan</x-admin.tombol>
                        </form>
                    </details>
                @endif
            </x-admin.kartu>

            @if (! $pesananKustom->trashed() && ! $pesananKustom->pesanan_id)
                {{-- Tahap 2 "Susun": status tidak dipilih manual; menyimpan penawaran memindahkan permintaan Baru ke Diproses. --}}
                <x-admin.kartu id="susun-penawaran" class="scroll-mt-24">
                    @php
                        $versiTerakhir = $pesananKustom->riwayatPenawaran->max('versi');
                        $statusPenawaran = match (true) {
                            $pesananKustom->penawaranKedaluwarsa() => ['gaya' => 'border-status-dibatalkan-teks/30 bg-status-dibatalkan-bg text-status-dibatalkan-teks', 'ikon' => 'event_busy', 'judul' => 'Penawaran kedaluwarsa sejak '.$pesananKustom->batas_penawaran->translatedFormat('d M Y'), 'isi' => 'Ubah tanggal "berlaku sampai" lalu simpan; penawaran kembali ke tahap Kirim dan perlu dikirim ulang ke tamu.'],
                            $pesananKustom->penawaran_dikirim_pada !== null => ['gaya' => 'border-status-completed-teks/25 bg-status-completed-bg text-status-completed-teks', 'ikon' => 'send', 'judul' => 'Versi '.$versiTerakhir.' terkirim '.$pesananKustom->penawaran_dikirim_pada->translatedFormat('d M Y, H:i').' WIB · menunggu jawaban tamu', 'isi' => 'Bila rencana, harga, isi "termasuk", atau masa berlaku diubah, status kembali ke Diproses dan versi baru perlu dikirim ulang.'],
                            $versiTerakhir !== null => ['gaya' => 'border-amber-700/30 bg-amber-50 text-amber-800', 'ikon' => 'edit_note', 'judul' => 'Ada perubahan sejak versi '.$versiTerakhir.' dikirim', 'isi' => 'Tamu masih memegang versi lama. Simpan lalu kirim versi terbaru lewat tombol hijau di Aksi Cepat.'],
                            default => ['gaya' => 'border-surface-border bg-page text-muted', 'ikon' => 'edit', 'judul' => 'Draf, belum pernah dikirim ke tamu', 'isi' => 'Isi rencana dan harga per orang, simpan, lalu kirim lewat tombol hijau di Aksi Cepat.'],
                        };
                    @endphp
                    <div class="mb-4">
                        <h2 class="text-base font-semibold text-ink">Susun Penawaran</h2>
                        <p class="mt-1 text-sm text-muted">Isi rencana, harga per orang, dan yang termasuk. Setelah disimpan, kirim lewat tombol hijau di Aksi Cepat.</p>
                    </div>
                    <div class="mb-5 flex items-start gap-3 rounded-xl border px-4 py-3 {{ $statusPenawaran['gaya'] }}">
                        <span class="material-symbols-outlined mt-0.5 text-[20px]!" aria-hidden="true">{{ $statusPenawaran['ikon'] }}</span>
                        <div>
                            <p class="text-sm font-semibold">{{ $statusPenawaran['judul'] }}</p>
                            <p class="mt-0.5 text-xs leading-5 opacity-90">{{ $statusPenawaran['isi'] }}</p>
                        </div>
                    </div>
                    <form
                        method="POST"
                        action="{{ route('admin.pesanan-kustom.perbarui-tindak-lanjut', $pesananKustom) }}"
                        class="space-y-4"
                        x-data="{ harga: @js(old('harga_final', $pesananKustom->harga_final)), orang: @js((int) $pesananKustom->jumlah_orang), rupiah(angka) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(angka || 0); } }"
                        x-on:input="if ($event.target.id === 'harga_final') harga = parseInt($event.target.value.replace(/[^0-9]/g, '')) || null"
                    >
                        @csrf
                        @method('PUT')
                        {{-- Petunjuk isi diletakkan di label (dalam kurung) dan contoh di dalam kolom, bukan teks kecil di luar kolom.
                             Contoh "termasuk" sengaja tanpa penginapan: penginapan trip kustom selalu dipesan dan dibayar tamu sendiri. --}}
                        @php
                            $contohRencana = "Tulis destinasi yang dikunjungi dan urutan kegiatannya per hari.\nContoh:\nHari 1 — jemput di hotel Malang, Museum Angkut (Batu), antar ke penginapan tamu di Batu.\nHari 2 — sunrise Penanjakan, kawah Bromo, savana Teletubbies, antar kembali ke Malang.";
                            $contohTermasuk = "Termasuk (sudah dalam harga per orang): mobil + guide yang merangkap sopir, BBM & tol, parkir, tiket masuk Bromo, jeep Bromo.\nTidak termasuk: penginapan (dipesan dan dibayar tamu), makan, pengeluaran pribadi.\nPenawaran pendukung (opsional, biaya tambahan): dokumentasi drone Rp 350.000/grup, sewa jaket Rp 25.000/orang.";
                        @endphp
                        <x-admin.formulir.area-teks
                            name="rencana_perjalanan"
                            label="Rencana yang akan ditawarkan (destinasi & kegiatan per hari)"
                            :value="$pesananKustom->rencana_perjalanan"
                            :placeholder="$contohRencana"
                            rows="5"
                        />
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <x-admin.formulir.uang name="harga_final" label="Harga per orang" :value="$pesananKustom->harga_final" :nullable="true" placeholder="Harga untuk 1 peserta, mis. 1.500.000" />
                                <p class="mt-2 rounded-lg bg-page px-3 py-2 text-xs text-muted" aria-live="polite">
                                    Total grup:
                                    <span class="font-semibold text-ink" x-text="harga ? rupiah(harga * orang) : '—'">{{ $pesananKustom->hargaTotalFormat() ?? '—' }}</span>
                                    <span x-show="harga" x-text="'(' + rupiah(harga) + ' × ' + orang + ' orang)'"></span>
                                </p>
                            </div>
                            <x-admin.formulir.tanggal name="batas_penawaran" label="Penawaran berlaku sampai (kosongkan bila tidak dibatasi; lewat tanggal ini penawaran tidak bisa diresmikan)" :value="$pesananKustom->batas_penawaran?->toDateString()" :min="now()->toDateString()" />
                        </div>
                        <x-admin.formulir.area-teks
                            name="rincian_penawaran"
                            label="Yang termasuk & tidak termasuk (penginapan selalu tidak termasuk)"
                            :value="$pesananKustom->rincian_penawaran"
                            :placeholder="$contohTermasuk"
                            rows="5"
                        />
                        <div class="grid gap-4 sm:grid-cols-2">
                            {{-- Guide dipilih dari daftar guide aktif (seperti pesanan biasa); nama lama yang tidak ada di daftar tetap ditampilkan. --}}
                            @php
                                $pilihanGuide = \App\Models\PersonelOperasional::query()->where('peran', \App\Models\PersonelOperasional::PERAN)->where('aktif', true)->orderBy('nama')->pluck('nama', 'nama')->all();
                                if (filled($pesananKustom->nama_pendamping) && ! array_key_exists($pesananKustom->nama_pendamping, $pilihanGuide)) {
                                    $pilihanGuide[$pesananKustom->nama_pendamping] = $pesananKustom->nama_pendamping;
                                }
                            @endphp
                            <x-admin.formulir.pilihan name="nama_pendamping" label="Rencana guide (penugasan resmi lewat Operasional setelah jadi pesanan resmi)" :pilihan="$pilihanGuide" :value="$pesananKustom->nama_pendamping" placeholder="Pilih guide yang direncanakan (opsional)" />
                            <x-admin.formulir.area-teks name="catatan_admin" label="Catatan guide" :value="$pesananKustom->catatan_admin" placeholder="Catatan untuk guide, mis. tamu membawa lansia atau ingin berangkat lebih pagi. Tidak ikut dikirim ke tamu." rows="2" />
                        </div>
                        <x-admin.tombol type="submit" icon="save">Simpan Penawaran</x-admin.tombol>
                    </form>
                </x-admin.kartu>
            @endif
        </div>

        <aside class="space-y-6">
            @unless ($pesananKustom->trashed())
                <x-admin.pesanan-kustom.aksi-cepat :pesanan-kustom="$pesananKustom" />
                @unless ($pesananKustom->pesanan_id)
                    <x-admin.pesanan-kustom.pesan-whatsapp :pesanan-kustom="$pesananKustom" />
                @endunless
            @endunless

            @if ($pesananKustom->riwayatPenawaran->isNotEmpty())
                <x-admin.kartu>
                    <h2 class="mb-3 text-base font-semibold text-ink">Riwayat Penawaran</h2>
                    <p class="mb-4 text-sm text-muted">Versi yang pernah dikirim tersimpan untuk ditinjau kembali.</p>
                    <div class="space-y-3">
                        @foreach ($pesananKustom->riwayatPenawaran as $riwayat)
                            @php
                                $snapshot = $riwayat->snapshot;
                            @endphp
                            <details class="rounded-xl border border-surface-border p-3" @if ($loop->first) open @endif>
                                <summary class="flex cursor-pointer flex-wrap items-center gap-2 text-sm font-medium text-ink">
                                    Versi {{ $riwayat->versi }} · {{ $riwayat->terkirim_pada->translatedFormat('d M Y, H:i') }} WIB
                                    @if ($riwayat->versi === $pesananKustom->riwayatPenawaran->max('versi'))
                                        <span class="rounded-full bg-status-completed-bg px-2 py-0.5 text-[10px] font-semibold text-status-completed-teks">Terbaru</span>
                                    @endif
                                </summary>
                                <div class="mt-3 space-y-1.5 border-t border-surface-border pt-3 text-sm">
                                    <p><span class="text-muted">Destinasi:</span> {{ $snapshot['destinasi'] ?? '—' }}</p>
                                    <p><span class="text-muted">Jadwal:</span> {{ $snapshot['jadwal'] ?? '—' }}{{ ! empty($snapshot['durasi_hari']) ? ' · '.$snapshot['durasi_hari'].' hari' : '' }}</p>
                                    <p><span class="text-muted">Peserta:</span> {{ $snapshot['jumlah_orang'] ?? '—' }} orang</p>
                                    <p><span class="text-muted">Harga:</span> {{ \App\Bantuan\FormatRupiah::dari($snapshot['harga_per_orang'] ?? null) }} × {{ $snapshot['jumlah_orang'] ?? '—' }} orang = <span class="font-semibold">{{ \App\Bantuan\FormatRupiah::dari($snapshot['harga_total'] ?? null) }}</span></p>
                                    <p><span class="text-muted">Berlaku sampai:</span> {{ ! empty($snapshot['batas_penawaran']) ? \Illuminate\Support\Carbon::parse($snapshot['batas_penawaran'])->translatedFormat('d M Y') : 'Tidak dibatasi' }}</p>
                                    @if (! empty($snapshot['rencana_perjalanan']))<p class="whitespace-pre-line wrap-anywhere"><span class="text-muted">Rencana:</span> {{ $snapshot['rencana_perjalanan'] }}</p>@endif
                                    @if (! empty($snapshot['rincian_penawaran']))<p class="whitespace-pre-line wrap-anywhere"><span class="text-muted">Termasuk:</span> {{ $snapshot['rincian_penawaran'] }}</p>@endif
                                    @if ($riwayat->pengguna)<p class="pt-1 text-xs text-muted">Dicatat oleh {{ $riwayat->pengguna->name }}</p>@endif
                                </div>
                            </details>
                        @endforeach
                    </div>
                </x-admin.kartu>
            @endif
        </aside>
    </div>

    @if (! $pesananKustom->trashed() && ! $pesananKustom->pesanan && $pesananKustom->status === \App\Enums\StatusPesananKustom::MenungguJawaban && ! $pesananKustom->tanggal_fleksibel && $pesananKustom->tanggal_diinginkan)
        <x-admin.pesanan-kustom.modal-jadikan-resmi :pesanan-kustom="$pesananKustom" />
    @endif
    @unless ($pesananKustom->trashed())
        <x-admin.pesanan-kustom.dialog-arsipkan :pesanan-kustom="$pesananKustom" />
    @endunless
    @if (! $pesananKustom->trashed() && ! $pesananKustom->pesanan_id)
        @if ($pesananKustom->tahapAlur() === 1 && $pesananKustom->dataBelumLengkap() !== [])
            <x-admin.pesanan-kustom.modal-data-belum-lengkap :pesanan-kustom="$pesananKustom" />
        @endif
        @if ($pesananKustom->status !== \App\Enums\StatusPesananKustom::TidakJadi)
            <x-admin.pesanan-kustom.modal-tidak-jadi :pesanan-kustom="$pesananKustom" />
        @endif
    @endif
</x-tata-letak.admin>
