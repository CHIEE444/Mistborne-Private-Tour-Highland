@props([
    'nama',
    'size' => 'md',
])

@php
    $lebar = match ($size) {
        'sm' => 'max-w-md',
        'lg' => 'max-w-2xl',
        'xl' => 'max-w-4xl',
        default => 'max-w-lg',
    };
@endphp

{{-- Popup "Disalin ke clipboard" tampil di atas modal ini: menutupnya (klik atau Esc) tidak ikut menutup modal di belakangnya. --}}
<div class="admin-dialog-aksi" x-data="modal('{{ $nama }}')" x-on:keydown.escape.window="if (! document.querySelector('[data-modal-salin][data-terbuka]')) tutup()" x-on:aksi-mulai-loading.window="$el.classList.add('admin-modal-sedang-loading'); tutup()">
    <div
        x-show="terbuka"
        x-cloak
        x-transition.opacity
        x-on:click="tutup()"
        class="fixed inset-0 z-[60] bg-black/50"
    ></div>

    <div
        x-show="terbuka"
        x-cloak
        x-transition
        class="fixed inset-0 z-[60] flex items-center justify-center p-2 sm:p-4"
    >
        <div
            x-on:click.outside="if (! $event.target.closest('[data-modal-salin]')) tutup()"
            {{ $attributes->class(["flex max-h-[calc(100vh-1rem)] max-h-[calc(100dvh-1rem)] w-full {$lebar} flex-col overflow-hidden rounded-admin-kartu bg-white shadow-admin-kartu sm:max-h-[calc(100vh-2rem)] sm:max-h-[calc(100dvh-2rem)]"]) }}
        >
            @isset($header)
                <div class="flex shrink-0 items-start justify-between gap-4 border-b border-surface-border px-4 py-3 sm:px-6 sm:py-4">
                    <div>{{ $header }}</div>
                    <button type="button" x-on:click="tutup()" aria-label="Tutup dialog" class="text-muted hover:text-ink">
                        <span class="material-symbols-outlined" aria-hidden="true">close</span>
                    </button>
                </div>
            @endisset

            <div class="min-h-0 overflow-y-auto overscroll-contain px-4 py-4 sm:px-6 sm:py-5">
                {{ $slot }}
            </div>

            @isset($footer)
                <div class="flex shrink-0 items-center justify-end gap-3 border-t border-surface-border px-4 py-3 sm:px-6 sm:py-4">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
</div>
