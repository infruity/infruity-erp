{{--
    A loading state: spinner + text, centred in its box. overlay puts it over a positioned parent with a white veil.
        <x-erp.loading>Memuat daftar…</x-erp.loading>
        <x-erp.loading overlay x-show="loading">Menyimpan…</x-erp.loading>
--}}
@props(['overlay' => false])
<div {{ $attributes->class(['flex items-center justify-center gap-2.5 text-[13px] font-medium text-gray-500', 'absolute inset-0 z-10 bg-white/70 backdrop-blur-[1px]' => $overlay, 'py-10' => ! $overlay]) }} role="status" aria-live="polite">
    <x-erp.spinner size="md" />
    <span>{{ $slot->isEmpty() ? 'Memuat…' : $slot }}</span>
</div>
