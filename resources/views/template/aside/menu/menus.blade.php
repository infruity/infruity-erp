@php
    $link = Request::segment(1);
    if (Request::segment(2) != null && !is_numeric(Request::segment(2))) {
        $link .= '/' . Request::segment(2);

        if (Request::segment(3) != null && !is_numeric(Request::segment(3))) {
            $link .= '/' . Request::segment(3);
        }
    }
@endphp
<div class="menu-item pt-5">
    <!--begin:Menu content-->
    <div class="menu-content">
        <span class="menu-heading fw-bold text-uppercase fs-7">Master</span>
    </div>
    <!--end:Menu content-->
</div>
<!--begin:Menu item-->
@if (check_access('products.index') || check_access('products.create'))
    <div data-kt-menu-trigger="click"
        class="menu-item {{ in_array(Request::segment(1), ['products']) ? 'here show' : '' }} menu-accordion">
        <!--begin:Menu link-->
        <span class="menu-link">
            <span class="menu-icon">
                <i class="ki-duotone ki-basket fs-2">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
            </span>
            <span class="menu-title">Produk</span>
            <span class="menu-arrow"></span>
        </span>
        <!--end:Menu link-->
        <!--begin:Menu sub-->
        <div class="menu-sub menu-sub-accordion">
            @if (check_access('products.index'))
                <!--begin:Menu item-->
                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ $link == 'products' ? 'active' : '' }}" href="{{ url('products') }}">
                        <span class="menu-bullet">
                            <span class="bullet bullet-dot"></span>
                        </span>
                        <span class="menu-title">Daftar Produk</span>
                    </a>
                    <!--end:Menu link-->
                </div>
                <!--end:Menu item-->
            @endif
            @if (check_access('products.create'))
                <!--begin:Menu item-->
                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ $link == 'products/create' ? 'active' : '' }}"
                        href="{{ url('products/create') }}">
                        <span class="menu-bullet">
                            <span class="bullet bullet-dot"></span>
                        </span>
                        <span class="menu-title">Tambah Produk</span>
                    </a>
                    <!--end:Menu link-->
                </div>
                <!--end:Menu item-->
            @endif
        </div>
        <!--end:Menu sub-->
    </div>
    <!--end:Menu item-->
@endif
@if (check_access('category.index'))
    <!--begin:Menu item-->
    <div class="menu-item">
        <!--begin:Menu link-->
        <a class="menu-link {{ Request::segment(1) == 'category' ? 'active' : '' }}" href="{{ url('category') }}">
            <span class="menu-icon">
                <i class="ki-duotone ki-duotone ki-chart fs-2">
                    <span class="path1"></span>
                </i>
            </span>
            <span class="menu-title">Kategori</span>
        </a>
        <!--end:Menu link-->
    </div>
@endif
@if (check_access('unit.index'))
    <div class="menu-item">
        <!--begin:Menu link-->
        <a class="menu-link {{ Request::segment(1) == 'unit' ? 'active' : '' }}" href="{{ url('unit') }}">
            <span class="menu-icon">
                <i class="ki-duotone ki-data">
                    <span class="path1"></span>
                    <span class="path2"></span>
                    <span class="path3"></span>
                    <span class="path4"></span>
                    <span class="path5"></span>
                </i>
            </span>
            <span class="menu-title">Satuan Produk</span>
        </a>
        <!--end:Menu link-->
    </div>
@endif
@if (check_access('supplier.index'))
    <div class="menu-item">
        <!--begin:Menu link-->
        <a class="menu-link {{ Request::segment(1) == 'supplier' ? 'active' : '' }}" href="{{ url('supplier') }}">
            <span class="menu-icon">
                <i class="ki-duotone ki-handcart">
                </i>
            </span>
            <span class="menu-title">Supplier</span>
        </a>
        <!--end:Menu link-->
    </div>
@endif
@if (check_access('location.index'))
    <div class="menu-item">
        <!--begin:Menu link-->
        <a class="menu-link {{ Request::segment(1) == 'location' ? 'active' : '' }}" href="{{ url('location') }}">
            <span class="menu-icon">
                <i class="ki-duotone ki-map fs-2">
                    <span class="path1"></span>
                    <span class="path2"></span>
                    <span class="path3"></span>
                </i>
            </span>
            <span class="menu-title">Lokasi Stock</span>
        </a>
        <!--end:Menu link-->
    </div>
