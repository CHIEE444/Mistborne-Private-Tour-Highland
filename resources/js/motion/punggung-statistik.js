export function initStatsParallax() {
    const ridge = document.querySelector('.stats-ridge');
    const image = ridge?.querySelector('img');
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const mobile = window.matchMedia('(max-width: 767px)').matches;

    if (!ridge || !image || reduced || mobile) return;

    let terlihat = false;
    let menungguFrame = false;
    const observer = new IntersectionObserver(([entry]) => {
        terlihat = entry.isIntersecting;
    }, { rootMargin: '100px 0px' });

    const perbarui = () => {
        if (!terlihat || menungguFrame) return;
        menungguFrame = true;
        window.requestAnimationFrame(() => {
            const top = ridge.getBoundingClientRect().top;
            const offset = Math.max(-8, Math.min(8, (window.innerHeight * 0.55 - top) * 0.018));
            image.style.setProperty('--stats-parallax-y', `${offset}px`);
            menungguFrame = false;
        });
    };

    observer.observe(ridge);
    window.addEventListener('scroll', perbarui, { passive: true });
}
