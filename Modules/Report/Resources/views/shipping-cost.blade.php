@extends('template.root')

@section('title', 'Ongkos Kirim')
@section('page-title', 'Ongkos Kirim')
@section('page-breadcrumb')
    <span class="text-gray-400">Laporan</span>
    <i class="ph ph-caret-right text-[0.55rem]"></i>
    <span>Ongkos Kirim</span>
@endsection

@section('content')
    @include('report::partials.mobile-detail', ['reportType' => 'shipping'])
@endsection
