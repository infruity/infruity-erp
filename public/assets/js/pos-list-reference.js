/* Kasir / POS list: draft/apply filter sheet and load-more helpers. Presentation only; /pos/data stays authoritative. */
(function (root, factory) {
    const api = factory();
    if (typeof module === 'object' && module.exports) module.exports = api;
    else root.PosListUi = api;
})(typeof globalThis !== 'undefined' ? globalThis : this, function () {
    'use strict';

    const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
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

    function createFilters(config) {
        const cfg = config || {};
        const blank = () => ({status: 'all', branch: cfg.defaultBranch || 'all', start: '', end: ''});
        return {
            branches: Array.isArray(cfg.branches) ? cfg.branches : [],
            timezone: cfg.timezone || 'Asia/Jakarta',
            filtersOpen: false,
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
            open() {
                this.draft = {...this.applied};
                this.branchSearch = '';
                this.filtersOpen = true;
            },
            close() { this.filtersOpen = false; },
            setDateRange(period, now) {
                if (!['today', 'week'].includes(period)) return;
                const range = dateRange(period, now, this.timezone);
                this.draft.start = range.start;
                this.draft.end = range.end;
            },
            apply() {
                if (this.dateError) return;
                this.applied = {...this.draft};
                this.filtersOpen = false;
                if (typeof this.onApply === 'function') this.onApply({...this.applied});
            },
            reset() {
                this.draft = blank();
                this.applied = blank();
                this.branchSearch = '';
                this.filtersOpen = false;
                if (typeof this.onApply === 'function') this.onApply({...this.applied});
            },
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

    return {createFilters, dateRange, formatDate, nextLength, remainingLabel, customerInitial, avatarTheme};
});
