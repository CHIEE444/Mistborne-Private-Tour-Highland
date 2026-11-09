@props(['pesanan', 'chargeGantiJadwal' => 0, 'dariPermintaan' => false])

@php
    $layanan = app(\App\Layanan\PesanWhatsAppLayanan::class);
    $nomor = $pesanan->whatsapp;

    $perubahanData = session('perubahan_data');
    $pesanTab = $perubahanData ? [
        'perubahan' => ['label' => 'Konfirmasi Perubahan Data', 'teks' => $layanan->perubahanData($pesanan, $perubahanData)],
    ] : [];
    $perubahanUnit = session('perubahan_unit');
    if ($perubahanUnit) {
        $pesanTab['unit'] = ['label' => 'Kendaraan Siap (Unit untuk Tamu)', 'teks' => $layanan->perubahanUnit($pesanan, $perubahanUnit['baru'])];
    }
    $pesanTab += $dariPermintaan ? [
        'deal' => ['label' => 'Pesanan Disetujui', 'teks' => $layanan->pesananDisetujui($pesanan)],
    ] : [];
    if (in_array($pesanan->status_penginapan, [\App\Enums\StatusPenginapan::Belum, \App\Enums\StatusPenginapan::Dipesankan], true) && blank($pesanan->nama_hotel)) {
        $pesanTab['penginapan'] = ['label' => 'Rekomendasi Penginapan', 'teks' => $layanan->rekomendasiPenginapan($pesanan)];
    }
    if ($pesanan->status_penginapan === \App\Enums\StatusPenginapan::Diurus) {
        $pesanTab['allin'] = ['label' => 'Info Penginapan All-inclusive', 'teks' => $layanan->infoPenginapanAllIn($pesanan)];
    }
    // Draft reservasi hotel ikut di kartu ini supaya semua pesan WhatsApp ada di satu tempat.
    $kategoriHotel = [];
    if ($pesanan->hotel_id !== null && $pesanan->hotel) {
        $hotelPesanan = $pesanan->hotel;
        $statusHotel = $pesanan->status_reservasi_hotel;
        $periksaHotel = app(\App\Layanan\PemeriksaReservasiHotel::class);
        $hotelBermasalah = $periksaHotel->kegagalanKeras($periksaHotel->periksa($pesanan, $hotelPesanan, $pesanan->hotel_check_in, $pesanan->hotel_check_out, $pesanan->jumlah_kamar, $pesanan->tipe_kamar)) !== [];
        $alasanTutup = $hotelBermasalah ? 'Perbaiki item merah pada daftar "Periksa sebelum kirim" dulu.' : (blank($hotelPesanan->whatsapp) ? 'Nomor WhatsApp hotel belum diisi di menu Hotel.' : null);

        if ($statusHotel !== \App\Enums\StatusReservasiHotel::Dibatalkan) {
            $kategoriHotel['hotel_minta'] = ['label' => 'Permintaan reservasi ke hotel', 'teks' => $layanan->permintaanReservasiHotel($pesanan), 'nomor' => $hotelPesanan->whatsapp, 'penerima' => 'Hotel', 'tutup' => $alasanTutup, 'ikon' => 'storefront'];
        }
        if ($statusHotel === \App\Enums\StatusReservasiHotel::Terkonfirmasi && $pesanan->status_penginapan === \App\Enums\StatusPenginapan::Diurus && $pesanan->bayar_hotel_nominal !== null) {
            $kategoriHotel['hotel_bukti'] = ['label' => 'Bukti transfer ke hotel', 'teks' => $layanan->buktiTransferKeHotel($pesanan), 'nomor' => $hotelPesanan->whatsapp, 'penerima' => 'Hotel', 'tutup' => blank($hotelPesanan->whatsapp) ? 'Nomor WhatsApp hotel belum diisi di menu Hotel.' : null, 'ikon' => 'receipt_long'];
        }
        if ($statusHotel === \App\Enums\StatusReservasiHotel::Terkonfirmasi) {
            $kategoriHotel['hotel_ulang'] = ['label' => 'Konfirmasi ulang ke hotel (menjelang menginap)', 'teks' => $layanan->konfirmasiUlangKeHotel($pesanan), 'nomor' => $hotelPesanan->whatsapp, 'penerima' => 'Hotel', 'tutup' => blank($hotelPesanan->whatsapp) ? 'Nomor WhatsApp hotel belum diisi di menu Hotel.' : null, 'ikon' => 'event_available'];
        }
        if ($statusHotel === \App\Enums\StatusReservasiHotel::Terkonfirmasi && $pesanan->status_penginapan !== \App\Enums\StatusPenginapan::Diurus) {
            $kategoriHotel['hotel_tamu'] = ['label' => 'Konfirmasi hotel ke tamu (jam check-in & check-out)', 'teks' => $layanan->konfirmasiHotelKeTamu($pesanan), 'ikon' => 'hotel'];
        }
        if ($statusHotel !== \App\Enums\StatusReservasiHotel::Dibatalkan) {
            $kategoriHotel['hotel_batal'] = ['label' => 'Pembatalan reservasi ke hotel', 'teks' => $layanan->pembatalanReservasiHotel($pesanan), 'nomor' => $hotelPesanan->whatsapp, 'penerima' => 'Hotel', 'tutup' => blank($hotelPesanan->whatsapp) ? 'Nomor WhatsApp hotel belum diisi di menu Hotel.' : null, 'ikon' => 'cancel'];
        }
    }
    $pesanTab += [
        'konfirmasi' => ['label' => 'Konfirmasi', 'teks' => $layanan->konfirmasiBayar($pesanan)],
        'h7' => ['label' => 'Pengingat H-7', 'teks' => $layanan->pengingatH7($pesanan)],
        'h3' => ['label' => 'Pengingat H-3', 'teks' => $layanan->pengingatH3($pesanan)],
        'h2' => ['label' => 'Pengingat H-2', 'teks' => $layanan->pengingatH2($pesanan)],
        'batal' => ['label' => 'Aturan Batal', 'teks' => $layanan->aturanPembatalan($pesanan)],
        'jadwal' => ['label' => 'Ganti Jadwal', 'teks' => $layanan->gantiJadwal($pesanan, $chargeGantiJadwal)],
    ];
    // Pesan pertama setelah pesanan dibuat (disetujui + info penginapan) dipisah dari Konfirmasi supaya urutan kirimnya jelas bagi admin.
    $kategoriPesan = [
        'Pesan Awal ke Tamu' => collect($pesanTab)->only(['deal', 'penginapan', 'allin'])->all(),
        'Pesan Pesanan & Perjalanan' => collect($pesanTab)->only(['perubahan', 'unit', 'konfirmasi', 'h7', 'h3', 'h2'])->all(),
        'Reservasi Hotel' => $kategoriHotel,
        'Kebijakan & Perubahan Pesanan' => collect($pesanTab)->only(['batal', 'jadwal'])->all(),
    ];
