@php
    $tabStatus = [['nilai' => null, 'label' => 'Semua']];
    foreach (\App\Models\PertanyaanTamu::STATUS as $nilai => $label) {
        $tabStatus[] = ['nilai' => $nilai, 'label' => $label];
    }
@endphp
<x-tata-letak.admin title="Pertanyaan Tamu" group="{{ __('admin.sidebar.grup_operasional') }}">
    <x-admin.kepala-halaman
        title="Pertanyaan Tamu"
        subtitle="Calon tamu yang bertanya ketersediaan atau seputar tour lewat website dan WhatsApp. Belum tentu memesan."
    />

    <x-admin.tab class="mb-6 overflow-x-auto">
        @foreach ($tabStatus as $tab)
            <x-admin.tab-item
                href="{{ route('admin.pertanyaan-tamu.indeks', $tab['nilai'] ? ['status' => $tab['nilai']] : []) }}"
                :active="$statusAktif === $tab['nilai']"
                :badge="$tab['nilai'] ? ($jumlahPerStatus[$tab['nilai']] ?? 0) : $jumlahPerStatus->sum()"
            >
                {{ $tab['label'] }}
            </x-admin.tab-item>
        @endforeach
    </x-admin.tab>

    @if ($pertanyaan->isEmpty())
        <x-admin.keadaan-kosong
            icon="forum"
            judul="Belum ada pertanyaan"
            keterangan="Pertanyaan dari form kontak dan tombol WhatsApp di website akan muncul di sini."
        />
    @else
        <div class="space-y-4">
            @foreach ($pertanyaan as $item)
                @php
                    $nomorTamu = $item->whatsapp;
                    $pesanBalasan = 'Halo Kak'.($item->nama ? ' '.$item->nama : '').", terima kasih sudah menghubungi MISTBORNE 😊\n\nKami bantu cek ".($item->journey ? "journey {$item->journey}" : 'perjalanan yang Kakak tanyakan').($item->tanggal_perjalanan ? ' untuk tanggal '.$item->tanggal_perjalanan->translatedFormat('d F Y') : '').'.';
                    $warnaStatus = match ($item->status) {
                        'baru' => 'bg-amber-100 text-amber-900',
                        'dibalas' => 'bg-status-dihubungi-bg text-status-dihubungi-teks',
                        'jadi_booking' => 'bg-status-completed-bg text-status-completed-teks',
                        default => 'bg-page text-muted',
                    };
                @endphp
                <x-admin.kartu>
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="font-semibold text-ink">{{ $item->nama ?: 'Calon tamu' }}</h2>
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $warnaStatus }}">{{ $item->labelStatus() }}</span>
                            <span class="rounded-full bg-violet-50 px-2.5 py-1 text-xs font-semibold text-violet-700">{{ $item->labelSumber() }}</span>
                        </div>
                        <p class="text-xs text-muted">{{ $item->created_at->timezone(config('app.timezone'))->translatedFormat('d M Y, H:i') }} WIB</p>
                    </div>

                    <dl class="mt-4 grid gap-3 rounded-xl bg-page/70 p-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
                        <div><dt class="text-xs text-muted">Journey</dt><dd class="mt-1 font-medium text-ink">{{ $item->journey ?: '—' }}</dd></div>
                        <div><dt class="text-xs text-muted">Tanggal perjalanan</dt><dd class="mt-1 font-medium text-ink">{{ $item->tanggal_perjalanan?->translatedFormat('d M Y') ?: 'Belum memilih tanggal' }}</dd></div>
                        <div><dt class="text-xs text-muted">Durasi · Jenis</dt><dd class="mt-1 font-medium text-ink">{{ collect([$item->durasi, $item->jenis_perjalanan])->filter()->implode(' · ') ?: '—' }}</dd></div>
                        <div><dt class="text-xs text-muted">Lokasi pick-up</dt><dd class="mt-1 font-medium text-ink">{{ $item->lokasi_pickup ?: '—' }}</dd></div>
                        @if ($nomorTamu)
                            <div><dt class="text-xs text-muted">WhatsApp</dt><dd class="mt-1 font-medium text-ink">{{ $nomorTamu }}</dd></div>
                        @endif
                    </dl>

                    <details class="mt-3 rounded-lg border border-surface-border">
                        <summary class="cursor-pointer px-3 py-2 text-xs font-semibold text-muted">Pesan yang dikirim tamu ke WhatsApp admin</summary>
                        <pre class="whitespace-pre-wrap wrap-anywhere border-t border-surface-border px-3 py-3 font-sans text-sm leading-relaxed text-ink">{{ $item->pesan }}</pre>
                    </details>

                    @if ($item->catatan_admin && $item->status === 'jadi_booking')
                        <p class="mt-3 rounded-lg border border-surface-border p-3 text-sm"><span class="font-semibold text-ink">Catatan:</span> {{ $item->catatan_admin }}</p>
                    @endif

                    <div class="mt-4 space-y-4 border-t border-surface-border pt-4">
                        @if ($item->status === 'jadi_booking')
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-sm text-muted">Sudah dilanjutkan ke form booking.</p>
                                @if ($item->permintaan?->pesanan_id)
                                    <x-admin.tombol as="a" href="{{ route('admin.pesanan.detail', $item->permintaan->pesanan_id) }}" variant="secondary" icon="open_in_new">Buka Pesanan</x-admin.tombol>
                                @else
                                    <x-admin.tombol as="a" href="{{ route('admin.permintaan-pesanan.indeks') }}" variant="secondary" icon="assignment">Lihat Permintaan Form</x-admin.tombol>
                                @endif
                            </div>
                        @else
                            <form method="POST" action="{{ route('admin.pertanyaan-tamu.perbarui', $item) }}" class="flex flex-col gap-2 sm:flex-row sm:items-end">
                                @csrf
                                @method('PATCH')
                                <label class="min-w-0 flex-1 text-xs text-muted">Catatan admin (opsional)
                                    <input name="catatan_admin" maxlength="1000" value="{{ $item->catatan_admin }}" placeholder="Contoh: sudah dibalas, menunggu tamu menentukan tanggal" class="mt-1 w-full rounded-lg border border-surface-border bg-white px-3 py-2 text-sm text-ink" />
                                </label>
                                <x-admin.tombol type="submit" variant="secondary">Simpan catatan</x-admin.tombol>
                            </form>

                            @if ($item->permintaan?->menungguTamu())
                                <p class="rounded-lg bg-page/70 px-3 py-2 text-sm text-muted">Form booking sudah dikirim ke tamu dan belum diisi. Status berubah menjadi "Jadi booking" setelah tamu mengisinya.</p>
                            @endif
                            <div class="flex flex-wrap items-center gap-2">
                                @if ($nomorTamu)
                                    <x-admin.tombol as="a" href="{{ app(\App\Kontrak\PengirimWhatsApp::class)->buatTautan($nomorTamu, $pesanBalasan) }}" target="_blank" rel="noopener noreferrer" variant="secondary" icon="chat">Balas via WhatsApp</x-admin.tombol>
                                @endif
                                <x-admin.tombol type="button" variant="secondary" icon="content_copy" x-on:click="salinTeks({{ \Illuminate\Support\Js::from($item->pesan) }})">Salin Pesan</x-admin.tombol>
                                <x-admin.pesanan.kirim-formulir :pertanyaan-id="$item->id" :nomor="$nomorTamu" label="Lanjutkan ke Form Booking" variant="secondary" />

                                <div class="flex flex-wrap items-center gap-2 sm:ms-auto">
                                    @if ($item->status === 'baru')
                                        <form method="POST" action="{{ route('admin.pertanyaan-tamu.perbarui', $item) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="dibalas" />
                                            <x-admin.tombol type="submit" icon="done_all">Tandai sudah dibalas</x-admin.tombol>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.pertanyaan-tamu.perbarui', $item) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="baru" />
                                            <x-admin.tombol type="submit" variant="secondary" icon="undo">Buka kembali</x-admin.tombol>
                                        </form>
                                    @endif
                                    @if ($item->status !== 'tidak_lanjut')
                                        <form method="POST" action="{{ route('admin.pertanyaan-tamu.perbarui', $item) }}" onsubmit="return confirm('Tandai pertanyaan ini sebagai tidak lanjut?')">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="tidak_lanjut" />
                                            <x-admin.tombol type="submit" variant="danger-outline">Tidak lanjut</x-admin.tombol>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </x-admin.kartu>
            @endforeach
        </div>

        <x-admin.paginasi :paginator="$pertanyaan" class="mt-6" />
    @endif
</x-tata-letak.admin>
