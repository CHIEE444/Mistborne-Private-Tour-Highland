import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

import './alpine/bilah-samping';
import './alpine/form-masuk';
import './alpine/loading-aksi';
import './alpine/notifikasi';
import './alpine/status-penugasan';
import './alpine/modal';
import './alpine/unggah-berkas';
import './alpine/validasi-formulir';
import './cegah-kirim-ganda';
import './gelembung-rail';

Alpine.plugin(collapse);

// Salin teks dan laporkan hasil sebenarnya. Clipboard API hanya tersedia pada
// konteks aman; fallback menjaga tombol tetap berfungsi pada localhost/browser lama.
window.salinTeks = async (teks, pesanSukses = 'Pesan sudah tersimpan di clipboard dan siap ditempel di WhatsApp.', pesanGagal = 'Browser tidak mengizinkan penyalinan. Silakan pilih dan salin pesan dari pratinjau.') => {
    let berhasil = false;
    try {
        if (navigator.clipboard?.writeText && window.isSecureContext) {
            await navigator.clipboard.writeText(teks);
            berhasil = true;
        }
    } catch {
        // Coba fallback di bawah saat browser menolak izin Clipboard API.
    }

    if (!berhasil) {
        let area;
        try {
            area = document.createElement('textarea');
            area.value = teks;
            area.setAttribute('readonly', '');
            area.style.position = 'fixed';
            area.style.inset = '0 auto auto 0';
            area.style.width = '1px';
            area.style.height = '1px';
            area.style.opacity = '0';
            document.body.appendChild(area);
            area.focus();
            area.select();
            berhasil = document.execCommand('copy');
        } catch {
            berhasil = false;
        } finally {
            area?.remove();
        }
    }

    window.dispatchEvent(new CustomEvent('hasil-salin', {
        detail: { berhasil, pesan: berhasil ? pesanSukses : pesanGagal },
    }));
    return berhasil;
};

window.Alpine = Alpine;

Alpine.start();
