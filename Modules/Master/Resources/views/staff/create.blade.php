@extends('layouts.erp-tailwind')

@section('title', 'Edit Karyawan - Master')
@section('page-title', 'Karyawan')
@section('page-breadcrumb')
    <span>Master</span><i class="ph ph-caret-right text-[0.55rem]"></i><span>Karyawan</span>
@endsection
@section('dashboard-fullscreen', '1')

@section('content')
<div class="flex min-h-0 flex-1 flex-col overflow-hidden bg-white lg:rounded-2xl lg:border lg:border-gray-100 lg:shadow-sm">
    <div class="flex shrink-0 items-center justify-between border-b border-gray-100 px-5 py-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('staff.index') }}" aria-label="Kembali ke karyawan" class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-gray-600"><i class="ph-bold ph-arrow-left"></i></a>
            <div><h2 class="text-[15px] font-bold text-gray-900">Edit Karyawan</h2><p class="mt-0.5 text-[12px] text-gray-400">Perbarui data karyawan</p></div>
        </div>
    </div>
    @include('master::staff.form-fields', ['insideOverlay' => false])
</div>
@endsection

@include('master::staff.js-create')
