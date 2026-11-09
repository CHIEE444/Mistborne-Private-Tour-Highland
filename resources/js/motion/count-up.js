// Angka stats menghitung naik — OPT-IN lewat [data-count-animate][data-count-to]
// (HTML awal sudah berisi angka akhir: aman no-JS/SEO). Stats band MISTBORNE tidak
// memasang animasi (spec: tanpa animasi); hook test STAT-03 [data-count-to] tetap ada.
import { reduced } from './reduced';

/* ===== Angka stats menghitung naik (opt-in) ===== */
export function initCountUp() {
    const els = document.querySelectorAll('[data-count-animate][data-count-to]');
    if (!els.length || reduced.matches) return;

    const easeOutCubic = (t) => 1 - Math.pow(1 - t, 3);
    const animate = (el) => {
        const target = parseFloat(el.dataset.countTo);
        const decimals = parseInt(el.dataset.decimals || '0', 10);
        const duration = 1200;
        const start = performance.now();
        const tick = (now) => {
            const p = Math.min((now - start) / duration, 1);
            el.textContent = (target * easeOutCubic(p)).toFixed(decimals);
            if (p < 1) requestAnimationFrame(tick);
        };
        requestAnimationFrame(tick);
    };

    const io = new IntersectionObserver(
        (entries) => {
            for (const entry of entries) {
                if (entry.isIntersecting) {
                    animate(entry.target);
                    io.unobserve(entry.target);
                }
            }
        },
        { threshold: 0.5 }
    );
    els.forEach((el) => io.observe(el));
}

