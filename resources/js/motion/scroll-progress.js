// Progress bar emas di tepi atas (scaleX via rAF, listener passive).
import { reduced } from './reduced';

/* ===== Progress bar emas di tepi atas (scaleX, rAF, passive) ===== */
export function initScrollProgress() {
    const bar = document.querySelector('[data-scroll-progress]');
    if (!bar || reduced.matches) return;
    let ticking = false;
    const update = () => {
        const max = document.documentElement.scrollHeight - window.innerHeight;
        const p = max > 0 ? Math.min(window.scrollY / max, 1) : 0;
        bar.style.transform = `scaleX(${p})`;
        ticking = false;
    };
    window.addEventListener(
        'scroll',
        () => {
            if (!ticking) {
                requestAnimationFrame(update);
                ticking = true;
            }
        },
        { passive: true }
    );
    update();
}

