@extends('template.root')
@section('title', 'Nota & Cetak')
@section('page-title', 'Nota & Cetak')

@section('content')
<link rel="stylesheet" href="{{ asset('assets/css/erp-nota-settings.css') }}?v={{ filemtime(public_path('assets/css/erp-nota-settings.css')) }}">
<x-erp.workspace title="Nota & Cetak" subtitle="Atur identitas toko dan informasi yang tampil pada struk." icon="ph-receipt">
    <x-slot:toolbar>
        <label class="nota-branch-picker" for="nota-branch">Cabang
            <select id="nota-branch" onchange="window.location.href='{{ url('setting-nota') }}?cabang_filter='+encodeURIComponent(this.value)">
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" @selected((string) $selected_branch === (string) $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </select>
        </label>
        <x-erp.button icon="eye" :href="url('setting-nota/view-receipt') }}?cabang_filter={{ $selected_branch">Pratinjau struk</x-erp.button>
    </x-slot:toolbar>
    <form id="add_product_form" class="nota-settings" action="{{ isset($data) ? url(Request::segment(1) . '/' . $data->id) : url(Request::segment(1)) }}" method="POST" enctype="multipart/form-data">
        @if (isset($data)) @method('PUT') @endif
        @csrf
        <input type="hidden" name="branch_id" value="{{ old('branch_id', $selected_branch) }}">
        <input type="hidden" name="header" value="{{ old('header', $data->header ?? '') }}">
        <input type="hidden" name="footer" value="{{ old('footer', $data->footer ?? '') }}">
        <aside class="nota-settings__identity" id="logo_upload_container">
            <h2>Logo toko</h2>
            <p>Logo yang akan digunakan pada struk.</p>
            <div class="nota-logo-preview">
                <img id="nota-logo-preview" src="{{ isset($data) && $data->logo ? asset('storage/' . $data->logo) : asset('assets/media/svg/files/blank-image.svg') }}" alt="Pratinjau logo toko">
            </div>
            <label class="erp-action" for="nota-logo"><i class="ph ph-upload-simple" aria-hidden="true"></i>Pilih logo</label>
            <input id="nota-logo" name="avatar" type="file" accept="image/png,image/jpeg" class="visually-hidden" aria-describedby="nota-logo-help">
            <button id="nota-logo-cancel" type="button" class="nota-text-action" hidden>Batalkan pilihan</button>
            <p id="nota-logo-help">Gunakan gambar PNG atau JPG.</p>
            <p id="nota-logo-filename" aria-live="polite"></p>
            @error('avatar')<p class="nota-field-error" role="alert">{{ $message }}</p>@enderror
        </aside>
        <div class="nota-settings__fields">
            @if ($errors->any() || session('error'))
                <div class="nota-form-error" role="alert" tabindex="-1">
                    <strong>Pengaturan belum tersimpan</strong>
                    <p>{{ session('error') ?: $errors->first() }}</p>
                </div>
            @endif
            @if (session('success'))<p class="nota-form-success" role="status">{{ session('success') }}</p>@endif
            <section class="nota-settings__section" aria-labelledby="nota-brand-title">
                <h2 id="nota-brand-title">Identitas toko</h2>
                <p>Informasi ini tampil pada bagian atas dan bawah struk.</p>
                <div class="nota-field-grid">
                    <div class="nota-field"><label for="nota-brand">Nama toko</label><input id="nota-brand" name="brand_name" type="text" value="{{ old('brand_name', $data->brand_name ?? '') }}" placeholder="Contoh: Infruity" autocomplete="organization">@error('brand_name')<p class="nota-field-error">{{ $message }}</p>@enderror</div>
                    <div class="nota-field"><label for="nota-phone">Nomor telepon</label><input id="nota-phone" name="brand_phone" type="tel" inputmode="tel" value="{{ old('brand_phone', $data->brand_phone ?? '') }}" placeholder="Contoh: 081234567890" autocomplete="tel">@error('brand_phone')<p class="nota-field-error">{{ $message }}</p>@enderror</div>
                    <div class="nota-field nota-field--full"><label for="nota-address">Alamat toko</label><input id="nota-address" name="brand_address" type="text" value="{{ old('brand_address', $data->brand_address ?? '') }}" placeholder="Jalan, nomor, dan kota" autocomplete="street-address">@error('brand_address')<p class="nota-field-error">{{ $message }}</p>@enderror</div>
                    <div class="nota-field nota-field--full"><label for="nota-social">Media sosial</label><input id="nota-social" name="brand_social_media" type="text" value="{{ old('brand_social_media', $data->brand_social_media ?? '') }}" placeholder="Contoh: @infruity">@error('brand_social_media')<p class="nota-field-error">{{ $message }}</p>@enderror</div>
                </div>
            </section>
            <section class="nota-settings__section" aria-labelledby="nota-message-title">
                <h2 id="nota-message-title">Pesan pada struk</h2>
                <div class="nota-field-grid">
                    <div class="nota-field nota-field--full"><label for="nota-greeting">Pesan sapaan</label><input id="nota-greeting" name="brand_greeting" type="text" value="{{ old('brand_greeting', $data->brand_greeting ?? '') }}" placeholder="Contoh: Terima kasih sudah berbelanja">@error('brand_greeting')<p class="nota-field-error">{{ $message }}</p>@enderror</div>
                    <div class="nota-field nota-field--full"><label for="nota-note">Catatan</label><textarea id="nota-note" name="note" rows="3" placeholder="Informasi tambahan untuk pelanggan">{{ old('note', $data->note ?? '') }}</textarea>@error('note')<p class="nota-field-error">{{ $message }}</p>@enderror</div>
                </div>
            </section>
            <section class="nota-settings__section" aria-labelledby="nota-display-title">
                <h2 id="nota-display-title">Informasi transaksi</h2>
                <p>Pilih informasi yang ingin ditampilkan pada struk.</p>
                @foreach (['is_using_cashier' => 'Nama kasir', 'is_using_customer' => 'Nama pelanggan', 'is_using_date' => 'Tanggal transaksi', 'is_using_invoice_number' => 'Nomor faktur'] as $field => $label)
                    <label class="nota-switch" for="nota-{{ $field }}"><span>{{ $label }}</span><input id="nota-{{ $field }}" name="{{ $field }}" type="checkbox" value="1" @checked(old($field, $data->{$field} ?? false))><span class="nota-switch__track" aria-hidden="true"></span></label>
                @endforeach
            </section>
        </div>
        <footer class="nota-settings__footer">
            <x-erp.button :href="url(Request::segment(1))" id="kt_ecommerce_edit_order_cancel">Batal</x-erp.button>
            @if (check_access(isset($data) ? 'setting-nota.update' : 'setting-nota.store'))
                <x-erp.button variant="primary" icon="check" type="submit" id="kt_ecommerce_edit_order_submit"><span>Simpan perubahan</span></x-erp.button>
            @endif
        </footer>
    </form>
</x-erp.workspace>
@endsection

@section('script')
<script>
(() => {
    const form = document.getElementById('add_product_form');
    const input = document.getElementById('nota-logo');
    const preview = document.getElementById('nota-logo-preview');
    const cancel = document.getElementById('nota-logo-cancel');
    const filename = document.getElementById('nota-logo-filename');
    const initial = preview.src;
    let previewUrl;
    const clearPreview = () => { if (previewUrl) URL.revokeObjectURL(previewUrl); previewUrl = null; };
    input.addEventListener('change', () => {
        clearPreview();
        const file = input.files[0];
        preview.src = file ? (previewUrl = URL.createObjectURL(file)) : initial;
        filename.textContent = file ? file.name : '';
        cancel.hidden = !file;
    });
    cancel.addEventListener('click', () => { input.value = ''; input.dispatchEvent(new Event('change')); input.focus(); });
    form.addEventListener('submit', () => {
        const button = form.querySelector('button[type="submit"]');
        if (button) { button.disabled = true; button.setAttribute('aria-busy', 'true'); button.querySelector('span').textContent = 'Menyimpan…'; }
    });
    const error = form.querySelector('.nota-form-error');
    if (error) error.focus();
})();
</script>
@endsection
