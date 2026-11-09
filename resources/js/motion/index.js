// Orkestrator modul motion situs publik.
import { initReveal } from './reveal';
import { initCountUp } from './count-up';
import { initScrollProgress } from './scroll-progress';
import { initActiveNav } from './scrollspy';
import { initStatsParallax } from './punggung-statistik';

// Ikon Material Symbols dekoratif disembunyikan dari screen reader.
function initIconAria() {
    document.querySelectorAll('.material-symbols-outlined').forEach((icon) => {
        if (!icon.hasAttribute('aria-hidden')) icon.setAttribute('aria-hidden', 'true');
    });
}

// Bintang ulasan menyala satu per satu (delay dari urutan).
function initStars() {
    document.querySelectorAll('[data-stars]').forEach((group) => {
        group.querySelectorAll('.star').forEach((star, i) => {
            star.style.setProperty('--star-i', String(i));
        });
    });
}

export function initMotion() {
    initIconAria();
    initStars();
    initReveal();
    initCountUp();
    initScrollProgress();
    initActiveNav();
    initStatsParallax();
}
