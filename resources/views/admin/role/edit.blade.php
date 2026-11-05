<template x-teleport="body">
    <div x-show="permissionOpen" x-cloak data-role-permission-overlay role="dialog" aria-modal="true" aria-label="Atur hak akses {{ $role->nm_role }}"
         x-transition:enter="transition-opacity ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[120] flex flex-col justify-end lg:items-end"
         @keydown.escape.window="if (permissionOpen) closePermissionOverlay()">
        <button type="button" @click="closePermissionOverlay()" aria-label="Tutup editor hak akses" class="absolute inset-0 bg-black/60"></button>
        <div x-show="permissionOpen"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="translate-y-full lg:translate-y-0 lg:translate-x-full"
             x-transition:enter-end="translate-y-0 lg:translate-x-0"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="translate-y-0 lg:translate-x-0"
             x-transition:leave-end="translate-y-full lg:translate-y-0 lg:translate-x-full"
             x-data="{ query: '' }"
             class="relative z-10 flex h-[90dvh] max-h-[95dvh] w-full flex-col overflow-hidden rounded-t-3xl bg-white shadow-2xl lg:h-full lg:max-h-full lg:w-[640px] lg:rounded-none">
            <div class="flex justify-center pt-3 pb-1 lg:hidden"><div class="h-1.5 w-16 rounded-full bg-gray-300"></div></div>
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                <div class="min-w-0">
                    <h2 class="truncate text-[15px] font-bold text-gray-900">{{ $role->nm_role }}</h2>
                    <p class="mt-0.5 text-[12px] text-gray-400">Atur izin modul untuk role ini</p>
                </div>
                <button type="button" @click="closePermissionOverlay()" aria-label="Tutup" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-500"><i class="ph-bold ph-x"></i></button>
            </div>
            <form action="{{ route('role-menu.update', $role->id_role) }}" method="POST" class="flex min-h-0 flex-1 flex-col">
                @csrf
                @method('PUT')
                <div class="flex-1 min-h-0 space-y-4 overflow-y-auto overscroll-contain bg-gray-50/60 px-5 py-5 pb-8">
                    <div class="relative">
                        <i class="ph ph-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-[16px]"></i>
                        <input x-model="query" type="search" placeholder="Cari modul..." aria-label="Cari modul" class="h-11 w-full rounded-xl border border-gray-200 bg-white pl-11 pr-4 text-sm outline-none focus:border-emerald-500">
                    </div>
                    @foreach($permissions as $prefix => $routes)
                        <section x-show="!query || (@js(strtolower($routes['label']))).includes(query.toLowerCase())" class="overflow-hidden rounded-2xl border border-gray-100 bg-white">
                            <h3 class="border-b border-gray-100 px-4 py-3 text-sm font-bold text-gray-800">{{ $routes['label'] }}</h3>
                            <div class="grid grid-cols-1 gap-0 sm:grid-cols-2">
                                @foreach($routes['actions'] as $key => $label)
                                    @php($permission = $prefix . '.' . $key)
                                    <label class="flex cursor-pointer items-center justify-between gap-3 border-b border-gray-50 px-4 py-3 text-[13px] text-gray-600">
                                        <span>{{ $label }}</span>
                                        <input type="checkbox" name="permissions[]" value="{{ $permission }}" @checked(in_array($permission, $rolePermissions, true)) class="h-4 w-4 shrink-0 accent-[#0b595b]">
                                    </label>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>
                <div class="flex shrink-0 items-center gap-3 border-t border-gray-100 bg-white px-5 pt-4 pb-[calc(1rem+env(safe-area-inset-bottom))]">
                    <button type="button" @click="closePermissionOverlay()" class="h-11 flex-1 rounded-xl border border-gray-200 text-sm font-semibold text-gray-600">Batal</button>
                    <button type="submit" class="h-11 flex-1 rounded-xl bg-[#0b595b] text-sm font-semibold text-white">Simpan Hak Akses</button>
                </div>
            </form>
        </div>
    </div>
</template>
