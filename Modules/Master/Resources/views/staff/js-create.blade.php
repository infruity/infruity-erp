@push('scripts')
<script>
window.staffCreateOverlay = function (initialOpen = false) {
    return {
        drawerOpen: Boolean(initialOpen),
        pushedHistoryEntry: false,
        init() {
            this.syncBodyScroll();
            if (this.drawerOpen) this.focusNameField();
        },
        openDrawer() {
            const url = new URL(window.location.href);
            if (!url.searchParams.has('create')) {
                url.searchParams.set('create', '1');
                window.history.pushState({ ...(window.history.state || {}), staffCreateOverlay: true }, '', `${url.pathname}${url.search}${url.hash}`);
                this.pushedHistoryEntry = true;
            }
            this.drawerOpen = true;
            this.syncBodyScroll();
            this.$nextTick(() => this.focusNameField());
        },
        closeDrawer() {
            this.drawerOpen = false;
            this.syncBodyScroll();
            const url = new URL(window.location.href);
            if (!url.searchParams.has('create')) return;
            if (this.pushedHistoryEntry && window.history.state?.staffCreateOverlay) {
                this.pushedHistoryEntry = false;
                window.history.back();
                return;
            }
            url.searchParams.delete('create');
            window.history.replaceState(window.history.state, '', `${url.pathname}${url.search}${url.hash}`);
        },
        syncFromUrl() {
            this.drawerOpen = new URLSearchParams(window.location.search).has('create');
            this.pushedHistoryEntry = false;
            this.syncBodyScroll();
        },
        syncBodyScroll() {
            document.body.classList.toggle('overflow-hidden', this.drawerOpen);
        },
        focusNameField() {
            document.getElementById('staff_name')?.focus({ preventScroll: true });
        },
    };
};

    document.getElementById('staff-form')?.addEventListener('submit', event => {
        const button = event.currentTarget.querySelector('[data-submit]');
        if (button) { button.disabled = true; button.textContent = 'Menyimpan...'; }
    });
})();
</script>
@endpush
