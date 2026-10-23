<script>
    // Variabel penanda (flag) untuk mengizinkan navigasi.
    let isNavigationAllowed = false;
    let hasUnsavedWholesaleChanges = false;
    window.addEventListener('wholesale-dirty-state', event => {
        hasUnsavedWholesaleChanges = event.detail.dirty;
    });
    const allowedPath = '/expenditure/payment/';

    /**
     * FUNGSI BARU: Gunakan fungsi ini untuk pindah ke halaman pembayaran.
     * Ini akan memastikan flag diatur dengan benar sebelum navigasi.
     */
    function redirectToPayment(transaksiId) {
        console.log('Navigasi ke halaman pembayaran diizinkan...');
        // 1. Atur flag untuk MENGIZINKAN navigasi
        isNavigationAllowed = true;

        // 2. Lakukan navigasi
        window.location.href = `/sortir/payment/${transaksiId}`;
    }

    function redirectToHome(transaksiId) {
        console.log('Navigasi ke halaman pembayaran diizinkan...');
        // 1. Atur flag untuk MENGIZINKAN navigasi
        isNavigationAllowed = true;

        // 2. Lakukan navigasi
        window.location.href = `/wholesale`;
    }

    // Event listener untuk klik tautan biasa (jika masih ada)
    document.addEventListener('click', function(event) {
        const link = event.target.closest('a');
        if (link) {
            const destinationUrl = new URL(link.href);
            if (destinationUrl.pathname.startsWith(allowedPath)) {
                isNavigationAllowed = true;
            }
        }
    });

    // Event listener utama yang mencegah pengguna meninggalkan halaman.
    window.addEventListener('beforeunload', function(event) {
        if (!isNavigationAllowed && hasUnsavedWholesaleChanges) {
            event.preventDefault();
            event.returnValue = '';
        }
    });
</script>
