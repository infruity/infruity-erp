@php
    $pageTitle = 'Kurir';
    $singular = 'Kurir';
    $routeBase = 'kurir';
    $nameField = 'name';
    $subtitleFields = ['type'];
    $formEditSubtitle = 'Perbarui data metode';
    $formCreateSubtitle = 'Isi data metode baru';
    $formBodyClass = 'px-5 pt-5 pb-6 space-y-5';
    $formFields = [
        [
            'key' => 'type',
            'label' => 'Tipe Kurir',
            'required' => true,
            'type' => 'select',
            'presentation' => 'picker',
            'placeholder' => 'Pilih Tipe Kurir',
            'options' => [
                ['value' => 'internal', 'label' => 'Internal'],
                ['value' => 'external', 'label' => 'Eksternal'],
            ],
        ],
        [
            'key' => 'name',
            'label' => 'Nama Kurir',
            'visibleWhen' => ['key' => 'type', 'value' => 'external'],
            'requiredWhen' => ['key' => 'type', 'value' => 'external'],
            'placeholder' => 'Misal: Gojek, Grab, Maxim',
        ],
        [
            'key' => 'staff_id',
            'label' => 'Pilih Karyawan',
            'type' => 'select',
            'presentation' => 'picker',
            'searchable' => true,
            'placeholder' => 'Pilih karyawan...',
            'options' => $staffOptions->map(fn ($staff) => [
                'value' => $staff->id,
                'label' => $staff->name,
            ])->values(),
            'visibleWhen' => ['key' => 'type', 'value' => 'internal'],
            'requiredWhen' => ['key' => 'type', 'value' => 'internal'],
        ],
        [
            'key' => 'description',
            'label' => 'Deskripsi',
            'type' => 'textarea',
        ],
    ];
    $pageMode = 'ajax';
@endphp
@include('master::partials.mobile-index')
