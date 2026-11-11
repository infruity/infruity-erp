/* Kasir / POS list: draft/apply filter sheet and load-more helpers. Presentation only; /pos/data stays authoritative. */
(function (root, factory) {
    const api = factory();
    if (typeof module === 'object' && module.exports) module.exports = api;
    else root.PosListUi = api;
})(typeof globalThis !== 'undefined' ? globalThis : this, function () {
    'use strict';

    const THEME_COUNT = 7;

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

    // The filter sheet itself is <x-erp.filter-sheet id="pos">: it edits a draft and reports the chosen
    // values. This state holds what the list is using (applied) and talks to the sheet through events.
    function createFilters(config) {
        const cfg = config || {};
        const blank = () => ({status: 'all', branch: cfg.defaultBranch || 'all', start: '', end: ''});
        let detailLinkVersion = 0;
        return {
            filtersOpen: false,
            applied: blank(),
            onApply: null,
            onLoadMore: null,
            hasMore: false,
            remaining: '',
            loading: false,
            get hasActive() {
                const a = this.applied;
                return a.status !== 'all' || a.branch !== blank().branch || !!a.start || !!a.end;
            },
            open() {
                this.filtersOpen = true;
                this.$dispatch('erp-filter-open', {id: 'pos', values: {...this.applied}});
            },
            close() {
                this.filtersOpen = false;
            },
            applyFilters(values) {
                const next = values || {};
                this.applied = {status: next.status || 'all', branch: next.branch || blank().branch, start: next.start || '', end: next.end || ''};
                this.filtersOpen = false;
                if (typeof this.onApply === 'function') this.onApply({...this.applied});
            },
            reset() {
                this.applied = blank();
                this.filtersOpen = false;
                if (typeof this.onApply === 'function') this.onApply({...this.applied});
            },
            detail: null,
            detailOpen: false,
            detailLinkLoading: false,
            detailLinkError: '',
            detailPrimary,
            canDelete,
            whatsappUrl,
            async openLinkedDetail(search, load) {
                const ids = new URLSearchParams(search).getAll('detail');
                if (!ids.length) return;
                const version = ++detailLinkVersion;
                this.detailLinkError = '';
                if (ids.length !== 1 || !/^[1-9][0-9]{0,18}$/.test(ids[0])) {
                    this.detailLinkError = 'Tautan detail transaksi tidak valid.';
                    return;
                }
                this.detailLinkLoading = true;
                try {
                    const detail = await load(ids[0]);
                    if (version !== detailLinkVersion) return;
                    if (!detail || String(detail.id) !== ids[0]) {
                        this.detailLinkError = 'Transaksi POS tidak ditemukan atau tidak dapat diakses.';
                        return;
                    }
                    this.openDetail(detail);
                } catch (error) {
                    if (version === detailLinkVersion) this.detailLinkError = 'Gagal memuat detail transaksi POS. Muat ulang halaman untuk mencoba lagi.';
                } finally {
                    if (version === detailLinkVersion) this.detailLinkLoading = false;
                }
            },
            openDetail(row) {
                if (!row) return;
                ++detailLinkVersion;
                this.detailLinkLoading = false;
                this.detailLinkError = '';
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

    return {createFilters, parseDetail, detailPrimary, canDelete, whatsappUrl, nextLength, remainingLabel, customerInitial, avatarTheme};
});
