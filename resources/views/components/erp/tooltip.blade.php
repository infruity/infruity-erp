@props(['text'])
@php($uid = 'erp-tip-'.\Illuminate\Support\Str::random(6))
<span x-data="{ show: false }" x-ref="host" class="inline-flex" aria-describedby="{{ $uid }}"
      @mouseenter="show = true" @mouseleave="show = false" @focusin="show = true" @focusout="show = false"
      @keydown.escape.window="show = false" {{ $attributes }}>
    {{ $slot }}
    <template x-teleport="body">
        <div x-show="show" x-cloak x-anchor.top.offset.6="$refs.host" id="{{ $uid }}" role="tooltip" class="erp-tooltip">{{ $text }}</div>
    </template>
</span>
