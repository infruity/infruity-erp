@extends('layouts.erp')
@section('title', 'Pengaturan EXP - CRM')
@section('page-title', 'Pengaturan EXP')
@section('content')
@include('crm::partials.assets')
<x-erp.workspace title="Pengaturan EXP" subtitle="Atur skala dan nilai untuk perhitungan EXP pelanggan." icon="ph-sliders-horizontal">
<form id="add_product_form" class="erp-crm-settings" action="{{ isset($data) ? url(Request::segment(1) . '/' . $data->id) : url(Request::segment(1)) }}" method="POST" enctype="multipart/form-data">
    @if (isset($data)) @method('PUT') @endif @csrf
    @if ($errors->any())<div class="erp-crm-form-error" role="alert">{{ $errors->first() }}</div>@endif
    @if (session('error'))<div class="erp-crm-form-error" role="alert">{{ session('error') }}</div>@endif
    <section class="erp-crm-section"><h2>Perhitungan EXP</h2><div class="erp-crm-fields">
        @php $skalaOptions = [1000, 100, 10, 1, 0.1, 0.01, 0.001]; $selectedSkala = old('skala', isset($data->skala) ? $data->skala : 1); @endphp
        <x-erp.field label="Skala" for="skala" class="erp-crm-field"><select name="skala" id="skala" class="erp-select">@foreach ($skalaOptions as $option)<option value="{{ $option }}" {{ $selectedSkala == $option ? 'selected' : '' }}>{{ $option }}</option>@endforeach</select></x-erp.field>
        <div class="erp-crm-field"><label for="exp-value" class="erp-label">Angka</label><input id="exp-value" type="number" inputmode="decimal" class="erp-input" name="value" value="{{ old('value', isset($data->value) ? $data->value : '1') }}"></div>
    </div></section>
    <div class="erp-crm-settings-actions"><x-erp.button :href="url(Request::segment(1))" id="kt_ecommerce_edit_order_cancel">Batal</x-erp.button><x-erp.button variant="primary" type="submit" id="kt_ecommerce_edit_order_submit"><span class="indicator-label">Simpan Perubahan</span><span class="indicator-progress">Menyimpan… <span class="erp-spinner" aria-hidden="true"></span></span></x-erp.button></div>
</form>
</x-erp.workspace>
@endsection

