{{-- RANCANG TRIP KUSTOM: formulir lima bagian bernomor dengan chip destinasi/minat dan panel "cara kerjanya". --}}
@php
    $fotoSampul = '/images/sikunir-sindoro-pagi.webp';
    $pilihanDestinasi = ['Dieng', 'Bromo', 'Kawah Ijen', 'Ciwidey', 'Papandayan', 'Lembang', 'Borobudur', 'Green Canyon', 'Telaga Sarangan'];
    $pilihanMinat = ['alam' => 'Alam', 'budaya' => 'Budaya', 'kuliner' => 'Kuliner', 'fotografi' => 'Fotografi', 'keluarga' => 'Ramah keluarga', 'petualangan' => 'Petualangan', 'sejarah' => 'Sejarah', 'belanja' => 'Belanja'];
    $pilihanTempo = [
        'santai' => ['Santai', 'Banyak waktu menikmati tiap tempat'],
        'normal' => ['Seimbang', 'Cukup waktu, tetap leluasa'],
        'aktif' => ['Padat', 'Ingin mengunjungi banyak tempat'],
    ];
    $langkah = [
        ['Konsultasi rute', 'Kami bahas destinasi, durasi, dan ritme yang Anda inginkan lewat WhatsApp.'],
        ['Itinerary & penawaran', 'Tim kami menyusun itinerary privat beserta penawaran harganya untuk Anda setujui.'],
        ['Berangkat', 'Pemandu lokal yang merangkap sopir menjemput Anda, lalu perjalanan berjalan dengan ritme Anda.'],
    ];
