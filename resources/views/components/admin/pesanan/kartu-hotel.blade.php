@props(['pesanan'])

@php
    use App\Enums\StatusPenginapan;
    use App\Enums\StatusReservasiHotel;

    $terkunci = in_array($pesanan->status->value, ['completed', 'dibatalkan'], true);
    $status = $pesanan->status_penginapan;
    $tampil = $status !== null && $status !== StatusPenginapan::Sudah;
    $hotel = $pesanan->hotel;
    $adaReservasi = $pesanan->hotel_id !== null;
    $alurHotel = app(\App\Layanan\AlurReservasiHotel::class);
    $ringkasanHotel = $alurHotel->ringkasan($pesanan);
    $hotelSudahDiurus = $alurHotel->sudahDiurus($pesanan);

    $untukJourney = $pesanan->perjalanan?->hotel()->aktif()->pluck('hotel.id')->all() ?? [];
    $semuaHotel = \App\Models\Hotel::query()->aktif()->orderBy('nama')->get();
    if ($hotel && ! $semuaHotel->contains('id', $hotel->id)) {
        $semuaHotel->push($hotel);
    }
    $pilihanRekomendasi = $semuaHotel->whereIn('id', $untukJourney)->mapWithKeys(fn ($h) => [$h->id => "{$h->nama} · {$h->area}".($h->rentangTarif() ? " · {$h->rentangTarif()}" : '')])->all();
    $pilihanLain = $semuaHotel->whereNotIn('id', $untukJourney)->mapWithKeys(fn ($h) => [$h->id => "{$h->nama} · {$h->area}".($h->aktif ? '' : ' (tidak aktif)')])->all();

    // Tipe kamar milik tiap hotel, supaya dropdown tipe mengikuti hotel yang dipilih.
    $petaHotel = $semuaHotel->mapWithKeys(fn ($h) => [$h->id => ['tipe' => $h->daftarTipeKamar(), 'terdaftar' => $h->punyaTipeKamarTerdaftar(), 'masuk' => $h->jamCheckIn(), 'keluar' => $h->jamCheckOut()]])->all();

    $nilai = fn (string $kolom, $bawaan) => old($kolom, $bawaan);
    $checkInBawaan = $pesanan->hotel_check_in?->toDateString() ?? $pesanan->tanggal_trip->toDateString();
    $checkOutBawaan = $pesanan->hotel_check_out?->toDateString() ?? (($pesanan->tanggal_selesai && $pesanan->tanggal_selesai->gt($pesanan->tanggal_trip) ? $pesanan->tanggal_selesai : $pesanan->tanggal_trip->copy()->addDay())->toDateString());
    $kamarBawaan = $pesanan->jumlah_kamar ?? (int) ceil(max(1, $pesanan->jumlah_wisatawan) / max(1, (int) config("mistborne.biaya_perjalanan.tamu_per_kamar")));

    $terkonfirmasi = $pesanan->status_reservasi_hotel === StatusReservasiHotel::Terkonfirmasi;
    $dibatalkan = $pesanan->status_reservasi_hotel === StatusReservasiHotel::Dibatalkan;
    $hotelTerkunci = $terkonfirmasi && ! $terkunci;
@endphp

