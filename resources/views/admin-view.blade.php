<div class="row">
    <div class="col">
        <a href="{{ url('permohonan') }}">
            <div class="card widget-1">
                <div class="card-body">
                    <div class="widget-content">
                        <div class="widget-round primary">
                            <div class="bg-round">
                                <svg class="fill-primary">
                                    <use href="{{ asset('cuba/svg/icon-sprite.svg#c-invoice') }}"> </use>
                                </svg>
                                <svg class="half-circle svg-fill">
                                    <use href="{{ asset('cuba/svg/icon-sprite.svg#halfcircle') }}"></use>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h4 class="counter" data-target="{{ $totalSemua }}">0</h4><span class="f-light">Total
                                Permohonan
                                Masuk</span>
                        </div>
                    </div>
                    <div class="font-primary f-w-500"><i class="bookmark-search me-1"
                            data-feather="trending-up"></i><span class="txt-primary">+50%</span></div>
                </div>
            </div>
        </a>
    </div>
    <div class="col">
        <a href="{{ url($url) }}">
            <div class="card widget-1">
                <div class="card-body">
                    <div class="widget-content">
                        <div class="widget-round warning">
                            <div class="bg-round">
                                <svg class="fill-warning">
                                    <use href="{{ asset('cuba/svg/icon-sprite.svg#c-invoice') }}"> </use>
                                </svg>
                                <svg class="half-circle svg-fill">
                                    <use href="{{ asset('cuba/svg/icon-sprite.svg#halfcircle') }}"></use>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h4 class="counter" data-target="{{ $totalFilter }}">0</h4><span
                                class="f-light">{{ $namaStatus }}</span>
                        </div>
                    </div>
                    <div class="font-warning f-w-500"><i class="bookmark-search me-1"
                            data-feather="trending-up"></i><span class="txt-warning">+50%</span></div>
                </div>
            </div>
        </a>
    </div>
    <div class="col">
        <a href="{{ url('permohonan-selesai') }}">
            <div class="card widget-1">
                <div class="card-body">
                    <div class="widget-content">
                        <div class="widget-round success">
                            <div class="bg-round">
                                <svg class="fill-success">
                                    <use href="{{ asset('cuba/svg/icon-sprite.svg#c-invoice') }}"> </use>
                                </svg>
                                <svg class="half-circle svg-fill">
                                    <use href="{{ asset('cuba/svg/icon-sprite.svg#halfcircle') }}"></use>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h4 class="counter" data-target="{{ $totalSelesai }}">0</h4><span class="f-light">Data
                                Permohonan Yang Sudah Selesai</span>
                        </div>
                    </div>
                    <div class="font-success f-w-500"><i class="bookmark-search me-1"
                            data-feather="trending-up"></i><span class="txt-success">+50%</span></div>
                </div>
            </div>
        </a>
    </div>

    <div class="card widget-1">
        <div class="card-header d-flex justify-content-between">
            List Permohonan yang Harus Anda Proses
            <a href="{{ 'cetak-data-dashboard' }}" class="btn btn-sm btn-success"><i class="fa fa-print"></i> Cetak
                Data</a>
        </div>
        <div class="card-body">
            <table id="usersTable" class="table table-bordered">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>No Daftar</th>
                        <th>Nama Pemohon</th>
                        <th>Jenis Permohonan</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {
        $('#usersTable').DataTable({
                pageLength: 100,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('monitoring-proses-data') }}",
                data: function(d) {
                    d.id_permohonan = $('#id_permohonan').val();
                    d.nm_pemohon = $('#nm_pemohon').val();
                    d.alamat_persil = $('#alamat_persil').val();
                    d.id_layanan = $('#id_layanan').val();
                    d.id_status = $('#id_status').val();
                    d.url = "{{ request()->segment(1) }}";
                }
            },
            columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex'
                },
                {
                    data: 'no_permohonan',
                    name: 'no_permohonan'
                },
                {
                    data: 'nama_pemohon',
                    name: 'nama_pemohon'
                },
                {
                    data: 'nm_layanan',
                    name: 'nm_layanan'
                },
            ]
        });
    });
</script>
