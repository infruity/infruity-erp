@php
    $pageTitle = 'Karyawan';
    $singular = 'Karyawan';
    $routeBase = 'staff';
    $nameField = 'name';
    $subtitleFields = ['nickname','contact'];
    $formFields = [['key'=>'name','label'=>'Nama Karyawan'],['key'=>'nickname','label'=>'Nama Panggilan'],['key'=>'contact','label'=>'No Kontak'],['key'=>'email','label'=>'E-mail'],['key'=>'position.name','label'=>'Jabatan'],['key'=>'department.name','label'=>'Departemen'],['key'=>'status','label'=>'Status']];
    $pageMode = 'page';
@endphp
@include('master::partials.mobile-index')