@if ($tampil)
    <x-admin.kartu id="reservasi-hotel" class="scroll-mt-24">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-sm font-semibold text-muted">Reservasi Hotel</h2>
                @if ($adaReservasi && ! $dibatalkan)
                    @if ($hotelSudahDiurus)
                        <span class="inline-flex items-center gap-1 rounded-full bg-status-completed-teks px-2.5 py-1 text-[11px] font-semibold text-white"><span class="material-symbols-outlined text-sm" aria-hidden="true">check_circle</span>Sudah diurus</span>
                    @else
                        <span class="rounded-full bg-status-payment-bg px-2.5 py-1 text-[11px] font-semibold text-status-payment-teks">Masih {{ $ringkasanHotel['total'] - $ringkasanHotel['selesai'] }} tahap lagi</span>
                    @endif
                @endif
            </div>
            @if ($pesanan->status_reservasi_hotel)
                <span @class([
                    'rounded-full px-2.5 py-1 text-[11px] font-semibold',
                    'bg-status-completed-bg text-status-completed-teks' => $terkonfirmasi,
                    'bg-status-payment-bg text-status-payment-teks' => $pesanan->status_reservasi_hotel === StatusReservasiHotel::Diminta,
                    'bg-status-dibatalkan-bg text-status-dibatalkan-teks' => $dibatalkan,
                ])>{{ $pesanan->status_reservasi_hotel->label() }}</span>
            @else
                <span class="rounded-full bg-page px-2.5 py-1 text-[11px] font-semibold text-muted">Belum ada reservasi</span>
            @endif
        </div>

        <p class="mb-3 text-xs text-muted">
            @if ($status === StatusPenginapan::Diurus)
                All-inclusive: biaya hotel sudah masuk harga paket. Pesankan hotel untuk tamu, lalu ikuti tahap di bawah.
            @elseif ($status === StatusPenginapan::Dipesankan)
                MISTBORNE memesankan hotel; tamu membayar kamar langsung ke hotel saat check-in.
            @else
                Tamu belum punya penginapan. Setelah tamu menyetujui satu hotel, simpan di sini; status penginapan otomatis menjadi &ldquo;Dipesankan MISTBORNE&rdquo;.
            @endif
        </p>

        @if ($errors->hasAny(['hotel_id', 'hotel_check_in', 'hotel_check_out', 'jumlah_kamar', 'tipe_kamar', 'catatan_reservasi_hotel']))
            <x-admin.peringatan type="danger" class="mb-4">Reservasi belum tersimpan. Periksa isian yang ditandai merah pada formulir reservasi.</x-admin.peringatan>
        @endif

        <x-admin.pesanan.alur-hotel :pesanan="$pesanan" :terkunci="$terkunci">
            {{-- Tahap 1: pilih hotel dan kamar. Status reservasi mengikuti tombol tiap tahap; kode booking diisi di tahap Hotel setuju. --}}
            <x-slot:formulir>
                @if ($hotelTerkunci)
                    <x-admin.peringatan type="info" class="mb-4">Hotel sudah terkonfirmasi, jadi tidak bisa diganti begitu saja. Mengganti hotel dikenai <strong>biaya (charge)</strong>. Pilihan hotel terbuka bila persetujuan biaya di bawah dicentang.</x-admin.peringatan>
                @endif

                <form method="POST" action="{{ route('admin.pesanan.hotel.simpan', $pesanan) }}" class="space-y-4"
                    x-data="{
                        terkunci: @js($terkunci),
                        petaHotel: @js($petaHotel),
                        hotelId: @js((string) $nilai('hotel_id', $pesanan->hotel_id ?? (count($untukJourney) === 1 ? $untukJourney[0] : ''))),
                        tipe: @js((string) $nilai('tipe_kamar', $pesanan->tipe_kamar)),
                        kamar: @js((int) $nilai('jumlah_kamar', $kamarBawaan)),
                        peserta: {{ max(1, (int) $pesanan->jumlah_wisatawan) }},
                        tamuPerKamar: {{ max(1, (int) config('mistborne.biaya_perjalanan.tamu_per_kamar')) }},
                        setujuBiaya: @js((bool) old('setuju_biaya_ganti_hotel')),
                        hotelAwal: @js((string) $pesanan->hotel_id),
                        terkonfirmasiAwal: @js($hotelTerkunci),
                        get daftarTipe() { return this.petaHotel[this.hotelId]?.tipe ?? []; },
                        get kapasitasTipe() { return this.daftarTipe.find((t) => t.nama === this.tipe)?.kapasitas ?? null; },
                        isiNyaman(t) { return Math.max(1, Math.min(t.kapasitas, this.tamuPerKamar)); },
                        kamarUntuk(t) { return Math.max(1, Math.ceil(this.peserta / this.isiNyaman(t))); },
                        get tipeDisarankan() {
                            return [...this.daftarTipe].sort((a, b) => this.kamarUntuk(a) - this.kamarUntuk(b) || a.kapasitas - b.kapasitas)[0]?.nama ?? '';
                        },
                        labelTipe(t) {
                            return t.nama + ' — muat ' + t.kapasitas + ' orang/kamar · ' + this.peserta + ' tamu = ' + this.kamarUntuk(t) + ' kamar' + (t.nama === this.tipeDisarankan ? ' · DISARANKAN' : '');
                        },
                        get hotelBerganti() { return this.terkonfirmasiAwal && this.hotelId !== '' && this.hotelId !== this.hotelAwal; },
                        get kunciHotel() { return this.terkonfirmasiAwal && ! this.setujuBiaya; },
                        pilihHotel() {
                            this.tipe = this.tipeDisarankan;
                            this.sesuaikanKamar();
                        },
                        sesuaikanKamar() {
                            const terpilih = this.daftarTipe.find((t) => t.nama === this.tipe);
                            if (terpilih) { this.kamar = this.kamarUntuk(terpilih); }
                        },
                        init() {
                            if (this.hotelId !== '' && ! this.tipe) { this.tipe = this.tipeDisarankan; this.sesuaikanKamar(); }
                        },
                    }">
                    @csrf
                    @method('PUT')

                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-admin.formulir.kolom label="Hotel" for="hotel_id" name="hotel_id" wajib class="sm:col-span-2">
                            <select id="hotel_id" name="hotel_id" required x-model="hotelId" x-on:change="pilihHotel()" @disabled($terkunci) x-bind:disabled="kunciHotel || terkunci" class="w-full rounded-lg border bg-white py-2.5 ps-3.5 pe-9 text-sm text-ink focus:outline-none focus:ring-1 disabled:bg-page disabled:text-muted {{ $errors->has('hotel_id') ? 'border-status-dibatalkan-teks' : 'border-surface-border focus:border-hijau-aksi focus:ring-hijau-aksi' }}">
                                <option value="">Pilih hotel</option>
                                @if ($pilihanRekomendasi)
                                    <optgroup label="Rekomendasi untuk {{ \Illuminate\Support\Str::title(mb_strtolower($pesanan->nama_perjalanan)) }}">
                                        @foreach ($pilihanRekomendasi as $id => $teks)
                                            <option value="{{ $id }}">{{ $teks }}</option>
                                        @endforeach
                                    </optgroup>
                                @endif
                                @if ($pilihanLain)
                                    <optgroup label="Hotel lainnya">
                                        @foreach ($pilihanLain as $id => $teks)
                                            <option value="{{ $id }}">{{ $teks }}</option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            </select>
                            {{-- Select yang dinonaktifkan tidak terkirim; nilai hotel tetap dikirim lewat input tersembunyi. --}}
                            <input type="hidden" name="hotel_id" :value="hotelId" x-bind:disabled="! kunciHotel">
                        </x-admin.formulir.kolom>

                        @if ($hotelTerkunci)
                            <label class="flex items-start gap-2 text-sm text-ink sm:col-span-2">
                                <input type="checkbox" name="setuju_biaya_ganti_hotel" value="1" x-model="setujuBiaya" class="mt-0.5 rounded border-surface-border text-hijau-aksi focus:ring-hijau-aksi">
                                <span>Tamu setuju dikenai biaya ganti hotel (charge)<span class="block text-xs text-muted">Centang hanya bila tamu sudah menyetujui biaya penggantian hotel. Nominal disampaikan admin ke tamu.</span></span>
                            </label>
                        @endif

                        <x-admin.formulir.masukan name="hotel_check_in" type="date" label="Check-in" :value="$checkInBawaan" wajib :disabled="$terkunci" />
                        <x-admin.formulir.masukan name="hotel_check_out" type="date" label="Check-out" :value="$checkOutBawaan" wajib :disabled="$terkunci" />

                        <x-admin.formulir.kolom label="Tipe Kamar (pilih yang DISARANKAN)" for="tipe_kamar" name="tipe_kamar">
                            <select id="tipe_kamar" name="tipe_kamar" x-model="tipe" x-on:change="sesuaikanKamar()" @disabled($terkunci) class="w-full rounded-lg border bg-white py-2.5 ps-3.5 pe-9 text-sm text-ink focus:outline-none focus:ring-1 {{ $errors->has('tipe_kamar') ? 'border-status-dibatalkan-teks' : 'border-surface-border focus:border-hijau-aksi focus:ring-hijau-aksi' }}">
                                <template x-if="daftarTipe.length === 0"><option value="">Pilih hotel dulu</option></template>
                                <template x-for="t in daftarTipe" :key="t.nama">
                                    <option :value="t.nama" :selected="t.nama === tipe" x-text="labelTipe(t)"></option>
                                </template>
                            </select>
                        </x-admin.formulir.kolom>

                        <x-admin.formulir.kolom label="Jumlah Kamar" for="jumlah_kamar" name="jumlah_kamar" wajib>
                            <input id="jumlah_kamar" name="jumlah_kamar" type="number" min="1" max="30" x-model.number="kamar" required @disabled($terkunci)
                                x-bind:placeholder="kapasitasTipe ? 'Otomatis: ' + peserta + ' tamu ÷ maks. ' + Math.min(kapasitasTipe, tamuPerKamar) + ' orang per kamar (standar nyaman MISTBORNE)' : 'Jumlah kamar yang dipesan'"
                                class="w-full rounded-lg border bg-white px-3.5 py-2.5 text-sm text-ink placeholder:text-muted focus:outline-none focus:ring-1 {{ $errors->has('jumlah_kamar') ? 'border-status-dibatalkan-teks' : 'border-surface-border focus:border-hijau-aksi focus:ring-hijau-aksi' }}">
                        </x-admin.formulir.kolom>

                        <details class="sm:col-span-2" @if (filled(old('catatan_reservasi_hotel', $pesanan->catatan_reservasi_hotel)) || $errors->has('catatan_reservasi_hotel')) open @endif>
                            <summary class="cursor-pointer list-none text-xs font-semibold text-hijau-aksi hover:underline">+ Catatan untuk hotel (opsional)</summary>
                            <x-admin.formulir.area-teks name="catatan_reservasi_hotel" :value="$pesanan->catatan_reservasi_hotel" rows="3" :disabled="$terkunci" class="mt-2" placeholder="Permintaan khusus ke hotel: kamar bersebelahan, lantai bawah, late check-in (ikut terkirim ke hotel)" />
                        </details>
                    </div>

                    @unless ($terkunci)
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            @if ($adaReservasi && ! $terkonfirmasi && ! $dibatalkan)
                                <x-admin.tombol type="submit" form="form-lepas-hotel" variant="danger-outline" size="sm" icon="close">Lepas hotel</x-admin.tombol>
                            @elseif ($terkonfirmasi)
                                <x-admin.tombol type="submit" form="form-batal-hotel" variant="danger-outline" size="sm" icon="cancel">Batalkan reservasi</x-admin.tombol>
                            @else
                                <span></span>
                            @endif
                            <x-admin.tombol type="submit">{{ $adaReservasi && ! $dibatalkan ? 'Simpan perubahan' : 'Simpan reservasi' }}</x-admin.tombol>
                        </div>
                    @endunless
                </form>

                @if ($adaReservasi && ! $terkunci && ! $terkonfirmasi && ! $dibatalkan)
                    <form id="form-lepas-hotel" method="POST" action="{{ route('admin.pesanan.hotel.lepas', $pesanan) }}" class="hidden">
                        @csrf
                        @method('DELETE')
                    </form>
                @endif
                @if ($terkonfirmasi && ! $terkunci)
                    <form id="form-batal-hotel" method="POST" action="{{ route('admin.pesanan.hotel.batal', $pesanan) }}" class="hidden" onsubmit="return confirm('Batalkan reservasi hotel ini? Draft pembatalan ke hotel akan terbuka setelahnya.')">
                        @csrf
                        @method('PATCH')
                    </form>
                @endif
            </x-slot:formulir>
        </x-admin.pesanan.alur-hotel>
    </x-admin.kartu>

    <x-admin.pesanan.modal-draft-hotel :pesanan="$pesanan" />
@endif
