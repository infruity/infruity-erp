@extends('template.root')
@section('title', 'Nota & Cetak')
@section('page-title', 'Nota & Cetak')

@section('content')
@php
    $notaFlag = static fn (string $key, bool $fallback = true): bool => (bool) old($key, $data->{$key} ?? $fallback);
    $notaConfig = [
        'address' => $notaFlag('is_using_address'),
        'invoice' => $notaFlag('is_using_invoice_number'),
        'date' => $notaFlag('is_using_date'),
        'cashier' => $notaFlag('is_using_cashier'),
        'payment' => $notaFlag('is_using_payment_method'),
        'phone' => $notaFlag('is_using_phone'),
        'social' => $notaFlag('is_using_social'),
        'paper' => in_array((string) old('paper_width', $data->paper_width ?? '80'), ['58', '80'], true) ? (string) old('paper_width', $data->paper_width ?? '80') : '80',
        'brandName' => (string) old('brand_name', $data->brand_name ?? ''),
        'brandAddress' => (string) old('brand_address', $data->brand_address ?? ''),
        'brandPhone' => preg_replace('/\D+/', '', (string) old('brand_phone', $data->brand_phone ?? '')),
        'brandSocial' => (string) old('brand_social_media', $data->brand_social_media ?? ''),
        'greeting' => (string) old('brand_greeting', $data->brand_greeting ?? ''),
        'note' => (string) old('note', $data->note ?? ''),
    ];
    $notaToggles = [
        ['key' => 'invoice', 'name' => 'is_using_invoice_number', 'label' => 'No. Invoice', 'icon' => 'ph-receipt'],
        ['key' => 'date', 'name' => 'is_using_date', 'label' => 'Waktu', 'icon' => 'ph-clock'],
        ['key' => 'cashier', 'name' => 'is_using_cashier', 'label' => 'Kasir', 'icon' => 'ph-user'],
        ['key' => 'payment', 'name' => 'is_using_payment_method', 'label' => 'Metode Pembayaran', 'icon' => 'ph-credit-card'],
    ];