@endif
@if (check_access('handling.index'))
    <div class="menu-item">
        <!--begin:Menu link-->
        <a class="menu-link {{ Request::segment(1) == 'handling' ? 'active' : '' }}" href="{{ url('handling') }}">
            <span class="menu-icon">
                <i class="ki-duotone ki-courier-express">
                    <span class="path1"></span>
                    <span class="path2"></span>
                    <span class="path3"></span>
                    <span class="path4"></span>
                    <span class="path5"></span>
                    <span class="path6"></span>
                    <span class="path7"></span>
                </i>
            </span>
            <span class="menu-title">Handling</span>
        </a>
        <!--end:Menu link-->
    </div>
@endif
@if (check_access('staff.index') || check_access('staff.create'))
    <div data-kt-menu-trigger="click"
        class="menu-item {{ in_array(Request::segment(1), ['staff']) ? 'here show' : '' }} menu-accordion">
        <!--begin:Menu link-->
        <span class="menu-link">
            <span class="menu-icon">
                <i class="ki-duotone ki-profile-user">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
            </span>
            <span class="menu-title">Staff</span>
            <span class="menu-arrow"></span>
        </span>
        <!--end:Menu link-->
        <!--begin:Menu sub-->
        <div class="menu-sub menu-sub-accordion">
            @if (check_access('staff.index'))
                <!--begin:Menu item-->
                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ $link == 'staff' ? 'active' : '' }}" href="{{ url('staff') }}">
                        <span class="menu-bullet">
                            <span class="bullet bullet-dot"></span>
                        </span>
                        <span class="menu-title">Daftar Staff</span>
                    </a>
                    <!--end:Menu link-->
                </div>
                <!--end:Menu item-->
            @endif
            @if (check_access('staff.create'))
                <!--begin:Menu item-->
                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ $link == 'staff/create' ? 'active' : '' }}"
                        href="{{ url('staff/create') }}">
                        <span class="menu-bullet">
                            <span class="bullet bullet-dot"></span>
                        </span>
                        <span class="menu-title">Tambah Staff</span>
                    </a>
                    <!--end:Menu link-->
                </div>
                <!--end:Menu item-->
            @endif
        </div>
        <!--end:Menu sub-->
    </div>
@endif
@if (check_access('kurir.index'))
    <div class="menu-item">
        <!--begin:Menu link-->
        <a class="menu-link {{ Request::segment(1) == 'kurir' ? 'active' : '' }}" href="{{ url('kurir') }}">
            <span class="menu-icon">
                <i class="ki-duotone ki-profile-user">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
            </span>
            <span class="menu-title">Kurir</span>
        </a>
        <!--end:Menu link-->
    </div>
@endif
@if (check_access('customers.index') || check_access('customers.create'))
    <div data-kt-menu-trigger="click"
        class="menu-item {{ in_array(Request::segment(1), ['customers']) ? 'here show' : '' }} menu-accordion">
        <!--begin:Menu link-->
        <span class="menu-link">
            <span class="menu-icon">
                <i class="ki-duotone ki-people">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
            </span>
            <span class="menu-title">Pelanggan</span>
            <span class="menu-arrow"></span>
        </span>
        <!--end:Menu link-->
        <!--begin:Menu sub-->
        <div class="menu-sub menu-sub-accordion">
            @if (check_access('customers.index'))
                <!--begin:Menu item-->
                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ $link == 'customers' ? 'active' : '' }}" href="{{ url('customers') }}">
                        <span class="menu-bullet">
                            <span class="bullet bullet-dot"></span>
                        </span>
                        <span class="menu-title">Daftar Pelanggan</span>
                    </a>
                    <!--end:Menu link-->
                </div>
                <!--end:Menu item-->
            @endif
            @if (check_access('customers.create'))
                <!--begin:Menu item-->
                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ $link == 'customers/create' ? 'active' : '' }}"
                        href="{{ url('customers/create') }}">
                        <span class="menu-bullet">
                            <span class="bullet bullet-dot"></span>
                        </span>
                        <span class="menu-title">Tambah Pelanggan</span>
                    </a>
                    <!--end:Menu link-->
                </div>
                <!--end:Menu item-->
            @endif
        </div>
        <!--end:Menu sub-->
    </div>
@endif
@if (check_access('department.index'))
    <div class="menu-item">
        <!--begin:Menu link-->
        <a class="menu-link {{ Request::segment(1) == 'department' ? 'active' : '' }}"
            href="{{ url('department') }}">
            <span class="menu-icon">
                <i class="ki-duotone ki-home">
                </i>
            </span>
            <span class="menu-title">Departemen</span>
        </a>
        <!--end:Menu link-->
    </div>
@endif

