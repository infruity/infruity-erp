@props(['name'])
<div x-show="active === @js($name)" x-cloak role="tabpanel" id="panel-{{ $name }}" aria-labelledby="tab-{{ $name }}" tabindex="0" {{ $attributes->class(['erp-tabs__panel']) }}>{{ $slot }}</div>
