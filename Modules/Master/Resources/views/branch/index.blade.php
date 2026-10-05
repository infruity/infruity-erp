@php
    $pageTitle = 'Cabang';
    $singular = 'Cabang';
    $routeBase = 'branch';
    $nameField = 'name';
    $subtitleFields = ['address','code'];
    $formFields = [['key'=>'name','label'=>'Nama Cabang','required'=>true],['key'=>'code','label'=>'Kode Cabang','required'=>true],['key'=>'address','label'=>'Alamat Cabang','required'=>true,'type'=>'textarea']];
    $pageMode = 'ajax';
@endphp
@include('master::partials.mobile-index')
