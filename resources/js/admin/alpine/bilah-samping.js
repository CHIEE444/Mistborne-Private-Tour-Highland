import Alpine from 'alpinejs';

/**
 * Drawer menu admin (rail) untuk tablet/handphone. Dipakai oleh tata-letak admin: tombol ☰ di bilah atas memanggil
 * toggle(), overlay dan tombol Esc memanggil tutup(). Di layar lebar (lg) rail selalu tampil, jadi drawer ditutup
 * saat layar melebar agar overlay tidak tertinggal.
 */
Alpine.data('bilahSamping', () => ({
    terbuka: false,

    toggle() {
        this.terbuka = !this.terbuka;
    },

    tutup() {
        this.terbuka = false;
    },

    init() {
        window.matchMedia('(min-width: 1024px)').addEventListener('change', (event) => {
            if (event.matches) {
                this.tutup();
            }
        });
    },
}));
