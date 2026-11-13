/*
 * ERP picker: a searchable dropdown, opened right under (or above) its field, that replaces the look of a select2 dropdown.
 * The original <select> stays the source of truth: choosing an item writes to it and fires the
 * same select2 events the page handlers already listen to, so ids, names and handlers are untouched.
 */
(function (root, factory) {
    if (typeof module === 'object' && module.exports) module.exports = factory();
    else root.ErpPicker = factory();
})(typeof self !== 'undefined' ? self : this, function () {
    'use strict';

    function normalizeItems(rows, map) {
        var list = Array.isArray(rows) ? rows : (rows && Array.isArray(rows.results) ? rows.results : []);
        var items = [];
        list.forEach(function (row) {
            var mapped = map ? map(row) : row;
            if (!mapped || mapped.id === undefined || mapped.id === null || mapped.id === '') return;
            var item = { id: String(mapped.id), text: String(mapped.text == null ? '' : mapped.text), meta: mapped.meta == null ? '' : String(mapped.meta) };
            // Extra fields (tier, address, stock...) that page handlers read from the select2 data object.
            if (mapped.data !== undefined) item.data = mapped.data;
            items.push(item);
        });
        return items;
    }

    function filterItems(items, term) {
        var needle = String(term || '').trim().toLowerCase();
        if (!needle) return items.slice();
        return items.filter(function (item) {
            return (item.text + ' ' + item.meta).toLowerCase().indexOf(needle) !== -1;
        });
    }

    function optionItems(select) {
        var items = [];
        Array.prototype.forEach.call((select && select.options) || [], function (option) {
            if (option.value === '' || option.value == null) return;
            items.push({ id: String(option.value), text: String(option.text).trim(), meta: '' });
        });
        return items;
    }

    function selectedLabel(select) {
        if (!select || select.value === '' || select.value == null) return '';
        var option = select.selectedOptions && select.selectedOptions[0];
        return option ? String(option.text).trim() : '';
    }

    /* ---------- browser only ---------- */
    var dialog = null;
    var active = null;

    function el(tag, className, text) {
        var node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    }

    function buildDialog() {
        // One floating box, positioned by place(): search, list, empty message, "clear" button.
        var overlay = el('div', 'erp-picker');
        overlay.hidden = true;
        overlay.setAttribute('role', 'dialog');
        var searchWrap = el('div', 'erp-picker__search');
        var searchIcon = el('i', 'ph ph-magnifying-glass');
        var search = el('input');
        search.type = 'search';
        search.autocomplete = 'off';
        searchWrap.append(searchIcon, search);
        var list = el('ul', 'erp-picker__list');
        list.setAttribute('role', 'listbox');
        var empty = el('p', 'erp-picker__empty');
        empty.hidden = true;
        var clear = el('button', 'erp-picker__clear', 'Hapus pilihan');
        clear.type = 'button';
        clear.hidden = true;
        overlay.append(searchWrap, list, empty, clear);
        document.body.append(overlay);

        overlay.addEventListener('keydown', function (event) {
            if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp') return;
            var rows = Array.prototype.slice.call(list.querySelectorAll('.erp-picker__item'));
            if (!rows.length) return;
            event.preventDefault();
            var index = rows.indexOf(document.activeElement);
            if (event.key === 'ArrowDown') index = index + 1 >= rows.length ? 0 : index + 1;
            else if (index <= 0) { search.hidden || searchWrap.hidden ? rows[rows.length - 1].focus() : search.focus(); return; }
            else index -= 1;
            rows[index].focus();
        });
        return { overlay: overlay, searchWrap: searchWrap, search: search, list: list, empty: empty, clear: clear };
    }

    var MAX_HEIGHT = 320;
    var GAP = 4;
    var MARGIN = 8;

    /*
     * Where the dropdown goes for a field rectangle, a viewport and the height its content wants (pure, so it is testable):
     * as wide as the field (at least 220px) and kept on screen; under the field when there is room, above it otherwise.
     */
    function computePlacement(field, vw, vh, natural) {
        var width = Math.min(Math.max(field.width, 220), vw - MARGIN * 2);
        var below = vh - field.bottom - GAP - MARGIN;
        var above = field.top - GAP - MARGIN;
        var openBelow = below >= Math.min(natural, 220) || below >= above;
        var height = Math.max(120, Math.min(natural, openBelow ? below : above));
        var boxHeight = Math.min(natural, height);
        return {
            width: width,
            left: Math.min(Math.max(field.left, MARGIN), vw - width - MARGIN),
            top: openBelow ? field.bottom + GAP : field.top - GAP - boxHeight,
            maxHeight: height,
            openBelow: openBelow
        };
    }

    function place() {
        if (!dialog || dialog.overlay.hidden || !active) return;
        var box = dialog.overlay;
        var field = active.anchor.getBoundingClientRect();
        var vh = window.innerHeight;
        if (field.bottom < 0 || field.top > vh) { closeDialog(); return; } // the field scrolled out of sight

        box.style.maxHeight = 'none';
        box.style.top = '0px';
        box.style.left = '0px';
        // position:fixed is measured from a transformed ancestor (sheets, drawers) instead of the viewport: find that origin.
        var origin = box.getBoundingClientRect();
        var spot = computePlacement(field, document.documentElement.clientWidth, vh, Math.min(box.scrollHeight, MAX_HEIGHT));
        box.style.width = spot.width + 'px';
        box.style.maxHeight = spot.maxHeight + 'px';
        box.style.left = (spot.left - origin.left) + 'px';
        box.style.top = (spot.top - origin.top) + 'px';
        box.classList.toggle('is-above', !spot.openBelow);
    }

    function onOutside(event) {
        if (!active || dialog.overlay.contains(event.target) || active.trigger.contains(event.target)) return;
        closeDialog(false);
    }

    // Escape closes the dropdown only, not the sheet or modal the field sits in; leaving it with Tab closes it too.
    function onKey(event) {
        if (event.key !== 'Escape' || !active) return;
        event.stopPropagation();
        event.preventDefault();
        closeDialog();
    }

    function onScroll(event) {
        if (dialog && dialog.overlay.contains(event.target)) return; // scrolling the list itself
        place();
    }

    function closeDialog(returnFocus) {
        if (!dialog || dialog.overlay.hidden) return;
        dialog.overlay.hidden = true;
        document.removeEventListener('pointerdown', onOutside, true);
        document.removeEventListener('focusin', onOutside, true);
        document.removeEventListener('keydown', onKey, true);
        window.removeEventListener('resize', place);
        window.removeEventListener('scroll', onScroll, true);
        var trigger = active && active.trigger;
        active = null;
        if (trigger) {
            trigger.setAttribute('aria-expanded', 'false');
            if (returnFocus !== false) trigger.focus();
        }
    }

    function fire(select, type, data) {
        var jq = window.jQuery;
        if (jq) {
            var $select = jq(select);
            $select.trigger('change');
            if (type) $select.trigger(jq.Event(type, { params: { data: data } }));
        } else {
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    function choose(item) {
        var select = active.select;
        var jq = window.jQuery;
        var instance = jq && jq(select).data('select2');
        if (instance) {
            // Same path select2 takes for a click on its own result: creates the option, keeps the data
            // for select2('data'), fires change and select2:select.
            var data = Object.assign({}, item.data || {}, { id: item.id, text: item.text });
            instance.trigger('select', { data: data });
            closeDialog();
            return;
        }
        var exists = Array.prototype.some.call(select.options, function (option) { return option.value === item.id; });
        if (!exists) select.append(new Option(item.text, item.id, true, true));
        if (jq) jq(select).val(item.id); else select.value = item.id;
        fire(select, 'select2:select', { id: item.id, text: item.text });
        closeDialog();
    }

    function renderList(items, append) {
        if (!append) dialog.list.replaceChildren();
        var current = active.select.value;
        items.forEach(function (item) {
            var row = el('li', 'erp-picker__item' + (item.id === current ? ' is-selected' : ''));
            row.setAttribute('role', 'option');
            row.setAttribute('aria-selected', String(item.id === current));
            row.tabIndex = 0;
            var text = el('div', 'erp-picker__text');
            text.append(el('strong', '', item.text));
            if (item.meta) text.append(el('span', '', item.meta));
            row.append(text);
            if (item.id === current) row.append(el('i', 'ph-bold ph-check'));
            row.addEventListener('click', function () { choose(item); });
            row.addEventListener('keydown', function (event) { if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); choose(item); } });
            dialog.list.append(row);
        });
        dialog.empty.hidden = dialog.list.childElementCount > 0;
        place();
    }

    function load(term, page) {
        var options = active.options;
        var request = ++active.request;
        var append = page > 1;
        if (!options.url) {
            // Static source: the options already inside the select.
            active.page = 1;
            active.more = false;
            active.items = optionItems(active.select);
            // A short fixed list needs no search box (and no on-screen keyboard on a phone).
            dialog.searchWrap.hidden = options.search === false || active.items.length <= 7;
            renderList(filterItems(active.items, term), false);
            return Promise.resolve();
        }
        var url = options.url + (options.url.indexOf('?') === -1 ? '?' : '&') + (options.searchParam || 'search') + '=' + encodeURIComponent(term || '');
        if (options.paginate) url += '&page=' + (page || 1);
        if (options.params) {
            var extra = options.params() || {};
            Object.keys(extra).forEach(function (key) { url += '&' + encodeURIComponent(key) + '=' + encodeURIComponent(extra[key] == null ? '' : extra[key]); });
        }
        active.loading = true;
        dialog.overlay.classList.add('is-loading');
        return fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' }, credentials: 'same-origin' })
            .then(function (response) { if (!response.ok) throw new Error('HTTP ' + response.status); return response.json(); })
            .then(function (json) {
                if (!active || request !== active.request) return;
                var items = normalizeItems(json, options.map);
                if (!append && options.prepend) items = filterItems(normalizeItems(options.prepend), term).concat(items);
                active.page = page || 1;
                active.more = !!(json && json.pagination && json.pagination.more);
                active.items = append ? active.items.concat(items) : items;
                renderList(options.localFilter ? filterItems(items, term) : items, append);
            })
            .catch(function () {
                if (!active || request !== active.request) return;
                dialog.list.replaceChildren();
                dialog.empty.textContent = 'Gagal memuat data. Coba lagi.';
                dialog.empty.hidden = false;
            })
            .then(function () {
                if (!active || request !== active.request) return;
                active.loading = false;
                dialog.overlay.classList.remove('is-loading');
            });
    }

    function openDialog(select, trigger, options) {
        if (!dialog) dialog = buildDialog();
        // The dropdown is as wide as the whole field. Usually that is the trigger itself; a composite field (a trigger
        // next to an "add" button inside one bordered box) names its wrapper with options.anchor.
        var anchor = (options.anchor && trigger.closest(options.anchor)) || trigger;
        active = { select: select, trigger: trigger, anchor: anchor, options: options, request: 0, items: [], timer: null, page: 1, more: false, loading: false };
        dialog.overlay.setAttribute('aria-label', options.title || 'Pilih');
        dialog.search.placeholder = options.placeholder || 'Cari...';
        dialog.search.value = '';
        dialog.searchWrap.hidden = options.search === false; // search:false = a short list that needs no search box
        dialog.empty.textContent = options.emptyText || 'Data tidak ditemukan';
        dialog.empty.hidden = true;
        dialog.clear.hidden = !(options.clearable && select.value !== '');
        // Bootstrap traps focus inside the open modal, so the box lives inside it when the select does.
        var host = select.closest('.modal') || document.body;
        if (dialog.overlay.parentNode !== host) host.append(dialog.overlay);
        dialog.overlay.hidden = false;
        trigger.setAttribute('aria-expanded', 'true');
        place();
        document.addEventListener('pointerdown', onOutside, true);
        document.addEventListener('focusin', onOutside, true);
        document.addEventListener('keydown', onKey, true);
        window.addEventListener('resize', place);
        window.addEventListener('scroll', onScroll, true);

        dialog.search.oninput = function () {
            clearTimeout(active.timer);
            var term = dialog.search.value;
            active.timer = setTimeout(function () { load(term, 1); }, 250);
        };
        dialog.clear.onclick = function () {
            var jq = window.jQuery;
            if (jq) jq(select).val(null); else select.value = '';
            fire(select, 'select2:clear', {});
            closeDialog();
        };
        dialog.list.onscroll = function () {
            if (!active || !active.more || active.loading) return;
            if (dialog.list.scrollTop + dialog.list.clientHeight >= dialog.list.scrollHeight - 40) load(dialog.search.value, active.page + 1);
        };
        load('', 1);
        // Typing is the point on a desktop; on a phone it would only raise the keyboard over the list.
        var fine = !window.matchMedia || window.matchMedia('(pointer: fine)').matches;
        if (!dialog.searchWrap.hidden && fine) dialog.search.focus();
    }

    function attach(selector, options) {
        var select = typeof selector === 'string' ? document.querySelector(selector) : selector;
        if (!select || select.dataset.erpPicker === 'true') return null;
        select.dataset.erpPicker = 'true';
        select.classList.add('erp-picker-source');

        // options.trigger: a page-made button (its own look and text) opens the dropdown instead of the standard one.
        var trigger = options.trigger || el('button', 'erp-picker__trigger');
        trigger.type = 'button';
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');
        var label = el('span', 'erp-picker__label');
        if (!options.trigger) {
            trigger.append(label, el('i', 'ph-bold ph-caret-down'));
            select.insertAdjacentElement('afterend', trigger);
        }

        var sync = function () {
            if (options.trigger) { trigger.disabled = select.disabled; return; }
            var value = selectedLabel(select);
            label.textContent = value || options.placeholderTrigger || 'Pilih';
            label.classList.toggle('is-placeholder', !value);
            trigger.disabled = select.disabled;
        };
        select.addEventListener('change', sync);
        if (window.jQuery) window.jQuery(select).on('change', sync);
        trigger.addEventListener('click', function () {
            if (select.disabled) return;
            if (active && active.trigger === trigger) { closeDialog(); return; } // a second click closes it
            openDialog(select, trigger, options);
        });
        sync();
        return { sync: sync };
    }

    return { normalizeItems: normalizeItems, filterItems: filterItems, optionItems: optionItems, selectedLabel: selectedLabel, computePlacement: computePlacement, attach: attach };
});
