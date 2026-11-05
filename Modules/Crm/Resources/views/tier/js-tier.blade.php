@section('script')
    <script type="text/javascript">
        var dataTable;
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        const segment1 = "{{ Request::segment(1) }}";

        $(document).ready(function() {
            dataTable = $('#transaction-table').DataTable({
                pageLength: 100,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                processing: true,
                serverSide: true,
                scrollX: true, // Aktifkan scroll horizontal
                fixedColumns: {
                    leftColumns: 0,
                    rightColumns: 1
                },
                columnDefs: [{
                    orderable: false,
                    targets: -1 // Disable sorting for action column
                }, ],
                ajax: {
                    url: "{{ route('tier.data') }}",
                    data: function(d) {
                        d.url = "{{ request()->segment(1) }}";
                    }
                },
                columns: [{
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'level',
                        name: 'level'
                    },
                    {
                        data: 'exp',
                        name: 'exp'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
                order: [
                    [1, 'asc']
                ]
            });
            // Search manual lewat input
            $('#search').on('keyup', function() {
                dataTable.search(this.value).draw();
            });

            // Pratinjau warna label (nilai tersimpan tetap kelas lama `badge-light-*`).
            const styleTones = {
                'badge-light-primary': 'erp-badge--primary', 'badge-light-secondary': 'erp-badge--neutral',
                'badge-light-success': 'erp-badge--success', 'badge-light-danger': 'erp-badge--danger',
                'badge-light-warning': 'erp-badge--warning', 'badge-light-info': 'erp-badge--info',
                'badge-light': 'erp-badge--neutral', 'badge-light-dark': 'erp-badge--neutral'
            };
            const syncStylePreview = () => {
                const preview = document.getElementById('crm-style-preview');
                preview.className = 'erp-badge shrink-0 ' + (styleTones[$('#style').val()] || 'erp-badge--neutral');
            };
            $('#style').on('change', syncStylePreview);
            document.getElementById('crm-dialog').addEventListener('erp:dialog-close', syncStylePreview);
            syncStylePreview();

            document.getElementById('crm-cancel').addEventListener('click', function(e) {
                e.preventDefault(); // Mencegah form reset langsung
                Swal.fire({
                    text: "Apakah Anda yakin ingin membatalkan?",
                    icon: "warning",
                    showCancelButton: !0,
                    buttonsStyling: !1,
                    confirmButtonText: "Ya, Batalkan!",
                    cancelButtonText: "Tidak, Kembali",
                    customClass: {
                        confirmButton: "erp-action erp-action--primary",
                        cancelButton: "erp-action ml-3"
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        erp.dialog.close('crm-dialog');
                    }
                });
            });

            $('#crm-form').on('submit', function(e) {
                e.preventDefault();

                var form = $(this)[0];
                var url = $(this).attr('action');
                var submitBtn = $('#crm-submit');

                var formData = new FormData(form); // Gunakan FormData agar file bisa ikut terkirim

                // Show loading
                submitBtn.prop('disabled', true);
                submitBtn.find('[data-label]').prop('hidden', true);
                submitBtn.find('[data-progress]').prop('hidden', false);

                $.ajax({
                    type: 'POST',
                    url: url,
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: response.message || 'Data berhasil disimpan.',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            // Reset form, tutup modal, dan refresh DataTable
                            $('#crm-form').trigger('reset');
                            $('#crm-form').find(
                                'input[name="_method"]').remove();
                            $('#crm-form').attr('action',
                                `/${segment1}`);
                            erp.dialog.close('crm-dialog');
                            if (typeof dataTable !== 'undefined') dataTable.ajax.reload(
                                null, false);
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON?.message ||
                                'Terjadi kesalahan saat menyimpan data.'
                        });
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false);
                        submitBtn.find('[data-label]').prop('hidden', false);
                        submitBtn.find('[data-progress]').prop('hidden', true);
                    }
                });
            });

        });

        function reloadDataTable() {
            // Pastikan dataTable sudah terinisialisasi sebelumnya
            if (typeof dataTable !== 'undefined') {
                dataTable.ajax.reload(null, false); // 'false' untuk tidak mereset ke halaman pertama
            } else {
                console.error('DataTable tidak terinisialisasi.');
            }
        }

        function deleteProduct(id) {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: 'Data yang dihapus tidak bisa dikembalikan!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal',
                customClass: {
                    confirmButton: 'erp-action erp-action--danger',
                    cancelButton: 'erp-action ml-3'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/tier/${id}`, // Ganti dengan URL yang sesuai
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

                            // Reload DataTable setelah berhasil menghapus data
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

        function editProduct(id) {
            $.ajax({
                url: `/tier/${id}/edit`, // URL untuk mengambil data produk yang akan diedit
                type: 'GET',
                success: function(response) {
                    console.log(response);
                    // Isi form dengan data produk yang ada
                    $('input[name="name"]').val(response.name);
                    $('input[name="exp"]').val(response.exp);
                    $('input[name="level"]').val(response.level);
                    // Tampilkan gambar jika ada
                    if (response.style) $('select[name="style"]').val(response.style).trigger('change');
                    if (response.icon) {
                        erp.imageInput.set(document.querySelector('#crm-form [data-erp-image-input]'), `/storage/${response.icon}`);
                    }

                    // Ubah action form untuk update
                    var form = $('#crm-form');
                    form.attr('action', `/tier/${id}`); // URL untuk update produk
                    form.find('input[name="_method"]').remove(); // Hapus input _method jika ada
                    form.append(
                        '<input type="hidden" name="_method" value="PUT">'
                    ); // Menambahkan input _method untuk PUT

                    // Tampilkan modal untuk edit produk
                    
                    erp.dialog.open('crm-dialog');
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Terjadi kesalahan saat memuat data produk.'
                    });
                }
            });
        }


        function field(label, control) {
            return `<div><label class="erp-label">${label}</label>${control}</div>`;
        }
        function esc(value) {
            return String(value ?? '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
        }

        function format(rowData) {
            const toggle = (name, text, checked) => `
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" class="erp-check" name="${name}" value="1" ${checked == 1 ? 'checked' : ''}>
                    ${text}
                </label>`;
            return `
                <form class="space-y-4" data-benefit-form data-id="${esc(rowData.id)}">
                    ${field('Diskon Setiap Transaksi', `<input type="number" class="erp-input" name="discount_transaction" value="${esc(rowData.discount_transaction)}" placeholder="Diskon (Rp jika > 100, % jika ≤ 100)">`)}
                    ${field('Gratis Produk', `
                        <div data-free-products>
                            <div class="mb-2 flex flex-wrap gap-2" data-free-chips></div>
                            <select multiple name="free_product_id[]" class="hidden" aria-hidden="true" tabindex="-1"></select>
                            <select id="free-product-picker" class="erp-select" aria-label="Tambah produk gratis"></select>
                        </div>`)}
                    ${field('Minimal Pembelian', `<input type="text" class="erp-input format-number" name="minimal_purchase" value="${esc(rowData.minimal_purchase)}" placeholder="Minimal Pembelian">`)}
                    ${field('Maksimal Claim', `<input type="number" class="erp-input" name="max_claim" value="${esc(rowData.max_claim)}" placeholder="Maksimal Claim">`)}
                    ${field('Voucher', `<input type="text" class="erp-input format-number" name="voucher" value="${esc(rowData.voucher)}" placeholder="Voucher">`)}
                    ${field('Hadiah Ulang Tahun', toggle('birthday_gift', 'Aktifkan Hadiah Ulang Tahun', rowData.birthday_gift))}
                    ${field('Promo Gabungan', toggle('combo_promo', 'Aktifkan Promo Gabungan', rowData.combo_promo))}
                    ${field('Deposito', `<input type="text" class="erp-input format-number" name="deposito" value="${esc(rowData.deposito)}" placeholder="Deposito">`)}
                    <div class="flex justify-end pt-2"><button type="submit" class="erp-action erp-action--primary">Simpan</button></div>
                </form>`;
        }

        // Produk gratis: daftar chip + ErpPicker satu per satu (pengganti select2 multiple).
        function setupFreeProducts(root, products) {
            const multi = root.querySelector('select[multiple]');
            const chips = root.querySelector('[data-free-chips]');
            const picker = root.querySelector('#free-product-picker');
            const add = (id, name) => {
                id = String(id);
                if ([...multi.options].some(o => o.value === id)) return;
                multi.append(new Option(name, id, true, true));
                const chip = document.createElement('span');
                chip.className = 'erp-badge erp-badge--primary';
                chip.dataset.id = id;
                chip.append(document.createTextNode(name + ' '));
                const remove = document.createElement('button');
                remove.type = 'button';
                remove.setAttribute('aria-label', 'Hapus ' + name);
                remove.innerHTML = '<i class="ph ph-x" aria-hidden="true"></i>';
                remove.addEventListener('click', () => {
                    [...multi.options].find(o => o.value === id)?.remove();
                    chip.remove();
                });
                chip.append(remove);
                chips.append(chip);
            };
            products.forEach(prod => add(prod.id, prod.name));
            ErpPicker.attach(picker, {
                title: 'Tambah produk gratis',
                placeholder: 'Cari produk...',
                placeholderTrigger: 'Tambah produk',
                emptyText: 'Produk tidak ditemukan',
                url: '/ajax/listProduct',
                searchParam: 'term',
                map: item => ({ id: item.id, text: item.name })
            });
            $(picker).on('select2:select', function(e) {
                add(e.params.data.id, e.params.data.text);
                picker.innerHTML = '';
                $(picker).trigger('change');
            });
        }

        $('#transaction-table tbody').on('click', 'tr', function(e) {
            if ($(e.target).closest('button, a').length) return;

            let row = dataTable.row($(this));
            if (!row.data()) return;
            const container = document.getElementById('tier-benefit-content');
            container.innerHTML = format(row.data());
            setupFreeProducts(container, row.data().freeProduct || []);
            erp.dialog.open('tier-benefit-dialog');
            bindFormatNumber();
        });

        // Submit form di dalam accordion
        $('#tier-benefit-content').on('submit', '[data-benefit-form]', function(e) {
            e.preventDefault();
            let form = $(this);
            let id = form.data('id');
            let data = form.serialize();

            $.ajax({
                url: `/tier/${id}/save-detail`,
                type: 'POST',
                data: data,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function() {
                    erp.dialog.close('tier-benefit-dialog');
                    Swal.fire({ icon:'success', title:'Benefit disimpan', confirmButtonText:'Selesai' });
                    if (typeof dataTable !== 'undefined') dataTable.ajax.reload(
                        null, false);
                },
                error: function() {
                    Swal.fire({ icon:'error', title:'Benefit belum tersimpan', text:'Periksa koneksi dan coba lagi.', confirmButtonText:'Tutup' });
                }
            });
        });
    </script>
@endsection
