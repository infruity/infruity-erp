/* Kasir / POS list: draft/apply filter sheet and load-more helpers. Presentation only; /pos/data stays authoritative. */
(function (root, factory) {
    const api = factory();
    if (typeof module === 'object' && module.exports) module.exports = api;
    else root.PosListUi = api;
})(typeof globalThis !== 'undefined' ? globalThis : this, function () {
    'use strict';

    const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    const CALENDAR_MONTHS = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    const THEME_COUNT = 7;

    // Calendar parts come from the application timezone, then date-only math runs in UTC.
    function dateRange(period, now, timeZone) {
        const parts = new Intl.DateTimeFormat('en-US', {timeZone: timeZone || 'UTC', year: 'numeric', month: '2-digit', day: '2-digit'}).formatToParts(now || new Date());
        const part = type => Number(parts.find(item => item.type === type).value);
        const start = new Date(Date.UTC(part('year'), part('month') - 1, part('day')));
        const end = new Date(start);
        if (period === 'week') {
            start.setUTCDate(start.getUTCDate() - ((start.getUTCDay() + 6) % 7));
            end.setTime(start.getTime());
            end.setUTCDate(end.getUTCDate() + 6);
        }
        const iso = date => [date.getUTCFullYear(), String(date.getUTCMonth() + 1).padStart(2, '0'), String(date.getUTCDate()).padStart(2, '0')].join('-');
        return {start: iso(start), end: iso(end)};
    }

    function formatDate(value) {
        if (!value) return 'Pilih Tanggal';
        const [year, month, day] = String(value).split('-').map(Number);
        if (!year || !month || !day) return value;
        return `${day} ${MONTHS[month - 1]} ${year}`;
    }

    // Same growth rule as the other load-more lists: from the larger of the current length and one step.
    function nextLength(current, step) {
        return Math.max(Number(current) || 0, step) + step;
    }

    function remainingLabel(info) {
        const left = Math.max(0, (info.recordsDisplay || 0) - (info.end || 0));
        return left > 0 ? `(${left} tersisa)` : '';
    }

    // Same initials and colour hash as the reference list avatar.
    function customerInitial(name) {
        if (!name) return '??';
        const cleaned = String(name).replace(/\s*\(.*?\)\s*/g, '').replace(/#\d+/g, '').trim();
        const parts = cleaned.split(/\s+/).filter(Boolean);
        if (parts.length === 0) return '??';
        if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }

    function avatarTheme(name) {
        if (!name) return 0;
        let hash = 0;
        for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash);
        return Math.abs(hash) % THEME_COUNT;
    }

    function parseDetail(text) {
        try {
            const value = JSON.parse(text);
            return value && typeof value === 'object' ? value : null;
        } catch (error) {
            return null;
        }
    }

    // Reference popup: Pending continues the draft, unpaid/partial pays, everything else shows the receipt.
    function detailPrimary(row) {
        if (row.status_key === 'pending' && row.edit_url) return {label: 'Lanjutkan Transaksi', url: row.edit_url};
        if (['unpaid', 'partial'].includes(row.status_key) && row.pay_url) return {label: 'Bayar', url: row.pay_url};
        return {label: 'Lihat Nota Transaksi', url: row.show_url};
    }

    // The server decides who may delete; settled and removed receipts never offer it.
    function canDelete(row) {
        return !!row.can_delete && !['paid', 'canceled'].includes(row.status_key);
    }

    function whatsappUrl(phone) {
        const digits = String(phone || '').replace(/\D/g, '');
        return digits ? `https://wa.me/${digits}` : '';
    }

    function createFilters(config) {
        const cfg = config || {};
        const blank = () => ({status: 'all', branch: cfg.defaultBranch || 'all', start: '', end: ''});
        return {
            branches: Array.isArray(cfg.branches) ? cfg.branches : [],
            timezone: cfg.timezone || 'Asia/Jakarta',
            filtersOpen: false,
            calendarOpen: false,
            calendarView: 'date',
            calendarSide: 'start',
            calendarMonth: 0,
            calendarYear: 1970,
            calendarTrigger: null,
            branchSearch: '',
            draft: blank(),
            applied: blank(),
            onApply: null,
            onLoadMore: null,
            hasMore: false,
            remaining: '',
            loading: false,
            formatDate,
            get visibleBranches() {
                const query = this.branchSearch.trim().toLocaleLowerCase();
                return this.branches.filter(branch => String(branch.name || '').toLocaleLowerCase().includes(query));
            },
            get showAllBranches() {
                const query = this.branchSearch.trim().toLocaleLowerCase();
                return !query || 'semua cabang'.includes(query);
            },
            get hasActive() {
                const a = this.applied;
                return a.status !== 'all' || a.branch !== blank().branch || !!a.start || !!a.end;
            },
            get dateError() {
                const d = this.draft;
                return d.start && d.end && d.start > d.end ? 'Tanggal akhir harus sama atau setelah tanggal mulai.' : '';
            },
            get calendarHeading() {
                if (this.calendarView === 'month') return String(this.calendarYear);
                if (this.calendarView === 'year') return `${this.calendarYear - 4} - ${this.calendarYear + 7}`;
                return `${CALENDAR_MONTHS[this.calendarMonth]} ${this.calendarYear}`;
            },
            get calendarWeekdays() { return ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']; },
            get calendarDays() {
                const firstDay = new Date(Date.UTC(this.calendarYear, this.calendarMonth, 1));
                const offset = firstDay.getUTCDay();
                const count = new Date(Date.UTC(this.calendarYear, this.calendarMonth + 1, 0)).getUTCDate();
                return [
                    ...Array.from({length:offset}, () => null),
                    ...Array.from({length:count}, (_, index) => {
                        const day = index + 1;
                        const date = `${this.calendarYear}-${String(this.calendarMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                        return {day, date};
                    }),
                ];
            },
            get calendarMonths() { return CALENDAR_MONTHS; },
            get calendarYears() { return Array.from({length:12}, (_, index) => this.calendarYear - 4 + index); },
            open() {
                this.draft = {...this.applied};
                this.branchSearch = '';
                this.calendarOpen = false;
                this.calendarView = 'date';
                this.calendarTrigger = null;
                this.filtersOpen = true;
            },
            close() {
                this.filtersOpen = false;
                this.calendarOpen = false;
                this.calendarView = 'date';
                this.calendarTrigger = null;
            },
            openCalendar(side, trigger, now) {
                if (!['start', 'end'].includes(side)) return;
                const value = this.draft[side] || dateRange('today', now, this.timezone).start;
                const [year, month] = String(value).split('-').map(Number);
                this.calendarSide = side;
                this.calendarMonth = month - 1;
                this.calendarYear = year;
                this.calendarView = 'date';
                this.calendarTrigger = trigger || null;
                this.calendarOpen = true;
            },
            closeCalendar() {
                this.calendarOpen = false;
                this.calendarView = 'date';
                const trigger = this.calendarTrigger;
                this.calendarTrigger = null;
                if (!trigger || typeof trigger.focus !== 'function') return;
                const restore = () => {
                    trigger.focus({preventScroll:true});
                    trigger.scrollIntoView?.({block:'nearest'});
                };
                const frame = typeof requestAnimationFrame === 'function' ? requestAnimationFrame : callback => callback();
                if (typeof this.$nextTick === 'function') this.$nextTick(() => frame(restore));
                else frame(restore);
            },
            setCalendarView(view) {
                if (['date', 'month', 'year'].includes(view)) this.calendarView = view;
            },
            navigateCalendar(step) {
                const amount = Number(step);
                if (!Number.isFinite(amount) || amount === 0) return;
                if (this.calendarView === 'date') {
                    const candidate = new Date(Date.UTC(this.calendarYear, this.calendarMonth + amount, 1));
                    this.calendarYear = candidate.getUTCFullYear();
                    this.calendarMonth = candidate.getUTCMonth();
                } else if (this.calendarView === 'month') {
                    this.calendarYear += amount;
                } else {
                    this.calendarYear += amount * 12;
                }
            },
            selectCalendarYear(value) {
                const year = Number(value);
                if (!Number.isInteger(year)) return;
                this.calendarYear = year;
                this.calendarView = 'month';
            },
            selectCalendarMonth(value) {
                const month = Number(value);
                if (!Number.isInteger(month) || month < 0 || month > 11) return;
                this.calendarMonth = month;
                this.calendarView = 'date';
            },
            selectCalendarDate(day) {
                const value = day && typeof day === 'object' ? day.date : '';
                if (!/^\d{4}-\d{2}-\d{2}$/.test(String(value))) return;
                this.draft[this.calendarSide] = value;
                this.closeCalendar();
            },
            isCalendarDateSelected(value) {
                return !!value && value === this.draft[this.calendarSide];
            },
            isCalendarDateInRange(value) {
                const {start, end} = this.draft;
                return !!start && !!end && start < end && value > start && value < end;
            },
            isDateRangeActive(period, now) {
                if (!['today', 'week'].includes(period)) return false;
                const range = dateRange(period, now, this.timezone);
                return this.draft.start === range.start && this.draft.end === range.end;
            },
            setDateRange(period, now) {
                if (!['today', 'week'].includes(period)) return;
                const range = dateRange(period, now, this.timezone);
                this.draft.start = range.start;
                this.draft.end = range.end;
                this.calendarOpen = false;
                this.calendarView = 'date';
                this.calendarTrigger = null;
            },
            apply() {
                if (this.dateError) return;
                this.applied = {...this.draft};
                this.filtersOpen = false;
                this.calendarOpen = false;
                this.calendarTrigger = null;
                if (typeof this.onApply === 'function') this.onApply({...this.applied});
            },
            reset() {
                this.draft = blank();
                this.applied = blank();
                this.branchSearch = '';
                this.filtersOpen = false;
                this.calendarOpen = false;
                this.calendarView = 'date';
                this.calendarTrigger = null;
                if (typeof this.onApply === 'function') this.onApply({...this.applied});
            },
            detail: null,
            detailOpen: false,
            detailPrimary,
            canDelete,
            whatsappUrl,
            openDetail(row) {
                if (!row) return;
                this.detail = row;
                this.detailOpen = true;
            },
            closeDetail() { this.detailOpen = false; },
            onDrawn(info) {
                this.loading = false;
                this.hasMore = (info.end || 0) < (info.recordsDisplay || 0);
                this.remaining = remainingLabel(info);
            },
            loadMore() {
                if (this.loading || !this.hasMore || typeof this.onLoadMore !== 'function') return;
                this.loading = true;
                this.onLoadMore();
            },
        };
    }

    return {createFilters, parseDetail, detailPrimary, canDelete, whatsappUrl, dateRange, formatDate, nextLength, remainingLabel, customerInitial, avatarTheme};
});
