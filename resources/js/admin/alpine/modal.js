import Alpine from 'alpinejs';

/**
 * Dipakai oleh <x-admin.modal>. Dibuka lewat event global `window.dispatchEvent(new CustomEvent('buka-modal', { detail: { nama } }))`
 * atau langsung lewat x-data="modal()" + tombol x-on:click="buka()".
 */
Alpine.data('modal', (nama = null) => ({
    terbuka: false,
    nama,

    buka() {
        this.$root.classList.remove('admin-modal-sedang-loading');
        this.terbuka = true;
    },

    tutup() {
        this.terbuka = false;
    },

    init() {
        if (!this.nama) {
            return;
        }

        window.addEventListener('buka-modal', (event) => {
            if (event.detail?.nama === this.nama) {
                this.buka();
            }
        });
    },
}));
