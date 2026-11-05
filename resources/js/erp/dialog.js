/**
 * Dialog dan drawer berbasis <dialog> native.
 *
 * Browser sudah menyediakan fokus trap, Esc, inert latar, dan pemulihan fokus lewat
 * showModal(). Skrip ini hanya menambah: pembuka/penutup deklaratif, klik latar,
 * dialog `data-static`, scroll-lock, dan event `erp:dialog-open` / `erp:dialog-close`.
 *
 *   <button data-erp-open="#id">…</button>   membuka <dialog id="id">
 *   <button data-erp-close>…</button>        menutup dialog terdekat
 *   <dialog data-static>                     tidak tertutup oleh klik latar atau Esc
 */
export const LOCK_CLASS = 'erp-scroll-lock';

export function install(doc = document) {
    const openers = new WeakMap();

    const resolve = (target) => {
        if (!target) return null;
        if (typeof target === 'string') return doc.getElementById(target.replace(/^#/, ''));
        return target;
    };
    const fire = (el, name, detail) => {
        el.dispatchEvent(new CustomEvent(name, { bubbles: true, detail }));
    };
    const openCount = () => doc.querySelectorAll('dialog[open]').length;
    const syncLock = () => doc.documentElement.classList.toggle(LOCK_CLASS, openCount() > 0);

    function open(target, opener = null) {
        const dialog = resolve(target);
        if (!dialog || dialog.open) return dialog;
        openers.set(dialog, opener || doc.activeElement);
        dialog.showModal();
        syncLock();
        fire(dialog, 'erp:dialog-open', { opener });
        return dialog;
    }

    function close(target, returnValue) {
        const dialog = resolve(target);
        if (dialog && dialog.open) dialog.close(returnValue);
        return dialog;
    }

    function toggle(target, opener) {
        const dialog = resolve(target);
        return dialog && dialog.open ? close(dialog) : open(dialog, opener);
    }

    doc.addEventListener('click', (event) => {
        const el = event.target;
        if (!el || !el.closest) return;

        const trigger = el.closest('[data-erp-open]');
        if (trigger) {
            event.preventDefault();
            open(trigger.getAttribute('data-erp-open'), trigger);
            return;
        }

        const closer = el.closest('[data-erp-close]');
        if (closer) {
            const dialog = closer.closest('dialog');
            if (dialog) {
                event.preventDefault();
                close(dialog);
            }
            return;
        }

        // Klik pada ::backdrop melaporkan target = elemen <dialog> itu sendiri.
        if (el.tagName === 'DIALOG' && el.hasAttribute('data-erp-dialog') && !el.hasAttribute('data-static')) {
            close(el);
        }
    });

    // Esc memicu `cancel`; batalkan untuk dialog static.
    doc.addEventListener('cancel', (event) => {
        const el = event.target;
        if (el && el.tagName === 'DIALOG' && el.hasAttribute('data-static')) event.preventDefault();
    }, true);

    // `close` tidak bubble, jadi dengarkan di fase capture.
    doc.addEventListener('close', (event) => {
        const el = event.target;
        if (!el || el.tagName !== 'DIALOG') return;
        syncLock();
        fire(el, 'erp:dialog-close', { returnValue: el.returnValue });
        const opener = openers.get(el);
        openers.delete(el);
        if (opener && opener.isConnected && typeof opener.focus === 'function') opener.focus({ preventScroll: true });
    }, true);

    return { open, close, toggle, get: resolve };
}
