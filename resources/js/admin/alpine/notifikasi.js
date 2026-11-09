import Alpine from 'alpinejs';

const INTERVAL_SEGARKAN_MS = 20000;

/** Angka badge menu yang dipakai bersama sidebar dan tombol di halaman (nilai awal dari server). */
Alpine.store('badge', {
    permintaan: window.badgeAdminAwal?.permintaan ?? 0,
    pertanyaan: window.badgeAdminAwal?.pertanyaan ?? 0,
    kustom: window.badgeAdminAwal?.kustom ?? 0,
});

/**
 * Lonceng notifikasi admin: angka belum dibaca, daftar terbaru, dan pembaruan berkala.
 * Notifikasi baru yang masuk saat halaman terbuka memunculkan toast tanpa muat ulang.
 */
Alpine.data('notifikasiAdmin', (awal, url) => ({
    buka: false,
    jumlah: awal.jumlah_belum_dibaca,
    daftar: awal.daftar,
    token: document.querySelector('meta[name="csrf-token"]')?.content ?? '',

    init() {
        setInterval(() => this.segarkan(), INTERVAL_SEGARKAN_MS);
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.segarkan();
            }
        });
    },

    get label() {
        return this.jumlah > 99 ? '99+' : String(this.jumlah);
    },

    async segarkan() {
        const hasil = await this.kirim(url.ringkasan, 'GET');
        if (hasil) {
            this.terapkan(hasil);
        }
    },

    async tandaiSemuaDibaca() {
        const hasil = await this.kirim(url.bacaSemua, 'POST');
        if (hasil) {
            this.jumlah = hasil.jumlah_belum_dibaca;
            this.daftar = hasil.daftar;
            this.perbaruiBadge(hasil);
        }
    },

    perbaruiBadge(hasil) {
        Alpine.store('badge').permintaan = hasil.jumlah_permintaan_baru;
        Alpine.store('badge').pertanyaan = hasil.jumlah_pertanyaan_baru;
        Alpine.store('badge').kustom = hasil.jumlah_kustom_baru;
    },

    terapkan(hasil) {
        const dikenal = new Set(this.daftar.map((item) => item.id));
        const baru = hasil.daftar.filter((item) => !item.dibaca && !dikenal.has(item.id));

        this.jumlah = hasil.jumlah_belum_dibaca;
        this.daftar = hasil.daftar;
        this.perbaruiBadge(hasil);

        if (baru.length > 0) {
            const tambahan = baru.length > 1 ? ` (+${baru.length - 1} lainnya)` : '';
            window.dispatchEvent(new CustomEvent('notifikasi', {
                detail: { tipe: 'sukses', pesan: `${baru[0].judul}: ${baru[0].baris[0]}${tambahan}` },
            }));
        }
    },

    async kirim(alamat, metode) {
        try {
            const respons = await fetch(alamat, {
                method: metode,
                credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': this.token, 'X-Requested-With': 'XMLHttpRequest' },
            });

            return respons.ok ? await respons.json() : null;
        } catch {
            return null;
        }
    },
}));
