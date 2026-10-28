@extends('layouts.erp-tailwind')

@section('title', 'Hak Akses - Master')
@section('page-title', 'Hak Akses')
@section('page-breadcrumb')
    <span>Master</span><i class="ph ph-caret-right text-[0.55rem]"></i><span>Hak Akses</span>
@endsection

@section('content')
<div x-data="{
        createOpen: false,
        roleDetailOpen: @js(isset($role)),
        permissionOpen: false,
        roleActionError: '',
        roleIndexUrl: @js(route('roles.index', request()->query())),
        roleDeleteUrl: @js(isset($role) ? route('roles.destroy', $role->id_role) : ''),
        closeRoleDetail() { this.roleDetailOpen = false; window.location.assign(this.roleIndexUrl); },
        closePermissionOverlay() { this.permissionOpen = false; this.roleDetailOpen = @js(isset($role)); },
        async deleteRole() {
            if (!this.roleDeleteUrl || !window.confirm('Yakin hapus role ini?')) return;
            this.roleActionError = '';
            try {
                const response = await fetch(this.roleDeleteUrl, {
                    method: 'DELETE',
                    credentials: 'same-origin',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                        'Accept': 'application/json'
                    }
                });
                const result = await response.json().catch(() => ({}));
                if (!response.ok || result.success !== true) {
                    this.roleActionError = result.message || 'Role tidak dapat dihapus.';
                    return;
                }
                window.location.assign(this.roleIndexUrl);
            } catch (error) {
                this.roleActionError = 'Terjadi kesalahan saat menghapus role.';
            }
        }
    }"
     x-init="if (new URLSearchParams(location.search).has('create') || @js($errors->any() || session()->has('error'))) createOpen = true"
     @mobile-add-toggle.window="createOpen = true"
     class="flex-1 flex flex-col min-h-0 -mx-4 md:mx-0">
    <div class="erp-surface bg-white rounded-none lg:rounded-2xl lg:border lg:border-gray-100 lg:shadow-sm flex-1 flex flex-col min-h-0 overflow-hidden">
        <div class="hidden md:flex gap-3 items-center p-4 border-b border-gray-100">
            <form action="{{ route('roles.index') }}" method="GET" class="relative flex-1"><i class="ph ph-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i><input name="q" value="{{ $search }}" type="search" placeholder="Cari role..." class="w-full h-11 rounded-full border border-gray-100 bg-gray-50/50 pl-11 pr-4 text-[13px] outline-none focus:border-emerald-500"></form>
            @if(check_access('role.store'))<button type="button" @click="createOpen = true" class="h-11 px-5 rounded-full bg-[#0b595b] text-white text-[13px] font-semibold flex items-center gap-2"><i class="ph-bold ph-plus"></i> Tambah Role</button>@endif
        </div>
        <div class="hidden md:block px-5 py-3 bg-gray-50/50 border-b border-gray-100 text-xs text-gray-500">Menampilkan {{ $roles->count() }} dari {{ $roles->total() }} role</div>
        <div class="flex-1 overflow-y-auto pb-28 md:pb-0">
            @forelse($roles as $roleItem)
                @php($permissionCount = (int) $roleItem->role_menu_count)
                @if($canShowRoles)
                    <a href="{{ route('roles.show', $roleItem->id_role) }}" class="flex items-center gap-3 px-4 py-4 border-b border-gray-100 hover:bg-gray-50/50 active:bg-gray-50 transition-colors">
                @else
                    <div class="flex items-center gap-3 px-4 py-4 border-b border-gray-100">
                @endif
                    <div @class(['w-10 h-10 rounded-xl border flex items-center justify-center shrink-0 text-sm font-bold', 'bg-emerald-50 border-emerald-100 text-emerald-700' => $loop->index % 5 === 0, 'bg-blue-50 border-blue-100 text-blue-700' => $loop->index % 5 === 1, 'bg-amber-50 border-amber-100 text-amber-700' => $loop->index % 5 === 2, 'bg-rose-50 border-rose-100 text-rose-700' => $loop->index % 5 === 3, 'bg-violet-50 border-violet-100 text-violet-700' => $loop->index % 5 === 4])>{{ strtoupper(substr($roleItem->nm_role, 0, 1)) }}</div>
                    <span class="flex-1 min-w-0"><span class="block text-[13px] font-semibold text-gray-900 truncate">{{ $roleItem->nm_role }}</span><span class="block text-[11px] text-gray-400 mt-0.5 truncate">ROLE-{{ str_pad((string) $roleItem->id_role, 4, '0', STR_PAD_LEFT) }}</span></span>
                    <span @class(['shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold', 'bg-emerald-50 text-emerald-700' => $permissionCount > 0, 'bg-gray-100 text-gray-500' => $permissionCount === 0])>{{ $permissionCount }} izin</span>
                    @if($canShowRoles)<i class="ph ph-caret-right text-gray-300 text-sm shrink-0"></i>@endif
                @if($canShowRoles)
                    </a>
                @else
                    </div>
                @endif
            @empty
                <div class="py-16 text-center text-gray-400"><i class="ph ph-shield-check text-3xl"></i><p class="mt-2 text-sm">Role tidak ditemukan</p></div>
            @endforelse
            @if($roles->hasPages())<div class="px-5 py-5">{{ $roles->links() }}</div>@endif
        </div>
    </div>
    <template x-teleport="body"><div x-show="createOpen" x-cloak data-erp-modal class="fixed inset-0 z-[100] flex flex-col justify-end lg:items-end" @keydown.escape.window="createOpen = false"><div class="absolute inset-0 bg-black/70" @click="createOpen = false"></div><div class="relative bg-white w-full lg:w-[420px] lg:h-full max-h-[95dvh] flex flex-col rounded-t-3xl lg:rounded-none overflow-hidden"><div class="flex justify-center pt-3 pb-1 lg:hidden"><div class="w-16 h-1.5 bg-gray-300 rounded-full"></div></div><div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between"><div><h3 class="text-[15px] font-bold text-gray-900">Tambah Role</h3><p class="text-[12px] text-gray-400 mt-0.5">Buat kelompok izin pengguna</p></div><button type="button" @click="createOpen = false" class="w-9 h-9 rounded-full bg-gray-100 text-gray-500" aria-label="Tutup"><i class="ph-bold ph-x"></i></button></div><form action="{{ route('roles.store') }}" method="POST" class="min-h-0 flex flex-col">@csrf<div class="min-h-0 overflow-y-auto bg-gray-50/60 px-5 py-5 space-y-5"><div><label for="role-name" class="text-xs font-semibold text-gray-600 mb-1.5 block">Nama Role <span class="text-red-400">*</span></label><input id="role-name" name="nm_role" value="{{ old('nm_role') }}" required minlength="4" maxlength="50" pattern="[A-Za-z ]+" class="w-full h-11 bg-white border border-gray-200 rounded-xl px-4 text-sm outline-none focus:border-emerald-500"></div><div><label for="role-description" class="text-xs font-semibold text-gray-600 mb-1.5 block">Keterangan</label><textarea id="role-description" name="description" maxlength="255" rows="3" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 text-sm outline-none focus:border-emerald-500">{{ old('description') }}</textarea></div>@if($errors->any())<p class="text-xs text-red-600">{{ $errors->first() }}</p>@endif @if(session('error'))<p class="text-xs text-red-600">{{ session('error') }}</p>@endif</div><div class="flex shrink-0 gap-3 px-5 py-4 pb-[calc(1rem+env(safe-area-inset-bottom))] border-t border-gray-100"><button type="button" @click="createOpen = false" class="flex-1 h-11 rounded-xl border border-gray-200 text-sm font-semibold text-gray-600">Batal</button><button type="submit" class="flex-1 h-11 rounded-xl bg-[#0b595b] text-white text-sm font-semibold">Tambah Role</button></div></form></div></div></template>
@if(isset($role))
    @include('admin.role.show')
    @include('admin.role.edit')
@endif
</div>
@endsection
