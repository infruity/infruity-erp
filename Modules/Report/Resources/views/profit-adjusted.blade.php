@extends('layouts.erp')

@section('title', 'Keuntungan Setelah Penyesuaian')
@section('page-title', 'Keuntungan Setelah Penyesuaian')
@section('page-breadcrumb')
    <a href="{{ route('reports.hub') }}" class="text-gray-300 transition hover:text-teal">Laporan</a>
    <i class="ph ph-caret-right text-[0.55rem]"></i>
    <span>Keuntungan Setelah Penyesuaian</span>
@endsection

@section('content')
    @include('report::partials.mobile-detail', ['reportType' => 'adjusted'])
@endsection
