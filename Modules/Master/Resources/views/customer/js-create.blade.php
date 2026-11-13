@push('scripts')
<script>
(() => {
    // Provinsi -> Kelurahan is the Alpine component x-erp.region-pickers; this only handles saving.
    window.initCustomerForms = (root = document) => {
        for (const form of root.querySelectorAll('form#customer-form')) {
            if (form.dataset.submitBound === 'true') continue;
            form.dataset.submitBound = 'true';
            form.addEventListener('submit', event => {
                const button = event.currentTarget.querySelector('[data-submit]');
                if (button) { button.disabled = true; button.textContent = 'Menyimpan...'; }
            });
        }
    };

    const initialize = () => window.initCustomerForms(document);
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize, { once: true });
    else initialize();

    new MutationObserver(records => {
        const hasNewCustomerForm = records.some(record => [...record.addedNodes].some(node => node.nodeType === Node.ELEMENT_NODE && (node.matches('form#customer-form') || node.querySelector('form#customer-form'))));
        if (hasNewCustomerForm) initialize();
    }).observe(document.body, { childList: true, subtree: true });
})();
</script>
@endpush
