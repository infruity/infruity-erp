{{--
    The one "load more" control of the ERP (product list, customers, POS, transfer, ...). Pages differ only in the
    wrapper around it and in the label (the slot); the button itself is drawn here.

    Alpine pages pass the name of their loading flag so the button is disabled and the icon spins while it loads:
        <x-erp.load-more @click="loadMore()" loading="loading">Muat Pelanggan Lagi</x-erp.load-more>
    Sizes are in px and colours are hex on purpose: the legacy layout (template.root) sets a smaller root font size and
    its own grey palette, so rem sizes and gray-* made the button 19% smaller and bluer there than on the Tailwind pages.
    jQuery/DataTables pages just put it in their own wrapper and bind the click themselves:
        <x-erp.load-more>Muat Produk Lagi</x-erp.load-more>
--}}
@props(['loading' => null])
@php($busy = $loading !== null && $loading !== 'false')
<button type="button"
    @if ($busy) :disabled="{{ $loading }}" :aria-busy="{{ $loading }}" @endif
    {{ $attributes->class(['flex h-[44px] w-full items-center justify-center gap-[8px] rounded-[12px] border-2 border-dashed border-[#e5e7eb] bg-white text-[14px] font-semibold text-[#6b7280] transition-colors hover:border-[#34d399] hover:bg-[#ecfdf5] hover:text-[#047857] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0b595b] disabled:cursor-wait disabled:opacity-60']) }}>
    <i class="ph ph-arrow-circle-down text-[16px]" @if ($busy) :class="{{ $loading }} ? 'animate-spin motion-reduce:animate-none' : ''" @endif aria-hidden="true"></i>
    <span>{{ $slot->isEmpty() ? 'Muat Lebih Banyak' : $slot }}</span>
</button>
