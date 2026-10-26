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
<div class="flex-1 min-h-0 overflow-y-auto bg-gray-50/60 px-5 py-5 pb-8 space-y-5">
    @if($errors->any())
        <div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700">Periksa kembali data yang ditandai di bawah.</div>
    @endif
    @if(session('error'))<div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700">{{ session('error') }}</div>@endif
    <div><label for="customer_name" class="text-xs font-semibold text-gray-600 mb-1.5 block">Nama Pelanggan <span class="text-red-400">*</span></label><input id="customer_name" name="customer_name" value="{{ old('customer_name', $data->name ?? '') }}" required maxlength="255" type="text" placeholder="Masukkan nama pelanggan" class="w-full h-11 bg-white border {{ $errors->has('customer_name') ? 'border-red-400' : 'border-gray-200' }} rounded-xl px-4 text-sm outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">@error('customer_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
    <div><label for="phone" class="text-xs font-semibold text-gray-600 mb-1.5 block">No WhatsApp</label><input id="phone" name="phone" value="{{ old('phone', $data->whatsapp ?? '') }}" type="tel" inputmode="tel" placeholder="Contoh: 081234567890" class="w-full h-11 bg-white border {{ $errors->has('phone') ? 'border-red-400' : 'border-gray-200' }} rounded-xl px-4 text-sm outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">@error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
    <div><label for="email" class="text-xs font-semibold text-gray-600 mb-1.5 block">E-mail</label><input id="email" name="email" value="{{ old('email', $data->email ?? '') }}" type="email" placeholder="contoh@email.com" class="w-full h-11 bg-white border {{ $errors->has('email') ? 'border-red-400' : 'border-gray-200' }} rounded-xl px-4 text-sm outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">@error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
    <div><label for="gender" class="text-xs font-semibold text-gray-600 mb-1.5 block">Jenis Kelamin</label><select id="gender" name="gender" class="w-full h-11 bg-white border border-gray-200 rounded-xl px-4 text-sm outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"><option value="">Pilih jenis kelamin</option><option value="male" @selected(old('gender', $data->gender ?? '') === 'male')>Pria</option><option value="female" @selected(old('gender', $data->gender ?? '') === 'female')>Wanita</option></select>@error('gender')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
    <div class="grid grid-cols-2 gap-4">
        <div x-data="{ pickerOpen: false, pickerSearch: '', pickerDirection: 'down', selected: @js(old('birth_place', $data->birth_place ?? '')), places: @js($birthPlaces ?? []) }" class="relative" @click.outside="pickerOpen = false">
            <label class="text-xs font-semibold text-gray-600 mb-1.5 block">Tempat Lahir</label>
            <input type="hidden" name="birth_place" x-model="selected">
            <button type="button" @click="let rect = $event.currentTarget.getBoundingClientRect(); pickerDirection = (window.innerHeight - rect.bottom) < 250 ? 'up' : 'down'; pickerOpen = !pickerOpen" class="w-full h-11 bg-white border border-gray-200 rounded-xl px-4 flex items-center justify-between text-sm transition-all focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500" :class="!selected ? 'text-gray-400' : 'text-gray-900'" aria-haspopup="listbox" :aria-expanded="pickerOpen"><span class="truncate" x-text="selected || 'Pilih Tempat Lahir'"></span><i class="ph-bold ph-caret-down text-gray-400 shrink-0 transition-transform" :class="pickerOpen ? 'rotate-180' : ''"></i></button>
            <div x-show="pickerOpen" x-cloak x-transition class="absolute z-30 w-full flex flex-col bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden" :class="pickerDirection === 'up' ? 'bottom-full mb-1' : 'mt-1'">
                <div class="relative border-b border-gray-100 shrink-0"><i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i><input type="search" x-model="pickerSearch" placeholder="Cari tempat lahir..." class="w-full bg-transparent pl-9 pr-3 py-3 text-xs outline-none focus:bg-gray-50 transition-colors"></div>
                <div class="max-h-32 overflow-y-auto overscroll-contain" role="listbox"><template x-for="place in places.filter(name => name.toLowerCase().includes(pickerSearch.toLowerCase())).slice(0, 40)" :key="place"><button type="button" role="option" @click="selected = place; pickerOpen = false; pickerSearch = ''" class="w-full px-3 py-2.5 border-b border-gray-100 text-left text-xs font-medium text-gray-700 hover:bg-gray-50 last:border-b-0" :aria-selected="selected === place" x-text="place"></button></template><div x-show="places.filter(name => name.toLowerCase().includes(pickerSearch.toLowerCase())).length === 0" class="text-center py-4 text-xs text-gray-400">Tidak ditemukan</div></div>
            </div>
            @error('birth_place')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div><label for="birth_of_date" class="text-xs font-semibold text-gray-600 mb-1.5 block">Tanggal Lahir</label><input id="birth_of_date" name="birth_of_date" value="{{ old('birth_of_date', $data->birth_of_date ?? '') }}" type="date" class="w-full h-11 bg-white border border-gray-200 rounded-xl px-4 text-sm outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">@error('birth_of_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
    </div>

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
