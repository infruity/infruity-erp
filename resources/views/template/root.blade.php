<!DOCTYPE html>
<html lang="id" class="erp-html">
<!--begin::Head-->

<head>
    <base href="/" />
    <title>@yield('title', 'Infruity')</title>
    <meta name="description" content="@yield('meta_description', 'UMKM Jual Buah Segar dan Sehat Secara Online')">
    <meta name="keywords" content="@yield('meta_keywords', 'jual buah online, buah segar, infruity, infruity.com, buah sehat')">
    <meta name="author" content="Infruity">
    <link rel="canonical" href="{{ url()->current() }}" />
    <meta property="og:type" content="article">
    <meta property="og:title" content="@yield('title', 'UMKM Jual Buah Segar dan Sehat Secara Online')">
    <meta property="og:site_name" content="UMKM Jual Buah Segar dan Sehat Secara Online">
    <meta property="og:description" content="@yield('meta_description', 'UMKM Jual Buah Segar dan Sehat Secara Online')">
    <meta property="og:image" content="@yield('og_image', asset('images/logo-infruity.png'))">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <link rel="shortcut icon" href="{{ asset('images/logo-infruity.png') }}" />

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!--begin::Fonts(mandatory for all pages)-->

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>



    <!--end::Fonts-->
    <!--begin::Vendor Stylesheets(used for this page only)-->
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}">
    <!--end::Vendor Stylesheets-->
    <!--begin::Global Stylesheets Bundle(mandatory for all pages)-->
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/plugins/global/plugins.bundle.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/style.bundle.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/erp-mobile-search.css') }}?v={{ filemtime(public_path('assets/css/erp-mobile-search.css')) }}">
    <script>
        window.tailwind = window.tailwind || {};
        window.tailwind.config = { corePlugins: { preflight: false }, theme: { extend: {
            colors: { primary: '#0F5C45', strawberry: '#D6455D' },
            fontFamily: { sans: ['"Plus Jakarta Sans"', 'sans-serif'], serif: ['"Poppins"', 'sans-serif'] },
            boxShadow: { soft: '0 10px 40px -10px rgba(0,0,0,0.08)' }
        } } };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    @isset($livewire)
        @livewireStyles
    @endisset
    @include('partials.erp-chrome')
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!--end::Global Stylesheets Bundle-->
    <script>
        // Frame-busting to prevent site from being loaded within a frame without permission (click-jacking) if (window.top != window.self) { window.top.location.replace(window.self.location.href); }
    </script>

    <link rel="stylesheet" href="{{ asset('assets/css/erp-theme.css') }}?v={{ filemtime(public_path('assets/css/erp-theme.css')) }}">
    <script defer src="{{ asset('assets/js/erp-overlays.js') }}?v={{ filemtime(public_path('assets/js/erp-overlays.js')) }}"></script>
<link rel="stylesheet" href="{{ asset('assets/fonts/infruity/fonts.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/phosphor/phosphor.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/erp-workspace.css') }}?v={{ filemtime(public_path('assets/css/erp-workspace.css')) }}">
<script defer src="{{ asset('assets/js/erp-collections.js') }}?v={{ filemtime(public_path('assets/js/erp-collections.js')) }}"></script>
<link rel="stylesheet" href="{{ asset('assets/css/erp-shell.css') }}?v={{ filemtime(public_path('assets/css/erp-shell.css')) }}">
</head>
<!--end::Head-->
<!--begin::Body-->

