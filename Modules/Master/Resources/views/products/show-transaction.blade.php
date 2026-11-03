@extends('template.root')

@section('title', 'Riwayat HPP')
@section('page-title', 'Riwayat HPP')
@section('page-breadcrumb')
    <span>Inventaris</span>
    <i class="ph ph-caret-right text-[0.55rem]"></i>
    <a href="{{ route('product-stock') }}">Stok Barang</a>
    <i class="ph ph-caret-right text-[0.55rem]"></i>
    <span>Riwayat HPP</span>
@endsection

@section('content')<x-erp.workspace title="Riwayat HPP" :subtitle="$product->name"><x-slot:toolbar><label class="erp-search"><i class="ph ph-magnifying-glass" aria-hidden="true"></i><input type="search" id="hpp-search" placeholder="Cari riwayat HPP…" aria-label="Cari riwayat HPP"></label><a href="{{ route('product-stock.show',$product->id) }}" class="erp-action">Pergerakan stok</a><a href="{{ route('product-transaction.show',$product->id) }}" class="erp-action erp-action--primary" aria-current="page">Pergerakan HPP</a><a href="{{ url('product-stock') }}" class="erp-action">Kembali</a></x-slot:toolbar><x-erp.collection table="product-transaction-table"><table class="table table-bordered align-middle fs-6 gy-5 nowrap" id="product-transaction-table" width="100%">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th class="text-nowrap min-w-70px">Type</th>
                            <th class="text-nowrap min-w-150px">Remarks</th>
                            <th class="text-nowrap text-center min-w-70px">Qty</th>
                            <th class="text-nowrap text-center min-w-120px">Harga Beli</th>
                            <th class="text-nowrap text-center min-w-120px">Total Belanja</th>
                            <th class="text-nowrap text-center min-w-100px">Covered Qty</th>
                            <th class="text-nowrap text-center min-w-120px">COGS</th>
                            <th class="text-nowrap text-center min-w-120px">Recovered COGS</th>
                            <th class="text-nowrap text-center min-w-120px">Saldo Qty</th>
                            <th class="text-nowrap text-center min-w-120px">Pending Qty</th>
                            <th class="text-nowrap text-center min-w-120px">Total Aset</th>
                            <th class="text-nowrap text-center min-w-120px">HPP Berjalan</th>
                            <th class="text-nowrap text-center min-w-120px">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody class="fw-semibold text-gray-600">
                    </tbody>
                </table></x-erp.collection><section id="check-balance"><header class="erp-detail-header"><h2>Ringkasan rekonsiliasi</h2></header><dl class="erp-detail-grid"><div><dt>Grand Total</dt><dd>Rp {{ toNumber($report->total_belanja) }}</dd></div><div><dt>Total Belanja</dt><dd>Rp {{ toNumber($report->total_belanja) }}</dd></div></dl><dl class="erp-detail-grid"><div><dt>Grand Total</dt><dd>Rp {{ tonumberround($report->total_cogs + $report->total_recovered_cogs + $report->last_asset, 0) }}</dd></div><div><dt>COGS/Var (recognized)</dt><dd>Rp {{ tonumberround($report->total_cogs, 0) }}</dd></div><div><dt>Recovered COGS</dt><dd>Rp {{ tonumberround($report->total_recovered_cogs, 0) }}</dd></div><div><dt>Lost Asset</dt><dd>Rp {{ tonumberround($report->last_asset, 0) }}</dd></div><div><dt>Opname (+/-)</dt><dd>Rp -</dd></div></dl></section></x-erp.workspace><script type="text/javascript">
        var dataTable;

        function formatNumber(data) {
            if (data === null || data === undefined || data === '') return '-';
            var num = parseFloat(data);
            if (isNaN(num)) return data;
            return num.toLocaleString('id-ID');
        }

        $(document).ready(function() {
            $('#hpp-search').on('input', function(){ dataTable?.search(this.value).draw(); });
            dataTable = $('#product-transaction-table').DataTable({
                pageLength: 100,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                processing: true,
                serverSide: true,
                scrollX: true,
                ajax: {
                    url: "{{ route('product-transaction-data') }}",
                    data: function(d) {
                        d.product_id = {{ $product->id }}
                    }
                },
                columns: [
                    {
                        data: 'type',
                        name: 'type',
                        className: 'text-center text-nowrap'
                    },
                    {
                        data: 'remarks',
                        name: 'remarks',
                        className: 'text-nowrap',
                        render: function(data, type, row) {
                            if (row.url) {
                                return '<a href="' + row.url + '" class="text-primary fw-bold text-decoration-underline">' + data + '</a>';
                            }
                            return data;
                        }
                    },
                    {
                        data: 'qty',
                        name: 'qty',
                        className: 'text-end text-nowrap'
                    },
                    {
                        data: 'harga_satuan',
                        name: 'harga_satuan',
                        className: 'text-end text-nowrap'
                    },
                    {
                        data: 'total_belanja',
                        name: 'total_belanja',
                        className: 'text-end text-nowrap'
                    },
                    {
                        data: 'covered_qty',
                        name: 'covered_qty',
                        className: 'text-end text-nowrap',
                    },
                    {
                        data: 'cogs',
                        name: 'cogs',
                        className: 'text-end text-nowrap',
                    },
                    {
                        data: 'recovered_cogs',
                        name: 'recovered_cogs',
                        className: 'text-end text-nowrap',
                    },
                    {
                        data: 'qty_berjalan',
                        name: 'qty_berjalan',
                        className: 'text-end text-nowrap',
                    },

                    {
                        data: 'qty_berjalan',
                        name: 'qty_berjalan',
                        className: 'text-end text-nowrap',
                        render: function(data) {
                            if (data < 0) {
                                return Math.abs(data).toLocaleString('id-ID');
                            }
                            return '0';
                        }
                    },

                    {
                        data: 'total_aset_berjalan',
                        name: 'total_aset_berjalan',
                        className: 'text-end text-nowrap'
                    },
                    {
                        data: 'hpp_berjalan',
                        name: 'hpp_berjalan',
                        className: 'text-end text-nowrap'
                    },
                    {
                        data: 'created_at',
                        name: 'created_at',
                        className: 'text-end text-nowrap'
                    },
                ],
                order: [
                    [10, 'asc']
                ]
            });
        });

        function reloadDataTable() {
            if (typeof dataTable !== 'undefined') {
                dataTable.ajax.reload(null, false);
            } else {
                console.error('DataTable tidak terinisialisasi.');
            }
        }
    </script>
@endsection
