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
// <dialog> modal membuat semua elemen di luarnya inert, termasuk SweetAlert yang ditempel di <body>:
// konfirmasi/error muncul di belakang dialog dan tidak bisa diklik. Bila ada dialog terbuka, render
// SweetAlert di dalam dialog paling atas dan tampilkan di top layer (popover), jadi terlihat dan bisa diklik.
const fire = Swal.fire;
Swal.fire = function (...args) {
    const dialogs = document.querySelectorAll('dialog[open]');
    const host = dialogs[dialogs.length - 1];
    if (!host) return fire.apply(this, args);
    const given = args[0] && typeof args[0] === 'object' ? args[0] : { title: args[0], html: args[1], icon: args[2] };
    return fire.call(this, { target: host, topLayer: true, heightAuto: false, ...given });
};
window.Swal = Swal;

// Paket datatables.net berbentuk UMD: panggil factory-nya dengan jQuery kita.
DataTable(window, $);
installDataTableDefaults($);
