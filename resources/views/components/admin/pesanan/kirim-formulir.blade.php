@props([
    'pertanyaanId' => null,
    'nomor' => null,
    'label' => 'Kirim Form ke Tamu',
    'variant' => 'primary',
])

@php $idJudul = 'judul-form-pesanan-wa'.($pertanyaanId ? '-'.$pertanyaanId : ''); @endphp
<div class="admin-dialog-aksi" x-data="{ terbuka: {{ \Illuminate\Support\Js::from($errors->has('whatsapp') && (int) old('pertanyaan_id') === (int) $pertanyaanId) }} }" x-on:aksi-mulai-loading.window="$el.classList.add('admin-modal-sedang-loading'); terbuka = false">
    <x-admin.tombol type="button" icon="send" :variant="$variant" x-on:click="terbuka = true">{{ $label }}</x-admin.tombol>

    <div
        x-show="terbuka"
        x-cloak
        x-transition.opacity
        x-on:click.self="terbuka = false"
        x-on:keydown.escape.window="terbuka = false"
        class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 p-4"
        role="presentation"
    >
        <section
            x-show="terbuka"
            x-transition
            role="dialog"
            aria-modal="true"
            aria-labelledby="{{ $idJudul }}"
            class="w-full max-w-lg rounded-2xl bg-white p-5 shadow-2xl sm:p-6"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 id="{{ $idJudul }}" class="text-lg font-semibold text-ink">Kirim Form ke Tamu</h2>
                    <p class="mt-1 text-sm leading-6 text-muted">WhatsApp akan terbuka dengan tautan formulir pribadi untuk tamu ini.</p>
                </div>
                <button type="button" x-on:click="terbuka = false" aria-label="Tutup" class="rounded-full p-1 text-muted hover:bg-page hover:text-ink">
                    <span class="material-symbols-outlined" aria-hidden="true">close</span>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.permintaan-pesanan.kirim-form') }}" class="mt-5 space-y-4">
                @csrf
                @if ($pertanyaanId)
                    <input type="hidden" name="pertanyaan_id" value="{{ $pertanyaanId }}" />
                @endif
                <x-admin.formulir.masukan
                    name="whatsapp"
                    label="Nomor WhatsApp tamu"
                    type="tel"
                    inputmode="tel"
                    autocomplete="tel"
                    maxlength="24"
                    pattern="\+?[0-9][0-9 ().\-]{8,22}"
                    placeholder="Diawali 08 atau +62, contoh 081234567890"
                    :value="old('whatsapp', $nomor)"
                    wajib
                />

                <div class="rounded-xl bg-page p-4 text-xs leading-5 text-muted">
                    Form meminta nama, nomor WhatsApp, journey, tanggal trip, jumlah peserta, hotel, lokasi jemput, serta catatan opsional. Setelah tamu mengirim, permintaan masuk ke antrean admin untuk dicek.
                </div>
                <p class="text-xs leading-5 text-muted">Mengirim formulir belum membuat pesanan atau menahan slot. Admin akan mengecek tanggal, follow-up, lalu membuat pesanan setelah deal.</p>

                <div class="flex justify-end gap-2 pt-1">
                    <x-admin.tombol type="button" variant="secondary" x-on:click="terbuka = false">Batal</x-admin.tombol>
                    <x-admin.tombol type="submit" icon="open_in_new">Buka WhatsApp</x-admin.tombol>
                </div>
            </form>
        </section>
    </div>
</div>
