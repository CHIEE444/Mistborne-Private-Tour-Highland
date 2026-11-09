<x-tata-letak.form-pesanan>
@push('head')
<script>
    /**
     * Formulir permintaan perjalanan: menyimpan draf isian di browser supaya tamu tidak perlu mengisi ulang semuanya
     * setelah galat validasi atau muat ulang halaman.
     */
    document.addEventListener('alpine:init', () => {
        Alpine.data('formPermintaan', (opsi) => ({
            jumlah: opsi.jumlah ?? '',
            journeyId: String(opsi.journeyId ?? ''),
            batasPerJourney: opsi.batasPerJourney,
            tanggalPerJourney: opsi.tanggalPerJourney ?? {},
            tanggal: opsi.tanggal ?? '',

            init() {
                const pilihJourney = document.getElementById('perjalanan_id');
                pilihJourney?.addEventListener('change', () => {
                    this.journeyId = pilihJourney.value;
                    // Tanggal journey lama belum tentu tersedia di journey baru.
                    if (! this.opsiTanggal.some((opsi) => opsi.tanggal === this.tanggal && opsi.tersedia)) { this.tanggal = ''; }
                });

                if (opsi.bersihkanDraft) {
                    this.hapusDraft();
                    return;
                }
                if (! opsi.adaInputLama) {
                    this.pulihkanDraft();
                }
                this.$nextTick(() => this.simpanDraft());
            },

            get jumlahTamu() { return parseInt(this.jumlah, 10) || 0; },
            get jumlahKamar() { return Math.max(1, Math.ceil((this.jumlahTamu || 1) / {{ max(1, (int) config("mistborne.biaya_perjalanan.tamu_per_kamar")) }})); },
            get maksTamu() { return this.batasPerJourney[this.journeyId] ?? null; },
            get opsiTanggal() { return this.tanggalPerJourney[this.journeyId] ?? []; },
            get muat() { return this.maksTamu === null || this.jumlahTamu <= this.maksTamu; },

            rapikanJumlah() {
                if (this.jumlah === '' || this.jumlah === null) return;
                const angka = Math.trunc(Number(this.jumlah));
                this.jumlah = Number.isFinite(angka) ? Math.min(50, Math.max(1, angka)) : '';
            },

            tambahContoh(teks) {
                const kolom = this.$refs.permintaanKhusus ?? document.getElementById('permintaan_khusus');
                if (! kolom) return;
                kolom.value = (kolom.value.trim() === '' ? '' : kolom.value.replace(/\s+$/, '') + '\n') + '• ' + teks;
                kolom.dispatchEvent(new Event('input', { bubbles: true }));
                kolom.focus();
            },

            periksaSebelumKirim(event) {
                if (! this.muat) {
                    event.preventDefault();
                    document.getElementById('jumlah_wisatawan')?.focus();
                    return;
                }
                this.simpanDraft();
            },

            bidang() {
                return [...this.$root.querySelectorAll('input[name], select[name], textarea[name]')]
                    .filter((el) => el.name !== '_token' && el.type !== 'hidden' && el.type !== 'submit');
            },

            simpanDraft() {
                try {
                    const draf = {};
                    this.bidang().forEach((el) => { draf[el.name] = el.value; });
                    localStorage.setItem(opsi.kunciDraft, JSON.stringify(draf));
                } catch (e) { /* penyimpanan browser tidak tersedia: formulir tetap bisa dipakai tanpa draf */ }
            },

            pulihkanDraft() {
                let draf = null;
                try { draf = JSON.parse(localStorage.getItem(opsi.kunciDraft) || 'null'); } catch (e) { return; }
                if (! draf) return;

                this.bidang().forEach((el) => {
                    if (! (el.name in draf) || draf[el.name] === '') return;
                    el.value = draf[el.name];
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                    el.dispatchEvent(new Event('change', { bubbles: true }));
                });
                this.jumlah = draf.jumlah_wisatawan ?? this.jumlah;
            },

            hapusDraft() {
                try { localStorage.removeItem(opsi.kunciDraft); } catch (e) { /* abaikan */ }
            },
        }));
    });