@if (check_access('position.index'))
    <div class="menu-item">
        <!--begin:Menu link-->
        <a class="menu-link {{ Request::segment(1) == 'position' ? 'active' : '' }}" href="{{ url('position') }}">
            <span class="menu-icon">
                <i class="ki-duotone ki-security-user">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
            </span>
            <span class="menu-title">Posisi</span>
        </a>
        <!--end:Menu link-->
    </div>
@endif
{{-- @if (check_access('stock-out-type.index'))
    <!--end:Menu item-->
    <div class="menu-item">
        <!--begin:Menu link-->
        <a class="menu-link {{ Request::segment(1) == 'stock-out-type' ? 'active' : '' }}" href="{{ url('stock-out-type') }}">
            <span class="menu-icon">
                <i class="ki-duotone ki-security-user">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
            </span>
            <span class="menu-title">Tipe Stock Out</span>
        </a>
        <!--end:Menu link-->
    </div>
@endif --}}
@if (check_access('branch.index'))
    <div class="menu-item">
        <!--begin:Menu link-->
        <a class="menu-link {{ Request::segment(1) == 'branch' ? 'active' : '' }}" href="{{ url('branch') }}">
            <span class="menu-icon">
                <i class="ki-duotone ki-security-user">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
            </span>
            <span class="menu-title">Cabang</span>
        </a>
        <!--end:Menu link-->
    </div>
@endif
@if (check_access('payment-method.index'))
    <div class="menu-item">
        <!--begin:Menu link-->
        <a class="menu-link {{ Request::segment(1) == 'payment-method' ? 'active' : '' }}"
            href="{{ url('payment-method') }}">
            <span class="menu-icon">
                <i class="ki-duotone ki-security-user">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
            </span>
            <span class="menu-title">Metode Pembayaran</span>
        </a>
        <!--end:Menu link-->
    </div>
@endif
{{-- @if (check_access('account.index'))
    <div class="menu-item">
        <!--begin:Menu link-->
        <a class="menu-link {{ Request::segment(1) == 'account' ? 'active' : '' }}" href="{{ url('account') }}">
            <span class="menu-icon">
                <i class="ki-duotone ki-security-user">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
            </span>
            <span class="menu-title">Akun</span>
        </a>
        <!--end:Menu link-->
    </div>
@endif --}}
@if (check_access('role.index') || check_access('user.index'))
    <div class="menu-item pt-5">
        <!--begin:Menu content-->
        <div class="menu-content">
            <span class="menu-heading fw-bold text-uppercase fs-7">Setting</span>
        </div>
        <!--end:Menu content-->
    </div>
    @if (check_access('role.index'))
        <div class="menu-item">
            <!--begin:Menu link-->
            <a class="menu-link {{ Request::segment(1) == 'roles' ? 'active' : '' }}" href="{{ url('roles') }}">
                <span class="menu-icon">
                    <i class="ki-duotone ki-security-user">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </span>
                <span class="menu-title">Role</span>
            </a>
            <!--end:Menu link-->
        </div>
    @endif
    @if (check_access('user.index'))
        <div class="menu-item">
            <!--begin:Menu link-->
            <a class="menu-link {{ Request::segment(1) == 'user' ? 'active' : '' }}" href="{{ url('user') }}">
                <span class="menu-icon">
                    <i class="ki-duotone ki-security-user">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </span>
                <span class="menu-title">User</span>
            </a>
            <!--end:Menu link-->
        </div>
    @endif
@endif
@if (check_access('product-stock.index') || check_access('stock-opname.index'))
    <div class="menu-item pt-5">
        <!--begin:Menu content-->
        <div class="menu-content">
            <span class="menu-heading fw-bold text-uppercase fs-7">Inventory</span>
        </div>
        <!--end:Menu content-->
    </div>
    <!--begin:Menu item-->
    <div data-kt-menu-trigger="click"
        class="menu-item {{ in_array(Request::segment(1), ['product-stock', 'stock-out', 'stock-opname']) ? 'here show' : '' }} menu-accordion">
        <!--begin:Menu link-->
        <span class="menu-link">
            <span class="menu-icon">
                <i class="ki-duotone ki-basket fs-2">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
            </span>
            <span class="menu-title">Stok</span>
            <span class="menu-arrow"></span>
        </span>
        <!--end:Menu link-->
        <!--begin:Menu sub-->
        <div class="menu-sub menu-sub-accordion">
            @if (check_access('product-stock.index'))
                <!--begin:Menu item-->
                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ Request::segment(1) == 'product-stock' ? 'active' : '' }}"
                        href="{{ url('product-stock') }}">
                        <span class="menu-bullet">
                            <span class="bullet bullet-dot"></span>
                        </span>
                        <span class="menu-title">Laporan Stok</span>
                    </a>
                    <!--end:Menu link-->
                </div>
                <!--end:Menu item-->
            @endif
            {{-- <!--begin:Menu item-->
            <div class="menu-item">
                <!--begin:Menu link-->
                <a class="menu-link {{ Request::segment(1) == 'stock-out' ? 'active' : '' }}" href="{{ url('stock-out') }}">
                    <span class="menu-bullet">
                        <span class="bullet bullet-dot"></span>
                    </span>
                    <span class="menu-title">Stock Out</span>
                </a>
                <!--end:Menu link-->
            </div>
            <!--end:Menu item--> --}}
            @if (check_access('stock-opname.index'))
                <!--begin:Menu item-->
                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ Request::segment(1) == 'stock-opname' ? 'active' : '' }}"
                        href="{{ url('stock-opname') }}">
                        <span class="menu-bullet">
                            <span class="bullet bullet-dot"></span>
                        </span>
                        <span class="menu-title">Stock Opname</span>
                    </a>
                    <!--end:Menu link-->
                </div>
                <!--end:Menu item-->
            @endif
        </div>
        <!--end:Menu sub-->
    </div>
    <!--end:Menu item-->
