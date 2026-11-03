@extends('template.root')
@section('title', 'Laporan')
@section('page-title', 'Laporan')
@section('content')
<x-erp.workspace title="Laporan" subtitle="Pilih laporan untuk meninjau operasional bisnis." icon="ph-chart-bar">
    <div class="px-5 py-8">
        <a href="{{ route('reports.hub') }}" class="erp-action erp-action--primary"><i class="ph ph-chart-bar" aria-hidden="true"></i>Buka Pusat Laporan</a>
    </div>
</x-erp.workspace>
@endsection
