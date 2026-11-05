@extends('layouts.erp')
@section('title', 'Jenis Stok Keluar')
@section('page-title', 'Jenis Stok Keluar')
@section('page-breadcrumb')
    <span>Inventaris</span><i class="ph ph-caret-right text-[0.55rem]"></i><span>Jenis Stok Keluar</span>
@endsection
@section('content')
    @include('master::partials.simple-crud', [
        'resource' => 'stock-out-type',
        'title' => 'Jenis Stok Keluar',
        'noun' => 'jenis stok keluar',
        'subtitle' => 'Kelola kategori transaksi stok keluar.',
        'icon' => 'tag',
        'dataRoute' => 'stock-out-type.data',
        'columns' => [['Nama', 'name']],
        'fields' => [['type_name', 'Nama Type', true, 'name']],
    ])
@endsection
