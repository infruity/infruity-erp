@php
    $pageTitle = 'Cabang';
    $singular = 'Cabang';
    $routeBase = 'branch';
    $nameField = 'name';
    $subtitleFields = ['address','code'];
    $formFields = [['key'=>'province','label'=>'Provinsi','required'=>true,'type'=>'region'],['key'=>'city','label'=>'Kabupaten / Kota','required'=>true,'type'=>'region'],['key'=>'district','label'=>'Kecamatan','required'=>true,'type'=>'region'],['key'=>'village','label'=>'Kelurahan','required'=>true,'type'=>'region'],['key'=>'address','label'=>'Alamat Cabang','required'=>true,'type'=>'textarea'],['key'=>'map_link','label'=>'Link Google Maps','type'=>'url','required'=>false]];
    $pageMode = 'ajax';
@endphp
@include('master::partials.mobile-index')
