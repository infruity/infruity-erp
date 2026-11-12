/*
 * ERP dialogs: one look and one wording for every SweetAlert2 dialog of the ERP.
 *
 * New code uses the helpers:
 *     ErpDialog.confirmDelete({ what: 'Satuan', name: 'Kg' }).then(ok => { if (ok) ... });
 *     ErpDialog.confirm({ title: 'Selesaikan transaksi?', text: '...', tone: 'primary' });
 *     ErpDialog.error('Daftar tidak dapat dimuat.');   ErpDialog.success('Tersimpan.');
 *
 * Existing code keeps calling Swal.fire(...): install() wraps it, so the ~670 calls of the legacy pages come out the same
 * as the helpers: one set of colours, the Metronic English button texts ("Ok, got it!", "Yes, delete!") in Indonesian,
 * cancel on the left, and a destructive confirmation (warning icon + cancel button) in red with the focus on Cancel.
 * Everything else a call passes (html, input, preConfirm, timer, toast, ...) is untouched.
 */
(function (root, factory) {
    if (typeof module === 'object' && module.exports) module.exports = factory();
    else root.ErpDialog = factory();
})(typeof self !== 'undefined' ? self : this, function () {
    'use strict';

    var COLORS = { primary: '#0b595b', danger: '#dc2626', warning: '#b45309', success: '#047857', cancel: '#4b5563' };

    // Texts that came with the Metronic templates or were typed per page, to the one wording used now.
    var TEXTS = {
        'ok, got it!': 'OK',
        'ok': 'OK',
        'mengerti': 'OK',
        'yes, cancel it!': 'Ya, batalkan',
        'ya, batalkan!': 'Ya, batalkan',
        'ya, batalkan': 'Ya, batalkan',
        'yes, delete!': 'Ya, hapus',
        'yes, delete it!': 'Ya, hapus',
        'ya, hapus!': 'Ya, hapus',
        'ya, hapus': 'Ya, hapus',
        'yes, close it!': 'Ya, tutup',
        'ya, tutup!': 'Ya, tutup',
        'no, return': 'Kembali',
        'tidak, kembali': 'Kembali',
        'no, cancel': 'Batal',
        'no': 'Batal'
    };

    function mapText(text) {
        if (typeof text !== 'string') return text;
        var mapped = TEXTS[text.trim().toLowerCase()];
        return mapped === undefined ? text : mapped;
    }

    /** Swal.fire(title, text, icon) and Swal.fire({...}) both end up as one options object. */
    function toOptions(args) {
        var first = args[0];
        if (first !== null && typeof first === 'object' && !Array.isArray(first)) return Object.assign({}, first);
        return { title: first, text: args[1], icon: args[2] };
    }

    function stripButtonClasses(customClass) {
        if (!customClass || typeof customClass !== 'object') return customClass;
        var kept = Object.assign({}, customClass);
        ['confirmButton', 'cancelButton', 'denyButton'].forEach(function (key) { delete kept[key]; });
        return Object.keys(kept).length ? kept : undefined;
    }

    /** The options every dialog is shown with. Pure, so it can be tested without a browser. */
    function normalize(args) {
        var o = toOptions(args);
        if (o.toast) {
            if (o.title) o.title = mapText(o.title);
            return o;
        }

        var asks = !!o.showCancelButton || !!o.showDenyButton;
        var tone = o.tone || (asks && (o.icon === 'warning' || o.icon === 'error') ? 'danger' : 'primary');
        delete o.tone;

        // Native SweetAlert2 buttons: the Metronic "btn btn-primary" classes made them look like a different app.
        o.buttonsStyling = true;
        var customClass = stripButtonClasses(o.customClass);
        if (customClass) o.customClass = customClass; else delete o.customClass;

        if (o.showConfirmButton !== false) {
            o.confirmButtonText = mapText(o.confirmButtonText) || (asks ? 'Ya' : 'OK');
            o.confirmButtonColor = COLORS[tone] || COLORS.primary;
        }
        if (o.showCancelButton) {
            o.cancelButtonText = mapText(o.cancelButtonText) || 'Batal';
            o.cancelButtonColor = COLORS.cancel;
        }
        if (o.showDenyButton) {
            o.denyButtonText = mapText(o.denyButtonText) || 'Tidak';
            o.denyButtonColor = COLORS.cancel;
        }
        if (asks) {
            o.reverseButtons = true; // Cancel on the left, the action on the right
            if (o.focusCancel === undefined && tone === 'danger') o.focusCancel = true; // Enter must not delete
        }
        return o;
    }

    var swal = null;

    function fire(options) {
        return swal.fire(options);
    }

    /** Resolves true when the user confirmed. */
    function confirm(options) {
        return fire(Object.assign({ icon: 'question', showCancelButton: true }, options)).then(function (result) { return !!(result && result.isConfirmed); });
    }

    /** "Hapus Satuan?  Satuan Kg akan dihapus permanen."  { what, name, text } */
    function confirmDelete(options) {
        options = options || {};
        var what = options.what || 'data';
        var text = options.text || (options.name ? what + ' ' + options.name + ' akan dihapus permanen.' : 'Data akan dihapus permanen.');
        return confirm({ icon: 'warning', tone: 'danger', title: 'Hapus ' + what + '?', text: text, confirmButtonText: options.confirmText || 'Hapus' });
    }

    function message(icon, defaultTitle) {
        return function (text, title) { return fire({ icon: icon, title: title || defaultTitle, text: text }); };
    }

    /** Wraps swal.fire once, so every existing Swal.fire(...) call gets the same look and wording. */
    function install(target) {
        swal = target;
        if (!target || target.__erpDialog) return target;
        var original = target.fire;
        target.fire = function () { return original.call(target, normalize(arguments)); };
        target.__erpDialog = true;
        return target;
    }

    var api = {
        COLORS: COLORS,
        normalize: normalize,
        install: install,
        confirm: confirm,
        confirmDelete: confirmDelete,
        error: message('error', 'Gagal'),
        success: message('success', 'Berhasil'),
        warning: message('warning', 'Perhatian'),
        info: message('info', 'Info')
    };

    if (typeof window !== 'undefined' && window.Swal) install(window.Swal);

    return api;
});
