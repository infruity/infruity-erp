@php
    $pageTitle = 'Jabatan';
    $singular = 'Jabatan';
    $routeBase = 'position';
    $nameField = 'name';
    $subtitleFields = [];
    $formFields = [['key'=>'name','label'=>'Nama Posisi','required'=>true],['key'=>'department_id','label'=>'Departemen','required'=>true,'type'=>'select','options'=>$departments->map(fn($department)=>['value'=>$department->id,'label'=>$department->name])->values()],['key'=>'description','label'=>'Deskripsi','type'=>'textarea']];
    $formBodyClass = 'px-5 pt-3 pb-5 space-y-3';
    $pageMode = 'ajax';
@endphp
@include('master::partials.mobile-index')
