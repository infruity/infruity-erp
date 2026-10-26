<!DOCTYPE html>
<html lang="id" class="erp-html">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>@yield('title', 'Master') - ERP Portal</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('assets/css/erp-mobile-search.css') }}?v={{ filemtime(public_path('assets/css/erp-mobile-search.css')) }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>



    <!-- Phosphor Icons -->


    <!-- jQuery (needed by existing page scripts / DataTables) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- DataTables (kept for index.blade.php DataTables usage) -->
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}">
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>

    <!-- SweetAlert2 (used by existing page scripts) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#0F5C45',
                        'primary-light': '#1a7d5e',
                        'primary-dark': '#0a3d2e',
                        accent: '#F2B400',
                        'accent-light': '#f5c733',
                        cream: '#F7F7F7',
                        'cream-dark': '#EBEBEB',
                        strawberry: '#D6455D',
                        'strawberry-light': '#e06a7e',
                    },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        serif: ['"Poppins"', 'sans-serif'],
                    },
                    boxShadow: {
                        'soft': '0 10px 40px -10px rgba(0,0,0,0.08)',
                        'glass': '0 8px 32px 0 rgba(31, 38, 135, 0.07)',
                        'input': '0 2px 10px rgba(0,0,0,0.02)',
                        'card': '0 1px 3px rgba(0,0,0,0.04), 0 6px 16px rgba(0,0,0,0.04)',
                        'sidebar': '4px 0 24px rgba(0,0,0,0.06)',
                    }
                }
            }
        }
    </script>
    <style>
        [x-cloak] {
            display: none !important;
        }

        .mobile-list-edge-fade {
            --mobile-list-fade-top: 32px;
            --mobile-list-fade-bottom: 64px;
        }

        @media (max-width: 767px) {
            .mobile-list-edge-fade {
                -webkit-mask-image: linear-gradient(to bottom, transparent 0, #000 var(--mobile-list-fade-top), #000 calc(100% - var(--mobile-list-fade-bottom)), transparent 100%);
                mask-image: linear-gradient(to bottom, transparent 0, #000 var(--mobile-list-fade-top), #000 calc(100% - var(--mobile-list-fade-bottom)), transparent 100%);
            }
        }

        @media (min-width: 768px) {
            .mobile-list-edge-fade {
                -webkit-mask-image: none;
                mask-image: none;
            }
        }

        .glass-dock {
            background: rgba(255,255,255,.76);
            -webkit-backdrop-filter: blur(24px) saturate(190%);
            backdrop-filter: blur(24px) saturate(190%);
            border: 1px solid rgba(255,255,255,.8);
            box-shadow: 0 16px 40px -8px rgba(15,92,69,.14), 0 4px 16px rgba(0,0,0,.06), inset 0 1px 1px rgba(255,255,255,.95);
        }

        .glass-nav-mobile {
            background: rgba(255,255,255,.72);
            -webkit-backdrop-filter: blur(22px) saturate(180%);
            backdrop-filter: blur(22px) saturate(180%);
            border-bottom: 1px solid rgba(255,255,255,.72);
            box-shadow: 0 6px 20px -2px rgba(0,0,0,.05);
        }

        /* Material Symbols Styling for Nav */
        .icon-rounded {
            font-family: 'Material Symbols Rounded';
            font-size: 28px;
            font-weight: 300;
        }
        .icon-inactive {
            font-variation-settings: 'FILL' 0, 'wght' 300;
        }
        .icon-active {
            font-variation-settings: 'FILL' 1, 'wght' 400;
        }

        /* ================================ */
        /* CUSTOM SCROLLBARS                */
        /* ================================ */
        .sidebar-scroll::-webkit-scrollbar { width: 2px; }
        .sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.2); border-radius: 10px; }
        .sidebar-scroll::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, 0.4); }

        .minimal-scrollbar::-webkit-scrollbar { width: 4px; }
        .minimal-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .minimal-scrollbar::-webkit-scrollbar-thumb { background: transparent; border-radius: 4px; }
        .minimal-scrollbar.is-scrolling::-webkit-scrollbar-thumb { background: #d1d5db; }
        .minimal-scrollbar::-webkit-scrollbar-thumb:hover { background: #9ca3af; }

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #c4c4c4; border-radius: 100px; }
        ::-webkit-scrollbar-thumb:hover { background: #999; }

        .dark-sidebar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.15); }
        .dark-sidebar::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, 0.25); }

        .sidebar-scroll { scrollbar-width: thin; scrollbar-color: rgba(255, 255, 255, 0.2) transparent; }
        .dark-sidebar { background-color: #0b595b; }
        .glass-header { background: rgba(247, 247, 247, 0.85); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); }

        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }

        /* Sidebar Navigation */
        .sidebar-link {
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        a.sidebar-link[href="#"] {
            cursor: default !important;
        }

        .sidebar-link.active { background-color: #d1f36e; color: #102f31 !important; font-weight: 600; box-shadow: 0 2px 6px rgba(209, 243, 110, 0.2); }
        .sidebar-link.active i { color: #102f31 !important; }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in-up { animation: fadeInUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) both; }

        .notif-item { transition: background 0.15s ease; }
        .notif-item:hover { background: rgba(15, 92, 69, 0.04); }
        .badge { font-size: 0.65rem; padding: 2px 8px; border-radius: 100px; font-weight: 600; letter-spacing: 0.02em; }

        .submenu-link { transition: all 0.2s ease; }
        .submenu-link i.sidebar-icon { display: none !important; }

        .submenu-link::before {
            content: ''; position: absolute; left: 28px; top: 50%; transform: translateY(-50%);
            width: 6px; height: 6px; border-radius: 50%; background-color: rgba(255, 255, 255, 0.25);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .submenu-link:hover::before { background-color: rgba(255, 255, 255, 0.6); transform: translateY(-50%) scale(1.2); }
        .submenu-link:hover { color: rgba(255, 255, 255, 0.9); }

        .submenu-link.active { color: #ffffff; font-weight: 600; }
        .submenu-link.active::before { background-color: #F2B400; transform: translateY(-50%) scale(1.2); box-shadow: 0 0 10px rgba(242, 180, 0, 0.5); }

        .submenu-link.level-3::before { left: 44px; }
        .chevron-rotate { transition: transform 0.25s ease; }
        .chevron-rotate.open { transform: rotate(180deg); }

        .sidebar-link, .submenu-link { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important; overflow: hidden; white-space: nowrap; }
        .sidebar-link span, .submenu-link span, .chevron-rotate { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); max-width: 200px; opacity: 1; }
        .toggle-container { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); max-width: 100px; opacity: 1; overflow: hidden; }

        .sidebar-collapsed span, .sidebar-collapsed .chevron-rotate { max-width: 0 !important; opacity: 0 !important; margin-left: 0 !important; }
        .sidebar-collapsed .logo-full { display: none !important; }
        .sidebar-collapsed .logo-mini { display: block !important; }
        .sidebar-collapsed .toggle-container { max-width: 0 !important; opacity: 0 !important; padding: 0 !important; margin: 0 !important; pointer-events: none; }
        .sidebar-collapsed .sidebar-link { justify-content: flex-start !important; padding-left: 1.5rem !important; padding-right: 0 !important; gap: 0 !important; }

        .resize-animation-stopper * { animation: none !important; transition: none !important; }

        /* Light Sidebar Overrides */
        .light-sidebar { background-color: #ffffff; border-right: 1px solid #f3f4f6; }
        .light-sidebar .text-white,
        .light-sidebar .text-white\/50,
        .light-sidebar .text-white\/45,
        .light-sidebar .text-white\/40,
        .light-sidebar .text-white\/35,
        .light-sidebar .text-white\/30 { color: #4b5563 !important; }
        .light-sidebar .font-medium { color: #374151 !important; }

        .light-sidebar .sidebar-link.active { background-color: #d1f36e !important; color: #102f31 !important; }
        .light-sidebar .submenu-link:hover { color: #111827 !important; }
        .light-sidebar .submenu-link::before { background-color: #d1d5db; }
        .light-sidebar .submenu-link:hover::before { background-color: #9ca3af; }
        .light-sidebar .submenu-link.active { color: #0F5C45 !important; font-weight: 600; }
        .light-sidebar .submenu-link.active::before { background-color: #0F5C45; }
        .light-sidebar .border-white\/10 { border-color: #f3f4f6 !important; }
        .light-sidebar .border-white\/20 { border-color: #e5e7eb !important; }
        .light-sidebar .bg-white\/10 { background-color: #f9fafb !important; }

        /* Smooth CSS Grid Collapse for Submenus */
        .submenu-wrapper {
            display: grid;
            grid-template-rows: 1fr;
            transition: grid-template-rows 0.3s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            opacity: 1;
        }
        .submenu-wrapper > div {
            overflow: hidden;
        }
        .sidebar-collapsed .submenu-wrapper {
            grid-template-rows: 0fr !important;
            opacity: 0 !important;
            display: none !important;
        }

        /* ======================== */
        /* iOS Safari Anti-Zoom Fix */
        /* ======================== */
        input, input[type="text"], input[type="email"], input[type="password"],
        input[type="number"], input[type="search"], input[type="tel"],
        input[type="url"], input[type="date"], input[type="datetime-local"],
        input[type="time"], select, textarea {
            font-size: 16px !important;
            touch-action: manipulation;
        }

        @yield('extra-style')
    </style>
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/erp-theme.css') }}?v={{ filemtime(public_path('assets/css/erp-theme.css')) }}">
    <script defer src="{{ asset('assets/js/erp-overlays.js') }}?v={{ filemtime(public_path('assets/js/erp-overlays.js')) }}"></script>
<link rel="stylesheet" href="{{ asset('assets/fonts/infruity/fonts.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/phosphor/phosphor.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/erp-workspace.css') }}?v={{ filemtime(public_path('assets/css/erp-workspace.css')) }}">
<script defer src="{{ asset('assets/js/erp-collections.js') }}?v={{ filemtime(public_path('assets/js/erp-collections.js')) }}"></script>
<link rel="stylesheet" href="{{ asset('assets/css/erp-shell.css') }}?v={{ filemtime(public_path('assets/css/erp-shell.css')) }}">
</head>

<body class="erp-ui font-sans bg-[#e5e9f3] text-gray-800 antialiased h-[100dvh] min-h-[100dvh] overflow-hidden">

    <div x-data="erpChrome()" @erp-mobile-sidebar-toggle.window="sidebarOpen = true" class="erp-shell flex h-[100dvh] min-h-[100dvh] w-full overflow-hidden">

        <x-erp.sidebar />

        <!-- ================================ -->
        <!-- MAIN CONTENT AREA                -->
        <!-- ================================ -->
        <div class="erp-shell__content min-h-0 min-w-0 flex-1 flex flex-col overflow-hidden">

            <x-erp.topbar />

            <!-- ================================ -->
            <!-- SCROLLABLE CONTENT AREA          -->
            <!-- ================================ -->
            <main class="erp-main flex-1 overflow-y-auto overscroll-none px-0 lg:px-8 py-0 lg:py-6 flex flex-col scrollbar-hide">
                <div class="animate-fade-in-up w-full flex-1 flex flex-col min-h-0 {{ trim($__env->yieldContent('dashboard-fullscreen')) ? 'px-0' : 'px-4' }} lg:px-0">
                    @yield('content')
                </div>
            </main>
        </div>

        <x-erp.mobile-navbar />

    </div>

    <script>
        function formatNumber(value) {
            return String(value).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        function unformatNumber(value) {
            return String(value ?? '').replace(/[.,]/g, '');
        }

        function bindFormatNumber() {
            $('.format-number').each(function () {
                const input = $(this);
                const initial = unformatNumber(input.val());
                if (initial !== '' && !Number.isNaN(Number(initial))) {
                    input.val(formatNumber(initial));
                }
                input.off('input.infruity-number').on('input.infruity-number', function () {
                    const raw = unformatNumber(input.val());
                    input.val(raw !== '' && !Number.isNaN(Number(raw)) ? formatNumber(raw) : '');
                });
            });
        }

        $(document).on('submit', 'form', function () {
            $(this).find('.format-number').each(function () {
                $(this).val(unformatNumber($(this).val()));
            });
        });

    </script>

    @include('partials.erp-chrome')

    @stack('scripts')
    @yield('script')
</body>

</html>
