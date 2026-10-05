@extends('layouts.erp-tailwind')

@section('title', (isset($data) ? 'Edit' : 'Tambah') . ' Pelanggan - Master')
@section('page-title', 'Pelanggan')
@section('page-breadcrumb')
    <span>Master</span><i class="ph ph-caret-right text-[0.55rem]"></i><span>Pelanggan</span>
@endsection
@section('dashboard-fullscreen', '1')

@section('content')
@php
    $editing = isset($data);
    $regionValues = [
        'province' => old('province', $data->province ?? ''),
        'city' => old('city', $data->city ?? ''),
        'district' => old('district', $data->district ?? ''),
        'village' => old('village', $data->village ?? ''),
    ];
    $regionNames = [
        'province' => $regionValues['province'] ? (\Modules\Master\Entities\Region::getProvince($regionValues['province'])->name ?? '') : '',
        'city' => $regionValues['city'] ? (\Modules\Master\Entities\Region::getCity($regionValues['city'])->name ?? '') : '',
        'district' => $regionValues['district'] ? (\Modules\Master\Entities\Region::getDistrict($regionValues['district'])->name ?? '') : '',
        'village' => $regionValues['village'] ? (\Modules\Master\Entities\Region::getVillage($regionValues['village'])->name ?? '') : '',
    ];
@endphp
<div class="flex-1 min-h-0 flex flex-col bg-white lg:rounded-2xl lg:border lg:border-gray-100 lg:shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between shrink-0">
        <div class="flex items-center gap-3">
            <a href="{{ route('customers.index') }}" aria-label="Kembali ke pelanggan" class="w-9 h-9 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center"><i class="ph-bold ph-arrow-left"></i></a>
            <div><h2 class="text-[15px] font-bold text-gray-900">{{ $editing ? 'Edit Pelanggan' : 'Tambah Pelanggan' }}</h2><p class="text-[12px] text-gray-400 mt-0.5">{{ $editing ? 'Perbarui data pelanggan' : 'Isi data pelanggan baru' }}</p></div>
        </div>
        @if($editing)<span class="text-[11px] text-gray-400 font-medium">{{ $data->code }}</span>@endif
    </div>

    <form id="customer-form" action="{{ $editing ? route('customers.update', $data->id) : route('customers.store') }}" method="POST" class="flex-1 min-h-0 flex flex-col">
        @csrf
        @if($editing) @method('PUT') @endif
        <div class="flex-1 min-h-0 overflow-y-auto bg-gray-50/60 px-5 py-5 pb-8 space-y-5">
            @if($errors->any())
                <div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700">Periksa kembali data yang ditandai di bawah.</div>
            @endif
            @if(session('error'))<div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700">{{ session('error') }}</div>@endif
            <div><label for="customer_name" class="text-xs font-semibold text-gray-600 mb-1.5 block">Nama Pelanggan <span class="text-red-400">*</span></label><input id="customer_name" name="customer_name" value="{{ old('customer_name', $data->name ?? '') }}" required maxlength="255" type="text" placeholder="Masukkan nama pelanggan" class="w-full h-11 bg-white border {{ $errors->has('customer_name') ? 'border-red-400' : 'border-gray-200' }} rounded-xl px-4 text-sm outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">@error('customer_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
            <div><label for="phone" class="text-xs font-semibold text-gray-600 mb-1.5 block">No WhatsApp</label><input id="phone" name="phone" value="{{ old('phone', $data->whatsapp ?? '') }}" type="tel" inputmode="tel" placeholder="Contoh: 081234567890" class="w-full h-11 bg-white border {{ $errors->has('phone') ? 'border-red-400' : 'border-gray-200' }} rounded-xl px-4 text-sm outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">@error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
            <div><label for="email" class="text-xs font-semibold text-gray-600 mb-1.5 block">E-mail</label><input id="email" name="email" value="{{ old('email', $data->email ?? '') }}" type="email" placeholder="contoh@email.com" class="w-full h-11 bg-white border {{ $errors->has('email') ? 'border-red-400' : 'border-gray-200' }} rounded-xl px-4 text-sm outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">@error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
            <div><label for="gender" class="text-xs font-semibold text-gray-600 mb-1.5 block">Jenis Kelamin</label><select id="gender" name="gender" class="w-full h-11 bg-white border border-gray-200 rounded-xl px-4 text-sm outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"><option value="">Pilih jenis kelamin</option><option value="male" @selected(old('gender', $data->gender ?? '') === 'male')>Pria</option><option value="female" @selected(old('gender', $data->gender ?? '') === 'female')>Wanita</option></select>@error('gender')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
            <div><label for="birth_of_date" class="text-xs font-semibold text-gray-600 mb-1.5 block">Tanggal Lahir</label><input id="birth_of_date" name="birth_of_date" value="{{ old('birth_of_date', $data->birth_of_date ?? '') }}" type="date" class="w-full h-11 bg-white border border-gray-200 rounded-xl px-4 text-sm outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">@error('birth_of_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>

            <div class="border-t border-gray-200 pt-5"><h3 class="text-sm font-bold text-gray-800">Alamat Pelanggan</h3><p class="text-xs text-gray-400 mt-0.5">Isi lokasi dan alamat bila tersedia</p></div>
            @foreach([
                ['key'=>'province','label'=>'Provinsi'],
                ['key'=>'city','label'=>'Kabupaten / Kota'],
                ['key'=>'district','label'=>'Kecamatan'],
                ['key'=>'village','label'=>'Kelurahan'],
            ] as $region)
                <div class="relative" data-region-picker="{{ $region['key'] }}">
                    <label for="{{ $region['key'] }}-search" class="text-xs font-semibold text-gray-600 mb-1.5 block">{{ $region['label'] }}</label>
                    <div class="relative"><input id="{{ $region['key'] }}-search" type="text" autocomplete="off" role="combobox" aria-expanded="false" aria-controls="{{ $region['key'] }}-options" value="{{ $regionNames[$region['key']] }}" placeholder="Cari {{ strtolower($region['label']) }}..." class="w-full h-11 bg-white border {{ $errors->has($region['key']) ? 'border-red-400' : 'border-gray-200' }} rounded-xl px-4 pr-10 text-sm outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"><i class="ph ph-caret-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i></div>
                    <input type="hidden" name="{{ $region['key'] }}" value="{{ $regionValues[$region['key']] }}">
                    <div id="{{ $region['key'] }}-options" role="listbox" hidden class="absolute z-30 mt-1 w-full max-h-48 overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-lg"></div>
                    @error($region['key'])<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            @endforeach
            <div><label for="address" class="text-xs font-semibold text-gray-600 mb-1.5 block">Tempat Tinggal / Alamat Lengkap</label><textarea id="address" name="address" rows="3" placeholder="Jalan, RT/RW, dan detail alamat" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 resize-none">{{ old('address', $data->address ?? '') }}</textarea>@error('address')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
        </div>
        <div class="shrink-0 bg-white border-t border-gray-100 px-5 pt-4 pb-[calc(1rem+env(safe-area-inset-bottom))] flex items-center gap-3"><a href="{{ route('customers.index') }}" class="flex-1 h-11 rounded-xl border border-gray-200 text-sm font-semibold text-gray-600 flex items-center justify-center">Batal</a><button type="submit" class="flex-1 h-11 rounded-xl bg-[#0b595b] text-white text-sm font-semibold" data-submit>{{ $editing ? 'Simpan Perubahan' : 'Tambah Pelanggan' }}</button></div>
    </form>
</div>
@endsection

@include('master::customer.js-create')
