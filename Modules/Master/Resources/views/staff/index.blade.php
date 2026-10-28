@php
    $pageTitle = 'Karyawan';
    $singular = 'Karyawan';
    $routeBase = 'staff';
    $nameField = 'name';
    $subtitleFields = ['position_label'];
    $trailingField = 'tenure_label';
    $formFields = [['key'=>'name','label'=>'Nama Karyawan'],['key'=>'date_in','label'=>'Tanggal Masuk'],['key'=>'position_label','label'=>'Jabatan'],['key'=>'branch_label','label'=>'Cabang']];
    $pageMode = 'page';
@endphp
@include('master::partials.mobile-index')