@endif
@if (check_access('wholesale.index'))
    <div class="menu-item">
        <!--begin:Menu link-->
        <a class="menu-link {{ Request::segment(1) == 'wholesale' ? 'active' : '' }}" href="{{ url('wholesale') }}">
            <span class="menu-icon">
                <i class="ki-duotone ki-security-user">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
            </span>
            <span class="menu-title">Pengadaan</span>
        </a>
        <!--end:Menu link-->
    </div>
@endif
@if (check_access('sortir.index'))
    <div class="menu-item">
        <!--begin:Menu link-->
        <a class="menu-link {{ Request::segment(1) == 'sortir' ? 'active' : '' }}" href="{{ url('sortir') }}">
            <span class="menu-icon">
                <i class="ki-duotone ki-security-user">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
            </span>
            <span class="menu-title">Sortir</span>
        </a>
        <!--end:Menu link-->
    </div>
@endif
@if (check_access('transfer.index'))
    <!--begin:Menu item-->
    <div data-kt-menu-trigger="click"
        class="menu-item {{ in_array(Request::segment(1), ['transfer-pengirim', 'transfer-penerima']) ? 'here show' : '' }} menu-accordion">
        <!--begin:Menu link-->
        <span class="menu-link">
            <span class="menu-icon">
                <i class="ki-duotone ki-basket fs-2">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
            </span>
            <span class="menu-title">Transfer Stok</span>
            <span class="menu-arrow"></span>
        </span>
        <!--end:Menu link-->
        <!--begin:Menu sub-->
        <div class="menu-sub menu-sub-accordion">
            @if (check_access('transfer.index'))
                <!--begin:Menu item-->
                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ Request::segment(1) == 'transfer-pengirim' ? 'active' : '' }}"
                        href="{{ url('transfer-pengirim') }}">
                        <span class="menu-bullet">
                            <span class="bullet bullet-dot"></span>
                        </span>
                        <span class="menu-title">Mengirim</span>
                    </a>
                    <!--end:Menu link-->
                </div>
                <!--end:Menu item-->
            @endif
            @if (check_access('transfer.index'))
                <!--begin:Menu item-->
                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ Request::segment(1) == 'transfer-penerima' ? 'active' : '' }}"
                        href="{{ url('transfer-penerima') }}">
                        <span class="menu-bullet">
                            <span class="bullet bullet-dot"></span>
                        </span>
                        <span class="menu-title">Menerima</span>
                    </a>
                    <!--end:Menu link-->
                </div>
                <!--end:Menu item-->
            @endif
        </div>
        <!--end:Menu sub-->
    </div>
    <!--end:Menu item-->
