@extends('layouts.erp-tailwind')

@section('title', 'Akun Pengguna - Master')
@section('page-title', 'Akun Pengguna')
@section('page-breadcrumb')
    <span>Master</span><i class="ph ph-caret-right text-[0.55rem]"></i><span>Akun Pengguna</span>
@endsection

@php
    $editableUsers = $users->getCollection()->map(fn ($user) => [
        'id' => $user->id_user,
        'name' => $user->nm_user,
        'email' => $user->email,
        'role' => $user->RoleUser?->id_role,
        'role_name' => $user->RoleUser?->role?->nm_role ?? 'Tanpa peran',
        'branches' => $user->branches->pluck('branch_id')->all(),
        'branch_names' => $user->branches->map(fn ($assignment) => $assignment->branch?->name)->filter()->values()->all(),
    ])->values();
@endphp

@section('content')
<div x-data="userMaster(@js($editableUsers), @js(route('user.store')), @js(url('user')), @js(old('_user_id')), @js(old('full_name')), @js(old('email')), @js(old('id_role')), @js(old('id_branch', [])))"
     x-init="if (@js($errors->any())) restoreOld(); else if (new URLSearchParams(location.search).has('create')) openCreate()"
     @mobile-search-toggle.window="searchOpen = !searchOpen; if (searchOpen) $nextTick(() => $refs.mobileSearch.focus())"
     @mobile-add-toggle.window="openCreate()"
     class="flex-1 flex flex-col min-h-0 -mx-4 md:mx-0">
    <div class="bg-white rounded-none lg:rounded-2xl lg:shadow-sm lg:border lg:border-gray-100 flex-1 flex flex-col min-h-0 overflow-hidden">
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
                    <div class="w-9 h-9 md:w-10 md:h-10 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 font-bold text-sm">{{ mb_strtoupper(mb_substr($user->email ?: $user->nm_user, 0, 2)) }}</div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-semibold text-gray-900 truncate">{{ $user->email }}</div>
                        <div class="text-[11px] text-gray-400 mt-0.5 truncate">{{ $user->nm_user }} · {{ $user->RoleUser?->role?->nm_role ?? 'Tanpa peran' }}</div>
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

    <div x-show="searchOpen" x-transition x-cloak class="md:hidden fixed left-0 right-0 z-[80] px-4 flex justify-center" style="bottom: calc(96px + env(safe-area-inset-bottom))">
        <form method="GET" action="{{ route('user.index') }}" class="relative w-full max-w-[360px] h-12 bg-white/90 backdrop-blur-xl border border-white/70 rounded-full shadow-lg flex items-center">
            <i class="ph ph-magnifying-glass absolute left-4 text-[#0b595b]/70"></i>
            <input x-ref="mobileSearch" name="q" value="{{ request('q') }}" type="search" placeholder="Cari akun pengguna..." class="w-full bg-transparent pl-11 pr-16 h-full text-sm font-medium text-gray-800 outline-none rounded-full">
            <button type="submit" class="absolute right-4 text-xs font-semibold text-[#0b595b]">Cari</button>
        </form>
    </div>

    <template x-teleport="body">
        <div x-show="selected" x-cloak role="dialog" aria-modal="true" aria-label="Detail Akun" class="fixed inset-0 z-[110] flex items-center justify-center px-4" @keydown.escape.window="selected = null">
            <div class="absolute inset-0 bg-black/60" @click="selected = null"></div>
            <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-xl overflow-hidden">
                <div class="flex items-center justify-between px-6 pt-6 pb-4"><h3 class="text-lg font-bold text-gray-800">Detail Akun</h3><button type="button" @click="selected = null" class="w-8 h-8 rounded-full bg-gray-100 text-gray-500 flex items-center justify-center" aria-label="Tutup"><i class="ph-bold ph-x"></i></button></div>
                <template x-if="selected">
                    <div class="px-6 pb-6 space-y-5 text-sm">
                        <div class="flex items-center gap-3"><i class="ph ph-envelope text-xl text-emerald-600 w-8"></i><div class="min-w-0"><div class="font-semibold text-gray-800 break-all" x-text="selected.email"></div><div class="text-[11px] text-gray-400">Alamat Email</div></div></div>
                        <div class="flex items-center gap-3"><i class="ph ph-user text-xl text-emerald-600 w-8"></i><div><div class="font-semibold text-gray-800" x-text="selected.name"></div><div class="text-[11px] text-gray-400">Nama Pengguna</div></div></div>
                        <div class="flex items-center gap-3"><i class="ph ph-shield-check text-xl text-emerald-600 w-8"></i><div><div class="font-semibold text-gray-800" x-text="selected.role_name"></div><div class="text-[11px] text-gray-400">Hak Akses</div></div></div>
                        <div class="flex items-center gap-3"><i class="ph ph-map-pin text-xl text-emerald-600 w-8"></i><div><div class="font-semibold text-gray-800" x-text="selected.branch_names.join(', ') || 'Tanpa cabang'"></div><div class="text-[11px] text-gray-400">Cabang</div></div></div>
                    </div>
                </template>
                <div class="flex border-t border-gray-100">
                    @if (check_access('user.edit'))
                        <button type="button" @click="editSelected()" class="flex-1 h-12 text-sm font-semibold text-[#0b595b] hover:bg-emerald-50"><i class="ph ph-pencil-simple mr-1"></i> Edit</button>
                    @endif
                    @if (check_access('user.destroy'))
                        <button type="button" @click="removeSelected()" class="flex-1 h-12 text-sm font-semibold text-red-600 hover:bg-red-50 border-l border-gray-100"><i class="ph ph-trash mr-1"></i> Hapus</button>
                    @endif
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <div x-show="drawerOpen" x-cloak class="fixed inset-0 z-[100] flex flex-col justify-end lg:flex-row lg:justify-end" @keydown.escape.window="drawerOpen = false">
            <div class="absolute inset-0 bg-black/70" @click="drawerOpen = false"></div>
            <div x-show="drawerOpen" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="translate-y-full lg:translate-y-0 lg:translate-x-full" x-transition:enter-end="translate-y-0 lg:translate-x-0" x-transition:leave="transition ease-in duration-200 transform" x-transition:leave-start="translate-y-0 lg:translate-x-0" x-transition:leave-end="translate-y-full lg:translate-y-0 lg:translate-x-full" class="relative w-full md:max-w-lg lg:max-w-sm mx-auto lg:mx-0 bg-white shadow-2xl flex flex-col max-h-[90vh] lg:max-h-full lg:h-full rounded-t-3xl lg:rounded-none overflow-hidden">
                <div class="flex justify-center pt-3 pb-1 lg:hidden"><div class="w-16 h-1.5 bg-gray-300 rounded-full"></div></div>
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between"><div><h3 class="text-[15px] font-bold text-gray-900" x-text="editing ? 'Edit Akun' : 'Tambah Akun'"></h3><p class="text-[12px] text-gray-400 mt-0.5" x-text="editing ? 'Perbarui data akun' : 'Isi data akun baru'"></p></div><button type="button" @click="drawerOpen = false" class="w-9 h-9 rounded-full bg-gray-100 text-gray-500 flex items-center justify-center" aria-label="Tutup"><i class="ph-bold ph-x"></i></button></div>
                <form method="POST" :action="editing ? baseUrl + '/' + editing : storeUrl" class="flex-1 min-h-0 flex flex-col">
                    @csrf
                    <input type="hidden" name="_method" value="PUT" :disabled="!editing">
                    <input type="hidden" name="_user_id" :value="editing || ''">
                    <div class="flex-1 overflow-y-auto bg-gray-50/60 px-5 py-5 space-y-5">
                        <div><label for="user-name" class="text-xs font-semibold text-gray-600 mb-1.5 block">Nama Lengkap <span class="text-red-400">*</span></label><input id="user-name" name="full_name" x-model="form.name" required type="text" class="w-full h-11 bg-white border border-gray-200 rounded-xl px-4 text-sm outline-none focus:border-emerald-500"></div>
                        <div><label for="user-email" class="text-xs font-semibold text-gray-600 mb-1.5 block">Alamat Email <span class="text-red-400">*</span></label><input id="user-email" name="email" x-model="form.email" required type="email" placeholder="nama@infruity.com" class="w-full h-11 bg-white border border-gray-200 rounded-xl px-4 text-sm outline-none focus:border-emerald-500"></div>
                        <div><label for="user-password" class="text-xs font-semibold text-gray-600 mb-1.5 block">Kata Sandi <span class="text-red-400" x-show="!editing">*</span></label><input id="user-password" name="password" x-model="form.password" :required="!editing" type="password" autocomplete="new-password" :placeholder="editing ? 'Kosongkan jika tidak diubah' : 'Masukkan kata sandi'" class="w-full h-11 bg-white border border-gray-200 rounded-xl px-4 text-sm outline-none focus:border-emerald-500"></div>
                        <div><label for="user-role" class="text-xs font-semibold text-gray-600 mb-1.5 block">Hak Akses <span class="text-red-400">*</span></label><select id="user-role" name="id_role" x-model="form.role" required class="w-full h-11 bg-white border border-gray-200 rounded-xl px-4 text-sm outline-none focus:border-emerald-500"><option value="">Pilih hak akses</option>@foreach ($role as $item)<option value="{{ $item->id_role }}">{{ $item->nm_role }}</option>@endforeach</select></div>
                        <fieldset><legend class="text-xs font-semibold text-gray-600 mb-1.5">Cabang <span class="text-red-400">*</span></legend><div class="bg-white border border-gray-200 rounded-xl max-h-48 overflow-y-auto divide-y divide-gray-100">@forelse ($branch as $item)<label class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-gray-50 text-sm text-gray-700"><input type="checkbox" name="id_branch[]" value="{{ $item->id }}" :checked="form.branches.includes({{ $item->id }})" @change="toggleBranch({{ $item->id }})" class="accent-[#0b595b] rounded"><span>{{ $item->name }}</span></label>@empty <p class="px-4 py-3 text-sm text-gray-400">Belum ada cabang</p>@endforelse</div><p class="text-[11px] text-gray-400 mt-1.5">Pilih minimal satu cabang.</p></fieldset>
                    </div>
                    <div class="px-5 pt-4 pb-[calc(1rem+env(safe-area-inset-bottom))] border-t border-gray-100 flex items-center gap-3"><button type="button" @click="drawerOpen = false" class="flex-1 h-11 rounded-xl border border-gray-200 text-sm font-semibold text-gray-600">Batal</button><button type="submit" class="flex-1 h-11 rounded-xl bg-[#0b595b] text-white text-sm font-semibold" x-text="editing ? 'Simpan Perubahan' : 'Tambah Akun'"></button></div>
                </form>
            </div>
        </div>
    </template>
</div>
@endsection

@push('scripts')
<script>
function userMaster(users, storeUrl, baseUrl, oldUserId, oldName, oldEmail, oldRole, oldBranches) {
    return {
        users, storeUrl, baseUrl, drawerOpen: false, searchOpen: false, editing: null, selected: null,
        form: { name: '', email: '', password: '', role: '', branches: [] },
        openDetail(id) { this.selected = this.users.find(user => user.id === id) || null; },
        editSelected() { const user = this.selected; this.selected = null; if (user) this.openEdit(user.id); },
        removeSelected() { const user = this.selected; this.selected = null; if (user) this.remove(user.id, user.name); },
        openCreate() {
            this.editing = null;
            this.form = { name: oldName || '', email: oldEmail || '', password: '', role: oldRole || '', branches: (oldBranches || []).map(Number) };
            this.drawerOpen = true;
        },
        restoreOld() {
            if (oldUserId && this.users.some(user => user.id === Number(oldUserId))) this.openEdit(Number(oldUserId));
            else this.openCreate();
            this.form = { name: oldName || '', email: oldEmail || '', password: '', role: oldRole || '', branches: (oldBranches || []).map(Number) };
        },
        openEdit(id) {
            const user = this.users.find(item => item.id === id);
            if (!user) return;
            this.editing = id;
            this.form = { name: user.name || '', email: user.email || '', password: '', role: String(user.role || ''), branches: (user.branches || []).map(Number) };
            this.drawerOpen = true;
        },
        toggleBranch(id) {
            this.form.branches = this.form.branches.includes(id) ? this.form.branches.filter(value => value !== id) : [...this.form.branches, id];
        },
        async remove(id, name) {
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
