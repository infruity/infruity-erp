@extends('layouts.erp-tailwind')

@section('title', isset($data) ? 'Edit Produk - Master' : 'Tambah Produk - Master')

@section('page-title', isset($data) ? 'Edit Produk' : 'Tambah Produk')
@section('page-breadcrumb')
    <span>Master</span>
    <i class="ph ph-caret-right text-[0.55rem]"></i>
    <a href="{{ route('products.index') }}">Produk</a>
    <i class="ph ph-caret-right text-[0.55rem]"></i>
    <span>{{ isset($data) ? 'Edit' : 'Tambah' }}</span>
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <style>
        .select2-container { max-width: 100%; }
        .select2-container .select2-selection--single { min-height: 42px; border: 1px solid #e5e7eb; border-radius: 0.75rem; background: #f9fafb; }
        .select2-container .select2-selection--single .select2-selection__rendered { line-height: 40px; padding-left: 1rem; font-size: 0.875rem; }
        .select2-container .select2-selection--single .select2-selection__arrow { height: 40px; }
        .select2-dropdown { border-color: #e5e7eb; }
    </style>
@endpush

@section('content')
    <div class="bg-white rounded-2xl border border-gray-100">
    @include('master::products.partials.create-form')
    <div class="flex gap-3 border-t border-gray-100 p-5"><a href="{{ route('products.index') }}" class="erp-button flex-1">Kembali</a>@unless(request()->is('products/*/show'))<button type="submit" form="add_product_form" id="product-create-submit" class="erp-button erp-button--primary flex-1">{{ isset($data) ? 'Simpan Perubahan' : 'Tambah Produk' }}</button>@endunless</div>
    </div>
@endsection

@section('script')
    @include('master::products.partials.create-script')
@endsection
