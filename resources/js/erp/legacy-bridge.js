/**
 * SEMENTARA (dihapus di B6). Jembatan agar skrip halaman yang masih memanggil
 * `bootstrap.Modal` / `$(el).modal()` berjalan di atas <dialog> komponen
 * `x-erp.dialog`, sehingga halaman bisa dipindah satu per satu tanpa menulis ulang
 * semua JS-nya sekaligus. Hanya Modal; Dropdown/Tab/Collapse harus diganti markup.
 *
 * Setiap pemakaian menulis `console.warn` agar mudah dicari sebelum B6.
 */
const EVENTS = ['show', 'shown', 'hide', 'hidden'];

export function install(erpDialog, win = window) {
    const warned = new Set();
    const warn = (what) => {
        if (warned.has(what)) return;
        warned.add(what);
        if (win.console) win.console.warn(`[erp] ${what} dipakai lewat legacy-bridge; ganti ke erp.dialog sebelum B6.`);
    };

    const emit = (el, name) => {
        const type = `${name}.bs.modal`;
        el.dispatchEvent(new CustomEvent(type, { bubbles: true }));
        if (win.jQuery) win.jQuery(el).trigger(type);
    };

    const instances = new WeakMap();

    class Modal {
        constructor(element) {
            this._el = typeof element === 'string' ? win.document.querySelector(element) : element;
            if (!this._el) throw new Error('Modal: elemen tidak ditemukan');
            instances.set(this._el, this);
            this._el.addEventListener('erp:dialog-open', () => emit(this._el, 'shown'));
            this._el.addEventListener('erp:dialog-close', () => emit(this._el, 'hidden'));
        }
        // Elemen yang bukan <dialog> (modal Bootstrap lama yang belum dipindah) tidak bisa
        // dibuka dari sini; abaikan dengan peringatan alih-alih melempar error.
        get _native() { return typeof this._el.showModal === 'function'; }
        show() {
            warn('bootstrap.Modal.show');
            if (!this._native) { warn('Modal pada elemen non-<dialog>'); return; }
            emit(this._el, 'show');
            erpDialog.open(this._el);
        }
        hide() {
            warn('bootstrap.Modal.hide');
            if (!this._native) return;
            emit(this._el, 'hide');
            erpDialog.close(this._el);
        }
        toggle() { return this._el.open ? this.hide() : this.show(); }
        dispose() { instances.delete(this._el); }
        static getInstance(el) { return instances.get(typeof el === 'string' ? win.document.querySelector(el) : el) || null; }
        static getOrCreateInstance(el) { return Modal.getInstance(el) || new Modal(el); }
    }

    if (!win.bootstrap) win.bootstrap = {};
    if (!win.bootstrap.Modal) win.bootstrap.Modal = Modal;
    if (!win.bootstrap.Tooltip) {
        win.bootstrap.Tooltip = class { static getOrCreateInstance() { return { dispose() {}, show() {}, hide() {} }; } constructor() {} dispose() {} };
    }

    if (win.jQuery && !win.jQuery.fn.modal) {
        win.jQuery.fn.modal = function (action = 'show') {
            return this.each(function () {
                const modal = Modal.getOrCreateInstance(this);
                if (action === 'hide') modal.hide();
                else if (action === 'toggle') modal.toggle();
                else modal.show();
            });
        };
    }

    return { Modal, EVENTS };
}
