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

(() => {
    const createAccount = document.getElementById('create-account');
    const accountFields = document.getElementById('account-creation-fields');
    const accountFieldset = document.getElementById('account-creation-fieldset');
    const syncAccountFields = () => {
        const enabled = Boolean(createAccount?.checked);
        if (accountFields) accountFields.hidden = !enabled;
        if (accountFieldset) accountFieldset.disabled = !enabled;
    };
    createAccount?.addEventListener('change', syncAccountFields);
    syncAccountFields();

    const accountEmail = document.getElementById('account_email');
    const autoAccountEmail = @js($nextAutoEmail ?? '');
    const syncAccountEmail = reset => {
        if (!accountEmail) return;
        const type = document.querySelector('input[name="account_email_type"]:checked')?.value || 'auto';
        const isAuto = type === 'auto';
        accountEmail.readOnly = isAuto;
        accountEmail.classList.toggle('bg-gray-50', isAuto);
        accountEmail.classList.toggle('bg-white', !isAuto);
        accountEmail.classList.toggle('cursor-not-allowed', isAuto);
        if (reset) accountEmail.value = isAuto ? autoAccountEmail : '';
    };
    document.querySelectorAll('input[name="account_email_type"]').forEach(input => input.addEventListener('change', () => syncAccountEmail(true)));
    syncAccountEmail(true);

    const staffName = document.getElementById('staff_name');
    const accountNamePreview = document.getElementById('account-name-preview');
    staffName?.addEventListener('input', () => {
        if (accountNamePreview) accountNamePreview.textContent = staffName.value.trim() || 'Isi nama karyawan di atas';
    });
    document.getElementById('staff-form')?.addEventListener('submit', event => {
        const button = event.currentTarget.querySelector('[data-submit]');
        if (button) { button.disabled = true; button.textContent = 'Menyimpan...'; }
    });
})();
</script>
@endpush
