import base from './tailwind.config.mjs';

/**
 * SEMENTARA (dihapus di B6). Dipakai resources/css/legacy.css untuk halaman yang
 * masih memakai layout template.root (Metronic/Bootstrap).
 *
 * Meniru perilaku CDN lama di layout itu: config inline-nya diset SEBELUM skrip
 * CDN dimuat sehingga tertimpa dan tidak pernah berlaku. Hasilnya, halaman-halaman
 * itu memakai tema Tailwind DEFAULT (tanpa warna/font/bayangan kustom) dengan
 * preflight AKTIF. Itu yang dipertahankan di sini supaya M0 tidak mengubah tampilan.
 * Tema kustom baru berlaku saat sebuah halaman pindah ke layouts.erp (otomatis
 * memperbaiki font shell dari font sistem ke Plus Jakarta Sans).
 */
export default {
    content: base.content,
    theme: {},
    plugins: [],
};
