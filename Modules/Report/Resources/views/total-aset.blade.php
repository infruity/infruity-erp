@extends('template.root')

@section('title', 'Total Aset')
@section('page-title', 'Total Aset')
@section('page-breadcrumb')
    <span class="text-gray-400">Laporan</span>
    <i class="ph ph-caret-right text-[0.55rem]"></i>
    <span>Total Aset</span>
@endsection

@section('content')
    @include('report::partials.mobile-detail', ['reportType' => 'asset'])
@endsection
