(function (root, factory) {
    const api = factory();
    if (typeof module === 'object' && module.exports) module.exports = api;
    if (root) root.DeliveryOrderFilterState = api;
})(typeof window !== 'undefined' ? window : globalThis, function () {
    'use strict';

    const defaults = Object.freeze({ branch: 'all', delivery: 'all', payment: 'all', dateRange: '' });
    const keys = Object.keys(defaults);
    const copy = value => ({ ...value });

    function normalize(value = {}) {
        return keys.reduce((state, key) => {
            state[key] = String(value[key] ?? defaults[key]);
            return state;
        }, {});
    }

    function create(initial = {}) {
        const resetDefaults = { ...defaults, branch: String(initial.branch ?? defaults.branch) };
        let applied = normalize(initial);
        let pending = copy(applied);
        const snapshot = value => copy(value);

        return {
            get applied() { return snapshot(applied); },
            get pending() { return snapshot(pending); },
            open() {
                pending = copy(applied);
                return snapshot(pending);
            },
            setPending(key, value) {
                if (keys.includes(key)) pending[key] = String(value ?? '');
                return snapshot(pending);
            },
            apply() {
                applied = copy(pending);
                return snapshot(applied);
            },
            reset() {
                applied = copy(resetDefaults);
                pending = copy(resetDefaults);
                return snapshot(applied);
            },
            discard() {
                pending = copy(applied);
                return snapshot(pending);
            },
            setApplied(key, value) {
                if (keys.includes(key)) applied[key] = String(value ?? '');
                pending = copy(applied);
                return snapshot(applied);
            }
        };
    }

    function createDebouncedSearch(callback, delay = 250) {
        let timeoutId = null;
        return {
            schedule(query) {
                if (timeoutId !== null) clearTimeout(timeoutId);
                timeoutId = setTimeout(() => {
                    timeoutId = null;
                    callback(String(query ?? ''));
                }, delay);
            },
            cancel() {
                if (timeoutId !== null) clearTimeout(timeoutId);
                timeoutId = null;
            }
        };
    }

    function bindDesktopDismissal(viewport, isOpen, dismiss) {
        const onChange = event => {
            const isMobile = event.matches ?? viewport.matches;
            if (!isMobile && isOpen()) dismiss();
        };
        if (viewport.addEventListener) {
            viewport.addEventListener('change', onChange);
        } else {
            viewport.addListener(onChange);
        }
        return () => {
            if (viewport.removeEventListener) {
                viewport.removeEventListener('change', onChange);
            } else {
                viewport.removeListener(onChange);
            }
        };
    }

    return { create, createDebouncedSearch, bindDesktopDismissal };
});
