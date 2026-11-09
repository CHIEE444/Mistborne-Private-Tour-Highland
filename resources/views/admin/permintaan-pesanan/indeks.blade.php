<x-tata-letak.admin title="Permintaan Pesanan" group="Operasional">
    <x-admin.kepala-halaman title="Permintaan Pesanan" subtitle="Periksa ketersediaan tanggal, lakukan follow-up, lalu buat pesanan setelah tamu deal.">
        <x-slot:actions>
            <x-admin.tombol as="a" href="{{ route('admin.pesanan.indeks') }}" variant="secondary" icon="receipt_long">Daftar Pesanan</x-admin.tombol>
        </x-slot:actions>
    </x-admin.kepala-halaman>

    @php
        $jumlahMenunggu = $permintaan->where('status', 'menunggu_tinjauan')->count();
        $jumlahFollowup = $permintaan->where('status', 'ditindaklanjuti')->count();
        $jumlahMenungguTamu = $permintaan->where('status', 'menunggu_tamu')->count();
    @endphp
    <div class="mb-6 grid gap-3 sm:grid-cols-3">
        <x-admin.kartu class="!p-4"><p class="text-xs text-muted">Perlu ditinjau</p><p class="mt-1 text-xl font-semibold text-ink">{{ $jumlahMenunggu }}</p></x-admin.kartu>
        <x-admin.kartu class="!p-4"><p class="text-xs text-muted">Sedang follow-up</p><p class="mt-1 text-xl font-semibold text-ink">{{ $jumlahFollowup }}</p></x-admin.kartu>
        <x-admin.kartu class="!p-4"><p class="text-xs text-muted">Menunggu tamu mengisi</p><p class="mt-1 text-xl font-semibold text-ink">{{ $jumlahMenungguTamu }}</p></x-admin.kartu>
    </div>

    @if ($permintaan->isEmpty())
        <x-admin.keadaan-kosong icon="assignment" judul="Belum ada permintaan pesanan" keterangan="Permintaan akan tampil di sini setelah tamu mengisi formulir yang dikirim admin." />
    @else
        <div class="space-y-4">
            @foreach ($permintaan as $item)
                @php
                    $infoSlot = $ketersediaan[$item->id];
                    $statusLabel = match ($item->status) {
                        'menunggu_tamu' => 'Menunggu tamu mengisi',
                        'menunggu_tinjauan' => 'Perlu ditinjau',
                        'ditindaklanjuti' => 'Sedang follow-up',
                        'disetujui' => 'Sudah menjadi pesanan',
                        'tidak_jadi' => 'Tidak jadi',
                        default => ucfirst($item->status),
                    };
                @endphp
                <x-admin.kartu>
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="font-semibold text-ink">{{ $item->nama_lengkap ?: 'Tamu belum mengisi formulir' }}</h2>
                                <span class="rounded-full bg-page px-2.5 py-1 text-xs font-semibold text-muted">{{ $statusLabel }}</span>
                                @if ($item->dariBookingWebsite())
                                    <span class="rounded-full bg-status-dihubungi-bg px-2.5 py-1 text-xs font-semibold text-status-dihubungi-teks">Dari website</span>
                                @endif
                            </div>
                            <p class="mt-1 text-sm text-muted">WhatsApp: {{ $item->whatsapp }} · Tautan berlaku sampai {{ $item->token_berlaku_sampai->translatedFormat('d M Y, H:i') }} WIB{{ $item->token_berlaku_sampai->isPast() && ! in_array($item->status, ['disetujui', 'tidak_jadi'], true) ? ' (sudah kedaluwarsa)' : '' }}</p>
                        </div>
                        @if ($item->pesanan_id)
                            <x-admin.tombol as="a" href="{{ route('admin.pesanan.detail', $item->pesanan_id) }}" variant="secondary" icon="open_in_new">Buka Pesanan</x-admin.tombol>
                        @elseif (! in_array($item->status, ['disetujui', 'tidak_jadi'], true))
                            <form method="POST" action="{{ route('admin.permintaan-pesanan.kirim-ulang-form', $item) }}">
                                @csrf
                                <x-admin.tombol type="submit" variant="secondary" icon="refresh">Kirim ulang formulir (1 hari)</x-admin.tombol>
                            </form>
                        @endif
                    </div>

                    @if ($item->perjalanan_id && $item->tanggal_trip)
                        <div class="mt-4 grid gap-3 rounded-xl bg-page/70 p-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
                            <div><p class="text-xs text-muted">Journey</p><p class="mt-1 font-medium text-ink">{{ $item->perjalanan?->nama ?: 'Journey tidak tersedia' }}</p></div>
                            @php
                                $durasiPermintaan = max(1, (int) $item->perjalanan?->durasi_hari);
                                $akhirPermintaan = $item->tanggal_trip->copy()->addDays($durasiPermintaan - 1);
                            @endphp
                            <div>
                                <p class="text-xs text-muted">Tanggal perjalanan</p>
                                <p class="mt-1 font-medium text-ink">{{ $item->tanggal_trip->translatedFormat('d M Y') }}@if ($durasiPermintaan > 1) – {{ $akhirPermintaan->translatedFormat('d M Y') }}@endif</p>
                                @if ($durasiPermintaan > 1)<p class="text-xs text-muted">{{ $durasiPermintaan }} hari {{ $durasiPermintaan - 1 }} malam</p>@endif
                            </div>
                            <div><p class="text-xs text-muted">Peserta</p><p class="mt-1 font-medium text-ink">{{ $item->jumlah_wisatawan }} orang</p></div>
                            <div>
                                <p class="text-xs text-muted">Ketersediaan saat ini</p>
                                @if ($infoSlot['tersedia'])
                                    <p class="mt-1 font-semibold text-status-completed-teks">Tersedia · sisa {{ $infoSlot['sisa'] }} slot</p>
                                @else
                                    <p class="mt-1 font-semibold text-status-dibatalkan-teks">{{ $infoSlot['alasan'] }}</p>
                                @endif
                            </div>
                            <div><p class="text-xs text-muted">Penginapan</p><p class="mt-1 font-medium text-ink"><x-admin.penginapan :status="$item->status_penginapan" :nama="$item->nama_hotel" />@if ($item->alamat_hotel)<span class="block text-xs font-normal text-muted">{{ $item->alamat_hotel }}</span>@endif</p></div>
                            @if ($item->kendaraan_pilihan)<div><p class="text-xs text-muted">Mobil destinasi</p><p class="mt-1 font-medium text-ink">{{ $item->kendaraan_pilihan }}</p></div>@endif
                            <div><p class="text-xs text-muted">Titik penjemputan</p><p class="mt-1 font-medium text-ink">{{ $item->lokasi_pickup }}</p></div>
                            <div><p class="text-xs text-muted">Jam jemput</p><p class="mt-1 font-medium text-ink">{{ $item->waktu_pickup ? substr((string) $item->waktu_pickup, 0, 5).' WIB' : 'Belum ditentukan' }}</p></div>
                            @if ($item->paket_opsional)<div><p class="text-xs text-muted">Catatan tambahan</p><p class="mt-1 font-medium text-ink">{{ $item->paket_opsional }}</p></div>@endif
                            @if ($item->permintaan_khusus)<div><p class="text-xs text-muted">Permintaan khusus</p><p class="mt-1 font-medium text-ink">{{ $item->permintaan_khusus }}</p></div>@endif
                        </div>
                        <p class="mt-3 text-xs text-muted">Ketersediaan dapat berubah. Slot baru diperiksa dan ditahan saat admin membuat pesanan setelah deal.</p>
                    @endif

                    @if ($item->status === 'tidak_jadi' && $item->alasan_tidak_jadi)
                        <p class="mt-3 rounded-lg border border-surface-border p-3 text-sm"><span class="font-semibold text-ink">Alasan tidak jadi:</span> {{ $item->alasan_tidak_jadi }}</p>
                    @endif

                    @if ($item->catatan_admin)
                        <p class="mt-3 rounded-lg border border-surface-border p-3 text-sm"><span class="font-semibold text-ink">Catatan follow-up:</span> {{ $item->catatan_admin }}</p>
                    @endif

                    @if (in_array($item->status, ['menunggu_tinjauan', 'ditindaklanjuti'], true))
                        @php
                            $pesanKonfirmasi = app(\App\Layanan\PesanWhatsAppLayanan::class)->konfirmasiPermintaan($item);
                            $pesanPenginapan = $item->status_penginapan === \App\Enums\StatusPenginapan::Belum
                                ? app(\App\Layanan\PesanWhatsAppLayanan::class)->rekomendasiPenginapan($item)
                                : null;
                        @endphp
                        <div class="mt-4 space-y-4 border-t border-surface-border pt-4">
                            <form method="POST" action="{{ route('admin.permintaan-pesanan.follow-up', $item) }}" class="flex flex-col gap-2 sm:flex-row sm:items-end">
                                @csrf
                                <label class="min-w-0 flex-1 text-xs text-muted">Catatan admin (opsional)
                                    <input name="catatan_admin" maxlength="1000" value="{{ $item->catatan_admin }}" placeholder="Contoh: menunggu tamu memilih tanggal lain" class="mt-1 w-full rounded-lg border border-surface-border bg-white px-3 py-2 text-sm text-ink" />
                                </label>
                                <x-admin.tombol type="submit" variant="secondary">Simpan follow-up</x-admin.tombol>
                            </form>

                            <div class="flex flex-wrap items-center gap-2">
                                <x-admin.tombol as="a" href="{{ app(\App\Kontrak\PengirimWhatsApp::class)->buatTautan($item->whatsapp, $pesanKonfirmasi) }}" target="_blank" rel="noopener noreferrer" variant="secondary" icon="chat">Konfirmasi via WhatsApp</x-admin.tombol>
                                <x-admin.tombol type="button" variant="secondary" icon="content_copy" x-on:click="salinTeks({{ \Illuminate\Support\Js::from($pesanKonfirmasi) }})">Salin Pesan</x-admin.tombol>
                                @if ($pesanPenginapan)
                                    <x-admin.tombol as="a" href="{{ app(\App\Kontrak\PengirimWhatsApp::class)->buatTautan($item->whatsapp, $pesanPenginapan) }}" target="_blank" rel="noopener noreferrer" variant="secondary" icon="hotel">Kirim Rekomendasi Penginapan</x-admin.tombol>
                                @endif
                                @if ($infoSlot && $infoSlot['tersedia'])
                                    <form method="POST" action="{{ route('admin.permintaan-pesanan.buat-pesanan', $item) }}">
                                        @csrf
                                        <x-admin.tombol type="submit" icon="check_circle">Deal · Buat Pesanan</x-admin.tombol>
                                    </form>
                                @endif
                                <div class="sm:ms-auto">
                                    <x-admin.tombol type="button" variant="danger" x-on:click="window.dispatchEvent(new CustomEvent('buka-modal', { detail: { nama: 'tidak-jadi-permintaan-{{ $item->id }}' } }))">Tidak jadi</x-admin.tombol>
                                    <x-admin.modal :nama="'tidak-jadi-permintaan-'.$item->id" size="sm">
                                        <x-slot:header>
                                            <p class="font-semibold text-ink">Tutup sebagai tidak jadi</p>
                                            <p class="text-xs text-muted">{{ $item->nama_lengkap ?: $item->whatsapp }}</p>
                                        </x-slot:header>
                                        <form id="form-tidak-jadi-{{ $item->id }}" method="POST" action="{{ route('admin.permintaan-pesanan.tidak-jadi', $item) }}" class="space-y-3">
                                            @csrf
                                            <p class="text-sm leading-6 text-muted">Permintaan ini akan ditutup dan tidak dapat diproses lagi. Alasan membantu mengevaluasi mengapa calon tamu tidak melanjutkan.</p>
                                            <x-admin.formulir.area-teks name="alasan_tidak_jadi" :id-input="'alasan-tidak-jadi-'.$item->id" label="Alasan (opsional)" rows="3" placeholder="Contoh: harga di luar anggaran, tanggal tidak cocok, tidak membalas" />
                                        </form>
                                        <x-slot:footer>
                                            <x-admin.tombol variant="secondary" x-on:click="tutup()">Batal</x-admin.tombol>
                                            <x-admin.tombol type="submit" form="form-tidak-jadi-{{ $item->id }}" variant="danger">Tutup permintaan</x-admin.tombol>
                                        </x-slot:footer>
                                    </x-admin.modal>
                                </div>
                            </div>
                        </div>
                    @elseif ($item->status === 'menunggu_tamu')
                        <p class="mt-4 border-t border-surface-border pt-4 text-sm text-muted">Form sudah dikirim ke tamu. Data belum masuk sampai tamu mengirim formulir.</p>
                    @endif
                </x-admin.kartu>
            @endforeach
        </div>
    @endif
</x-tata-letak.admin>
