<template x-teleport="body">
    <div x-show="customerCreateOpen" x-cloak data-erp-modal role="dialog" aria-modal="true" aria-label="Tambah Pelanggan" class="fixed inset-0 z-[120] flex flex-col justify-end lg:flex-row lg:justify-end" @keydown.escape.window="closeCustomerCreate()">
        <div x-show="customerCreateOpen" x-transition:enter="transition-opacity ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="absolute inset-0 bg-black/70" @click="closeCustomerCreate()"></div>
        <div x-show="customerCreateOpen" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="translate-y-full lg:translate-y-0 lg:translate-x-full" x-transition:enter-end="translate-y-0 lg:translate-x-0" x-transition:leave="transition ease-in duration-200 transform" x-transition:leave-start="translate-y-0 lg:translate-x-0" x-transition:leave-end="translate-y-full lg:translate-y-0 lg:translate-x-full" class="relative z-10 w-full md:max-w-lg lg:max-w-sm mx-auto lg:mx-0 bg-white shadow-2xl flex flex-col max-h-[90dvh] lg:max-h-full lg:h-full rounded-t-3xl lg:rounded-none overflow-hidden">
            <div class="flex justify-center pt-3 pb-1 lg:hidden shrink-0"><div class="w-16 h-1.5 bg-gray-300 rounded-full"></div></div>
            <div class="shrink-0 px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-white">
                <div><h3 class="text-[15px] font-bold text-gray-900">Tambah Pelanggan</h3><p class="text-[12px] text-gray-400 mt-0.5">Isi data pelanggan baru</p></div>
                <button type="button" @click="closeCustomerCreate()" aria-label="Tutup" class="w-9 h-9 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-500 transition-colors shrink-0"><i class="ph-bold ph-x text-base"></i></button>
            </div>
            <form id="customer-form" action="{{ route('customers.store') }}" method="POST" class="flex-1 min-h-0 flex flex-col">
                @csrf
                @include('master::customer.form-fields', ['data' => null])
                <div class="shrink-0 bg-white border-t border-gray-100 px-5 pt-4 pb-[calc(1rem+env(safe-area-inset-bottom))] flex items-center gap-3"><button type="button" @click="closeCustomerCreate()" class="flex-1 h-11 rounded-xl border border-gray-200 text-sm font-semibold text-gray-600">Batal</button><button type="submit" class="flex-1 h-11 rounded-xl bg-[#0b595b] text-white text-sm font-semibold" data-submit>Tambah Pelanggan</button></div>
            </form>
        </div>
    </div>
</template>
@include('master::customer.js-create')
