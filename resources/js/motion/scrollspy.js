// Link navbar aktif mengikuti section yang sedang terlihat.
/* ===== Link navbar aktif mengikuti section yang terlihat ===== */
export function initActiveNav() {
    // Hanya link nav desktop yang dipantau (link drawer mobile akan menimpanya); Beranda ikut walau URL-nya lengkap.
    const links = document.querySelectorAll(
        'nav[data-component="layout.navbar.menu-desktop"] .nav-link[href*="#"]'
    );
    if (!links.length) return;
    const map = new Map();
    links.forEach((link) => {
        let id;
        try {
            id = new URL(link.href, window.location.href).hash.slice(1);
        } catch {
            return;
        }
        const section = id ? document.getElementById(decodeURIComponent(id)) : null;
        if (section) map.set(section, link);
    });
    if (!map.size) return;

    const setActive = (link) => {
        links.forEach((l) => {
            l.classList.remove('nav-active');
            l.removeAttribute('aria-current');
        });
        link.classList.add('nav-active');
        link.setAttribute('aria-current', 'location');
    };

    // Klik anchor: tandai langsung agar URL dan highlight selalu sinkron.
    links.forEach((link) => {
        link.addEventListener('click', () => setActive(link));
    });

    // Pita observasi 35%-50% tinggi viewport.
    const bandTop = () => window.innerHeight * 0.35;
    const bandBottom = () => window.innerHeight * 0.5;

    // Status awal dihitung langsung dari posisi scroll: section di pita aktif, atau section terakhir yang sudah lewat.
    const sectionAtBand = () => {
        const top = bandTop();
        const bottom = bandBottom();
        let containing = null;
        let lastPassed = null;
        map.forEach((link, section) => {
            const rect = section.getBoundingClientRect();
            if (rect.top <= top && rect.bottom >= bottom) containing = link;
            if (rect.top <= top) lastPassed = link;
        });
        return containing ?? lastPassed ?? links[0];
    };
    setActive(sectionAtBand());

    // Dua section bisa lewat pita dalam satu batch IO; pilih yang PALING ATAS di viewport
    // (bukan urutan entri observer) agar aktif selalu deterministik. Bila tidak ada section
    // di pita (gap), status terakhir dipertahankan.
    const io = new IntersectionObserver(
        (entries) => {
            const visible = entries
                .filter((entry) => entry.isIntersecting && map.has(entry.target))
                .sort((a, b) => a.target.getBoundingClientRect().top - b.target.getBoundingClientRect().top);
            if (visible.length) setActive(map.get(visible[0].target));
        },
        // Pita 35%-50% tinggi viewport: lebih toleran terhadap section pendek
        // yang tidak pernah menyentuh pita 45%-50% yang lama.
        { rootMargin: '-35% 0px -50% 0px' }
    );
    map.forEach((_, section) => io.observe(section));
}
