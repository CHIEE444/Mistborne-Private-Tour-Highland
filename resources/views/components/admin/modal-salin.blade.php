{{--
    Satu-satunya modal umpan balik penyalinan. Dipicu oleh window.salinTeks() lewat event `hasil-salin`
    dengan detail { berhasil: bool, pesan: string }.
--}}
<div
    x-data="{
        terbuka: false,
        berhasil: false,
        pesan: '',
        timer: null,
        tampil(detail) {
            this.berhasil = detail.berhasil;
            this.pesan = detail.pesan;
            this.terbuka = true;
            clearTimeout(this.timer);
            this.timer = setTimeout(() => this.terbuka = false, 3500);
        },
    }"
    x-on:hasil-salin.window="tampil($event.detail)"
    x-on:keydown.escape.window="terbuka = false"
    data-modal-salin
    x-bind:data-terbuka="terbuka"
>
    <div
        x-cloak
        x-show="terbuka"
        x-transition.opacity
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 p-4"
        role="presentation"
        x-on:click.self="terbuka = false"
    >
        <div
            x-show="terbuka"
            x-transition
            role="dialog"
            aria-modal="true"
            aria-labelledby="judul-modal-salin"
            class="w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-2xl"
        >
            {{-- Lingkaran dan ikon dipisah: aturan global .material-symbols-outlined (inline-block, 24px, tanpa layer)
                 mengalahkan flex/text-3xl sehingga centang menempel di atas lingkaran bila digabung dalam satu span. --}}
            <span
                class="mx-auto flex h-14 w-14 items-center justify-center rounded-full"
                :class="berhasil ? 'bg-status-completed-bg text-status-completed-teks' : 'bg-status-dibatalkan-bg text-status-dibatalkan-teks'"
                aria-hidden="true"
            >
                <span class="material-symbols-outlined text-[30px]!"x-text="berhasil ? 'check_circle' : 'error'"></span>
            </span>
            <p id="judul-modal-salin" class="mt-4 text-lg font-semibold text-ink" x-text="berhasil ? 'Disalin ke clipboard' : 'Gagal menyalin'"></p>
            <p aria-live="polite" class="mt-2 text-sm leading-6 text-muted" x-text="pesan"></p>
            <button
                type="button"
                x-on:click="terbuka = false"
                class="mt-5 rounded-full bg-admin-sidebar px-5 py-2.5 text-sm font-semibold text-white hover:bg-admin-sidebar-active"
            >
                Mengerti
            </button>
        </div>
    </div>
</div>
