<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Master') - ERP Portal</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,700&display=swap"
        rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />

    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

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
</head>

<body class="font-sans bg-[#e5e9f3] text-gray-800 antialiased h-screen overflow-hidden">

    <div x-data="erpChrome()" class="flex h-screen w-full">

        <!-- MOBILE SIDEBAR OVERLAY -->
        <div x-show="sidebarOpen" x-transition:enter="transition-opacity duration-300"
            x-transition:leave="transition-opacity duration-200" @click="sidebarOpen = false"
            class="fixed inset-0 bg-black/30 backdrop-blur-sm z-40 lg:hidden">
        </div>

        <!-- ================================ -->
        <!-- SIDEBAR                          -->
        <!-- ================================ -->
        <aside
            :class="[sidebarOpen ? 'translate-x-0' : '-translate-x-full', sidebarCollapsed ? 'sidebar-collapsed w-24' : 'w-64', theme === 'light' ? 'light-sidebar' : 'dark-sidebar']"
            class="fixed lg:static inset-y-0 left-0 z-50 shadow-none flex flex-col transition-all duration-300 lg:translate-x-0 lg:rounded-3xl lg:my-5 lg:ml-5 lg:h-[calc(100vh-2.5rem)] overflow-hidden shrink-0">

            <!-- Logo & Toggle -->
            <div class="flex items-center pt-5 pb-14"
                :class="sidebarCollapsed ? 'justify-center px-2' : 'justify-between px-6'">
                <a href="{{ url('/dashboard') }}"
                    class="flex items-center logo-link transition-transform duration-200 h-9"
                    :class="sidebarCollapsed ? 'hover:scale-110' : ''">
                    <img :src="theme === 'light' ? '{{ asset('infruity-ui/003. ERP/assets/logo/Infruity Logo - 6.png') }}' : '{{ asset('infruity-ui/003. ERP/assets/logo/Logo Putih 1.png') }}'" alt="Infruity Logo" class="h-9 logo-full">
                    <img :src="theme === 'light' ? '{{ asset('infruity-ui/003. ERP/assets/logo/Infruity Logo - 7.png') }}' : '{{ asset('infruity-ui/003. ERP/assets/logo/Logo IN Putih.png') }}'" alt="Infruity Icon" class="h-8 logo-mini hidden">
                </a>
                <div class="flex items-center gap-2 toggle-container">
                    <button @click="sidebarCollapsed = !sidebarCollapsed"
                        class="hidden lg:block text-white transition hover:opacity-80 shrink-0" title="Toggle Sidebar">
                        <svg width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"
                            class="text-[1.65rem] transition-transform duration-300"
                            :class="sidebarCollapsed ? 'rotate-180' : ''">
                            <rect x="3" y="3" width="18" height="18" rx="4" ry="4"></rect>
                            <line x1="9" y1="3" x2="9" y2="21"></line>
                        </svg>
                    </button>
                    <button @click="sidebarOpen = false"
                        class="lg:hidden p-2 rounded-xl hover:bg-white/10 text-white/50 hover:text-white/80 transition">
                        <i class="ph ph-x text-xl"></i>
                    </button>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav id="sidebar-nav" class="flex-1 overflow-y-auto overscroll-none sidebar-scroll px-3 pb-4 space-y-3">

                <div>
                    <a href="{{ url('/dashboard') }}"
                        class="sidebar-link {{ request()->is('dashboard', 'crm-dashboard') ? 'active shadow-lg shadow-black/10' : '' }} flex items-center justify-between px-4 py-3 rounded-xl text-sm text-white">
                        <div class="flex items-center gap-3">
                            <i class="ph ph-squares-four sidebar-icon text-xl w-6 text-center"></i>
                            <span class="font-medium">Dashboard</span>
                        </div>
                    </a>
                </div>

                <!-- === Master === -->
                <div>
                    <a href="{{ route('products.index') }}"
                        class="sidebar-link {{ request()->is('dashboard', 'crm-dashboard') ? '' : 'active shadow-lg shadow-black/10' }} flex items-center justify-between px-4 py-3 rounded-xl text-sm text-white">
                        <div class="flex items-center gap-3">
                            <i class="ph ph-database sidebar-icon text-xl w-6 text-center"></i>
                            <span class="font-medium">Master</span>
                        </div>
                    </a>
                    <div class="submenu-wrapper" x-show="!sidebarCollapsed">
                        <div class="py-1 space-y-0.5">
                            <a href="{{ route('products.index') }}"
                                class="submenu-link {{ request()->routeIs('products.*') ? 'active' : '' }} relative flex items-center gap-3 pl-12 pr-4 py-2.5 rounded-lg text-sm text-white/45">
                                <i class="ph ph-shopping-cart sidebar-icon text-lg w-5 text-center text-white/35"></i>
                                <span>Produk</span>
                            </a>
                            <a href="{{ route('receipt.index') }}"
                                class="submenu-link {{ request()->routeIs('receipt.*', 'product-receipt.*') ? 'active' : '' }} relative flex items-center gap-3 pl-12 pr-4 py-2.5 rounded-lg text-sm text-white/45">
                                <i class="ph ph-receipt sidebar-icon text-lg w-5 text-center text-white/35"></i>
                                <span>Resep Produk</span>
                            </a>
                            <a href="{{ route('unit.index') }}"
                                class="submenu-link {{ request()->routeIs('unit.*') ? 'active' : '' }} relative flex items-center gap-3 pl-12 pr-4 py-2.5 rounded-lg text-sm text-white/45">
                                <i class="ph ph-list-dashes sidebar-icon text-lg w-5 text-center text-white/35"></i>
                                <span>Satuan Produk</span>
                            </a>
                            <a href="{{ route('supplier.index') }}"
                                class="submenu-link {{ request()->routeIs('supplier.*') ? 'active' : '' }} relative flex items-center gap-3 pl-12 pr-4 py-2.5 rounded-lg text-sm text-white/45">
                                <i class="ph ph-storefront sidebar-icon text-lg w-5 text-center text-white/35"></i>
                                <span>Pemasok</span>
                            </a>
                            <a href="{{ route('customers.index') }}"
                                class="submenu-link {{ request()->routeIs('customers.*') ? 'active' : '' }} relative flex items-center gap-3 pl-12 pr-4 py-2.5 rounded-lg text-sm text-white/45">
                                <i class="ph ph-users sidebar-icon text-lg w-5 text-center text-white/35"></i>
                                <span>Pelanggan</span>
                            </a>
                            <a href="{{ route('branch.index') }}"
                                class="submenu-link {{ request()->routeIs('branch.*') ? 'active' : '' }} relative flex items-center gap-3 pl-12 pr-4 py-2.5 rounded-lg text-sm text-white/45">
                                <i class="ph ph-map-pin sidebar-icon text-lg w-5 text-center text-white/35"></i>
                                <span>Cabang</span>
                            </a>
                            <a href="{{ route('account.index') }}"
                                class="submenu-link {{ request()->routeIs('account.*') ? 'active' : '' }} relative flex items-center gap-3 pl-12 pr-4 py-2.5 rounded-lg text-sm text-white/45">
                                <i class="ph ph-circle sidebar-icon text-lg w-5 text-center text-white/35"></i>
                                <span>Akun Pengguna</span>
                            </a>
                            <a href="{{ route('position.index') }}"
                                class="submenu-link {{ request()->routeIs('position.*') ? 'active' : '' }} relative flex items-center gap-3 pl-12 pr-4 py-2.5 rounded-lg text-sm text-white/45">
                                <i class="ph ph-identification-badge sidebar-icon text-lg w-5 text-center text-white/35"></i>
                                <span>Jabatan</span>
                            </a>
                            <a href="{{ route('staff.index') }}"
                                class="submenu-link {{ request()->routeIs('staff.*') ? 'active' : '' }} relative flex items-center gap-3 pl-12 pr-4 py-2.5 rounded-lg text-sm text-white/45">
                                <i class="ph ph-user sidebar-icon text-lg w-5 text-center text-white/35"></i>
                                <span>Karyawan</span>
                            </a>
                            <a href="{{ route('kurir.index') }}"
                                class="submenu-link {{ request()->routeIs('kurir.*') ? 'active' : '' }} relative flex items-center gap-3 pl-12 pr-4 py-2.5 rounded-lg text-sm text-white/45">
                                <i class="ph ph-user-focus sidebar-icon text-lg w-5 text-center text-white/35"></i>
                                <span>Kurir</span>
                            </a>
                            <a href="{{ route('payment-method.index') }}"
                                class="submenu-link {{ request()->routeIs('payment-method.*') ? 'active' : '' }} relative flex items-center gap-3 pl-12 pr-4 py-2.5 rounded-lg text-sm text-white/45">
                                <i class="ph ph-credit-card sidebar-icon text-lg w-5 text-center text-white/35"></i>
                                <span>Metode Pembayaran</span>
                            </a>
                            <a href="{{ route('category.index') }}"
                                class="submenu-link {{ request()->routeIs('category.*') ? 'active' : '' }} relative flex items-center gap-3 pl-12 pr-4 py-2.5 rounded-lg text-sm text-white/45">
                                <i class="ph ph-tag sidebar-icon text-lg w-5 text-center text-white/35"></i>
                                <span>Kategori</span>
                            </a>
                            <a href="{{ route('roles.index') }}"
                                class="submenu-link {{ request()->routeIs('roles.*') ? 'active' : '' }} relative flex items-center gap-3 pl-12 pr-4 py-2.5 rounded-lg text-sm text-white/45">
                                <i class="ph ph-shield-check sidebar-icon text-lg w-5 text-center text-white/35"></i>
                                <span>Hak Akses</span>
                            </a>
                        </div>
                    </div>
                </div>

            </nav>

            <!-- User Profile Section -->
            <div class="px-4 pb-6 pt-4 border-t border-white/10 shrink-0 transition-all duration-300 flex items-center relative"
                 :class="sidebarCollapsed ? 'justify-center' : 'justify-between'" x-data="{ settingsOpen: false }">

                <!-- Settings Dropdown Menu -->
                <div x-show="settingsOpen" @click.outside="settingsOpen = false" x-cloak
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                    class="absolute right-4 bottom-[calc(100%-1rem)] mb-2 w-52 bg-white rounded-2xl shadow-xl shadow-black/5 border border-gray-100 overflow-hidden z-50 py-2">
                    <div class="px-4 py-3 border-b border-gray-50">
                        <p class="text-sm font-semibold text-gray-800">Pengaturan</p>
                        <p class="text-[0.65rem] text-gray-400">Atur preferensi akun</p>
                    </div>
                    <a href="#" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-600 hover:bg-gray-50 transition">
                        <i class="ph ph-user-circle text-lg"></i> Profil Saya
                    </a>
                    <a href="#" @click.prevent="theme = theme === 'light' ? 'dark' : 'light'; settingsOpen = false" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-600 hover:bg-gray-50 transition">
                        <i class="ph text-lg" :class="theme === 'light' ? 'ph-moon' : 'ph-sun'"></i>
                        <span x-text="theme === 'light' ? 'Mode Gelap' : 'Mode Terang'"></span>
                    </a>
                    <div class="border-t border-gray-50 mt-1 pt-1">
                        <form method="POST" action="{{ url('/logout') }}">
                            @csrf
                            <button type="submit" class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-strawberry hover:bg-strawberry/5 transition text-left">
                                <i class="ph ph-sign-out text-lg"></i> Keluar
                            </button>
                        </form>
                    </div>
                </div>

                <div class="flex items-center w-full transition-all duration-300" :class="sidebarCollapsed ? 'justify-center gap-0' : 'gap-3'">
                    <div class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center shrink-0 border border-white/20 overflow-hidden relative group cursor-pointer">
                        <i class="ph ph-user text-xl text-white"></i>
                    </div>
                    <div class="flex flex-col flex-1 toggle-container overflow-hidden">
                        <span class="text-sm font-semibold text-white whitespace-nowrap">{{ auth()->user()->name ?? 'Pengguna' }}</span>
                        <span class="text-xs text-white/50 whitespace-nowrap">Admin</span>
                    </div>
                    <div class="toggle-container shrink-0 ml-auto flex items-center">
                        <button @click="settingsOpen = !settingsOpen" class="text-white/40 hover:text-white transition flex items-center justify-center" title="Pengaturan">
                            <i class="ph ph-gear text-lg"></i>
                        </button>
                    </div>
                </div>
            </div>

        </aside>

        <!-- ================================ -->
        <!-- MAIN CONTENT AREA                -->
        <!-- ================================ -->
        <div class="flex-1 flex flex-col overflow-hidden h-full lg:h-auto">

            <!-- Mobile Header Bar -->
            @unless(trim($__env->yieldContent('dashboard-fullscreen')))
            <div class="md:hidden px-4 py-3 bg-white/95 backdrop-blur-md border-b border-gray-100 flex items-center justify-between z-20 shrink-0 shadow-xs">
                <div class="flex items-center gap-2">
                    <img src="{{ asset('images/infruity-wordmark.png') }}" alt="Infruity Logo" style="height: 20px; width: auto; max-width: 100px;" class="object-contain" />
                    <span class="text-gray-300 font-light text-sm">|</span>
                    <span class="text-[12px] font-bold text-gray-800">@yield('page-title', 'Master')</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-600 transition cursor-pointer relative">
                        <i class="ph-bold ph-bell text-xs"></i>
                        <span class="absolute top-1 right-1 w-1.5 h-1.5 bg-rose-500 rounded-full ring-1 ring-white"></span>
                    </div>
                </div>
            </div>
            @endunless

            @unless(trim($__env->yieldContent('hide-global-header')))
            <!-- Floating Header (hidden on mobile; mobile header bar above replaces it) -->
            <header
                class="hidden md:flex bg-white rounded-full shadow-sm border border-gray-100 mx-4 md:mx-6 lg:mx-8 mt-4 md:mt-6 lg:mt-5 px-5 lg:px-6 py-3 lg:py-4 items-center justify-between gap-4 z-30 sticky top-4 md:top-6 lg:top-5">

                <!-- Left: Hamburger + Title -->
                <div class="flex items-center gap-4 pl-2">
                    <button data-hamburger @click="sidebarOpen = !sidebarOpen"
                        class="lg:hidden p-2 -ml-2 rounded-full hover:bg-gray-100 text-gray-500 transition shrink-0">
                        <i class="ph ph-list text-2xl"></i>
                    </button>
                    <div class="hidden md:block">
                        <h1 class="text-base md:text-lg font-bold text-gray-900 leading-none mb-1.5">@yield('page-title', 'Master')</h1>
                        <div class="flex items-center gap-1 text-[0.65rem] text-gray-400 leading-none">
                            @yield('page-breadcrumb', 'Master')
                        </div>
                    </div>
                </div>

                <!-- Right: Notif + Profile -->
                <div class="flex items-center gap-3">

                    <!-- Notification Bell -->
                    <div class="relative" x-data="{ notifOpen: false, hasUnread: true }">
                        <button @click="notifOpen = !notifOpen; hasUnread = false"
                            class="w-9 h-9 rounded-full border border-gray-200 flex items-center justify-center hover:bg-gray-50 text-gray-600 transition shrink-0">
                            <div class="relative flex items-center justify-center">
                                <i class="ph ph-bell text-lg"></i>
                                <span x-show="hasUnread" class="absolute top-[3px] right-[1.5px] w-2 h-2 bg-strawberry rounded-full border border-white"></span>
                            </div>
                        </button>

                        <div x-show="notifOpen" @click.outside="notifOpen = false" x-cloak
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                            class="absolute right-0 top-12 w-80 bg-white rounded-2xl shadow-soft border border-gray-100 overflow-hidden z-50">
                            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                                <h4 class="font-serif font-semibold text-gray-800">Notifikasi</h4>
                            </div>
                            <div class="max-h-72 overflow-y-auto overscroll-none divide-y divide-gray-50">
                                <div class="notif-item flex gap-3 px-5 py-3.5">
                                    <p class="text-sm text-gray-400">Tidak ada notifikasi baru.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Profile -->
                    <div class="relative" x-data="{ profileOpen: false }">
                        <button @click="profileOpen = !profileOpen" class="w-9 h-9 rounded-full border-2 border-gray-200 hover:border-emerald-400 flex items-center justify-center text-gray-600 transition overflow-hidden">
                            <i class="ph ph-user text-lg"></i>
                        </button>
                        <div x-show="profileOpen" @click.outside="profileOpen = false" x-cloak
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                            class="absolute right-0 top-12 w-52 bg-white rounded-2xl shadow-soft border border-gray-100 overflow-hidden z-50 py-2">
                            <div class="px-4 py-3 border-b border-gray-50">
                                <p class="text-sm font-semibold text-gray-800">{{ auth()->user()->name ?? 'Pengguna' }}</p>
                            </div>
                            <form method="POST" action="{{ url('/logout') }}">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-strawberry hover:bg-strawberry/5 transition text-left">
                                    <i class="ph ph-sign-out text-lg"></i> Keluar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>
            @endunless

            <!-- ================================ -->
            <!-- SCROLLABLE CONTENT AREA          -->
            <!-- ================================ -->
            <main class="flex-1 overflow-y-auto overscroll-none px-0 lg:px-8 py-0 lg:py-6 pb-2 md:pb-4 lg:pb-6 flex flex-col scrollbar-hide">
                <div class="animate-fade-in-up w-full flex-1 flex flex-col min-h-0 {{ trim($__env->yieldContent('dashboard-fullscreen')) ? 'px-0' : 'px-4' }} lg:px-0">
                    @yield('content')
                </div>
            </main>
        </div>

    @unless(trim($__env->yieldContent('dashboard-fullscreen')))
    <!-- Mobile Bottom Navigation Pill -->
    @php
        $simpleMasterMobile = request()->routeIs(
            'unit.*', 'category.*', 'supplier.*', 'customers.*', 'branch.*',
            'account.*', 'position.*', 'staff.*', 'kurir.*', 'payment-method.*',
            'receipt.index', 'roles.index'
        );
        $showMasterFilter = request()->routeIs('receipt.index');
        $masterRouteName = request()->route()?->getName();
        $isProductsIndex = request()->is('products');
        $canMobileCreate = request()->routeIs('receipt.index')
            ? check_access('product-receipt.create')
            : (request()->routeIs('roles.index')
                ? check_access('role.store')
                : (! $simpleMasterMobile || (
            $masterRouteName && \App\Models\RoleMenu::checkAccess(explode('.', $masterRouteName)[0] . '.create')
        )));
        if ($isProductsIndex) {
            $canMobileCreate = check_access('products.create');
        }
    @endphp
    <div class="w-full fixed bottom-0 left-0 right-0 z-30 md:hidden pb-[calc(1rem+env(safe-area-inset-bottom))] pt-2 px-4 flex justify-center pointer-events-none" x-data="{ profileOpen: false, searchActive: false, filterActive: false }">
        <div class="relative w-full max-w-[360px]">
            <div x-show="profileOpen" x-cloak @click="profileOpen = false" class="fixed inset-0 z-30 pointer-events-auto"></div>

            <div x-show="profileOpen" x-cloak @click.outside="profileOpen = false"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 scale-95"
                 class="absolute right-0 bottom-[calc(100%+16px)] w-56 bg-white/40 backdrop-blur-xl rounded-3xl shadow-[0_8px_32px_rgba(0,0,0,0.1)] border border-white/60 overflow-hidden pointer-events-auto origin-bottom-right z-40">
                <div class="px-5 py-4 border-b border-white/50">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full border border-gray-200 bg-gray-100 flex items-center justify-center"><i class="ph ph-user text-gray-500"></i></div>
                        <div>
                            <p class="text-[13px] font-bold text-gray-800 leading-tight">{{ auth()->user()->nm_user ?? auth()->user()->username ?? 'Pengguna' }}</p>
                            <p class="text-[11px] font-medium text-gray-500 mt-0.5">Admin</p>
                        </div>
                    </div>
                </div>
                <div class="py-1.5">
                    <a href="#" class="flex items-center gap-3 px-5 py-2.5 text-[13px] font-medium text-gray-600 hover:text-[#0b595b] hover:bg-black/5 transition-colors">
                        <i class="ph ph-user-circle text-[18px]"></i> Profil Saya
                    </a>
                </div>
                <div class="border-t border-white/40 py-1.5 bg-red-50/30">
                    <form method="POST" action="{{ url('/logout') }}">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-3 px-5 py-2.5 text-[13px] font-bold text-red-600 hover:bg-red-500/10 transition-colors text-left">
                            <i class="ph ph-sign-out text-[18px]"></i> Keluar
                        </button>
                    </form>
                </div>
            </div>

            <div class="pointer-events-auto bg-white/10 backdrop-blur-md backdrop-saturate-150 border border-white/30 shadow-[0_8px_32px_rgba(0,0,0,0.1)] rounded-full h-[64px] p-2 flex items-center justify-between w-full relative">
                <button @click="sidebarOpen = !sidebarOpen; profileOpen = false"
                        :class="sidebarOpen ? 'text-[#0b595b] w-12' : 'text-gray-500 hover:text-[#0b595b] w-12'"
                        style="transition: color 300ms ease;"
                        class="h-full bg-transparent flex items-center justify-center rounded-full active:scale-95">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6" aria-hidden="true"><path d="m3 10 9-7 9 7v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 21v-7h6v7"/></svg>
                </button>

                <button @click="searchActive = !searchActive; filterActive = false; $dispatch('mobile-search-toggle')"
                        :class="searchActive ? 'text-[#0b595b] w-12' : 'text-gray-500 hover:text-[#0b595b] w-12'"
                        style="transition: color 300ms ease;"
                        class="h-full bg-transparent flex items-center justify-center rounded-full active:scale-95">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 transition-all">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>

                @if(! $simpleMasterMobile || $showMasterFilter)
                <button @click="filterActive = !filterActive; searchActive = false; $dispatch('mobile-filter-toggle')"
                        :class="filterActive ? 'text-[#0b595b] w-12' : 'text-gray-500 hover:text-[#0b595b] w-12'"
                        style="transition: color 300ms ease;"
                        class="relative h-full bg-transparent flex items-center justify-center rounded-full active:scale-95">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" class="w-6 h-6" aria-hidden="true"><path d="M4 7h16M4 17h16M8 4v6M16 14v6"/></svg>
                    <span x-show="$store.productFilters?.active" x-cloak class="absolute top-2 right-2 w-2 h-2 rounded-full bg-emerald-500 ring-2 ring-white"></span>
                </button>
                @endif

                @if($canMobileCreate)
                @if($simpleMasterMobile || $isProductsIndex)
                <button type="button" @click="$dispatch('mobile-add-toggle')"
                   class="h-full bg-transparent flex items-center justify-center rounded-full active:scale-95 text-gray-500 hover:text-[#0b595b] w-12"
                   style="transition: color 300ms ease;" aria-label="Tambah data">
                @else
                <a href="{{ route('products.create') }}"
                   class="h-full bg-transparent flex items-center justify-center rounded-full active:scale-95 text-gray-500 hover:text-[#0b595b] w-12"
                   style="transition: color 300ms ease;">
                @endif
                    <svg viewBox="0 0 24 24" class="w-[28px] h-[28px] transition-all" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.3" fill="none">
                        <path d="M2 12v3.45c0 2.849.698 4.005 1.606 4.944.94.909 2.098 1.608 4.946 1.608h6.896c2.848 0 4.006-.7 4.946-1.608C21.302 19.455 22 18.299 22 15.45V8.552c0-2.849-.698-4.006-1.606-4.945C19.454 2.7 18.296 2 15.448 2H8.552c-2.848 0-4.006.699-4.946 1.607C2.698 4.547 2 5.703 2 8.552Z"></path>
                        <line x1="7" x2="17" y1="12" y2="12"></line>
                        <line x1="12" x2="12" y1="7" y2="17"></line>
                    </svg>
                @if($simpleMasterMobile || $isProductsIndex)
                </button>
                @else
                </a>
                @endif
                @endif

                <button @click="profileOpen = !profileOpen"
                        :class="profileOpen ? 'text-gray-800 w-12' : 'text-gray-500 hover:text-gray-800 w-12'"
                        style="transition: color 300ms ease;"
                        class="h-full bg-transparent flex items-center justify-center rounded-full active:scale-95">
                    <div class="relative w-8 h-8 rounded-full border-[2.5px] transition-colors flex items-center justify-center bg-gray-100" :class="profileOpen ? 'border-gray-300' : 'border-transparent'">
                        <img src="https://api.dicebear.com/9.x/glass/svg?seed=Finance" alt="Profile" class="w-full h-full object-cover rounded-full" />
                    </div>
                </button>
            </div>
        </div>
    </div>
    @endunless
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

        function erpChrome() {
            return {
                sidebarOpen: false,
                sidebarCollapsed: false,
                theme: 'dark',
                toggleMenu(menu) {
                    if (this.sidebarCollapsed) this.sidebarCollapsed = false;
                },
            };
        }
    </script>

    @stack('scripts')
    @yield('script')
</body>

</html>
