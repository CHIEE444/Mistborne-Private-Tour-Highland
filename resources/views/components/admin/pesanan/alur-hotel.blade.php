@props(['pesanan', 'terkunci' => false])

{{--
    Stepper Reservasi Hotel empat tahap; urutan dan status tiap tahap diputuskan AlurReservasiHotel. Hanya tahap yang
    sedang dikerjakan yang terbuka, tahap selesai dilipat jadi satu baris ringkas, tahap berikutnya abu-abu. Formulir
    pilih hotel masuk lewat slot `formulir` (hanya di detail pesanan; antrean Urus Hotel mengarah ke sana).
--}}
@php
    use App\Bantuan\FormatRupiah;
    use App\Enums\StatusPenginapan;
    use App\Enums\StatusReservasiHotel;

    $alur = app(\App\Layanan\AlurReservasiHotel::class);
    $pesan = app(\App\Layanan\PesanWhatsAppLayanan::class);
    $hotel = $pesanan->hotel;
    $adaReservasi = $alur->adaReservasi($pesanan);
    $dibatalkan = $alur->dibatalkan($pesanan);
    $tahap = $alur->langkah($pesanan);
    $berikutnya = $alur->langkahBerikutnya($pesanan);
    $ringkasan = $alur->ringkasan($pesanan);
    $terkonfirmasi = $pesanan->status_reservasi_hotel === StatusReservasiHotel::Terkonfirmasi;
    $adaFormulir = isset($formulir);
    $galatReservasi = $errors->hasAny(['hotel_id', 'hotel_check_in', 'hotel_check_out', 'jumlah_kamar', 'tipe_kamar', 'catatan_reservasi_hotel']);

    $malam = $pesanan->hotel_check_in && $pesanan->hotel_check_out ? (int) $pesanan->hotel_check_in->diffInDays($pesanan->hotel_check_out) : 0;
    $perkiraanBayar = $hotel?->tarif_mulai && $pesanan->jumlah_kamar && $malam > 0 ? $hotel->tarif_mulai * $pesanan->jumlah_kamar * $malam : null;
    $extBukti = $pesanan->bayar_hotel_bukti ? strtolower(pathinfo($pesanan->bayar_hotel_bukti, PATHINFO_EXTENSION)) : null;

    // "Periksa sebelum kirim" hanya tampil bila ada yang perlu diperhatikan; item merah menahan pengiriman ke hotel.
    $pemeriksa = app(\App\Layanan\PemeriksaReservasiHotel::class);
    $masalahHotel = $adaReservasi && ! $dibatalkan
        ? array_values(array_filter($pemeriksa->periksa($pesanan, $hotel, $pesanan->hotel_check_in, $pesanan->hotel_check_out, $pesanan->jumlah_kamar, $pesanan->tipe_kamar), fn (array $item): bool => ! $item['ok']))
        : [];
    $adaMasalahKeras = collect($masalahHotel)->contains(fn (array $item): bool => $item['keras']);

    $ringkasReservasi = $hotel
        ? $hotel->nama.' · '.$pesanan->jumlah_kamar.' kamar'.($pesanan->tipe_kamar ? ' '.$pesanan->tipe_kamar : '')
            .($pesanan->hotel_check_in && $pesanan->hotel_check_out ? ' · '.$pesanan->hotel_check_in->translatedFormat('d M').'–'.$pesanan->hotel_check_out->translatedFormat('d M Y') : '')
        : null;
@endphp

