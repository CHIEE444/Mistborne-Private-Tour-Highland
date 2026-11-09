{{-- ITEM FAQ (v4, mengikuti referensi): pertanyaan besar di kiri, tombol kotak di kanan (hijau muda "+" saat tertutup,
     gelap "×" saat terbuka), jawaban abu-abu di bawah pertanyaan. Accordion Alpine x-collapse; id panel unik untuk aria-controls. --}}
@props(['faq', 'index', 'open' => false])
<div x-data="{ open: {{ $open ? 'true' : 'false' }} }">
    <button
        type="button"
        class="group flex w-full items-start justify-between gap-6 text-start"
        @click="open = !open"
        :aria-expanded="open"
        aria-controls="faq-panel-{{ $index }}"
    >
        <span class="max-w-[30ch] pt-1 text-[clamp(19px,2vw,26px)] font-medium leading-snug tracking-[-0.015em] text-[#1C2421]">{{ $faq['q'] }}</span>
        <span
            class="flex h-11 w-11 shrink-0 items-center justify-center transition-colors duration-300"
            :class="open ? 'bg-[#2B2F2D] text-white' : 'bg-[#DDEBD3] text-[#2B2F2D] group-hover:bg-[#CFE3C2]'"
            aria-hidden="true"
        >
            <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current transition-transform duration-300" :class="open ? 'rotate-45' : ''" stroke-width="1.4" stroke-linecap="round"><path d="M12 4v16M4 12h16"/></svg>
        </span>
    </button>
    <div
        id="faq-panel-{{ $index }}"
        x-show="open"
        @if (! $open) x-cloak @endif
        x-collapse
        class="max-w-[62ch] pe-16 pt-4 text-[15px] leading-relaxed text-[#6B736F]"
    >
        {{ $faq['a'] }}
    </div>
</div>
