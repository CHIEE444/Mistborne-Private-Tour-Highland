// Gelembung bicara (popover berpanah) untuk ikon sidebar admin di layar lebar: nama fitur muncul
// di samping ikon yang disorot/difokus, lalu meluncur halus saat kursor berpindah ke ikon lain.
const layarLebar = window.matchMedia('(min-width: 1024px)');

let gelembung = null;
let pemicuAktif = null;
let timerSembunyi = null;

function buatGelembung() {
    gelembung = document.createElement('div');
    gelembung.id = 'rail-gelembung';
    gelembung.className = 'rail-gelembung';
    gelembung.setAttribute('role', 'tooltip');

    const judul = document.createElement('span');
    judul.className = 'rail-gelembung-judul';
    const sub = document.createElement('span');
    sub.className = 'rail-gelembung-sub';

    gelembung.append(judul, sub);
    document.body.append(gelembung);
}

function tampilkan(pemicu) {
    if (! layarLebar.matches) {
        return;
    }

    clearTimeout(timerSembunyi);

    if (! gelembung) {
        buatGelembung();
    }

    const sedangTampil = gelembung.classList.contains('rail-gelembung--tampil');
    const sub = pemicu.dataset.gelembungSub ?? '';

    gelembung.querySelector('.rail-gelembung-judul').textContent = pemicu.dataset.gelembung;
    gelembung.querySelector('.rail-gelembung-sub').textContent = sub;
    gelembung.querySelector('.rail-gelembung-sub').hidden = sub === '';

    const kotak = pemicu.getBoundingClientRect();
    const rail = pemicu.closest('.rail').getBoundingClientRect();

    // Muncul pertama kali: langsung di posisi ikon (tanpa meluncur dari posisi lama).
    gelembung.classList.toggle('rail-gelembung--lompat', ! sedangTampil);
    gelembung.style.top = `${kotak.top + kotak.height / 2}px`;
    gelembung.style.left = `${rail.right + 14}px`;
    void gelembung.offsetWidth;
    gelembung.classList.remove('rail-gelembung--lompat');
    gelembung.classList.add('rail-gelembung--tampil');

    pemicuAktif?.removeAttribute('aria-describedby');
    pemicu.setAttribute('aria-describedby', gelembung.id);
    pemicuAktif = pemicu;
}

function sembunyikan(segera = false) {
    clearTimeout(timerSembunyi);
    timerSembunyi = setTimeout(() => {
        gelembung?.classList.remove('rail-gelembung--tampil');
        pemicuAktif?.removeAttribute('aria-describedby');
        pemicuAktif = null;
    }, segera ? 0 : 80);
}

const pemicuDari = (target) => target instanceof Element ? target.closest('.rail [data-gelembung]') : null;

document.addEventListener('pointerover', (event) => {
    const pemicu = pemicuDari(event.target);
    if (pemicu) {
        tampilkan(pemicu);
    }
});

document.addEventListener('pointerout', (event) => {
    const pemicu = pemicuDari(event.target);
    if (pemicu && ! pemicu.contains(event.relatedTarget)) {
        sembunyikan();
    }
});

document.addEventListener('focusin', (event) => {
    const pemicu = pemicuDari(event.target);
    if (pemicu) {
        tampilkan(pemicu);
    }
});

document.addEventListener('focusout', (event) => {
    if (pemicuDari(event.target)) {
        sembunyikan();
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        sembunyikan(true);
    }
});

document.addEventListener('scroll', () => sembunyikan(true), true);
layarLebar.addEventListener('change', () => sembunyikan(true));