@endphp
<x-tata-letak.aplikasi title="Rancang Trip Kustom" :footer-rapat="true">
    {{-- SAMPUL --}}
    <section class="fp-sampul" aria-labelledby="rancang-kustom-judul">
        <img src="{{ $fotoSampul }}" alt="" class="fp-sampul__img" width="1600" height="1067" decoding="async" fetchpriority="high" />
        <div class="relative mx-auto max-w-[1240px] px-4 pb-44 pt-28 sm:px-8 sm:pt-32 lg:pb-52">
            <x-bersama.tombol-beranda class="mb-8" />
            <p class="text-[15px] font-medium text-white/80">Private Trip Kustom</p>
            <h1 id="rancang-kustom-judul" class="mt-2 max-w-[16ch] text-balance text-[36px] font-semibold leading-[1.02] tracking-[-0.02em] sm:text-[52px] lg:text-[60px]">Rancang Trip Kustom Anda</h1>
            <p class="mt-5 max-w-[56ch] text-[16px] leading-relaxed text-white/85 sm:text-[17px]">Beri kami gambaran perjalanan yang Anda inginkan. Tim kami akan membantu merangkai detail trip dan penawarannya bersama Anda.</p>
            <ul class="mt-6 flex flex-wrap gap-2 text-[13.5px] font-medium text-white">
                <li class="fp-kaca rounded-full px-4 py-2">Rute sesuai keinginan</li>
                <li class="fp-kaca rounded-full px-4 py-2">Jadwal Anda tentukan</li>
                <li class="fp-kaca rounded-full px-4 py-2">Rombongan privat</li>
            </ul>
        </div>
    </section>

    <div class="relative mx-auto -mt-36 max-w-[1240px] px-4 sm:px-8 lg:-mt-40">
        @if (session('sukses'))
            {{-- Setelah form disimpan, tamu melihat DRAFT chat dulu: draf → menunggu (WhatsApp sudah dibuka) → terkirim
                 (tamu menyatakan sudah menekan kirim). Keterangan "sudah dikirim" baru muncul di tahap terakhir; WhatsApp
                 tidak dibuka otomatis karena sering diblokir browser dan membingungkan tamu. --}}
            <div x-data="{
                buka: true,
                tahap: 'draf',
                disalin: false,
                salin(teks) {
                    navigator.clipboard?.writeText(teks).then(() => { this.disalin = true; setTimeout(() => this.disalin = false, 2500); });
                },
            }">
                <div
                    x-show="buka"
                    x-cloak
                    x-on:keydown.escape.window="buka = false"
                    class="fixed inset-0 z-[80] flex items-center justify-center p-4"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="modal-sukses-judul"
                    aria-describedby="modal-sukses-isi"
                >
                    <div class="absolute inset-0 bg-[#0C1A12]/60 backdrop-blur-sm" x-on:click="buka = false" aria-hidden="true"></div>
                    <div
                        class="relative max-h-[calc(100dvh-2rem)] w-full max-w-[480px] overflow-y-auto rounded-[28px] bg-white p-8 text-center shadow-[0_40px_90px_-30px_rgba(0,0,0,.6)]"
                        x-transition:enter="transition duration-300 ease-out"
                        x-transition:enter-start="translate-y-3 scale-95 opacity-0"
                        x-transition:enter-end="translate-y-0 scale-100 opacity-100"
                    >
                        {{-- Tahap 1: draft chat --}}
                        <div x-show="tahap === 'draf'">
                            <span class="mx-auto flex size-16 items-center justify-center rounded-full bg-[#E6F2EA] text-[#1D7A56]">
                                <x-bersama.ikon-wa class="size-8" />
                            </span>
                            <h2 id="modal-sukses-judul" class="mt-5 text-2xl font-semibold tracking-tight text-[#12301F]">Satu langkah lagi: kirim draft ke admin</h2>
                            <p id="modal-sukses-isi" class="mt-3 text-[15px] leading-relaxed text-[#5B6A61]">
                                Permintaan Anda <strong class="font-semibold text-[#12301F]">belum sampai ke admin</strong>. Tekan <strong class="font-semibold text-[#12301F]">Kirim lewat WhatsApp</strong>, lalu tekan tombol kirim di WhatsApp.
                            </p>

                            @if (session('pesanWa'))
                                <div class="mt-5 rounded-2xl bg-[#E9F3EC] p-3 text-start">
                                    <p class="mb-2 px-1 text-xs font-semibold text-[#1D5A3F]">Draft chat untuk admin MISTBORNE</p>
                                    <p class="max-h-56 overflow-y-auto whitespace-pre-wrap wrap-anywhere rounded-xl rounded-tr-sm bg-white px-4 py-3 text-[13px] leading-relaxed text-[#12301F] shadow-sm">{{ session('pesanWa') }}</p>
                                </div>
                            @endif

                            <div class="mt-6 flex flex-col gap-3">
                                @if (session('tautanWa'))
                                    <a href="{{ session('tautanWa') }}" target="_blank" rel="noopener noreferrer" x-on:click="tahap = 'menunggu'" class="inline-flex h-12 items-center justify-center gap-2 rounded-full bg-[#1D7A56] text-sm font-semibold text-white transition-colors hover:bg-[#12301F]">
                                        <x-bersama.ikon-wa class="size-[18px]" />
                                        Kirim lewat WhatsApp
                                    </a>
                                @endif
                                @if (session('pesanWa'))
                                    <button type="button" x-on:click="salin(@js(session('pesanWa')))" class="inline-flex h-12 items-center justify-center rounded-full bg-[#F1F4EF] text-sm font-semibold text-[#12301F] transition-colors hover:bg-[#E6ECE5]">
                                        <span x-text="disalin ? 'Draft disalin' : 'Salin draft chat'">Salin draft chat</span>
                                    </button>
                                @endif
                                <button type="button" x-on:click="buka = false" class="text-sm font-medium text-[#5B6A61] underline hover:text-[#12301F]">Nanti saja</button>
                            </div>
                        </div>

                        {{-- Tahap 2: WhatsApp sudah dibuka, tamu memastikan pesannya terkirim --}}
                        <div x-show="tahap === 'menunggu'" x-cloak>
                            <span class="mx-auto flex size-16 items-center justify-center rounded-full bg-[#E6F2EA] text-[#1D7A56]">
                                <x-bersama.ikon-wa class="size-8" />
                            </span>
                            <h2 class="mt-5 text-2xl font-semibold tracking-tight text-[#12301F]">Sudah menekan kirim di WhatsApp?</h2>
                            <p class="mt-3 text-[15px] leading-relaxed text-[#5B6A61]">Pastikan draft chat sudah terkirim (muncul tanda centang) supaya admin MISTBORNE menerima permintaan Anda.</p>
                            <div class="mt-6 flex flex-col gap-3">
                                <button type="button" x-on:click="tahap = 'terkirim'" class="inline-flex h-12 items-center justify-center rounded-full bg-[#1D7A56] text-sm font-semibold text-white transition-colors hover:bg-[#12301F]">Ya, sudah terkirim</button>
                                @if (session('tautanWa'))
                                    <a href="{{ session('tautanWa') }}" target="_blank" rel="noopener noreferrer" class="inline-flex h-12 items-center justify-center gap-2 rounded-full bg-[#F1F4EF] text-sm font-semibold text-[#12301F] transition-colors hover:bg-[#E6ECE5]">
                                        <x-bersama.ikon-wa class="size-[18px]" />
                                        Belum, buka WhatsApp lagi
                                    </a>
                                @endif
                            </div>
                        </div>

                        {{-- Tahap 3: tamu sudah mengirim draft --}}
                        <div x-show="tahap === 'terkirim'" x-cloak>
                            <span class="mx-auto flex size-16 items-center justify-center rounded-full bg-[#1D7A56] text-white">
                                <span class="material-symbols-outlined text-[34px]!" aria-hidden="true">check</span>
                            </span>
                            <h2 class="mt-5 text-2xl font-semibold tracking-tight text-[#12301F]">Data pesanan Anda sudah dikirim</h2>
                            <p class="mt-3 text-[15px] leading-relaxed text-[#5B6A61]">Admin MISTBORNE sudah menerima draft chat Anda dan akan membalas lewat WhatsApp untuk membahas rute serta penawarannya. Terima kasih.</p>
                            <div class="mt-6 flex flex-col gap-3">
                                <button type="button" x-on:click="buka = false" class="inline-flex h-12 items-center justify-center rounded-full bg-[#12301F] text-sm font-semibold text-white transition-colors hover:bg-[#1D7A56]">Mengerti</button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Bar keterangan di atas formulir mengikuti tahap modal --}}
                <div class="fp-kartu mb-6 flex flex-col items-start gap-3 px-6 py-5 text-sm text-[#12301F] sm:flex-row sm:items-center sm:justify-between">
                    <div x-show="tahap !== 'terkirim'">
                        <p class="font-semibold">Permintaan Anda tersimpan. Langkah terakhir: kirim draft chat ke admin.</p>
                        <p class="mt-0.5 text-[#5B6A61]">Admin MISTBORNE baru menerima permintaan Anda setelah draft chat dikirim lewat WhatsApp.</p>
                    </div>
                    <div x-show="tahap === 'terkirim'" x-cloak>
                        <p class="font-semibold">Data pesanan Anda sudah dikirim ke admin MISTBORNE.</p>
                        <p class="mt-0.5 text-[#5B6A61]">Tim kami biasanya membalas dalam beberapa menit pada jam kerja.</p>
                    </div>

                    @if (session('tautanWa'))
                        <a x-show="tahap !== 'terkirim'" href="{{ session('tautanWa') }}" target="_blank" rel="noopener noreferrer" x-on:click="tahap = 'menunggu'; buka = true" class="inline-flex shrink-0 items-center gap-2 rounded-full bg-[#1D7A56] px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#12301F]">
                            <x-bersama.ikon-wa class="size-4" />
                            Kirim draft WhatsApp
                        </a>
                    @endif
                </div>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px] lg:items-start lg:gap-8">
            <form
                method="POST"
                action="{{ route('rancang-trip-kustom.simpan') }}"
                class="fp-kartu"
                novalidate
                x-data="{
                    tanggalFleksibel: {{ old('tanggal_fleksibel') ? 'true' : 'false' }},
                    tanggal: @js(old('tanggal_fleksibel') ? '' : old('tanggal_diinginkan', '')),
                    penginapan: @js(old('status_penginapan', '')),
                    jumlah: {{ max(1, min(50, (int) old('jumlah_orang', 2))) }},
                    durasi: {{ max(1, min(30, (int) old('durasi_hari', 3))) }},
                    destinasi: @js(old('destinasi_diinginkan', '')),
                    bahasa: @js(str_replace('_', '-', app()->getLocale())),
                    galat: {},
                    get daftarDestinasi() { return this.destinasi.split(',').map((d) => d.trim()).filter(Boolean); },
                    adaDestinasi(nama) { return this.daftarDestinasi.some((d) => d.toLowerCase() === nama.toLowerCase()); },
                    pilihDestinasi(nama) {
                        const daftar = this.adaDestinasi(nama) ? this.daftarDestinasi.filter((d) => d.toLowerCase() !== nama.toLowerCase()) : [...this.daftarDestinasi, nama];
                        this.destinasi = daftar.join(', ');
                        delete this.galat.destinasi_diinginkan;
                    },
                    get teksTanggal() { const d = this.tanggal ? new Date(this.tanggal + 'T00:00:00') : null; return d && ! isNaN(d) ? d.toLocaleDateString(this.bahasa, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) : ''; },
                    // Pesan galat tampil langsung di bawah kolom yang bermasalah (server tetap pemeriksa akhir).
                    periksa(event) {
                        const f = new FormData(event.target);
                        const nilai = (k) => String(f.get(k) ?? '').trim();
                        const bulat = (k, min, max) => /^\d+$/.test(nilai(k)) && +nilai(k) >= min && +nilai(k) <= max;
                        const g = {};
                        const hariIni = new Date(); hariIni.setHours(0, 0, 0, 0);
                        // Sama dengan pesanan resmi: paling cepat H-7, paling lambat satu tahun sesudahnya.
                        const minimal = new Date(hariIni); minimal.setDate(minimal.getDate() + 7);
                        const batas = new Date(minimal); batas.setFullYear(batas.getFullYear() + 1);

                        if (nilai('nama') === '') g.nama = 'Nama lengkap wajib diisi.';
                        else if (nilai('nama').length < 3) g.nama = 'Nama lengkap minimal 3 karakter.';
                        else if (/[<>]/.test(nilai('nama'))) g.nama = 'Nama tidak boleh memuat karakter < atau >.';

                        const wa = nilai('whatsapp');
                        const waLokal = /[^\d\s\-.()+]/.test(wa) ? wa : (wa.replace(/\D+/g, '').replace(/^62/, '0'));
                        if (wa === '') g.whatsapp = 'Nomor WhatsApp wajib diisi.';
                        else if (!/^08\d{7,12}$/.test(waLokal)) g.whatsapp = 'Masukkan nomor WhatsApp dengan angka saja, diawali 08 atau +62, contoh 081234567890.';

                        if (nilai('jumlah_orang') === '') g.jumlah_orang = 'Jumlah orang wajib diisi.';
                        else if (!bulat('jumlah_orang', 1, 50)) g.jumlah_orang = 'Jumlah orang harus angka bulat 1 sampai 50.';

                        if (nilai('destinasi_diinginkan') === '') g.destinasi_diinginkan = 'Destinasi yang diinginkan wajib diisi.';
                        else if (nilai('destinasi_diinginkan').length < 3) g.destinasi_diinginkan = 'Destinasi minimal 3 karakter.';

                        if (!this.tanggalFleksibel) {
                            const tgl = nilai('tanggal_diinginkan');
                            const d = tgl === '' ? null : new Date(tgl + 'T00:00:00');
                            if (tgl === '') g.tanggal_diinginkan = 'Pilih tanggal perjalanan atau tandai tanggal masih fleksibel.';
                            else if (!d || isNaN(d)) g.tanggal_diinginkan = 'Tanggal perjalanan tidak valid.';
                            else if (d < minimal) g.tanggal_diinginkan = 'Tanggal perjalanan minimal 7 hari dari hari ini (H-7), sama seperti pesanan resmi, agar guide, mobil, dan pembayaran sempat disiapkan.';
                            else if (d > batas) g.tanggal_diinginkan = 'Tanggal perjalanan hanya dapat dipilih sampai satu tahun ke depan.';
                        }

                        if (nilai('durasi_hari') === '') g.durasi_hari = 'Durasi perjalanan wajib diisi.';
                        else if (!bulat('durasi_hari', 1, 30)) g.durasi_hari = 'Durasi harus angka bulat 1 sampai 30 hari.';

                        if (nilai('lokasi_penjemputan') === '') g.lokasi_penjemputan = 'Area penjemputan wajib diisi.';
                        else if (nilai('lokasi_penjemputan').length < 3) g.lokasi_penjemputan = 'Area penjemputan minimal 3 karakter.';

                        if (!['sudah', 'belum'].includes(nilai('status_penginapan'))) g.status_penginapan = 'Pilih status penginapan: sudah punya atau belum.';
                        else if (nilai('status_penginapan') === 'sudah') {
                            if (nilai('nama_hotel') === '') g.nama_hotel = 'Nama hotel atau penginapan wajib diisi bila Anda sudah punya penginapan.';
                            else if (nilai('nama_hotel').length < 3) g.nama_hotel = 'Nama hotel atau penginapan minimal 3 karakter.';
                            else if (/[<>]/.test(nilai('nama_hotel'))) g.nama_hotel = 'Nama hotel tidak boleh memuat karakter < atau >.';
                        }

                        this.galat = g;
                        const pertama = Object.keys(g)[0];
                        if (pertama) {
                            event.preventDefault();
                            const el = event.target.querySelector('[name=' + pertama + ']');
                            const sasaran = el?.type === 'hidden' ? el.closest('[data-kolom]') : el;
                            sasaran?.scrollIntoView({ block: 'center', behavior: 'smooth' });
                            if (el?.type !== 'hidden') el?.focus({ preventScroll: true });
                        }
                    },
                    bersihkan(event) { delete this.galat[event.target.name]; },
                }"
                x-on:submit="periksa($event)"
                x-on:input="bersihkan($event)"
                x-on:change="bersihkan($event)"
            >
                @csrf

                {{-- Honeypot anti-bot: kosong & tersembunyi dari manusia, bot biasanya mengisi semua field --}}
                <div class="sr-only" aria-hidden="true">
                    <label for="website">Website</label>
                    <input type="text" name="website" id="website" tabindex="-1" autocomplete="off" />
                </div>

                {{-- 1. Kenalan --}}
                <div class="fp-bagian">
                    <div class="fp-bagian__kepala">
                        <span class="fp-nomor" aria-hidden="true">1</span>
                        <div>
                            <h2 class="fp-judul">Kenalan dulu</h2>
                            <p class="fp-sub">Kami menghubungi Anda lewat WhatsApp untuk membahas rutenya.</p>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="rtk-nama" class="fp-f @error('nama') is-invalid @enderror" :class="galat.nama && 'is-invalid'">
                                <span class="fp-f__ikon"><x-bersama.ikon-formulir nama="nama" /></span>
                                <span class="fp-f__isi">
                                    <span class="fp-f__label">Nama Lengkap</span>
                                    <input type="text" name="nama" id="rtk-nama" :aria-invalid="galat.nama ? 'true' : 'false'" value="{{ old('nama') }}" required maxlength="255" autocomplete="name" placeholder="Nama seperti di KTP/paspor" />
                                </span>
                            </label>
                            @error('nama') <p class="fp-galat">{{ $message }}</p> @enderror
                            <p x-cloak x-show="galat.nama" x-text="galat.nama" class="fp-galat" role="alert"></p>
                        </div>

                        <div>
                            <label for="rtk-whatsapp" class="fp-f @error('whatsapp') is-invalid @enderror" :class="galat.whatsapp && 'is-invalid'">
                                <span class="fp-f__ikon"><x-bersama.ikon-wa class="size-[21px]" /></span>
                                <span class="fp-f__isi">
                                    <span class="fp-f__label">Nomor WhatsApp</span>
                                    <input type="tel" name="whatsapp" id="rtk-whatsapp" :aria-invalid="galat.whatsapp ? 'true' : 'false'" value="{{ old('whatsapp') }}" placeholder="081234567890" required inputmode="tel" autocomplete="tel" pattern="\+?[0-9][0-9 ().\-]{8,22}" maxlength="24" title="Masukkan nomor WhatsApp yang diawali 08 atau +62, contoh 081234567890." />
                                </span>
                            </label>
                            <p class="fp-bantu">Masukkan angka saja dan awali dengan 08, contoh 081234567890.</p>
                            @error('whatsapp') <p class="fp-galat">{{ $message }}</p> @enderror
                            <p x-cloak x-show="galat.whatsapp" x-text="galat.whatsapp" class="fp-galat" role="alert"></p>
                        </div>

                        <div data-kolom>
                            <div class="fp-pax" role="group" aria-labelledby="rtk-jumlah-label">
                                <div>
                                    <p class="text-[13px] font-medium text-white/70" id="rtk-jumlah-label">Jumlah Orang</p>
                                    <p class="mt-0.5 text-[15px] font-semibold">orang dalam rombongan</p>
                                </div>
                                <div class="fp-pax__kontrol">
                                    <button type="button" class="fp-pax__tombol" x-on:click="jumlah = Math.max(1, jumlah - 1); delete galat.jumlah_orang" :disabled="jumlah <= 1" aria-label="Kurangi jumlah orang">−</button>
                                    <span class="fp-pax__angka select-none" x-text="jumlah" aria-live="polite">{{ max(1, min(50, (int) old('jumlah_orang', 2))) }}</span>
                                    <button type="button" class="fp-pax__tombol" x-on:click="jumlah = Math.min(50, jumlah + 1); delete galat.jumlah_orang" :disabled="jumlah >= 50" aria-label="Tambah jumlah orang">+</button>
                                </div>
                            </div>
                            <input type="hidden" name="jumlah_orang" id="rtk-jumlah" :value="jumlah" value="{{ max(1, min(50, (int) old('jumlah_orang', 2))) }}" />
                            @error('jumlah_orang') <p class="fp-galat">{{ $message }}</p> @enderror
                            <p x-cloak x-show="galat.jumlah_orang" x-text="galat.jumlah_orang" class="fp-galat" role="alert"></p>
                        </div>
                    </div>
                </div>

                {{-- 2. Tujuan & minat --}}
                <div class="fp-bagian">
                    <div class="fp-bagian__kepala">
                        <span class="fp-nomor" aria-hidden="true">2</span>
                        <div>
                            <h2 class="fp-judul">Ke mana Anda ingin pergi?</h2>
                            <p class="fp-sub">Ketuk destinasi di bawah atau tulis sendiri. Boleh lebih dari satu.</p>
                        </div>
                    </div>

                    <div class="mb-4 flex flex-wrap gap-2">
                        @foreach ($pilihanDestinasi as $tempat)
                            <button type="button" x-on:click="pilihDestinasi(@js($tempat))" :aria-pressed="adaDestinasi(@js($tempat)) ? 'true' : 'false'"
                                class="rounded-full px-4 py-2.5 text-[14px] font-medium transition-colors duration-150"
                                :class="adaDestinasi(@js($tempat)) ? 'bg-[#12301F] text-white' : 'bg-[#F1F4EF] text-[#2F4A3A] hover:bg-[#E6ECE5]'">{{ $tempat }}</button>
                        @endforeach
                    </div>
                    <label for="rtk-destinasi" class="fp-f @error('destinasi_diinginkan') is-invalid @enderror" :class="galat.destinasi_diinginkan && 'is-invalid'">
                        <span class="fp-f__ikon"><x-bersama.ikon-formulir nama="lokasi" /></span>
                        <span class="fp-f__isi">
                            <span class="fp-f__label">Destinasi yang Diinginkan</span>
                            <input type="text" name="destinasi_diinginkan" id="rtk-destinasi" :aria-invalid="galat.destinasi_diinginkan ? 'true' : 'false'" x-model="destinasi" value="{{ old('destinasi_diinginkan') }}" placeholder="Contoh: Dieng, Bromo, dan Ijen" required maxlength="255" />
                        </span>
                    </label>
                    @error('destinasi_diinginkan') <p class="fp-galat">{{ $message }}</p> @enderror
                    <p x-cloak x-show="galat.destinasi_diinginkan" x-text="galat.destinasi_diinginkan" class="fp-galat" role="alert"></p>

                    <fieldset class="mt-7">
                        <legend class="text-[15px] font-semibold text-[#12301F]">Hal yang paling Anda sukai <span class="fp-opsional text-[13px]">(opsional)</span></legend>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($pilihanMinat as $nilai => $label)
                                <label class="fp-chip"><input type="checkbox" name="minat_perjalanan[]" value="{{ $nilai }}" @checked(in_array($nilai, (array) old('minat_perjalanan', []), true)) /><span>{{ $label }}</span></label>
                            @endforeach
                        </div>
                        @error('minat_perjalanan') <p class="fp-galat">{{ $message }}</p> @enderror
                    </fieldset>

                    <fieldset class="mt-7">
                        <legend class="text-[15px] font-semibold text-[#12301F]">Suasana perjalanan <span class="fp-opsional text-[13px]">(opsional)</span></legend>
                        <div class="mt-3 grid gap-3 sm:grid-cols-3">
                            @foreach ($pilihanTempo as $nilai => [$judul, $ket])
                                <label class="fp-pilih">
                                    <input type="radio" name="tempo_perjalanan" value="{{ $nilai }}" @checked(old('tempo_perjalanan') === $nilai) />
                                    <span class="fp-pilih__isi"><span><span class="fp-pilih__judul">{{ $judul }}</span><span class="fp-pilih__ket">{{ $ket }}</span></span></span>
                                </label>
                            @endforeach
                        </div>
                        @error('tempo_perjalanan') <p class="fp-galat">{{ $message }}</p> @enderror
                    </fieldset>
                </div>

                {{-- 3. Waktu --}}
                <div class="fp-bagian">
                    <div class="fp-bagian__kepala">
                        <span class="fp-nomor" aria-hidden="true">3</span>
                        <div>
                            <h2 class="fp-judul">Kapan dan berapa lama?</h2>
                            <p class="fp-sub">Belum pasti tanggalnya? Aktifkan tanggal fleksibel, kami bantu mencarikan waktu terbaik.</p>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]">
                        <div>
                            <label for="rtk-tanggal" class="fp-f @error('tanggal_diinginkan') is-invalid @enderror" :class="{ 'is-invalid': galat.tanggal_diinginkan, 'is-mati': tanggalFleksibel }">
                                <span class="fp-f__ikon"><x-bersama.ikon-formulir nama="kalender" /></span>
                                <span class="fp-f__isi">
                                    <span class="fp-f__label">Tanggal Perjalanan</span>
                                    <span class="fp-tampil" :class="! teksTanggal && 'is-kosong'" x-text="tanggalFleksibel ? 'Tanggal fleksibel' : (teksTanggal || 'Pilih tanggal')">Pilih tanggal</span>
                                </span>
                                <input
                                    type="date" name="tanggal_diinginkan" id="rtk-tanggal" class="fp-pemilih" :aria-invalid="galat.tanggal_diinginkan ? 'true' : 'false'" value="{{ old('tanggal_fleksibel') ? '' : old('tanggal_diinginkan') }}"
                                    x-ref="tanggal" x-model="tanggal" :required="!tanggalFleksibel" :disabled="tanggalFleksibel"
                                    min="{{ now()->addDays(7)->toDateString() }}" max="{{ now()->addDays(7)->addYear()->toDateString() }}"
                                />
                            </label>
                            @error('tanggal_diinginkan') <p class="fp-galat">{{ $message }}</p> @enderror
                            <p x-cloak x-show="galat.tanggal_diinginkan" x-text="galat.tanggal_diinginkan" class="fp-galat" role="alert"></p>
                            <label class="fp-saklar mt-4">
                                <input type="checkbox" name="tanggal_fleksibel" value="1" x-model="tanggalFleksibel" x-on:change="if (tanggalFleksibel) { $refs.tanggal.value = ''; tanggal = ''; delete galat.tanggal_diinginkan }" @checked(old('tanggal_fleksibel')) />
                                <span class="fp-saklar__jalur" aria-hidden="true"></span>
                                Tanggal saya masih fleksibel
                            </label>
                        </div>

                        <div data-kolom>
                            <div class="fp-f justify-between" role="group" aria-labelledby="rtk-durasi-label">
                                <span class="fp-f__isi">
                                    <span class="fp-f__label" id="rtk-durasi-label">Durasi (hari)</span>
                                    <span class="fp-tampil" x-text="durasi + ' hari'">{{ max(1, min(30, (int) old('durasi_hari', 3))) }} hari</span>
                                </span>
                                <span class="flex items-center gap-2">
                                    <button type="button" class="grid size-10 place-items-center rounded-full bg-white text-xl text-[#12301F] shadow-[0_0_0_1px_#E3E8E1] transition-colors hover:bg-[#EEF4EF] disabled:opacity-35" x-on:click="durasi = Math.max(1, durasi - 1); delete galat.durasi_hari" :disabled="durasi <= 1" aria-label="Kurangi durasi">−</button>
                                    <button type="button" class="grid size-10 place-items-center rounded-full bg-white text-xl text-[#12301F] shadow-[0_0_0_1px_#E3E8E1] transition-colors hover:bg-[#EEF4EF] disabled:opacity-35" x-on:click="durasi = Math.min(30, durasi + 1); delete galat.durasi_hari" :disabled="durasi >= 30" aria-label="Tambah durasi">+</button>
                                </span>
                            </div>
                            <input type="hidden" name="durasi_hari" id="rtk-durasi" :value="durasi" value="{{ max(1, min(30, (int) old('durasi_hari', 3))) }}" />
                            @error('durasi_hari') <p class="fp-galat">{{ $message }}</p> @enderror
                            <p x-cloak x-show="galat.durasi_hari" x-text="galat.durasi_hari" class="fp-galat" role="alert"></p>
                        </div>
                    </div>
                </div>

                {{-- 4. Jemput & menginap --}}
                <div class="fp-bagian">
                    <div class="fp-bagian__kepala">
                        <span class="fp-nomor" aria-hidden="true">4</span>
                        <div>
                            <h2 class="fp-judul">Jemput dan menginap</h2>
                            <p class="fp-sub">Kota atau area penjemputan cukup dulu; detail alamat bisa menyusul.</p>
                        </div>
                    </div>

                    <label for="rtk-jemput" class="fp-f @error('lokasi_penjemputan') is-invalid @enderror" :class="galat.lokasi_penjemputan && 'is-invalid'">
                        <span class="fp-f__ikon"><x-bersama.ikon-formulir nama="lokasi" /></span>
                        <span class="fp-f__isi">
                            <span class="fp-f__label">Area Penjemputan</span>
                            <input type="text" name="lokasi_penjemputan" id="rtk-jemput" :aria-invalid="galat.lokasi_penjemputan ? 'true' : 'false'" value="{{ old('lokasi_penjemputan') }}" placeholder="Contoh: Wonosobo, Malang" required maxlength="255" />
                        </span>
                    </label>
                    @error('lokasi_penjemputan') <p class="fp-galat">{{ $message }}</p> @enderror
                    <p x-cloak x-show="galat.lokasi_penjemputan" x-text="galat.lokasi_penjemputan" class="fp-galat" role="alert"></p>

                    <fieldset class="mt-6" data-kolom>
                        <legend class="text-[15px] font-semibold text-[#12301F]">Penginapan selama trip</legend>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            @foreach (['sudah' => ['Saya sudah punya penginapan', 'Sopir menjemput dari penginapan Anda.'], 'belum' => ['Saya belum punya, mohon direkomendasikan', 'Kami kirim rekomendasi lewat WhatsApp.']] as $nilai => [$judul, $ket])
                                <label class="fp-pilih">
                                    <input type="radio" name="status_penginapan" value="{{ $nilai }}" x-model="penginapan" @checked(old('status_penginapan') === $nilai) />
                                    <span class="fp-pilih__isi"><span><span class="fp-pilih__judul">{{ $judul }}</span><span class="fp-pilih__ket">{{ $ket }}</span></span></span>
                                </label>
                            @endforeach
                        </div>
                        @error('status_penginapan') <p class="fp-galat">{{ $message }}</p> @enderror
                        <p x-cloak x-show="galat.status_penginapan" x-text="galat.status_penginapan" class="fp-galat" role="alert"></p>
                        <p class="fp-bantu">Penginapan tidak termasuk dalam harga. Anda memesan dan membayarnya langsung ke penginapan; kami membantu dengan rekomendasi.</p>

                        <div x-show="penginapan === 'sudah'" x-cloak class="mt-4">
                            <label for="rtk-hotel" class="fp-f @error('nama_hotel') is-invalid @enderror" :class="galat.nama_hotel && 'is-invalid'">
                                <span class="fp-f__ikon"><x-bersama.ikon-formulir nama="nama" /></span>
                                <span class="fp-f__isi">
                                    <span class="fp-f__label">Nama hotel / penginapan</span>
                                    <input type="text" name="nama_hotel" id="rtk-hotel" value="{{ old('nama_hotel') }}" maxlength="255" placeholder="Contoh: Hotel Tugu Malang" :disabled="penginapan !== 'sudah'" :aria-invalid="galat.nama_hotel ? 'true' : 'false'" />
                                </span>
                            </label>
                            @error('nama_hotel') <p class="fp-galat">{{ $message }}</p> @enderror
                            <p x-cloak x-show="galat.nama_hotel" x-text="galat.nama_hotel" class="fp-galat" role="alert"></p>
                        </div>
                        <p x-show="penginapan === 'belum'" x-cloak class="mt-4 rounded-2xl bg-[#EEF4EF] px-4 py-3 text-[13.5px] leading-relaxed text-[#1D5A3F]">Tim kami akan mengirim rekomendasi penginapan lewat WhatsApp. Setelah Anda memesan, kabari nama penginapannya agar sopir menjemput dari sana.</p>
                    </fieldset>
                </div>

                {{-- 5. Cerita --}}
                <div class="fp-bagian">
                    <div class="fp-bagian__kepala">
                        <span class="fp-nomor" aria-hidden="true">5</span>
                        <div>
                            <h2 class="fp-judul">Ceritakan perjalanan impian Anda</h2>
                            <p class="fp-sub">Opsional, tetapi sangat membantu kami menyusun itinerary yang pas.</p>
                        </div>
                    </div>
                    <label for="rtk-catatan" class="fp-f fp-f--area">
                        <span class="fp-f__ikon"><x-bersama.ikon-formulir nama="catatan" /></span>
                        <span class="fp-f__isi">
                            <span class="fp-f__label">Ceritakan gambaran trip yang Anda inginkan <span class="fp-opsional">(opsional)</span></span>
                            <textarea name="catatan" id="rtk-catatan" rows="4" maxlength="1000" placeholder="Contoh: ingin menikmati sunrise, mengunjungi beberapa tempat alam, dan tidak terburu-buru. Tempat yang wajib dikunjungi atau hal yang ingin dihindari juga boleh diceritakan.">{{ old('catatan') }}</textarea>
                        </span>
                    </label>

                    <button type="submit" class="fp-kirim mt-7">
                        <x-bersama.ikon-wa class="size-5" />
                        Kirim Permintaan
                    </button>
                    <p class="mt-3 text-center text-[13px] text-[#7A857E]">WhatsApp akan terbuka dengan ringkasan perjalanan Anda setelah form dikirim.</p>
                </div>
            </form>

            {{-- CARA KERJA --}}
            <aside class="fp-ringkas" aria-label="Cara kerja trip kustom">
                <div class="fp-kartu overflow-hidden">
                    <div class="fp-ringkas__foto">
                        <img src="/images/lembang-tangkuban-parahu.webp" alt="Sawah dan desa di kaki Gunung Tangkuban Parahu saat matahari pagi" width="800" height="600" loading="lazy" decoding="async" />
                        <div class="absolute inset-x-0 bottom-0 p-5 text-white">
                            <p class="text-[20px] font-semibold leading-tight tracking-tight">Perjalanan yang dirancang untuk Anda</p>
                        </div>
                    </div>
                    <div class="p-6">
                        <ol class="space-y-5">
                            @foreach ($langkah as $i => [$judul, $isi])
                                <li class="flex gap-4">
                                    <span class="fp-nomor is-selesai" aria-hidden="true">{{ $i + 1 }}</span>
                                    <div>
                                        <p class="text-[15px] font-semibold text-[#12301F]">{{ $judul }}</p>
                                        <p class="mt-1 text-[13.5px] leading-relaxed text-[#5B6A61]">{{ $isi }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                        <div class="mt-6 rounded-2xl bg-[#EEF4EF] p-5">
                            <p class="text-[15px] font-semibold text-[#12301F]">Tanpa kewajiban</p>
                            <p class="mt-1.5 text-[13.5px] leading-relaxed text-[#3E5F4B]">Mengirim permintaan belum berarti memesan. Anda bebas menyesuaikan rute sampai penawarannya pas.</p>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</x-tata-letak.aplikasi>
