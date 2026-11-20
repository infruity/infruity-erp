@extends('template.root')

@section('title', 'Kerugian Barang Buang')
@section('page-title', 'Kerugian Barang Buang')
@section('page-breadcrumb')
    <span class="text-gray-400">Laporan</span>
    <i class="ph ph-caret-right text-[0.55rem]"></i>
    <span>Kerugian Barang Buang</span>
@endsection

@section('content')
    @include('report::partials.mobile-detail', ['reportType' => 'loss'])
@endsection
