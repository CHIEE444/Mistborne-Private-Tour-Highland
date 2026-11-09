{{--
    Area toast global. Server mengisi pesan lewat session flash `sukses`/`gagal` (redirect()->with('sukses', '...')).
    JS lain bisa memicu toast baru lewat: window.dispatchEvent(new CustomEvent('notifikasi', { detail: { tipe: 'sukses', pesan: '...' } }))
    dan menutup semua toast lewat: window.dispatchEvent(new CustomEvent('notifikasi-bersihkan'))
--}}
<div
    x-data="{
        daftar: [],
        tambah(tipe, pesan) {
            const id = Date.now() + Math.random();
            this.daftar.push({ id, tipe, pesan });
            setTimeout(() => this.hapus(id), 4000);
        },
        hapus(id) {
            this.daftar = this.daftar.filter((item) => item.id !== id);
        },
        init() {
            window.addEventListener('notifikasi', (event) => this.tambah(event.detail.tipe, event.detail.pesan));
            {{-- Dipanggil sebelum membuka popup penting (mis. Pengingat Hari Ini) agar toast tidak menimpa judulnya. --}}
            window.addEventListener('notifikasi-bersihkan', () => { this.daftar = []; });

            @if (session('sukses'))
                this.tambah('sukses', @js(session('sukses')));
            @endif

            @if (session('gagal'))
                this.tambah('gagal', @js(session('gagal')));
            @elseif ($errors->any())
                {{-- Galat validasi yang tidak terikat ke field tetap tampil sebagai toast. --}}
                this.tambah('gagal', @js($errors->first()));
            @endif
        },
    }"
    class="pointer-events-none fixed inset-x-0 top-4 z-[70] flex flex-col items-center gap-2 px-4"
>
    <template x-for="item in daftar" :key="item.id">
        <div
            x-show="true"
            x-transition
            :class="item.tipe === 'gagal' ? 'bg-status-dibatalkan-bg text-status-dibatalkan-teks' : 'bg-status-completed-bg text-status-completed-teks'"
            class="pointer-events-auto flex w-full max-w-sm items-center gap-3 rounded-admin-kartu px-4 py-3 text-sm font-medium shadow-admin-kartu"
        >
            <span class="material-symbols-outlined text-lg" aria-hidden="true" x-text="item.tipe === 'gagal' ? 'error' : 'check_circle'"></span>
            <span class="flex-1" x-text="item.pesan"></span>
            <button type="button" x-on:click="hapus(item.id)" aria-label="Tutup notifikasi" class="opacity-70 hover:opacity-100">
                <span class="material-symbols-outlined text-base" aria-hidden="true">close</span>
            </button>
        </div>
    </template>
</div>
