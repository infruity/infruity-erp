@extends('layouts.erp-tailwind')

@section('title', (isset($data) ? 'Edit' : 'Tambah') . ' Pelanggan - Master')
@section('page-title', 'Pelanggan')
@section('page-breadcrumb')
    <span>Master</span><i class="ph ph-caret-right text-[0.55rem]"></i><span>Pelanggan</span>
@endsection
@section('dashboard-fullscreen', '1')

@section('content')
@php($editing = isset($data))
<div class="flex-1 min-h-0 flex flex-col bg-white lg:rounded-2xl lg:border lg:border-gray-100 lg:shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between shrink-0">
        <div class="flex items-center gap-3">
            <a href="{{ route('customers.index') }}" aria-label="Kembali ke pelanggan" class="w-9 h-9 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center"><i class="ph-bold ph-arrow-left"></i></a>
            <div><h2 class="text-[15px] font-bold text-gray-900">{{ $editing ? 'Edit Pelanggan' : 'Tambah Pelanggan' }}</h2><p class="text-[12px] text-gray-400 mt-0.5">{{ $editing ? 'Perbarui data pelanggan' : 'Isi data pelanggan baru' }}</p></div>
        </div>
        @if($editing)<span class="text-[11px] text-gray-400 font-medium">{{ $data->code }}</span>@endif
    </div>

    <form id="customer-form" action="{{ $editing ? route('customers.update', $data->id) : route('customers.store') }}" method="POST" class="flex-1 min-h-0 flex flex-col">
        @csrf
        @if($editing) @method('PUT') @endif
        @include('master::customer.form-fields')
        <div class="shrink-0 bg-white border-t border-gray-100 px-5 pt-4 pb-[calc(1rem+env(safe-area-inset-bottom))] flex items-center gap-3"><a href="{{ route('customers.index') }}" class="flex-1 h-11 rounded-xl border border-gray-200 text-sm font-semibold text-gray-600 flex items-center justify-center">Batal</a><button type="submit" class="flex-1 h-11 rounded-xl bg-[#0b595b] text-white text-sm font-semibold" data-submit>{{ $editing ? 'Simpan Perubahan' : 'Tambah Pelanggan' }}</button></div>
    </form>
</div>
@endsection

@include('master::customer.js-create')
