/**
 * Alpine state of <x-erp.filter-sheet>.
 *
 * The sheet edits a draft (branch, status, start, end). Nothing reaches the page
 * until "Terapkan": the page then receives an `erp-filter-apply` window event.
 * The page opens the sheet with an `erp-filter-open` window event that carries the
 * values currently applied, so the draft always starts from what the list shows.
 *
 *   open:  window event 'erp-filter-open'  detail { id, values: { branch, status, start, end } }
 *   apply: window event 'erp-filter-apply' detail { id, values: { branch, status, start, end } }
 *   close: window event 'erp-filter-close' detail { id }   (always fired, apply included)
 *
 * Extra choice groups: config.groups = [{key, default}] (chips or radio rows, rendered by the
 * component). Their chosen values travel as values.extra = {key: value}; the key is absent from
 * the event when no group is configured, so pages without groups see the same shape as before.
 *
 * With desktop 'hidden' (the default) the sheet is a phone sheet only: it refuses to open from 768px up
 * and closes itself when the window grows past that, so a page's own desktop filter never fights it.
 *
 * Group extras: a group can be shown only while other groups have given values (showWhen, e.g. the
 * weekly pickers only for "Mingguan"), and choosing a value can reset dependent groups to their default
 * (resets, e.g. picking another year clears the picked week).
 *
 * Optional search box: config.search = true adds a text field; its draft travels as values.search.
 * The open event may also carry detail.title / detail.subtitle to rename the sheet for that opening
 * (a page whose filter depends on a tab); the next opening without them restores the defaults.
 *
 * Config: id, branches, showBranchAll, defaultBranch (what "no branch filter" means, e.g. the one
 * branch of a single-branch user; Reset returns to it), timezone (dates for "Hari Ini" / "Minggu Ini"
 * are read in this zone, not the browser's), weekStart ('sunday' | 'monday'),
 * resetApplies (Reset also applies and closes, instead of only clearing the draft).
 */
