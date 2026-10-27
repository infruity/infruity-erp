@extends('layouts.erp-tailwind')

@section('title', 'Akun Pengguna - Master')
@section('page-title', 'Akun Pengguna')
@section('page-breadcrumb')
    <span>Master</span><i class="ph ph-caret-right text-[0.55rem]"></i><span>Akun Pengguna</span>
@endsection

@php
    $permissionDefinitions = config('constant.permissions', []);
    $accessGroupDefinitions = [
        ['label' => 'Master', 'icon' => 'ph-folder', 'iconBg' => 'bg-emerald-50', 'iconColor' => 'text-emerald-500', 'modules' => [
            ['key' => 'products', 'label' => 'Produk'], ['key' => 'category', 'label' => 'Kategori'],
            ['key' => 'unit', 'label' => 'Satuan Produk'], ['key' => 'supplier', 'label' => 'Pemasok'],
            ['key' => 'customers', 'label' => 'Pelanggan'], ['key' => 'branch', 'label' => 'Cabang'],
            ['key' => 'position', 'label' => 'Jabatan'], ['key' => 'staff', 'label' => 'Karyawan'],
            ['key' => 'payment-method', 'label' => 'Metode Pembayaran'], ['key' => 'user', 'label' => 'Akun Pengguna'],
            ['key' => 'role-menu', 'label' => 'Hak Akses'], ['key' => 'role', 'label' => 'Peran'],
            ['key' => 'account', 'label' => 'Akun'], ['key' => 'product-stock', 'label' => 'Stok Produk'],
            ['key' => 'location', 'label' => 'Lokasi'], ['key' => 'handling', 'label' => 'Penanganan'],
            ['key' => 'department', 'label' => 'Departemen'],
            ['key' => 'kurir', 'label' => 'Kurir', 'permissions' => ['kurir.index']],
        ]],
        ['label' => 'Transaksi', 'icon' => 'ph-receipt', 'iconBg' => 'bg-amber-50', 'iconColor' => 'text-amber-500', 'modules' => [
            ['key' => 'wholesale', 'label' => 'Pengadaan'], ['key' => 'sortir', 'label' => 'Sortir Barang'],
            ['key' => 'transfer', 'label' => 'Pindah Stok'], ['key' => 'stock-opname', 'label' => 'Stok Opname'],
            ['key' => 'production', 'label' => 'Produksi'], ['key' => 'pos', 'label' => 'Kasir / POS'],
            ['key' => 'order-book', 'label' => 'Pesanan'], ['key' => 'delivery-order', 'label' => 'Pengiriman'],
            ['key' => 'expenditure', 'label' => 'Pengeluaran'], ['key' => 'receipt', 'label' => 'Resep'],
            ['key' => 'parcel', 'label' => 'Parcel'], ['key' => 'other-book', 'label' => 'Other Book'],
            ['key' => 'product-receipt', 'label' => 'Resep Produk'], ['key' => 'stock-out', 'label' => 'Stok Keluar'],
            ['key' => 'stock-out-type', 'label' => 'Tipe Stok Keluar'],
        ]],
        ['label' => 'CRM', 'icon' => 'ph-users', 'iconBg' => 'bg-purple-50', 'iconColor' => 'text-purple-500', 'modules' => [
            ['key' => 'crm-dashboard', 'label' => 'Dashboard CRM'], ['key' => 'customer-report', 'label' => 'Laporan Customer'],
            ['key' => 'tier', 'label' => 'Tier'], ['key' => 'setting-exp', 'label' => 'Pengaturan EXP'],
            ['key' => 'point-schedule', 'label' => 'Jadwal Poin'], ['key' => 'deposito', 'label' => 'Deposito'],
            ['key' => 'customer-deposito', 'label' => 'Deposito Customer'], ['key' => 'campaign', 'label' => 'Campaign'],
        ]],
        ['label' => 'Laporan', 'icon' => 'ph-chart-bar', 'iconBg' => 'bg-cyan-50', 'iconColor' => 'text-cyan-500', 'modules' => [
            ['key' => 'report', 'label' => 'Laporan'],
        ]],
        ['label' => 'Pengaturan', 'icon' => 'ph-gear', 'iconBg' => 'bg-gray-100', 'iconColor' => 'text-gray-500', 'modules' => [
            ['key' => 'setting-nota', 'label' => 'Nota & Cetak'],
        ]],
    ];
    $accessGroups = collect($accessGroupDefinitions)->map(function ($group) use ($permissionDefinitions) {
        $modules = collect($group['modules'])
            ->filter(fn ($module) => isset($permissionDefinitions[$module['key']]) || !empty($module['permissions']))
            ->map(function ($module) use ($permissionDefinitions) {
                if (isset($permissionDefinitions[$module['key']])) {
                    $actions = $permissionDefinitions[$module['key']]['actions'] ?? [];
                    $module['permissions'] = collect(array_keys($actions))
                        ->map(fn ($action) => $module['key'] . '.' . $action)
                        ->values()
                        ->all();
                }
                return $module;
            })
            ->values()
            ->all();

        return ['label' => $group['label'], 'icon' => $group['icon'], 'iconBg' => $group['iconBg'], 'iconColor' => $group['iconColor'], 'modules' => $modules];
    })->filter(fn ($group) => count($group['modules']) > 0)->values()->all();

    $canManageUserPermissions = check_access('user.update');
    $currentUserId = auth()->id();
    $editableUsers = $users->getCollection()->map(fn ($user) => [
        'id' => $user->id_user,
        'name' => $user->nm_user,
        'email' => $user->email,
        'granted_modules_count' => $user->granted_modules_count,
        'modules_total' => $modulesTotal,
        'role' => $user->RoleUser?->id_role,
        'role_name' => $user->RoleUser?->role?->nm_role ?? 'Tanpa peran',
        'permissions' => $user->RoleUser?->role?->roleMenu?->pluck('permission')->all() ?? [],
        'permission_overrides' => $user->permissionOverrides->mapWithKeys(fn ($override) => [$override->permission => (bool) $override->is_allowed])->all(),
        'can_change_permissions' => $canManageUserPermissions && (bool) $user->is_aktif && (int) $currentUserId !== (int) $user->id_user,
        'branches' => $user->branches->pluck('branch_id')->all(),
        'branch_names' => $user->branches->map(fn ($assignment) => $assignment->branch?->name)->filter()->values()->all(),
        'is_aktif' => (bool) $user->is_aktif,
        'staff_id' => $user->staff?->id,
        'staff_name' => $user->staff?->name,
    ])->values();
