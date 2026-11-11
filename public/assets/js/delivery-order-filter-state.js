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

    // What the list is currently filtered by. The phone sheet (shared filter-sheet component) and the
    // inline desktop controls both write here through setApplied.
    function create(initial = {}) {
        const resetDefaults = { ...defaults, branch: String(initial.branch ?? defaults.branch) };
        let applied = normalize(initial);
        const snapshot = value => copy(value);

        return {
            get applied() { return snapshot(applied); },
            setApplied(key, value) {
                if (keys.includes(key)) applied[key] = String(value ?? '');
                return snapshot(applied);
            },
            reset() {
                applied = copy(resetDefaults);
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

    return { create, createDebouncedSearch };
});
