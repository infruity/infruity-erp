@push('scripts')
<script>
window.staffEditOverlay = function (initialOpen = false, initialId = null, returnUrl = '/staff', editIndexUrl = '/staff') {
    return {
        drawerOpen: Boolean(initialOpen),
        currentId: initialId === null ? null : String(initialId),
        formHtml: '',
        loading: false,
        loadError: '',
        requestId: 0,
        returnUrl,
        editIndexUrl,
        pushedHistoryEntry: false,

        init() {
            this.syncBodyScroll();
        },

        editIndexPath() {
            return new URL(this.editIndexUrl, window.location.origin).pathname.replace(/\/$/, '');
        },

        editUrl(id) {
            const url = new URL(`${this.editIndexPath()}/${encodeURIComponent(id)}/edit`, window.location.origin);
            url.search = '';
            url.hash = '';
            return url;
        },

        editIdFromLocation() {
            const path = window.location.pathname;
            const prefix = `${this.editIndexPath()}/`;
            if (!path.startsWith(prefix)) return null;
            const segments = path.slice(prefix.length).replace(/\/$/, '').split('/');
            return segments.length === 2 && segments[1] === 'edit' ? decodeURIComponent(segments[0]) : null;
        },

        async openStaffEdit(record) {
            if (!record?.id) return;

            const currentUrl = new URL(window.location.href);
            this.returnUrl = `${currentUrl.pathname}${currentUrl.search}${currentUrl.hash}`;
            const id = String(record.id);
            const editUrl = this.editUrl(id);
            this.currentId = id;
            this.formHtml = '';
            this.loadError = '';
            this.drawerOpen = true;
            this.syncBodyScroll();

            if (currentUrl.pathname !== editUrl.pathname) {
                window.history.pushState({
                    ...(window.history.state || {}),
                    staffEditOverlay: true,
                    staffEditReturnUrl: this.returnUrl,
                    staffEditId: id,
                }, '', `${editUrl.pathname}${editUrl.search}${editUrl.hash}`);
                this.pushedHistoryEntry = true;
            } else {
                this.pushedHistoryEntry = false;
            }

            return this.loadForm(id);
        },

        async loadForm(id) {
            const requestId = ++this.requestId;
            this.loading = true;
            this.loadError = '';
            const url = this.editUrl(id);
            url.searchParams.set('overlay', '1');

            try {
                const response = await fetch(url.toString(), {
                    headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!response.ok || response.redirected) throw new Error('Form edit karyawan gagal dimuat.');
                const html = await response.text();
                if (requestId !== this.requestId || String(this.currentId) !== String(id) || !this.drawerOpen) return;
                this.formHtml = html;
            } catch (error) {
                if (requestId !== this.requestId || String(this.currentId) !== String(id)) return;
                this.loadError = error.message || 'Form edit karyawan gagal dimuat.';
            } finally {
                if (requestId === this.requestId) this.loading = false;
            }
        },

        closeStaffEdit() {
            if (!this.drawerOpen) return;
            this.drawerOpen = false;
            this.syncBodyScroll();
            this.requestId++;
            this.loading = false;
            this.loadError = '';

            if (this.pushedHistoryEntry && window.history.state?.staffEditOverlay) {
                this.pushedHistoryEntry = false;
                window.history.back();
                return;
            }

            const target = new URL(this.returnUrl || this.editIndexUrl, window.location.origin);
            window.history.replaceState(window.history.state, '', `${target.pathname}${target.search}${target.hash}`);
            this.currentId = null;
            this.formHtml = '';
        },

        syncFromUrl() {
            const id = this.editIdFromLocation();
            if (!id) {
                this.drawerOpen = false;
                this.currentId = null;
                this.formHtml = '';
                this.loading = false;
                this.loadError = '';
                this.pushedHistoryEntry = false;
                this.syncBodyScroll();
                return;
            }

            const alreadyOpen = this.drawerOpen && String(this.currentId) === String(id);
            this.drawerOpen = true;
            this.currentId = String(id);
            this.returnUrl = window.history.state?.staffEditReturnUrl || this.editIndexUrl;
            this.pushedHistoryEntry = Boolean(window.history.state?.staffEditOverlay);
            this.syncBodyScroll();
            if (!alreadyOpen) {
                this.formHtml = '';
                this.loadForm(id);
            }
        },

        syncBodyScroll() {
            document.body.classList.toggle('overflow-hidden', this.drawerOpen);
        },
    };
};
</script>
@endpush
