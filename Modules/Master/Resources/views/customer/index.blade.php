@php
    $pageTitle = 'Pelanggan';
    $singular = 'Pelanggan';
    $routeBase = 'customers';
    $nameField = 'name';
    $subtitleFields = ['whatsapp'];
    $formFields = [['key'=>'name','label'=>'Nama Pelanggan'],['key'=>'code','label'=>'Kode'],['key'=>'whatsapp','label'=>'No Whatsapp'],['key'=>'email','label'=>'E-mail'],['key'=>'gender','label'=>'Jenis Kelamin','type'=>'select','options'=>[['value'=>'male','label'=>'Laki-laki'],['value'=>'female','label'=>'Perempuan']]],['key'=>'birth_of_date','label'=>'Tanggal Lahir'],['key'=>'address','label'=>'Tempat Tinggal / Alamat Lengkap']];
    $pageMode = 'server';
@endphp
@include('master::partials.mobile-index')
