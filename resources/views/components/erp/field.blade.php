{{--
    One form field: label (with the required star), an optional hint, the control, help text and the validation error.
        <x-erp.field label="Nama Satuan" for="unit-name" required>
            <input id="unit-name" name="name" ...>
        </x-erp.field>
    The error is read from the validation bag by the field name (for="..."), or given with :error="...".
    hint="Opsional" sits at the right of the label; help="..." is a grey line under the control.
    The control keeps its own classes: its active state comes from erp-fields.css like every other field.
--}}
@props(['label' => null, 'for' => null, 'required' => false, 'hint' => null, 'help' => null, 'error' => null])
@php($message = $error ?? (($for && isset($errors)) ? $errors->first($for) : ''))
<div {{ $attributes->class(['erp-field']) }}>
    @if ($label || $hint)
        <div class="erp-field__head">
            @if ($label)<label{!! $for ? ' for="' . e($for) . '"' : '' !!} class="erp-field__label">{{ $label }}@if ($required)<span class="erp-field__req" aria-hidden="true">*</span>@endif</label>@endif
            @if ($hint)<span class="erp-field__hint">{{ $hint }}</span>@endif
        </div>
    @endif
    {{ $slot }}
    @if ($help)<p class="erp-field__help">{{ $help }}</p>@endif
    @if ($message)<p class="erp-field__error" role="alert">{{ $message }}</p>@endif
</div>
