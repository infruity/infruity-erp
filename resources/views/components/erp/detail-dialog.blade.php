@props([
    'open',
    'close',
    'title',
    'labelledby',
    'closeLabel' => 'Tutup detail',
])

<div x-show="{{ $open }}" x-cloak role="dialog" aria-modal="true" aria-labelledby="{{ $labelledby }}" class="fixed inset-0 z-[120] flex items-center justify-center px-4" @keydown.escape.window="{{ $close }}" {{ $attributes }}>
    <div x-show="{{ $open }}" x-transition.opacity @click="{{ $close }}" class="absolute inset-0 bg-black/60"></div>
    <section x-show="{{ $open }}" x-transition class="relative z-10 flex w-full max-w-sm max-h-[calc(100dvh-2rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
        <div class="flex min-h-0 flex-1 flex-col overflow-y-auto p-6">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h2 id="{{ $labelledby }}" class="text-lg font-bold text-gray-800">{{ $title }}</h2>
                    {{ $subtitle ?? '' }}
                </div>
                <button type="button" @click="{{ $close }}" aria-label="{{ $closeLabel }}" class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 text-gray-500 transition-colors hover:bg-gray-200"><i class="ph-bold ph-x"></i></button>
            </div>
            {{ $slot }}
        </div>
        @isset($actions)
            <div class="flex w-full border-t border-gray-100">{{ $actions }}</div>
        @endisset
    </section>
</div>
