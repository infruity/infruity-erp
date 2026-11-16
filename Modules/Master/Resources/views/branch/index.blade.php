@php
    $pageTitle = 'Cabang';
    $singular = 'Cabang';
    $routeBase = 'branch';
    $nameField = 'name';
    $subtitleFields = ['address','code'];
    $formFields = [['key'=>'name','label'=>'Nama Cabang','required'=>true,'placeholder'=>'Masukkan nama cabang'],['key'=>'address','label'=>'Alamat Cabang','required'=>true,'type'=>'textarea','placeholder'=>'Masukkan alamat lengkap cabang'],['key'=>'map_link','label'=>'Link Google Maps','type'=>'url','required'=>false,'placeholder'=>'https://maps.google.com/...']];
    $pageMode = 'ajax';
@endphp
@include('master::partials.mobile-index')
