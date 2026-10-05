@php
    $pageTitle = 'Metode Pembayaran';
    $singular = 'Metode';
    $routeBase = 'payment-method';
    $nameField = 'name';
    $subtitleFields = ['code'];
    $formFields = [['key'=>'name','label'=>'Nama Metode Pembayaran','required'=>true]];
    $pageMode = 'ajax';
@endphp
@include('master::partials.mobile-index')
