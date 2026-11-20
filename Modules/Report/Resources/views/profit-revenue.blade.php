@extends('template.root')

@section('title', 'Keuntungan Penjualan')
@section('page-title', 'Keuntungan Penjualan')
@section('page-breadcrumb')
    <span class="text-gray-400">Laporan</span>
    <i class="ph ph-caret-right text-[0.55rem]"></i>
    <span>Keuntungan Penjualan</span>
@endsection

@section('content')
    @include('report::partials.mobile-detail', ['reportType' => 'profit'])
@endsection
