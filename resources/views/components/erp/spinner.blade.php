{{-- The one loading spinner (replaces Bootstrap's .spinner-border). size: sm | md | lg. Colour follows the text colour. --}}
@props(['size' => 'md', 'label' => 'Memuat'])
@php($px = ['sm' => 'h-4 w-4 border-2', 'md' => 'h-5 w-5 border-2', 'lg' => 'h-8 w-8 border-[3px]'][$size] ?? 'h-5 w-5 border-2')
<span {{ $attributes->class(['inline-block shrink-0 animate-spin rounded-full border-current border-r-transparent text-[#0b595b]', $px]) }} role="status" aria-label="{{ $label }}"></span>
