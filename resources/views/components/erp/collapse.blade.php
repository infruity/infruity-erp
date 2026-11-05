@props(['open' => false, 'title' => null])
@php($uid = 'erp-collapse-'.\Illuminate\Support\Str::random(6))
<div x-data="{ open: @js((bool) $open) }" {{ $attributes }}>
    <button type="button" @click="open = !open" :aria-expanded="open.toString()" aria-controls="{{ $uid }}"
            class="flex w-full items-center justify-between gap-3 text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal/30">
        <span class="min-w-0 flex-1">{{ $trigger ?? $title }}</span>
        <i class="ph ph-caret-down shrink-0 transition-transform" :class="open && 'rotate-180'" aria-hidden="true"></i>
    </button>
    <div id="{{ $uid }}" x-show="open" x-collapse x-cloak>{{ $slot }}</div>
</div>
