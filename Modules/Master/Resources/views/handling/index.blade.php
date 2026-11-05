@extends('layouts.erp')
@section('title', 'Handling')
@section('page-title', 'Handling')
@section('page-breadcrumb')
    <span>Master</span><i class="ph ph-caret-right text-[0.55rem]"></i><span>Handling</span>
@endsection
@section('content')
    @include('master::partials.simple-crud', [
        'resource' => 'handling',
        'title' => 'Handling',
        'noun' => 'handling',
        'subtitle' => 'Kelola penanganan dan keterangan barang.',
        'icon' => 'package',
        'dataRoute' => 'handling-data',
        'columns' => [['Nama', 'name'], ['Deskripsi', 'description']],
        'fields' => [['name', 'Nama', true], ['description', 'Deskripsi']],
    ])
@endsection
