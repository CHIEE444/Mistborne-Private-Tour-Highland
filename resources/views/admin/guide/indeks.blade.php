<x-tata-letak.admin title="Guide" group="Operasional">
    <x-admin.kepala-halaman title="Guide" subtitle="Kelola guide yang merangkap driver: kontak, data SIM, ketersediaan, dan penugasan.">
        <x-slot:actions>
            <x-admin.tombol type="button" icon="add" x-on:click="window.dispatchEvent(new CustomEvent('buka-modal', { detail: { nama: 'tambah-personel' } }))">Tambah Personel</x-admin.tombol>
        </x-slot:actions>
    </x-admin.kepala-halaman>

    <div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <x-admin.kartu class="flex items-center gap-4 !p-4">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-status-completed-bg text-status-completed-teks"><span class="material-symbols-outlined" aria-hidden="true">hiking</span></span>
            <div><p class="text-xs text-muted">Guide siap</p><p class="mt-0.5 text-xl font-semibold tabular-nums text-ink">{{ $personel->filter(fn ($item) => $item->peran === 'guide' && $item->aktif && $statusPenugasan[$item->id]['status'] === 'siap')->count() }}</p></div>
        </x-admin.kartu>
        <x-admin.kartu class="flex items-center gap-4 !p-4">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-status-dihubungi-bg text-status-dihubungi-teks"><span class="material-symbols-outlined" aria-hidden="true">hiking</span></span>
            <div><p class="text-xs text-muted">Guide bertugas</p><p class="mt-0.5 text-xl font-semibold tabular-nums text-ink">{{ $personel->filter(fn ($item) => $item->peran === 'guide' && $item->aktif && $statusPenugasan[$item->id]['status'] === 'bertugas')->count() }}</p></div>
        </x-admin.kartu>
        <x-admin.kartu class="flex items-center gap-4 !p-4">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-status-payment-bg text-status-payment-teks"><span class="material-symbols-outlined" aria-hidden="true">badge</span></span>
            <div><p class="text-xs text-muted">Data SIM belum lengkap</p><p class="mt-0.5 text-xl font-semibold tabular-nums text-ink">{{ $personel->filter(fn ($item) => $item->aktif && ! $item->dataMengemudiLengkap())->count() }}</p></div>
        </x-admin.kartu>
        <x-admin.kartu class="flex items-center gap-4 !p-4">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-status-dibatalkan-bg text-status-dibatalkan-teks"><span class="material-symbols-outlined" aria-hidden="true">event_busy</span></span>
            <div><p class="text-xs text-muted">SIM habis ≤ 30 hari</p><p class="mt-0.5 text-xl font-semibold tabular-nums text-ink">{{ $personel->filter(fn ($item) => $item->aktif && $item->masa_berlaku_sim && $item->masa_berlaku_sim->lte(today()->addDays(30)))->count() }}</p></div>
        </x-admin.kartu>
    </div>

    <x-admin.kartu :padding="false" class="overflow-hidden">
        <div class="flex items-center justify-between gap-3 border-b border-surface-border px-5 py-4 sm:px-6">
            <div>
                <h2 class="font-semibold text-ink">Daftar personel</h2>
                <p class="mt-0.5 text-xs text-muted">Buka detail penugasan atau ubah profil tiap personel.</p>
            </div>
            <span class="shrink-0 rounded-full bg-page px-2.5 py-1 text-xs font-medium text-muted">{{ $personel->where('aktif', true)->count() }} aktif</span>
        </div>

        @if ($personel->isEmpty())
            <div class="p-6"><x-admin.keadaan-kosong icon="badge" judul="Belum ada personel" keterangan="Tambahkan guide (merangkap driver) lewat tombol Tambah Personel di atas." /></div>
        @else
            @php
                $personelDraft = $personel->firstWhere('id', (int) old('edit_personel_id'));
                $kategoriDraft = $personelDraft ? old('personel.'.$personelDraft->id.'.peran', $personelDraft->peran) : 'guide';
            @endphp
            <div x-data="{ editPersonel: @js($personelDraft?->id), kategoriAktif: @js($kategoriDraft), filterStatus: { guide: 'semua' }, cariPersonel: '' }" class="p-4 sm:p-5">
                <label class="relative mb-4 block max-w-xl">
                    <span class="sr-only">Cari personel berdasarkan nama, WhatsApp, email, wilayah, atau keahlian</span>
                    <span class="material-symbols-outlined pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 text-lg text-muted" aria-hidden="true">search</span>
                    <input type="search" x-model.debounce.150ms="cariPersonel" maxlength="100" autocomplete="off" aria-describedby="bantuan-cari-personel" placeholder="Cari nama, WhatsApp, email, wilayah, keahlian..." class="w-full rounded-full border border-surface-border bg-white py-2.5 ps-10 pe-4 text-sm text-ink placeholder:text-muted focus:border-hijau-aksi focus:outline-none focus:ring-1 focus:ring-hijau-aksi" />
                    <span id="bantuan-cari-personel" class="sr-only">Bisa mencari nama, nomor WhatsApp, email, wilayah, keahlian, nomor SIM, dan pengalaman.</span>
                </label>
                @foreach (['guide' => 'Guide'] as $peranKategori => $namaKategori)
                    @php
                        $personelKategori = $personel->where('peran', $peranKategori);
                        $jumlahStatus = [
                            'semua' => $personelKategori->count(),
                            'bertugas' => $personelKategori->filter(fn ($orang) => $orang->aktif && $statusPenugasan[$orang->id]['status'] === 'bertugas')->count(),
                            'menunggu' => $personelKategori->filter(fn ($orang) => $orang->aktif && $statusPenugasan[$orang->id]['status'] === 'menunggu')->count(),
                            'tidak_bertugas' => $personelKategori->filter(fn ($orang) => $orang->aktif && ! in_array($statusPenugasan[$orang->id]['status'], ['bertugas', 'menunggu'], true))->count(),
                            'nonaktif' => $personelKategori->where('aktif', false)->count(),
                        ];
                    @endphp
                    <div id="panel-{{ $peranKategori }}" role="tabpanel" aria-labelledby="tab-{{ $peranKategori }}" x-show="kategoriAktif === '{{ $peranKategori }}'" x-cloak>
                        @if ($personelKategori->isEmpty())
                            <div class="rounded-xl border border-dashed border-surface-border px-4 py-5 text-center text-xs text-muted">
                                Belum ada {{ strtolower($namaKategori) }} terdaftar.
                            </div>
                        @else
                            <div class="mb-4 flex flex-wrap gap-2" role="group" aria-label="Filter status {{ strtolower($namaKategori) }}">
                                @foreach ([
                                    'semua' => 'Semua',
                                    'bertugas' => 'Bertugas',
                                    'menunggu' => 'Menunggu konfirmasi',
                                    'tidak_bertugas' => 'Tidak bertugas',
                                    'nonaktif' => 'Nonaktif',
                                ] as $filterNilai => $filterLabel)
                                    <button type="button" x-on:click="filterStatus['{{ $peranKategori }}'] = '{{ $filterNilai }}'" :aria-pressed="(filterStatus['{{ $peranKategori }}'] === '{{ $filterNilai }}').toString()" :class="filterStatus['{{ $peranKategori }}'] === '{{ $filterNilai }}' ? 'border-admin-sidebar bg-admin-sidebar text-white' : 'border-surface-border bg-white text-muted hover:bg-page'" class="inline-flex min-h-9 items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-hijau-aksi">
                                        {{ $filterLabel }} <span @class(['rounded-full px-1.5 py-0.5 text-[10px] tabular-nums', 'bg-white/15' => $filterNilai === 'semua', 'bg-page' => $filterNilai !== 'semua'])>{{ $jumlahStatus[$filterNilai] }}</span>
                                    </button>
                                @endforeach
                            </div>
                            <div class="divide-y divide-surface-border rounded-xl border border-surface-border">
                @foreach ($personelKategori as $item)
                    @php
                        $infoTugas = $statusPenugasan[$item->id];
                        $prefixPersonel = "personel.{$item->id}.";
                        $filterStatusItem = ! $item->aktif ? 'nonaktif' : match ($infoTugas['status']) {
                            'bertugas' => 'bertugas',
                            'menunggu' => 'menunggu',
                            default => 'tidak_bertugas',
                        };
                        $statusLabel = ! $item->aktif ? 'Nonaktif' : match ($infoTugas['status']) {
                            'bertugas' => 'Sedang bertugas',
                            'terjadwal' => 'Terjadwal',
                            'menunggu' => 'Menunggu konfirmasi',
                            default => 'Siap ditugaskan',
                        };
                    $nomorWhatsappCari = preg_replace('/^62/', '0', preg_replace('/\D+/', '', (string) $item->whatsapp));
                    $kataCariPersonel = mb_strtolower(implode(' ', array_filter([
                        $item->nama,
                        $nomorWhatsappCari,
                        $item->whatsapp,
                        $item->email,
                        $item->wilayah_operasi,
                        $item->keahlian,
                        $item->nomor_sim,
                        $item->pengalaman_tahun,
                    ])));
                    @endphp
                    <article data-personel-item data-filter-status="{{ $filterStatusItem }}" data-cari-personel="{{ $kataCariPersonel }}" x-show="(filterStatus['{{ $peranKategori }}'] === 'semua' || filterStatus['{{ $peranKategori }}'] === '{{ $filterStatusItem }}') && (cariPersonel.trim() === '' || cariPersonel.trim().toLocaleLowerCase('id-ID').split(/\s+/).every(kata => $el.dataset.cariPersonel.includes(kata)))" x-cloak class="p-4 sm:p-5">
                        {{-- Landscape: di layar lebar satu baris memuat identitas, kontak & profil, lalu penugasan secara berdampingan. --}}
                        <div class="grid gap-4 lg:grid-cols-2 lg:gap-6 xl:grid-cols-[minmax(0,15rem)_minmax(0,1fr)_minmax(0,1.15fr)]">
                        <div class="flex flex-wrap items-start justify-between gap-3 lg:col-span-2 xl:col-span-1 xl:flex-col xl:justify-start">
                            <div class="flex min-w-0 items-center gap-3">
                                @if ($item->foto_profil)
                                    <img src="{{ route('admin.guide.foto', $item) }}" alt="Foto {{ $item->nama }}" class="h-12 w-12 shrink-0 rounded-full border border-surface-border object-cover" />
                                @else
                                    <span @class([
                                        'flex h-12 w-12 shrink-0 items-center justify-center rounded-full text-sm font-semibold',
                                        'bg-status-dihubungi-bg text-status-dihubungi-teks' => $item->peran === 'guide',
                                    ])>{{ \Illuminate\Support\Str::of($item->nama)->explode(' ')->filter()->take(2)->map(fn ($kata) => mb_substr($kata, 0, 1))->implode('') }}</span>
                                @endif
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="font-semibold wrap-anywhere text-ink">{{ $item->nama }}</h3>
                                        <span @class([
                                            'rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide',
                                            'bg-status-dihubungi-bg text-status-dihubungi-teks' => $item->peran === 'guide',
                                            ])>{{ $item->peran }}</span>
                                        {{-- "Menunggu konfirmasi" solid (amber-700 + teks putih, kontras ±5:1) supaya paling mudah terlihat di antara status lain. --}}
                                        <span @class([
                                            'inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-[11px] font-bold leading-none',
                                            'bg-gray-100 text-gray-500' => ! $item->aktif,
                                            'border-status-completed-teks/25 bg-status-completed-bg text-status-completed-teks' => $item->aktif && $infoTugas['status'] === 'siap',
                                            'border-amber-700 bg-amber-700 text-white shadow-sm' => $item->aktif && $infoTugas['status'] === 'menunggu',
                                            'border-status-payment-teks/25 bg-status-payment-bg text-status-payment-teks' => $item->aktif && $infoTugas['status'] === 'terjadwal',
                                            'border-status-dihubungi-teks/25 bg-status-dihubungi-bg text-status-dihubungi-teks' => $item->aktif && $infoTugas['status'] === 'bertugas',
                                        ])>
                                            @if ($item->aktif && $infoTugas['status'] === 'menunggu')
                                                <span class="material-symbols-outlined text-[14px]!" aria-hidden="true">schedule</span>
                                            @endif
                                            {{ $statusLabel }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-xs text-muted">{{ $item->pengalaman_tahun ? $item->pengalaman_tahun.' tahun pengalaman' : '—' }}{{ $item->wilayah_operasi ? ' · '.$item->wilayah_operasi : '' }}</p>
                                </div>
                            </div>
                            <button type="button" x-on:click="editPersonel = editPersonel === {{ $item->id }} ? null : {{ $item->id }}" class="inline-flex h-9 items-center gap-1.5 rounded-full border border-surface-border px-3 text-xs font-semibold text-ink transition hover:bg-page focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-hijau-aksi" :aria-expanded="(editPersonel === {{ $item->id }}).toString()" aria-label="Ubah {{ $item->nama }}">
                                <span class="material-symbols-outlined text-base" x-text="editPersonel === {{ $item->id }} ? 'close' : 'edit'">edit</span><span x-text="editPersonel === {{ $item->id }} ? 'Tutup' : 'Ubah'">Ubah</span>
                            </button>
                        </div>

                            <section class="min-w-0" aria-label="Kontak dan profil {{ $item->nama }}">
                                <p class="mb-3 text-[10px] font-bold uppercase tracking-[.12em] text-muted">Kontak & profil</p>
                                <div class="flex flex-wrap gap-2">
                                    @if ($item->whatsapp)
                                        <a href="https://wa.me/{{ preg_replace('/^0/', '62', preg_replace('/\D+/', '', $item->whatsapp)) }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-9 items-center gap-2 rounded-lg border border-surface-border bg-white px-3 py-2 text-xs font-medium text-ink transition hover:border-status-completed-teks/40 hover:bg-status-completed-bg/50">
                                            <span class="material-symbols-outlined text-base text-status-completed-teks" aria-hidden="true">chat</span>{{ preg_replace('/^62/', '0', (string) $item->whatsapp) }}
                                        </a>
                                    @else
                                        <span class="inline-flex min-h-9 items-center rounded-lg bg-page px-3 py-2 text-xs text-muted">WhatsApp belum diisi</span>
                                    @endif
                                    @if ($item->email)
                                        <a href="mailto:{{ $item->email }}" class="inline-flex min-h-9 max-w-full items-center gap-2 rounded-lg border border-surface-border bg-white px-3 py-2 text-xs font-medium text-ink transition hover:bg-page">
                                            <span class="material-symbols-outlined text-base text-muted" aria-hidden="true">mail</span><span class="break-all">{{ $item->email }}</span>
                                        </a>
                                    @else
                                        <span class="inline-flex min-h-9 items-center rounded-lg bg-page px-3 py-2 text-xs text-muted">Email belum diisi</span>
                                    @endif
                                </div>

                                <div class="mt-3 flex flex-wrap gap-x-4 gap-y-2 text-xs leading-5 text-muted">
                                    <span class="inline-flex items-center gap-1.5"><span class="material-symbols-outlined text-[16px]" aria-hidden="true">location_on</span>{{ $item->wilayah_operasi ?: 'Wilayah belum diisi' }}</span>
                                </div>
                                <div class="mt-3 rounded-lg border border-surface-border bg-white px-3 py-2 text-xs leading-5">
                                    @if ($item->dataMengemudiLengkap())
                                        @php $simHabisSegera = $item->masa_berlaku_sim->lt(today()); $simMendekati = ! $simHabisSegera && $item->masa_berlaku_sim->lte(today()->addDays(30)); @endphp
                                        <p class="text-ink"><span class="material-symbols-outlined align-[-3px] text-[16px] text-muted" aria-hidden="true">badge</span> SIM {{ $item->jenis_sim }} · {{ $item->nomor_sim }} · berlaku s.d. <span @class(['font-semibold', 'text-status-dibatalkan-teks' => $simHabisSegera, 'text-amber-800' => $simMendekati])>{{ $item->masa_berlaku_sim->translatedFormat('d M Y') }}{{ $simHabisSegera ? ' (SUDAH HABIS)' : ($simMendekati ? ' (segera habis)' : '') }}</span></p>
                                        <p class="text-muted">Mengemudi {{ $item->pengalaman_mengemudi_tahun }} tahun · menguasai {{ $item->kendaraan_dikuasai ?: '—' }}</p>
                                    @else
                                        <p class="font-medium text-status-dibatalkan-teks"><span class="material-symbols-outlined align-[-3px] text-[16px]" aria-hidden="true">warning</span> Data SIM belum lengkap — guide belum bisa ditugaskan. Lengkapi lewat tombol Ubah.</p>
                                    @endif
                                </div>
                                <p class="mt-2 text-xs leading-5 text-muted"><span class="font-semibold text-ink">Keahlian:</span> {{ $item->keahlian ?: 'Belum diisi' }}</p>
                                @if ($item->catatan)
                                    <p class="mt-2 max-w-prose text-xs leading-5 text-muted"><span class="font-semibold text-ink">Catatan:</span> {{ $item->catatan }}</p>
                                @endif
                            </section>

                            <section class="min-w-0 self-start rounded-xl bg-page/70 p-3 sm:p-4" aria-label="Penugasan mendatang {{ $item->nama }}">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <p class="text-[10px] font-bold uppercase tracking-[.12em] text-muted">Penugasan mendatang</p>
                                        <p class="mt-1 text-xs text-muted">{{ $infoTugas['jadwal']->isNotEmpty() ? 'Jadwal aktif personel' : 'Belum ada jadwal aktif' }}</p>
                                    </div>
                                    @if ($infoTugas['jadwal']->isNotEmpty())
                                        <details class="group">
                                            <summary class="inline-flex min-h-9 cursor-pointer list-none items-center gap-1.5 rounded-lg border border-surface-border bg-white px-3 py-2 text-xs font-semibold text-ink transition hover:border-admin-sidebar/30 hover:bg-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-hijau-aksi">
                                                <span class="tabular-nums">{{ $infoTugas['jadwal']->count() }} jadwal</span>
                                                <span class="material-symbols-outlined text-base transition-transform duration-200 group-open:rotate-180" aria-hidden="true">keyboard_arrow_down</span>
                                            </summary>
                                            <div class="mt-3 max-h-80 divide-y divide-surface-border overflow-y-auto scroll-smooth border-y border-surface-border [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                                                @foreach ($infoTugas['jadwal'] as $pesananTugas)
                                                    @php
                                                        $statusTugas = $pesananTugas->status_konfirmasi_guide;
                                                        $waktuRespons = $pesananTugas->guide_dikonfirmasi_at;
                                                    @endphp
                                                    <div class="py-3 first:pt-2 last:pb-2">
                                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                                            <p class="font-semibold text-ink">{{ $pesananTugas->kode }} · {{ $pesananTugas->nama_perjalanan }}</p>
                                                            <span @class([
                                                                'rounded-full px-2 py-0.5 text-[11px] font-semibold',
                                                                'bg-amber-700 text-white' => $statusTugas === 'menunggu',
                                                                'bg-status-completed-bg text-status-completed-teks' => $statusTugas === 'diterima',
                                                                'bg-white text-muted' => ! in_array($statusTugas, ['menunggu', 'diterima'], true),
                                                            ])>{{ match ($statusTugas) { 'menunggu' => 'Menunggu konfirmasi', 'diterima' => 'Tugas diterima', default => 'Belum dikonfirmasi' } }}</span>
                                                        </div>
                                                        <p class="mt-1 text-muted">{{ $pesananTugas->tanggal_trip->translatedFormat('l, d M Y') }}@if ($pesananTugas->tanggal_selesai && ! $pesananTugas->tanggal_selesai->isSameDay($pesananTugas->tanggal_trip)) — {{ $pesananTugas->tanggal_selesai->translatedFormat('d M Y') }} @endif · {{ $pesananTugas->waktu_pickup ? substr((string) $pesananTugas->waktu_pickup, 0, 5).' WIB' : 'Jam belum ditentukan' }}</p>
                                                        <p class="text-muted">Jemput: {{ $pesananTugas->lokasi_pickup ?: 'Belum ditentukan' }}</p>
                                                        @if ($waktuRespons)<p class="mt-1 text-muted">Dikonfirmasi {{ $waktuRespons->timezone(config('app.timezone'))->translatedFormat('d M Y, H:i') }} WIB</p>@endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </details>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-lg border border-dashed border-surface-border bg-white/70 px-3 py-2 text-xs text-muted"><span class="material-symbols-outlined text-base" aria-hidden="true">event_available</span>Siap menerima tugas</span>
                                    @endif
                                </div>
                            </section>
                        </div>

                        <div x-cloak x-show="editPersonel === {{ $item->id }}" x-transition class="mt-5 rounded-xl border border-surface-border bg-page/60 p-4 sm:p-5">
                            <form method="POST" action="{{ route('admin.guide.perbarui', $item) }}" enctype="multipart/form-data" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" x-data="{ fotoPratinjau: null, namaBerkas: '' }">
                                @csrf @method('PUT')
                                <input type="hidden" name="edit_personel_id" value="{{ $item->id }}" />
                                <div class="col-span-full">
                                    <label for="foto-{{ $item->id }}" class="mb-1.5 block text-sm font-medium text-ink">Foto profil</label>
                                    <div class="flex flex-wrap items-center gap-3">
                                        <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-full border border-surface-border bg-page">
                                            <img x-show="fotoPratinjau" x-cloak :src="fotoPratinjau" alt="Pratinjau foto profil baru" class="h-full w-full object-cover" />
                                            @if ($item->foto_profil)
                                                <img x-show="!fotoPratinjau" src="{{ route('admin.guide.foto', $item) }}" alt="Foto {{ $item->nama }} saat ini" class="h-full w-full object-cover" />
                                            @else
                                                <span x-show="!fotoPratinjau" class="material-symbols-outlined text-2xl text-muted" aria-hidden="true">person</span>
                                            @endif
                                        </div>
                                        <div>
                                            <input id="foto-{{ $item->id }}" type="file" name="personel[{{ $item->id }}][foto]" accept="image/jpeg,image/png,image/webp" class="peer sr-only" x-on:change="namaBerkas = $event.target.files.length ? $event.target.files[0].name : ''; fotoPratinjau = $event.target.files.length ? URL.createObjectURL($event.target.files[0]) : null" />
                                            <label for="foto-{{ $item->id }}" class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-surface-border bg-white px-3.5 py-2 text-xs font-semibold text-ink transition hover:bg-page peer-focus-visible:outline-none peer-focus-visible:ring-2 peer-focus-visible:ring-hijau-aksi">
                                                <span class="material-symbols-outlined text-base" aria-hidden="true">upload_file</span>Pilih foto
                                            </label>
                                            <span class="min-w-0 break-all text-xs text-muted" x-text="namaBerkas || 'Belum ada berkas dipilih'">Belum ada berkas dipilih</span>
                                        </div>
                                    </div>
                                    <p class="mt-1 text-xs text-muted">JPG, PNG, atau WebP, maksimal 5 MB. Kosongkan jika tidak mengganti foto.</p>
                                    @error($prefixPersonel.'foto') <p class="mt-1 text-xs text-status-dibatalkan-teks">{{ $message }}</p> @enderror
                                </div>
                                <x-admin.formulir.masukan :name="$prefixPersonel.'nama'" label="Nama" :value="$item->nama" />
                                <x-admin.formulir.masukan :name="$prefixPersonel.'whatsapp'" label="WhatsApp" type="tel" placeholder="Diawali 08 atau +62, contoh 081234567890" pattern="\+?[0-9][0-9 ().\-]{8,22}" title="Gunakan nomor WhatsApp yang diawali 08 atau +62, contoh 081234567890." :value="str_starts_with((string) $item->whatsapp, '62') ? '0'.substr((string) $item->whatsapp, 2) : $item->whatsapp" />
                                <x-admin.formulir.masukan :name="$prefixPersonel.'email'" label="Email kontak" type="email" placeholder="Tautan penugasan dikirim ke email ini" :value="$item->email" />
                                <x-admin.formulir.masukan :name="$prefixPersonel.'wilayah_operasi'" label="Wilayah operasi" :value="$item->wilayah_operasi" />
                                <x-admin.formulir.masukan :name="$prefixPersonel.'pengalaman_tahun'" label="Pengalaman (tahun)" type="number" min="0" max="60" placeholder="Opsional; kosongkan atau 0 untuk fresh graduate" :value="$item->pengalaman_tahun" />
                                <x-admin.formulir.pilihan :name="$prefixPersonel.'status_operasional'" label="Status ketersediaan" :pilihan="['siap' => 'Siap ditugaskan', 'bertugas' => 'Sedang bertugas']" :value="$item->status_operasional" />
                                <x-admin.personel.data-mengemudi :awalan="$prefixPersonel" :personel="$item" />
                                <x-admin.formulir.area-teks :name="$prefixPersonel.'keahlian'" label="Keahlian / spesialisasi" rows="2" :value="$item->keahlian" />
                                <x-admin.formulir.area-teks :name="$prefixPersonel.'catatan'" label="Catatan tambahan" rows="2" :value="$item->catatan" />
                                <div class="space-y-3 sm:col-span-2 lg:col-span-1 lg:pt-7">
                                <input type="hidden" name="personel[{{ $item->id }}][aktif]" value="0" />
                                <label class="flex items-center gap-2 text-sm text-ink"><input type="checkbox" name="personel[{{ $item->id }}][aktif]" value="1" @checked(old($prefixPersonel.'aktif', $item->aktif)) class="rounded border-surface-border text-hijau-aksi focus:ring-hijau-aksi"> Aktif untuk penugasan</label>
                                @error($prefixPersonel.'aktif') <p class="text-xs text-status-dibatalkan-teks">{{ $message }}</p> @enderror
                                @if ($item->user)
                                    <p class="text-sm text-muted">Akun Panel Guide aktif: <span class="font-medium break-all text-ink">{{ $item->user->email }}</span></p>
                                @else
                                    <label class="flex items-start gap-2 text-sm text-ink"><input type="checkbox" name="buat_akun_panel" value="1" class="mt-0.5 rounded border-surface-border text-hijau-aksi focus:ring-hijau-aksi"> <span>Buat akun Panel Guide<span class="block text-xs text-muted">Masuk dengan email kontak dan kata sandi &ldquo;{{ config('mistborne.sandi_bawaan_guide') }}&rdquo;.</span></span></label>
                                @endif
                                </div>
                                <div class="col-span-full flex items-center justify-between gap-3 border-t border-surface-border pt-3">
                                    <x-admin.tombol type="button" variant="danger" size="sm" icon="delete" x-on:click="window.dispatchEvent(new CustomEvent('buka-modal', { detail: { nama: 'hapus-personel-{{ $item->id }}' } }))">Hapus personel</x-admin.tombol>
                                    <x-admin.tombol type="submit" size="sm" icon="save">Simpan perubahan</x-admin.tombol>
                                </div>
                            </form>
                        </div>
                    </article>
                    <x-admin.modal :nama="'hapus-personel-'.$item->id" size="sm">
                        <x-slot:header>
                            <p class="font-semibold text-status-dibatalkan-teks">Hapus personel?</p>
                        </x-slot:header>
                        <p class="text-sm leading-6 text-muted">Yakin ingin menghapus personel <span class="font-semibold text-ink">{{ $item->nama }}</span>? Data yang dihapus tidak dapat dipulihkan. Personel yang masih terhubung ke pesanan aktif tidak dapat dihapus.</p>
                        <x-slot:footer>
                            <x-admin.tombol variant="secondary" x-on:click="tutup()">Batal</x-admin.tombol>
                            <form method="POST" action="{{ route('admin.guide.hapus', $item) }}">
                                @csrf @method('DELETE')
                                <x-admin.tombol type="submit" variant="danger" icon="delete">Ya, hapus personel</x-admin.tombol>
                            </form>
                        </x-slot:footer>
                    </x-admin.modal>
                @endforeach
                                <div x-show="cariPersonel.trim() !== '' && !Array.from($el.parentElement.querySelectorAll('[data-personel-item]')).some(item => (filterStatus['{{ $peranKategori }}'] === 'semua' || item.dataset.filterStatus === filterStatus['{{ $peranKategori }}']) && cariPersonel.trim().toLocaleLowerCase('id-ID').split(/\s+/).every(kata => item.dataset.cariPersonel.includes(kata)))" x-cloak class="px-4 py-8 text-center text-sm text-muted" role="status" aria-live="polite">Tidak ada {{ strtolower($namaKategori) }} yang cocok. Coba kata kunci lain atau pilih filter status Semua.</div>
                                @foreach (['bertugas' => 'Belum ada personel yang sedang bertugas.', 'menunggu' => 'Tidak ada penugasan yang menunggu konfirmasi.', 'tidak_bertugas' => 'Tidak ada personel yang tidak bertugas.', 'nonaktif' => 'Tidak ada personel nonaktif.'] as $filterNilai => $pesanKosong)
                                    @if ($jumlahStatus[$filterNilai] === 0)
                                        <div x-show="filterStatus['{{ $peranKategori }}'] === '{{ $filterNilai }}' && cariPersonel.trim() === ''" x-cloak class="px-4 py-8 text-center text-sm text-muted">{{ $pesanKosong }}</div>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </x-admin.kartu>

    {{-- Formulir tambah dibuka dari tombol "+ Tambah Personel" di kepala halaman, supaya daftar personel bisa selebar layar. --}}
    <x-admin.modal nama="tambah-personel" size="xl">
        <x-slot:header>
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-admin-sidebar text-emas"><span class="material-symbols-outlined" aria-hidden="true">person_add</span></span>
                <div>
                    <h2 class="text-base font-semibold text-ink">Tambah personel</h2>
                    <p class="mt-0.5 text-xs text-muted">Lengkapi data lalu simpan profil guide (merangkap driver).</p>
                </div>
            </div>
        </x-slot:header>

        <form id="form-tambah-personel" method="POST" action="{{ route('admin.guide.simpan') }}" enctype="multipart/form-data" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" x-data="{ namaBerkas: '', fotoPratinjau: null }">
            @csrf
            <div class="col-span-full">
                <label for="foto-personel-baru" class="mb-1.5 block text-sm font-medium text-ink">Foto profil</label>
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-full border border-surface-border bg-page">
                        <img x-show="fotoPratinjau" x-cloak :src="fotoPratinjau" alt="Pratinjau foto profil" class="h-full w-full object-cover" />
                        <span x-show="!fotoPratinjau" class="material-symbols-outlined text-2xl text-muted" aria-hidden="true">person</span>
                    </div>
                    <div>
                        <input id="foto-personel-baru" type="file" name="foto" accept="image/jpeg,image/png,image/webp" class="peer sr-only" x-on:change="namaBerkas = $event.target.files.length ? $event.target.files[0].name : ''; fotoPratinjau = $event.target.files.length ? URL.createObjectURL($event.target.files[0]) : null" />
                        <label for="foto-personel-baru" class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-surface-border bg-white px-3.5 py-2 text-xs font-semibold text-ink transition hover:bg-page peer-focus-visible:outline-none peer-focus-visible:ring-2 peer-focus-visible:ring-hijau-aksi">
                            <span class="material-symbols-outlined text-base" aria-hidden="true">upload_file</span>Pilih foto
                        </label>
                        <span class="min-w-0 break-all text-xs text-muted" x-text="namaBerkas || 'Belum ada berkas dipilih'">Belum ada berkas dipilih</span>
                    </div>
                </div>
                <p class="mt-1 text-xs text-muted">JPG, PNG, atau WebP, maksimal 5 MB.</p>
                @error('foto') <p class="mt-1 text-xs text-status-dibatalkan-teks">{{ $message }}</p> @enderror
            </div>
            <x-admin.formulir.masukan name="nama" label="Nama lengkap" placeholder="Nama lengkap guide" wajib />
            <x-admin.formulir.masukan name="whatsapp" label="Nomor WhatsApp" type="tel" :value="config('mistborne.kontak_uji.whatsapp')" placeholder="Diawali 08 atau +62, contoh 081234567890" pattern="\+?[0-9][0-9 ().\-]{8,22}" title="Gunakan nomor WhatsApp yang diawali 08 atau +62, contoh 081234567890." />
            <x-admin.formulir.masukan name="email" label="Email kontak" type="email" :value="config('mistborne.kontak_uji.email')" placeholder="Tautan penugasan dikirim ke sini. Contoh: nama@email.com" />
            <x-admin.formulir.masukan name="wilayah_operasi" label="Wilayah operasi" placeholder="Contoh: Bandung" />
            <x-admin.formulir.masukan name="pengalaman_tahun" label="Pengalaman (tahun)" type="number" min="0" max="60" placeholder="Opsional; kosongkan atau 0 untuk fresh graduate" />
            <x-admin.formulir.pilihan name="status_operasional" label="Status ketersediaan" :pilihan="['siap' => 'Siap ditugaskan', 'bertugas' => 'Sedang bertugas']" value="siap" />
            <x-admin.personel.data-mengemudi />
            <x-admin.formulir.area-teks name="keahlian" label="Keahlian / spesialisasi" rows="2" placeholder="Bahasa, spesialisasi jalur, atau sertifikasi" />
            <x-admin.formulir.area-teks name="catatan" label="Catatan tambahan" rows="2" placeholder="Informasi relevan untuk tim operasional" />
            <div class="space-y-3 sm:col-span-2 lg:col-span-1 lg:pt-7">
                <label class="flex items-center gap-2 text-sm text-ink"><input type="hidden" name="aktif" value="0"><input type="checkbox" name="aktif" value="1" checked class="rounded border-surface-border text-hijau-aksi focus:ring-hijau-aksi"> Aktif untuk pilihan penugasan</label>
                <label class="flex items-start gap-2 text-sm text-ink"><input type="checkbox" name="buat_akun_panel" value="1" @checked(old('buat_akun_panel')) class="mt-0.5 rounded border-surface-border text-hijau-aksi focus:ring-hijau-aksi"> <span>Buat akun Panel Guide<span class="block text-xs text-muted">Masuk dengan email kontak di atas dan kata sandi &ldquo;{{ config('mistborne.sandi_bawaan_guide') }}&rdquo;.</span></span></label>
            </div>
        </form>

        <x-slot:footer>
            <x-admin.tombol variant="secondary" x-on:click="tutup()">Batal</x-admin.tombol>
            <x-admin.tombol type="submit" form="form-tambah-personel" icon="save">Simpan personel</x-admin.tombol>
        </x-slot:footer>
    </x-admin.modal>

    {{-- Simpan gagal validasi: buka lagi formulir tambah supaya isian lama dan pesan galat langsung terlihat. --}}
    @if ($errors->any() && ! old('edit_personel_id'))
        <div x-data x-init="$nextTick(() => window.dispatchEvent(new CustomEvent('buka-modal', { detail: { nama: 'tambah-personel' } })))"></div>
    @endif
</x-tata-letak.admin>