@endphp

@section('content')
<div x-data="userMaster(@js($editableUsers), @js(route('user.store')), @js(url('user')), @js(old('_user_id')), @js(old('full_name')), @js(old('email')), @js(old('id_role')), @js(old('id_branch', [])), @js($accessGroups), @js($nextAutoEmail), @js(old('email_type')), @js(old('staff_id')), @js($staffOptions))"
     x-init="if (@js($errors->any())) restoreOld(); else if (new URLSearchParams(location.search).has('create')) openCreate()"
     @user-filter-open.window="filterOpen = true"
     @mobile-add-toggle.window="openCreate()"
     class="flex-1 flex flex-col min-h-0 -mx-4 md:mx-0">
    <form x-data="{ mobileSearch: @js(request('q', '')) }" method="GET" action="{{ route('user.index') }}" class="md:hidden">
        <input type="hidden" name="role_id" value="{{ $selectedRoleId }}">
        <x-erp.mobile-search>
            <x-slot:input><input x-ref="mobileSearch" x-model="mobileSearch" name="q" type="search" placeholder="Cari akun..." aria-label="Cari akun pengguna" class="erp-mobile-search__input"></x-slot:input>
            <x-slot:clear><button type="button" x-show="mobileSearch" x-cloak @click="mobileSearch = ''; $refs.mobileSearch.focus()" aria-label="Hapus pencarian" class="erp-mobile-search__clear"><i class="ph-bold ph-x text-xs" aria-hidden="true"></i></button></x-slot:clear>
            <x-slot:filter><button type="button" @click="$dispatch('user-filter-open')" aria-label="Filter akun" class="erp-mobile-search__filter {{ $selectedRoleId ? 'is-active' : '' }}"><i class="ph ph-sliders-horizontal" aria-hidden="true"></i>@if($selectedRoleId)<span class="erp-mobile-search__filter-dot"></span>@endif</button></x-slot:filter>
        </x-erp.mobile-search>
    </form>
    <div class="erp-surface bg-white rounded-none lg:rounded-2xl lg:shadow-sm lg:border lg:border-gray-100 flex-1 flex flex-col min-h-0 overflow-hidden">
        <div class="hidden md:flex items-center gap-3 p-4 border-b border-gray-100">
            <form method="GET" action="{{ route('user.index') }}" class="relative flex-1">
                <i class="ph ph-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input name="q" value="{{ request('q') }}" type="search" placeholder="Cari nama atau email pengguna..." class="w-full h-11 rounded-full border border-gray-100 bg-gray-50/50 pl-11 pr-20 text-[13px] outline-none focus:border-emerald-500">
                <button type="submit" class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-semibold text-[#0b595b]">Cari</button>
            </form>
            @if (check_access('user.create'))
            <button type="button" @click="openCreate()" class="flex items-center gap-2 bg-[#0b595b] hover:bg-[#0a4e50] text-white rounded-full h-11 px-5 text-[13px] font-semibold shadow-sm"><i class="ph-bold ph-plus"></i> Tambah Akun</button>
            @endif
        </div>

        @if (session('success'))
            <div class="mx-5 mt-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('success') }}</div>
        @endif
        @if (session('error') || $errors->any())
            <div class="mx-5 mt-4 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">{{ session('error') ?: $errors->first() }}</div>
        @endif

        <div class="hidden md:flex px-5 py-3 bg-gray-50/50 border-b border-gray-100 text-xs text-gray-400 font-medium">Menampilkan <strong class="mx-1 text-gray-700">{{ $users->count() }}</strong> dari <strong class="mx-1 text-gray-700">{{ $users->total() }}</strong> akun pengguna</div>
        <div class="flex-1 overflow-y-auto overscroll-none pb-28 md:pb-0">
            @forelse ($users as $user)
                <button type="button" @click="openDetail({{ $user->id_user }})" class="w-full flex items-center gap-3 md:gap-4 px-5 py-3.5 md:py-4 border-b border-gray-100 hover:bg-gray-50/50 text-left focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-600" aria-label="Detail {{ $user->email }}">
                    <div @class(['w-9 h-9 md:w-10 md:h-10 rounded-xl border flex items-center justify-center shrink-0 font-bold text-sm', 'bg-emerald-50 border-emerald-100 text-emerald-700' => $loop->index % 5 === 0, 'bg-blue-50 border-blue-100 text-blue-700' => $loop->index % 5 === 1, 'bg-amber-50 border-amber-100 text-amber-700' => $loop->index % 5 === 2, 'bg-rose-50 border-rose-100 text-rose-700' => $loop->index % 5 === 3, 'bg-violet-50 border-violet-100 text-violet-700' => $loop->index % 5 === 4])>{{ mb_strtoupper(mb_substr($user->email ?: $user->nm_user, 0, 2)) }}</div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-semibold text-gray-900 truncate">{{ $user->email }}</div>
                        <div class="mt-0.5 flex min-w-0 items-center gap-1.5"><span class="truncate text-[11px] text-gray-400">{{ $user->nm_user ?: 'Belum tertaut' }}</span><span class="shrink-0 rounded-full bg-gray-100 px-1.5 py-0.5 text-[10px] font-semibold text-gray-600">{{ $user->RoleUser?->role?->nm_role ?? '—' }}</span><span @class(['shrink-0 rounded-full px-1.5 py-0.5 text-[10px] font-semibold', 'bg-emerald-50 text-emerald-700' => $user->is_aktif, 'bg-gray-100 text-gray-600' => !$user->is_aktif])>{{ $user->is_aktif ? 'Aktif' : 'Nonaktif' }}</span><span @class(['shrink-0 rounded-full px-1.5 py-0.5 text-[10px] font-semibold', 'bg-emerald-50 text-emerald-700' => $modulesTotal > 0 && $user->granted_modules_count === $modulesTotal, 'bg-amber-50 text-amber-700' => $user->granted_modules_count < $modulesTotal])>{{ $user->granted_modules_count }}/{{ $modulesTotal }} modul</span></div>
                    </div>
                    <div class="hidden lg:flex gap-1.5 max-w-[35%] flex-wrap justify-end">
                        @forelse ($user->branches as $assignedBranch)
                            <span class="text-[11px] font-medium text-emerald-700 bg-emerald-50 rounded-full px-2.5 py-1">{{ $assignedBranch->branch?->name ?? 'Cabang tidak tersedia' }}</span>
                        @empty
                            <span class="text-xs text-gray-400">Tanpa cabang</span>
                        @endforelse
                    </div>
                </button>
            @empty
                <div class="py-16 text-center"><div class="w-14 h-14 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto mb-3"><i class="ph ph-magnifying-glass text-gray-400 text-xl"></i></div><p class="text-sm text-gray-500 font-medium">Akun tidak ditemukan</p><p class="text-xs text-gray-400 mt-1">Coba ubah kata kunci pencarian</p></div>
            @endforelse
            @if ($users->hasPages())
                <div class="px-5 py-5 border-t border-gray-100">{{ $users->links() }}</div>
            @endif
        </div>
    </div>

    <template x-teleport="body">
        <div x-show="filterOpen" x-cloak data-erp-modal class="fixed inset-0 z-[100] flex flex-col justify-end" @keydown.escape.window="filterOpen = false">
            <div class="absolute inset-0 bg-black/60" @click="filterOpen = false"></div>
            <form method="GET" action="{{ route('user.index') }}" class="relative rounded-t-3xl bg-white pb-[env(safe-area-inset-bottom)] shadow-2xl">
                <input type="hidden" name="q" value="{{ request('q') }}">
                <div class="flex justify-center pt-3 pb-1"><div class="h-1.5 w-16 rounded-full bg-gray-300"></div></div>
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4"><h3 class="text-base font-bold text-gray-800">Filter Akun</h3><button type="button" @click="filterOpen = false" aria-label="Tutup filter" class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 text-gray-500"><i class="ph-bold ph-x"></i></button></div>
                <div class="p-5"><label for="mobile-user-role-filter" class="mb-2 block text-xs font-semibold text-gray-600">Role</label><select id="mobile-user-role-filter" name="role_id" class="h-11 w-full rounded-xl border border-gray-200 bg-white px-4 text-sm"><option value="">Semua role</option>@foreach($role as $roleOption)<option value="{{ $roleOption->id_role }}" @selected((string) $selectedRoleId === (string) $roleOption->id_role)>{{ $roleOption->nm_role }}</option>@endforeach</select></div>
                <div class="flex gap-3 border-t border-gray-100 px-5 py-4"><a href="{{ route('user.index') }}" class="flex h-11 flex-1 items-center justify-center rounded-xl border border-gray-200 text-sm font-semibold text-gray-600">Reset</a><button type="submit" class="h-11 flex-1 rounded-xl bg-[#0b595b] text-sm font-semibold text-white">Terapkan</button></div>
            </form>
        </div>
    </template>

    <template x-teleport="body">
        <div x-show="selected" x-cloak data-erp-modal role="dialog" aria-modal="true" aria-label="Detail Akun"
             x-transition:enter="transition-opacity ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[120] flex items-end justify-center sm:items-center sm:px-4" @keydown.escape.window="selected = null">
            <div class="absolute inset-0 bg-black/60" @click="selected = null"></div>
            <div x-show="selected" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="translate-y-full sm:translate-y-4 sm:scale-95" x-transition:enter-end="translate-y-0 sm:scale-100" x-transition:leave="transition ease-in duration-200 transform" x-transition:leave-start="translate-y-0 sm:scale-100" x-transition:leave-end="translate-y-full sm:translate-y-4 sm:scale-95" class="relative z-10 flex max-h-[90vh] w-full flex-col overflow-hidden rounded-t-3xl bg-white shadow-xl sm:max-w-sm sm:rounded-2xl">
                <div class="flex shrink-0 justify-center pt-3 pb-1 sm:hidden"><div class="h-1.5 w-16 rounded-full bg-gray-300"></div></div>
                <div class="flex shrink-0 items-center gap-3 border-b border-gray-100 px-5 pt-2 pb-4 sm:pt-5">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-emerald-100 bg-emerald-50 text-sm font-bold text-emerald-700"><span x-text="selected?.email ? selected.email[0].toUpperCase() : '?'" aria-hidden="true"></span></div>
                    <div class="min-w-0 flex-1"><h3 class="truncate text-[14px] font-bold text-gray-900" x-text="selected?.email || ''"></h3><p class="truncate text-[11px] text-gray-400" x-text="selected?.name || 'Belum tertaut'"></p></div>
                    <button type="button" @click="selected = null" aria-label="Tutup detail akun" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-500 transition-colors hover:bg-gray-200"><i class="ph-bold ph-x text-sm"></i></button>
                </div>
                <div class="shrink-0 px-4 pt-3 pb-1">
                    <label for="user-access-search" class="sr-only">Cari modul atau sub modul</label>
                    <div class="relative"><i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" aria-hidden="true"></i><input id="user-access-search" type="text" x-model="modalSearchQuery" placeholder="Cari modul atau sub modul..." class="h-9 w-full rounded-xl border border-gray-100 bg-gray-50 pl-9 pr-9 text-[13px] text-gray-800 placeholder-gray-400 outline-none transition-all focus:bg-white focus:outline-none focus:ring-1 focus:ring-emerald-500"><button x-show="modalSearchQuery.length > 0" x-cloak type="button" @click="modalSearchQuery = ''" aria-label="Hapus pencarian modul" class="absolute right-2 top-1/2 flex h-5 w-5 -translate-y-1/2 items-center justify-center rounded-full bg-gray-200 text-gray-500 transition-colors hover:bg-gray-300"><i class="ph-bold ph-x text-[10px]"></i></button></div>
                </div>
                <p x-show="selected?.permission_saving" x-cloak role="status" class="px-5 pt-2 text-[11px] font-medium text-emerald-700">Menyimpan perubahan akses…</p>
                <div class="max-h-[300px] overflow-y-auto overscroll-none px-4 py-2 scrollbar-hide flex flex-col gap-2">
                    <template x-for="group in filteredAccessGroups" :key="group.label">
                        <section class="mb-2">
                            <div class="flex items-center gap-3 rounded-xl px-3 py-3 transition-colors hover:bg-gray-50">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-100 bg-white" :class="[group.iconBg, group.iconColor]"><i :class="'ph-duotone ' + group.icon + ' text-base'" aria-hidden="true"></i></div>
                                <div class="min-w-0 flex-1"><div class="text-[13px] font-semibold text-gray-800" x-text="group.label"></div><div class="text-[11px] text-gray-400" x-text="hasFullGroupAccess(selected, group) ? 'Akses penuh' : (hasAnyGroupAccess(selected, group) ? 'Akses sebagian' : 'Tidak ada akses')"></div></div>
                                <button type="button" role="switch" :aria-checked="hasAnyGroupAccess(selected, group) ? 'true' : 'false'" :aria-disabled="(!selected?.can_change_permissions || selected?.permission_saving) ? 'true' : 'false'" :aria-label="'Akses grup ' + group.label" :title="selected?.can_change_permissions ? 'Simpan akses untuk seluruh modul di grup ini' : 'Hak akses hanya dapat diubah pada akun lain oleh pengguna berizin'" :disabled="!selected?.can_change_permissions || Boolean(selected?.permission_saving)" @click.stop="toggleGroupAccess(selected, group)" class="relative mr-1 h-6 w-11 shrink-0 rounded-full transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 disabled:cursor-not-allowed disabled:opacity-60" :class="hasAnyGroupAccess(selected, group) ? 'bg-emerald-500' : 'bg-gray-200'"><span class="pointer-events-none absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow-sm transition-transform" :class="hasAnyGroupAccess(selected, group) ? 'translate-x-5' : 'translate-x-0'"></span></button>
                            </div>
                            <div class="px-3 pt-1 pb-3"><div class="ml-[18px] flex flex-col gap-1 border-l-[1.5px] border-emerald-100">
                                <template x-for="module in group.modules" :key="module.key">
                                    <div class="flex items-center justify-between py-1.5 pr-2">
                                        <div class="flex min-w-0 flex-1 items-center"><div class="w-6 shrink-0"><div class="h-[1.5px] w-full bg-emerald-100"></div></div><span class="truncate pl-1.5 text-[13px] font-medium text-gray-500" x-text="module.label"></span></div>
                                        <button type="button" role="switch" :aria-checked="hasModuleAccess(selected, module) ? 'true' : 'false'" :aria-disabled="(!selected?.can_change_permissions || selected?.permission_saving) ? 'true' : 'false'" :aria-label="'Akses ' + module.label" :title="selected?.can_change_permissions ? 'Simpan akses untuk modul ini' : 'Hak akses hanya dapat diubah pada akun lain oleh pengguna berizin'" :disabled="!selected?.can_change_permissions || Boolean(selected?.permission_saving)" @click.stop="toggleModuleAccess(selected, module)" class="relative h-5 w-9 shrink-0 rounded-full transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 disabled:cursor-not-allowed disabled:opacity-60" :class="hasModuleAccess(selected, module) ? 'bg-emerald-500' : 'bg-gray-200'"><span class="pointer-events-none absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow-sm transition-transform" :class="hasModuleAccess(selected, module) ? 'translate-x-4' : 'translate-x-0'"></span></button>
                                    </div>
                                </template>
                            </div></div>
                        </section>
                    </template>
                    <div x-show="filteredAccessGroups.length === 0" class="py-8 text-center text-[13px] text-gray-400">Modul tidak ditemukan</div>
                </div>
                <div x-show="selected?.is_aktif" x-cloak class="flex w-full shrink-0 border-t border-gray-100 pb-[env(safe-area-inset-bottom)]">
                    @if (check_access('user.edit'))
                        <button type="button" @click="editSelected()" class="flex flex-1 items-center justify-center gap-2 py-4 text-sm font-semibold text-[#0b595b] transition-colors hover:bg-emerald-50"><i class="ph-bold ph-pencil-simple"></i>Edit</button>
                    @endif
                    @if (check_access('user.destroy'))
                        <div class="w-px bg-gray-100"></div><button type="button" @click="removeSelected()" class="flex flex-1 items-center justify-center gap-2 py-4 text-sm font-semibold text-red-500 transition-colors hover:bg-red-50"><i class="ph-bold ph-trash"></i>Hapus</button>
                    @endif
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <div x-show="drawerOpen" x-cloak data-erp-modal
             x-transition:enter="transition-opacity ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[100] flex flex-col justify-end lg:flex-row lg:justify-end" @keydown.escape.window="closeDrawer()">
            <div class="absolute inset-0 bg-black/70" @click="closeDrawer()"></div>
            <div x-show="drawerOpen" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="translate-y-full lg:translate-y-0 lg:translate-x-full" x-transition:enter-end="translate-y-0 lg:translate-x-0" x-transition:leave="transition ease-in duration-200 transform" x-transition:leave-start="translate-y-0 lg:translate-x-0" x-transition:leave-end="translate-y-full lg:translate-y-0 lg:translate-x-full" class="relative w-full md:max-w-lg lg:max-w-sm mx-auto lg:mx-0 bg-white shadow-2xl flex flex-col max-h-[90dvh] lg:max-h-full lg:h-full rounded-t-3xl lg:rounded-none overflow-hidden">
                <div class="flex justify-center pt-3 pb-1 lg:hidden"><div class="w-16 h-1.5 bg-gray-300 rounded-full"></div></div>
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between"><div><h3 class="text-[15px] font-bold text-gray-900" x-text="editing ? 'Edit Akun' : 'Tambah Akun'"></h3><p class="text-[12px] text-gray-400 mt-0.5" x-text="editing ? 'Perbarui data akun' : 'Isi data akun baru'"></p></div><button type="button" @click="closeDrawer()" class="w-9 h-9 rounded-full bg-gray-100 text-gray-500 flex items-center justify-center" aria-label="Tutup"><i class="ph-bold ph-x"></i></button></div>
                <form method="POST" :action="editing ? baseUrl + '/' + editing : storeUrl" class="flex-1 min-h-0 flex flex-col">
                    @csrf
                    <input type="hidden" name="_method" value="PUT" :disabled="!editing">
                    <input type="hidden" name="_user_id" :value="editing || ''">
                    <input type="hidden" name="link_only" value="1" :disabled="!editing">
                    <input type="hidden" name="staff_id" :value="form.staffId || ''">
                    <div class="relative flex-1 min-h-0 bg-gray-50/60"
                         x-data="{ scrollPos: 0, thumbHeight: 0, isScrolling: false, scrollTimeout: null, updateScroll() { if (!this.$refs.scrollArea) return; const el = this.$refs.scrollArea; const scrollRatio = el.clientHeight / el.scrollHeight; this.thumbHeight = Math.max(el.clientHeight * scrollRatio, 30); const maxScrollTop = el.scrollHeight - el.clientHeight; const maxThumbTop = el.clientHeight - this.thumbHeight - 16; this.scrollPos = maxScrollTop <= 0 ? 0 : (el.scrollTop / maxScrollTop) * maxThumbTop; } }"
                         x-init="$nextTick(() => updateScroll())" @resize.window="updateScroll()">
                        <div x-ref="scrollArea" @scroll="updateScroll(); isScrolling = true; clearTimeout(scrollTimeout); scrollTimeout = setTimeout(() => isScrolling = false, 800)" class="h-full overflow-y-auto overscroll-none scrollbar-hide px-5 pb-6 pt-5">
                            <div class="flex flex-col gap-5">
                        @if($errors->any())<div role="alert" class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700"><p class="font-semibold">Data belum dapat disimpan</p><ul class="mt-1 list-disc pl-4">@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>@endif
                        <div>
                            <div class="mb-1.5 flex items-center justify-between gap-3">
                                <label for="user-email" class="text-xs font-semibold text-gray-600">Alamat Email <span class="text-red-400">*</span></label>
                                <div x-show="!editing" x-cloak class="flex rounded-lg bg-gray-100 p-0.5" role="radiogroup" aria-label="Jenis email">
                                    <label class="cursor-pointer rounded-md px-2.5 py-1 text-[10px] font-medium transition-all" :class="form.emailType === 'auto' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500 hover:text-gray-700'">
                                        <input type="radio" name="email_type" value="auto" x-model="form.emailType" :disabled="Boolean(editing)" @change="setEmailType('auto')" class="sr-only">Bawaan
                                    </label>
                                    <label class="cursor-pointer rounded-md px-2.5 py-1 text-[10px] font-medium transition-all" :class="form.emailType === 'custom' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500 hover:text-gray-700'">
                                        <input type="radio" name="email_type" value="custom" x-model="form.emailType" :disabled="Boolean(editing)" @change="setEmailType('custom')" class="sr-only">Kustom
                                    </label>
                                </div>
                            </div>
                            <input id="user-email" @if($errors->has('email')) aria-invalid="true" aria-describedby="user-email-error" @endif name="email" x-model="form.email" :readonly="editing || form.emailType === 'auto'" :class="form.emailType === 'auto' ? 'bg-gray-50 text-gray-500 cursor-not-allowed border-gray-200' : 'bg-white text-gray-900 border-gray-200 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500'" required type="email" placeholder="Misal: nama@infruity.com" class="w-full h-11 border rounded-xl px-4 text-sm outline-none transition-all m-0">@error('email')<p id="user-email-error" class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="w-full">
                            <label for="user-staff-search" class="mb-1.5 block text-xs font-semibold text-gray-600">Tautkan ke Karyawan</label>
                            <div class="flex w-full flex-col overflow-hidden rounded-xl border border-gray-200 bg-white">
                                <div class="relative shrink-0 border-b border-gray-100 bg-gray-50/50">
                                    <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-base text-gray-400" aria-hidden="true"></i>
                                    <input id="user-staff-search" x-model="staffSearch" type="text" placeholder="Cari karyawan..." class="w-full bg-transparent py-3 pl-9 pr-4 text-sm outline-none transition-colors">
                                </div>
                                <div class="relative max-h-48 overflow-y-auto overscroll-none bg-white">
                                    <template x-for="staff in filteredStaffOptions" :key="staff.id">
                                        <button type="button" @click="toggleStaff(staff.id)" class="flex w-full cursor-pointer items-center gap-3 border-b border-gray-100 px-3.5 py-3 text-left transition-colors last:border-b-0 hover:bg-gray-50">
                                            <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full border-2 border-gray-300 transition-colors" :class="String(form.staffId) === String(staff.id) ? 'border-emerald-500' : ''" aria-hidden="true"><span class="h-2 w-2 rounded-full bg-emerald-500 transition-transform" :class="String(form.staffId) === String(staff.id) ? 'scale-100' : 'scale-0'"></span></span>
                                            <span class="flex min-w-0 flex-1 items-center justify-between gap-3"><span class="truncate text-[13px] font-medium" :class="String(form.staffId) === String(staff.id) ? 'font-bold text-gray-900' : ''" x-text="staff.name"></span></span>
                                        </button>
                                    </template>
                                    <p x-show="filteredStaffOptions.length === 0" class="px-4 py-5 text-center text-[13px] text-gray-400">Karyawan tidak ditemukan</p>
                                </div>
                            </div>
                        </div>
                        <fieldset x-show="!editing" x-cloak :disabled="Boolean(editing)" class="contents">
                        <div><label for="user-name" class="text-xs font-semibold text-gray-600 mb-1.5 block">Nama Lengkap <span class="text-red-400">*</span></label><input id="user-name" @if($errors->has('full_name')) aria-invalid="true" aria-describedby="user-name-error" @endif name="full_name" x-model="form.name" required type="text" class="w-full h-11 bg-white border border-gray-200 rounded-xl px-4 text-sm outline-none focus:border-emerald-500">@error('full_name')<p id="user-name-error" class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <div><label for="user-password" class="text-xs font-semibold text-gray-600 mb-1.5 block">Kata Sandi <span class="text-red-400" x-show="!editing">*</span></label><input id="user-password" @if($errors->has('password')) aria-invalid="true" aria-describedby="user-password-error" @endif name="password" x-model="form.password" :required="!editing" type="password" autocomplete="new-password" :placeholder="editing ? 'Kosongkan jika tidak diubah' : 'Masukkan kata sandi'" class="w-full h-11 bg-white border border-gray-200 rounded-xl px-4 text-sm outline-none focus:border-emerald-500">@error('password')<p id="user-password-error" class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <div><label for="user-role" class="text-xs font-semibold text-gray-600 mb-1.5 block">Hak Akses <span class="text-red-400">*</span></label><select id="user-role" @if($errors->has('id_role')) aria-invalid="true" aria-describedby="user-role-error" @endif name="id_role" x-model="form.role" required class="w-full h-11 bg-white border border-gray-200 rounded-xl px-4 text-sm outline-none focus:border-emerald-500"><option value="">Pilih hak akses</option>@foreach ($role as $item)<option value="{{ $item->id_role }}">{{ $item->nm_role }}</option>@endforeach</select>@error('id_role')<p id="user-role-error" class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <fieldset><legend class="text-xs font-semibold text-gray-600 mb-1.5">Cabang <span class="text-red-400">*</span></legend><div class="bg-white border border-gray-200 rounded-xl max-h-48 overflow-y-auto divide-y divide-gray-100">@forelse ($branch as $item)<label class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-gray-50 text-sm text-gray-700"><input type="checkbox" name="id_branch[]" value="{{ $item->id }}" :checked="form.branches.includes({{ $item->id }})" @change="toggleBranch({{ $item->id }})" class="accent-[#0b595b] rounded"><span>{{ $item->name }}</span></label>@empty <p class="px-4 py-3 text-sm text-gray-400">Belum ada cabang</p>@endforelse</div><p class="text-[11px] text-gray-400 mt-1.5">Pilih minimal satu cabang.</p></fieldset>
                        </fieldset>
                            </div>
                        </div>
                        <div class="absolute top-0 right-1 h-full w-1.5 pointer-events-none py-2">
                            <div class="h-full w-full rounded-full bg-gray-400/50 transition-opacity duration-300 ease-in-out" :style="`height: ${thumbHeight}px; transform: translateY(${scrollPos}px);`" :class="isScrolling ? 'opacity-100' : 'opacity-0'"></div>
                        </div>
                    </div>
                    <div class="px-5 pt-4 pb-[calc(1rem+env(safe-area-inset-bottom))] border-t border-gray-100 flex items-center gap-3"><button type="button" @click="closeDrawer()" class="flex-1 h-11 rounded-xl border border-gray-200 text-sm font-semibold text-gray-600">Batal</button><button type="submit" class="flex-1 h-11 rounded-xl bg-[#0b595b] text-white text-sm font-semibold" x-text="editing ? 'Simpan Perubahan' : 'Tambah Akun'"></button></div>
                </form>
            </div>
        </div>
    </template>
</div>
@endsection

@push('scripts')
<script>
function userMaster(users, storeUrl, baseUrl, oldUserId, oldName, oldEmail, oldRole, oldBranches, accessGroups, nextAutoEmail, oldEmailType, oldStaffId, staffOptions = []) {
    return {
        users, storeUrl, baseUrl, accessGroups, nextAutoEmail, oldEmailType, oldStaffId, staffOptions, staffSearch: '', drawerOpen: false, filterOpen: false, editing: null, selected: null, modalSearchQuery: '',
        form: { name: '', email: '', emailType: 'auto', password: '', role: '', branches: [], staffId: '' },
        get filteredAccessGroups() {
            const query = this.modalSearchQuery.toLowerCase().trim();
            if (!query) return this.accessGroups;
            return this.accessGroups.map(group => {
                if (group.label.toLowerCase().includes(query)) return group;
                return { ...group, modules: group.modules.filter(module => module.label.toLowerCase().includes(query)) };
            }).filter(group => group.modules.length > 0);
        },
        get filteredStaffOptions() {
            const query = this.staffSearch.trim().toLocaleLowerCase();
            return this.staffOptions.filter(staff => String(staff.name || '').toLocaleLowerCase().includes(query));
        },
        toggleStaff(staffId) {
            this.form.staffId = String(this.form.staffId) === String(staffId) ? '' : String(staffId);
        },
        permissionAccess(user, permission) {
            if (!user) return false;
            const overrides = user.permission_overrides || {};
            if (Object.prototype.hasOwnProperty.call(overrides, permission)) return Boolean(overrides[permission]);
            return Number(user.role) === 1 || (user.permissions || []).includes(permission);
        },
        hasModuleAccess(user, module) {
            return Boolean(user) && (module.permissions || []).some(permission => this.permissionAccess(user, permission));
        },
        hasAnyGroupAccess(user, group) {
            return Boolean(user) && group.modules.some(module => this.hasModuleAccess(user, module));
        },
        hasFullGroupAccess(user, group) {
            return Boolean(user) && group.modules.length > 0 && group.modules.every(module => this.hasModuleAccess(user, module));
        },
        async savePermissionOverrides(user, changes) {
            if (!user?.can_change_permissions || user.permission_saving || Object.keys(changes).length === 0) return false;

            const previousOverrides = { ...(user.permission_overrides || {}) };
            user.permission_overrides = { ...previousOverrides, ...changes };
            user.permission_saving = true;

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const response = await fetch(this.baseUrl + '/' + user.id + '/permissions', {
                    method: 'PUT',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({ overrides: changes }),
                });
                const result = await response.json().catch(() => ({}));
                if (!response.ok || !result.success) throw new Error(result.message || 'Silakan coba lagi.');
                user.permission_overrides = { ...previousOverrides, ...result.overrides };
                return true;
            } catch (error) {
                user.permission_overrides = previousOverrides;
                await Swal.fire('Gagal menyimpan akses', error.message, 'error');
                return false;
            } finally {
                user.permission_saving = false;
            }
        },
        toggleModuleAccess(user, module) {
            const allow = !this.hasModuleAccess(user, module);
            const changes = {};
            (module.permissions || []).forEach(permission => { changes[permission] = allow; });
            return this.savePermissionOverrides(user, changes);
        },
        toggleGroupAccess(user, group) {
            const allow = !this.hasAnyGroupAccess(user, group);
            const changes = {};
            group.modules.forEach(module => (module.permissions || []).forEach(permission => { changes[permission] = allow; }));
            return this.savePermissionOverrides(user, changes);
        },
        openDetail(id) { this.modalSearchQuery = ''; this.selected = this.users.find(user => user.id === id) || null; },
        editSelected() { const user = this.selected; this.selected = null; if (user) this.openEdit(user.id); },
        removeSelected() { const user = this.selected; this.selected = null; if (user) this.remove(user.id, user.name); },
        setEmailType(type) {
            this.form.emailType = type === 'auto' ? 'auto' : 'custom';
            this.form.email = this.form.emailType === 'auto' ? this.nextAutoEmail : '';
        },
        closeDrawer() {
            this.drawerOpen = false;
            const url = new URL(window.location.href);
            if (!url.searchParams.has('create')) return;
            url.searchParams.delete('create');
            window.history.replaceState(window.history.state, '', `${url.pathname}${url.search}${url.hash}`);
        },
        openCreate() {
            this.editing = null;
            this.staffSearch = '';
            this.form = { name: oldName || '', email: oldEmail || this.nextAutoEmail, emailType: this.oldEmailType === 'custom' ? 'custom' : 'auto', password: '', role: oldRole || '', branches: (oldBranches || []).map(Number), staffId: String(this.oldStaffId || '') };
            this.drawerOpen = true;
        },
        restoreOld() {
            const isEditing = Boolean(oldUserId && this.users.some(user => user.id === Number(oldUserId)));
            if (isEditing) this.openEdit(Number(oldUserId));
            else this.openCreate();
            this.form = { name: oldName || '', email: oldEmail || (isEditing ? '' : this.nextAutoEmail), emailType: isEditing ? 'custom' : (this.oldEmailType === 'custom' ? 'custom' : 'auto'), password: '', role: oldRole || '', branches: (oldBranches || []).map(Number), staffId: String(this.oldStaffId || '') };
        },
        openEdit(id) {
            const user = this.users.find(item => item.id === id);
            if (!user || user.is_aktif === false) return;
            this.editing = id;
            this.staffSearch = '';
            this.form = { name: user.name || '', email: user.email || '', emailType: 'custom', password: '', role: String(user.role || ''), branches: (user.branches || []).map(Number), staffId: String(user.staff_id || '') };
            this.drawerOpen = true;
        },
        toggleBranch(id) {
            this.form.branches = this.form.branches.includes(id) ? this.form.branches.filter(value => value !== id) : [...this.form.branches, id];
        },
        async remove(id, name) {
            const user = this.users.find(item => item.id === id);
            if (!user || user.is_aktif === false) return;
            const confirmed = await Swal.fire({ title: 'Hapus akun ' + name + '?', text: 'Akun pengguna akan dinonaktifkan.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Hapus', cancelButtonText: 'Batal', confirmButtonColor: '#dc2626' });
            if (!confirmed.isConfirmed) return;
            try {
                const response = await fetch(this.baseUrl + '/' + id, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' } });
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.message || 'Silakan coba lagi.');
                location.reload();
            } catch (error) {
                Swal.fire('Gagal menghapus akun', error.message, 'error');
            }
        },
    };
}
</script>
@endpush