@endif
@if (check_access('production.index') || check_access('receipt.index'))
    <!--begin:Menu item-->
    <div data-kt-menu-trigger="click"
        class="menu-item {{ in_array(Request::segment(1), ['production', 'receipt', 'parcel']) ? 'here show' : '' }} menu-accordion">
        <!--begin:Menu link-->
        <span class="menu-link">
            <span class="menu-icon">
                <i class="ki-duotone ki-basket fs-2">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
            </span>
            <span class="menu-title">Produksi</span>
            <span class="menu-arrow"></span>
        </span>
        <!--end:Menu link-->
        <!--begin:Menu sub-->
        <div class="menu-sub menu-sub-accordion">
            @if (check_access('receipt.index'))
                <!--begin:Menu item-->
                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ Request::segment(1) == 'receipt' ? 'active' : '' }}"
                        href="{{ url('receipt') }}">
                        <span class="menu-bullet">
                            <span class="bullet bullet-dot"></span>
                        </span>
                        <span class="menu-title">Buat Resep</span>
                    </a>
                    <!--end:Menu link-->
                </div>
                <!--end:Menu item-->
            @endif
            @if (check_access('production.index'))
                <!--begin:Menu item-->
                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ Request::segment(1) == 'production' ? 'active' : '' }}"
                        href="{{ url('production') }}">
                        <span class="menu-bullet">
                            <span class="bullet bullet-dot"></span>
                        </span>
                        <span class="menu-title">Produksi (Stok)</span>
                    </a>
                    <!--end:Menu link-->
                </div>
                <!--end:Menu item-->
            @endif
            {{-- @if (check_access('parcel.index'))
                <!--begin:Menu item-->
                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ Request::segment(1) == 'parcel' ? 'active' : '' }}"
                        href="{{ url('parcel') }}">
                        <span class="menu-bullet">
                            <span class="bullet bullet-dot"></span>
                        </span>
                        <span class="menu-title">Produksi (Parcel)</span>
                    </a>
                    <!--end:Menu link-->
                </div>
                <!--end:Menu item-->
            @endif --}}
        </div>
        <!--end:Menu sub-->
    </div>
    <!--end:Menu item-->
@endif
@if (check_access('pos.index') ||
        check_access('delivery-order.index') ||
        check_access('order-book.index') ||
        check_access('setting-nota.index') ||
        check_access('expenditure.index'))
    <div class="menu-item pt-5">
        <!--begin:Menu content-->
        <div class="menu-content">
            <span class="menu-heading fw-bold text-uppercase fs-7">Transaksi</span>
        </div>
        <!--end:Menu content-->
    </div>
@endif
@if (check_access('pos.index'))
    <div class="menu-item">
        <!--begin:Menu link-->
        <a class="menu-link {{ Request::segment(1) == 'pos' ? 'active' : '' }}" href="{{ url('pos') }}">
            <span class="menu-icon">
                <i class="ki-duotone ki-security-user">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
            </span>
            <span class="menu-title">Penjualan</span>
        </a>
        <!--end:Menu link-->
    </div>
@endif
@if (check_access('delivery-order.index'))
    <div class="menu-item">
        <!--begin:Menu link-->
        <a class="menu-link {{ Request::segment(1) == 'delivery-order' ? 'active' : '' }}"
            href="{{ url('delivery-order') }}">
            <span class="menu-icon">
                <i class="ki-duotone ki-security-user">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
            </span>
            <span class="menu-title">Pengiriman</span>
        </a>
        <!--end:Menu link-->
    </div>
@endif
@if (check_access('order-book.index'))
    <div class="menu-item">
        <!--begin:Menu link-->
        <a class="menu-link {{ Request::segment(1) == 'order-book' ? 'active' : '' }}"
            href="{{ url('order-book') }}">
            <span class="menu-icon">
                <i class="ki-duotone ki-security-user">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
            </span>
            <span class="menu-title">Pesanan Masuk</span>
        </a>
        <!--end:Menu link-->
    </div>
@endif
@if (check_access('expenditure.index'))
    <div class="menu-item">
        <!--begin:Menu link-->
        <a class="menu-link {{ Request::segment(1) == 'expenditure' ? 'active' : '' }}"
            href="{{ url('expenditure') }}">
            <span class="menu-icon">
                <i class="ki-duotone ki-security-user">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
            </span>
            <span class="menu-title">Pemasukan / Pengeluaran</span>
        </a>
        <!--end:Menu link-->
    </div>
@endif
@if (check_access('setting-nota.index'))
    <div class="menu-item">
        <!--begin:Menu link-->
        <a class="menu-link {{ Request::segment(1) == 'setting-nota' ? 'active' : '' }}"
            href="{{ url('setting-nota') }}">
            <span class="menu-icon">
                <i class="ki-duotone ki-security-user">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
            </span>
            <span class="menu-title">Setting Nota</span>
        </a>
        <!--end:Menu link-->
    </div>
@endif
@if (check_access('crm-dashboard.index') ||
        check_access('customer-report.index') ||
        check_access('deposito.index') ||
        check_access('customer-deposito.index') ||
        check_access('tier.index') ||
        check_access('point-schedule.index') ||
        check_access('campaign.index'))
    <div class="menu-item pt-5">
        <!--begin:Menu content-->
        <div class="menu-content">
            <span class="menu-heading fw-bold text-uppercase fs-7">Crm</span>
        </div>
        <!--end:Menu content-->
    </div>
