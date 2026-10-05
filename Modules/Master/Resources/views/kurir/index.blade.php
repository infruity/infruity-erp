@php
    $pageTitle = 'Kurir';
    $singular = 'Kurir';
    $routeBase = 'kurir';
    $nameField = 'name';
    $subtitleFields = ['type','description'];
    $formFields = [['key'=>'type','label'=>'Tipe Kurir','required'=>true,'type'=>'select','options'=>[['value'=>'internal','label'=>'Internal'],['value'=>'external','label'=>'Eksternal']]],['key'=>'name','label'=>'Nama Kurir','required'=>true],['key'=>'staff_id','label'=>'Pilih Karyawan','type'=>'select','options'=>$staffOptions->map(fn($staff)=>['value'=>$staff->id,'label'=>$staff->name])->values(),'visibleWhen'=>['key'=>'type','value'=>'internal'],'requiredWhen'=>['key'=>'type','value'=>'internal']],['key'=>'description','label'=>'Deskripsi','type'=>'textarea']];
    $pageMode = 'ajax';
@endphp
@include('master::partials.mobile-index')
