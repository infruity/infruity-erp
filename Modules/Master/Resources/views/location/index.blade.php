@extends('layouts.erp')
@section('title', 'Lokasi')
@section('page-title', 'Lokasi')
@section('page-breadcrumb')
    <span>Master</span><i class="ph ph-caret-right text-[0.55rem]"></i><span>Lokasi</span>
@endsection
@section('content')
    @include('master::partials.simple-crud', [
        'resource' => 'location',
        'title' => 'Lokasi',
        'noun' => 'lokasi',
        'subtitle' => 'Kelola lokasi penyimpanan dan alamatnya.',
        'icon' => 'map-pin',
        'dataRoute' => 'location-data',
        'columns' => [['Nama Lokasi', 'name'], ['Alamat', 'address']],
        'fields' => [['name', 'Nama Lokasi', true], ['address', 'Alamat', true], ['description', 'Deskripsi']],
    ])
@endsection
