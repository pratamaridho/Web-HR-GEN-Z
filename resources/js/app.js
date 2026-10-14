import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// Angka dashboard dihitung naik dari 0. Teks akhir sudah ada di HTML,
// jadi bila skrip gagal atau gerakan dimatikan, angkanya tetap benar.
document.addEventListener('DOMContentLoaded', () => {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    document.querySelectorAll('[data-hitung]').forEach((el) => {
        const akhir = parseInt(el.dataset.hitung, 10);
        const akhiran = el.dataset.akhiran || '';
        const durasi = 800;

        if (!akhir) {
            return;
        }

        const mulai = performance.now();
        const langkah = (sekarang) => {
            const p = Math.min(1, (sekarang - mulai) / durasi);
            el.textContent = Math.round(akhir * (1 - Math.pow(1 - p, 3))) + akhiran;

            if (p < 1) {
                requestAnimationFrame(langkah);
            }
        };

        requestAnimationFrame(langkah);
        setTimeout(() => { el.textContent = akhir + akhiran; }, durasi + 150);
    });
});
