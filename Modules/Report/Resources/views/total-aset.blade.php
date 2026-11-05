@extends('layouts.erp')

@section('title', 'Total Aset')
@section('page-title', 'Total Aset')
@section('page-breadcrumb')
    <a href="{{ route('reports.hub') }}" class="text-gray-300 transition hover:text-teal">Laporan</a>
    <i class="ph ph-caret-right text-[0.55rem]"></i>
    <span>Total Aset</span>
@endsection

@section('content')
    @include('report::partials.mobile-detail', ['reportType' => 'asset'])
@endsection
