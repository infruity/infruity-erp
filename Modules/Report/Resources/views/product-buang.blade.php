@extends('layouts.erp')

@section('title', 'Kerugian Barang Buang')
@section('page-title', 'Kerugian Barang Buang')
@section('page-breadcrumb')
    <a href="{{ route('reports.hub') }}" class="text-gray-300 transition hover:text-teal">Laporan</a>
    <i class="ph ph-caret-right text-[0.55rem]"></i>
    <span>Kerugian Barang Buang</span>
@endsection

@section('content')
    @include('report::partials.mobile-detail', ['reportType' => 'loss'])
@endsection
