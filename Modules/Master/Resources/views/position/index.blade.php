@php
    $pageTitle = 'Jabatan';
    $singular = 'Jabatan';
    $routeBase = 'position';
    $nameField = 'name';
    $subtitleFields = [];
    $formFields = [['key'=>'name','label'=>'Nama Posisi','required'=>true,'placeholder'=>'Misal: Kru Toko']];
    $formBodyClass = 'px-5 pb-6 pt-5 flex flex-col gap-5';
    $pageMode = 'ajax';
@endphp
@include('master::partials.mobile-index')
