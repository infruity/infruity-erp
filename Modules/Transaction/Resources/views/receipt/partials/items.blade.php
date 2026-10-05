@foreach($receipts as $receipt)
    @php($product = $receipt->products)
    @php($detail = [
        'id' => $receipt->id,
        'code' => $receipt->code,
        'description' => $receipt->description,
        'updatedAt' => $receipt->updated_at?->toIso8601String(),
        'product' => [
            'id' => $product?->id,
            'name' => $product?->name ?? 'Produk tidak ditemukan',
            'hpp' => (float) ($product?->hpp ?? 0),
            'unit' => $product?->unit?->abbreviation ?? '',
        ],
        'ingredients' => $receipt->recipeIngredients->map(fn ($item) => [
            'id' => $item->product_receipt_id,
            'name' => $item->ingredients?->name ?? '',
            'quantity' => (float) $item->quantity,
            'unit' => $item->ingredients?->unit?->abbreviation ?? '',
        ])->values()->all(),
    ])
    <div role="button" tabindex="0"
         @click="openRecipeDetail(@js($detail))"
         @keydown.enter.prevent="openRecipeDetail(@js($detail))"
         @keydown.space.prevent="openRecipeDetail(@js($detail))"
         class="flex cursor-pointer items-center gap-3 border-b border-gray-100 px-5 py-4 hover:bg-gray-50/50 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-emerald-600">
        @if($product?->image)
            <div class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-gray-100 bg-gray-100 text-lg shadow-sm md:h-12 md:w-12 md:text-xl">
                <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
            </div>
        @else
            @php($productIcon = $product ? \Modules\Master\Support\ProductIcon::resolve($product) : ['📦', 'bg-gray-100'])
            <div class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-gray-100 text-lg shadow-sm md:h-12 md:w-12 md:text-xl {{ $productIcon[1] }}">
                {{ $productIcon[0] }}
            </div>
        @endif
        <div class="min-w-0 flex-1">
            <span class="block truncate text-sm font-semibold text-gray-900">{{ $product?->name ?? 'Produk tidak ditemukan' }}</span>
            <span class="mt-0.5 block truncate text-[11px] text-gray-400">{{ $receipt->code }}{{ $receipt->description ? ' • ' . $receipt->description : '' }}</span>
        </div>
    </div>
@endforeach
