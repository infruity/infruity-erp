/* Shared focus lifecycle for the application's custom Alpine sheets/drawers.
 * Native <dialog> and Bootstrap modals keep their own focus implementation. */
(() => {
    'use strict';
    if (window.erpOverlays) return;
    const selector = '[data-erp-modal]';
    const focusableSelector = 'a[href],button,input:not([type="hidden"]),select,textarea,[tabindex]:not([tabindex="-1"])';
    const openers = new WeakMap();
    const inerted = new Map();
    let active = null;
    let scheduled = false;
    let restoring = false;
    let suspended = false;
    const pluginModal = () => document.querySelector('dialog[open],.modal.show,.swal2-container');
    const visible = element => element.isConnected && !element.hidden && element.getClientRects().length > 0 && getComputedStyle(element).visibility !== 'hidden';
    const focusable = modal => [...modal.querySelectorAll(focusableSelector)].filter(element => visible(element) && !element.disabled && !element.closest('[inert]'));
    function clearInert() {
        inerted.forEach((wasInert, element) => { element.inert = wasInert; });
        inerted.clear();
    }
    function isolate(modal) {
        let child = modal;
        while (child && child !== document.body) {
            const parent = child.parentElement;
            if (!parent) break;
            [...parent.children].forEach(sibling => {
                if (sibling === child || /^(SCRIPT|STYLE|LINK|TEMPLATE)$/.test(sibling.tagName)) return;
                if (!inerted.has(sibling)) inerted.set(sibling, sibling.inert);
                sibling.inert = true;
            });
            child = parent;
        }
    }
    function focusInside(modal) {
        const invalid = modal.querySelector('[aria-invalid="true"], [data-erp-initial-focus], [autofocus]');
        const target = invalid && visible(invalid) && !invalid.disabled ? invalid : focusable(modal)[0] || modal;
        if (!target.hasAttribute('tabindex') && target === modal) target.tabIndex = -1;
        target.focus({ preventScroll: true });
    }
    function refresh() {
        scheduled = false;
        // Bootstrap, SweetAlert and native dialogs have their own trap. Release
        // inherited inert state while one is above a custom sheet, then resume.
        if (pluginModal()) { clearInert(); suspended = true; return; }
        if (suspended) {
            suspended = false;
            if (active && visible(active)) isolate(active);
        }
        const candidates = [...document.querySelectorAll(selector)].filter(element => visible(element) && !element.closest('dialog,.modal'));
        candidates.sort((a, b) => (Number(getComputedStyle(a).zIndex) || (a.firstElementChild ? Number(getComputedStyle(a.firstElementChild).zIndex) : 0) || 0) - (Number(getComputedStyle(b).zIndex) || (b.firstElementChild ? Number(getComputedStyle(b.firstElementChild).zIndex) : 0) || 0));
        const next = candidates[candidates.length - 1] || null;
        if (next === active) return;
        const previous = active;
        clearInert();
        active = next;
        if (next) {
            if (!openers.has(next)) {
                const focused = document.activeElement;
                openers.set(next, previous && previous.contains(focused) ? openers.get(previous) : focused);
            }
            if (!next.matches('[role=dialog]') && !next.querySelector('[role=dialog]')) { next.setAttribute('role', 'dialog'); next.setAttribute('aria-modal', 'true'); }
            isolate(next);
            requestAnimationFrame(() => { if (active === next && !next.contains(document.activeElement)) focusInside(next); });
        } else if (previous) {
            const opener = openers.get(previous);
            openers.delete(previous);
            if (opener?.isConnected && visible(opener) && !opener.closest('[inert]')) {
                restoring = true;
                opener.focus({ preventScroll: true });
                restoring = false;
            }
        }
        if (previous && previous !== next && !visible(previous)) openers.delete(previous);
    }
    function schedule() {
        if (!scheduled) { scheduled = true; requestAnimationFrame(refresh); }
    }
    document.addEventListener('keydown', event => {
        if (!active || pluginModal() || event.key !== 'Tab' || event.defaultPrevented) return;
        const controls = focusable(active);
        const first = controls[0];
        const last = controls[controls.length - 1];
        if (!first) { event.preventDefault(); focusInside(active); return; }
        if (event.shiftKey && (document.activeElement === first || !active.contains(document.activeElement))) {
            event.preventDefault(); last.focus();
        } else if (!event.shiftKey && (document.activeElement === last || !active.contains(document.activeElement))) {
            event.preventDefault(); first.focus();
        }
    }, true);
    document.addEventListener('focusin', event => {
        if (active && !restoring && !active.contains(event.target) && !document.querySelector('dialog[open],.modal.show,.swal2-container')) focusInside(active);
    });
    const start = () => {
        new MutationObserver(schedule).observe(document.body, { subtree: true, childList: true, attributes: true, attributeFilter: ['style', 'hidden', 'class', 'open'] });
        refresh();
    };
    window.erpOverlays = { refresh: schedule };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, { once: true });
    else start();
})();
