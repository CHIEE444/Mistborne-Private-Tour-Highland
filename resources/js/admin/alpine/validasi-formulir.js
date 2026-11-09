import Alpine from 'alpinejs';

/**
 * Validasi sisi klien untuk form admin: pesan Indonesia tampil di dalam komponen field masing-masing
 * (bukan popup browser). Field yang wajib diberi atribut data-wajib="pesan" dan data-kunci="nama.field";
 * <x-admin.formulir.kolom> menampilkan pesannya dari `galat[kunci]`. Server tetap sumber kebenaran.
 */
Alpine.data('validasiFormulir', () => ({
    galat: {},

    periksa(event) {
        const galatBaru = {};
        let pertama = null;

        event.target.querySelectorAll('[data-wajib]').forEach((el) => {
            const kosong = el.type === 'file'
                ? el.files.length === 0
                : String(el.value ?? '').trim() === '';

            if (kosong) {
                galatBaru[el.dataset.kunci ?? el.name] = el.dataset.wajib || 'Isian ini wajib diisi.';
                pertama ??= el;
            }
        });

        this.galat = galatBaru;

        if (pertama) {
            event.preventDefault();
            const sasaran = pertama.offsetParent === null ? pertama.parentElement : pertama;
            sasaran.scrollIntoView({ block: 'center', behavior: 'smooth' });
            sasaran.focus?.({ preventScroll: true });
        }
    },

    bersihkan(event) {
        const kunci = event.target?.dataset?.kunci;

        if (kunci && this.galat[kunci]) {
            delete this.galat[kunci];
        }
    },
}));
