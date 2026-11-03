<!DOCTYPE html>
<html lang="en">
<!--begin::Head-->

<head>
    <base href="/" />
    <title>Infruity - UMKM Jual Buah Terbaik di Negeri Ini</title>
    <meta charset="utf-8" />
    <meta name="description" content="UMKM jual buah dengan harga terjangkau" />
    <meta name="keywords" content="buah, umkm, pasar" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta property="og:locale" content="en_US" />
    <meta property="og:type" content="article" />
    <meta property="og:title" content="Infruity - UMKM Olahan Buah Terbesar di Abad Ini" />
    <meta property="og:url" content="https://keenthemes.com/metronic" />
    <meta property="og:site_name" content="Infruity" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- <link rel="canonical" href="https://preview.keenthemes.com/metronic8" /> --}}
    <link rel="shortcut icon" href="{{ asset('assets/media/logos/logo-infruity.png') }}" />
    <!--begin::Fonts(mandatory for all pages)-->

    <!--end::Fonts-->
    <!--begin::Vendor Stylesheets(used for this page only)-->
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}">
    <!--end::Vendor Stylesheets-->
    <!--begin::Global Stylesheets Bundle(mandatory for all pages)-->
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/plugins/global/plugins.bundle.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/style.bundle.css') }}">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    {{-- @livewireStyles --}}

    <!--end::Global Stylesheets Bundle-->
    <script>
        // Frame-busting to prevent site from being loaded within a frame without permission (click-jacking) if (window.top != window.self) { window.top.location.replace(window.self.location.href); }
    </script>
<link rel="stylesheet" href="{{ asset('assets/fonts/infruity/fonts.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/phosphor/phosphor.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/erp-workspace.css') }}?v={{ filemtime(public_path('assets/css/erp-workspace.css')) }}">
<script defer src="{{ asset('assets/js/erp-collections.js') }}?v={{ filemtime(public_path('assets/js/erp-collections.js')) }}"></script>
</head>
<!--end::Head-->
<!--begin::Body-->

<body id="kt_app_body" data-kt-app-header-fixed-mobile="true" data-kt-app-toolbar-enabled="true" class="app-default">
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
    <!--begin::App-->
    <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
        <!--begin::Page-->
        <div class="app-page flex-column flex-column-fluid" id="kt_app_page">
            @include('template.header')
            <!--begin::Wrapper-->
            <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
                <!--begin::Toolbar-->
                @include('template.toolbar')
                <!--end::Toolbar-->
                <!--begin::Wrapper container-->
                <div class="app-container container-xxl">
                    <!--begin::Main-->
                    <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
                        <!--begin::Content wrapper-->
                        <div class="d-flex flex-column flex-column-fluid">
                            <!--begin::Content-->
                            <div id="kt_app_content" class="app-content">
                                <!--begin::Sitemap-->
                                @include('template.notif')
                                @yield('content')
                                <!--end::Sitemap-->
                            </div>
                            <!--end::Content-->
                        </div>
                        <!--end::Content wrapper-->
                        @include('template.footer')
                    </div>
                    <!--end:::Main-->
                </div>
                <!--end::Wrapper container-->
            </div>
            <!--end::Wrapper-->
        </div>
        <!--end::Page-->
    </div>
    <!--end::App-->
    <!--begin::Drawers-->
    <!--begin::Activities drawer-->
    {{-- @include('template.drawer') --}}
    <!--end::Activities drawer-->
    <!--begin::Chat drawer-->
    {{-- @include('template.drawer-chat') --}}
    <!--end::Chat drawer-->
    <!--begin::Chat drawer-->
    {{-- @include('template.drawer-shopping-cart') --}}
    <!--end::Chat drawer-->
    <!--end::Drawers-->
    <!--begin::Scrolltop-->
    <div id="kt_scrolltop" class="scrolltop" data-kt-scrolltop="true">
        <i class="ki-outline ki-arrow-up"></i>
    </div>
    <!--end::Scrolltop-->
    <!--begin::Modals-->
    <!--begin::Modal - Upgrade plan-->
    {{-- @include('template.upgrade-plan') --}}
    <!--end::Modal - Upgrade plan-->
    <!--begin::Modal - Users Search-->
    {{-- @include('template.modal-user') --}}
    <!--end::Modal - Users Search-->
    <!--begin::Modal - Invite Friends-->
    {{-- @include('template.modal-invite-user') --}}
    <!--end::Modal - Invite Friend-->
    <!--end::Modals-->
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
    {{-- <script src="{{ asset('assets/js/widgets.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/custom/widgets.js') }}"></script>
    <script src="{{ asset('assets/js/custom/apps/chat/chat.js') }}"></script>
    <script src="{{ asset('assets/js/custom/utilities/modals/upgrade-plan.js') }}"></script>
    <script src="{{ asset('assets/js/custom/utilities/modals/users-search.js') }}"></script> --}}

    @if (isset($page_plugin_js))
        @foreach ($page_plugin_js as $item)
            <script type="text/javascript" src="{{ asset($item) }}"></script>
        @endforeach
    @endif
    @yield('script')
    <!--end::Custom Javascript-->
    <!--end::Javascript-->
    {{-- @livewireScripts --}}
</body>
<!--end::Body-->

</html>
