{{-- jQuery + SweetAlert2 sebagai skrip klasik (lihat vite.config.js). Dimuat sinkron karena skrip inline halaman memakai `$` dan `Swal` saat HTML di-parse. --}}
@php($vendorJs = public_path('build/vendor/vendor.js'))
@if (is_file($vendorJs))
    <script src="{{ asset('build/vendor/vendor.js') }}?v={{ filemtime($vendorJs) }}"></script>
@else
    <script>console.error('Vendor JS belum dibangun. Jalankan: npm run build');</script>
@endif
