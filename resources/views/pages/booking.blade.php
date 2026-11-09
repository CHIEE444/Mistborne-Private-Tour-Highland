{{-- FORMULIR BOOKING: empat bagian bernomor (tamu → jadwal → penjemputan → catatan) dengan ringkasan all-inclusive di kanan. --}}
@php
    $namaJourney = $journey['name'];
    $foto = $journey['img'];
    $specs = $journey['specs'];
    $price = $journey['price'];
    $batasTamu = max(1, (int) ($journey['maks_orang'] ?? 15));
    $durasiHari = (int) ($journey['durasi_hari'] ?? 1);
    $tamuPerKamar = max(1, (int) config('mistborne.biaya_perjalanan.tamu_per_kamar'));
    $termasuk = [
        __('Hotel bintang 4, kamar menyesuaikan jumlah tamu'),
        __('Tiket masuk semua objek wisata di itinerary'),
        __('Mobil privat, BBM, tol, dan parkir'),
        __('Pemandu lokal MISTBORNE yang merangkap sopir'),
    ];
    $ikonCentang = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12.5 4.2 4.2L19 7" /></svg>';
@endphp
<x-tata-letak.aplikasi
    :title="__('Formulir Booking — :name', ['name' => $journey['name']])"
    :description="__('Lengkapi data diri untuk memesan :name via WhatsApp.', ['name' => $journey['name']])"
    :footer-rapat="true"
