@props(['pesanan', 'terkunci' => false])

{{-- Status penugasan guide: hasil email penugasan, lalu konfirmasi guide (dipantau otomatis oleh status-penugasan.js). --}}
@php
    $idFormKirimUlang = 'kirim-ulang-penugasan-'.$pesanan->id;
    $nomorGuide = $pesanan->guide?->whatsapp;
    $tautanWaGuide = filled($nomorGuide)
        ? app(\App\Kontrak\PengirimWhatsApp::class)->buatTautan($nomorGuide, "Halo {$pesanan->nama_guide}, ada penugasan baru untuk Anda: {$pesanan->kode} ({$pesanan->nama_perjalanan}) tanggal {$pesanan->tanggal_trip->translatedFormat('d F Y')}. Mohon buka Panel Guide untuk menerima atau menolak tugas ini.")
        : null;
@endphp

<section
    x-data="statusPenugasan(@js($pesanan->ringkasanPenugasanGuide()), @js(route('admin.pesanan.penugasan.status', $pesanan)))"
    :class="berubah ? 'ring-2 ring-hijau-aksi' : ''"
    class="rounded-xl border border-surface-border bg-page/50 p-3 transition"
    aria-labelledby="judul-status-penugasan-{{ $pesanan->id }}"
>
    <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
        <p id="judul-status-penugasan-{{ $pesanan->id }}" class="text-xs font-semibold uppercase tracking-wide text-muted">Status penugasan guide</p>
        <span x-cloak x-show="perluDipantau" class="inline-flex items-center gap-1.5 text-[11px] font-medium text-muted">
            <span class="relative flex h-2 w-2"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-500 opacity-60"></span><span class="relative inline-flex h-2 w-2 rounded-full bg-amber-600"></span></span>
            Dipantau langsung
        </span>
    </div>

    <ol class="space-y-2.5 text-sm" aria-live="polite">
        {{-- 1. Email penugasan --}}
        <li class="flex gap-2.5">
            <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full"
                :class="{
                    'bg-status-completed-bg text-status-completed-teks': status.email.status === 'terkirim',
                    'bg-status-dibatalkan-bg text-status-dibatalkan-teks': status.email.status === 'gagal',
                    'bg-status-payment-bg text-status-payment-teks': status.email.status === 'tanpa_email',
                    'bg-white text-muted': ! status.email.status,
                }">
                <span class="material-symbols-outlined text-[16px]!" aria-hidden="true" x-text="status.email.status === 'terkirim' ? 'mail' : (status.email.status ? 'error' : 'schedule')">mail</span>
            </span>
            <div class="min-w-0 flex-1">
                <p class="font-semibold text-ink" x-text="{
                    terkirim: 'Email penugasan sudah terkirim',
                    gagal: 'Email penugasan GAGAL terkirim',
                    tanpa_email: 'Email belum bisa dikirim',
                }[status.email.status] ?? 'Belum ada catatan email penugasan'"></p>
                <template x-if="status.email.status === 'terkirim'">
                    <p class="text-xs leading-5 text-muted">Diterima server email untuk <span class="font-medium break-all text-ink" x-text="status.email.alamat"></span> · <span x-text="status.email.waktu"></span>. Bila guide belum melihatnya, minta cek folder Spam/Promosi.</p>
                </template>
                <template x-if="status.email.status === 'gagal' || status.email.status === 'tanpa_email'">
                    <p class="text-xs leading-5 text-status-dibatalkan-teks"><span x-text="status.email.galat"></span> <span x-show="status.email.waktu" class="text-muted">(<span x-text="status.email.waktu"></span>)</span></p>
                </template>
                <template x-if="! status.email.status">
                    <p class="text-xs leading-5 text-muted">Email dikirim otomatis setiap kali Anda menekan Simpan Operasional. Pesanan lama belum tercatat; tekan Kirim ulang email bila ragu.</p>
                </template>
                @unless ($terkunci)
                    <div x-cloak x-show="status.email.status !== 'terkirim'" class="mt-1.5 flex flex-wrap gap-2">
                        <x-admin.tombol type="submit" form="{{ $idFormKirimUlang }}" variant="secondary" size="sm" icon="send">Kirim ulang email</x-admin.tombol>
                        @if ($tautanWaGuide)
                            <x-admin.tombol as="a" href="{{ $tautanWaGuide }}" target="_blank" rel="noopener noreferrer" variant="secondary" size="sm" icon="chat">Kabari lewat WhatsApp</x-admin.tombol>
                        @endif
                    </div>
                @endunless
            </div>
        </li>

        {{-- 2. Konfirmasi guide --}}
        <li class="flex gap-2.5">
            <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full"
                :class="{
                    'bg-amber-700 text-white': status.konfirmasi === 'menunggu',
                    'bg-status-completed-bg text-status-completed-teks': status.konfirmasi === 'diterima',
                    'bg-status-dibatalkan-bg text-status-dibatalkan-teks': status.konfirmasi === 'ditolak',
                }">
                <span class="material-symbols-outlined text-[16px]!" aria-hidden="true" x-text="{ menunggu: 'schedule', diterima: 'task_alt', ditolak: 'cancel' }[status.konfirmasi] ?? 'schedule'">schedule</span>
            </span>
            <div class="min-w-0 flex-1">
                <template x-if="status.konfirmasi === 'menunggu'">
                    <div>
                        <span class="inline-flex items-center rounded-full bg-amber-700 px-2.5 py-1 text-xs font-bold text-white">Menunggu konfirmasi guide</span>
                        <p class="mt-1 text-xs leading-5 text-muted"><span class="font-medium text-ink" x-text="status.guide"></span> belum menjawab. Status ini berubah sendiri begitu guide menerima atau menolak tugas.</p>
                    </div>
                </template>
                <template x-if="status.konfirmasi === 'diterima'">
                    <div>
                        <span class="inline-flex items-center rounded-full bg-status-completed-bg px-2.5 py-1 text-xs font-bold text-status-completed-teks">Guide menerima tugas</span>
                        <p class="mt-1 text-xs leading-5 text-muted"><span class="font-medium text-ink" x-text="status.guide"></span> menerima tugas ini · <span x-text="status.konfirmasi_waktu"></span></p>
                    </div>
                </template>
                <template x-if="status.konfirmasi === 'ditolak'">
                    <div>
                        <span class="inline-flex items-center rounded-full bg-status-dibatalkan-bg px-2.5 py-1 text-xs font-bold text-status-dibatalkan-teks">Guide menolak tugas</span>
                        <p class="mt-1 text-xs leading-5 text-status-dibatalkan-teks">Pilih guide pengganti pada kolom di atas lalu simpan. <span class="text-muted" x-show="status.konfirmasi_waktu">(<span x-text="status.konfirmasi_waktu"></span>)</span></p>
                    </div>
                </template>
            </div>
        </li>
    </ol>
</section>
