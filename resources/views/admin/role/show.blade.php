<template x-teleport="body">
    <div x-show="roleDetailOpen" x-cloak data-role-detail-overlay data-erp-modal role="dialog" aria-modal="true" aria-labelledby="role-detail-title"
         x-transition:enter="transition-opacity ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[110] flex items-center justify-center px-4 py-4"
         @keydown.escape.window="if (roleDetailOpen) closeRoleDetail()">
        <button type="button" @click="closeRoleDetail()" aria-label="Tutup detail role" class="absolute inset-0 bg-black/60"></button>
        <section data-role-detail-panel class="relative z-10 flex max-h-[calc(100dvh-2rem)] w-full max-w-sm flex-col overflow-hidden rounded-3xl bg-[#e4edfc] shadow-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-[#d2def1] px-5 py-5">
                <div class="flex min-w-0 items-start gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-[#d2f0ec] text-[#0b7773]"><i class="ph ph-shield-check text-xl"></i></div>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-[#7184a0]">Detail Role</p>
                        <h2 id="role-detail-title" class="mt-1 truncate text-base font-bold text-[#233650]">{{ $role->nm_role }}</h2>
                    </div>
                </div>
                <button type="button" @click="closeRoleDetail()" aria-label="Tutup" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#d7e3f5] text-[#526985]"><i class="ph-bold ph-x"></i></button>
            </div>
            <div data-role-detail-summary class="space-y-4 overflow-y-auto bg-[#e4edfc] px-5 py-5">
                <div>
                    <h3 class="text-xs font-semibold text-[#647a98]">Keterangan</h3>
                    <p class="mt-1 text-sm text-[#354b68]">{{ $role->description ?: 'Belum ada keterangan.' }}</p>
                </div>
                <div class="flex items-center justify-between rounded-xl border border-[#d4e0f2] bg-[#dce8f8] px-4 py-3">
                    <span class="text-sm font-medium text-[#354b68]">Hak akses aktif</span>
                    <span class="rounded-full bg-[#d2f0ec] px-3 py-1 text-xs font-semibold text-[#0b7773]">{{ count($rolePermissions) }} izin</span>
                </div>
                <p x-show="roleActionError" x-cloak x-text="roleActionError" role="alert" class="text-sm text-red-600"></p>
            </div>
            <div class="flex shrink-0 border-t border-[#d2def1] bg-[#e4edfc] pb-[env(safe-area-inset-bottom)]">
                @if(check_access('role-menu.update'))
                    <button type="button" data-role-permission-edit @click="roleDetailOpen = false; permissionOpen = true; roleActionError = ''" class="flex h-12 flex-1 items-center justify-center gap-2 text-base font-semibold text-[#0b595b] transition-colors hover:bg-[#dce9f8]"><i class="ph-bold ph-pencil-simple"></i> Edit</button>
                @endif
                @if(check_access('role.destroy'))
                    @if(strtolower($role->nm_role) === 'administrator')
                        <button type="button" data-role-delete-action disabled aria-disabled="true" title="Role Administrator tidak dapat dihapus" @class(['flex h-12 flex-1 cursor-not-allowed items-center justify-center gap-2 border-l border-[#d2def1] text-base font-semibold text-red-400 opacity-70' => check_access('role-menu.update'), 'flex h-12 flex-1 cursor-not-allowed items-center justify-center gap-2 text-base font-semibold text-red-400 opacity-70' => !check_access('role-menu.update')])><i class="ph ph-trash"></i> Hapus</button>
                    @else
                        <button type="button" data-role-delete-action @click="deleteRole()" @class(['flex h-12 flex-1 items-center justify-center gap-2 border-l border-[#d2def1] text-base font-semibold text-red-500 transition-colors hover:bg-[#fce5eb]' => check_access('role-menu.update'), 'flex h-12 flex-1 items-center justify-center gap-2 text-base font-semibold text-red-500 transition-colors hover:bg-[#fce5eb]' => !check_access('role-menu.update')])><i class="ph ph-trash"></i> Hapus</button>
                    @endif
                @endif
                @unless(check_access('role-menu.update') || check_access('role.destroy'))
                    <button type="button" @click="closeRoleDetail()" class="h-12 w-full text-sm font-semibold text-[#536a87]">Tutup</button>
                @endunless
            </div>
        </section>
    </div>
</template>
