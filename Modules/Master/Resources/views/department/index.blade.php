@extends('layouts.erp')
@section('title', 'Departemen')
@section('page-title', 'Departemen')
@section('page-breadcrumb')
    <span>Master</span><i class="ph ph-caret-right text-[0.55rem]"></i><span>Departemen</span>
@endsection
@section('content')
    @include('master::partials.simple-crud', [
        'resource' => 'department',
        'title' => 'Departemen',
        'noun' => 'departemen',
        'subtitle' => 'Kelola departemen dan penempatan tim.',
        'icon' => 'users-three',
        'dataRoute' => 'department-data',
        'columns' => [['Nama', 'name'], ['Kode', 'code'], ['Deskripsi', 'description']],
        'fields' => [['name', 'Name', true], ['description', 'Description']],
    ])
@endsection
