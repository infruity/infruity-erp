@php
    $data = null;
    $insideOverlay = true;
@endphp

<div x-data="staffCreateOverlay(@js($initialCreateOpen ?? false))"
     x-init="init()"
     @staff-create-open.window="openDrawer()"
     @popstate.window="syncFromUrl()"
     @keydown.escape.window="if (drawerOpen) closeDrawer()"
     x-show="drawerOpen" x-cloak data-staff-create-overlay data-erp-modal role="dialog" aria-modal="true" aria-label="Tambah Karyawan"
     class="fixed inset-0 z-[120] flex flex-col justify-end overflow-hidden lg:flex-row lg:justify-end">
    <div x-show="drawerOpen" x-transition:enter="transition-opacity ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="closeDrawer()" class="absolute inset-0 bg-black/70"></div>

    <section x-show="drawerOpen" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="translate-y-full lg:translate-y-0 lg:translate-x-full" x-transition:enter-end="translate-y-0 lg:translate-x-0" x-transition:leave="transition ease-in duration-300 transform" x-transition:leave-start="translate-y-0 lg:translate-x-0" x-transition:leave-end="translate-y-full lg:translate-y-0 lg:translate-x-full" class="relative z-10 mx-auto flex h-auto max-h-[90dvh] w-full flex-col overflow-hidden rounded-t-3xl border-t border-gray-100 bg-white shadow-2xl md:max-w-lg lg:mx-0 lg:h-full lg:max-h-full lg:max-w-sm lg:rounded-none lg:border-l lg:border-t-0">
        <div class="flex w-full shrink-0 justify-center pt-3 pb-1 lg:hidden" @click="closeDrawer()">
            <div class="h-1.5 w-16 rounded-full bg-gray-300"></div>
        </div>
        <div class="flex shrink-0 items-center justify-between border-b border-gray-100 bg-white px-5 py-4">
            <div>
                <h2 class="text-[15px] font-bold text-gray-900">Tambah Karyawan</h2>
                <p class="mt-0.5 text-[12px] text-gray-400">Isi data karyawan baru</p>
            </div>
            <button type="button" @click="closeDrawer()" aria-label="Tutup form karyawan" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-500 transition-colors hover:bg-gray-200"><i class="ph-bold ph-x text-base"></i></button>
        </div>
        @include('master::staff.form-fields', ['insideOverlay' => true])
    </section>
</div>

@include('master::staff.js-create')
