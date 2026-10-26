@props(['mobileOnly' => false])

<style>
    [x-cloak] { display:none!important; }
    .erp-sidebar-overlay { position:fixed; inset:0; z-index:45; background:rgba(0,0,0,.32); -webkit-backdrop-filter:blur(4px); backdrop-filter:blur(4px); }
    .erp-sidebar { position:fixed; inset:0 auto 0 0; z-index:50; display:flex; width:256px; height:100dvh; flex:none; flex-direction:column; overflow:hidden; background:#0b595b; color:#fff; transform:translateX(-100%); transition:transform .3s cubic-bezier(.4,0,.2,1); }
    .erp-sidebar.is-open { transform:translateX(0); }
    .erp-sidebar--mobile-only { display:flex; }
    .erp-sidebar__brand-row { display:flex; flex:none; align-items:center; justify-content:space-between; padding:20px 20px 56px 24px; }
    .erp-sidebar__brand { display:flex; height:36px; align-items:center; transition:transform .2s ease; }
    .erp-sidebar__brand img { display:block; width:auto; height:36px; object-fit:contain; }
    .erp-sidebar__brand .erp-sidebar__logo-mini { display:none; height:32px; }
    .erp-sidebar__tools { display:flex; align-items:center; gap:8px; }
    .erp-sidebar__tool { display:flex; width:36px; height:36px; align-items:center; justify-content:center; border:0; border-radius:12px; background:transparent; color:rgba(255,255,255,.7); cursor:pointer; transition:background .18s ease,color .18s ease; }
    .erp-sidebar__tool:hover { background:rgba(255,255,255,.1); color:#fff; }
    .erp-sidebar__tool svg { width:25px; height:25px; }
    .erp-sidebar__close { display:flex; }
    .erp-sidebar__collapse { display:none; }
    .erp-sidebar__nav { flex:1; min-height:0; overflow-y:auto; overscroll-behavior:contain; padding:0 12px 16px; scrollbar-width:thin; scrollbar-color:rgba(255,255,255,.2) transparent; }
    .erp-sidebar__nav::-webkit-scrollbar { width:2px; }
    .erp-sidebar__nav::-webkit-scrollbar-track { background:transparent; }
    .erp-sidebar__nav::-webkit-scrollbar-thumb { border-radius:10px; background:rgba(255,255,255,.2); }
    .erp-sidebar__group { margin-bottom:12px; }
    .erp-sidebar .sidebar-link { display:flex; width:100%; min-height:44px; align-items:center; justify-content:space-between; gap:12px; overflow:hidden; border:0; border-radius:12px; padding:0 16px; background:transparent; color:#fff; font-size:14px; line-height:1.25; text-align:left; text-decoration:none; white-space:nowrap; cursor:pointer; transition:background .2s ease,color .2s ease; }
    .erp-sidebar .sidebar-link:hover { background:rgba(255,255,255,.08); }
    .erp-sidebar .sidebar-link.active { background:#d1f36e; color:#102f31!important; font-weight:600; box-shadow:0 2px 6px rgba(209,243,110,.2); }
    .erp-sidebar .sidebar-link.active .sidebar-icon { color:#102f31!important; }
    .erp-sidebar__link-label { display:flex; min-width:0; align-items:center; gap:12px; }
    .erp-sidebar .sidebar-icon { width:24px; flex:none; color:#fff; font-size:21px; text-align:center; }
    .erp-sidebar .submenu-wrapper { display:grid; grid-template-rows:1fr; overflow:hidden; opacity:1; transition:grid-template-rows .3s ease,opacity .3s ease; }
    .erp-sidebar .submenu-wrapper > div { min-height:0; overflow:hidden; }
    .erp-sidebar .submenu-link { position:relative; display:flex; min-height:40px; align-items:center; gap:12px; overflow:hidden; border-radius:8px; padding:10px 16px 10px 48px; color:rgba(255,255,255,.45); font-size:14px; line-height:1.35; text-decoration:none; white-space:normal; transition:color .18s ease,background .18s ease; }
    .erp-sidebar .submenu-link::before { position:absolute; top:50%; left:28px; width:6px; height:6px; border-radius:50%; background:rgba(255,255,255,.28); content:''; transform:translateY(-50%); transition:all .18s ease; }
    .erp-sidebar .submenu-link:hover { color:rgba(255,255,255,.95); background:rgba(255,255,255,.04); }
    .erp-sidebar .submenu-link:hover::before { background:rgba(255,255,255,.7); transform:translateY(-50%) scale(1.15); }
    .erp-sidebar .submenu-link.active { color:#fff; font-weight:600; }
    .erp-sidebar .submenu-link.active::before { background:#f2b400; box-shadow:0 0 10px rgba(242,180,0,.5); transform:translateY(-50%) scale(1.2); }
    .erp-sidebar .submenu-link.level-3 { padding-left:64px; }
    .erp-sidebar .submenu-link.level-3::before { left:44px; }
    .erp-sidebar__nested-toggle { display:flex; width:100%; align-items:center; justify-content:space-between; border:0; background:transparent; text-align:left; }
    .erp-sidebar__nested-toggle .chevron-rotate { color:rgba(255,255,255,.48); font-size:14px; transition:transform .2s ease; }
    .erp-sidebar__nested-toggle .chevron-rotate.open { transform:rotate(180deg); }
    .erp-sidebar__profile { position:relative; display:none; flex:none; align-items:center; justify-content:space-between; gap:12px; border-top:1px solid rgba(255,255,255,.1); padding:16px 16px 24px; }
    .erp-sidebar__profile-main { display:flex; min-width:0; flex:1; align-items:center; gap:12px; }
    .erp-sidebar__avatar { display:flex; width:40px; height:40px; flex:none; align-items:center; justify-content:center; overflow:hidden; border:1px solid rgba(255,255,255,.2); border-radius:50%; background:rgba(255,255,255,.1); color:#fff; font-size:21px; }
    .erp-sidebar__profile-copy { display:flex; min-width:0; flex:1; flex-direction:column; overflow:hidden; }
    .erp-sidebar__profile-name { overflow:hidden; color:#fff; font-size:13px; font-weight:600; text-overflow:ellipsis; white-space:nowrap; }
    .erp-sidebar__profile-subtitle { margin-top:2px; color:rgba(255,255,255,.58); font-size:11px; white-space:nowrap; }
    .erp-sidebar__settings-menu { position:absolute; right:12px; bottom:calc(100% - 8px); z-index:60; width:208px; overflow:hidden; border:1px solid #f3f4f6; border-radius:16px; background:#fff; padding:8px 0; color:#374151; box-shadow:0 16px 40px -12px rgba(0,0,0,.22); }
    .erp-sidebar__settings-menu[hidden] { display:none; }
    .erp-sidebar__settings-heading { border-bottom:1px solid #f3f4f6; padding:10px 14px; color:#6b7280; font-size:10px; }
    .erp-sidebar__settings-heading strong { display:block; color:#1f2937; font-size:13px; }
    .erp-sidebar__settings-link { display:flex; width:100%; min-height:40px; align-items:center; gap:10px; border:0; padding:0 14px; background:transparent; color:#4b5563; font-size:12px; text-align:left; text-decoration:none; cursor:pointer; }
    .erp-sidebar__settings-link:hover { background:#f9fafb; color:#0b595b; }
    .erp-sidebar__settings-link.is-danger { border-top:1px solid #f3f4f6; color:#d6455d; }
    .erp-sidebar.light-sidebar { border-right:1px solid #f3f4f6; background:#fff; color:#374151; }
    .erp-sidebar.light-sidebar .sidebar-link { color:#4b5563; }
    .erp-sidebar.light-sidebar .sidebar-link.active { color:#102f31!important; }
    .erp-sidebar.light-sidebar .sidebar-icon { color:#4b5563; }
    .erp-sidebar.light-sidebar .submenu-link { color:#6b7280; }
    .erp-sidebar.light-sidebar .submenu-link.active { color:#0f5c45; }
    .erp-sidebar.light-sidebar .submenu-link::before { background:#d1d5db; }
    .erp-sidebar.light-sidebar .submenu-link.active::before { background:#0f5c45; }
    .erp-sidebar.light-sidebar .erp-sidebar__profile { border-color:#f3f4f6; }
    .erp-sidebar.light-sidebar .erp-sidebar__avatar { border-color:#e5e7eb; background:#f9fafb; color:#4b5563; }
    .erp-sidebar.light-sidebar .erp-sidebar__profile-name { color:#1f2937; }
    .erp-sidebar.light-sidebar .erp-sidebar__profile-subtitle { color:#6b7280; }
    .erp-sidebar.sidebar-collapsed { width:96px; }
    .erp-sidebar.sidebar-collapsed .erp-sidebar__brand-row { justify-content:center; padding-right:8px; padding-left:8px; }
    .erp-sidebar.sidebar-collapsed .erp-sidebar__brand { justify-content:center; }
    .erp-sidebar.sidebar-collapsed .erp-sidebar__logo-full,
    .erp-sidebar.sidebar-collapsed .sidebar-link span,
    .erp-sidebar.sidebar-collapsed .submenu-link span,
    .erp-sidebar.sidebar-collapsed .chevron-rotate,
    .erp-sidebar.sidebar-collapsed .erp-sidebar__profile-copy,
    .erp-sidebar.sidebar-collapsed .erp-sidebar__tools { display:none!important; }
    .erp-sidebar.sidebar-collapsed .erp-sidebar__logo-mini { display:block; }
    .erp-sidebar.sidebar-collapsed .sidebar-link { justify-content:flex-start; padding-left:24px; }
    .erp-sidebar.sidebar-collapsed .submenu-wrapper { display:none; }
    .erp-sidebar.sidebar-collapsed .erp-sidebar__profile { justify-content:center; }
    @media (min-width:640px) { .erp-sidebar__profile { display:flex; } }
    @media (min-width:768px) { .erp-sidebar--mobile-only { display:none!important; } }
    @media (min-width:1024px) {
        .erp-sidebar { position:relative; inset:auto; height:calc(100dvh - 40px); margin:20px 0 20px 20px; border-radius:24px; transform:none!important; }
        .erp-sidebar__close { display:none; }
        .erp-sidebar__collapse { display:flex; }
        .erp-sidebar-overlay { display:none!important; }
    }
</style>

<div class="erp-sidebar-overlay" x-show="sidebarOpen" x-transition:enter="transition-opacity duration-300" x-transition:leave="transition-opacity duration-200" @click="sidebarOpen = false" x-cloak></div>
<aside class="erp-sidebar {{ $mobileOnly ? 'erp-sidebar--mobile-only' : '' }}"
    :class="[sidebarOpen ? 'is-open' : '', sidebarCollapsed ? 'sidebar-collapsed' : '', theme === 'light' ? 'light-sidebar' : 'dark-sidebar']"
    aria-label="Navigasi utama">
    <div class="erp-sidebar__brand-row">
        <a href="{{ url('/dashboard') }}" @click.prevent="if (sidebarCollapsed) sidebarCollapsed = false" class="erp-sidebar__brand" :title="sidebarCollapsed ? 'Perbesar Sidebar' : 'Infruity ERP'">
            <img :src="theme === 'light' ? '{{ asset('infruity-ui/003. ERP/assets/logo/Infruity Logo - 6.png') }}' : '{{ asset('infruity-ui/003. ERP/assets/logo/Logo Putih 1.png') }}'" alt="Infruity" class="erp-sidebar__logo-full">
            <img :src="theme === 'light' ? '{{ asset('infruity-ui/003. ERP/assets/logo/Infruity Logo - 7.png') }}' : '{{ asset('infruity-ui/003. ERP/assets/logo/Logo IN Putih.png') }}'" alt="Infruity" class="erp-sidebar__logo-mini">
        </a>
        <div class="erp-sidebar__tools">
            <button type="button" @click="sidebarCollapsed = !sidebarCollapsed" class="erp-sidebar__tool erp-sidebar__collapse" aria-label="Ciutkan sidebar" title="Ciutkan sidebar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" :style="sidebarCollapsed ? 'transform:rotate(180deg)' : ''"><rect x="3" y="3" width="18" height="18" rx="4"></rect><line x1="9" y1="3" x2="9" y2="21"></line></svg>
            </button>
            <button type="button" @click="sidebarOpen = false" class="erp-sidebar__tool erp-sidebar__close" aria-label="Tutup menu"><i class="ph ph-x text-xl" aria-hidden="true"></i></button>
        </div>
    </div>

    <nav id="sidebar-nav" class="erp-sidebar__nav" aria-label="Menu ERP">
        <div class="erp-sidebar__group">
            <a href="{{ url('/dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard', 'admin.crm-dashboard', 'crm.dashboard') ? 'active' : '' }}" @if(request()->routeIs('dashboard', 'admin.crm-dashboard', 'crm.dashboard')) aria-current="page" @endif>
                <span class="erp-sidebar__link-label"><i class="ph ph-squares-four sidebar-icon" aria-hidden="true"></i><span class="font-medium">Dashboard</span></span>
            </a>
        </div>

        @php
            $masterRoutes = ['products.*', 'receipt.*', 'product-receipt.*', 'unit.*', 'supplier.*', 'customers.*', 'branch.*', 'user.*', 'position.*', 'staff.*', 'kurir.*', 'payment-method.*', 'roles.*'];
            $inventoryRoutes = ['sortir.*', 'transfer-pengirim.*', 'transfer-penerima.*', 'stock-opname.*'];
            $salesRoutes = ['pos.*', 'order-book.*', 'delivery-order', 'delivery-order.*'];
            $reportRoutes = ['reports.hub', 'report-*', 'product-stock', 'product-stock.*'];
        @endphp

        <div class="erp-sidebar__group">
            <a href="#" data-type="header" @click.prevent="if (sidebarCollapsed) sidebarCollapsed = false" class="sidebar-link {{ request()->routeIs(...$masterRoutes) ? 'active' : '' }}">
                <span class="erp-sidebar__link-label"><i class="ph ph-database sidebar-icon" aria-hidden="true"></i><span class="font-medium">Master</span></span>
            </a>
            <div class="submenu-wrapper" x-show="!sidebarCollapsed"><div class="py-1 space-y-0.5">
                <a href="{{ route('products.index') }}" class="submenu-link {{ request()->routeIs('products.*') ? 'active' : '' }}"><span>Produk</span></a>
                <a href="{{ route('receipt.index') }}" class="submenu-link {{ request()->routeIs('receipt.*', 'product-receipt.*') ? 'active' : '' }}"><span>Resep Produk</span></a>
                <a href="{{ route('unit.index') }}" class="submenu-link {{ request()->routeIs('unit.*') ? 'active' : '' }}"><span>Satuan Produk</span></a>
                <a href="{{ route('supplier.index') }}" class="submenu-link {{ request()->routeIs('supplier.*') ? 'active' : '' }}"><span>Pemasok</span></a>
                <a href="{{ route('customers.index') }}" class="submenu-link {{ request()->routeIs('customers.*') ? 'active' : '' }}"><span>Pelanggan</span></a>
                <a href="{{ route('branch.index') }}" class="submenu-link {{ request()->routeIs('branch.*') ? 'active' : '' }}"><span>Cabang</span></a>
                <a href="{{ route('user.index') }}" class="submenu-link {{ request()->routeIs('user.*') ? 'active' : '' }}"><span>Akun Pengguna</span></a>
                <a href="{{ route('position.index') }}" class="submenu-link {{ request()->routeIs('position.*') ? 'active' : '' }}"><span>Jabatan</span></a>
                <a href="{{ route('staff.index') }}" class="submenu-link {{ request()->routeIs('staff.*') ? 'active' : '' }}"><span>Karyawan</span></a>
                <a href="{{ route('kurir.index') }}" class="submenu-link {{ request()->routeIs('kurir.*') ? 'active' : '' }}"><span>Kurir</span></a>
                <a href="{{ route('payment-method.index') }}" class="submenu-link {{ request()->routeIs('payment-method.*') ? 'active' : '' }}"><span>Metode Pembayaran</span></a>
                <a href="{{ route('roles.index') }}" class="submenu-link {{ request()->routeIs('roles.*') ? 'active' : '' }}"><span>Hak Akses</span></a>
            </div></div>
        </div>

        <div class="erp-sidebar__group">
            <a href="#" data-type="header" @click.prevent="if (sidebarCollapsed) sidebarCollapsed = false" class="sidebar-link {{ request()->routeIs('wholesale.*') ? 'active' : '' }}">
                <span class="erp-sidebar__link-label"><i class="ph ph-bag sidebar-icon" aria-hidden="true"></i><span class="font-medium">Pembelian</span></span>
            </a>
            <div class="submenu-wrapper" x-show="!sidebarCollapsed"><div class="py-1 space-y-0.5">
                <a href="{{ route('wholesale.index') }}" class="submenu-link {{ request()->routeIs('wholesale.*') ? 'active' : '' }}"><span>Pengadaan</span></a>
            </div></div>
        </div>

        <div class="erp-sidebar__group">
            <a href="#" data-type="header" @click.prevent="if (sidebarCollapsed) sidebarCollapsed = false" class="sidebar-link {{ request()->routeIs(...$inventoryRoutes) ? 'active' : '' }}">
                <span class="erp-sidebar__link-label"><i class="ph ph-package sidebar-icon" aria-hidden="true"></i><span class="font-medium">Inventaris</span></span>
            </a>
            <div class="submenu-wrapper" x-show="!sidebarCollapsed"><div class="py-1 space-y-0.5">
                <a href="{{ route('sortir.index') }}" class="submenu-link {{ request()->routeIs('sortir.*') ? 'active' : '' }}"><span>Sortir Barang</span></a>
                <div x-data="{ transferOpen: true }">
                    <button type="button" @click="transferOpen = !transferOpen" class="submenu-link erp-sidebar__nested-toggle" :aria-expanded="transferOpen"><span>Pindah Stok</span><i class="ph ph-caret-down chevron-rotate" :class="transferOpen ? 'open' : ''" aria-hidden="true"></i></button>
                    <div x-show="transferOpen" x-transition><a href="{{ route('transfer-pengirim.index') }}" class="submenu-link level-3 {{ request()->routeIs('transfer-pengirim.*') ? 'active' : '' }}"><span>Mengirim</span></a><a href="{{ route('transfer-penerima.index') }}" class="submenu-link level-3 {{ request()->routeIs('transfer-penerima.*') ? 'active' : '' }}"><span>Menerima</span></a></div>
                </div>
                <a href="{{ route('stock-opname.index') }}" class="submenu-link {{ request()->routeIs('stock-opname.*') ? 'active' : '' }}"><span>Stok Opname</span></a>
            </div></div>
        </div>

        <div class="erp-sidebar__group">
            <a href="#" data-type="header" @click.prevent="if (sidebarCollapsed) sidebarCollapsed = false" class="sidebar-link {{ request()->routeIs('production.*') ? 'active' : '' }}">
                <span class="erp-sidebar__link-label"><i class="ph ph-factory sidebar-icon" aria-hidden="true"></i><span class="font-medium">Produksi</span></span>
            </a>
            <div class="submenu-wrapper" x-show="!sidebarCollapsed"><div class="py-1 space-y-0.5">
                <a href="{{ route('production.index') }}" class="submenu-link {{ request()->routeIs('production.*') ? 'active' : '' }}"><span>Produksi Barang</span></a>
            </div></div>
        </div>

        <div class="erp-sidebar__group">
            <a href="#" data-type="header" @click.prevent="if (sidebarCollapsed) sidebarCollapsed = false" class="sidebar-link {{ request()->routeIs(...$salesRoutes) ? 'active' : '' }}">
                <span class="erp-sidebar__link-label"><i class="ph ph-shopping-cart sidebar-icon" aria-hidden="true"></i><span class="font-medium">Penjualan</span></span>
            </a>
            <div class="submenu-wrapper" x-show="!sidebarCollapsed"><div class="py-1 space-y-0.5">
                <a href="{{ route('pos.index') }}" class="submenu-link {{ request()->routeIs('pos.*') ? 'active' : '' }}"><span>Kasir / POS</span></a>
                <a href="{{ route('order-book.index') }}" class="submenu-link {{ request()->routeIs('order-book.*') ? 'active' : '' }}"><span>Pesanan</span></a>
                <a href="{{ route('delivery-order') }}" class="submenu-link {{ request()->routeIs('delivery-order', 'delivery-order.*') ? 'active' : '' }}"><span>Pengiriman</span></a>
            </div></div>
        </div>

        <div class="erp-sidebar__group">
            <a href="#" data-type="header" @click.prevent="if (sidebarCollapsed) sidebarCollapsed = false" class="sidebar-link {{ request()->routeIs('other-book', 'expenditure.*') ? 'active' : '' }}">
                <span class="erp-sidebar__link-label"><i class="ph ph-currency-circle-dollar sidebar-icon" aria-hidden="true"></i><span class="font-medium">Keuangan</span></span>
            </a>
            <div class="submenu-wrapper" x-show="!sidebarCollapsed"><div class="py-1 space-y-0.5">
                <a href="{{ route('other-book') }}" class="submenu-link {{ request()->routeIs('other-book') ? 'active' : '' }}"><span>Pemasukan</span></a>
                <a href="{{ route('expenditure.index') }}" class="submenu-link {{ request()->routeIs('expenditure.*') ? 'active' : '' }}"><span>Pengeluaran</span></a>
            </div></div>
        </div>

        <div class="erp-sidebar__group">
            <a href="{{ route('reports.hub') }}" class="sidebar-link {{ request()->routeIs(...$reportRoutes) ? 'active' : '' }}">
                <span class="erp-sidebar__link-label"><i class="ph ph-chart-line-up sidebar-icon" aria-hidden="true"></i><span class="font-medium">Laporan</span></span>
            </a>
            <div class="submenu-wrapper" x-show="!sidebarCollapsed"><div class="py-1 space-y-0.5">
                <a href="{{ route('product-stock') }}" class="submenu-link {{ request()->routeIs('product-stock', 'product-stock.*') ? 'active' : '' }}"><span>Stok Barang</span></a>
                <a href="{{ route('report-total-aset') }}" class="submenu-link {{ request()->routeIs('report-total-aset') ? 'active' : '' }}"><span>Total Aset</span></a>
                <a href="{{ route('report-product-sales.index') }}" class="submenu-link {{ request()->routeIs('report-product-sales.*') ? 'active' : '' }}"><span>Pendapatan Penjualan</span></a>
                <a href="{{ route('report-profit-revenue.index') }}" class="submenu-link {{ request()->routeIs('report-profit-revenue.*') ? 'active' : '' }}"><span>Keuntungan Penjualan</span></a>
                <a href="{{ route('report-profit-adjusted.index') }}" class="submenu-link {{ request()->routeIs('report-profit-adjusted.*') ? 'active' : '' }}"><span>Keuntungan Setelah Penyesuaian</span></a>
                <a href="{{ route('report-shipping-cost') }}" class="submenu-link {{ request()->routeIs('report-shipping-cost') ? 'active' : '' }}"><span>Ongkos Kirim</span></a>
                <a href="{{ route('report-product-buang') }}" class="submenu-link {{ request()->routeIs('report-product-buang') ? 'active' : '' }}"><span>Kerugian Barang Buang</span></a>
            </div></div>
        </div>

        <div class="erp-sidebar__group">
            <a href="{{ route('settings.index') }}" class="sidebar-link {{ request()->routeIs('settings.*', 'change-password', 'setting-nota.*') ? 'active' : '' }}" @if(request()->routeIs('settings.*')) aria-current="page" @endif>
                <span class="erp-sidebar__link-label"><i class="ph ph-gear sidebar-icon" aria-hidden="true"></i><span class="font-medium">Pengaturan</span></span>
            </a>
            <div class="submenu-wrapper" x-show="!sidebarCollapsed"><div class="py-1 space-y-0.5">
                <a href="{{ route('settings.index') }}" class="submenu-link {{ request()->routeIs('settings.*', 'change-password') ? 'active' : '' }}"><span>Profil &amp; Akun</span></a>
                <a href="{{ route('setting-nota.index') }}" class="submenu-link {{ request()->routeIs('setting-nota.*') ? 'active' : '' }}"><span>Nota &amp; Cetak</span></a>
            </div></div>
        </div>
    </nav>

    <div class="erp-sidebar__profile" x-data="{ settingsOpen: false }">
        <div class="erp-sidebar__settings-menu" x-show="settingsOpen" x-cloak @click.outside="settingsOpen = false" @keydown.escape.window="settingsOpen = false">
            <div class="erp-sidebar__settings-heading"><strong>{{ auth()->user()->nm_user ?? auth()->user()->username ?? auth()->user()->name ?? 'Pengguna' }}</strong>Pengaturan akun</div>
            <a href="{{ route('settings.index') }}" class="erp-sidebar__settings-link"><i class="ph ph-user-circle" aria-hidden="true"></i>Pengaturan</a>
            <a href="{{ route('change-password') }}" class="erp-sidebar__settings-link"><i class="ph ph-lock-key" aria-hidden="true"></i>Ubah password</a>
            <button type="button" @click="theme = theme === 'light' ? 'dark' : 'light'; settingsOpen = false" class="erp-sidebar__settings-link"><i class="ph" :class="theme === 'light' ? 'ph-moon' : 'ph-sun'" aria-hidden="true"></i><span x-text="theme === 'light' ? 'Mode Gelap' : 'Mode Terang'"></span></button>
            <form method="POST" action="{{ url('/logout') }}">@csrf<button type="submit" class="erp-sidebar__settings-link is-danger"><i class="ph ph-sign-out" aria-hidden="true"></i>Keluar</button></form>
        </div>
        <div class="erp-sidebar__profile-main">
            <div class="erp-sidebar__avatar" aria-hidden="true"><i class="ph ph-user"></i></div>
            <div class="erp-sidebar__profile-copy"><span class="erp-sidebar__profile-name">{{ auth()->user()->nm_user ?? auth()->user()->username ?? auth()->user()->name ?? 'Pengguna' }}</span><span class="erp-sidebar__profile-subtitle">Akun Infruity</span></div>
        </div>
        <button type="button" @click="settingsOpen = !settingsOpen" :aria-expanded="settingsOpen" class="erp-sidebar__tool" aria-label="Pengaturan akun"><i class="ph ph-gear text-lg" aria-hidden="true"></i></button>
    </div>
</aside>

<script>
    (function () {
        var nav = document.getElementById('sidebar-nav');
        if (!nav) return;
        var key = 'erp-sidebar-scroll';
        var read = function () { try { return sessionStorage.getItem(key); } catch (e) { return null; } };
        var save = function () { try { sessionStorage.setItem(key, nav.scrollTop); } catch (e) {} };

        var saved = read();
        if (saved !== null) nav.scrollTop = parseInt(saved, 10) || 0;

        // Pastikan menu aktif selalu terlihat, termasuk saat pertama kali dibuka.
        var active = nav.querySelector('.submenu-link.active') || nav.querySelector('.sidebar-link.active');
        if (active) {
            var top = active.offsetTop - nav.offsetTop;
            var bottom = top + active.offsetHeight;
            if (top < nav.scrollTop || bottom > nav.scrollTop + nav.clientHeight) {
                nav.scrollTop = Math.max(0, top - (nav.clientHeight - active.offsetHeight) / 2);
            }
        }

        nav.addEventListener('scroll', save, { passive: true });
        nav.addEventListener('click', save);
        window.addEventListener('pagehide', save);
    })();
</script>