@endphp
<link rel="stylesheet" href="{{ asset('assets/css/nota-settings-form.css') }}?v={{ filemtime(public_path('assets/css/nota-settings-form.css')) }}">
<form id="add_product_form" class="nota-form" x-data="notaSettingsForm(@js($notaConfig))" x-init="init()" action="{{ isset($data) ? url(Request::segment(1) . '/' . $data->id) : url(Request::segment(1)) }}" method="POST" @submit="saving = true">
    @if (isset($data)) @method('PUT') @endif
    @csrf
    <input type="hidden" name="branch_id" value="{{ old('branch_id', $selected_branch) }}">
    <input type="hidden" name="header" value="{{ old('header', $data->header ?? '') }}">
    <input type="hidden" name="footer" value="{{ old('footer', $data->footer ?? '') }}">
    <input type="hidden" name="is_using_customer" value="{{ old('is_using_customer', ($data->is_using_customer ?? true) ? 1 : 0) }}">
    <input type="hidden" name="brand_name" :value="s.brandName">
    <input type="hidden" name="brand_greeting" :value="s.greeting">
    <input type="hidden" name="note" :value="s.note">
    <input type="hidden" name="is_using_address" :value="s.address ? 1 : 0">
    <input type="hidden" name="is_using_phone" :value="s.phone ? 1 : 0">
    <input type="hidden" name="is_using_social" :value="s.social ? 1 : 0">
    <input type="hidden" name="paper_width" :value="paper">
    @foreach ($notaToggles as $toggle)
        <input type="hidden" name="{{ $toggle['name'] }}" :value="s.{{ $toggle['key'] }} ? 1 : 0">
    @endforeach
    <input type="hidden" name="brand_address" :value="s.brandAddress">
    <input type="hidden" name="brand_phone" :value="s.brandPhone">
    <input type="hidden" name="brand_social_media" :value="s.brandSocial">

    <div class="nota-form__tabs" role="tablist" aria-label="Bagian pengaturan nota">
        <button type="button" role="tab" :aria-selected="tab === 'settings'" :class="{ 'is-active': tab === 'settings' }" @click="tab = 'settings'">Pengaturan</button>
        <button type="button" role="tab" :aria-selected="tab === 'preview'" :class="{ 'is-active': tab === 'preview' }" @click="tab = 'preview'">Preview</button>
    </div>

    @if ($errors->any() || session('error'))
        <p class="nota-form__error" role="alert">{{ session('error') ?: $errors->first() }}</p>
    @endif
    @if (session('success'))<p class="nota-form__success" role="status">{{ session('success') }}</p>@endif

    <div class="nota-form__body">
        <section class="nota-card" x-show="tab === 'settings'" aria-label="Pengaturan nota">
            <div class="nota-card__branch">
                <label class="nota-label" for="nota-branch"><i class="ph-bold ph-storefront" aria-hidden="true"></i><span>Cabang</span><em aria-hidden="true">*</em></label>
                <div class="nota-select">
                    <select id="nota-branch" onchange="window.location.href='{{ url('setting-nota') }}?cabang_filter='+encodeURIComponent(this.value)">
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) $selected_branch === (string) $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                    <i class="ph-bold ph-caret-down" aria-hidden="true"></i>
                </div>
            </div>

            <div class="nota-card__scroll">
                <div class="nota-box">
                    <div class="nota-box__head">
                        <span class="nota-box__title"><i class="ph-bold ph-map-pin" aria-hidden="true"></i>Alamat Store</span>
                        <button type="button" class="nota-switch" role="switch" :aria-checked="s.address" :class="{ 'is-on': s.address }" @click="s.address = !s.address" aria-label="Tampilkan alamat store"><span></span></button>
                    </div>
                    <div class="nota-box__field" x-show="s.address">
                        <textarea x-model="s.brandAddress" rows="2" maxlength="255" placeholder="Masukkan alamat store..." aria-label="Alamat store"></textarea>
                    </div>
                </div>

                <div class="nota-box nota-box--list">
                    @foreach ($notaToggles as $toggle)
                        <div class="nota-row">
                            <span class="nota-row__label"><i class="ph-bold {{ $toggle['icon'] }}" aria-hidden="true"></i>{{ $toggle['label'] }}</span>
                            <button type="button" class="nota-switch" role="switch" :aria-checked="s.{{ $toggle['key'] }}" :class="{ 'is-on': s.{{ $toggle['key'] }} }" @click="s.{{ $toggle['key'] }} = !s.{{ $toggle['key'] }}" aria-label="Tampilkan {{ strtolower($toggle['label']) }}"><span></span></button>
                        </div>
                    @endforeach
                    <div class="nota-row">
                        <span class="nota-row__label"><i class="ph-bold ph-printer" aria-hidden="true"></i>Ukuran Thermal</span>
                        <div class="nota-paper nota-paper--row" role="group" aria-label="Ukuran kertas thermal">
                            <button type="button" :class="{ 'is-active': paper === '80' }" @click="paper = '80'">80mm</button>
                            <button type="button" :class="{ 'is-active': paper === '58' }" @click="paper = '58'">58mm</button>
                        </div>
                    </div>
                    <div class="nota-row nota-row--stack">
                        <div class="nota-row__top">
                            <span class="nota-row__label"><i class="ph-bold ph-phone" aria-hidden="true"></i>Nomor WhatsApp</span>
                            <button type="button" class="nota-switch" role="switch" :aria-checked="s.phone" :class="{ 'is-on': s.phone }" @click="s.phone = !s.phone" aria-label="Tampilkan nomor WhatsApp"><span></span></button>
                        </div>
                        <div class="nota-affix" x-show="s.phone"><input type="tel" inputmode="numeric" pattern="[0-9]*" x-model="s.brandPhone" @input="s.brandPhone = s.brandPhone.replace(/\D+/g, '')" maxlength="15" placeholder="81234567890" aria-label="Nomor WhatsApp"></div>
                    </div>
                    <div class="nota-row nota-row--stack">
                        <div class="nota-row__top">
                            <span class="nota-row__label"><i class="ph-bold ph-instagram-logo" aria-hidden="true"></i>Instagram</span>
                            <button type="button" class="nota-switch" role="switch" :aria-checked="s.social" :class="{ 'is-on': s.social }" @click="s.social = !s.social" aria-label="Tampilkan Instagram"><span></span></button>
                        </div>
                        <div class="nota-affix" x-show="s.social"><input type="text" x-model="s.brandSocial" maxlength="60" placeholder="infruity" aria-label="Instagram"></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="nota-card nota-card--preview" x-show="tab === 'preview'" x-cloak aria-label="Preview struk">
            <div class="nota-preview__head">
                <div class="nota-preview__tools">
                    <div class="nota-paper" role="group" aria-label="Lebar kertas">
                        <button type="button" :class="{ 'is-active': paper === '80' }" @click="paper = '80'">80mm</button>
                        <button type="button" :class="{ 'is-active': paper === '58' }" @click="paper = '58'">58mm</button>
                    </div>
                    <button type="button" class="nota-test" @click="testPrint()"><i class="ph-bold ph-printer" aria-hidden="true"></i><span>Uji Cetak</span></button>
                </div>
            </div>
            <div class="nota-preview__stage">
                <article class="nota-receipt" id="nota-receipt-preview" :class="'is-' + paper">
                    <div class="nota-brand">
                        <h1 x-text="s.brandName || 'in!fruity'"></h1>
                        <p x-show="s.address && s.brandAddress" x-text="s.brandAddress"></p>
                    </div>
                    <div class="nota-meta">
                        <div class="nota-row" x-show="s.invoice"><span>No. Invoice</span><span>INV20261112142</span></div>
                        <div class="nota-row" x-show="s.date"><span>Waktu</span><span>12 Nov 2026, 12:22</span></div>
                        <div class="nota-row" x-show="s.cashier"><span>Kasir</span><span>{{ auth()->user()->nm_user ?? 'Kasir' }}</span></div>
                        <div class="nota-row" x-show="s.payment"><span>Metode</span><span>Tunai</span></div>
                    </div>
                    <div class="nota-divider"></div>
                    <div class="nota-status">### Sudah Lunas ###</div>
                    <div class="nota-divider"></div>
                    <div class="nota-items">
                        <div class="nota-item"><h4>Anggur Merah China</h4><div class="nota-row"><span>1 Kg x Rp 72.000</span><span>Rp 72.000</span></div></div>
                        <div class="nota-item"><h4>Alpukat Grade AB</h4><div class="nota-row"><span>1 Kg x Rp 35.000</span><span>Rp 35.000</span></div><div class="nota-disc"><span>Diskon (10%)</span><span>-Rp 3.500</span></div></div>
                    </div>
                    <div class="nota-divider"></div>
                    <div class="nota-sums">
                        <div class="nota-row"><span>Subtotal</span><span>Rp 103.500</span></div>
                        <div class="nota-row is-disc"><span>Diskon Transaksi</span><span>-Rp 5.000</span></div>
                        <div class="nota-row"><span>Ongkos Kirim</span><span>Rp 10.000</span></div>
                    </div>
                    <div class="nota-divider"></div>
                    <div class="nota-total">
                        <div class="nota-row is-grand"><span>Total (2 Produk)</span><span>Rp 108.500</span></div>
                        <div class="nota-row"><span>Bayar</span><span>Rp 108.500</span></div>
                        <div class="nota-row"><span>Kembali</span><span>-</span></div>
                    </div>
                    <div class="nota-divider"></div>
                    <div class="nota-foot">
                        <h3 x-text="s.greeting || 'Terima kasih atas kunjungan Anda'"></h3>
                        <p x-text="s.note || 'Kritik dan saran hubungi layanan konsumen'"></p>
                        <p x-show="(s.phone && s.brandPhone) || (s.social && s.brandSocial)"><span x-show="s.phone && s.brandPhone">WA : <span x-text="s.brandPhone"></span></span> <span x-show="s.phone && s.brandPhone && s.social && s.brandSocial">/</span> <span x-show="s.social && s.brandSocial">IG : <span x-text="s.brandSocial.replace(/^@/, '')"></span></span></p>
                    </div>
                </article>
            </div>
        </section>
    </div>

    <footer class="nota-form__footer">
        <a href="{{ url(Request::segment(1)) }}" class="nota-icon" aria-label="Kembali ke daftar cabang" title="Kembali"><i class="ph-bold ph-arrow-left" aria-hidden="true"></i></a>
        @if (check_access(isset($data) ? 'setting-nota.update' : 'setting-nota.store'))
            <button type="submit" class="nota-icon" :disabled="saving" aria-label="Simpan pengaturan" title="Simpan"><i class="ph-bold ph-bookmark-simple" aria-hidden="true"></i></button>
            <button type="submit" id="kt_ecommerce_edit_order_submit" class="nota-primary" :disabled="saving"><span x-text="saving ? 'Menyimpan...' : 'Simpan Pengaturan'">Simpan Pengaturan</span></button>
        @endif
    </footer>
</form>
@endsection

@section('script')
<script>
    function notaSettingsForm(config) {
        return {
            s: config,
            tab: 'settings',
            paper: config.paper,
            saving: false,
            init() {},
            testPrint() {
                const sheet = document.getElementById('nota-receipt-preview');
                if (!sheet) return;
                const frame = document.createElement('iframe');
                frame.style.cssText = 'position:fixed;width:0;height:0;border:0;visibility:hidden';
                document.body.appendChild(frame);
                const doc = frame.contentDocument;
                doc.open();
                doc.write('<!doctype html><html><head><meta charset="utf-8"><link rel="stylesheet" href="' + new URL('{{ asset('assets/css/nota-settings-form.css') }}', location.href).href + '"><style>@page{size:' + this.paper + 'mm auto;margin:0}body{margin:0;font:12px/1.4 sans-serif}.nota-receipt{width:' + this.paper + 'mm !important;padding:6px !important;border:0 !important;box-shadow:none !important;border-radius:0 !important}</style></head><body>' + sheet.outerHTML + '</body></html>');
                doc.close();
                setTimeout(() => { frame.contentWindow.focus(); frame.contentWindow.print(); setTimeout(() => frame.remove(), 1500); }, 150);
            },
        };
    }
</script>
@endsection