@endif
@if (check_access('crm-dashboard.index'))
    <div class="menu-item">
        <!--begin:Menu link-->
        <a class="menu-link {{ Request::segment(1) == 'crm-dashboard' ? 'active' : '' }}"
            href="{{ url('crm-dashboard') }}">
            <span class="menu-icon">
                <i class="ki-duotone ki-security-user">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
            </span>
            <span class="menu-title">Dashboard</span>
        </a>
        <!--end:Menu link-->
    </div>
@endif
@if (check_access('customer-report.index'))
    <div class="menu-item">
        <!--begin:Menu link-->
        <a class="menu-link {{ Request::segment(1) == 'customer-report' ? 'active' : '' }}"
            href="{{ url('customer-report') }}">
            <span class="menu-icon">
                <i class="ki-duotone ki-security-user">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
            </span>
            <span class="menu-title">Pelanggan</span>
        </a>
        <!--end:Menu link-->
    </div>
@endif
@if (check_access('deposito.index') || check_access('customer-deposito.index'))
    <div data-kt-menu-trigger="click"
        class="menu-item {{ in_array(Request::segment(1), ['deposito', 'customer-deposito']) ? 'here show' : '' }} menu-accordion">
        <!--begin:Menu link-->
        <span class="menu-link">
            <span class="menu-icon">
                <i class="ki-duotone ki-basket fs-2">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
            </span>
            <span class="menu-title">Deposito</span>
            <span class="menu-arrow"></span>
        </span>
        <!--end:Menu link-->
        <!--begin:Menu sub-->
        <div class="menu-sub menu-sub-accordion">
            @if (check_access('deposito.index'))
                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ Request::segment(1) == 'deposito' ? 'active' : '' }}"
                        href="{{ url('deposito') }}">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-security-user">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </span>
                        <span class="menu-title">Buat Deposito</span>
                    </a>
                    <!--end:Menu link-->
                </div>
            @endif
            @if (check_access('customer-deposito.index'))
                <!--begin:Menu item-->
                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ Request::segment(1) == 'customer-deposito' ? 'active' : '' }}"
                        href="{{ url('customer-deposito') }}">
                        <span class="menu-bullet">
                            <span class="bullet bullet-dot"></span>
                        </span>
                        <span class="menu-title">Deposito Pelanggan</span>
                    </a>
                    <!--end:Menu link-->
                </div>
            @endif
        </div>
        <!--end:Menu sub-->
    </div>
@endif
@if (check_access('tier.index') || check_access('point-schedule.index') || check_access('campaign.index'))
    <div data-kt-menu-trigger="click"
        class="menu-item {{ in_array(Request::segment(1), ['tier', 'point-schedule', 'campaign']) ? 'here show' : '' }} menu-accordion">
        <!--begin:Menu link-->
        <span class="menu-link">
            <span class="menu-icon">
                <i class="ki-duotone ki-basket fs-2">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
            </span>
            <span class="menu-title">Loyalty Scheme</span>
            <span class="menu-arrow"></span>
        </span>
        <!--end:Menu link-->
        <!--begin:Menu sub-->
        <div class="menu-sub menu-sub-accordion">
            @if (check_access('tier.index'))
                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ Request::segment(1) == 'tier' ? 'active' : '' }}"
                        href="{{ url('tier') }}">
                        <span class="menu-icon">
                            <i class="ki-duotone ki-security-user">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </span>
                        <span class="menu-title">Tier</span>
                    </a>
                    <!--end:Menu link-->
                </div>
            @endif
            @if (check_access('point-schedule.index'))
                <!--begin:Menu item-->
                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ Request::segment(1) == 'point-schedule' ? 'active' : '' }}"
                        href="{{ url('point-schedule') }}">
                        <span class="menu-bullet">
                            <span class="bullet bullet-dot"></span>
                        </span>
                        <span class="menu-title">Setting Scheme</span>
                    </a>
                    <!--end:Menu link-->
                </div>
            @endif
            @if (check_access('campaign.index'))
                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ Request::segment(1) == 'campaign' ? 'active' : '' }}"
                        href="{{ url('campaign') }}">
                        <span class="menu-bullet">
                            <span class="bullet bullet-dot"></span>
                        </span>
                        <span class="menu-title">Event</span>
                    </a>
                    <!--end:Menu link-->
                </div>
            @endif
            <!--end:Menu item-->
            <!--begin:Menu item-->
            {{-- <div class="menu-item">
            <!--begin:Menu link-->
            <a class="menu-link {{ Request::segment(1) == 'setting-exp' ? 'active' : '' }}"
                href="{{ url('setting-exp') }}">
                <span class="menu-icon">
                    <i class="ki-duotone ki-security-user">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </span>
                <span class="menu-title">Setting Exp</span>
            </a>
            <!--end:Menu link-->
        </div> --}}
            <!--end:Menu item-->
        </div>
        <!--end:Menu sub-->
    </div>