@endphp

<x-admin.kartu>
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-muted">Pesan WhatsApp</h2>
        <span class="rounded-full bg-page px-2 py-0.5 text-[11px] text-muted">Pesan Siap Kirim · Manual</span>
    </div>

    <div class="space-y-5">
        @foreach ($kategoriPesan as $judulKategori => $daftarPesan)
            @if (count($daftarPesan))
                <section>
                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted">{{ $judulKategori }}</h3>
                    <div class="space-y-2">
        @foreach ($daftarPesan as $kunci => $data)
            @php
                $ikonTab = $data['ikon'] ?? match ($kunci) {
                    'deal' => 'check_circle',
                    'perubahan' => 'edit_note',
                    'unit' => 'directions_car',
                    'penginapan', 'allin' => 'hotel',
                    'konfirmasi' => 'receipt_long',
                    'h7' => 'event_available',
                    'h3' => 'checklist',
                    'h2' => 'schedule',
                    'batal' => 'rule',
                    'jadwal' => 'event_repeat',
                    default => 'chat',
                };
            @endphp
            <details @if (($kunci === 'deal' && session('pesanan_baru_dari_permintaan')) || in_array($kunci, ['perubahan', 'unit'], true)) open @endif class="group rounded-xl border border-surface-border bg-white transition-colors open:border-hijau-aksi/50 open:bg-page/40">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 rounded-xl px-3.5 py-3 text-left text-sm font-semibold text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-hijau-aksi focus-visible:ring-offset-2 [&::-webkit-details-marker]:hidden">
                    <span class="flex items-center gap-3">
                        <span @class([
                            'material-symbols-outlined text-[19px] transition-colors',
                            'text-hijau-aksi' => in_array($kunci, ['h7', 'h3', 'h2'], true),
                            'text-current' => ! in_array($kunci, ['h7', 'h3', 'h2'], true),
                        ]) aria-hidden="true">{{ $ikonTab }}</span>
                        {{ $data['label'] }}
                    </span>
                    <span class="material-symbols-outlined text-lg transition-transform group-open:rotate-90" aria-hidden="true">chevron_right</span>
                </summary>

                <div class="border-t border-surface-border p-3.5">
                    <x-admin.pratinjau-whatsapp :pesan="$data['teks']" :nomor-tujuan="$data['nomor'] ?? $nomor" />

                    <div class="mt-3 flex flex-wrap gap-2">
                        <x-admin.tombol
                            type="button"
                            variant="secondary"
                            icon="content_copy"
                            x-on:click="salinTeks({{ \Illuminate\Support\Js::from($data['teks']) }})"
                        >
                            Salin Pesan
                        </x-admin.tombol>

                        @if (! empty($data['tutup']))
                            <x-admin.tombol type="button" disabled icon="send" title="{{ $data['tutup'] }}">Buka WhatsApp {{ $data['penerima'] ?? '' }}</x-admin.tombol>
                            <p class="basis-full text-xs text-status-dibatalkan-teks">{{ $data['tutup'] }}</p>
                        @else
                            <x-admin.tombol
                                as="a"
                                href="{{ app(\App\Kontrak\PengirimWhatsApp::class)->buatTautan($data['nomor'] ?? $nomor, $data['teks']) }}"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                Buka WhatsApp{{ isset($data['penerima']) ? ' '.$data['penerima'] : '' }}
                            </x-admin.tombol>
                        @endif
                    </div>
                </div>
            </details>
        @endforeach
                    </div>
                </section>
            @endif
        @endforeach
    </div>
</x-admin.kartu>
