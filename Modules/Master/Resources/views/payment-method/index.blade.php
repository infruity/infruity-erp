@php
    $pageTitle = 'Metode Pembayaran';
    $singular = 'Metode';
    $detailTitle = 'Detail Metode Pembayaran';
    $emptyLabel = 'Metode pembayaran';
    $loadMoreNoun = 'Metode';
    $routeBase = 'payment-method';
    $nameField = 'name';
    $subtitleFields = [];
    $formFields = [['key'=>'name','label'=>'Nama Metode Pembayaran','required'=>true,'placeholder'=>'Masukkan nama metode']];
    $pageMode = 'ajax';
@endphp
@include('master::partials.mobile-index')
