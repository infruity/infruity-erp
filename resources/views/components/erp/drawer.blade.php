@props(['id', 'title' => null, 'static' => false, 'closeLabel' => 'Tutup'])
{{-- Panel samping (kanan). Di mobile tampil sebagai panel penuh. Dibangun di atas x-erp.dialog. --}}
<x-erp.dialog :id="$id" :title="$title" variant="drawer" :static="$static" :close-label="$closeLabel" {{ $attributes }}>
    {{ $slot }}
    @isset($footer)
        <x-slot:footer>{{ $footer }}</x-slot:footer>
    @endisset
</x-erp.dialog>
