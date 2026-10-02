    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        document.querySelectorAll('#add_product_form .nav-link[data-bs-toggle="tab"]').forEach(function(link) {
            link.addEventListener('click', function(event) {
                event.preventDefault();
                document.querySelectorAll('#add_product_form .nav-link').forEach(item => item.classList.remove('active'));
                document.querySelectorAll('#add_product_form .tab-pane').forEach(item => item.classList.remove('active'));
                link.classList.add('active');
                document.querySelector(link.getAttribute('href'))?.classList.add('active');
            });
        });
        document.getElementById('add_product_form')?.addEventListener('invalid', function(event) {
            const pane = event.target.closest('.tab-pane');
            if (pane && !pane.classList.contains('active')) {
                document.querySelector('#add_product_form .nav-link[href="#' + pane.id + '"]')?.click();
            }
        }, true);
        $('#category_id').select2({
            width: '100%',
            placeholder: 'Ketik nama kategori',
            tags: true, // ini aktifkan fitur menambah item baru
            ajax: {
                url: '{{ route('ajax.category') }}',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        search: params.term
                    };
                },
                processResults: function(data, params) {
                    const term = params.term || '';

                    // Map hasil dari server
                    let results = data.map(item => ({
                        id: item.id, // penting: pastikan id-nya sesuai yg mau kamu simpan
                        text: item.name
                    }));

                    // Kalau term (yang diketik user) tidak ada di hasil, tambahkan manual
                    if (term && !results.some(r => r.text.toLowerCase() === term.toLowerCase())) {
                        results.push({
                            id: term, // kita pakai term sebagai id juga (karena kategori baru)
                            text: term
                        });
                    }

                    return {
                        results: results
                    };
                },
                cache: true
            },
            createTag: function(params) {
                const term = $.trim(params.term);

                if (term === '') {
                    return null;
                }

                return {
                    id: term,
                    text: term,
                    newTag: true // optional: kalau mau tandai item baru
                };
            }
        });

        $("#add_product_form").submit(function() {
            const overlaySubmit = document.getElementById('product-create-submit');
            if (overlaySubmit) {
                overlaySubmit.disabled = true;
                overlaySubmit.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Memuat...';
            }
            $(this).find(":submit").attr('disabled', 'disabled');
            $(this).find(":submit").html(
                `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Memuat...`
            );
        });

        $(document).ready(function() {
            if (typeof bindFormatNumber === 'function') {
                bindFormatNumber();
            }
            const path = window.location.pathname;
            if (/products\/\d+\/show/.test(path)) {
                // Disable semua input/select/textarea kecuali yang punya class 'variant'
                document.querySelectorAll('input, select, textarea, button').forEach(function(el) {
                    if (!el.classList.contains('variant')) {
                        el.disabled = true;
                    }
                });

                // Khusus tombol submit: sembunyikan jika tidak class 'variant'
                const submitBtn = document.getElementById('kt_ecommerce_add_product_submit');
                if (submitBtn) {
                    submitBtn.style.display = 'none';
                }
            }
        });

        function addVariant() {
            let html = `
            <tr>
                <td>
                    <select name="variant[id][]" class="form-select mb-2 select2_product"></select>
                </td>
                <td>
                    <input type="text" inputmode="numeric" name="variant[price][]" class="form-control format-number mb-2" placeholder="Harga Produk" />
                </td>
                <td class="text-end">
                    <button type="button" class="btn btn-icon btn-danger remove_variant">
                        <i class="ki-outline ki-cross fs-2"></i>
                    </button>
                </td>
            </tr>
            `;
            $('#kt_ecommerce_edit_order_selected_products_body').append(html);
            $('#kt_ecommerce_edit_order_selected_products_body .select2_product').select2({
                placeholder: 'Ketik nama produk',
                tags: true, // ini aktifkan fitur menambah item baru
                ajax: {
                    url: "{{ route('ajax.getProduct') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            search: params.term
                        };
                    },
                    processResults: function(data, params) {
                        const term = params.term || '';

                        // Map hasil dari server
                        let results = data.map(item => ({
                            id: item.id, // penting: pastikan id-nya sesuai yg mau kamu simpan
                            text: item.name
                        }));

                        // Kalau term (yang diketik user) tidak ada di hasil, tambahkan manual
                        if (term && !results.some(r => r.text.toLowerCase() === term.toLowerCase())) {
                            results.push({
                                id: term, // kita pakai term sebagai id juga (karena produk baru)
                                text: term
                            });
                        }

                        return {
                            results: results
                        };
                    },
                    cache: true
                },
                createTag: function(params) {
                    const term = $.trim(params.term);

                    if (term === '') {
                        return null;
                    }

                    return {
                        id: term,
                        text: term,
                        newTag: true // optional: kalau mau tandai item baru
                    };
                }
            });



            bindFormatNumber();
        }
        $('#variant_table').on('click', '.remove_variant', function() {
            $(this).closest('tr').remove();
        });

        @if (isset($variant) && $variant->count() > 0)
            const response = {!! json_encode($variant) !!};
            $('#kt_ecommerce_edit_order_selected_products_body').empty();

            // Loop hasil response
            response.forEach(item => {
                let html = `
                <tr>
                    <td>
                        <select name="variant[id][]" class="form-select mb-2 select2_product">
                            <option value="${item.product_id}" selected>${item.product.name}</option>
                        </select>
                    </td>
                    <td>
                        <input type="text" name="variant[price][]" class="form-control format-number mb-2" placeholder="Product price" value="${item.product.price || ''}"/>
                    </td>
                    <td class="text-end">
                        <button type="button" class="btn btn-icon btn-danger remove_variant">
                            <i class="ki-outline ki-cross fs-2"></i>
                        </button>
                    </td>
                </tr>
                `;
                $('#kt_ecommerce_edit_order_selected_products_body').append(html);
            });

            $('#kt_ecommerce_edit_order_selected_products_body .select2_product').select2({
                placeholder: 'Pilih produk',
                ajax: {
                    url: "{{ route('ajax.getProduct') }}",
                    dataType: 'json',
                    delay: 250,
                    data: params => ({
                        search: params.term
                    }),
                    processResults: data => ({
                        results: data.map(item => ({
                            id: item.id,
                            text: item.name
                        }))
                    })
                }
            });
        @endif

        function addBranch() {
            let html = `
            <tr>
                <td>
                    <select name="branch[id][]" class="form-select mb-2 select2_product"></select>
                </td>
                <td>
                    <input type="text" inputmode="numeric" name="branch[price][]" class="form-control format-number mb-2" placeholder="Harga Produk" />
                </td>
                <td class="text-end">
                    <button type="button" class="btn btn-icon btn-danger remove_branch">
                        <i class="ki-outline ki-cross fs-2"></i>
                    </button>
                </td>
            </tr>
            `;
            $('#kt_ecommerce_edit_order_selected_products_branch_body').append(html);
            $('#kt_ecommerce_edit_order_selected_products_branch_body .select2_product').select2({
                placeholder: 'Ketik nama branch',
                tags: true, // ini aktifkan fitur menambah item baru
                ajax: {
                    url: "{{ route('ajax.getBranch') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            search: params.term
                        };
                    },
                    processResults: function(data, params) {
                        const term = params.term || '';
                        let results = data.map(item => ({
                            id: item.id, // penting: pastikan id-nya sesuai yg mau kamu simpan
                            text: item.name
                        }));
                        return {
                            results: results
                        };
                    },
                    cache: true
                },
            });
            bindFormatNumber();
        }
        $('#branch_table').on('click', '.remove_branch', function() {
            $(this).closest('tr').remove();
        });

        @if (isset($product_branch) && $product_branch->count() > 0)
            const productBranch = {!! json_encode($product_branch) !!};
            $('#kt_ecommerce_edit_order_selected_products_branch_body').empty();

            // Loop hasil response
            productBranch.forEach(item => {
                let html = `
                <tr>
                    <td>
                        <select name="branch[id][]" class="form-select mb-2 select2_product">
                            <option value="${item.branch_id}" selected>${item.branch.name}</option>
                        </select>
                    </td>
                    <td>
                        <input type="text" name="branch[price][]" class="form-control format-number mb-2" placeholder="Branch price" value="${item.price || ''}"/>
                    </td>
                    <td class="text-end">
                        <button type="button" class="btn btn-icon btn-danger remove_branch">
                            <i class="ki-outline ki-cross fs-2"></i>
                        </button>
                    </td>
                </tr>
                `;
                $('#kt_ecommerce_edit_order_selected_products_branch_body').append(html);
            });

            $('#kt_ecommerce_edit_order_selected_products_branch_body .select2_product').select2({
                placeholder: 'Pilih produk',
                ajax: {
                    url: "{{ route('ajax.getProduct') }}",
                    dataType: 'json',
                    delay: 250,
                    data: params => ({
                        search: params.term
                    }),
                    processResults: data => ({
                        results: data.map(item => ({
                            id: item.id,
                            text: item.name
                        }))
                    })
                }
            });
        @endif
    </script>
    <script>
        function initializeProductCreateForm(root) {
            const form = root.querySelector('#add_product_form');
            if (!form || form.dataset.productDrawerReady) return;
            form.dataset.productDrawerReady = 'true';

            const variantToggle = form.querySelector('#product-has-variants');
            const variantPanel = form.querySelector('#product-variants');
            const basePrice = form.querySelector('#product-base-price');
            const branchToggle = form.querySelector('#product-branch-toggle');
            const branchPanel = form.querySelector('#product-branch-prices');

            variantToggle?.addEventListener('change', function () {
                variantPanel.classList.toggle('hidden', !this.checked);
                basePrice.classList.toggle('hidden', this.checked);
                const price = form.querySelector('#product-price');
                price.required = !this.checked;
                price.value = this.checked ? '0' : (price.value === '0' ? '' : price.value);
                if (this.checked && !form.querySelector('#kt_ecommerce_edit_order_selected_products_body tr')) addVariant();
            });
            branchToggle?.addEventListener('click', function () {
                branchPanel.classList.toggle('hidden');
                if (!branchPanel.classList.contains('hidden') && !form.querySelector('#kt_ecommerce_edit_order_selected_products_branch_body tr')) addBranch();
            });

            form.querySelectorAll('.nav-link[data-bs-toggle="tab"]').forEach(function (tab) {
                tab.addEventListener('click', function () {
                    form.querySelectorAll('.nav-link').forEach(item => item.classList.toggle('active', item === tab));
                    form.querySelectorAll('.tab-pane').forEach(pane => pane.classList.toggle('active', '#' + pane.id === tab.getAttribute('href')));
                    form.querySelectorAll('.nav-link').forEach(item => item.setAttribute('aria-selected', item === tab ? 'true' : 'false'));
                });
            });

            form.querySelectorAll('[data-picker-search]').forEach(function (search) {
                search.addEventListener('input', function () {
                    const options = form.querySelector('#' + search.dataset.pickerSearch);
                    const query = search.value.trim().toLocaleLowerCase('id');
                    let visible = 0;
                    options.querySelectorAll('[data-picker-option]').forEach(function (option) {
                        const match = option.dataset.pickerLabel.toLocaleLowerCase('id').includes(query);
                        option.classList.toggle('hidden', !match);
                        visible += match ? 1 : 0;
                    });
                    options.querySelector('[data-picker-empty]')?.classList.toggle('hidden', visible > 0);
                });
            });

            form.querySelectorAll('[data-picker-option]').forEach(function (option) {
                option.addEventListener('click', function () {
                    const select = form.querySelector('#' + option.dataset.pickerSelect);
                    select.value = option.dataset.pickerValue;
                    option.parentElement.querySelectorAll('[data-picker-option]').forEach(item => {
                        const active = item === option;
                        item.classList.toggle('bg-emerald-50/60', active);
                        item.querySelector('.product-picker-radio')?.classList.toggle('!border-emerald-500', active);
                        item.querySelector('.product-picker-radio span')?.classList.toggle('!scale-100', active);
                        const label = item.querySelector('.text-\\[13px\\]');
                        label?.classList.toggle('!font-bold', active);
                        label?.classList.toggle('!text-gray-900', active);
                    });
                });
            });

            ['product-type', 'product-unit'].forEach(function (id) {
                const select = form.querySelector('#' + id);
                const selectedOption = select?.options[select.selectedIndex];
                const selected = form.querySelector(`[data-picker-select="${id}"][data-picker-value="${selectedOption?.value}"]`);
                selected?.click();
            });

            form.addEventListener('submit', function (event) {
                const missingPicker = ['product-type', 'product-unit'].find(id => !form.querySelector('#' + id)?.value);
                if (missingPicker) {
                    event.preventDefault();
                    const picker = form.querySelector(`[data-picker-root="${missingPicker}"]`);
                    picker?.classList.add('!border-red-400');
                    picker?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    picker?.querySelector('input[type="search"]')?.focus({ preventScroll: true });
                    return;
                }
                form.querySelectorAll('[data-picker-root]').forEach(picker => picker.classList.remove('!border-red-400'));
                if (variantToggle?.checked) {
                    const variantSelect = form.querySelector('#kt_ecommerce_edit_order_selected_products_body select');
                    if (!variantSelect?.value) {
                        event.preventDefault();
                        variantSelect?.focus();
                        if (!variantSelect) alert('Tambahkan minimal satu varian produk.');
                    }
                }
            });
            form.addEventListener('submit', function (event) {
                if (event.defaultPrevented) return;
                const submitButton = document.getElementById('product-create-submit');
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<span class="ph ph-spinner-gap animate-spin mr-2"></span> Menyimpan...';
                }
            });

            const avatar = form.querySelector('#product-avatar');
            const preview = form.querySelector('#product-image-preview');
            let objectUrl = null;
            avatar?.addEventListener('change', function () {
                if (objectUrl) URL.revokeObjectURL(objectUrl);
                const file = this.files?.[0];
                objectUrl = file ? URL.createObjectURL(file) : null;
                preview.src = objectUrl || '';
                preview.classList.toggle('hidden', !file);
                form.querySelector('#product-image-placeholder')?.classList.toggle('hidden', !!file);
            });

            if ($.fn.select2 && !form.querySelector('#category_id').classList.contains('select2-hidden-accessible')) {
                $('#category_id').select2({
                    width: '100%',
                    placeholder: 'Ketik nama kategori',
                    tags: true,
                    ajax: {
                        url: '{{ route('ajax.category') }}',
                        dataType: 'json',
                        delay: 250,
                        data: params => ({ search: params.term }),
                        processResults: data => ({ results: data.map(item => ({ id: item.id, text: item.name })) }),
                        cache: true
                    }
                });
            }
            if (typeof bindFormatNumber === 'function') bindFormatNumber();
        }
        const standaloneProductForm = document.getElementById('add_product_form');
        if (standaloneProductForm) initializeProductCreateForm(standaloneProductForm.parentElement);
    </script>
    @if (request()->segment(3) === 'show')
        <script>
            $(document).ready(function() {
                const productId = "{{ $data->id }}"; // dari Blade

                $('#variant_table').DataTable({
                pageLength: 100,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: '{{ route('variants.get') }}',
                        data: function(d) {
                            d.product_id = productId;
                        }
                    },
                    columns: [{
                            data: 'name',
                            name: 'name'
                        },
                        {
                            data: 'price',
                            name: 'price'
                        },
                        {
                            data: 'action',
                            name: 'action',
                            orderable: false,
                            searchable: false,
                            className: 'text-end'
                        }
                    ]
                });

                let table = $('#variant_table').DataTable();

                // Edit: buka modal dan isi data
                $('#variant_table').on('click', '.edit-variant', function() {
                    $('#variant_id').val($(this).data('id'));
                    $('#variant_name').val($(this).data('name'));
                    $('#variant_price').val($(this).data('price'));
                    document.getElementById('editVariantModal').showModal();
                });

                // Submit Edit
                $('#editVariantForm').submit(function(e) {
                    e.preventDefault();
                    let id = $('#variant_id').val();

                    $.ajax({
                        url: '/products/variants/' + id,
                        type: 'PUT',
                        data: {
                            product_name: $('#variant_name').val(),
                            price: $('#variant_price').val(),
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function() {
                            Swal.fire('Berhasil', 'Varian berhasil diperbarui.', 'success');
                            document.getElementById('editVariantModal').close();
                            table.ajax.reload();
                        },
                        error: function() {
                            Swal.fire('Gagal', 'Terjadi kesalahan saat memperbarui.', 'error');
                        }
                    });
                });

                // Hapus Variant
                $('#variant_table').on('click', '.delete-variant', function() {
                    let id = $(this).data('id');

                    Swal.fire({
                        title: 'Yakin?',
                        text: "Data akan dihapus!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, hapus!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: '/products/variants/' + id,
                                type: 'DELETE',
                                data: {
                                    _token: $('meta[name="csrf-token"]').attr('content')
                                },
                                success: function() {
                                    Swal.fire('Dihapus!', 'Varian telah dihapus.',
                                        'success');
                                    table.ajax.reload();
                                },
                                error: function() {
                                    Swal.fire('Gagal', 'Tidak dapat menghapus data.',
                                        'error');
                                }
                            });
                        }
                    });
                });
            });

            function addVariant() {
                let html = `
                <tr>
                    <td>
                        <input type="text" name="product_name[]" class="form-control mb-2" placeholder="Nama produk" />
                    </td>
                    <td>
                        <input type="text" name="price[]" class="form-control format-number mb-2" placeholder="Harga produk" />
                    </td>
                    <td class="text-end">
                        <button type="button" class="btn btn-icon btn-danger save_variant">
                            <i class="ki-outline ki-check fs-2"></i>
                        </button>
                        <button type="button" class="btn btn-icon btn-danger remove_variant">
                            <i class="ki-outline ki-cross fs-2"></i>
                        </button>
                    </td>
                </tr>
            `;
                $('#kt_ecommerce_edit_order_selected_products_body').append(html);
                $('#variant_table').on('click', '.remove_variant', function() {
                    $(this).closest('tr').remove();
                });
                bindFormatNumber(); // Re-bind ke elemen baru setelah append
            }
            $('#variant_table').on('click', '.remove_variant', function() {
                $(this).closest('tr').remove();
            });

            $('#variant_table').on('click', '.save_variant', function() {
                let $row = $(this).closest('tr');
                let productName = $row.find('input[name="product_name[]"]').val();
                let price = $row.find('input[name="price[]"]').val();
                let productId = {{ $data->id }}; // jika kamu butuh ID produk utama

                if (productName === '' || price === '') {
                    alert('Nama produk dan harga harus diisi.');
                    return;
                }

                $.ajax({
                    url: '/products/variant/store',
                    method: 'POST',
                    data: {
                        product_name: productName,
                        price: price,
                        parent_id: productId,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: 'Varian produk berhasil disimpan.',
                            showConfirmButton: false
                        });
                        $('#variant_table').DataTable().ajax.reload(null,
                            false); // false = tetap di halaman sekarang
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: 'Gagal menyimpan varian produk. Cek console untuk detail.',
                        });
                        console.error(xhr.responseText);
                    }
                });
            });
        </script>
    @endif
