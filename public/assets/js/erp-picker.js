/*
 * ERP picker: a searchable modal that replaces the look of a select2 dropdown.
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
        var overlay = el('div', 'erp-picker');
        overlay.hidden = true;
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        var backdrop = el('div', 'erp-picker__backdrop');
        var panel = el('div', 'erp-picker__panel');
        var head = el('div', 'erp-picker__head');
        var titles = el('div', 'erp-picker__titles');
        var title = el('h3', 'erp-picker__title');
        var subtitle = el('p', 'erp-picker__subtitle');
        titles.append(title, subtitle);
        var close = el('button', 'erp-picker__close');
        close.type = 'button';
        close.setAttribute('aria-label', 'Tutup');
        close.append(el('i', 'ph-bold ph-x'));
        head.append(titles, close);
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
        panel.append(head, searchWrap, list, empty, clear);
        overlay.append(backdrop, panel);
        document.body.append(overlay);

        backdrop.addEventListener('click', closeDialog);
        close.addEventListener('click', closeDialog);
        overlay.addEventListener('keydown', function (event) { if (event.key === 'Escape') closeDialog(); });
        return { overlay: overlay, title: title, subtitle: subtitle, search: search, list: list, empty: empty, clear: clear };
    }

    function closeDialog() {
        if (!dialog || dialog.overlay.hidden) return;
        dialog.overlay.hidden = true;
        document.documentElement.classList.remove('erp-picker-open');
        var trigger = active && active.trigger;
        active = null;
        if (trigger) trigger.focus();
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
        active = { select: select, trigger: trigger, options: options, request: 0, items: [], timer: null, page: 1, more: false, loading: false };
        dialog.title.textContent = options.title || 'Pilih';
        dialog.subtitle.textContent = options.subtitle || '';
        dialog.subtitle.hidden = !options.subtitle;
        dialog.search.placeholder = options.placeholder || 'Cari...';
        dialog.search.value = '';
        dialog.empty.textContent = options.emptyText || 'Data tidak ditemukan';
        dialog.empty.hidden = true;
        dialog.clear.hidden = !(options.clearable && select.value !== '');
        // Bootstrap traps focus inside the open modal, so the overlay lives inside it when the select does.
        var host = select.closest('.modal') || document.body;
        if (dialog.overlay.parentNode !== host) host.append(dialog.overlay);
        dialog.overlay.hidden = false;
        document.documentElement.classList.add('erp-picker-open');

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
        dialog.search.focus();
    }

    function attach(selector, options) {
        var select = typeof selector === 'string' ? document.querySelector(selector) : selector;
        if (!select || select.dataset.erpPicker === 'true') return null;
        select.dataset.erpPicker = 'true';
        select.classList.add('erp-picker-source');

        var trigger = el('button', 'erp-picker__trigger');
        trigger.type = 'button';
        var label = el('span', 'erp-picker__label');
        var action = null;
        if (options.actionLabels) action = el('span', 'erp-picker__action');
        trigger.append(label);
        if (action) trigger.append(action);
        trigger.append(el('i', 'ph-bold ph-caret-down'));
        select.insertAdjacentElement('afterend', trigger);

        var sync = function () {
            var value = selectedLabel(select);
            label.textContent = value || options.placeholderTrigger || 'Pilih';
            label.classList.toggle('is-placeholder', !value);
            if (action) action.textContent = value ? options.actionLabels[1] : options.actionLabels[0];
            trigger.disabled = select.disabled;
        };
        select.addEventListener('change', sync);
        if (window.jQuery) window.jQuery(select).on('change', sync);
        trigger.addEventListener('click', function () { if (!select.disabled) openDialog(select, trigger, options); });
        sync();
        return { sync: sync };
    }

    return { normalizeItems: normalizeItems, filterItems: filterItems, optionItems: optionItems, selectedLabel: selectedLabel, attach: attach };
});
