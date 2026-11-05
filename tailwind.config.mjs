/**
 * Hasil penggabungan 4 konfigurasi Tailwind CDN yang sebelumnya inline:
 * layouts/erp-tailwind, template/root, auth/login, landing.
 *
 * Satu-satunya nilai yang bentrok adalah `cream`: layout ERP #F7F7F7, login dan
 * landing #FAF8F2. Hanya login dan landing yang memakai `bg-cream`, jadi nilai
 * #FAF8F2 yang dipakai. `cream-dark` dipertahankan apa adanya.
 *
 * Token tambahan (teal, lime, canvas, control, panel) berasal dari
 * docs/ui-design-system.md. Semua bersifat tambahan, tidak mengubah utility lama.
 * @tailwindcss/forms sengaja TIDAK dipakai: class-nya (`.form-select`, dst.) bentrok
 * dengan `.form-select` Bootstrap/Metronic selama migrasi. Gaya form `erp-*` ditulis sendiri.
 *
 * Tailwind `extend` menggabungkan objek warna, jadi skala bawaan `teal-50..950`
 * dan `lime-50..950` tetap ada.
 */
export default {
    content: [
        './resources/views/**/*.blade.php',
        './Modules/*/Resources/views/**/*.blade.php',
        './app/**/*.php',
        './Modules/*/Http/**/*.php',
        './Modules/*/Support/**/*.php',
        './resources/js/**/*.js',
        // JS halaman yang belum dipindah ke Vite. Bundle vendor Metronic tidak dipindai.
        './public/assets/js/*.js',
        '!./public/assets/js/*.bundle.js',
    ],
    theme: {
        extend: {
            colors: {
                primary: {
                    DEFAULT: '#0F5C45',
                    light: '#1a7d5e',
                    dark: '#0a3d2e',
                },
                accent: {
                    DEFAULT: '#F2B400',
                    light: '#f5c733',
                },
                cream: {
                    DEFAULT: '#FAF8F2',
                    dark: '#EBEBEB',
                },
                strawberry: {
                    DEFAULT: '#D6455D',
                    light: '#e06a7e',
                },
                teal: {
                    DEFAULT: '#0b595b',
                    hover: '#08484a',
                },
                lime: {
                    DEFAULT: '#d1f36e',
                },
                canvas: '#e5e9f3',
                forest: '#0f5c45', // hijau tua dashboard CRM
                mist: '#ebf2ef', // hijau pucat latar chip
            },
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                serif: ['"Poppins"', 'sans-serif'],
            },
            // Teks dasar aplikasi 13px di atas root 16px (docs/ui-design-system.md); `text-sm` tetap 14px.
            fontSize: {
                body: ['13px', '1.5'],
            },
            boxShadow: {
                soft: '0 10px 40px -10px rgba(0,0,0,0.08)',
                glass: '0 8px 32px 0 rgba(31, 38, 135, 0.07)',
                input: '0 2px 10px rgba(0,0,0,0.02)',
                card: '0 1px 3px rgba(0,0,0,0.04), 0 6px 16px rgba(0,0,0,0.04)',
                sidebar: '4px 0 24px rgba(0,0,0,0.06)',
            },
            borderRadius: {
                control: '12px',
                panel: '16px',
            },
            height: {
                control: '44px',
            },
            minHeight: {
                control: '44px',
            },
        },
    },
    plugins: [],
};