</script>
@endpush
    <section>
        <div class="mb-7 max-w-3xl">
            <h1 class="text-2xl font-semibold leading-tight tracking-[-.025em] text-[#173525] sm:text-[2rem]">Formulir permintaan perjalanan</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-[#657168]">Lengkapi informasi perjalanan Anda. Admin akan memeriksa tanggal dan ketersediaan, lalu menghubungi Anda untuk membahas detail sebelum pesanan dibuat.</p>
        </div>

        @if (session('terkirim'))
            <div x-data="{ terbuka: true }" x-effect="document.body.classList.toggle('overflow-hidden', terbuka)">
                <div x-show="terbuka" x-cloak x-transition.opacity class="fixed inset-0 z-[100] flex items-center justify-center bg-[#14231a]/55 p-4 backdrop-blur-[2px]" role="presentation" x-on:click.self="terbuka = false" x-on:keydown.escape.window="terbuka = false">
                    <section x-show="terbuka" x-transition role="dialog" aria-modal="true" aria-labelledby="judul-permintaan-terkirim" aria-describedby="deskripsi-permintaan-terkirim" class="w-full max-w-md rounded-xl border border-[#dce7dd] bg-white p-6 shadow-xl sm:p-8">
                        <div class="flex h-11 w-11 items-center justify-center rounded-full bg-[#e8f0e9] text-xl font-semibold text-[#315f48]" aria-hidden="true">✓</div>
                        <h2 id="judul-permintaan-terkirim" class="mt-5 text-xl font-semibold tracking-[-.02em] text-[#173525]">Permintaan Anda sudah diterima</h2>
                        <p id="deskripsi-permintaan-terkirim" class="mt-3 text-sm leading-6 text-[#657168]">Admin MISTBORNE akan memeriksa tanggal dan kapasitas, lalu menghubungi Anda untuk membahas detailnya. Pesanan baru dibuat setelah ketersediaan dan rencana perjalanan disepakati.</p>
                        @if ($tautanKonfirmasiWa)
                            <a href="{{ $tautanKonfirmasiWa }}" target="_blank" rel="noopener noreferrer" class="mt-6 inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-lg bg-[#1D7A56] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#175F44] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#1D7A56] focus-visible:ring-offset-2">Konfirmasi ke admin via WhatsApp</a>
                            <p class="mt-2 text-xs leading-5 text-[#78847b]">Pesan sudah terisi ringkasan data terbaru dari formulir ini, jadi admin tidak perlu menanyakan ulang.</p>
                        @endif
                        <button type="button" x-on:click="terbuka = false" class="mt-3 inline-flex min-h-11 w-full items-center justify-center rounded-lg border border-[#dce7dd] px-5 py-3 text-sm font-semibold text-[#315f48] transition hover:bg-[#f3f7f3] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#315f48] focus-visible:ring-offset-2">Mengerti</button>
                    </section>
                </div>
            </div>
        @endif

        @if (in_array($permintaan->status, ['disetujui', 'tidak_jadi'], true))
            <div class="rounded-2xl border border-[#e3e8e1] bg-white p-6 shadow-sm sm:p-8" role="status">
                <h2 class="text-lg font-semibold text-ink">{{ $permintaan->status === 'disetujui' ? 'Permintaan sudah menjadi pesanan' : 'Permintaan ini sudah ditutup' }}</h2>
                <p class="mt-2 text-sm leading-6 text-muted">{{ $permintaan->status === 'disetujui' ? 'Admin sudah membuat pesanan dari data ini. Silakan lanjutkan komunikasi dengan admin melalui WhatsApp.' : 'Untuk membuat permintaan baru, silakan hubungi admin MISTBORNE.' }}</p>
            </div>
        @else
            @if (in_array($permintaan->status, ['menunggu_tinjauan', 'ditindaklanjuti'], true))
                <div class="mt-5 border-l-2 border-[#8c9c8e] py-1 pl-4 text-sm leading-6 text-[#657168]">
                    Data sebelumnya sudah dikirim. Jika admin meminta koreksi, Anda dapat memperbarui formulir ini menggunakan tautan yang sama.
                </div>
            @endif

            <p class="mt-5 text-xs leading-5 text-[#78847b]">Tautan formulir ini berlaku sampai <strong class="font-semibold text-[#21372a]">{{ $permintaan->token_berlaku_sampai->translatedFormat('d F Y, H:i') }} WIB</strong>. Bila setelah itu masih ada perubahan, minta tautan baru ke admin.</p>

            @php
                $contohPermintaanKhusus = "Ceritakan kebutuhan khusus rombongan Anda agar tim bisa menyiapkannya, misalnya:\n• Pantangan makanan atau alergi (seafood, kacang, vegetarian, halal)\n• Ada lansia, anak kecil, atau peserta yang mudah mabuk perjalanan\n• Perayaan: ulang tahun, honeymoon, atau kejutan untuk pasangan\n• Butuh car seat anak, selimut ekstra, atau bagasi besar\n• Ingin singgah di spot foto tertentu";
            @endphp
            <form method="POST" action="{{ route('form-pesanan.simpan', $permintaan->token) }}" class="fpub mt-5 overflow-hidden" novalidate
                x-data="formPermintaan(@js([
                    'kunciDraft' => 'mistborne:form-pesanan:'.$permintaan->token,
                    'journeyId' => old('perjalanan_id', $permintaan->perjalanan_id),
                    'batasPerJourney' => $batasPerJourney,
                    'tanggalPerJourney' => $tanggalPerJourney,
                    'tanggal' => old('tanggal_trip', $permintaan->tanggal_trip?->toDateString()),
                    'jumlah' => old('jumlah_wisatawan', $permintaan->jumlah_wisatawan),
                    'adaInputLama' => count(old()) > 0,
                    'bersihkanDraft' => (bool) session('terkirim'),
                ]))"
                x-on:submit="periksaSebelumKirim($event)"
                x-on:input="simpanDraft()"
                x-on:change="simpanDraft()">
                @csrf

                <div class="border-b border-[#e8ece6] px-5 py-5 sm:px-8 sm:py-6">
                    <h2 class="text-base font-semibold text-[#21372a]">Informasi pemesanan</h2>
                    <p class="mt-1 text-sm leading-6 text-[#78847b]">Kolom bertanda <span class="text-[#a04d3e]">*</span> wajib diisi. Perkiraan informasi sudah dapat disesuaikan saat admin menghubungi Anda.</p>
                </div>
                <div class="space-y-6 p-5 sm:p-8">

                @if ($errors->any())
                    <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
                        <p class="font-semibold">Mohon periksa kembali data berikut:</p>
                        <ul class="mt-2 list-inside list-disc space-y-1">
                            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <div class="grid gap-5 sm:grid-cols-2">
                    <div><x-admin.formulir.masukan name="nama_lengkap" label="Nama lengkap" placeholder="Contoh: Rina Putri" petunjuk="Masukkan nama orang yang menjadi kontak utama untuk pemesanan ini." :value="$permintaan->nama_lengkap" wajib /></div>
                    <div><x-admin.formulir.masukan name="whatsapp" label="Nomor WhatsApp" type="tel" inputmode="tel" pattern="\+?[0-9][0-9 ().\-]{8,22}" maxlength="24" placeholder="081234567890" petunjuk="Gunakan nomor aktif yang bisa dihubungi admin. Tulis diawali 08 tanpa spasi atau tanda baca." :value="$permintaan->whatsapp" wajib /></div>

                    <div>
                        <x-admin.formulir.kolom label="Journey / paket" for="perjalanan_id" name="perjalanan_id" petunjuk="Pilih perjalanan yang ingin dipesan. Admin akan menghubungi Anda jika perlu memastikan pilihan paket." wajib>
                            <select id="perjalanan_id" name="perjalanan_id" required class="w-full rounded-lg border border-surface-border bg-white px-3.5 py-2.5 text-sm text-ink focus:border-hijau-aksi focus:outline-none focus:ring-1 focus:ring-hijau-aksi">
                                <option value="">Pilih journey</option>
                                @foreach ($journey as $opsi)
                                    <option value="{{ $opsi->id }}" data-durasi="{{ (int) $opsi->durasi_hari }}" @selected((string) old('perjalanan_id', $permintaan->perjalanan_id) === (string) $opsi->id)>{{ $opsi->nama }}</option>
                                @endforeach
                            </select>
                        </x-admin.formulir.kolom>
                    </div>

                    <div>
                        {{-- Hanya Tanggal Tersedia journey yang dipilih; tanggal penuh tampil tetapi tidak bisa dipilih. --}}
                        <x-admin.formulir.kolom label="Tanggal perjalanan" for="tanggal_trip" name="tanggal_trip" wajib>
                            <select id="tanggal_trip" name="tanggal_trip" required x-model="tanggal" class="w-full rounded-lg border border-surface-border bg-white px-3.5 py-2.5 text-sm text-ink focus:border-hijau-aksi focus:outline-none focus:ring-1 focus:ring-hijau-aksi">
                                <option value="" x-text="! journeyId ? 'Pilih journey dulu' : (opsiTanggal.some((opsi) => opsi.tersedia) ? 'Pilih tanggal yang tersedia' : 'Belum ada tanggal tersedia, tanyakan ke admin')"></option>
                                <template x-for="opsi in opsiTanggal" :key="opsi.tanggal">
                                    <option :value="opsi.tanggal" :disabled="! opsi.tersedia" :selected="opsi.tanggal === tanggal" x-text="opsi.teks"></option>
                                </template>
                            </select>
                        </x-admin.formulir.kolom>
                    </div>
                    <div class="rounded-xl border border-surface-border bg-[#F3F6F1] px-4 py-3.5 text-sm text-ink sm:col-span-2" aria-live="polite" x-data="{
                        durasi: 0,
                        dipilih: false,
                        tanggal: '',
                        init() {
                            const pilih = document.getElementById('perjalanan_id');
                            const tanggal = document.getElementById('tanggal_trip');
                            const baca = () => {
                                this.dipilih = pilih.value !== '';
                                this.durasi = parseInt(pilih.selectedOptions[0]?.dataset.durasi || '0', 10);
                                this.tanggal = tanggal.value;
                            };
                            pilih.addEventListener('change', baca);
                            tanggal.addEventListener('input', baca);
                            tanggal.addEventListener('change', baca);
                            baca();
                        },
                        format(tanggal) { return tanggal.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }); },
                        get awal() { return this.tanggal ? new Date(this.tanggal + 'T00:00:00') : null; },
                        get akhir() { if (! this.awal || isNaN(this.awal) || ! this.durasi) return null; const hasil = new Date(this.awal); hasil.setDate(hasil.getDate() + this.durasi - 1); return hasil; },
                        get labelDurasi() { return this.durasi > 1 ? this.durasi + ' hari ' + (this.durasi - 1) + ' malam' : '1 hari'; },
                    }">
                        <p><span class="text-muted">Durasi perjalanan:</span> <strong class="font-semibold" x-text="durasi ? labelDurasi : (dipilih ? 'Fleksibel, mengikuti rencana yang disepakati' : 'Pilih journey untuk melihat durasi')"></strong></p>
                        <div class="mt-1.5 space-y-0.5" x-show="akhir" x-cloak>
                            <p><span class="text-muted">Berangkat:</span> <strong class="font-semibold" x-text="awal ? format(awal) : ''"></strong></p>
                            <p><span class="text-muted">Selesai:</span> <strong class="font-semibold" x-text="akhir ? format(akhir) : ''"></strong></p>
                        </div>
                    </div>
                    <div>
                        <x-admin.formulir.masukan name="jumlah_wisatawan" label="Jumlah peserta" type="number" min="1" max="50" step="1" inputmode="numeric" x-model="jumlah" x-on:input="rapikanJumlah()" x-on:blur="rapikanJumlah()" :value="$permintaan->jumlah_wisatawan" placeholder="Contoh: 4" petunjuk="Isi jumlah seluruh orang yang ikut, termasuk Anda. Angka bulat 1–50; pemandu tidak dihitung." wajib />
                    </div>
                    <div class="rounded-xl border border-hijau-aksi/30 bg-[#EAF3EE] px-4 py-3.5 sm:col-span-2">
                        <p class="flex items-center gap-2 text-sm font-semibold text-[#1D5A3F]"><span class="material-symbols-outlined text-lg" aria-hidden="true">verified</span>All-inclusive: tamu cukup membayar sekali</p>
                        <p class="mt-1 text-sm leading-relaxed text-[#1D5A3F]">Penginapan bintang 4 (4 orang per kamar) dan tiket masuk objek wisata sudah termasuk dalam harga. Tidak ada biaya mendadak di lokasi.</p>
                    </div>
                    <div class="sm:col-span-2" x-show="daftar.length" x-cloak x-data="{
                        hotel: @js($hotelPerJourney),
                        id: '',
                        init() {
                            const pilih = document.getElementById('perjalanan_id');
                            const baca = () => { this.id = pilih?.value ?? ''; };
                            pilih?.addEventListener('change', baca);
                            baca();
                        },
                        get daftar() { return this.hotel[this.id] ?? []; },
                    }">
                        <p class="text-sm font-semibold text-ink">Akomodasi selama perjalanan</p>
                        <p class="mt-1 text-sm font-medium text-[#1D5A3F]">Jumlah kamar menyesuaikan jumlah tamu (4 orang per kamar). <span x-text="'Untuk ' + jumlahTamu + ' tamu, Anda mendapat ' + jumlahKamar + ' kamar.'" aria-live="polite"></span></p>
                        <div class="mt-3 grid gap-3">
                            <template x-for="item in daftar" :key="item.nama">
                                <article class="overflow-hidden rounded-2xl border border-surface-border bg-white" x-data="{ aktif: 0 }">
                                    <template x-if="item.foto || item.galeri.length">
                                        <div class="grid gap-2 p-2" :class="(item.foto ? 1 : 0) + item.galeri.length > 1 ? 'sm:grid-cols-[minmax(0,1fr)_6rem]' : ''">
                                            <div class="relative overflow-hidden rounded-xl">
                                                <img :src="(item.foto ? [{ url: item.foto, label: null }, ...item.galeri] : item.galeri)[aktif].url" :alt="item.nama" class="aspect-[16/10] w-full object-cover" loading="lazy">
                                                <span x-show="(item.foto ? [{ url: item.foto, label: null }, ...item.galeri] : item.galeri)[aktif].label" x-text="(item.foto ? [{ url: item.foto, label: null }, ...item.galeri] : item.galeri)[aktif].label" class="absolute bottom-3 start-3 rounded-full bg-[#12301F]/80 px-3 py-1 text-xs font-medium text-white"></span>
                                            </div>
                                            <div class="relative" x-show="(item.foto ? 1 : 0) + item.galeri.length > 1">
                                                <ul class="flex gap-2 overflow-x-auto sm:absolute sm:inset-0 sm:flex-col sm:overflow-y-auto sm:overflow-x-hidden">
                                                    <template x-for="(foto, indeks) in (item.foto ? [{ url: item.foto, label: null }, ...item.galeri] : item.galeri)" :key="indeks">
                                                        <li class="shrink-0 sm:w-full"><button type="button" x-on:click="aktif = indeks" :aria-label="foto.label ?? item.nama" :class="aktif === indeks ? 'ring-2 ring-hijau-aksi ring-offset-1' : 'opacity-80 hover:opacity-100'" class="block w-20 overflow-hidden rounded-xl transition sm:w-full"><img :src="foto.url" :alt="foto.label ?? item.nama" class="aspect-[4/3] w-full object-cover" loading="lazy"></button></li>
                                                    </template>
                                                </ul>
                                            </div>
                                        </div>
                                    </template>
                                    <template x-if="! item.foto && ! item.galeri.length"><div class="flex aspect-[16/10] w-full flex-col items-center justify-center gap-1 bg-[#EAF3EE] text-hijau-aksi"><span class="material-symbols-outlined text-4xl" aria-hidden="true">hotel</span><span class="text-xs">Foto hotel menyusul</span></div></template>
                                    <div class="p-3.5">
                                        <p class="text-[#C9A24B]" x-text="'★'.repeat(item.bintang)" :aria-label="'Hotel bintang ' + item.bintang"></p>
                                        <h3 class="mt-0.5 text-sm font-semibold text-ink"><a :href="item.url" target="_blank" rel="noopener" class="hover:text-hijau-aksi hover:underline" x-text="item.nama"></a></h3>
                                        <p class="mt-0.5 flex items-center gap-1 text-xs text-muted"><span class="material-symbols-outlined text-sm" aria-hidden="true">location_on</span><span x-text="item.area"></span></p>
                                        <p class="mt-2 flex items-start gap-1.5 rounded-lg bg-page px-3 py-2 text-xs text-ink" x-show="item.jarak"><span class="material-symbols-outlined text-sm text-hijau-aksi" aria-hidden="true">schedule</span><span><span class="font-semibold">Estimasi perjalanan destinasi–hotel:</span> <span x-text="item.jarak"></span></span></p>
                                        <p class="mt-2 text-sm leading-relaxed text-ink" x-show="item.deskripsi" x-text="item.deskripsi"></p>
                                        <template x-if="item.fasilitas.length">
                                            <div>
                                                <p class="mt-2.5 text-[11px] font-semibold uppercase tracking-wide text-muted">Fasilitas yang didapat</p>
                                                <ul class="mt-1 flex flex-wrap gap-1.5"><template x-for="f in item.fasilitas" :key="f"><li class="rounded-full bg-page px-2.5 py-1 text-xs text-ink" x-text="f"></li></template></ul>
                                            </div>
                                        </template>
                                        <div class="mt-4 flex flex-wrap gap-2 border-t border-surface-border pt-3.5">
                                            <a :href="item.url" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-2 rounded-full bg-hijau-aksi px-4 py-2 text-sm font-semibold text-white transition-colors duration-200 hover:bg-[#12301F] focus:outline-none focus-visible:ring-2 focus-visible:ring-hijau-aksi focus-visible:ring-offset-2">Lihat detail hotel<span class="material-symbols-outlined text-lg" aria-hidden="true">arrow_forward</span></a>
                                            <a :href="item.peta" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-2 rounded-full border border-ink/25 bg-white px-4 py-2 text-sm font-semibold text-ink transition-colors duration-200 hover:border-ink hover:bg-ink hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-hijau-aksi focus-visible:ring-offset-2"><span class="material-symbols-outlined text-lg" aria-hidden="true">map</span>Lihat lokasi di Google Maps</a>
                                        </div>
                                    </div>
                                </article>
                            </template>
                        </div>
                    </div>
                    <div class="sm:col-span-2" x-show="! muat" x-cloak>
                        <p x-cloak x-show="! muat" class="mt-1.5 flex items-start gap-1 text-xs text-status-dibatalkan-teks" role="alert">
                            <span class="material-symbols-outlined text-sm" aria-hidden="true">error</span>
                            <span x-text="'Journey ini maksimal ' + maksTamu + ' tamu, sedangkan jumlah peserta ' + jumlahTamu + ' orang. Kurangi jumlah peserta.'"></span>
                        </p>
                    </div>
                    <div><x-admin.formulir.masukan name="lokasi_pickup" label="Lokasi penjemputan" placeholder="Nama hotel, alamat, atau patokan" petunjuk="Sebutkan alamat atau titik jemput yang mudah ditemukan, misalnya nama hotel dan area/kota." :value="$permintaan->lokasi_pickup" wajib /></div>
                    <div><x-admin.formulir.masukan name="waktu_pickup" label="Perkiraan jam penjemputan (opsional)" type="time" petunjuk="Pilih perkiraan waktu dalam WIB. Admin akan memastikan jam final saat follow-up." :value="\App\Bantuan\JamPenjemputan::untukInput($permintaan->waktu_pickup)" /></div>
                    <div><x-admin.formulir.masukan name="paket_opsional" label="Catatan tambahan (opsional)" placeholder="Contoh: ingin menambahkan kebutuhan tertentu" petunjuk="Tuliskan tambahan atau preferensi perjalanan yang ingin dibicarakan dengan admin." :value="$permintaan->paket_opsional" /></div>
                    <div class="sm:col-span-2">
                        <x-admin.formulir.area-teks name="permintaan_khusus" label="Permintaan khusus (opsional)" rows="6" maxlength="1000" x-ref="permintaanKhusus" :placeholder="$contohPermintaanKhusus" :value="old('permintaan_khusus', $permintaan->permintaan_khusus)" />
                        <div class="mt-2.5">
                            <p class="text-xs font-medium text-[#66736a]">Contoh yang sering diminta tamu. Klik untuk menambahkan, lalu lengkapi detailnya:</p>
                            <div class="mt-1.5 flex flex-wrap gap-1.5">
                                @foreach (['Pantangan makanan / alergi: ', 'Ada lansia di rombongan: ', 'Ada anak kecil (usia): ', 'Butuh car seat anak', 'Mudah mabuk perjalanan: mohon berkendara pelan', 'Merayakan ulang tahun / honeymoon', 'Bawa bagasi besar: ', 'Ingin singgah di spot foto: '] as $contoh)
                                    <button type="button" x-on:click="tambahContoh(@js($contoh))" class="rounded-full border border-[#dce7dd] bg-white px-3 py-1.5 text-xs font-medium text-[#315f48] transition hover:bg-[#f3f7f3] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#315f48]">+ {{ rtrim($contoh, ': ') }}</button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-l-2 border-[#a1ad9f] pl-4 text-sm leading-6 text-[#66736a]">Mengirim formulir belum mengunci tanggal atau membuat pesanan. Admin akan menghubungi Anda untuk memastikan ketersediaan dan kesepakatan terlebih dahulu.</div>
                <div class="flex flex-col-reverse gap-3 border-t border-[#e8ece6] pt-5 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs leading-5 text-[#7a857d]">Pastikan nomor WhatsApp Anda aktif.</p>
                    <button type="submit" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-lg bg-[#173525] px-6 py-3 text-sm font-semibold text-white transition hover:bg-[#244a35] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#315f48] focus-visible:ring-offset-2">Kirim permintaan</button>
                </div>
                </div>
            </form>
        @endif
    </section>
</x-tata-letak.form-pesanan>
