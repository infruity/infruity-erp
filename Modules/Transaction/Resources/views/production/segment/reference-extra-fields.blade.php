<div class="production-payload-fields" hidden aria-hidden="true">
    <input type="date" class="form-control" id="production_date" name="production_date"
        value="{{ old('production_date') ?? date('Y-m-d') }}">
    <input type="text" inputmode="numeric" name="sell_price" id="sell_price" class="form-control format-number"
        placeholder="Masukkan Harga Jual" x-model="sellPrice"
        value="{{ isset($edit_sell_price) ? number_format($edit_sell_price, 0, ',', '.') : '' }}" />
    <input type="hidden" name="production_number" id="production_number" class="form-control"
        value="{{ $production_number }}">
    <input type="hidden" name="id" id="production_id" class="form-control"
        value="{{ isset($id) ? $id : '' }}">
    <input type="text" inputmode="numeric" name="service_cost" id="service_cost" class="form-control format-number"
        placeholder="Masukkan Biaya Jasa" x-model="serviceCost"
        value="{{ isset($edit_service_cost) ? number_format($edit_service_cost, 0, ',', '.') : '' }}" />
</div>
