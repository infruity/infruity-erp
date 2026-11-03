@extends('template.root')
@section('title', 'Detail Produksi')

@section('content')
<div class="d-flex flex-column flex-lg-row">
    <!--begin::Content-->
    <div class="flex-lg-row-fluid me-lg-15 order-2 order-lg-1 mb-10 mb-lg-0">
        <!--begin::Card-->
        <section class="erp-editor-section pb-0" id="kt_profile_details_view">
            <!--begin::Card header-->
            <div class="erp-detail-header collapsible cursor-pointer rotate" data-bs-toggle="collapse" data-bs-target="#kt_customer_view_details" aria-expanded="true" aria-controls="kt_customer_view_details">
                <!--begin::Card title-->
                <div class="card-title m-0">
                    <h3 class="fw-bold m-0">Detail Produksi #{{ $data->production_number }}</h3>
                </div>
                <!--end::Card title-->
                <!--begin::Actions-->
                <div class="card-toolbar">
                    <div class="btn-group" role="group">
                        @if(in_array($data->status, ['temp', 'draft']))
                        <a href="{{ route('production.edit', $data->id) }}" class="btn btn-sm btn-light-primary">
                            <i class="ki-duotone ki-pencil fs-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            Edit
                        </a>
                        @endif
                        
                        @if($data->status == 'draft')
                        <a href="{{ route('production.payment', $data->id) }}" class="btn btn-sm btn-success">
                            <i class="ki-duotone ki-check fs-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            Selesaikan Produksi
                        </a>
                        @endif
                        
                        <a href="{{ route('production.print', $data->id) }}" class="btn btn-sm btn-light-info">
                            <i class="ki-duotone ki-printer fs-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                            </i>
                            Print
                        </a>
                        
                        <a href="{{ route('production.index') }}" class="btn btn-sm btn-light">
                            <i class="ki-duotone ki-arrow-left fs-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            Kembali
                        </a>
                    </div>
                </div>
                <!--end::Actions-->
            </div>
            <!--end::Card header-->
            
            <!--begin::Card body-->
            <div class="erp-editor-body p-9" id="kt_customer_view_details">
                <!--begin::Row-->
                <div class="row mb-7">
                    <!--begin::Label-->
                    <label class="col-lg-4 fw-semibold text-muted">Nomor Produksi</label>
                    <!--end::Label-->
                    <!--begin::Col-->
                    <div class="col-lg-8">
                        <span class="fw-bold fs-6 text-gray-800">#{{ $data->production_number }}</span>
                    </div>
                    <!--end::Col-->
                </div>
                <!--end::Row-->
                
                <!--begin::Row-->
                <div class="row mb-7">
                    <!--begin::Label-->
                    <label class="col-lg-4 fw-semibold text-muted">Produk yang Diproduksi</label>
                    <!--end::Label-->
                    <!--begin::Col-->
                    <div class="col-lg-8 fv-row">
                        <span class="fw-bold fs-6 text-gray-800">{{ $data->products->name ?? 'N/A' }}</span>
                    </div>
                    <!--end::Col-->
                </div>
                <!--end::Row-->
                
                <!--begin::Row-->
                <div class="row mb-7">
                    <!--begin::Label-->
                    <label class="col-lg-4 fw-semibold text-muted">Jumlah Produksi</label>
                    <!--end::Label-->
                    <!--begin::Col-->
                    <div class="col-lg-8 fv-row">
                        <span class="fw-bold fs-6 text-gray-800">{{ tonumber($data->quantity) }} {{ $data->products->unit->abbreviation ?? 'pcs' }}</span>
                    </div>
                    <!--end::Col-->
                </div>
                <!--end::Row-->
                
                <!--begin::Row-->
                <div class="row mb-7">
                    <!--begin::Label-->
                    <label class="col-lg-4 fw-semibold text-muted">Tanggal Produksi</label>
                    <!--end::Label-->
                    <!--begin::Col-->
                    <div class="col-lg-8 fv-row">
                        <span class="fw-bold fs-6 text-gray-800">{{ dateindo($data->production_date) }} {{ ($data->created_at?->format('H:i') ?? '—') }} WIB</span>
                    </div>
                    <!--end::Col-->
                </div>
                <!--end::Row-->
                
                <!--begin::Row-->
                <div class="row mb-7">
                    <!--begin::Label-->
                    <label class="col-lg-4 fw-semibold text-muted">PIC / Penanggung Jawab</label>
                    <!--end::Label-->
                    <!--begin::Col-->
                    <div class="col-lg-8 fv-row">
                        <span class="fw-bold fs-6 text-gray-800">{{ $data->staff->name ?? $data->creator->nm_user ?? 'Tidak ada' }}</span>
                    </div>
                    <!--end::Col-->
                </div>
                <!--end::Row-->
                
                <!--begin::Row-->
                <div class="row mb-7">
                    <!--begin::Label-->
                    <label class="col-lg-4 fw-semibold text-muted">Status</label>
                    <!--end::Label-->
                    <!--begin::Col-->
                    <div class="col-lg-8 fv-row">
                        @if($data->status == 'posting')
                            <span class="badge badge-light-success fs-7 fw-bold">Selesai</span>
                        @elseif($data->status == 'draft')
                            <span class="badge badge-light-warning fs-7 fw-bold">Siap Produksi</span>
                        @elseif($data->status == 'temp')
                            <span class="badge badge-light-info fs-7 fw-bold">Draft Sementara</span>
                        @else
                            <span class="badge badge-light-secondary fs-7 fw-bold">{{ ucfirst($data->status) }}</span>
                        @endif
                        @if($data->pos_id)
                            <span class="badge badge-light-primary fs-7 fw-bold ms-2">POS</span>
                            <div class="mt-2">
                                <a href="{{ route('pos.show', $data->pos_id) }}" class="text-primary fs-6 text-hover-primary fw-bold">
                                    {{ $data->pos && $data->pos->customer ? $data->pos->customer->name : 'Pelanggan Umum' }}
                                </a>
                            </div>
                        @endif
                    </div>
                    <!--end::Col-->
                </div>
                <!--end::Row-->
                
                @if($data->description)
                <!--begin::Row-->
                <div class="row mb-7">
                    <!--begin::Label-->
                    <label class="col-lg-4 fw-semibold text-muted">Catatan</label>
                    <!--end::Label-->
                    <!--begin::Col-->
                    <div class="col-lg-8 fv-row">
                        <span class="fw-semibold fs-6 text-gray-800">{{ $data->description }}</span>
                    </div>
                    <!--end::Col-->
                </div>
                <!--end::Row-->
                @endif
            </div>
            <!--end::Card body-->
        </section>
        <!--end::Card-->
        
        <!--begin::Card-->
        <section class="erp-editor-section mt-6">
            <!--begin::Card header-->
            <div class="erp-detail-header">
                <!--begin::Card title-->
                <div class="card-title">
                    <h2>Bahan Baku yang Digunakan</h2>
                </div>
                <!--end::Card title-->
            </div>
            <!--end::Card header-->
            
            <!--begin::Card body-->
            <div class="erp-editor-body pt-0">
                <!--begin::Table-->
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_table_ingredients">
                        <thead>
                            <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                <th class="min-w-175px">Bahan Baku</th>
                                <th class="text-center min-w-70px">Jumlah</th>
                                <th class="text-center min-w-100px">HPP Satuan</th>
                                <th class="text-center min-w-100px">Total HPP</th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-600">
                            @forelse($production_detail as $item)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="symbol symbol-50px me-3">
                                            <div class="symbol-label bg-light-primary">
                                                <i class="ki-duotone ki-package fs-2x text-primary">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                    <span class="path3"></span>
                                                </i>
                                            </div>
                                        </div>
                                        <div class="ms-5">
                                            <div class="fw-bold text-gray-800 text-hover-primary mb-1">{{ $item->products->name }}</div>
                                            <div class="text-muted fs-7">{{ $item->products->category->name ?? 'Umum' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="fw-bold">{{ tonumber($item->quantity) }}</span>
                                    <div class="text-muted fs-7">{{ $item->products->unit->abbreviation ?? 'pcs' }}</div>
                                </td>
                                <td class="text-center">
                                    <span class="fw-bold text-primary">Rp {{ tonumberround($item->products->hpp) }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="fw-bold text-success">Rp {{ tonumberround($item->products->hpp * $item->quantity) }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="ki-duotone ki-information-5 fs-2x text-gray-400 mb-2">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                        </i>
                                        <div>Tidak ada bahan baku</div>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <!--end::Table-->
            </div>
            <!--end::Card body-->
        </section>
        <!--end::Card-->
    </div>
    <!--end::Content-->
    
    <!--begin::Sidebar-->
    <div class="flex-column flex-lg-row-auto w-lg-250px w-xl-300px mb-10 order-1 order-lg-2">
        <!--begin::Card-->
        <section class="erp-editor-section mb-6 mb-xl-9">
            <!--begin::Card header-->
            <div class="erp-detail-header mt-6">
                <!--begin::Card title-->
                <div class="card-title flex-column">
                    <h2 class="mb-1">Ringkasan Biaya</h2>
                    <div class="fs-6 fw-semibold text-muted">Perhitungan HPP Produksi</div>
                </div>
                <!--end::Card title-->
            </div>
            <!--end::Card header-->
            
            <!--begin::Card body-->
            <div class="erp-editor-body p-9 pt-4">
                <!--begin::Separator-->
                <div class="separator separator-dashed mb-7"></div>
                <!--end::Separator-->
                
                <!--begin::Item-->
                <div class="d-flex flex-stack mb-3">
                    <div class="text-gray-700 fw-semibold fs-6 me-2">Total HPP Bahan:</div>
                    <div class="text-gray-900 fw-bold fs-6">Rp {{ tonumberround($total_hpp) }}</div>
                </div>
                <!--end::Item-->
                
                <!--begin::Item-->
                <div class="d-flex flex-stack mb-3">
                    <div class="text-gray-700 fw-semibold fs-6 me-2">Jumlah Produksi:</div>
                    <div class="text-gray-900 fw-bold fs-6">{{ tonumber($data->quantity) }} {{ $data->products->unit->abbreviation ?? 'pcs' }}</div>
                </div>
                <!--end::Item-->
                
                <!--begin::Separator-->
                <div class="separator separator-dashed mb-7"></div>
                <!--end::Separator-->
                
                <!--begin::Item-->
                <div class="d-flex flex-stack">
                    <div class="text-gray-700 fw-semibold fs-6 me-2">HPP per Unit:</div>
                    <div class="text-gray-900 fw-bold fs-5">Rp {{ tonumberround($hpp_per_unit) }}</div>
                </div>
                <!--end::Item-->
            </div>
            <!--end::Card body-->
        </section>
        <!--end::Card-->
        
        <!--begin::Card-->
        <section class="erp-editor-section mb-6 mb-xl-9">
            <!--begin::Card header-->
            <div class="erp-detail-header mt-6">
                <!--begin::Card title-->
                <div class="card-title flex-column">
                    <h2 class="mb-1">Informasi Tambahan</h2>
                </div>
                <!--end::Card title-->
            </div>
            <!--end::Card header-->
            
            <!--begin::Card body-->
            <div class="erp-editor-body p-9 pt-4">
                <!--begin::Item-->
                <div class="d-flex flex-stack mb-3">
                    <div class="text-gray-700 fw-semibold fs-6 me-2">Dibuat oleh:</div>
                    <div class="text-gray-900 fw-bold fs-6">{{ $data->creator->nm_user ?? 'System' }}</div>
                </div>
                <!--end::Item-->
                
                <!--begin::Item-->
                <div class="d-flex flex-stack mb-3">
                    <div class="text-gray-700 fw-semibold fs-6 me-2">Tanggal dibuat:</div>
                    <div class="text-gray-900 fw-bold fs-6">{{ ($data->created_at?->format('d/m/Y H:i') ?? '—') }}</div>
                </div>
                <!--end::Item-->
                
                @if($data->updated_at != $data->created_at)
                <!--begin::Item-->
                <div class="d-flex flex-stack mb-3">
                    <div class="text-gray-700 fw-semibold fs-6 me-2">Terakhir diupdate:</div>
                    <div class="text-gray-900 fw-bold fs-6">{{ ($data->updated_at?->format('d/m/Y H:i') ?? '—') }}</div>
                </div>
                <!--end::Item-->
                @endif
                
                <!--begin::Item-->
                <div class="d-flex flex-stack">
                    <div class="text-gray-700 fw-semibold fs-6 me-2">Total Bahan:</div>
                    <div class="text-gray-900 fw-bold fs-6">{{ $production_detail->count() }} item</div>
                </div>
                <!--end::Item-->
            </div>
            <!--end::Card body-->
        </section>
        <!--end::Card-->
    </div>
    <!--end::Sidebar-->
</div>
@endsection