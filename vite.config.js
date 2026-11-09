import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/admin/aplikasi.css',
                'resources/js/admin/aplikasi.js',
            ],
            refresh: true,
            fonts: [
                // Satu keluarga untuk seluruh situs [PERMINTAAN USER]
                bunny('Montserrat', {
                    weights: [300, 400, 500, 600, 700],
                }),
                // [TAHAP 7 / PENYIMPANGAN dilaporkan] Aksara Arab untuk /ar — Montserrat tidak memuatnya.
                // Bunny hanya mengunduh subset glyph yang benar-benar dipakai halaman.
                bunny('Noto Sans Arabic', {
                    weights: [400, 500, 600, 700],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
