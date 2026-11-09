{{-- Toast publik setelah pesan WhatsApp terkirim; dipicu event "wa-terkirim" dari resources/js/app.js. --}}
@php
    $hijauTua = warna('hijauTua');
    $emas = warna('emas');
@endphp
<div
    x-data="{
        tampil: false,
        init() {
            window.addEventListener('wa-terkirim', () => {
                this.tampil = true;
                clearTimeout(this.waktu);
                this.waktu = setTimeout(() => { this.tampil = false }, 6000);
            });
        },
    }"
    x-show="tampil"
    x-cloak
    x-transition
    class="pointer-events-none fixed inset-x-0 bottom-4 z-[100] flex justify-center px-4 sm:bottom-6"
    role="status"
    aria-live="polite"
>
    <div class="pointer-events-auto flex max-w-sm items-start gap-3 rounded-2xl border border-white/10 px-4 py-3.5 shadow-[0_18px_40px_-16px_rgba(10,26,18,.45)]" style="background-color: {{ $hijauTua }}">
        <span class="material-symbols-outlined mt-0.5 shrink-0 text-lg" style="color: {{ $emas }}" aria-hidden="true">chat</span>
        <div class="flex-1 text-sm text-white">
            <p class="font-semibold">Pesan terkirim ke WhatsApp</p>
            <p class="mt-0.5 text-xs text-white/75">Tim MISTBORNE biasanya merespons dalam beberapa menit pada jam kerja.</p>
        </div>
        <button type="button" x-on:click="tampil = false" aria-label="Tutup" class="shrink-0 text-white/60 hover:text-white">
            <span class="material-symbols-outlined text-base" aria-hidden="true">close</span>
        </button>
    </div>
</div>
