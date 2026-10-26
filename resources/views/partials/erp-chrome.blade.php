{{--
    Shared Alpine state for the ERP shell.

    Included by every layout that renders <x-erp.sidebar>, <x-erp.topbar> and
    <x-erp.mobile-navbar> so all three components read the same state:
    sidebarOpen (mobile drawer), sidebarCollapsed (desktop rail) and theme.
--}}
<script>
    function erpChrome() {
        return {
            sidebarOpen: false,
            sidebarCollapsed: false,
            theme: 'dark',
        };
    }
</script>
