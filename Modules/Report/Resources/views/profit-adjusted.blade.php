@extends('template.root')

@section('title', 'Keuntungan Setelah Penyesuaian')
@section('page-title', 'Keuntungan Setelah Penyesuaian')
@section('page-breadcrumb')
    <a href="{{ route('reports.hub') }}" class="text-gray-400 transition hover:text-[#0b595b]">Laporan</a>
    <i class="ph ph-caret-right text-[0.55rem]"></i>
    <span>Keuntungan Setelah Penyesuaian</span>
@endsection

@section('content')
    @include('report::partials.mobile-detail', ['reportType' => 'adjusted'])
@endsection
