import Alpine from 'alpinejs';

/** Dipakai oleh <x-admin.unggah-berkas> (seret-lepas file, mis. bukti pembayaran/video). */
Alpine.data('unggahBerkas', (aktifkanPratinjau = false, maksimalMb = null) => ({
    namaBerkas: null,
    menyeret: false,
    urlBerkas: null,
    adalahGambar: false,
    detailBerkas: null,

    tandaiMenyeret(status) {
        this.menyeret = status;
    },

    pilihDariInput(event) {
        this.simpanBerkas(event.target.files?.[0] ?? null);
    },

    jatuhkanBerkas(event) {
        this.menyeret = false;
        const berkas = event.dataTransfer?.files?.[0] ?? null;
        if (this.simpanBerkas(berkas) && event.dataTransfer?.files?.length) {
            this.$refs.input.files = event.dataTransfer.files;
        }
    },

    simpanBerkas(berkas) {
        if (berkas && maksimalMb && berkas.size > maksimalMb * 1024 * 1024) {
            this.$refs.input.value = '';
            this.namaBerkas = null;
            if (this.urlBerkas) URL.revokeObjectURL(this.urlBerkas);
            this.urlBerkas = null;
            this.adalahGambar = false;
            window.dispatchEvent(new CustomEvent('notifikasi', {
                detail: { tipe: 'gagal', pesan: 'Lampiran lebih dari 5 MB. Pilih file yang ukurannya maksimal 5 MB.' },
            }));
            return false;
        }

        this.namaBerkas = berkas ? berkas.name : null;
        if (this.urlBerkas) URL.revokeObjectURL(this.urlBerkas);
        this.urlBerkas = aktifkanPratinjau && berkas ? URL.createObjectURL(berkas) : null;
        this.adalahGambar = Boolean(berkas?.type.startsWith('image/'));
        this.detailBerkas = berkas ? this.hitungDetail(berkas) : null;
        return true;
    },

    /** Detail file untuk pratinjau: format, ukuran, dan (khusus gambar) dimensi piksel. */
    hitungDetail(berkas) {
        const detail = {
            format: (berkas.type.split('/')[1] ?? 'berkas').toUpperCase().replace('JPEG', 'JPG'),
            ukuran: berkas.size >= 1024 * 1024
                ? (berkas.size / 1024 / 1024).toFixed(2) + ' MB'
                : Math.max(1, Math.round(berkas.size / 1024)) + ' KB',
            dimensi: null,
        };

        const url = this.urlBerkas;

        if (url && berkas.type.startsWith('image/')) {
            const gambar = new Image();
            gambar.onload = () => {
                if (this.urlBerkas === url) {
                    this.detailBerkas = { ...detail, dimensi: `${gambar.naturalWidth} × ${gambar.naturalHeight} px` };
                }
            };
            gambar.src = url;
        }

        return detail;
    },

    destroy() {
        if (this.urlBerkas) URL.revokeObjectURL(this.urlBerkas);
    },
}));