@endif
@if (check_access('report.transaction') ||
        check_access('report.customer.transaction') ||
        check_access('report.branch.transaction') ||
        check_access('report.product.sales') ||
        check_access('report.branch.product') ||
        check_access('report.customer.product') ||
        check_access('report.product.buang') ||
        check_access('report.total.aset'))
    <div class="menu-item pt-5">
        <!--begin:Menu content-->
        <div class="menu-content">
            <span class="menu-heading fw-bold text-uppercase fs-7">Laporan</span>
        </div>
        <!--end:Menu content-->
    </div>
@endif
{{-- @if (check_access('report.transaction') ||
        check_access('report.customer.transaction') ||
        check_access('report.branch.transaction') ||
        check_access('report.product.sales') ||
        check_access('report.branch.product') ||
        check_access('report.customer.product'))
    <div data-kt-menu-trigger="click"
        class="menu-item {{ in_array(Request::segment(1), [
            'report-branch-transaction',
            'report-branch-product',
            'report-customer-transaction',
            'report-customer-product',
            'report-transaction',
            'report-product-sales',
        ])
            ? 'here show'
            : '' }} menu-accordion">
        <!--begin:Menu link-->
        <span class="menu-link">
            <span class="menu-icon">
                <i class="ki-duotone ki-basket fs-2">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
            </span>
            <span class="menu-title">Laporan Penjualan</span>
            <span class="menu-arrow"></span>
        </span>
        <!--end:Menu link-->
        <!--begin:Menu sub-->
        <div class="menu-sub menu-sub-accordion">
            @if (check_access('report.transaction'))
                <!--begin:Menu item-->
                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ Request::segment(1) == 'report-transaction' ? 'active' : '' }}"
                        href="{{ url('report-transaction') }}">
                        <span class="menu-bullet">
                            <span class="bullet bullet-dot"></span>
                        </span>
                        <span class="menu-title">Transaksi Penjualan</span>
                    </a>
                    <!--end:Menu link-->
                </div>
                <!--end:Menu item-->
            @endif
            @if (check_access('report.customer.transaction'))
                <!--begin:Menu item-->
                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ Request::segment(1) == 'report-customer-transaction' ? 'active' : '' }}"
                        href="{{ url('report-customer-transaction') }}">
                        <span class="menu-bullet">
                            <span class="bullet bullet-dot"></span>
                        </span>
                        <span class="menu-title">Penjualan Per Pelanggan</span>
                    </a>
                    <!--end:Menu link-->
                </div>
                <!--end:Menu item-->
            @endif
            @if (check_access('report.branch.transaction'))
                <!--begin:Menu item-->
                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ Request::segment(1) == 'report-branch-transaction' ? 'active' : '' }}"
                        href="{{ url('report-branch-transaction') }}">
                        <span class="menu-bullet">
                            <span class="bullet bullet-dot"></span>
                        </span>
                        <span class="menu-title">Penjualan Per Cabang</span>
                    </a>
                    <!--end:Menu link-->
                </div>
                <!--end:Menu item-->
            @endif
            @if (check_access('report.product.sales'))
                <!--begin:Menu item-->
                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ Request::segment(1) == 'report-product-sales' ? 'active' : '' }}"
                        href="{{ url('report-product-sales') }}">
                        <span class="menu-bullet">
                            <span class="bullet bullet-dot"></span>
                        </span>
                        <span class="menu-title">Penjualan</span>
                    </a>
                    <!--end:Menu link-->
                </div>
                <!--end:Menu item-->
            @endif
            @if (check_access('report.product.buang'))
                <!--begin:Menu item-->
                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ Request::segment(1) == 'report-product-buang' ? 'active' : '' }}"
                        href="{{ url('report-product-buang') }}">
                        <span class="menu-bullet">
                            <span class="bullet bullet-dot"></span>
                        </span>
                        <span class="menu-title">Produk Buang</span>
                    </a>
                    <!--end:Menu link-->
                </div>
                <!--end:Menu item-->
            @endif
            @if (check_access('report.total.aset'))
                <!--begin:Menu item-->
                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ Request::segment(1) == 'report-total-aset' ? 'active' : '' }}"
                        href="{{ url('report-total-aset') }}">
                        <span class="menu-bullet">
                            <span class="bullet bullet-dot"></span>
                        </span>
                        <span class="menu-title">Total Aset</span>
                    </a>
                    <!--end:Menu link-->
                </div>
                <!--end:Menu item-->
            @endif
            @if (check_access('report.branch.product'))
                <!--begin:Menu item-->
                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ Request::segment(1) == 'report-branch-product' ? 'active' : '' }}"
                        href="{{ url('report-branch-product') }}">
                        <span class="menu-bullet">
                            <span class="bullet bullet-dot"></span>
                        </span>
                        <span class="menu-title">Laporan Penjualan Per-Channel (Based on Produk & Qty)</span>
                    </a>
                    <!--end:Menu link-->
                </div>
                <!--end:Menu item-->
            @endif
            @if (check_access('report.customer.product'))
                <!--begin:Menu item-->
                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ Request::segment(1) == 'report-customer-product' ? 'active' : '' }}"
                        href="{{ url('report-customer-product') }}">
                        <span class="menu-bullet">
                            <span class="bullet bullet-dot"></span>
                        </span>
                        <span class="menu-title">Laporan Transaksi Penjualan (Based on Produk & Qty)</span>
                    </a>
                    <!--end:Menu link-->
                </div>
                <!--end:Menu item-->
            @endif
        </div>
        <!--end:Menu sub-->
    </div>
