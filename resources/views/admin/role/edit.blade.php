@extends('layouts.erp-tailwind')

@section('title', 'Hak Akses ' . $role->nm_role . ' - Master')
@section('page-title', 'Hak Akses')
@section('dashboard-fullscreen', '1')

@section('content')
<div class="flex-1 min-h-0 flex flex-col bg-white lg:rounded-2xl lg:border lg:border-gray-100 lg:shadow-sm overflow-hidden" x-data="{ query: '' }">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-3 shrink-0"><a href="{{ route('roles.index') }}" aria-label="Kembali ke hak akses" class="w-9 h-9 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center"><i class="ph-bold ph-arrow-left"></i></a><div class="min-w-0"><h2 class="text-[15px] font-bold text-gray-900 truncate">{{ $role->nm_role }}</h2><p class="text-[12px] text-gray-400 mt-0.5">Atur izin modul untuk role ini</p></div></div>
    <form action="{{ route('role-menu.update', $role->id_role) }}" method="POST" class="flex-1 min-h-0 flex flex-col">@csrf @method('PUT')
        <div class="flex-1 min-h-0 overflow-y-auto bg-gray-50/60 px-5 py-5 pb-8 space-y-4">
            <div class="relative"><i class="ph ph-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i><input x-model="query" type="search" placeholder="Cari modul..." class="w-full h-11 rounded-xl border border-gray-200 bg-white pl-11 pr-4 text-sm outline-none focus:border-emerald-500"></div>
            @foreach($permissions as $prefix => $routes)
                <section x-show="!query || (@js(strtolower($routes['label']))).includes(query.toLowerCase())" class="rounded-2xl border border-gray-100 bg-white overflow-hidden">
                    <h3 class="px-4 py-3 border-b border-gray-100 text-sm font-bold text-gray-800">{{ $routes['label'] }}</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-0">
                        @foreach($routes['actions'] as $key => $label)
                            @php($permission = $prefix . '.' . $key)
                            <label class="flex items-center justify-between gap-3 px-4 py-3 border-b border-gray-50 text-[13px] text-gray-600 cursor-pointer"><span>{{ $label }}</span><input type="checkbox" name="permissions[]" value="{{ $permission }}" @checked(in_array($permission, $rolePermissions)) class="w-4 h-4 accent-[#0b595b] shrink-0"></label>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
        <div class="shrink-0 bg-white border-t border-gray-100 px-5 pt-4 pb-[calc(1rem+env(safe-area-inset-bottom))] flex items-center gap-3"><a href="{{ route('roles.index') }}" class="flex-1 h-11 rounded-xl border border-gray-200 text-sm font-semibold text-gray-600 flex items-center justify-center">Batal</a><button type="submit" class="flex-1 h-11 rounded-xl bg-[#0b595b] text-white text-sm font-semibold">Simpan Hak Akses</button></div>
    </form>
</div>
@endsection
