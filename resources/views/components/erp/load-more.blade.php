@props(['loading' => 'false'])

<button type="button"
    :disabled="{{ $loading }}"
    :aria-busy="{{ $loading }}"
    {{ $attributes->class(['flex h-11 w-full items-center justify-center gap-2 rounded-xl border-2 border-dashed border-gray-200 bg-white text-sm font-semibold text-gray-500 transition-colors hover:border-emerald-400 hover:bg-emerald-50 hover:text-emerald-700 disabled:cursor-wait disabled:opacity-60']) }}>
    <i class="ph ph-arrow-circle-down text-base" :class="{{ $loading }} ? 'animate-spin motion-reduce:animate-none' : ''" aria-hidden="true"></i>
    <span>{{ $slot->isEmpty() ? 'Muat Lebih Banyak' : $slot }}</span>
</button>
