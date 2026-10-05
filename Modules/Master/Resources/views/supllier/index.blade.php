@php
    $pageTitle = 'Pemasok';
    $singular = 'Pemasok';
    $routeBase = 'supplier';
    $nameField = 'name';
    $subtitleFields = ['pic_whatsapp','pic_name'];
    $formFields = [['key'=>'name','label'=>'Nama Pemasok','required'=>true],['key'=>'pic_name','label'=>'Kontak PIC','required'=>true],['key'=>'pic_whatsapp','label'=>'No Whatsapp','required'=>true,'type'=>'tel'],['key'=>'email','label'=>'E-mail','type'=>'email'],['key'=>'address','label'=>'Alamat Pemasok','type'=>'textarea']];
    $pageMode = 'ajax';
@endphp
@include('master::partials.mobile-index')
