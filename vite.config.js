import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            // rekap-print-zip hanya dimuat di halaman rekap (lihat @vite di
            // rekap.blade.php) supaya JSZip tidak membebani halaman lain.
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/rekap-print-zip.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
