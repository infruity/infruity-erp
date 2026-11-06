@props(['label', 'for' => null, 'required' => false, 'help' => null, 'error' => null])
{{-- Label + satu kontrol (slot) + bantuan/galat. Kontrolnya tetap elemen asli (<input class="erp-input">, <select class="erp-select">, <textarea class="erp-textarea">). --}}
<div {{ $attributes }}>
    <label @if($for) for="{{ $for }}" @endif @class(['erp-label', 'erp-label--required' => $required])>{{ $label }}</label>
    {{ $slot }}
    @if($help)<p class="erp-help" @if($for) id="{{ $for }}-help" @endif>{{ $help }}</p>@endif
    @if($error)<p class="erp-error" role="alert" @if($for) id="{{ $for }}-error" @endif>{{ $error }}</p>@endif
</div>