<section id="alur-hotel-{{ $pesanan->id }}" class="mb-4 scroll-mt-24" aria-labelledby="alur-hotel-judul-{{ $pesanan->id }}">
    <h3 id="alur-hotel-judul-{{ $pesanan->id }}" class="sr-only">Tahap reservasi hotel</h3>

    @if ($dibatalkan)
        <x-admin.peringatan type="warning" class="mb-3">
            Reservasi hotel dibatalkan, jadi tahap berikutnya tidak berlaku. Draft pembatalan ke hotel ada di kartu Pesan WhatsApp.
            {{ $adaFormulir ? 'Pilih hotel lagi di bawah bila ingin memesan ulang.' : 'Pilih hotel lagi di detail pesanan bila ingin memesan ulang.' }}
        </x-admin.peringatan>
        @if ($adaFormulir)
            {{ $formulir }}
        @endif
    @else
        @if ($berikutnya)
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2 rounded-xl border border-hijau-aksi/40 bg-page px-4 py-3">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-hijau-aksi">Tahap berikutnya</p>
                    <p class="text-sm font-semibold text-ink">{{ $berikutnya['nomor'] }}. {{ $berikutnya['judul'] }}</p>
                </div>
                <span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-semibold text-muted">{{ $ringkasan['selesai'] }} dari {{ $ringkasan['total'] }} tahap</span>
            </div>
        @else
            <div class="mb-3 flex items-center gap-2 rounded-xl border border-status-completed-teks/30 bg-status-completed-bg px-4 py-3 text-sm font-semibold text-status-completed-teks">
                <span class="material-symbols-outlined text-lg" aria-hidden="true">task_alt</span>
                Semua tahap reservasi hotel sudah selesai ({{ $ringkasan['selesai'] }} dari {{ $ringkasan['total'] }}).
            </div>
        @endif

        <ol class="space-y-2">
            @foreach ($tahap as $item)
                @php
                    $kunci = $item['kunci'];
                    $status = $item['status'];
                @endphp
                <li @class([
                    'rounded-xl border p-3',
                    'border-hijau-aksi/50 bg-page' => $status === 'berikutnya',
                    'border-surface-border bg-white' => $status === 'selesai',
                    'border-surface-border bg-white opacity-60' => $status === 'menunggu',
                ])>
                    <div class="flex items-start gap-3">
                        <span @class([
                            'mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[11px] font-semibold',
                            'bg-status-completed-teks text-white' => $status === 'selesai',
                            'bg-hijau-aksi text-white' => $status === 'berikutnya',
                            'bg-page text-muted' => $status === 'menunggu',
                        ]) aria-hidden="true">
                            @if ($status === 'selesai')
                                <span class="material-symbols-outlined text-sm">check</span>
                            @else
                                {{ $item['nomor'] }}
                            @endif
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm font-semibold text-ink">{{ $item['judul'] }}</p>
                                @if ($status === 'selesai')
                                    <span class="text-[11px] font-semibold text-status-completed-teks">Selesai{{ $item['waktu'] ? ' · '.$item['waktu']->translatedFormat('d M Y, H:i') : '' }}</span>
                                @elseif ($status === 'berikutnya')
                                    <span class="rounded-full bg-hijau-aksi/10 px-2 py-0.5 text-[11px] font-semibold text-hijau-aksi">Sekarang</span>
                                @endif
                            </div>

                            {{-- Tahap selesai: satu baris ringkas, plus jalan untuk mengoreksi --}}
                            @if ($status === 'selesai')
                                <p class="mt-0.5 text-xs text-muted">
                                    @switch ($kunci)
                                        @case ('pesan')
                                            {{ $ringkasReservasi }}
                                            @break
                                        @case ('setuju')
                                            Kode booking: <strong class="font-semibold text-ink">{{ $pesanan->kode_booking_hotel ?: '—' }}</strong>
                                            @break
                                        @case ('bayar')
                                            Transfer {{ FormatRupiah::dari($pesanan->bayar_hotel_nominal) }} pada {{ $pesanan->bayar_hotel_tanggal?->translatedFormat('d M Y') }}, sudah diterima hotel.
                                            @if ($pesanan->bayar_hotel_bukti)
                                                <a href="{{ route('admin.pesanan.hotel.bukti.lihat', $pesanan) }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-hijau-aksi hover:underline">Lihat bukti ({{ strtoupper($extBukti) }})</a>
                                            @endif
                                            @break
                                        @case ('kabari_tamu')
                                            Info kamar sudah dikirim ke tamu.
                                            @break
                                    @endswitch
                                </p>

                                @php
                                    $tandaBisaDibatalkan = match ($kunci) {
                                        'pesan' => ! $terkonfirmasi && $pesanan->permintaan_hotel_dikirim_at ? 'permintaan' : null,
                                        'bayar' => 'konfirmasi_bayar',
                                        'kabari_tamu' => 'kabari_tamu',
                                        default => null,
                                    };
                                @endphp
                                @if (! $terkunci && $tandaBisaDibatalkan)
                                    <form method="POST" action="{{ route('admin.pesanan.hotel.langkah.batal', [$pesanan, $tandaBisaDibatalkan]) }}" class="mt-1">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-[11px] font-medium text-muted underline hover:text-ink">Batalkan tanda</button>
                                    </form>
                                @endif
                                @if ($kunci === 'pesan' && $adaFormulir)
                                    <details class="mt-2 rounded-lg border border-surface-border bg-white" @if ($galatReservasi) open @endif>
                                        <summary class="cursor-pointer list-none px-3 py-2 text-xs font-semibold text-ink">Ubah reservasi (tanggal, kamar, atau ganti hotel)</summary>
                                        <div class="border-t border-surface-border p-3">{{ $formulir }}</div>
                                    </details>
                                @endif
                            @endif

                            {{-- Tahap yang sedang dikerjakan: hanya di sini ada isian dan tombol --}}
                            @if ($status === 'berikutnya')
                                <p class="text-xs text-muted">{{ $item['keterangan'] }}</p>

                                @if ($kunci === 'pesan')
                                    @if (! $adaReservasi)
                                        @if ($adaFormulir)
                                            <div class="mt-3">{{ $formulir }}</div>
                                        @else
                                            <p class="mt-2 text-xs text-ink">Hotel belum dipilih. Pilih hotel di detail pesanan.</p>
                                        @endif
                                    @else
                                        <p class="mt-2 text-xs text-ink">{{ $ringkasReservasi }}</p>

                                        @if ($masalahHotel !== [])
                                            <div class="mt-2.5 rounded-lg border border-surface-border bg-white p-3">
                                                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted">Periksa sebelum kirim</p>
                                                <ul class="space-y-1.5">
                                                    @foreach ($masalahHotel as $periksa)
                                                        <li class="flex items-start gap-2 text-xs">
                                                            <span @class(['material-symbols-outlined text-base', 'text-status-dibatalkan-teks' => $periksa['keras'], 'text-status-payment-teks' => ! $periksa['keras']]) aria-hidden="true">{{ $periksa['keras'] ? 'cancel' : 'warning' }}</span>
                                                            <span><span class="font-semibold text-ink">{{ $periksa['label'] }}</span> <span class="text-muted">{{ $periksa['keterangan'] }}</span></span>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif

                                        @unless ($terkunci)
                                            <x-admin.pesanan.kirim-whatsapp
                                                class="mt-2.5"
                                                :aksi="route('admin.pesanan.hotel.langkah.tandai', [$pesanan, 'permintaan'])"
                                                :teks="$pesan->permintaanReservasiHotel($pesanan)"
                                                :nomor="$hotel?->whatsapp"
                                                label="Kirim permintaan ke hotel"
                                                :tutup="$adaMasalahKeras ? 'Perbaiki item merah pada daftar Periksa sebelum kirim dulu.' : null"
                                            />
                                        @endunless

                                        @if ($adaFormulir)
                                            <details class="mt-2 rounded-lg border border-surface-border bg-white" @if ($galatReservasi) open @endif>
                                                <summary class="cursor-pointer list-none px-3 py-2 text-xs font-semibold text-ink">Ubah reservasi (tanggal, kamar, atau ganti hotel)</summary>
                                                <div class="border-t border-surface-border p-3">{{ $formulir }}</div>
                                            </details>
                                        @endif
                                    @endif
                                @elseif ($kunci === 'setuju' && ! $terkunci)
                                    <form method="POST" action="{{ route('admin.pesanan.hotel.konfirmasi', $pesanan) }}" class="mt-2.5 flex flex-wrap items-start gap-2">
                                        @csrf
                                        @method('PUT')
                                        <input type="text" name="kode_booking_hotel" value="{{ old('kode_booking_hotel', $pesanan->kode_booking_hotel) }}" maxlength="100" required placeholder="Kode booking atau nama petugas hotel" aria-label="Kode booking atau nama petugas hotel" class="min-w-[14rem] flex-1 rounded-lg border border-surface-border bg-white px-3.5 py-2 text-sm text-ink placeholder:text-muted focus:border-hijau-aksi focus:outline-none focus:ring-1 focus:ring-hijau-aksi">
                                        <x-admin.tombol type="submit" size="sm" icon="check_circle">Hotel sudah setuju</x-admin.tombol>
                                    </form>
                                    @error('kode_booking_hotel') <p class="mt-1.5 text-xs text-status-dibatalkan-teks">{{ $message }}</p> @enderror
                                @elseif ($kunci === 'bayar')
                                    <ol class="mt-2.5 space-y-2">
                                        @foreach ($alur->langkahBayar($pesanan) as $sub)
                                            <li class="rounded-lg border border-surface-border bg-white p-2.5">
                                                <p @class(['flex items-center gap-1.5 text-xs font-semibold', 'text-ink' => $sub['status'] !== 'menunggu', 'text-muted' => $sub['status'] === 'menunggu'])>
                                                    <span @class([
                                                        'material-symbols-outlined text-base',
                                                        'text-status-completed-teks' => $sub['status'] === 'selesai',
                                                        'text-hijau-aksi' => $sub['status'] === 'berikutnya',
                                                        'text-muted' => $sub['status'] === 'menunggu',
                                                    ]) aria-hidden="true">{{ $sub['status'] === 'selesai' ? 'check_circle' : ($sub['status'] === 'berikutnya' ? 'arrow_forward' : 'schedule') }}</span>
                                                    {{ $sub['judul'] }}
                                                </p>

                                                @if ($sub['kunci'] === 'transfer')
                                                    @if ($sub['status'] === 'selesai')
                                                        <p class="mt-1 text-xs text-ink">
                                                            {{ FormatRupiah::dari($pesanan->bayar_hotel_nominal) }} pada {{ $pesanan->bayar_hotel_tanggal->translatedFormat('d F Y') }}.
                                                            @if ($pesanan->bayar_hotel_bukti)
                                                                <a href="{{ route('admin.pesanan.hotel.bukti.lihat', $pesanan) }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-hijau-aksi hover:underline">Lihat bukti ({{ strtoupper($extBukti) }})</a>
                                                            @else
                                                                <span class="text-status-payment-teks">Bukti transfer belum diunggah.</span>
                                                            @endif
                                                        </p>
                                                    @endif
                                                    @unless ($terkunci)
                                                        @php
                                                            $galat = (string) old('_pesanan_hotel') === (string) $pesanan->id;
                                                            $kelasInput = 'w-full rounded-lg border bg-white px-3.5 py-2.5 text-sm text-ink placeholder:text-muted focus:outline-none focus:ring-1 ';
                                                        @endphp
                                                        <details class="mt-2 rounded-lg border border-surface-border" @if ($sub['status'] === 'berikutnya' || ($galat && $errors->hasAny(['bayar_hotel_nominal', 'bayar_hotel_tanggal', 'bayar_hotel_bukti']))) open @endif>
                                                            <summary class="cursor-pointer list-none px-3 py-2 text-xs font-semibold text-ink">{{ $sub['status'] === 'selesai' ? 'Ubah catatan transfer' : 'Isi catatan transfer' }}</summary>
                                                            <form method="POST" action="{{ route('admin.pesanan.hotel.pembayaran.simpan', $pesanan) }}" enctype="multipart/form-data" class="grid gap-3 border-t border-surface-border p-3 sm:grid-cols-2">
                                                                @csrf
                                                                @method('PUT')
                                                                <input type="hidden" name="_pesanan_hotel" value="{{ $pesanan->id }}">
                                                                <div>
                                                                    <x-admin.formulir.label :for="'bayar-nominal-'.$pesanan->id" wajib>Nominal transfer (Rp)</x-admin.formulir.label>
                                                                    <x-admin.formulir.input-rupiah
                                                                        name="bayar_hotel_nominal"
                                                                        :id="'bayar-nominal-'.$pesanan->id"
                                                                        :value="$galat ? old('bayar_hotel_nominal') : $pesanan->bayar_hotel_nominal"
                                                                        nullable
                                                                        :invalid="$galat && $errors->has('bayar_hotel_nominal')"
                                                                        required
                                                                        aria-required="true"
                                                                        :placeholder="$perkiraanBayar ? 'Perkiraan '.number_format($perkiraanBayar, 0, ',', '.').' (tarif mulai × kamar × malam)' : 'Angka saja, contoh: 1.500.000'"
                                                                    />
                                                                    @if ($galat) @error('bayar_hotel_nominal') <p class="mt-1.5 text-xs text-status-dibatalkan-teks">{{ $message }}</p> @enderror @endif
                                                                </div>
                                                                <div>
                                                                    <x-admin.formulir.label :for="'bayar-tanggal-'.$pesanan->id" wajib>Tanggal transfer</x-admin.formulir.label>
                                                                    <input id="bayar-tanggal-{{ $pesanan->id }}" type="date" name="bayar_hotel_tanggal" max="{{ now()->toDateString() }}" required aria-required="true"
                                                                        value="{{ $galat ? old('bayar_hotel_tanggal') : ($pesanan->bayar_hotel_tanggal?->toDateString() ?? now()->toDateString()) }}"
                                                                        class="{{ $kelasInput }}{{ $galat && $errors->has('bayar_hotel_tanggal') ? 'border-status-dibatalkan-teks' : 'border-surface-border focus:border-hijau-aksi focus:ring-hijau-aksi' }}">
                                                                    @if ($galat) @error('bayar_hotel_tanggal') <p class="mt-1.5 text-xs text-status-dibatalkan-teks">{{ $message }}</p> @enderror @endif
                                                                </div>
                                                                <div class="sm:col-span-2">
                                                                    <x-admin.unggah-berkas name="bayar_hotel_bukti" accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf" keterangan="Bukti transfer: JPG/PNG/WEBP/PDF, maksimal 5 MB" maksimal-mb="5" pratinjau-gambar />
                                                                    @if ($galat) @error('bayar_hotel_bukti') <p class="mt-1.5 text-xs text-status-dibatalkan-teks">{{ $message }}</p> @enderror @endif
                                                                </div>
                                                                <div class="flex justify-end sm:col-span-2">
                                                                    <x-admin.tombol type="submit" icon="save">Simpan transfer</x-admin.tombol>
                                                                </div>
                                                            </form>
                                                        </details>
                                                    @endunless
                                                @elseif ($sub['kunci'] === 'bukti' && ! $terkunci)
                                                    @if ($sub['status'] === 'berikutnya')
                                                        <x-admin.pesanan.kirim-whatsapp
                                                            class="mt-2"
                                                            :aksi="route('admin.pesanan.hotel.langkah.tandai', [$pesanan, 'bukti'])"
                                                            :teks="$pesan->buktiTransferKeHotel($pesanan)"
                                                            :nomor="$hotel?->whatsapp"
                                                            label="Kirim bukti ke hotel"
                                                        />
                                                        <p class="mt-1 text-[11px] text-muted">Lampirkan berkas bukti transfer di WhatsApp setelah pesan terbuka.</p>
                                                    @elseif ($sub['status'] === 'selesai')
                                                        <form method="POST" action="{{ route('admin.pesanan.hotel.langkah.batal', [$pesanan, 'bukti']) }}" class="mt-1">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-[11px] font-medium text-muted underline hover:text-ink">Batalkan tanda</button>
                                                        </form>
                                                    @endif
                                                @elseif ($sub['kunci'] === 'konfirmasi_bayar' && $sub['status'] === 'berikutnya' && ! $terkunci)
                                                    <form method="POST" action="{{ route('admin.pesanan.hotel.langkah.tandai', [$pesanan, 'konfirmasi_bayar']) }}" class="mt-2">
                                                        @csrf
                                                        @method('PATCH')
                                                        <x-admin.tombol type="submit" size="sm" icon="done">Hotel sudah terima pembayaran</x-admin.tombol>
                                                    </form>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ol>
                                @elseif ($kunci === 'kabari_tamu' && ! $terkunci)
                                    <x-admin.pesanan.kirim-whatsapp
                                        class="mt-2.5"
                                        :aksi="route('admin.pesanan.hotel.langkah.tandai', [$pesanan, 'kabari_tamu'])"
                                        :teks="$pesanan->status_penginapan === StatusPenginapan::Diurus ? $pesan->infoPenginapanAllIn($pesanan) : $pesan->konfirmasiHotelKeTamu($pesanan)"
                                        :nomor="$pesanan->whatsapp"
                                        penerima="tamu"
                                        label="Kirim info kamar ke tamu"
                                    />
                                @endif
                            @endif
                        </div>
                    </div>
                </li>
            @endforeach
        </ol>
    @endif
</section>
