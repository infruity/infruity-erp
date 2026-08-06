@extends('template.root')

@section('content')
<div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-title">
            <h3 class="card-label">Detail Transaksi Sortir/Buang</h3>
        </div>
        <div class="card-toolbar">
            <a href="{{ route('report-product-buang') }}" class="btn btn-sm btn-light btn-active-light-primary">
                <i class="ki-duotone ki-arrow-left fs-2">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
                Kembali
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="row mb-10">
            <div class="col-md-6">
                <div class="mb-4">
                    <span class="text-gray-600 fw-semibold fs-6">Cabang:</span>
                    <span class="fw-bold fs-6 ms-2">{{ $data->branch->name ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <span class="text-gray-600 fw-semibold fs-6">Tanggal Transaksi:</span>
                    <span class="fw-bold fs-6 ms-2">{{ date('d M Y', strtotime($data->date)) }}</span>
                </div>
            </div>
            <div class="col-md-6 text-md-end">
                <div class="mb-4">
                    <span class="text-gray-600 fw-semibold fs-6">Nomor Faktur:</span>
                    <span class="fw-bold fs-6 ms-2">{{ $data->invoice_number }}</span>
                </div>
                <div class="mb-4">
                    <span class="text-gray-600 fw-semibold fs-6">Dibuat Oleh:</span>
                    <span class="fw-bold fs-6 ms-2">{{ $data->createdBy->nm_user ?? 'System' }}</span>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5">
                <thead>
                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                        <th class="min-w-200px">Produk</th>
                        <th class="text-center min-w-100px">Qty</th>
                        <th class="text-end min-w-100px">Harga (HPP)</th>
                        <th class="text-end min-w-100px">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 fw-semibold">
                    @foreach($detail as $item)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="ms-0">
                                    <span class="text-gray-800 fw-bold fs-5 mb-1 d-block">{{ $item->product->name ?? 'Produk Dihapus' }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            {{ $item->quantity }} {{ $item->product->unit->abbreviation ?? 'pcs' }}
                        </td>
                        <td class="text-end">
                            Rp{{ number_format($item->price, 0, ',', '.') }}
                        </td>
                        <td class="text-end">
                            Rp{{ number_format($item->subtotal, 0, ',', '.') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end fw-bold fs-5">Total Transaksi</td>
                        <td class="text-end fw-bold fs-5 text-success">Rp{{ number_format($data->total, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
