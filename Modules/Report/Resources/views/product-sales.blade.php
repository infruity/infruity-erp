@extends('layouts.erp')

@section('title', 'Pendapatan Penjualan')
@section('page-title', 'Pendapatan Penjualan')
@section('page-breadcrumb')
    <a href="{{ route('reports.hub') }}" class="text-gray-300 transition hover:text-teal">Laporan</a>
    <i class="ph ph-caret-right text-[0.55rem]"></i>
    <span>Pendapatan Penjualan</span>
@endsection

@section('content')
    @include('report::partials.mobile-detail', ['reportType' => 'sales'])
@endsection
