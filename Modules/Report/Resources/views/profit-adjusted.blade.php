@extends('template.root')

@section('title', 'Keuntungan Setelah Penyesuaian')
@section('page-title', 'Keuntungan Setelah Penyesuaian')
@section('page-breadcrumb')
    <span class="text-gray-400">Laporan</span>
    <i class="ph ph-caret-right text-[0.55rem]"></i>
    <span>Keuntungan Setelah Penyesuaian</span>
@endsection

@section('content')
    @include('report::partials.mobile-detail', ['reportType' => 'adjusted'])
@endsection
