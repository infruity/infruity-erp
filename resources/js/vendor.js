/**
 * Bundle klasik (IIFE) untuk library yang dipakai skrip inline halaman saat
 * HTML masih di-parse: `$` / `jQuery` dan `Swal`. Lihat vite.config.js.
 */
import $ from 'jquery';
import Swal from 'sweetalert2';

window.$ = window.jQuery = $;
window.Swal = Swal;
