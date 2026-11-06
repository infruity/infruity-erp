@extends('layouts.erp')
@section('title', 'Jadwal Poin - CRM')
@section('page-title', 'Jadwal Poin')
@section('content')
@include('crm::partials.assets')
<x-erp.workspace title="Jadwal Poin" subtitle="Atur periode reset poin dan skala perhitungan EXP." icon="ph-calendar-clock">
<form id="add_product_form" class="erp-crm-settings" action="{{ isset($data) ? url(Request::segment(1) . '/' . $data->id) : url(Request::segment(1)) }}" method="POST" enctype="multipart/form-data">
    @if (isset($data)) @method('PUT') @endif @csrf
    @if ($errors->any())<div class="erp-crm-form-error" role="alert">{{ $errors->first() }}</div>@endif
    @if (session('error'))<div class="erp-crm-form-error" role="alert">{{ session('error') }}</div>@endif
    <div class="erp-crm-settings-grid">
        <section class="erp-crm-section"><h2>Periode reset</h2><p>Tentukan kapan reset otomatis dimulai dan berakhir.</p><div class="erp-crm-fields">
            <div class="erp-crm-field"><label for="start_date" class="erp-label">Tanggal mulai</label><input type="date" class="erp-input" name="start_date" id="start_date" value="{{ old('start_date', isset($data->start_date) ? $data->start_date : '') }}"></div>
            <div class="erp-crm-field"><label for="frequency" class="erp-label">Frekuensi</label><select name="frequency" id="frequency" class="erp-select">@foreach ($frequencies as $item)<option value="{{ $item->value }}" {{ old('frequency', isset($data->frequency) ? $data->frequency : '') == $item->value ? 'selected' : '' }}>{{ $item->name }}</option>@endforeach</select></div>
            <div class="erp-crm-field"><label for="break" class="erp-label">Selesai setelah</label><select name="break" id="break" class="erp-select"><option value="1" {{ old('break', isset($data->break) ? $data->break : '') == 1 ? 'selected' : '' }}>Tanggal</option><option value="2" {{ old('break', isset($data->break) ? $data->break : '') == 2 ? 'selected' : '' }}>Selamanya</option></select></div>
            <div class="erp-crm-field"><label for="end_date" class="erp-label">Tanggal selesai</label><input type="date" class="erp-input" name="end_date" id="end_date" value="{{ old('end_date', isset($data->end_date) ? $data->end_date : '') }}"></div>
        </div></section>
        <section class="erp-crm-section"><h2>Perhitungan EXP</h2><p>Pilih skala dan angka perhitungan yang berlaku.</p><div class="erp-crm-fields">
            @php $skalaOptions = [1000, 100, 10, 1, 0.1, 0.01, 0.001]; $selectedSkala = old('skala', isset($exp->skala) ? $exp->skala : 1); @endphp
            <div class="erp-crm-field"><label for="skala" class="erp-label">Skala</label><select name="skala" id="skala" class="erp-select">@foreach ($skalaOptions as $option)<option value="{{ $option }}" {{ $selectedSkala == $option ? 'selected' : '' }}>{{ $option }}</option>@endforeach</select></div>
            <div class="erp-crm-field"><label for="exp-value" class="erp-label">Angka</label><input id="exp-value" type="number" inputmode="decimal" class="erp-input" name="value" value="{{ old('value', isset($exp->value) ? $exp->value : '1') }}"></div>
        </div></section>
    </div>
    <div class="erp-crm-settings-actions"><x-erp.button :href="url(Request::segment(1))" id="kt_ecommerce_edit_order_cancel">Batal</x-erp.button><x-erp.button variant="primary" type="submit" id="kt_ecommerce_edit_order_submit"><span class="indicator-label">Simpan Perubahan</span><span class="indicator-progress">Menyimpan… <span class="erp-spinner" aria-hidden="true"></span></span></x-erp.button></div>
</form>
</x-erp.workspace>
@endsection
@section('script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const startDateInput = document.getElementById('start_date');
            const frequencySelect = document.getElementById('frequency');
            const endDateInput = document.getElementById('end_date');
            const breakSelect = document.getElementById('break');

            function updateEndDate() {
                console.log('updateEndDate');
                const startDateStr = startDateInput.value;
                const frequencyMonths = parseInt(frequencySelect.value);
                const breakVal = breakSelect.value;

                if (breakVal === '2') {
                    // endDateInput.value = '';
                    endDateInput.readOnly = true;
                    // return;
                } else {
                    endDateInput.readOnly = false;
                }

                console.log(startDateStr, frequencyMonths, breakVal);
                if (!startDateStr || isNaN(frequencyMonths)) {
                    endDateInput.value = '';
                    return;
                }

                const startDate = new Date(startDateStr);
                const endDate = new Date(startDate);
                endDate.setMonth(endDate.getMonth() + frequencyMonths);
                console.log(endDate);

                // Format YYYY-MM-DD
                const yyyy = endDate.getFullYear();
                const mm = String(endDate.getMonth() + 1).padStart(2, '0');
                const dd = String(endDate.getDate()).padStart(2, '0');
                endDateInput.value = `${yyyy}-${mm}-${dd}`;
            }

            // Event listeners
            startDateInput.addEventListener('change', updateEndDate);
            frequencySelect.addEventListener('change', updateEndDate);
            breakSelect.addEventListener('change', updateEndDate);

            // Jalankan sekali di awal jika nilai sudah terisi
            updateEndDate();
        });
    </script>
@endsection