<body id="kt_body" class="aside-enabled erp-ui erp-legacy {{ request()->routeIs('reports.hub', 'report-*', 'product-stock*', 'pos.payment', 'sortir.create', 'sortir.edit', 'sortir.show', 'transfer-pengirim.create', 'transfer-pengirim.edit', 'transfer-pengirim.show', 'transfer-penerima.show', 'wholesale.create', 'wholesale.edit', 'wholesale.show') ? 'erp-hide-dock' : '' }}">
    <!--begin::Theme mode setup on page load-->
    <script>
        var defaultThemeMode = "light";
        var themeMode;
        if (document.documentElement) {
            if (document.documentElement.hasAttribute("data-bs-theme-mode")) {
                themeMode = document.documentElement.getAttribute("data-bs-theme-mode");
            } else {
                if (localStorage.getItem("data-bs-theme") !== null) {
                    themeMode = localStorage.getItem("data-bs-theme");
                } else {
                    themeMode = defaultThemeMode;
                }
            }
            if (themeMode === "system") {
                themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
            }
            document.documentElement.setAttribute("data-bs-theme", themeMode);
        }
    </script>
    <!--end::Theme mode setup on page load-->
    <!--begin::Main-->
    <!--begin::Root-->
    <div class="erp-shell flex h-[100dvh] min-h-[100dvh] w-full overflow-hidden bg-[#e5e9f3] font-sans text-gray-800 antialiased"
        x-data="erpChrome()" @erp-mobile-sidebar-toggle.window="sidebarOpen = true">
        <x-erp.sidebar />
        <div class="erp-shell__content flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden">
            <x-erp.topbar />

            <main id="kt_content" class="erp-main flex min-h-0 flex-1 flex-col overflow-y-auto overscroll-none px-4 md:px-6 md:py-6 lg:px-8 scrollbar-hide">
                <div id="kt_post" class="flex min-h-0 flex-1 flex-col">
                    <div id="kt_content_container" class="w-full min-w-0 flex-1 px-0">
                        @include('template.notif')
                        @yield('content')
                    </div>
                </div>
            </main>
        </div>
        <x-erp.mobile-navbar />
        @unless (request()->routeIs('reports.hub', 'report-*', 'product-stock.show'))

        @endunless
    </div>
    <!--end::Root-->
    {{-- @include('template.drawer') --}}
    <!--end::Main-->
    <!--begin::Scrolltop-->
    <div id="kt_scrolltop" class="scrolltop" data-kt-scrolltop="true">
        <i class="ki-duotone ki-arrow-up">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
    </div>
    <!--end::Scrolltop-->
    {{-- @include('template.modals') --}}
    <!--begin::Javascript-->
    <script>
        var hostUrl = "assets/";
    </script>
    <!--begin::Global Javascript Bundle(mandatory for all pages)-->
    <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
    <!--end::Global Javascript Bundle-->
    <!--begin::Vendors Javascript(used for this page only)-->
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
    <!--end::Vendors Javascript-->
    <!--begin::Custom Javascript(used for this page only)-->
    {{-- <script src="assets/js/custom/apps/ecommerce/catalog/products.js"></script>
    <script src="assets/js/widgets.bundle.js"></script>
    <script src="assets/js/custom/widgets.js"></script>
    <script src="assets/js/custom/apps/chat/chat.js"></script>
    <script src="assets/js/custom/utilities/modals/users-search.js"></script> --}}
    @if (isset($page_plugin_js))
        @foreach ($page_plugin_js as $item)
            <script type="text/javascript" src="{{ asset($item) }}"></script>
        @endforeach
    @endif
    <script>
        if (window.jQuery?.fn?.dataTable) {
            jQuery.extend(true, jQuery.fn.dataTable.defaults, { language: {
                processing: 'Memuat data…', emptyTable: 'Belum ada data untuk ditampilkan.',
                zeroRecords: 'Tidak ada hasil yang sesuai.', lengthMenu: 'Tampilkan _MENU_',
                info: '_START_–_END_ dari _TOTAL_ data', infoEmpty: 'Belum ada data',
                infoFiltered: '(dari _MAX_ data)', paginate: { previous: 'Sebelumnya', next: 'Berikutnya' }
            } });
        }
    </script>
    @yield('script')
    <!--end::Custom Javascript-->
    <!--end::Javascript-->
    <script>
        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        // Unformat: 1.000 -> 1000
        function unformatNumber(numStr) {
            return numStr.replace(/[.,]/g, "");
        }

        // Bind input format handler (untuk semua elemen format-number)
        function bindFormatNumber() {
            $('.format-number').each(function() {
                // Format isi awal jika belum diformat
                let raw = unformatNumber($(this).val());
                if (!isNaN(raw) && raw !== "") {
                    $(this).val(formatNumber(Number(raw)));
                }

                // Hapus event sebelumnya lalu bind ulang
                $(this).off('input').on('input', function() {
                    let valRaw = unformatNumber($(this).val());
                    if (!isNaN(valRaw) && valRaw !== "") {
                        $(this).val(formatNumber(Number(valRaw)));
                    } else {
                        $(this).val('');
                    }
                });
            });
        }

        $(document).ready(function() {
            bindFormatNumber(); // Jalankan saat awal

            // Sebelum form disubmit, ubah semua format-number jadi angka murni
            $('form').on('submit', function() {
                $('.format-number').each(function() {
                    $(this).val(unformatNumber($(this).val()));
                });
            });
        });
    </script>
    <script src="https://www.gstatic.com/firebasejs/10.12.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/10.12.0/firebase-messaging-compat.js"></script>

    <script>
        firebase.initializeApp({
            apiKey: "AIzaSyD68q1qNP-udtOOndJNd6ipHEsqO-iZEyM",
            authDomain: "laravel-web-dev.firebaseapp.com",
            projectId: "laravel-web-dev",
            storageBucket: "laravel-web-dev.firebasestorage.app",
            messagingSenderId: "537811764561",
            appId: "1:537811764561:web:429e5642c941aac61a7f70",
            measurementId: "G-PXD4TLQS7G"
        });

        const messaging = firebase.messaging();

        // Registrasi service worker
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/firebase-messaging-sw.js')
                .then(async (registration) => {
                    // console.log('✅ Service Worker terdaftar:', registration);

                    // Tunggu SW siap
                    const swReady = await navigator.serviceWorker.ready;
                    // console.log('✅ Service Worker aktif:', swReady);

                    // Ambil token FCM
                    const token = await messaging.getToken({
                        vapidKey: "BPu7NXtLeC_NjJnRb8OsHhY6bU0u-m761uLeKrqHpm3nCG7jlgxHMO91K3WqbeQglmJeto7LbMrpT2yRjrLu75A",
                        serviceWorkerRegistration: registration, // gunakan ini, bukan useServiceWorker()
                    });

                    // console.log('🔥 Token FCM:', token);

                    // Kirim token ke backend Laravel
                    await fetch('/api/save-fcm-token', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            // Tambahkan auth jika perlu
                        },
                        body: JSON.stringify({
                            fcm_token: token
                        }),
                    });
                })
                .catch((err) => {
                    // console.error('Gagal mendapatkan token FCM:', err);
                });
        }

        document.getElementById('btn-notif')?.addEventListener('click', () => {
            Notification.requestPermission().then(permission => {
                if (permission === 'granted') {
                    alert(
                        'Berhasil'
                    );
                } else {
                    alert(
                        'Kamu menolak notifikasi. Ubah izin di pengaturan browser jika ingin mengaktifkannya.'
                    );
                }
            });
        });
    </script>
</body>
<!--end::Body-->

</html>
