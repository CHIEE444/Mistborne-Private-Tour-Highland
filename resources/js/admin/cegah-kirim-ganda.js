/**
 * Menonaktifkan tombol submit setelah form dikirim (mencegah kirim ganda) di semua form admin,
 * termasuk tombol di luar <form> yang terhubung lewat atribut form="id".
 */
document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || event.defaultPrevented) {
        return;
    }

    const pemicu = event.submitter instanceof HTMLButtonElement ? event.submitter : null;

    setTimeout(() => {
        const tombolTurunan = [...form.querySelectorAll('button[type="submit"]')];
        const tombolLuarForm = form.id
            ? [...document.querySelectorAll(`button[type="submit"][form="${CSS.escape(form.id)}"]`)]
            : [];

        [...tombolTurunan, ...tombolLuarForm].forEach((tombol) => {
            tombol.disabled = true;
            tombol.classList.add('opacity-50', 'cursor-not-allowed');
        });

        if (pemicu && [...tombolTurunan, ...tombolLuarForm].includes(pemicu)) {
            pemicu.setAttribute('aria-busy', 'true');
        }
    }, 0);
});
