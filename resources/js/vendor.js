/**
 * Bundle klasik (IIFE) untuk library yang dipakai skrip inline halaman saat
 * HTML masih di-parse: `$` / `jQuery`, `Swal`, dan DataTables. Lihat vite.config.mjs.
 *
 * DataTables 1.13.7 = versi yang sama dengan bundle Metronic yang digantikan.
 * Hanya inti; ekstensi (fixedColumns, responsive, select, buttons) ditambahkan
 * bersama halaman yang membutuhkannya. Bawaan Metronic (dom, class, paginasi) ada di
 * vendor/datatables-bootstrap.js agar markup tabel tidak berubah.
 */
import $ from 'jquery';
import Swal from 'sweetalert2';
import DataTable from 'datatables.net';
import { install as installDataTableDefaults } from './vendor/datatables-bootstrap.js';

window.$ = window.jQuery = $;
window.Swal = Swal;

// Paket datatables.net berbentuk UMD: panggil factory-nya dengan jQuery kita.
DataTable(window, $);
installDataTableDefaults($);
