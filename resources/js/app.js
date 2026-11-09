
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import { initMotion } from './motion';

// window.WA_NUMBER diisi server-side (Pengaturan::current()->nomor_whatsapp_admin)
// lewat <script> inline di komponen/tata-letak/aplikasi.blade.php, sebelum berkas ini dimuat.

Alpine.plugin(collapse);

/**
 * Catat pertanyaan calon tamu di server (untuk antrean "Pertanyaan Tamu" admin) tepat setelah WhatsApp dibuka.
 * Gagal mencatat tidak boleh mengganggu tamu, jadi semua galat ditelan dan tidak ada yang ditunggu.
 */
function catatPertanyaan(data) {
    try {
        fetch(window.PERTANYAAN_URL, {
            method: 'POST',
            keepalive: true,
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: JSON.stringify(data),
        }).catch(() => {});
    } catch {
        // Pencatatan bersifat tambahan; WhatsApp sudah terbuka.
    }
}

window.catatPertanyaan = catatPertanyaan;

// Tombol/tautan WhatsApp bertanda data-catat-wa dicatat sebagai pertanyaan tamu di mana pun ia berada,
// termasuk tombol hero yang tidak berada di dalam komponen Alpine mana pun dan tanpa mengisi filter pencarian.
function catatKlikWhatsApp(event) {
    const tautan = event.target instanceof Element ? event.target.closest('[data-catat-wa]') : null;
    if (!tautan) return;
    catatPertanyaan({
        sumber: 'cta_whatsapp',
        pesan: tautan.dataset.catatWa || 'Menekan tombol WhatsApp (tanpa pesan awal)',
        journey: null,
    });
    window.dispatchEvent(new CustomEvent('wa-terkirim'));
}
document.addEventListener('click', catatKlikWhatsApp);
document.addEventListener('auxclick', catatKlikWhatsApp);

