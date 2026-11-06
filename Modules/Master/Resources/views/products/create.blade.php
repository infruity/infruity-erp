@extends('layouts.erp')

@section('title', isset($data) ? 'Edit Produk - Master' : 'Tambah Produk - Master')

@section('page-title', isset($data) ? 'Edit Produk' : 'Tambah Produk')
@section('page-breadcrumb')
    <span>Master</span>
    <i class="ph ph-caret-right text-[0.55rem]"></i>
    <a href="{{ route('products.index') }}">Produk</a>
    <i class="ph ph-caret-right text-[0.55rem]"></i>
    <span>{{ isset($data) ? 'Edit' : 'Tambah' }}</span>
@endsection

@section('content')
    <div class="bg-white rounded-2xl border border-gray-100">
    @include('master::products.partials.create-form')
    <div class="flex gap-3 border-t border-gray-100 p-5"><x-erp.button :href="route('products.index')" class="flex-1">Kembali</x-erp.button>@unless(request()->is('products/*/show'))<x-erp.button variant="primary" type="submit" form="add_product_form" id="product-create-submit" class="flex-1">{{ isset($data) ? 'Simpan Perubahan' : 'Tambah Produk' }}</x-erp.button>@endunless</div>
    </div>
@endsection

@section('script')
    @include('master::products.partials.create-script')
@endsection
