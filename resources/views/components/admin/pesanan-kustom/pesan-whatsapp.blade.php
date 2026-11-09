@props(['pesananKustom'])

{{-- Semua draft WhatsApp pesanan kustom di satu kartu, dikelompokkan seperti kartu Pesan WhatsApp pada pesanan biasa.
     Di sini hanya pratinjau, salin, dan buka WhatsApp; penanda tahap ada di tombol hijau Aksi Cepat. --}}
@php
    $layanan = app(\App\Layanan\PesanWhatsAppLayanan::class);
    $pengirim = app(\App\Kontrak\PengirimWhatsApp::class);

    $pesanAwal = ['pembuka' => ['label' => 'Hubungi tamu (pesan pembuka)', 'ikon' => 'chat', 'teks' => $layanan->pembukaTripKustom($pesananKustom)]];
    if ($pesananKustom->status_penginapan === \App\Enums\StatusPenginapan::Belum && blank($pesananKustom->nama_hotel)) {
        $pesanAwal['penginapan'] = ['label' => 'Rekomendasi Penginapan', 'ikon' => 'hotel', 'teks' => $layanan->rekomendasiPenginapan($pesananKustom)];
    }
    $kategori = [
        'Pesan Awal ke Tamu' => $pesanAwal,
        // Draf penawaran kedaluwarsa tidak ditawarkan: tamu tidak boleh menerima harga yang masa berlakunya sudah lewat.
        'Penawaran' => $pesananKustom->penawaranLengkap() && ! ($pesananKustom->batas_penawaran?->isBefore(today()) ?? false)
            ? ['penawaran' => ['label' => 'Penawaran trip kustom', 'ikon' => 'receipt_long', 'teks' => $layanan->penawaranTripKustom($pesananKustom)]]
            : [],
    ];
@endphp

<x-admin.kartu>
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-muted">Pesan WhatsApp</h2>
        <span class="rounded-full bg-page px-2 py-0.5 text-[11px] text-muted">Pesan Siap Kirim · Manual</span>
    </div>

    <div class="space-y-5">
        @foreach ($kategori as $judul => $daftar)
            @if ($daftar !== [])
                <section>
                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted">{{ $judul }}</h3>
                    <div class="space-y-2">
                        @foreach ($daftar as $data)
                            <details class="group rounded-xl border border-surface-border bg-white transition-colors open:border-hijau-aksi/50 open:bg-page/40">
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 rounded-xl px-3.5 py-3 text-left text-sm font-semibold text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-hijau-aksi focus-visible:ring-offset-2 [&::-webkit-details-marker]:hidden">
                                    <span class="flex items-center gap-3">
                                        <span class="material-symbols-outlined text-[19px]" aria-hidden="true">{{ $data['ikon'] }}</span>
                                        {{ $data['label'] }}
                                    </span>
                                    <span class="material-symbols-outlined text-lg transition-transform group-open:rotate-90" aria-hidden="true">chevron_right</span>
                                </summary>
                                <div class="border-t border-surface-border p-3.5">
                                    <x-admin.pratinjau-whatsapp :pesan="$data['teks']" :nomor-tujuan="$pesananKustom->whatsapp" />
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <x-admin.tombol type="button" variant="secondary" icon="content_copy" x-on:click="salinTeks({{ \Illuminate\Support\Js::from($data['teks']) }})">Salin Pesan</x-admin.tombol>
                                        <x-admin.tombol as="a" href="{{ $pengirim->buatTautan($pesananKustom->whatsapp, $data['teks']) }}" target="_blank" rel="noopener noreferrer">Buka WhatsApp</x-admin.tombol>
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
