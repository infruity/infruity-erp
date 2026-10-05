@extends('layouts.erp-tailwind')

@section('title', 'Hak Akses - Master')
@section('page-title', 'Hak Akses')
@section('page-breadcrumb')
    <span>Master</span><i class="ph ph-caret-right text-[0.55rem]"></i><span>Hak Akses</span>
@endsection

@section('content')
<div x-data="{ searchOpen: false, createOpen: false }"
     x-init="if (new URLSearchParams(location.search).has('create') || @js($errors->any() || session()->has('error'))) createOpen = true"
     @mobile-search-toggle.window="searchOpen = !searchOpen; if (searchOpen) $nextTick(() => $refs.search.focus())"
     @mobile-add-toggle.window="createOpen = true"
     class="flex-1 flex flex-col min-h-0 -mx-4 md:mx-0">
    <div class="bg-white rounded-none lg:rounded-2xl lg:border lg:border-gray-100 lg:shadow-sm flex-1 flex flex-col min-h-0 overflow-hidden">
        <div class="hidden md:flex gap-3 items-center p-4 border-b border-gray-100">
            <form action="{{ route('roles.index') }}" method="GET" class="relative flex-1"><i class="ph ph-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i><input name="q" value="{{ $search }}" type="search" placeholder="Cari role..." class="w-full h-11 rounded-full border border-gray-100 bg-gray-50/50 pl-11 pr-4 text-[13px] outline-none focus:border-emerald-500"></form>
            @if(check_access('role.store'))<button type="button" @click="createOpen = true" class="h-11 px-5 rounded-full bg-[#0b595b] text-white text-[13px] font-semibold flex items-center gap-2"><i class="ph-bold ph-plus"></i> Tambah Role</button>@endif
        </div>
        <div class="hidden md:block px-5 py-3 bg-gray-50/50 border-b border-gray-100 text-xs text-gray-500">Menampilkan {{ $data->count() }} dari {{ $data->total() }} role</div>
        <div class="flex-1 overflow-y-auto pb-28 md:pb-0">
            @forelse($data as $role)
                <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100 hover:bg-gray-50/50">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-700 flex items-center justify-center shrink-0"><i class="ph ph-shield-check text-xl"></i></div>
                    <a href="{{ route('roles.show', $role->id_role) }}" class="flex-1 min-w-0"><span class="block text-sm font-semibold text-gray-900 truncate">{{ $role->nm_role }}</span><span class="block text-[11px] text-gray-400 mt-0.5 truncate">{{ $role->role_menu_count }} izin{{ $role->description ? ' • ' . $role->description : '' }}</span></a>
                    <a href="{{ route('roles.show', $role->id_role) }}" class="w-9 h-9 rounded-lg flex items-center justify-center text-gray-400 hover:text-emerald-700 hover:bg-emerald-50" aria-label="Atur izin {{ $role->nm_role }}"><i class="ph ph-sliders-horizontal"></i></a>
                </div>
            @empty
                <div class="py-16 text-center text-gray-400"><i class="ph ph-shield-check text-3xl"></i><p class="mt-2 text-sm">Hak akses tidak ditemukan</p></div>
            @endforelse
            @if($data->hasPages())<div class="px-5 py-5">{{ $data->links() }}</div>@endif
        </div>
    </div>
    <form x-show="searchOpen" x-cloak action="{{ route('roles.index') }}" method="GET" class="md:hidden fixed left-4 right-4 z-[80] h-12 bg-white/90 backdrop-blur-xl border border-gray-200 rounded-full shadow-lg flex items-center" style="bottom: calc(96px + env(safe-area-inset-bottom))"><i class="ph ph-magnifying-glass absolute left-4 text-[#0b595b]"></i><input x-ref="search" name="q" value="{{ $search }}" type="search" placeholder="Cari role..." class="w-full h-full bg-transparent pl-11 pr-12 text-sm outline-none rounded-full"><button type="submit" class="absolute right-3 text-xs font-semibold text-[#0b595b]">Cari</button></form>
    <template x-teleport="body"><div x-show="createOpen" x-cloak class="fixed inset-0 z-[100] flex flex-col justify-end lg:items-end" @keydown.escape.window="createOpen = false"><div class="absolute inset-0 bg-black/70" @click="createOpen = false"></div><div class="relative bg-white w-full lg:w-[420px] lg:h-full rounded-t-3xl lg:rounded-none overflow-hidden"><div class="flex justify-center pt-3 pb-1 lg:hidden"><div class="w-16 h-1.5 bg-gray-300 rounded-full"></div></div><div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between"><div><h3 class="text-[15px] font-bold text-gray-900">Tambah Role</h3><p class="text-[12px] text-gray-400 mt-0.5">Buat kelompok izin pengguna</p></div><button type="button" @click="createOpen = false" class="w-9 h-9 rounded-full bg-gray-100 text-gray-500" aria-label="Tutup"><i class="ph-bold ph-x"></i></button></div><form action="{{ route('roles.store') }}" method="POST">@csrf<div class="bg-gray-50/60 px-5 py-5 space-y-5"><div><label for="role-name" class="text-xs font-semibold text-gray-600 mb-1.5 block">Nama Role <span class="text-red-400">*</span></label><input id="role-name" name="nm_role" value="{{ old('nm_role') }}" required minlength="4" maxlength="50" pattern="[A-Za-z ]+" class="w-full h-11 bg-white border border-gray-200 rounded-xl px-4 text-sm outline-none focus:border-emerald-500"></div><div><label for="role-description" class="text-xs font-semibold text-gray-600 mb-1.5 block">Keterangan</label><textarea id="role-description" name="description" maxlength="255" rows="3" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 text-sm outline-none focus:border-emerald-500">{{ old('description') }}</textarea></div>@if($errors->any())<p class="text-xs text-red-600">{{ $errors->first() }}</p>@endif @if(session('error'))<p class="text-xs text-red-600">{{ session('error') }}</p>@endif</div><div class="flex gap-3 px-5 py-4 pb-[calc(1rem+env(safe-area-inset-bottom))] border-t border-gray-100"><button type="button" @click="createOpen = false" class="flex-1 h-11 rounded-xl border border-gray-200 text-sm font-semibold text-gray-600">Batal</button><button type="submit" class="flex-1 h-11 rounded-xl bg-[#0b595b] text-white text-sm font-semibold">Tambah Role</button></div></form></div></div></template>
</div>
@endsection
