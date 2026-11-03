(function (root, factory) {
    const api = factory();
    if (typeof module === 'object' && module.exports) module.exports = api;
    if (root) root.DeliveryOrderReferenceDates = api;
})(typeof window !== 'undefined' ? window : globalThis, function () {
    'use strict';
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    const iso = date => date.toISOString().slice(0, 10);
    function valid(value) {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(value || '')) return false;
        const date = new Date(value + 'T00:00:00Z');
        return !Number.isNaN(date.getTime()) && iso(date) === value;
    }
    function today(timezone, now = new Date()) {
        const parts = new Intl.DateTimeFormat('en-CA', { timeZone: timezone, year: 'numeric', month: '2-digit', day: '2-digit' })
            .formatToParts(now).reduce((date, part) => { date[part.type] = part.value; return date; }, {});
        return `${parts.year}-${parts.month}-${parts.day}`;
    }
    function week(timezone, now = new Date()) {
        const start = new Date(today(timezone, now) + 'T00:00:00Z');
        start.setUTCDate(start.getUTCDate() - (start.getUTCDay() || 7) + 1);
        const end = new Date(start); end.setUTCDate(start.getUTCDate() + 6);
        return [iso(start), iso(end)];
    }
    function range(value) {
        const [start, end] = String(value || '').split(' to ');
        return [valid(start) ? start : '', valid(end) ? end : ''];
    }
    function format(value) {
        if (!valid(value)) return 'Pilih Tanggal';
        const [year, month, day] = value.split('-').map(Number);
        return `${day} ${months[month - 1]} ${year}`;
    }
    function grid(year, month) {
        const first = new Date(Date.UTC(year, month, 1));
        const count = new Date(Date.UTC(year, month + 1, 0)).getUTCDate();
        return [...Array((first.getUTCDay() + 6) % 7).fill(null), ...Array.from({ length: count }, (_, i) => iso(new Date(Date.UTC(year, month, i + 1))))];
    }
    function attach({ root, state, timezone }) {
        const body = root.querySelector('.delivery-order-filter-sheet__body');
        const calendar = root.querySelector('[data-delivery-calendar]');
        const sections = [...body.children].filter(section => section !== calendar);
        const heading = calendar.querySelector('[data-delivery-calendar-heading]');
        const days = calendar.querySelector('[data-delivery-calendar-weekdays]');
        const container = calendar.querySelector('[data-delivery-calendar-grid]');
        let year, month, view = 'date', target = 'start', trigger = null, frame = null;
        function focus(element) {
            if (frame !== null) cancelAnimationFrame(frame);
            frame = requestAnimationFrame(() => {
                frame = null;
                if (!root.hidden && element?.getClientRects().length) {
                    element.focus({ preventScroll: true });
                    element.scrollIntoView({ block: 'nearest' });
                }
            });
        }
        function sync() {
            const [start, end] = range(state.pending.dateRange);
            root.querySelector('[data-delivery-date-label="start"]').textContent = format(start);
            root.querySelector('[data-delivery-date-label="end"]').textContent = format(end);
            root.querySelector('[data-delivery-date-clear]').hidden = !start && !end;
        }
        function close() {
            if (frame !== null) cancelAnimationFrame(frame);
            frame = null;
            calendar.hidden = true;
            sections.forEach(section => { section.hidden = false; });
            root.querySelectorAll('[data-delivery-date]').forEach(button => button.setAttribute('aria-expanded', 'false'));
            sync();
        }
        function render() {
            const [start, end] = range(state.pending.dateRange);
            days.hidden = view !== 'date';
            container.classList.toggle('delivery-order-calendar__grid--choices', view !== 'date');
            container.replaceChildren();
            const firstYear = Math.floor(year / 12) * 12;
            heading.textContent = view === 'date' ? `${months[month]} ${year}` : view === 'month' ? String(year) : `${firstYear} – ${firstYear + 11}`;
            const values = view === 'date' ? grid(year, month) : view === 'month' ? months : Array.from({ length: 12 }, (_, i) => firstYear + i);
            values.forEach((value, index) => {
                const element = document.createElement(value === null ? 'span' : 'button');
                if (value === null) element.setAttribute('aria-hidden', 'true');
                else {
                    element.type = 'button';
                    element.dataset.deliveryCalendarPick = view === 'month' ? index : value;
                    element.textContent = view === 'date' ? String(Number(value.slice(-2))) : String(value);
                    element.setAttribute('aria-label', view === 'date' ? format(value) : String(value));
                    const selected = view === 'date' ? value === start || value === end : view === 'month' ? index === month : value === year;
                    element.setAttribute('aria-pressed', String(selected));
                    element.classList.toggle('is-selected', selected);
                    if (view === 'date' && start && end && value > start && value < end) element.classList.add('is-in-range');
                }
                container.append(element);
            });
        }
        function save(start, end) {
            state.setPending('dateRange', end ? `${start} to ${end}` : start);
            sync();
        }
        function onClick(event) {
            const button = event.target.closest('button');
            if (!button || !root.contains(button)) return;
            if (button.hasAttribute('data-delivery-date')) {
                target = button.dataset.deliveryDate;
                trigger = button;
                const selected = range(state.pending.dateRange)[target === 'start' ? 0 : 1] || today(timezone);
                [year, month] = selected.split('-').map(Number); month -= 1;
                view = 'date';
                sections.forEach(section => { section.hidden = true; });
                calendar.hidden = false;
                button.setAttribute('aria-expanded', 'true');
                render(); focus(heading);
            } else if (button.hasAttribute('data-delivery-date-clear')) {
                state.setPending('dateRange', ''); sync();
                focus(root.querySelector('[data-delivery-date="start"]'));
            } else if (button.hasAttribute('data-delivery-date-preset')) {
                const value = button.dataset.deliveryDatePreset === 'week' ? week(timezone).join(' to ') : today(timezone);
                state.setPending('dateRange', value); sync();
            } else if (button.hasAttribute('data-delivery-calendar-heading')) {
                view = view === 'date' ? 'month' : view === 'month' ? 'year' : 'date'; render(); focus(heading);
            } else if (button.hasAttribute('data-delivery-calendar-move')) {
                const delta = Number(button.dataset.deliveryCalendarMove);
                if (view === 'date') {
                    const date = new Date(Date.UTC(year, month + delta, 1)); year = date.getUTCFullYear(); month = date.getUTCMonth();
                } else year += delta * (view === 'year' ? 12 : 1);
                render();
            } else if (button.hasAttribute('data-delivery-calendar-pick')) {
                const value = button.dataset.deliveryCalendarPick;
                if (view === 'year') { year = Number(value); view = 'month'; render(); focus(heading); }
                else if (view === 'month') { month = Number(value); view = 'date'; render(); focus(heading); }
                else {
                    let [start, end] = range(state.pending.dateRange);
                    if (target === 'start' || !start) {
                        start = value; if (end && end < start) end = '';
                        save(start, end); target = 'end'; render(); focus(heading);
                    } else {
                        end = value; if (end < start) [start, end] = [end, start];
                        save(start, end); close(); focus(trigger);
                    }
                }
            } else if (button.hasAttribute('data-delivery-calendar-back')) { close(); focus(trigger); }
        }
        root.addEventListener('click', onClick);
        sync();
        return { open: close, close, sync, destroy() { root.removeEventListener('click', onClick); close(); } };
    }
    return { today, week, range, format, grid, attach };
});