>
    {{-- SAMPUL --}}
    <section class="fp-sampul" aria-labelledby="booking-judul">
        <img src="{{ $foto }}" alt="" class="fp-sampul__img" width="1600" height="1000" decoding="async" fetchpriority="high" />
        <div class="relative mx-auto max-w-[1240px] px-4 pb-44 pt-28 sm:px-8 sm:pt-32 lg:pb-52">
            <x-bersama.tombol-beranda />
            <p class="mt-12 text-[15px] font-medium text-white/80">{{ __('Formulir booking') }}</p>
            <h1 id="booking-judul" class="mt-2 max-w-[18ch] text-balance text-[36px] font-semibold leading-[1.02] tracking-[-0.02em] sm:text-[52px] lg:text-[60px]">{{ $namaJourney }}</h1>
            <ul class="mt-6 flex flex-wrap gap-2 text-[13.5px] font-medium text-white">
                @foreach (array_filter([$journey['place'], $journey['duration'], $journey['capacity']]) as $fakta)
                    <li class="fp-kaca rounded-full px-4 py-2">{{ $fakta }}</li>
                @endforeach
            </ul>
        </div>
    </section>

    <div class="relative mx-auto -mt-36 max-w-[1240px] px-4 sm:px-8 lg:-mt-40">
        <div
            x-data="{
                    pax: {{ $form['pax'] ?? 1 }},
                    tanggal: @js(old('tanggal_trip', '')),
                    jam: @js(old('waktu_pickup', '')),
                    nama: @js(old('nama_lengkap', $user->name ?? '')),
                    wa: @js(old('whatsapp', '')),
                    jemput: @js(old('lokasi_pickup', '')),
                    durasiHari: {{ $durasiHari }},
                    adaTanggal: @js($daftarTanggal->where('tersedia', true)->isNotEmpty()),
                    bahasa: @js(str_replace('_', '-', app()->getLocale())),
                    batas: {{ $batasTamu }},
                    catatanKamar: @js(__('Untuk :orang tamu, Anda mendapat :kamar kamar.')),
                    catatanKapasitas: @js(__('Kapasitas :maks tamu. Pemandu MISTBORNE yang merangkap sopir memakai 1 kursi dan tidak dihitung sebagai tamu.')),
                    teksLama: @js(__(':hari hari :malam malam')),
                    teksSehari: @js(__('Satu hari')),
                    sedangMengirim: false,
                    pengatur: null,
                    // Setelah pesanan tersimpan, browser dialihkan ke WhatsApp (aplikasi/tab lain) sehingga halaman ini
                    // tidak ikut ditutup: tombol harus dilepas lagi — saat kembali (back/cache) atau setelah jeda aman.
                    mulaiKirim() {
                        if (this.sedangMengirim) return false;
                        this.sedangMengirim = true;
                        clearTimeout(this.pengatur);
                        this.pengatur = setTimeout(() => { this.sedangMengirim = false }, 8000);
                        return true;
                    },
                    init() {
                        window.addEventListener('pageshow', () => { this.sedangMengirim = false; clearTimeout(this.pengatur); });
                    },
                    formatTanggal(tanggal) { return tanggal.toLocaleDateString(this.bahasa, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }); },
                    formatPendek(tanggal) { return tanggal.toLocaleDateString(this.bahasa, { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' }); },
                    get tanggalBerangkat() { return this.tanggal ? new Date(this.tanggal + 'T00:00:00') : null; },
                    get tanggalSelesai() { const awal = this.tanggalBerangkat; if (! awal || isNaN(awal)) return null; const akhir = new Date(awal); akhir.setDate(akhir.getDate() + this.durasiHari - 1); return akhir; },
                    get teksDurasi() { return this.durasiHari <= 1 ? this.teksSehari : this.teksLama.replace(':hari', this.durasiHari).replace(':malam', this.durasiHari - 1); },
                    get kamar() { return Math.max(1, Math.ceil(this.pax / {{ $tamuPerKamar }})); },
                    get teksKamar() { return this.catatanKamar.replace(':orang', this.pax).replace(':kamar', this.kamar); },
                    get teksKapasitas() { return this.catatanKapasitas.replace(':maks', this.batas); },
                    get namaOk() { return this.nama.trim().length >= 3 && ! /[<>]/.test(this.nama); },
                    get waOk() { const lokal = this.wa.replace(/[\s\-.()]/g, '').replace(/^\+?62/, '0'); return /^08[0-9]{7,12}$/.test(lokal); },
                    get jemputOk() { return this.jemput.trim().length >= 3 && ! /[<>]/.test(this.jemput); },
                    get bagianTamu() { return this.namaOk && this.waOk; },
                    inc() { if (this.pax < this.batas) this.pax++ },
                    dec() { if (this.pax > 1) this.pax-- },
                }"
            class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_380px] lg:items-start lg:gap-8"
        >
            {{-- FORMULIR --}}
            <div class="fp-kartu">
                <form
                    method="POST"
                    action="{{ route('booking.simpan') }}"
                    x-on:submit="if (! mulaiKirim()) { $event.preventDefault(); }"
                >
                    @csrf
                    <input type="hidden" name="token_booking" value="{{ old('token_booking', $tokenBooking) }}" />
                    <input type="hidden" name="destination" value="{{ $journey['name'] }}" />

                    @if ($errors->any())
                        <div role="alert" class="mx-5 mt-5 rounded-2xl bg-[#FFF4EF] px-5 py-4 text-sm text-[#9A3412] sm:mx-9 sm:mt-8">
                            <p class="font-semibold">{{ __('Mohon periksa kembali data pesanan:') }}</p>
                            <ul class="mt-2 list-inside list-disc space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- 1. Tamu --}}
                    <div class="fp-bagian">
                        <div class="fp-bagian__kepala">
                            <span class="fp-nomor" :class="bagianTamu && 'is-selesai'" aria-hidden="true">1</span>
                            <div>
                                <h2 class="fp-judul">{{ __('Siapa yang berangkat?') }}</h2>
                                <p class="fp-sub">{{ __('Satu booking untuk satu rombongan privat. Harga tidak dihitung per orang.') }}</p>
                            </div>
                        </div>

                        <div class="fp-pax">
                            <div>
                                <p class="text-[13px] font-medium text-white/70" id="booking-pax-label">{{ __('Jumlah tamu') }}</p>
                                <div class="fp-pax__orang" aria-hidden="true">
                                    @for ($i = 1; $i <= min($batasTamu, 12); $i++)
                                        <svg viewBox="0 0 24 24" fill="currentColor" :style="{ opacity: {{ $i }} <= pax ? 1 : .25 }"><circle cx="12" cy="7.5" r="3.6" /><path d="M4.8 20.2c0-4 3.2-6.7 7.2-6.7s7.2 2.7 7.2 6.7Z" /></svg>
                                    @endfor
                                </div>
                            </div>
                            <div class="fp-pax__kontrol" role="group" aria-labelledby="booking-pax-label">
                                <button type="button" x-on:click="dec()" class="fp-pax__tombol" aria-label="{{ __('Kurangi pax') }}" :disabled="pax <= 1">−</button>
                                <span class="fp-pax__angka select-none" x-text="pax" aria-live="polite">{{ $form['pax'] ?? 1 }}</span>
                                <button type="button" x-on:click="inc()" class="fp-pax__tombol" aria-label="{{ __('Tambah pax') }}" :disabled="pax >= batas">+</button>
                            </div>
                        </div>
                        <input type="hidden" name="pax" x-model="pax" id="booking-pax" />
                        <p class="fp-bantu" x-text="teksKapasitas">{{ __('Kapasitas :maks tamu. Pemandu MISTBORNE yang merangkap sopir memakai 1 kursi dan tidak dihitung sebagai tamu.', ['maks' => $batasTamu]) }}</p>
                        @error('pax') <p class="fp-galat">{{ $message }}</p> @enderror

                        <div class="mt-5 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="booking-nama" class="fp-f @error('nama_lengkap') is-invalid @enderror">
                                    <span class="fp-f__ikon"><x-bersama.ikon-formulir nama="nama" /></span>
                                    <span class="fp-f__isi">
                                        <span class="fp-f__label">{{ __('Nama Lengkap') }}</span>
                                        <input id="booking-nama" name="nama_lengkap" type="text" required maxlength="255" autocomplete="name"
                                            x-model="nama" value="{{ old('nama_lengkap', $user->name ?? '') }}"
                                            placeholder="{{ __('Nama seperti di KTP/paspor') }}" />
                                    </span>
                                    <span class="fp-ok" x-show="namaOk" x-cloak aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m5 12.5 4.2 4.2L19 7" /></svg></span>
                                </label>
                                @error('nama_lengkap') <p class="fp-galat">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="booking-wa" class="fp-f @error('whatsapp') is-invalid @enderror">
                                    <span class="fp-f__ikon"><x-bersama.ikon-wa class="size-[21px]" /></span>
                                    <span class="fp-f__isi">
                                        <span class="fp-f__label">{{ __('Nomor WhatsApp') }}</span>
                                        <input id="booking-wa" name="whatsapp" type="tel" required inputmode="tel" autocomplete="tel" maxlength="15"
                                            x-model="wa" value="{{ old('whatsapp') }}" placeholder="{{ __('081234567890') }}" />
                                    </span>
                                    <span class="fp-ok" x-show="waOk" x-cloak aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m5 12.5 4.2 4.2L19 7" /></svg></span>
                                </label>
                                @error('whatsapp') <p class="fp-galat">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- 2. Jadwal --}}
                    <div class="fp-bagian">
                        <div class="fp-bagian__kepala">
                            <span class="fp-nomor" :class="tanggalSelesai && 'is-selesai'" aria-hidden="true">2</span>
                            <div>
                                <h2 class="fp-judul">{{ __('Kapan Anda ingin berangkat?') }}</h2>
                                <p class="fp-sub">{{ __('Pilih salah satu tanggal keberangkatan yang tersedia. Tanggal yang penuh tidak bisa dipilih.') }}</p>
                            </div>
                        </div>

                        {{-- Hanya Tanggal Tersedia journey ini (KapasitasTripLayanan): sisa slot = kuota journey dan guide yang masih bebas. --}}
                        @if ($daftarTanggal->where('tersedia', true)->isEmpty())
                            <div class="fp-tanggal-kosong" role="status">
                                <p class="font-semibold text-[#12301F]">{{ __('Belum ada tanggal yang bisa dipesan untuk perjalanan ini.') }}</p>
                                <p class="mt-1">{{ __('Jadwal baru segera dibuka. Tanyakan jadwal terdekat lewat WhatsApp, atau rancang trip kustom dengan tanggal pilihan Anda.') }}</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <a href="https://wa.me/{{ \App\Models\Pengaturan::current()->nomor_whatsapp_admin }}?text={{ rawurlencode(__('Halo Mistborne, saya ingin menanyakan jadwal terdekat untuk :journey.', ['journey' => $namaJourney])) }}" target="_blank" rel="noopener noreferrer" data-catat-wa="{{ __('Tanya jadwal :journey', ['journey' => $namaJourney]) }}" class="fp-tanggal-kosong__tombol">{{ __('Tanya jadwal lewat WhatsApp') }}</a>
                                    <a href="{{ route('rancang-trip-kustom') }}" class="fp-tanggal-kosong__tombol is-sekunder">{{ __('Rancang Trip Kustom') }}</a>
                                </div>
                            </div>
                        @else
                            <fieldset class="fp-tanggal @error('tanggal_trip') is-invalid @enderror">
                                <legend class="sr-only">{{ __('Tanggal Perjalanan') }}</legend>
                                @foreach ($daftarTanggal->groupBy(fn (array $opsi): string => \Illuminate\Support\Carbon::parse($opsi['tanggal'])->translatedFormat('F Y')) as $bulan => $daftarBulan)
                                    <p class="fp-tanggal__bulan">{{ $bulan }}</p>
                                    <div class="fp-tanggal__daftar">
                                        @foreach ($daftarBulan as $opsi)
                                            @php $hari = \Illuminate\Support\Carbon::parse($opsi['tanggal']); @endphp
                                            <label class="fp-tanggal__opsi" @if (! $opsi['tersedia']) title="{{ __($opsi['alasan']) }}" @endif>
                                                <input type="radio" name="tanggal_trip" value="{{ $opsi['tanggal'] }}" x-model="tanggal" required @disabled(! $opsi['tersedia']) @checked(old('tanggal_trip') === $opsi['tanggal']) />
                                                <span>
                                                    <span class="fp-tanggal__hari">{{ $hari->translatedFormat('D') }}</span>
                                                    <span class="fp-tanggal__angka">{{ $hari->translatedFormat('d M') }}</span>
                                                    <span class="fp-tanggal__sisa">{{ $opsi['tersedia'] ? __('Sisa :jumlah slot', ['jumlah' => $opsi['sisa']]) : __($opsi['label']) }}</span>
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                @endforeach
                            </fieldset>
                        @endif
                        @error('tanggal_trip') <p class="fp-galat">{{ $message }}</p> @enderror

                        <div class="fp-linimasa" aria-live="polite">
                            <div>
                                <span class="fp-linimasa__label">{{ __('Berangkat') }}</span>
                                <span class="fp-linimasa__tanggal" x-text="tanggalBerangkat ? formatPendek(tanggalBerangkat) : '—'">—</span>
                            </div>
                            <div class="fp-linimasa__garis"><span><span class="sr-only">{{ __('Durasi perjalanan') }}: </span><span x-text="teksDurasi">{{ $journey['duration'] }}</span></span></div>
                            <div class="text-end">
                                <span class="fp-linimasa__label">{{ __('Selesai') }}</span>
                                <span class="fp-linimasa__tanggal" x-text="tanggalSelesai ? formatPendek(tanggalSelesai) : '—'">—</span>
                            </div>
                        </div>
                        <p class="fp-bantu">{{ __('Durasi perjalanan') }}: <strong class="font-semibold text-[#12301F]">{{ $journey['duration'] }}</strong>. <span x-show="! tanggalSelesai">{{ __('Pilih tanggal untuk melihat tanggal selesai perjalanan.') }}</span></p>
                    </div>

                    {{-- 3. Penjemputan --}}
                    <div class="fp-bagian">
                        <div class="fp-bagian__kepala">
                            <span class="fp-nomor" :class="jemputOk && 'is-selesai'" aria-hidden="true">3</span>
                            <div>
                                <h2 class="fp-judul">{{ __('Di mana kami menjemput Anda?') }}</h2>
                                <p class="fp-sub">{{ __('Pemandu menunggu di lobi atau titik yang Anda tentukan.') }}</p>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
                            <div>
                                <label for="booking-pickup" class="fp-f @error('lokasi_pickup') is-invalid @enderror">
                                    <span class="fp-f__ikon"><x-bersama.ikon-formulir nama="lokasi" /></span>
                                    <span class="fp-f__isi">
                                        <span class="fp-f__label">{{ __('Alamat / titik jemput') }}</span>
                                        <input id="booking-pickup" name="lokasi_pickup" type="text" required maxlength="255" x-model="jemput"
                                            value="{{ old('lokasi_pickup') }}" placeholder="{{ __('Alamat lengkap, nama hotel, atau patokan') }}" />
                                    </span>
                                    <span class="fp-ok" x-show="jemputOk" x-cloak aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m5 12.5 4.2 4.2L19 7" /></svg></span>
                                </label>
                                @error('lokasi_pickup') <p class="fp-galat">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="booking-jam" class="fp-f @error('waktu_pickup') is-invalid @enderror">
                                    <span class="fp-f__ikon"><x-bersama.ikon-formulir nama="jam" /></span>
                                    <span class="fp-f__isi">
                                        <span class="fp-f__label">{{ __('Jam jemput') }} <span class="fp-opsional">({{ __('opsional') }})</span></span>
                                        <span class="fp-tampil" :class="! jam && 'is-kosong'" x-text="jam ? jam + ' WIB' : @js(__('Pilih jam'))">{{ __('Pilih jam') }}</span>
                                    </span>
                                    <input id="booking-jam" name="waktu_pickup" type="time" class="fp-pemilih" value="{{ old('waktu_pickup') }}" x-model="jam" />
                                </label>
                                @error('waktu_pickup') <p class="fp-galat">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <p class="fp-bantu">{{ __('Waktu setempat (WIB); bisa dilengkapi setelah konfirmasi.') }}</p>
                    </div>

                    {{-- 4. Catatan --}}
                    <div class="fp-bagian">
                        <div class="fp-bagian__kepala">
                            <span class="fp-nomor" aria-hidden="true">4</span>
                            <div>
                                <h2 class="fp-judul">{{ __('Ada yang perlu kami siapkan?') }}</h2>
                                <p class="fp-sub">{{ __('Opsional. Ceritakan kebutuhan rombongan agar perjalanan terasa lebih nyaman.') }}</p>
                            </div>
                        </div>

                        <div class="grid gap-4">
                            <div>
                                <label for="booking-paket" class="fp-f @error('paket_opsional') is-invalid @enderror">
                                    <span class="fp-f__ikon"><x-bersama.ikon-formulir nama="catatan" /></span>
                                    <span class="fp-f__isi">
                                        <span class="fp-f__label">{{ __('Catatan tambahan') }} <span class="fp-opsional">({{ __('opsional') }})</span></span>
                                        <input id="booking-paket" name="paket_opsional" type="text" maxlength="255" value="{{ old('paket_opsional') }}"
                                            placeholder="{{ __('Contoh: tambahan makan atau penjemputan khusus') }}" />
                                    </span>
                                </label>
                                <p class="fp-bantu">{{ __('Jika ada biaya tambahan, konfirmasikan dengan admin. Catatan ini tidak menambah harga otomatis.') }}</p>
                                @error('paket_opsional') <p class="fp-galat">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="booking-catatan" class="fp-f fp-f--area @error('permintaan_khusus') is-invalid @enderror">
                                    <span class="fp-f__ikon"><x-bersama.ikon-formulir nama="peserta" /></span>
                                    <span class="fp-f__isi">
                                        <span class="fp-f__label">{{ __('Permintaan Khusus') }} <span class="fp-opsional">({{ __('opsional') }})</span></span>
                                        <textarea id="booking-catatan" name="permintaan_khusus" rows="3" maxlength="1000" placeholder="{{ __('diet, kursi extrawheel, dll') }}">{{ old('permintaan_khusus') }}</textarea>
                                    </span>
                                </label>
                                @error('permintaan_khusus') <p class="fp-galat">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Kirim --}}
                    <div class="fp-bagian">
                        <button data-booking-submit type="submit" class="fp-kirim"
                            x-bind:disabled="sedangMengirim || ! adaTanggal"
                            x-bind:aria-busy="sedangMengirim"
                        >
                            <x-bersama.ikon-wa class="size-5" />
                            <span x-text="sedangMengirim ? @js(__('Menyimpan pesanan...')) : @js(__('Lanjut ke WhatsApp'))">{{ __('Lanjut ke WhatsApp') }}</span>
                        </button>
                        <p class="mt-3 text-center text-[13px] text-[#7A857E]">{{ __('WhatsApp akan terbuka dengan pesan yang sudah diisi otomatis.') }} {{ __('Data ini akan ikut dalam draft WhatsApp; sebelum WA terkirim, Anda bisa mengedit isi pesan langsung di aplikasi WhatsApp.') }}</p>
                    </div>
                </form>
            </div>

            {{-- RINGKASAN --}}
            <aside class="fp-ringkas" aria-label="{{ __('Ringkasan booking') }}">
                <div class="fp-kartu overflow-hidden">
                    <div class="fp-ringkas__foto">
                        <img src="{{ $foto }}" alt="{{ $namaJourney }}" width="800" height="600" loading="lazy" decoding="async" />
                        <div class="absolute inset-x-0 bottom-0 p-5 text-white">
                            <p class="text-[13px] text-white/80">{{ $journey['place'] }}</p>
                            <p class="mt-1 text-[20px] font-semibold leading-tight tracking-tight">{{ $namaJourney }}</p>
                        </div>
                    </div>
                    <div class="p-6">
                        @if ($price)
                            <p class="text-[13px] text-[#6B776F]">{{ __('Mulai dari') }}</p>
                            <p class="mt-1 text-[28px] font-semibold leading-none tracking-tight tabular-nums text-[#12301F]"><bdi>{{ $price }}</bdi></p>
                            <p class="mt-2 text-[13px] text-[#6B776F]">{{ __('per booking') }} · {{ $specs[0]['text'] }} · {{ $journey['capacity'] }}</p>
                        @else
                            <p class="text-[15px] font-medium text-[#12301F]">{{ __('Harga mengikuti itinerary final.') }}</p>
                        @endif

                        <div class="mt-6 rounded-2xl bg-[#EEF4EF] p-5">
                            <p class="text-[15px] font-semibold text-[#12301F]">{{ __('All-inclusive: tamu cukup membayar sekali') }}</p>
                            <p class="mt-1.5 text-[13.5px] leading-relaxed text-[#3E5F4B]">{{ __('Penginapan bintang 4 (4 orang per kamar) dan tiket masuk objek wisata sudah termasuk dalam harga. Tidak ada biaya mendadak di lokasi.') }}</p>
                            <ul class="fp-termasuk mt-4">
                                @foreach ($termasuk as $butir)
                                    <li>{!! $ikonCentang !!}<span>{{ $butir }}</span></li>
                                @endforeach
                            </ul>
                        </div>

                        @if (count($hotel))
                            <div class="mt-6">
                                <p class="text-[15px] font-semibold text-[#12301F]">{{ __('Akomodasi Selama Perjalanan') }}</p>
                                <p class="mt-1 text-[13.5px] text-[#3E5F4B]">{{ __('Jumlah kamar menyesuaikan jumlah tamu (4 orang per kamar).') }} <span class="font-semibold text-[#12301F]" x-text="teksKamar" aria-live="polite"></span></p>
                                <x-bersama.kartu-hotel-all-inclusive :hotel="$hotel" :ringkas="true" class="mt-3" />
                            </div>
                        @endif
                    </div>
                </div>
            </aside>
        </div>
    </div>
</x-tata-letak.aplikasi>