(function (root) {
    const MONTHS = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    const SHORT_MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    const WEEKDAYS = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    const DESKTOP_QUERY = '(min-width: 768px)';
    const FOCUSABLE = 'button:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';

    const pad = (number) => String(number).padStart(2, '0');
    const toIsoDate = (date) => `${date.getUTCFullYear()}-${pad(date.getUTCMonth() + 1)}-${pad(date.getUTCDate())}`;

    /** Today as YYYY-MM-DD in the given IANA zone (the browser's zone when none is given). */
    function today(timeZone, now = new Date()) {
        const parts = new Intl.DateTimeFormat('en-CA', { timeZone: timeZone || undefined, year: 'numeric', month: '2-digit', day: '2-digit' })
            .formatToParts(now)
            .reduce((date, part) => {
                if (part.type !== 'literal') date[part.type] = part.value;
                return date;
            }, {});

        return `${parts.year}-${parts.month}-${parts.day}`;
    }

    /** Monday through Sunday of the current week in the given zone. */
    function week(timeZone, now = new Date()) {
        const [year, month, day] = today(timeZone, now).split('-').map(Number);
        const monday = new Date(Date.UTC(year, month - 1, day));
        monday.setUTCDate(monday.getUTCDate() - (monday.getUTCDay() || 7) + 1);
        const sunday = new Date(monday.getTime());
        sunday.setUTCDate(sunday.getUTCDate() + 6);

        return { start: toIsoDate(monday), end: toIsoDate(sunday) };
    }

    /** Empty cells before the 1st of the month, for a week that starts on Monday or Sunday. */
    function monthGridOffset(year, month, weekStart = 'sunday') {
        const weekday = new Date(Date.UTC(year, month, 1)).getUTCDay();
        return weekStart === 'monday' ? (weekday + 6) % 7 : weekday;
    }

    /** First to last day of the current month in the given zone. */
    function monthRange(timeZone, now = new Date()) {
        const [year, month] = today(timeZone, now).split('-').map(Number);

        return {
            start: toIsoDate(new Date(Date.UTC(year, month - 1, 1))),
            end: toIsoDate(new Date(Date.UTC(year, month, 0))),
        };
    }

    function daysInMonth(year, month) {
        return new Date(Date.UTC(year, month + 1, 0)).getUTCDate();
    }

    function erpFilterSheet(config = {}) {
        const timezone = config.timezone || '';
        const weekStart = config.weekStart === 'monday' ? 'monday' : 'sunday';
        const [startYear, startMonth] = today(timezone).split('-').map(Number);
        const groups = (config.groups || []).map((group) => ({
            key: String(group.key),
            default: String(group.default ?? 'all'),
            resets: (group.resets || []).map(String),
        }));
        const groupDefaults = () => Object.fromEntries(groups.map((group) => [group.key, group.default]));

        return {
            id: config.id || 'filter',
            timezone,
            weekStart,
            resetApplies: Boolean(config.resetApplies),
            defaultBranch: String(config.defaultBranch || 'all'),
            desktop: config.desktop === 'modal' ? 'modal' : 'hidden',
            open: false,
            branch: String(config.defaultBranch || 'all'),
            status: 'all',
            extra: groupDefaults(),
            groups,
            hasSearch: Boolean(config.search),
            query: '',
            headings: null,
            start: '',
            end: '',
            branchSearch: '',
            showBranchAll: Boolean(config.showBranchAll),
            branches: (config.branches || []).map((branch) => ({
                id: String(branch.id),
                name: String(branch.name),
                search: String(branch.name).toLocaleLowerCase('id'),
            })),
            calendarOpen: false,
            calendarView: 'date',
            calendarMonth: startMonth - 1,
            calendarYear: startYear,
            calendarTrigger: null,
            calendarMonths: MONTHS,
            returnFocus: null,
            previousBodyOverflow: '',
            previousMainOverflow: '',

            init() {
                if (this.desktop === 'modal' || typeof root.matchMedia !== 'function') return;
                const viewport = root.matchMedia(DESKTOP_QUERY);
                const onChange = (event) => { if (event.matches && this.open) this.close(); };
                if (viewport.addEventListener) viewport.addEventListener('change', onChange);
                else if (viewport.addListener) viewport.addListener(onChange);
            },
            get phoneOnlyAndDesktopNow() {
                return this.desktop !== 'modal' && typeof root.matchMedia === 'function' && root.matchMedia(DESKTOP_QUERY).matches;
            },
            onOpen(event) {
                const detail = event.detail || {};
                if (detail.id !== this.id) return;
                if (this.phoneOnlyAndDesktopNow) return;
                const values = detail.values || {};
                this.branch = values.branch || this.defaultBranch;
                this.status = values.status || 'all';
                this.extra = { ...groupDefaults(), ...(values.extra || {}) };
                this.query = String(values.search || '');
                this.setHeadings(detail);
                this.start = values.start || '';
                this.end = values.end || '';
                this.branchSearch = '';
                this.calendarOpen = false;
                this.calendarView = 'date';
                this.returnFocus = root.document?.activeElement || null;
                this.lockScroll();
                this.open = true;
                this.$nextTick(() => this.$refs.closeButton?.focus());
            },
            setExtra(key, value) {
                this.extra[key] = String(value);
                const group = this.groups.find((candidate) => candidate.key === key);
                (group?.resets || []).forEach((dependent) => {
                    this.extra[dependent] = this.groups.find((candidate) => candidate.key === dependent)?.default ?? 'all';
                });
            },
            /** True while every given group currently has one of the given values; no condition always matches. */
            matches(condition) {
                if (!condition) return true;
                return Object.entries(condition).every(([key, expected]) => {
                    const allowed = Array.isArray(expected) ? expected.map(String) : [String(expected)];
                    return allowed.includes(String(this.extra[key]));
                });
            },
            setHeadings(detail) {
                const doc = root.document;
                if (!doc?.getElementById) return;
                const title = doc.getElementById(`${this.id}-filter-title`);
                const subtitle = doc.getElementById(`${this.id}-filter-subtitle`);
                if (!this.headings) this.headings = { title: title?.textContent ?? '', subtitle: subtitle?.textContent ?? '' };
                if (title) title.textContent = detail.title ?? this.headings.title;
                if (subtitle) subtitle.textContent = detail.subtitle ?? this.headings.subtitle;
            },
            close() {
                if (!this.open) return;
                this.open = false;
                this.calendarOpen = false;
                this.unlockScroll();
                root.dispatchEvent(new root.CustomEvent('erp-filter-close', { detail: { id: this.id } }));
                const target = this.returnFocus;
                this.returnFocus = null;
                this.$nextTick(() => target?.focus?.());
            },
            reset() {
                this.branch = this.defaultBranch;
                this.status = 'all';
                this.extra = groupDefaults();
                this.query = '';
                this.start = '';
                this.end = '';
                this.branchSearch = '';
                if (this.resetApplies) this.apply();
            },
            apply() {
                const values = { branch: this.branch, status: this.status, start: this.start, end: this.end };
                if (groups.length) values.extra = { ...this.extra };
                if (this.hasSearch) values.search = this.query.trim();
                root.dispatchEvent(new root.CustomEvent('erp-filter-apply', { detail: { id: this.id, values } }));
                this.close();
            },

            lockScroll() {
                const doc = root.document;
                if (!doc) return;
                this.previousBodyOverflow = doc.body.style.overflow;
                const main = doc.querySelector('.erp-main');
                this.previousMainOverflow = main?.style.overflow || '';
                doc.body.style.overflow = 'hidden';
                if (main) main.style.overflow = 'hidden';
            },
            unlockScroll() {
                const doc = root.document;
                if (!doc) return;
                doc.body.style.overflow = this.previousBodyOverflow;
                const main = doc.querySelector('.erp-main');
                if (main) main.style.overflow = this.previousMainOverflow;
            },
            trapFocus(event) {
                const panel = this.$refs.panel;
                if (!panel) return;
                const focusable = Array.from(panel.querySelectorAll(FOCUSABLE)).filter((element) => element.getClientRects().length > 0);
                if (!focusable.length) {
                    event.preventDefault();
                    panel.focus();
                    return;
                }
                const first = focusable[0];
                const last = focusable[focusable.length - 1];
                const active = root.document?.activeElement;
                if (event.shiftKey && active === first) {
                    event.preventDefault();
                    last.focus();
                } else if (!event.shiftKey && active === last) {
                    event.preventDefault();
                    first.focus();
                }
            },

            branchMatches(search) {
                const term = this.branchSearch.trim().toLocaleLowerCase('id');
                return term === '' || String(search).includes(term);
            },
            get visibleBranchCount() {
                const rows = this.branches.filter((branch) => this.branchMatches(branch.search)).length;
                const all = this.showBranchAll && this.branchMatches('semua cabang') ? 1 : 0;
                return rows + all;
            },

            setDateRange(period) {
                const range = period === 'week'
                    ? week(this.timezone)
                    : (period === 'month' ? monthRange(this.timezone) : { start: today(this.timezone), end: today(this.timezone) });
                this.start = range.start;
                this.end = range.end;
                const [year, month] = range.start.split('-').map(Number);
                this.calendarYear = year;
                this.calendarMonth = month - 1;
                this.calendarView = 'date';
            },
            formatDate(value) {
                if (!value) return 'Pilih Tanggal';
                const [year, month, day] = value.split('-').map(Number);
                return `${day} ${SHORT_MONTHS[month - 1]} ${year}`;
            },

            get weekdays() {
                const order = this.weekStart === 'monday' ? [1, 2, 3, 4, 5, 6, 0] : [0, 1, 2, 3, 4, 5, 6];
                return order.map((index) => ({ name: WEEKDAYS[index], initial: WEEKDAYS[index][0] }));
            },
            get calendarHeading() {
                if (this.calendarView === 'month') return String(this.calendarYear);
                if (this.calendarView === 'year') return `${this.calendarYear - 4} - ${this.calendarYear + 7}`;
                return `${MONTHS[this.calendarMonth]} ${this.calendarYear}`;
            },
            get calendarBlankDays() {
                return Array.from({ length: monthGridOffset(this.calendarYear, this.calendarMonth, this.weekStart) }, (_, index) => index);
            },
            get calendarDays() {
                return Array.from({ length: daysInMonth(this.calendarYear, this.calendarMonth) }, (_, index) => index + 1);
            },
            get calendarYears() {
                return Array.from({ length: 12 }, (_, index) => this.calendarYear - 4 + index);
            },
            calendarDate(day) {
                return `${this.calendarYear}-${pad(this.calendarMonth + 1)}-${pad(day)}`;
            },
            openCalendar(trigger, field) {
                this.calendarTrigger = trigger;
                const value = (field === 'end' ? (this.end || this.start) : (this.start || this.end)) || today(this.timezone);
                const [year, month] = value.split('-').map(Number);
                this.calendarYear = year;
                this.calendarMonth = month - 1;
                this.calendarView = 'date';
                this.calendarOpen = true;
                this.focusAfterPaint(() => this.$refs.calendarBack);
            },
            closeCalendar() {
                this.calendarOpen = false;
                this.calendarView = 'date';
                this.focusAfterPaint(() => this.calendarTrigger);
            },
            focusAfterPaint(getTarget) {
                this.$nextTick(() => requestAnimationFrame(() => {
                    const target = getTarget();
                    if (!target || !target.getClientRects().length) return;
                    target.focus({ preventScroll: true });
                    target.scrollIntoView({ block: 'nearest' });
                }));
            },
            moveCalendar(step) {
                if (this.calendarView === 'date') {
                    const date = new Date(Date.UTC(this.calendarYear, this.calendarMonth + step, 1));
                    this.calendarMonth = date.getUTCMonth();
                    this.calendarYear = date.getUTCFullYear();
                } else if (this.calendarView === 'month') {
                    this.calendarYear += step;
                } else {
                    this.calendarYear += step * 12;
                }
            },
            cycleCalendarView() {
                this.calendarView = this.calendarView === 'date' ? 'month' : (this.calendarView === 'month' ? 'year' : 'date');
            },
            chooseCalendarMonth(month) {
                this.calendarMonth = month;
                this.calendarView = 'date';
            },
            chooseCalendarYear(year) {
                this.calendarYear = year;
                this.calendarView = 'month';
            },
            selectCalendarDate(day) {
                const date = this.calendarDate(day);
                if (!this.start || (this.start && this.end)) {
                    this.start = date;
                    this.end = '';
                } else if (date < this.start) {
                    this.end = this.start;
                    this.start = date;
                } else {
                    this.end = date;
                }
            },
            isCalendarDateSelected(day) {
                const date = this.calendarDate(day);
                return date === this.start || date === this.end;
            },
            isCalendarDateInRange(day) {
                if (!this.start || !this.end) return false;
                const date = this.calendarDate(day);
                return date > this.start && date < this.end;
            },
        };
    }

    erpFilterSheet.today = today;
    erpFilterSheet.week = week;
    erpFilterSheet.monthRange = monthRange;
    erpFilterSheet.monthGridOffset = monthGridOffset;
    erpFilterSheet.daysInMonth = daysInMonth;

    if (typeof module !== 'undefined' && module.exports) {
        module.exports = erpFilterSheet;
    }

    const register = () => root.Alpine.data('erpFilterSheet', erpFilterSheet);
    if (root.Alpine && typeof root.Alpine.data === 'function') {
        register();
    } else if (root.document) {
        root.document.addEventListener('alpine:init', register);
    }
})(typeof window !== 'undefined' ? window : globalThis);
