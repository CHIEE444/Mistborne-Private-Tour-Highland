import Alpine from 'alpinejs';

/** Jeda cek ulang saat menunggu jawaban guide: cukup rapat agar jawaban langsung terlihat, tetap ringan (satu query kecil). */
const INTERVAL_PANTAU_MS = 2000;

/**
 * Panel status penugasan guide di kartu Operasional: konfirmasi guide dan hasil email penugasan.
 * Selama guide belum menjawab, status dicek ulang otomatis; begitu guide menerima atau menolak, panel berubah
 * dan muncul toast tanpa muat ulang halaman. Pemantauan berhenti sendiri setelah ada jawaban.
 */
Alpine.data('statusPenugasan', (awal, url) => ({
    status: awal,
    berubah: false,

    init() {
        setInterval(() => {
            if (!document.hidden && this.perluDipantau) {
                this.segarkan();
            }
        }, INTERVAL_PANTAU_MS);
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.segarkan();
            }
        });
        window.addEventListener('focus', () => this.segarkan());
    },

    get perluDipantau() {
        return this.status.konfirmasi === 'menunggu';
    },

    async segarkan() {
        let hasil = null;
        try {
            const respons = await fetch(url, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            hasil = respons.ok ? await respons.json() : null;
        } catch {
            return;
        }

        if (!hasil) {
            return;
        }

        const sebelumnya = this.status.konfirmasi;
        this.status = hasil;

        if (sebelumnya === 'menunggu' && ['diterima', 'ditolak'].includes(hasil.konfirmasi)) {
            this.berubah = true;
            setTimeout(() => (this.berubah = false), 2400);
            window.dispatchEvent(new CustomEvent('notifikasi', {
                detail: hasil.konfirmasi === 'diterima'
                    ? { tipe: 'sukses', pesan: `${hasil.guide} menerima tugas ini.` }
                    : { tipe: 'gagal', pesan: `${hasil.guide} menolak tugas ini. Pilih guide pengganti lalu simpan.` },
            }));
        }
    },
}));
