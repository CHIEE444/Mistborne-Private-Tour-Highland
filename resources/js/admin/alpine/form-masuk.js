import Alpine from 'alpinejs';

/**
 * Efek visual saat form Masuk dikirim (form tetap submit biasa; tombol dinonaktifkan oleh cegah-kirim-ganda.js).
 */
Alpine.data('formMasuk', () => ({
    mengirim: false,

    kirim() {
        this.mengirim = true;
    },
}));
