@php
    $pageTitle = 'Akun Pengguna';
    $singular = 'Akun';
    $routeBase = 'account';
    $nameField = 'name';
    $subtitleFields = ['code'];
    $formFields = [['key'=>'name','label'=>'Nama Akun','required'=>true],['key'=>'code','label'=>'Kode Akun','required'=>true]];
    $pageMode = 'ajax';
@endphp
@include('master::partials.mobile-index')
