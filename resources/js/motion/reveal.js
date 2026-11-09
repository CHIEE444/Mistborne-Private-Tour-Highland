// Scroll reveal: satu IntersectionObserver, sekali jalan per elemen (unobserve setelah tampil).
import { reduced } from './reduced';

/* ===== Scroll reveal ===== */
export function initReveal() {
    const els = document.querySelectorAll('[data-reveal]');
    if (!els.length) return;

    if (reduced.matches) {
        els.forEach((el) => el.classList.add('is-revealed'));
        return;
    }

    const io = new IntersectionObserver(
        (entries) => {
            for (const entry of entries) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-revealed');
                    io.unobserve(entry.target);
                }
            }
        },
        { threshold: 0.15, rootMargin: '0px 0px -8% 0px' }
    );

    els.forEach((el) => {
        const delay = parseInt(el.dataset.revealDelay || '0', 10);
        if (delay > 0) el.style.setProperty('--reveal-delay', `${delay}ms`);
        io.observe(el);
    });
}

