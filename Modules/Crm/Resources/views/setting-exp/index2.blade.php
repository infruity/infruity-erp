@extends('template.root')
@section('title', 'Pengaturan EXP - CRM')
@section('page-title', 'Pengaturan EXP')
@section('content')
@include('crm::partials.assets')
<x-erp.workspace title="Pengaturan EXP" subtitle="Atur skala dan nilai untuk perhitungan EXP pelanggan." icon="ph-sliders-horizontal">
<form id="add_product_form" class="erp-crm-settings" action="{{ isset($data) ? url(Request::segment(1) . '/' . $data->id) : url(Request::segment(1)) }}" method="POST" enctype="multipart/form-data" data-kt-redirect="">
    @if (isset($data)) @method('PUT') @endif @csrf
    @if ($errors->any())<div class="erp-crm-form-error" role="alert">{{ $errors->first() }}</div>@endif
    @if (session('error'))<div class="erp-crm-form-error" role="alert">{{ session('error') }}</div>@endif
    <section class="erp-crm-section"><h2>Perhitungan EXP</h2><div class="erp-crm-fields">
        @php $skalaOptions = [1000, 100, 10, 1, 0.1, 0.01, 0.001]; $selectedSkala = old('skala', isset($data->skala) ? $data->skala : 1); @endphp
        <div class="erp-crm-field"><label for="skala">Skala</label><select name="skala" id="skala" class="form-select">@foreach ($skalaOptions as $option)<option value="{{ $option }}" {{ $selectedSkala == $option ? 'selected' : '' }}>{{ $option }}</option>@endforeach</select></div>
        <div class="erp-crm-field"><label for="exp-value">Angka</label><input id="exp-value" type="number" inputmode="decimal" class="form-control" name="value" value="{{ old('value', isset($data->value) ? $data->value : '1') }}"></div>
    </div></section>
    <div class="erp-crm-settings-actions"><a href="{{ url(Request::segment(1)) }}" id="kt_ecommerce_edit_order_cancel" class="erp-action">Batal</a><button type="submit" id="kt_ecommerce_edit_order_submit" class="erp-action erp-action--primary"><span class="indicator-label">Simpan Perubahan</span><span class="indicator-progress">Menyimpan… <span class="spinner-border spinner-border-sm" aria-hidden="true"></span></span></button></div>
</form>
</x-erp.workspace>
@endsection
@section('script')<script>$(function () { $('#skala').select2({ width: '100%', placeholder: 'Pilih skala' }); });</script>@endsection
