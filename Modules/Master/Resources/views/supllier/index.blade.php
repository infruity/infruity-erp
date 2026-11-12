@php
    $pageTitle = 'Pemasok';
    $singular = 'Pemasok';
    $routeBase = 'supplier';
    $nameField = 'name';
    $subtitleFields = ['pic_whatsapp'];
    $formFields = [['key'=>'name','label'=>'Nama Pemasok','required'=>true],['key'=>'pic_name','label'=>'Kontak PIC','required'=>true,'placeholder'=>'Nama orang yang bisa dihubungi (PIC)'],['key'=>'pic_whatsapp','label'=>'No Whatsapp','required'=>true,'type'=>'tel','placeholder'=>'Contoh: 6281234567890'],['key'=>'email','label'=>'E-mail','type'=>'email','placeholder'=>'contoh@email.com'],['key'=>'province','label'=>'Provinsi','type'=>'region'],['key'=>'city','label'=>'Kabupaten / Kota','type'=>'region'],['key'=>'district','label'=>'Kecamatan','type'=>'region'],['key'=>'village','label'=>'Kelurahan','type'=>'region'],['key'=>'address','label'=>'Alamat Pemasok','type'=>'textarea','placeholder'=>'Jalan, RT/RW, dsb']];
    $pageMode = 'ajax';
@endphp
@include('master::partials.mobile-index')
