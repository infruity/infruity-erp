@props(['table', 'emptyTitle' => null, 'emptyNoun' => null, 'createUrl' => null, 'createLabel' => 'Tambah'])
<div class="erp-collection" data-erp-collection="{{ $table }}" @if ($emptyTitle) data-erp-reference="1" data-erp-empty-title="{{ $emptyTitle }}" data-erp-empty-noun="{{ $emptyNoun }}" data-erp-create-url="{{ $createUrl }}" data-erp-create-label="{{ $createLabel }}" @endif>
    <div class="erp-table-region">{{ $slot }}</div>
</div>
