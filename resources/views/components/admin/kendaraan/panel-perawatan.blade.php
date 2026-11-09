@props(['kendaraan', 'ringkasan'])

@php
    $warnaTingkat = [
        'ok' => 'bg-status-completed-bg text-status-completed-teks',
        'perhatian' => 'bg-status-payment-bg text-status-payment-teks',
        'bahaya' => 'bg-status-dibatalkan-bg text-status-dibatalkan-teks',
    ];
    $dokumen = [
        ['icon' => 'badge', 'nama' => 'STNK', 'hasil' => $ringkasan['dokumen']['stnk']],
        ['icon' => 'receipt_long', 'nama' => 'Pajak kendaraan', 'hasil' => $ringkasan['dokumen']['pajak']],
    ];
@endphp

<x-admin.kartu class="flex flex-col gap-6">
    <h2 class="flex items-center gap-2 text-base font-semibold text-ink">
        <span class="material-symbols-outlined text-xl text-muted" aria-hidden="true">build</span>
        Perawatan &amp; Dokumen
    </h2>

    <section aria-labelledby="judul-dokumen">
        <h3 id="judul-dokumen" class="mb-3 text-sm font-semibold text-ink">Dokumen</h3>
        <ul class="space-y-2">
            @foreach ($dokumen as $butir)
                <li class="flex items-center gap-3 rounded-xl border border-surface-border px-3.5 py-3">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-xl {{ $warnaTingkat[$butir['hasil']['tingkat']] }}">
                        <span class="material-symbols-outlined text-xl" aria-hidden="true">{{ $butir['icon'] }}</span>
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-ink">{{ $butir['nama'] }}</p>
                        <p class="text-xs text-muted">{{ $butir['hasil']['keterangan'] }}</p>
                    </div>
                </li>
            @endforeach
        </ul>
    </section>

    <section aria-labelledby="judul-servis-terjadwal">
        <div class="mb-3 flex items-center justify-between gap-3">
            <h3 id="judul-servis-terjadwal" class="text-sm font-semibold text-ink">Servis terjadwal</h3>
            <button type="button" class="inline-flex items-center gap-1 text-sm font-medium text-hijau-aksi hover:underline" x-on:click="window.dispatchEvent(new CustomEvent('buka-modal', { detail: { nama: 'servis-tambah' } }))">
                <span class="material-symbols-outlined text-lg" aria-hidden="true">add</span>
                Tambah jadwal
            </button>
        </div>

        @forelse ($ringkasan['servisTerjadwal'] as $servis)
            @php $terlambat = $servis->terlambat($kendaraan->odometer); @endphp
            <div @class([
                'mb-2 flex items-center gap-3 rounded-xl border px-3.5 py-3',
                'border-status-dibatalkan-teks/40 bg-status-dibatalkan-bg/40' => $terlambat,
                'border-surface-border' => ! $terlambat,
            ])>
                <span class="material-symbols-outlined text-xl text-muted" aria-hidden="true">handyman</span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-ink">{{ $servis->jenis }}</p>
                    <p @class(['text-xs', 'font-semibold text-status-dibatalkan-teks' => $terlambat, 'text-muted' => ! $terlambat])>
                        {{ $servis->keteranganJatuhTempo($kendaraan->odometer) }}
                    </p>
                </div>
                <button
                    type="button"
                    class="shrink-0 rounded-full border border-surface-border px-3 py-1 text-xs font-semibold text-ink transition-colors hover:bg-page"
                    x-on:click="window.dispatchEvent(new CustomEvent('siapkan-servis', { detail: @js([
                        'aksi' => route('admin.kendaraan.servis.perbarui', [$kendaraan, $servis]),
                        'jenis' => $servis->jenis,
                        'catatan' => $servis->catatan,
                        'odometer' => $kendaraan->odometer,
                    ]) })); window.dispatchEvent(new CustomEvent('buka-modal', { detail: { nama: 'servis-selesai' } }))"
                >
                    Tandai selesai
                </button>
                <form method="POST" action="{{ route('admin.kendaraan.servis.hapus', [$kendaraan, $servis]) }}" onsubmit="return confirm('Hapus jadwal servis ini?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="flex size-8 items-center justify-center rounded-full text-muted transition-colors hover:bg-status-dibatalkan-bg hover:text-status-dibatalkan-teks" aria-label="Hapus jadwal {{ $servis->jenis }}">
                        <span class="material-symbols-outlined text-lg" aria-hidden="true">delete</span>
                    </button>
                </form>
            </div>
        @empty
            <p class="rounded-xl border border-dashed border-surface-border px-3.5 py-4 text-center text-sm text-muted">
                Belum ada servis terjadwal. Tambahkan jadwal agar kendaraan tidak lupa diservis.
            </p>
        @endforelse
    </section>

    <section aria-labelledby="judul-riwayat-servis">
        <h3 id="judul-riwayat-servis" class="mb-3 text-sm font-semibold text-ink">Riwayat servis</h3>

        @forelse ($ringkasan['riwayatServis'] as $servis)
            <div class="flex items-start gap-3 border-t border-surface-border py-3 first:border-t-0 first:pt-0">
                <p class="w-24 shrink-0 text-xs text-muted">{{ $servis->tanggal_servis?->translatedFormat('d M Y') }}</p>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-ink">{{ $servis->jenis }}</p>
                    <p class="text-xs text-muted">
                        {{ collect([
                            $servis->odometer_servis !== null ? number_format($servis->odometer_servis, 0, ',', '.').' km' : null,
                            \App\Bantuan\FormatRupiah::dari($servis->biaya),
                        ])->filter()->implode(' · ') }}
                    </p>
                    @if ($servis->catatan)
                        <p class="mt-0.5 text-xs text-muted">{{ $servis->catatan }}</p>
                    @endif
                </div>
                <form method="POST" action="{{ route('admin.kendaraan.servis.hapus', [$kendaraan, $servis]) }}" onsubmit="return confirm('Hapus catatan servis ini?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="flex size-8 items-center justify-center rounded-full text-muted transition-colors hover:bg-status-dibatalkan-bg hover:text-status-dibatalkan-teks" aria-label="Hapus riwayat {{ $servis->jenis }}">
                        <span class="material-symbols-outlined text-lg" aria-hidden="true">delete</span>
                    </button>
                </form>
            </div>
        @empty
            <p class="rounded-xl border border-dashed border-surface-border px-3.5 py-4 text-center text-sm text-muted">
                Belum ada riwayat servis. Catatan yang ditandai selesai akan muncul di sini.
            </p>
        @endforelse
    </section>
</x-admin.kartu>