@endif --}}
@if (check_access('report.product.sales'))
    <div class="menu-item">
        <!--begin:Menu link-->
        <a class="menu-link {{ Request::segment(1) == 'report-product-sales' ? 'active' : '' }}"
            href="{{ url('report-product-sales') }}">
            <span class="menu-icon">
                <i class="ki-duotone ki-basket fs-2">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
            </span>
            <span class="menu-title">Laporan Penjualan</span>
        </a>
        <!--end:Menu link-->
    </div>
@endif
@if (check_access('report.profit.revenue'))
    <div class="menu-item">
        <!--begin:Menu link-->
        <a class="menu-link {{ Request::segment(1) == 'report-profit-revenue' ? 'active' : '' }}"
            href="{{ url('report-profit-revenue') }}">
            <span class="menu-icon">
                <i class="ki-duotone ki-chart-pie-4 fs-2">
                    <span class="path1"></span>
                    <span class="path2"></span>
                    <span class="path3"></span>
                </i>
            </span>
            <span class="menu-title">Laporan Keuntungan Pendapatan</span>
        </a>
        <!--end:Menu link-->
    </div>
@endif
@if (check_access('report.profit.adjusted'))
    <div class="menu-item">
        <!--begin:Menu link-->
        <a class="menu-link {{ Request::segment(1) == 'report-profit-adjusted' ? 'active' : '' }}"
            href="{{ url('report-profit-adjusted') }}">
            <span class="menu-icon">
                <i class="ki-duotone ki-chart-line-star fs-2">
                    <span class="path1"></span>
                    <span class="path2"></span>
                    <span class="path3"></span>
                </i>
            </span>
            <span class="menu-title">Laporan Keuntungan Setelah Penyesuaian</span>
        </a>
        <!--end:Menu link-->
    </div>
@endif
@if (check_access('report.shipping.cost'))
    <div class="menu-item">
        <!--begin:Menu link-->
        <a class="menu-link {{ Request::segment(1) == 'report-shipping-cost' ? 'active' : '' }}"
            href="{{ url('report-shipping-cost') }}">
            <span class="menu-icon">
                <i class="ki-duotone ki-basket fs-2">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
            </span>
            <span class="menu-title">Laporan Ongkos Kirim</span>
        </a>
        <!--end:Menu link-->
    </div>
@endif
@if (check_access('report.product.buang'))
    <div class="menu-item">
        <!--begin:Menu link-->
        <a class="menu-link {{ Request::segment(1) == 'report-product-buang' ? 'active' : '' }}"
            href="{{ url('report-product-buang') }}">
            <span class="menu-icon">
                <i class="ki-duotone ki-basket fs-2">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
            </span>
            <span class="menu-title">Produk Buang</span>
        </a>
        <!--end:Menu link-->
    </div>
@endif
@if (check_access('report.total.aset'))
    <div class="menu-item">
        <!--begin:Menu link-->
        <a class="menu-link {{ Request::segment(1) == 'report-total-aset' ? 'active' : '' }}"
            href="{{ url('report-total-aset') }}">
            <span class="menu-icon">
                <i class="ki-duotone ki-basket fs-2">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
            </span>
            <span class="menu-title">Total Aset</span>
        </a>
        <!--end:Menu link-->
    </div>
@endif