// Form kontak: rakit pesan lalu buka wa.me (murni front-end); label dari Blade lewat @js.
// Pilihan paket memakai kode stabil; labelnya diterjemahkan lewat labels.paketOptions saat dikirim.
Alpine.data('contactForm', (labels = {}, journeys = []) => ({
    labels,
    journeys,
    // Field mengikuti form booking & panel admin: journey, tanggal (H+7 s.d. 1 tahun), jumlah peserta
    // (rombongan melebihi kapasitas journey diproses sebagai Trip Kustom), alamat/titik jemput, jam jemput,
    // permintaan khusus, kontak tamu.
    batasRombongan: 50,
    form: {
        nama: '',
        whatsapp: '',
        journey: journeys[0]?.value ?? '',
        tanggal: '',
        wisatawan: 2,
        // Sistem all-inclusive: penginapan dan tiket masuk diurus MISTBORNE, tamu tidak mengisi data hotel.
        penginapan: 'diurus',
        hotel: '',
        lokasi: '',
        jam: '',
        catatan: '',
    },
    touched: {},
    modalKustom: false,
    init() {
        // Modal peringatan muncul setiap kali rombongan mulai melebihi kapasitas journey (→ Trip Kustom).
        this.$watch('keKustom', (kustom) => {
            if (kustom) {
                this.modalKustom = true;
            }
        });
    },
    sentuh(kolom) {
        this.touched[kolom] = true;
    },
    get terpilih() {
        return this.journeys.find((j) => j.value === this.form.journey) ?? this.journeys[0] ?? null;
    },
    // Tanggal terformat sesuai bahasa halaman, mis. "Sab, 7 Nov 2026".
    tanggalTampil(iso) {
        const tanggal = new Date(`${iso}T00:00:00`);
        if (Number.isNaN(tanggal.getTime())) {
            return iso;
        }
        return tanggal.toLocaleDateString(document.documentElement.lang || 'id', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
    },
    get maksPeserta() {
        return this.terpilih?.maks || 15;
    },
    // Rombongan lebih besar dari kapasitas journey (mis. satu keluarga 10 orang) dialihkan ke Trip Kustom.
    get keKustom() {
        return this.form.journey !== 'CUSTOM HIGHLAND JOURNEY' && Number(this.form.wisatawan) > this.maksPeserta;
    },
    // Validasi sisi klien — aturan sama dengan validasi server form booking.
    get ok() {
        const f = this.form;
        const wa = String(f.whatsapp || '').replace(/[\s\-.()]/g, '');
        const tanggal = String(f.tanggal || '');
        const peserta = Number(f.wisatawan);
        return {
            nama: f.nama.trim() !== '' && !/[<>]/.test(f.nama),
            whatsapp: /^(\+?62|0)8\d{7,12}$/.test(wa),
            tanggal: /^\d{4}-\d{2}-\d{2}$/.test(tanggal) && tanggal >= this.labels.tanggalMin && tanggal <= this.labels.tanggalMax,
            wisatawan: Number.isInteger(peserta) && peserta >= 1 && peserta <= this.batasRombongan,
            lokasi: f.lokasi.trim() !== '' && !/[<>]/.test(f.lokasi),
        };
    },
    get lengkap() {
        return Object.values(this.ok).every(Boolean) && !!this.terpilih;
    },
    kurangi() {
        this.form.wisatawan = Math.max(1, (Number(this.form.wisatawan) || 1) - 1);
    },
    tambah() {
        this.form.wisatawan = Math.min(this.batasRombongan, (Number(this.form.wisatawan) || 1) + 1);
    },
    // Isi pesan WhatsApp yang dikirim saat submit.
    lines() {
        const f = this.form;
        const L = this.labels;
        const fmt = (iso) => {
            if (!iso) return '-';
            const [y, m, d] = iso.split('-');
            return `${d} ${['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'][Number(m) - 1]} ${y}`;
        };
        return [
            L.prefix,
            `${L.nama}: ${f.nama || '-'}`,
            `${L.whatsapp}: ${f.whatsapp || '-'}`,
            `${L.journey}: ${f.journey}${this.keKustom ? ` — ${L.kustomRombongan}` : ''}`,
            `${L.tanggal}: ${fmt(f.tanggal)}`,
            `${L.wisatawan}: ${f.wisatawan || 1} ${L.orang ?? ''}`,
            `${L.penginapan}: ${L.penginapanAllIn}`,
            `${L.lokasi}: ${f.lokasi || '-'}`,
            f.jam ? `${L.jam}: ${f.jam}` : '',
            `${L.catatan}: ${f.catatan || '-'}`,
        ].filter((baris) => baris !== '');
    },
    // Journey aktif saat ini — dipakai caption foto inset panel kiri (rsx-shot__cap).
    journeyAktif() {
        return this.terpilih ?? { name: '' };
    },
    submit() {
        if (!this.lengkap) {
            Object.keys(this.ok).forEach((k) => (this.touched[k] = true));
            return;
        }
        const f = this.form;
        const msg = this.lines().join('\n');
        window.open(`https://wa.me/${window.WA_NUMBER}?text=${encodeURIComponent(msg)}`, '_blank', 'noopener,noreferrer');
        catatPertanyaan({
            sumber: 'kontak',
            nama: f.nama || null,
            whatsapp: f.whatsapp || null,
            journey: f.journey,
            lokasi_pickup: f.lokasi || null,
            tanggal_perjalanan: f.tanggal || null,
            pesan: msg,
        });
        window.dispatchEvent(new CustomEvent('wa-terkirim'));
    },
}));

/**
 * Pindah fokus antar butir menu (panah atas/bawah, melingkar) tanpa plugin Alpine Focus.
 * @param {HTMLElement} menu wadah menu; butirnya [role="menuitem"]
 * @param {number} arah 1 = berikutnya, -1 = sebelumnya
 */
window.pindahFokusMenu = (menu, arah) => {
    const butir = [...menu.querySelectorAll('[role="menuitem"]')];
    if (butir.length === 0) {
        return;
    }
    const sekarang = butir.indexOf(document.activeElement);
    const tujuan = sekarang === -1 ? (arah > 0 ? 0 : butir.length - 1) : (sekarang + arah + butir.length) % butir.length;
    butir[tujuan].focus();
};

window.Alpine = Alpine;

Alpine.start();

// Animasi scroll reveal, count-up stats, progress bar, nav aktif (lihat folder js/motion/)
initMotion();
