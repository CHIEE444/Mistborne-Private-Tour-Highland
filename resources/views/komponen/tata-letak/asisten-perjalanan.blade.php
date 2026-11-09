@php
    // KONSTANTA DESAIN komponen ini — ukuran, posisi, warna dari config/desain.php
    // (halaman pusat token). Panggil lewat warna()/ukuran()/font() — jangan hex baru di markup.
    $emas = warna('emas');
    $waHijau = warna('waHijau');
    $hijauPekat = warna('hijauPekat');
@endphp
{{-- FLOATING CONCIERGE (#concierge, A1) — tombol persis desain; muncul fade+translate 2 detik setelah load [TAMBAHAN];
     cincin hijau lembut pada dot; popup x-transition origin-bottom-right --}}
<div id="concierge" x-data="{ open: false, shown: false }" @keydown.escape.window="open = false" x-init="setTimeout(() => shown = true, 2000)" class="pointer-events-auto fixed bottom-4 end-4 z-50 flex flex-col items-end pb-[env(safe-area-inset-bottom)] sm:bottom-6 sm:end-6">
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition origin-bottom-end duration-300 ease-out"
        x-transition:enter-start="opacity-0 scale-95 translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition origin-bottom-end duration-150 ease-in"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95 translate-y-2"
        class="mb-3 w-64 rounded-2xl border border-[#C8A24A]/40 bg-white p-4 shadow-2xl"
        role="dialog"
        aria-label="{{ __('Popup Asisten Perjalanan') }}"
    >
        <div class="flex items-center gap-2">
            <x-bersama.ikon-wa class="size-5 shrink-0 text-[#25D366]" />
            <span class="font-serif text-base font-semibold tracking-tight text-[#12301F]">{{ __('Asisten Perjalanan Mistborne') }}</span>
        </div>
        <p class="mt-1.5 text-xs leading-relaxed text-gray-600">
            {{ __('Butuh bantuan merancang perjalanan privat? Tim kami siap membalas dalam hitungan menit.') }}
        </p>
        <x-bersama.tautan-wa
            :text="__('Halo Asisten Perjalanan Mistborne, saya butuh bantuan')"
            class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-full bg-[#1B2E22] px-4 py-2 text-xs font-semibold text-[#C8A24A] shadow-md transition-all hover:bg-[#253f2f] active:scale-95"
        >
            <x-bersama.ikon-wa class="size-4 shrink-0" />
            {{ __('Chat WhatsApp') }}
        </x-wa-link>
    </div>

    <button
        class="concierge-enter flex items-center gap-2.5 rounded-full border border-[#C8A24A]/40 bg-[#1B2E22] px-3.5 py-3.5 text-white shadow-2xl min-[1700px]:px-5 min-[1700px]:py-3 transition-transform hover:scale-105 active:scale-95"
        type="button"
        @click="open = !open"
        :aria-expanded="open"
        :class="shown ? 'is-shown' : ''"
        aria-label="{{ __('Buka asisten perjalanan Mistborne') }}"
    >
        <span class="relative flex items-center justify-center">
            <x-bersama.ikon-wa class="size-5 shrink-0 text-[#25D366]" />
            <span class="ring-pulse-green absolute -end-1 -top-1 h-2.5 w-2.5 rounded-full bg-[#25D366] ring-2 ring-[#1B2E22]" aria-hidden="true"></span>
        </span>
        <span class="hidden text-xs font-semibold tracking-wide min-[1700px]:inline">{{ __('Asisten Perjalanan') }}</span>
    </button>
</div>
