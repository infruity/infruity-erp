@extends('layouts.erp')
@section('title', 'Laporan')
@section('page-title', 'Laporan')
@section('content')
<x-erp.workspace title="Laporan" subtitle="Pilih laporan untuk meninjau operasional bisnis." icon="ph-chart-bar">
    <div class="px-4 py-6">
        <x-erp.button variant="primary" icon="chart-bar" :href="route('reports.hub')">Buka Pusat Laporan</x-erp.button>
    </div>
</x-erp.workspace>
@endsection
