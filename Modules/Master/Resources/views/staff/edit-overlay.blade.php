<template x-teleport="body">
    <div x-data="staffEditOverlay(@js($initialOpen ?? false), @js($data->id ?? null), @js($returnUrl ?? route('staff.index')), @js(route('staff.index')))"
         x-init="init()"
         @staff-edit-open.window="openStaffEdit($event.detail)"
         @popstate.window="syncFromUrl()"
         @keydown.escape.window="if (drawerOpen) closeStaffEdit()"
         x-show="drawerOpen" x-cloak data-staff-edit-overlay data-erp-modal role="dialog" aria-modal="true" aria-labelledby="staff-edit-title"
         class="fixed inset-0 z-[120] flex flex-col justify-end overflow-hidden lg:flex-row lg:justify-end">
        <div x-show="drawerOpen" x-transition:enter="transition-opacity ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="closeStaffEdit()" class="absolute inset-0 bg-black/70"></div>

        <section x-show="drawerOpen" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="translate-y-full lg:translate-y-0 lg:translate-x-full" x-transition:enter-end="translate-y-0 lg:translate-x-0" x-transition:leave="transition ease-in duration-300 transform" x-transition:leave-start="translate-y-0 lg:translate-x-0" x-transition:leave-end="translate-y-full lg:translate-y-0 lg:translate-x-full" class="relative z-10 mx-auto flex h-auto max-h-[90dvh] w-full flex-col overflow-hidden rounded-t-3xl border-t border-gray-100 bg-white shadow-2xl md:max-w-lg lg:mx-0 lg:h-full lg:max-h-full lg:max-w-sm lg:rounded-none lg:border-l lg:border-t-0">
            <div class="flex w-full shrink-0 justify-center pt-3 pb-1 lg:hidden" @click="closeStaffEdit()">
                <div class="h-1.5 w-16 rounded-full bg-gray-300"></div>
            </div>
            <div class="flex shrink-0 items-center justify-between border-b border-gray-100 bg-white px-5 py-4">
                <div>
                    <h2 id="staff-edit-title" class="text-[15px] font-bold text-gray-900">Edit Karyawan</h2>
                    <p class="mt-0.5 text-[12px] text-gray-400">Perbarui data jabatan</p>
                </div>
                <button type="button" @click="closeStaffEdit()" aria-label="Tutup form edit karyawan" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-500 transition-colors hover:bg-gray-200"><i class="ph-bold ph-x text-base"></i></button>
            </div>

            @if ($data)
                @include('master::staff.form-fields', ['insideOverlay' => true, 'cancelAction' => 'closeStaffEdit()'])
            @else
                <div x-show="loading && !formHtml" class="flex flex-1 items-center justify-center bg-gray-50/60 text-sm text-gray-500">Memuat data karyawan...</div>
                <p x-show="loadError" x-cloak role="alert" class="mx-5 mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700" x-text="loadError"></p>
                <div x-show="formHtml" x-cloak x-html="formHtml" class="flex min-h-0 flex-1 flex-col"></div>
            @endif
        </section>
    </div>
</template>

@include('master::staff.js-edit')
