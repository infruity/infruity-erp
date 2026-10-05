@php
    $pageTitle = 'Jabatan';
    $singular = 'Jabatan';
    $routeBase = 'position';
    $nameField = 'name';
    $subtitleFields = ['department.name','code'];
    $formFields = [['key'=>'name','label'=>'Nama Posisi','required'=>true],['key'=>'department_id','label'=>'Departemen','required'=>true,'type'=>'select','options'=>$departments->map(fn($department)=>['value'=>$department->id,'label'=>$department->name])->values()],['key'=>'description','label'=>'Deskripsi','type'=>'textarea']];
    $pageMode = 'ajax';
@endphp
@include('master::partials.mobile-index')
