@extends('template.root')

@section('content')
    <div>
        <div class="card card-flush">
            <div class="card-header align-items-center py-5 gap-2 gap-md-5">
                <div class="card-title">
                    <div class="d-flex align-items-center position-relative my-1">
                        <i class="ki-outline ki-magnifier fs-3 position-absolute ms-4"></i>
                        <input type="text" data-kt-ecommerce-product-filter="search" id="search"
                            class="form-control form-control-solid w-250px ps-12" placeholder="Search Kurir" />
                    </div>
                </div>
                <div class="card-toolbar flex-row-fluid justify-content-end gap-5">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                        data-bs-target="#kt_modal_add_kurir">Tambah Kurir</button>
                </div>
            </div>
            <div class="card-body pt-0">
                <table class="table align-middle table-row-dashed fs-6 gy-5" id="transaction-table" width="100%">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th class="text-start min-w-100px">Tipe</th>
                            <th class="text-start min-w-100px">Nama Kurir</th>
                            <th class="text-start min-w-200px">Deskripsi</th>
                            <th class="text-end min-w-70px">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="fw-semibold text-gray-600"></tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="modal fade" id="kt_modal_add_kurir" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <div class="modal-content">
                <form class="form" action="{{ url(Request::segment(1)) }}" id="kt_modal_add_kurir_form"
                    data-kt-redirect="#">
                    @csrf
                    <div class="modal-header" id="kt_modal_add_kurir_header">
                        <h2 class="fw-bold">Tambah Kurir</h2>
                        <div id="kt_modal_add_kurir_close" class="btn btn-icon btn-sm btn-active-icon-primary">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </div>
                    </div>
                    <div class="modal-body py-10 px-lg-17">
                        <div class="scroll-y me-n7 pe-7" id="kt_modal_add_kurir_scroll" data-kt-scroll="true"
                            data-kt-scroll-activate="{default: false, lg: true}" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_modal_add_kurir_header"
                            data-kt-scroll-wrappers="#kt_modal_add_kurir_scroll" data-kt-scroll-offset="300px">
                            <div class="fv-row mb-15">
                                <label class="fs-6 fw-semibold mb-2">Tipe Kurir</label>
                                <select class="form-select form-select-solid" data-control="select2" data-placeholder="Pilih Tipe" name="type" id="kurir_type">
                                    <option value="internal">Internal (Staff)</option>
                                    <option value="external">External</option>
                                </select>
                            </div>
                            <div class="fv-row mb-15" id="kurir_internal_field">
                                <label class="fs-6 fw-semibold mb-2">Pilih Staff</label>
                                <select class="form-select" data-control="select2" data-placeholder="Cari staff..." id="kurir_staff_select">
                                    <option></option>
                                </select>
                                <input type="hidden" name="name" id="kurir_name" />
                                <input type="hidden" name="staff_id" id="kurir_staff_id" />
                            </div>
                            <div class="fv-row mb-15" id="kurir_external_field" style="display:none;">
                                <label class="fs-6 fw-semibold mb-2">Nama Kurir</label>
                                <input type="text" class="form-control form-control-solid" placeholder="Nama Kurir External"
                                    id="kurir_external_name" />
                            </div>
                            <div class="fv-row mb-15">
                                <label class="fs-6 fw-semibold mb-2">Deskripsi</label>
                                <textarea class="form-control form-control-solid" placeholder="" name="description"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer flex-center">
                        <button type="reset" id="kt_modal_add_kurir_cancel" class="btn btn-light me-3">Batal</button>
                        <button type="submit" id="kt_modal_add_kurir_submit" class="btn btn-primary">
                            <span class="indicator-label">Simpan</span>
                            <span class="indicator-progress">Please wait...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@section('script')
    <script type="text/javascript">
        var dataTable;
        var staffUrl = "{{ route('ajax.getKurirStaff') }}";
        var baseUrl = "{{ url(Request::segment(1)) }}";
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        const segment1 = "{{ Request::segment(1) }}";

        $(document).ready(function() {
            initStaffSelect2();

            $('#kurir_type').on('change', function() {
                toggleKurirFields(this.value);
            });

            // Sync external name input to hidden name field
            $('#kurir_external_name').on('input', function() {
                $('#kurir_name').val($(this).val());
            });

            toggleKurirFields($('#kurir_type').val());

            dataTable = $('#transaction-table').DataTable({
                pageLength: 100,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                processing: true,
                serverSide: true,
                scrollX: true,
                ajax: {
                    url: "{{ route('kurir.data') }}",
                    data: function(d) {
                        d.url = "{{ request()->segment(1) }}";
                    }
                },
                columns: [{
                        data: 'type',
                        name: 'type'
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'description',
                        name: 'description'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        className: 'text-end',
                        orderable: false,
                        searchable: false
                    },

                ]
            });
            $('#search').on('keyup', function() {
                dataTable.search(this.value).draw();
            });

            document.getElementById('kt_modal_add_kurir_cancel').addEventListener('click', function(e) {
                e.preventDefault();
                Swal.fire({
                    text: "Apakah Anda yakin ingin membatalkan?",
                    icon: "warning",
                    showCancelButton: !0,
                    buttonsStyling: !1,
                    confirmButtonText: "Ya, Batalkan!",
                    cancelButtonText: "Tidak, Kembali",
                    customClass: {
                        confirmButton: "btn btn-primary",
                        cancelButton: "btn btn-active-light"
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const modal = bootstrap.Modal.getInstance(document.getElementById(
                            'kt_modal_add_kurir'));
                        modal.hide();
                        document.getElementById('kt_modal_add_kurir_form').reset();
                        resetKurirForm();
                    }
                });
            });

            $('#kt_modal_add_kurir_form').on('submit', function(e) {
                e.preventDefault();

                // Ensure name field is synced based on type
                var type = $('#kurir_type').val();
                if (type === 'external') {
                    $('#kurir_name').val($('#kurir_external_name').val());
                }

                var form = $(this);
                var url = form.attr('action');
                var submitBtn = $('#kt_modal_add_kurir_submit');

                submitBtn.prop('disabled', true);
                submitBtn.find('.indicator-label').hide();
                submitBtn.find('.indicator-progress').show();

                $.ajax({
                    type: 'POST',
                    url: url,
                    data: form.serialize(),
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: response.message || 'Data berhasil disimpan.',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            form.trigger('reset');
                            form.find('input[name="_method"]').remove();
                            form.attr('action',
                                `/${segment1}`);
                            resetKurirForm();

                            $('#kt_modal_add_kurir_header h2').text(
                                'Tambah Kurir');

                            const modal = bootstrap.Modal.getInstance(document
                                .getElementById('kt_modal_add_kurir'));
                            if (modal) modal.hide();

                            if (typeof dataTable !== 'undefined') {
                                dataTable.ajax.reload(null, false);
                            }
                        });
                    },
                    error: function(xhr) {
                        var msg = 'Terjadi kesalahan saat menyimpan data.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: msg
                        });
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false);
                        submitBtn.find('.indicator-label').show();
                        submitBtn.find('.indicator-progress').hide();
                    }
                });
            });
        });

        function reloadDataTable() {
            if (typeof dataTable !== 'undefined') {
                dataTable.ajax.reload(null, false);
            } else {
                console.error('DataTable tidak terinisialisasi.');
            }
        }

        function deleteKurir(id) {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: 'Data yang dihapus tidak bisa dikembalikan!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal',
                customClass: {
                    confirmButton: 'btn btn-danger',
                    cancelButton: 'btn btn-secondary'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/kurir/${id}`,
                        type: 'DELETE',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: response.message || 'Data berhasil dihapus.',
                                showConfirmButton: false,
                                timer: 1500
                            });
                            reloadDataTable();
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: xhr.responseJSON?.message ||
                                    'Terjadi kesalahan saat menghapus data.'
                            });
                        }
                    });
                }
            });
        }

        function editKurir(id) {
            $.ajax({
                url: `/kurir/${id}/edit`,
                type: 'GET',
                success: function(response) {
                    $('textarea[name="description"]').val(response.description);

                    var form = $('#kt_modal_add_kurir_form');
                    form.attr('action', `/kurir/${id}`);
                    form.find('input[name="_method"]').remove();
                    form.append(
                        '<input type="hidden" name="_method" value="PUT">'
                    );

                    $('#kurir_type').val(response.type).trigger('change');
                    toggleKurirFields(response.type);

                    if (response.type === 'internal' && response.staff_id) {
                        $.ajax({
                            url: staffUrl,
                            dataType: 'json',
                            success: function(data) {
                                var staff = data.results.find(function(s) {
                                    return s.id == response.staff_id;
                                });
                                if (staff) {
                                    var opt = new Option(staff.text, response.staff_id, true, true);
                                    $('#kurir_staff_select').append(opt);
                                    $('#kurir_staff_select').val(response.staff_id).trigger('change.select2');
                                    $('#kurir_name').val(response.name);
                                    $('#kurir_staff_id').val(response.staff_id);
                                }
                            }
                        });
                    } else {
                        $('#kurir_external_name').val(response.name);
                        $('#kurir_name').val(response.name);
                    }

                    $('#kt_modal_add_kurir_header h2').text('Edit Kurir');
                    var modal = new bootstrap.Modal(document.getElementById('kt_modal_add_kurir'));
                    modal.show();
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Terjadi kesalahan saat memuat data kurir.'
                    });
                }
            });
        }

        function resetKurirForm() {
            toggleKurirFields('internal');
            $('#kurir_name').val('');
            $('#kurir_staff_id').val('');
            if ($('#kurir_staff_select').hasClass('select2-hidden-accessible')) {
                $('#kurir_staff_select').val(null).trigger('change.select2');
            }
            $('#kurir_external_name').val('');
        }

        function toggleKurirFields(type) {
            if (type === 'internal') {
                $('#kurir_internal_field').show();
                $('#kurir_external_field').hide();
                $('#kurir_external_name').val('');
                $('#kurir_name').val(''); // Clear name when switching
            } else {
                $('#kurir_internal_field').hide();
                $('#kurir_external_field').show();
                $('#kurir_staff_id').val('');
                $('#kurir_name').val($('#kurir_external_name').val()); // Sync current value
            }
        }

        function initStaffSelect2() {
            $('#kurir_staff_select').select2({
                allowClear: true,
                placeholder: 'Cari staff...',
                dropdownParent: $('#kt_modal_add_kurir'),
                ajax: {
                    url: staffUrl,
                    dataType: 'json',
                    quietMillis: 250,
                    processResults: function(data) {
                        return data;
                    },
                    cache: true
                }
            });

            $('#kurir_staff_select').on('select2:select', function(e) {
                var data = e.params.data;
                $('#kurir_name').val(data.name);
                $('#kurir_staff_id').val(data.id);
            });

            $('#kurir_staff_select').on('select2:clear', function(e) {
                $('#kurir_name').val('');
                $('#kurir_staff_id').val('');
            });
        }
    </script>
@endsection
@endsection