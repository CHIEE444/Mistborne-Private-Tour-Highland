import Alpine from 'alpinejs';

/**
 * Overlay proses bersama untuk aksi tulis di semua halaman admin.
 *
 * Hanya ada satu overlay: ia muncul saat form dikirim dan tetap tampil di halaman lama sampai
 * browser menampilkan halaman tujuan, jadi tidak perlu dimunculkan ulang setelah halaman dimuat.
 */
Alpine.data('loadingAksi', () => ({
    aktif: false,
    judul: 'Memproses aksi...',
    keterangan: 'Mohon tunggu sebentar dan jangan muat ulang halaman.',

    mulai(event) {
        const form = event.target;
        if (
            event.defaultPrevented
            || !(form instanceof HTMLFormElement)
            || !form.checkValidity()
            || form.dataset.loadingHandled === 'true'
        ) {
            return;
        }

        const tombol = event.submitter;
        const salinanTombol = tombol instanceof HTMLElement ? tombol.cloneNode(true) : null;
        salinanTombol?.querySelectorAll('.material-symbols-outlined').forEach((ikon) => ikon.remove());
        const aksi = salinanTombol?.textContent?.trim().toLocaleLowerCase('id') ?? '';

        this.judul = this.judulUntuk(aksi);
        this.aktif = true;
        document.body.classList.add('admin-sedang-memproses');
        window.dispatchEvent(new CustomEvent('aksi-mulai-loading'));
    },

    judulUntuk(aksi) {
        if (/hapus|delete/.test(aksi)) {
            return 'Menghapus data...';
        }
        if (/deal|buat pesanan/.test(aksi)) {
            return 'Membuat pesanan...';
        }
        if (/buat|tambah/.test(aksi)) {
            return 'Menyimpan data baru...';
        }
        if (/simpan|edit|ubah|perbarui|update/.test(aksi)) {
            return 'Menyimpan perubahan...';
        }
        if (/whatsapp/.test(aksi)) {
            return 'Menyiapkan WhatsApp...';
        }

        return 'Memproses aksi...';
    },

    /** Halaman yang dipulihkan dari tombol Kembali (bfcache) tidak boleh tertahan di overlay lama. */
    pulihkan(event) {
        if (event?.persisted) {
            this.aktif = false;
            document.body.classList.remove('admin-sedang-memproses');
        }
    },
}));
