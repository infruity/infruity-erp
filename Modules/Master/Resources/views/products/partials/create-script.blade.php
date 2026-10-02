    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
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
        function productBranchPricing(root) {
            let branches = [];
            try {
                branches = JSON.parse(root.dataset.branches || '[]').map(branch => ({
                    id: String(branch.id),
                    name: branch.name || ''
                }));
            } catch (error) {
                console.error('Daftar cabang produk tidak dapat dibaca.', error);
            }

            return {
                branches,
                priceRules: [{
                    id: Date.now(),
                    price: root.dataset.initialPrice || '',
                    branches: ['all']
                }],
                addPriceRule() {
                    this.priceRules.push({ id: Date.now() + Math.random(), price: '', branches: [] });
                },
                hasOtherBranchRules(rule, branchId) {
                    return this.priceRules.some(other => {
                        if (other.id === rule.id) return false;
                        if (branchId === 'all') return other.branches.length > 0;
                        return other.branches.includes('all') || other.branches.includes(String(branchId));
                    });
                },
                toggleBranch(rule, branchId, checked) {
                    rule.branches = rule.branches.filter(id => id !== 'all');
                    const id = String(branchId);
                    if (checked && !rule.branches.includes(id)) rule.branches.push(id);
                    if (!checked) rule.branches = rule.branches.filter(selected => selected !== id);
                },
                branchIdsFor(rule) {
                    return rule.branches.includes('all')
                        ? this.branches.map(branch => String(branch.id))
                        : rule.branches;
                }
            };
        }

        function initializeProductCreateForm(root) {
            const form = root.querySelector('#add_product_form');
            if (!form || form.dataset.productDrawerReady) return;
            form.dataset.productDrawerReady = 'true';

            const variantToggle = form.querySelector('#product-has-variants');
            const variantPanel = form.querySelector('#product-variants');
            const branchPanel = form.querySelector('#product-branch-prices');

            variantToggle?.addEventListener('change', function () {
                this.setAttribute('aria-checked', this.checked ? 'true' : 'false');
                variantPanel.classList.toggle('hidden', !this.checked);
                branchPanel.classList.toggle('hidden', this.checked);
                const price = form.querySelector('#product-price');
                price.value = this.checked ? '0' : (price.value === '0' ? '' : price.value);
                if (this.checked && !form.querySelector('#kt_ecommerce_edit_order_selected_products_body tr')) addVariant();
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

            const typeSearch = form.querySelector('[data-product-type-search]');
            const typeCreate = form.querySelector('[data-product-type-create]');
            const typePicker = form.querySelector('[data-product-type-picker]');
            const categoryValue = form.querySelector('#category_id');
            const productTypeValue = form.querySelector('#product-type');
            const productStatusValue = form.querySelector('#product-status');
            const typeOptions = Array.from(form.querySelectorAll('[data-product-type-option]'));

            function selectProductType(option) {
                categoryValue.value = option.dataset.categoryValue;
                productTypeValue.value = option.dataset.productTipe;
                productStatusValue.value = option.dataset.productStatus;
                typePicker.querySelectorAll('[data-product-type-option]').forEach(function (item) {
                    const selected = item === option;
                    item.classList.toggle('bg-emerald-50/60', selected);
                    item.querySelector('.product-picker-radio')?.classList.toggle('!border-emerald-500', selected);
                    item.querySelector('.product-picker-radio span')?.classList.toggle('!scale-100', selected);
                    const label = item.querySelector('.text-\\[13px\\]');
                    label?.classList.toggle('!font-bold', selected);
                    label?.classList.toggle('!text-gray-900', selected);
                });
                typeCreate?.classList.add('hidden');
                typePicker.classList.remove('!border-red-400');
            }

            function selectCustomProductType(name) {
                const value = name.trim();
                if (!value) return;
                categoryValue.value = value;
                productTypeValue.value = 'product';
                productStatusValue.value = 'no-receipt';
                typeOptions.forEach(item => {
                    item.classList.remove('bg-emerald-50/60');
                    item.querySelector('.product-picker-radio')?.classList.remove('!border-emerald-500');
                    item.querySelector('.product-picker-radio span')?.classList.remove('!scale-100');
                    const label = item.querySelector('.text-\\[13px\\]');
                    label?.classList.remove('!font-bold', '!text-gray-900');
                });
                typeCreate.replaceChildren();
                const icon = document.createElement('i');
                icon.className = 'ph ph-plus-circle';
                const label = document.createElement('span');
                label.textContent = `Tambah tipe produk “${value}”`;
                typeCreate.append(icon, label);
                typeCreate.classList.add('bg-emerald-50/60');
                typeCreate.classList.remove('hidden');
                typeCreate.dataset.selectedName = value;
                typePicker.classList.remove('!border-red-400');
            }

            typeSearch?.addEventListener('input', function () {
                const query = this.value.trim().toLocaleLowerCase('id');
                let visible = 0;
                let exactMatch = false;
                typeOptions.forEach(function (option) {
                    const label = option.dataset.categoryLabel.toLocaleLowerCase('id');
                    const match = label.includes(query);
                    option.classList.toggle('hidden', !match);
                    visible += match ? 1 : 0;
                    exactMatch = exactMatch || label === query;
                });
                typeCreate.classList.toggle('hidden', !query || exactMatch);
                typeCreate.dataset.categoryValue = this.value.trim();
                typeCreate.replaceChildren();
                const icon = document.createElement('i');
                icon.className = 'ph ph-plus-circle';
                const label = document.createElement('span');
                label.textContent = `Tambah tipe produk “${this.value.trim()}”`;
                typeCreate.append(icon, label);
                form.querySelector('[data-product-type-empty]')?.classList.toggle('hidden', visible > 0 || !query);
            });
            typeOptions.forEach(option => option.addEventListener('click', () => selectProductType(option)));
            typeCreate?.addEventListener('click', function () { selectCustomProductType(this.dataset.categoryValue); });

            const initialType = typeOptions.find(option => option.dataset.categoryValue === categoryValue.value)
                || typeOptions.find(option => option.dataset.productTipe === productTypeValue.value && option.dataset.productStatus === productStatusValue.value);
            if (initialType) selectProductType(initialType);
            else if (categoryValue.value && !/^\\d+$/.test(categoryValue.value)) selectCustomProductType(categoryValue.value);

            const unitSelect = form.querySelector('#product-unit');
            const selectedUnit = unitSelect?.options[unitSelect.selectedIndex];
            form.querySelector(`[data-picker-select="product-unit"][data-picker-value="${selectedUnit?.value}"]`)?.click();

            form.addEventListener('submit', function (event) {
                const missingPicker = !categoryValue.value ? 'product-type' : (!unitSelect?.value ? 'product-unit' : null);
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
                    return;
                }

                const pricingRoot = form.querySelector('[data-branch-pricing]');
                const pricing = window.Alpine?.$data(pricingRoot);
                const rules = pricing?.priceRules || [];
                const invalidRule = rules.find(rule => !String(rule.price).trim() || pricing.branchIdsFor(rule).length === 0);
                if (invalidRule) {
                    event.preventDefault();
                    alert('Lengkapi harga dan pilih minimal satu cabang untuk setiap aturan harga.');
                    return;
                }
                form.querySelector('#product-price').value = rules[0]?.price || '0';
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
