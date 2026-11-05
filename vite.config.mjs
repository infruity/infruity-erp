import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

/**
 * Dua build:
 *
 *   npm run build          CSS (Tailwind) lewat Vite  -> public/build
 *                          + vendor JS                -> public/build/vendor/vendor.js
 *   npm run dev            Vite dev server untuk CSS (HMR)
 *   npm run dev:vendor     rebuild vendor JS saat file berubah
 *
 * jQuery dan SweetAlert2 sengaja dibangun sebagai skrip klasik (IIFE), bukan
 * module. Skrip inline di banyak halaman memakai `$` dan `Swal` langsung saat
 * HTML di-parse; module baru jalan sesudah parsing selesai dan akan membuat
 * skrip-skrip itu error. Alpine tetap module (entry resources/js/alpine.js),
 * karena memang harus jalan sesudah <body> ada, sama seperti `defer` sebelumnya.
 */
export default defineConfig(({ mode }) => {
    if (mode === 'vendor') {
        return {
            publicDir: false,
            build: {
                outDir: 'public/build/vendor',
                emptyOutDir: true,
                manifest: false,
                sourcemap: false,
                rollupOptions: {
                    input: 'resources/js/vendor.js',
                    output: {
                        format: 'iife',
                        entryFileNames: 'vendor.js',
                    },
                },
            },
        };
    }

    return {
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/css/legacy.css', 'resources/css/datatables.css',
                'resources/css/flatpickr.css', 'resources/js/alpine.js', 'resources/js/erp.js'],
                refresh: [
                    'resources/views/**',
                    'Modules/*/Resources/views/**',
                    'app/**/*.php',
                    'Modules/*/Http/**/*.php',
                ],
            }),
        ],
        build: {
            // vendor.js ada di subfolder; build CSS tidak boleh menghapusnya.
            emptyOutDir: false,
        },
    };
});
