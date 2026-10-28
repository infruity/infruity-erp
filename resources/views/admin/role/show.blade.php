<template x-teleport="body">
    <div x-show="roleDetailOpen" x-cloak data-role-detail-overlay data-erp-modal role="dialog" aria-modal="true" aria-labelledby="role-detail-title"
         x-transition:enter="transition-opacity ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[110] flex flex-col justify-end lg:items-center lg:justify-center lg:p-6"
         @keydown.escape.window="if (roleDetailOpen) closeRoleDetail()">
        <button type="button" @click="closeRoleDetail()" aria-label="Tutup detail role" class="absolute inset-0 bg-black/60"></button>
        <section class="relative z-10 flex max-h-[90dvh] w-full flex-col overflow-hidden rounded-t-3xl bg-white shadow-2xl lg:max-w-lg lg:rounded-3xl">
            <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-5 py-5">
                <div class="flex min-w-0 items-start gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700"><i class="ph ph-shield-check text-xl"></i></div>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Detail Role</p>
                        <h2 id="role-detail-title" class="mt-1 truncate text-base font-bold text-gray-900">{{ $role->nm_role }}</h2>
                    </div>
                </div>
                <button type="button" @click="closeRoleDetail()" aria-label="Tutup" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-500"><i class="ph-bold ph-x"></i></button>
            </div>
            <div class="space-y-4 overflow-y-auto px-5 py-5">
                <div>
                    <h3 class="text-xs font-semibold text-gray-500">Keterangan</h3>
                    <p class="mt-1 text-sm text-gray-700">{{ $role->description ?: 'Belum ada keterangan.' }}</p>
                </div>
                <div class="flex items-center justify-between rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
                    <span class="text-sm font-medium text-gray-700">Hak akses aktif</span>
                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">{{ count($rolePermissions) }} izin</span>
                </div>
                <p x-show="roleActionError" x-cloak x-text="roleActionError" role="alert" class="text-sm text-red-600"></p>
            </div>
            <div class="flex shrink-0 items-center gap-3 border-t border-gray-100 px-5 pt-4 pb-[calc(1rem+env(safe-area-inset-bottom))]">
                <button type="button" @click="closeRoleDetail()" class="h-11 flex-1 rounded-xl border border-gray-200 text-sm font-semibold text-gray-600">Tutup</button>
                @if(check_access('role.destroy'))
                    @if(strtolower($role->nm_role) === 'administrator')
                        <button type="button" data-role-delete-action disabled aria-disabled="true" title="Role Administrator tidak dapat dihapus" class="flex h-11 cursor-not-allowed items-center justify-center gap-2 rounded-xl border border-gray-200 px-4 text-sm font-semibold text-gray-400"><i class="ph ph-trash"></i> Hapus</button>
                    @else
                        <button type="button" data-role-delete-action @click="deleteRole()" class="flex h-11 items-center justify-center gap-2 rounded-xl border border-red-200 px-4 text-sm font-semibold text-red-600"><i class="ph ph-trash"></i> Hapus</button>
                    @endif
                @endif
                @if(check_access('role-menu.update'))
                    <button type="button" data-role-permission-edit @click="roleDetailOpen = false; permissionOpen = true; roleActionError = ''" class="h-11 flex-1 rounded-xl bg-[#0b595b] text-sm font-semibold text-white">Edit</button>
                @endif
            </div>
        </section>
    </div>
</template>
