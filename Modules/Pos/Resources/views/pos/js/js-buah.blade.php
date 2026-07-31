<script>
    (function() {
        const originalStringify = JSON.stringify;
        JSON.stringify = function(value, replacer, space) {
            try {
                return originalStringify(value, replacer, space);
            } catch (e) {
                if (e.message && e.message.includes('circular')) {
                    const customReplacer = function(key, val) {
                        if (val && typeof val === 'object') {
                            if (val.nodeType === 1 || val.jquery || (val.constructor && val.constructor.name === 'HTMLSelectElement')) {
                                return undefined;
                            }
                        }
                        return typeof replacer === 'function' ? replacer(key, val) : val;
                    };
                    return originalStringify(value, customReplacer, space);
                }
                throw e;
            }
        };
    })();

    function getCircularReplacer() {
        const seen = new WeakSet();
        return (key, value) => {
            if (value && (
                value instanceof HTMLElement || 
                value.nodeType === 1 || 
                (window.jQuery && value instanceof window.jQuery) || 
                value.jquery || 
                (value.constructor && value.constructor.name && value.constructor.name.includes('HTML'))
            )) {
                return undefined;
            }
            if (typeof value === "object" && value !== null) {
                if (seen.has(value)) {
                    return undefined;
                }
                seen.add(value);
            }
            return value;
        };
    }
    
    function posApp() {
        return {
            products: [],
            cart: [],
            parcel: [],
            jus: [],

            // edit product
            editModal: false,
            editItem: null,
            editPrice: 0,
            editQty: 1,
            editTotal: 0,
            editDiscount: 0,
            editDiscountPercent: 0,
            editDiscountNominal: 0,
            editDiscountMode: 'nominal', // atau 'percent'
            editTotalFormatted: '',
            editProductName: '',
            editProductUnit: '',
            editTitle: 'Edit Product',
            minimalPurchase: 0,
            voucher: 0,
            isShowGiftButton: false,
            diskonGlobal: 0,
            ongkirGlobal: 0,
            diskonOngkir: 0,
            currentInvoiceNumber: '',
            autoSaveTimeout: null,
            isAutoSaving: false,
            autoSaveQueued: false,
            originalStatus: 'temp',
            isEditing: false,

            // Add Product
            showAddModal: false,
            showGiftModal: false,
            showParcelModal: false,
            showJusModal: false,
            addProduct: {
                id: null,
                name: '',
                unit: '',
                price: 0,
                hpp: 0,
                discount: 0,
                discountNominal: 0,
                discountPercent: 0,
                qty: 1
            },

            addParcel: {
                id: null,
                name: '',
                harga: 0,
                qty: 1
            },

            // Animation states
            badgeAnimation: false,
            priceAnimation: false,

            init() {
                const self = this; // simpan konteks Alpine
                window.mainCartInstance = this;
                
                $('#addModal').on('hidden.bs.modal', function () {
                    if (window.mainCartInstance) window.mainCartInstance.resetAddForm();
                });
                $('#editModal').on('hidden.bs.modal', function () {
                    if (window.mainCartInstance) window.mainCartInstance.resetAddForm();
                });
                $('#jusModal').on('hidden.bs.modal', function () {
                    if (window.mainCartInstance) window.mainCartInstance.closeJusModal();
                });
                $('#editJusModal').on('hidden.bs.modal', function () {
                    if (window.mainCartInstance) window.mainCartInstance.closeEditJusModal();
                });
                $('#parcelModal').on('hidden.bs.modal', function () {
                    if (window.mainCartInstance) window.mainCartInstance.closeParcelModal();
                });
                $('#parcelEditModal').on('hidden.bs.modal', function () {
                    if (window.mainCartInstance) window.mainCartInstance.closeParcelEditModal();
                });
                $('#giftModal').on('hidden.bs.modal', function () {
                    if (window.mainCartInstance) window.mainCartInstance.closeGiftModal();
                });
                
                this.currentInvoiceNumber = document.querySelector('input[name="invoice_number"]')?.value || '';

                $('#customer_id').on('select2:select', function(e) {
                    const data = e.params.data;
                    self.setMinimalPurchase(data.minimalPurchase || 0);
                    self.setVoucher(data.voucher || 0);
                    self.setDiscountGlobal(data.discount || 0);
                });

                const data = @json($data ?? null);
                const detail = @json($detail ?? null);
                if (data && !this._loaded) {
                    this.isEditing = true;
                    this.originalStatus = data.status || 'temp';
                    this.loadExistingData(data, detail);
                    this._loaded = true;
                }
                const currentRouteIsOrderBook = {{ Route::currentRouteNamed('order-book.order') ? 'true' : 'false' }};
                if (currentRouteIsOrderBook && !this._loaded) {
                    const data = @json($data ?? null);
                    const detail = @json($detail ?? null);
                    this.isEditing = true;
                    this.originalStatus = data.status || 'temp';
                    this.loadExistingOrderBook(data, detail);
                    this._loaded = true;
                }

                // Add watchers for auto-save
                this.$watch('cart', () => this.autoSaveDraft());
                this.$watch('parcel', () => this.autoSaveDraft());
                this.$watch('jus', () => this.autoSaveDraft());
                this.$watch('diskonGlobal', () => this.autoSaveDraft());
                this.$watch('ongkirGlobal', () => this.autoSaveDraft());
                this.$watch('diskonOngkir', () => this.autoSaveDraft());
                this.$watch('customer_id', () => this.autoSaveDraft());

                // Reset modals when hidden (e.g., clicked outside)
                $('#jusModal').on('hidden.bs.modal', () => {
                    this.closeJusModal();
                });
                $('#jusEditModal').on('hidden.bs.modal', () => {
                    this.closeEditJusModal();
                });
                $('#productModal').on('hidden.bs.modal', () => {
                    this.closeAddModal();
                });
                $('#productEditModal').on('hidden.bs.modal', () => {
                    this.closeEditModal();
                });
                $('#parcelModal').on('hidden.bs.modal', () => {
                    if (typeof this.closeParcelModal === 'function') this.closeParcelModal();
                });
                $('#parcelEditModal').on('hidden.bs.modal', () => {
                    if (typeof this.closeEditParcelModal === 'function') this.closeEditParcelModal();
                });

                // Add listeners for Select2 changes
                ['#branch_id', '#branch_process_id', '#customer_id', '#courier_id', '#address_id'].forEach(selector => {
                    $(selector).on('change', () => this.autoSaveDraft());
                });

                // Add listener for date and invoice (though invoice is read-only)
                $('input[name="date"], textarea[name="note"], input[name="ongkir_date"], input[name="ongkir_time"]').on(
                    'change input', () => this.autoSaveDraft());
            },

            setMinimalPurchase(value) {
                this.minimalPurchase = value;
                // console.log('Minimal Purchase set to:', this.minimalPurchase);
            },

            setVoucher(value) {
                this.voucher = value;
                // console.log('Voucher set to:', this.voucher);
            },

            setDiscountGlobal(value) {
                this.diskonGlobal = value;
                // console.log('Discount Global set to:', this.diskonGlobal);
            },

            calculateUsedStock(productId, currentParcelItems = null, currentParcelQty = 1) {
                let used = 0;
                const mainApp = window.mainCartInstance || this;
                
                let targetStockId = window.productStockMap?.[productId] || productId;

                if (mainApp.cart) {
                    mainApp.cart.forEach(c => {
                        let currentStockId = window.productStockMap?.[c.id] || c.id;
                        if (currentStockId == targetStockId && c.typeProduct !== 'parcel' && c.typeProduct !== 'jus' && c.type !== 'jus' && c.status !== 'receipt') {
                            used += Number(c.qty || 0);
                        }

                        if (c.data && c.data.products && c.data.products.length > 0) {
                            c.data.products.forEach((pId, idx) => {
                                if (pId) {
                                    let ingStockId = window.productStockMap?.[pId] || pId;
                                    if (ingStockId == targetStockId) {
                                        let rawQty = c.data.productsQty && c.data.productsQty[idx] ? String(c.data.productsQty[idx]).replace(',', '.') : "0";
                                        const qty = Number(rawQty) || 0;
                                        used += qty * (Number(c.qty) || 1);
                                    }
                                }
                            });
                        } else if (c.product_receipt && c.product_receipt.length > 0 && (c.typeProduct === 'jus' || c.type === 'jus' || c.status === 'receipt')) {
                            // If they added Jus directly from the product list
                            c.product_receipt.forEach((receipt) => {
                                let ingId = receipt.ingredients ? receipt.ingredients.id : (receipt.product_receipt_id || receipt.product_id);
                                if (ingId) {
                                    let ingStockId = window.productStockMap?.[ingId] || ingId;
                                    if (ingStockId == targetStockId) {
                                        let rawQty = receipt.quantity ? String(receipt.quantity).replace(',', '.') : "0";
                                        const qty = Number(rawQty) || 0;
                                        used += qty * (Number(c.qty) || 1);
                                    }
                                }
                            });
                        }
                    });
                }
                
                if (mainApp.parcel) {
                    mainApp.parcel.forEach(p => {
                        if (p.data && Array.isArray(p.data)) {
                            used += p.data.filter(ing => {
                                let currentStockId = window.productStockMap?.[ing.id || ing.product] || (ing.id || ing.product);
                                return currentStockId == targetStockId;
                            }).reduce((sum, ing) => sum + (Number(ing.qty) * Number(p.qty)), 0);
                        }
                    });
                }
                
                if (currentParcelItems && Array.isArray(currentParcelItems)) {
                    used += currentParcelItems.filter(p => {
                        let currentStockId = window.productStockMap?.[p.id || p.product] || (p.id || p.product);
                        return currentStockId == targetStockId;
                    }).reduce((sum, p) => sum + (Number(p.qty) * Number(currentParcelQty)), 0);
                }



                return used;
            },

            formatRupiah(value) {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0
                }).format(value);
            },


            get formattedPrice() {
                return this.formatRupiah(this.modalTotalPrice);
            },

            set formattedPrice(val) {
                const raw = val.replace(/\./g, '').replace(/[^0-9]/g, '');
                this.modalTotalPrice = Number(raw || 0);
                this.updateQtyFromTotal();
            },

            submitTransaction(doneCallback) {
                if (!this.cart.length) {
                    alert('Keranjang masih kosong!');
                    return;
                }

                const customerId = document.getElementById('customer_id').value;
                // if (!customerId) {
                //     alert('Silakan pilih customer terlebih dahulu.');
                //     return;
                // }

                const payload = {
                    customer_id: customerId,
                    items: this.cart.map(item => ({
                        product_id: item.id,
                        qty: item.qty,
                        price: item.price,
                        hpp: item.hpp,
                        discount: item.discount,
                        total_input: item.total_input
                    }))
                };
                fetch('/pos/submitTransaction', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                'content')
                        },
                        body: JSON.stringify(payload)
                    })
                    .then(res => res.json())
                    .then(async res => {
                        const json = await res.json().catch(() => ({}));

                        // 🔥 Jika error (422, 500, dll)
                        if (!res.ok) {

                            // Jika VALIDASI ERROR
                            if (json.errors) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Validasi gagal',
                                    html: Object.values(json.errors)
                                        .map(msg => `<div>${msg}</div>`)
                                        .join('')
                                });
                                if (typeof doneCallback === 'function') doneCallback();
                            } else {
                                // Error lain
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Gagal',
                                    text: json.message || 'Terjadi kesalahan',
                                });
                                if (typeof doneCallback === 'function') doneCallback();
                            }

                            throw new Error("Request Failed");
                        }

                        // 🔥 SUCCESS → langsung redirect
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: 'Data berhasil disimpan.'
                        });
                        this.cart = []; // Kosongkan keranjang
                        document.getElementById('customer_id').value = '';
                        $('#customer_id').val(null).trigger('change');
                        window.location.href = '/pos/show/' + data.id;

                        if (typeof doneCallback === 'function') doneCallback();
                    })
                    .catch(err => {
                        console.error(err);
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: err || 'Terjadi kesalahan saat menyimpan data.'
                        });
                    });
            },

            // Edit Product Section
            openEditModal(item) {
                console.log('Opening edit modal for item:', item);
                this.editItem = {
                    ...item
                }; // salin data item
                this.editProduct = item;
                this.editProductName = item.name;
                this.editProductUnit = item.unit;
                this.editTitle = item.name;
                this.editPrice = item.price;
                this.editQty = item.qty;
                this.editTotal = (item.total_input !== undefined && item.total_input !== null) ? item.total_input : (item.qty * item.price);
                this.editTotalFormatted = this.formatRupiah(this.editTotal);

                this.editDiscount = item.discount || 0;
                this.editDiscountPercent = item.discountPercent || 0;
                if (this.editDiscountPercent > 0) {
                    this.editDiscountNominal = this.editDiscountPercent;
                } else {
                    this.editDiscountNominal = this.editDiscount;
                }

                this.editModal = true;

                setTimeout(() => {
                    const modal = new bootstrap.Modal(document.getElementById('editModal'));
                    modal.show();
                }, 0);
            },

            updateEditTotalFormatted(e) {
                const raw = e.target.value.replace(/[^\d]/g, '');
                this.editTotal = Number(raw || 0);
                this.editTotalFormatted = this.formatRupiah(this.editTotal);
                this.updateQtyFromEditTotal(); // Sesuaikan qty berdasarkan harga
            },

            updateQtyFromEditTotalFormatted(e) {
                let raw = e.target.value.replace(/\./g, '').replace(/[^0-9]/g, '');
                let inputTotal = parseFloat(raw || 0);
                const price = parseFloat(this.editPrice || 1);
                
                let input = parseFloat(this.editDiscountNominal || 0);
                let isPercent = (input <= 100 && input > 0);
                let qty;
                let totalDiscount;
                if (isPercent) {
                    let discountedPrice = price * (1 - input / 100);
                    if (discountedPrice === 0) {
                        qty = parseFloat(this.editQty || 1);
                        totalDiscount = qty * price;
                        inputTotal = 0;
                        e.target.value = this.formatRupiah(0);
                    } else {
                        qty = inputTotal / discountedPrice;
                        totalDiscount = qty * price * (input / 100);
                    }
                } else {
                    let qty_A = (inputTotal + input) / price;
                    let qty_B = (price - input > 0) ? (inputTotal / (price - input)) : (inputTotal === 0 ? 1 : -1);
                    if (qty_A < 1) {
                        qty = qty_A;
                        totalDiscount = input;
                    } else if (qty_B >= 1) {
                        qty = qty_B;
                        totalDiscount = qty * input;
                    } else {
                        qty = qty_A;
                        totalDiscount = input;
                    }
                }
                
                this.editQty = parseFloat(qty.toFixed(2));
                this.editTotal = inputTotal;
                this.editDiscount = totalDiscount;
                this.editTotalFormatted = this.formatRupiah(inputTotal);
            },

            updateQtyFromEditTotal() {
                let price = parseFloat(this.editPrice || 1);
                let qty = (parseFloat(this.editTotal || 0) + parseFloat(this.editDiscount || 0)) / price;

                if (qty > 0) {
                    this.editQty = parseFloat(qty.toFixed(2));
                }
            },

            updateTotalFromEditQty() {
                let qty = parseFloat(this.editQty || 0);
                let price = parseFloat(this.editPrice || 0);
                let input = parseFloat(this.editDiscountNominal || 0);
                let isPercent = (input <= 100 && input > 0);
                if (!isPercent) {
                    let maxDiscount = qty < 1 ? (qty * price) : price;
                    if (input > maxDiscount) {
                        input = maxDiscount;
                        this.editDiscountNominal = input;
                    }
                }
                
                let totalDiscount = isPercent ? (qty * price * (input / 100)) : (qty < 1 ? input : (qty * input));
                this.editDiscount = totalDiscount;
                
                this.editTotal = parseFloat((qty * price - totalDiscount).toFixed(2));
                this.editTotalFormatted = this.formatRupiah(this.editTotal);
            },
            calculateEditDiscountAmount() {
                const val = parseFloat(this.editDiscountNominal || 0);
                let qty = parseFloat(this.editQty || 0);
                // console.log('Discount:', val);
                if (val <= 100 && val > 0) {
                    let originalPrice = parseFloat(this.editPrice || 0);
                    let subtotal = qty * originalPrice;
                    return parseFloat(((subtotal || 0) * val / 100).toFixed(2)); // persen
                } else {
                    return qty < 1 ? val : (val * qty); // nominal
                }
            },
            // Update otomatis qty berdasarkan total
            updateEditQtyFromTotal() {
                this.updateQtyFromEditTotal();
            },

            // Update total otomatis berdasarkan qty
            updateEditTotalFromQty() {
                this.updateTotalFromEditQty();
            },

            updateEditDiscountValue(e) {
                let raw = e.target.value.replace(/\./g, '').replace(/[^0-9]/g, '');
                let input = parseFloat(raw || 0);
                const qty = parseFloat(this.editQty || 0);
                const price = parseFloat(this.editPrice || 0);
                let isPercent = (input <= 100 && input > 0);
                if (!isPercent) {
                    let maxDiscount = qty < 1 ? (qty * price) : price;
                    if (input > maxDiscount) {
                        input = maxDiscount;
                    }
                }
                
                e.target.value = this.formatRupiah(input);
                this.editDiscountNominal = input;
                
                let totalDiscount = isPercent ? (qty * price * (input / 100)) : (qty < 1 ? input : (qty * input));
                let totalAfterDiscount = (qty * price) - totalDiscount;
                
                this.editDiscount = totalDiscount;
                this.editDiscountPercent = (input <= 100 && input > 0) ? input : 0;
                
                this.editTotal = totalAfterDiscount;
                this.editTotalFormatted = this.formatRupiah(totalAfterDiscount);
            },

            updateEditDiscount() {
                let qty = parseFloat(this.editQty || 0);
                let price = parseFloat(this.editPrice || 0);
                let input = parseFloat(this.editDiscountNominal || 0);
                let isPercent = (input <= 100 && input > 0);
                if (!isPercent) {
                    let maxDiscount = qty < 1 ? (qty * price) : price;
                    if (input > maxDiscount) {
                        input = maxDiscount;
                        this.editDiscountNominal = input;
                    }
                }

                let totalDiscount = isPercent ? (qty * price * (input / 100)) : (qty < 1 ? input : (qty * input));
                this.editDiscountPercent = (input <= 100 && input > 0) ? input : 0;
                this.editDiscount = totalDiscount;
                
                this.editTotal = parseFloat(((qty * price) - totalDiscount).toFixed(2));
                this.editTotalFormatted = this.formatRupiah(this.editTotal);
            },

            saveEditToCart() {
                if (this.editQty <= 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Kuantitas tidak valid',
                        text: 'Jumlah produk harus lebih dari 0.',
                    });
                    return;
                }
                
                const usedByOthers = this.calculateUsedStock(this.editItem.id) - (this.editItem.qty || 0);
                const availableForEdit = (this.editItem.original_stock ?? 0) - usedByOthers;

                if (this.editItem.original_stock !== undefined && parseFloat(this.editQty) > availableForEdit && this.editItem.typeProduct === 'product') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Stok tidak mencukupi',
                        text: 'Sisa stok tersedia hanya ' + availableForEdit + '.',
                    });
                    return;
                }

                const idx = this.cart.findIndex(i => i.key === this.editItem.key);
                if (idx !== -1) {
                    const disc = this.calculateEditDiscountAmount();
                    this.cart[idx].qty = this.editQty;
                    this.cart[idx].total_input = this.editTotal;
                    this.cart[idx].discount = disc;
                    this.cart[idx].discountPercent = this.editDiscountPercent;
                }
                this.closeEditModal();
            },

            deleteFromCart() {
                if (!this.editItem) return;

                const index = this.cart.findIndex(item => item.id === this.editItem.id);
                if (index !== -1) {
                    this.cart.splice(index, 1);

                    const jusIndex = this.jus.findIndex(item => item.id === this.editItem.id);
                    if(jusIndex !== -1) {
                        this.jus.splice(jusIndex, 1);
                    }

                    const parcelIndex = this.parcel.findIndex(item => item.id === this.editItem.id);
                    if(parcelIndex !== -1) {
                        this.parcel.splice(parcelIndex, 1);
                    }

                    this.closeEditModal();
                    if(typeof this.closeEditJusModal === 'function') {
                        this.closeEditJusModal();
                    }
                }
            },

            // End edit modal section

            closeEditModal() {
                this.editModal = false;
                const modalEl = document.getElementById('editModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
                
                this.editItem = null;
                this.editPrice = 0;
                this.editQty = 1;
                this.editTotal = 0;
                this.editDiscount = 0;
                this.editDiscountPercent = 0;
                this.editDiscountNominal = 0;
                this.editTotalFormatted = '';
                this.editProductName = '';
                this.editProductUnit = '';
            },

            openEditJusModal(item) {
                console.log('Opening edit jus modal for item:', item);
                this.editItem = { ...item };
                this.editProduct = item;
                this.editProductName = item.name;
                this.editProductUnit = item.unit;
                this.editTitle = item.name;
                this.editPrice = item.price;
                this.editQty = item.qty;
                this.editTotal = (item.total_input !== undefined && item.total_input !== null) ? item.total_input : (item.qty * item.price);
                this.editTotalFormatted = this.formatRupiah(this.editTotal);
                
                this.editDiscount = item.discount || 0;
                this.editDiscountPercent = item.discountPercent || 0;
                if (this.editDiscountPercent > 0) {
                    this.editDiscountNominal = this.editDiscountPercent;
                } else {
                    this.editDiscountNominal = this.editDiscount;
                }
                
                this.editJusModal = true;

                const container = $('#receiptEditContainer');
                container.empty();

                if (item.data && item.data.products && item.data.products.length > 0) {
                    item.data.products.forEach((prodId, idx) => {
                        let qty = item.data.productsQty[idx] || 1;
                        let originalStock = item.data.productsOriginalStock ? (item.data.productsOriginalStock[idx] || 0) : 0;
                        let row = `
                        <div class="row receipt-row mb-2">
                            <div class="col-9 mb-3">
                                <label class="form-label">Nama Produk</label>
                                <select name="receipt_edit_product_id[]" class="form-select receipt-edit-select" data-selected-id="${prodId}" data-original-stock="${originalStock}">
                                </select>
                            </div>
                            <div class="col-3 mb-3">
                                <label class="form-label">Qty</label>
                                <input type="number" name="receipt_edit_qty[]" class="form-control" value="${qty}">
                            </div>
                        </div>
                        <div class="row receipt-row mb-2">
                            <div class="col-12 mb-3 text-center text-muted">
                                <em>Quantity akan dihitung otomatis berdasarkan Jumlah yang akan dibeli</em>
                            </div>
                        </div>`;
                        container.append(row);
                    });
                } else {
                    let row = `
                        <div class="row receipt-row mb-2">
                            <div class="col-12 mb-3 text-center text-muted">
                                <em>Tidak ada bahan</em>
                            </div>
                        </div>`;
                    container.append(row);
                }

                container.find('.receipt-edit-select').each(function(idx) {
                    const selectedId = $(this).data('selected-id');
                    
                    $(this).select2({
                        placeholder: 'Pilih Produk',
                        dropdownParent: $('#editJusModal'),
                        ajax: {
                            url: '/ajax/listProduct',
                            dataType: 'json',
                            delay: 250,
                            data: function(params) {
                                return {
                                    term: params.term || '',
                                    branch: $('#branch_id').val(),
                                    limit: 10
                                };
                            },
                            processResults: data => {
                                window.productStockMap = window.productStockMap || {};
                                data.forEach(item => {
                                    window.productStockMap[item.id] = item.parent_product ? item.parent_product.parent_id : item.id;
                                });
                                return {
                                    results: data
                                        .filter(item => item.name != null)
                                        .map(item => {
                                            let qtyInCart = 0;
                                            const mainApp = window.mainCartInstance || (document.querySelector('[x-data="posApp()"]') ? document.querySelector('[x-data="posApp()"]').__x.$data : null);
                                            if (mainApp && typeof mainApp.calculateUsedStock === 'function') {
                                                qtyInCart = mainApp.calculateUsedStock(item.id);
                                            }
                                            let stock_available = (item.get_stock?.stock_available ?? 0) - qtyInCart;
                                            return {
                                                id: item.id,
                                                text: item.name || '',
                                                unit: item.unit,
                                                price: item.price,
                                                hpp: item.hpp,
                                                receipt: item.product_receipt,
                                                stock_available: stock_available,
                                                original_stock: item.get_stock?.stock_available ?? 0,
                                                parent_id: item.parent_product ? item.parent_product.parent_id : item.id
                                            };
                                        })
                                };
                            }
                        }
                    });

                    if (selectedId) {
                        const originalStockVal = parseFloat($(this).attr('data-original-stock')) || 0;
                        let selectedText = "Bahan " + selectedId;
                        if (item.data && item.data.productsText && item.data.productsText[idx]) {
                            selectedText = item.data.productsText[idx];
                        }
                        let option = new Option(selectedText, selectedId, true, true);
                        $(option).attr('data-original-stock', originalStockVal);
                        $(this).append(option).trigger('change');
                    }
                });

                setTimeout(() => {
                    const modal = new bootstrap.Modal(document.getElementById('editJusModal'));
                    modal.show();
                }, 0);
            },

            closeEditJusModal() {
                this.editJusModal = false;
                const modalEl = document.getElementById('editJusModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
                
                this.editItem = null;
                this.editPrice = 0;
                this.editQty = 1;
                this.editTotal = 0;
                this.editDiscount = 0;
                this.editDiscountPercent = 0;
                this.editDiscountNominal = 0;
                this.editTotalFormatted = '';
                this.editProductName = '';
                this.editProductUnit = '';
                
                const container = $('#receiptEditContainer');
                container.empty();
            },

            saveEditJusToCart() {
                const idx = this.cart.findIndex(i => i.key === this.editItem.key);
                if (idx !== -1) {
                    if (this.editQty <= 0) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Kuantitas tidak valid',
                            text: 'Jumlah produk harus lebih dari 0.',
                        });
                        return;
                    }
                    const disc = this.calculateEditDiscountAmount();
                    this.cart[idx].qty = this.editQty;
                    this.cart[idx].total_input = this.editTotal;
                    this.cart[idx].discount = disc;
                    this.cart[idx].discountPercent = this.editDiscountPercent;

                    // Gunakan this.value (native DOM) bukan $(this).val() untuk
                    // menghindari Select2's $.fn.val override yang memanggil toLowerCase()
                    let receiptProducts = $('select[name="receipt_edit_product_id[]"]')
                        .map(function() { return this.value; }).get();
                    let receiptProductsQty = $('input[name="receipt_edit_qty[]"]')
                        .map(function() { return this.value; })
                        .get();

                    if (receiptProductsQty.some(qty => parseFloat(qty) <= 0)) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Kuantitas tidak valid',
                            text: 'Quantity product (bahan) harus lebih dari 0.',
                        });
                        return;
                    }
                    
                    let stockErrors = [];
                    $('select[name="receipt_edit_product_id[]"]').each((index, el) => {
                        let select2Data = $(el).select2('data');
                        if (select2Data && select2Data.length > 0 && select2Data[0].original_stock !== undefined && select2Data[0].original_stock !== null) {
                            var originalStock = parseFloat(select2Data[0].original_stock) || 0;
                        } else {
                            var originalStock = parseFloat($(el).find('option:selected').attr('data-original-stock')) || parseFloat($(el).attr('data-original-stock')) || 0;
                        }
                        
                        if (select2Data && select2Data.length > 0 && select2Data[0].id) {
                            let productId = select2Data[0].id;
                            let productName = select2Data[0].text;
                            
                            // Gunakan native .value pada input qty
                            let qtyNeed = parseFloat($('input[name="receipt_edit_qty[]"]').eq(index)[0].value) || 0;
                            let totalQtyNeed = qtyNeed * (parseFloat(this.editQty) || 1);
                            let totalUsed = this.calculateUsedStock(productId);
                            
                            // Kurangi qty yang sudah dipakai item ini (karena sedang diedit)
                            let oldUsed = 0;
                            if (this.editItem && this.editItem.data && this.editItem.data.products) {
                                // gunakan == (loose) agar int 5 cocok dengan string "5"
                                let pIdx = this.editItem.data.products.findIndex(p => p == productId);
                                if (pIdx !== -1) {
                                    let oldQtyPerUnit = parseFloat(this.editItem.data.productsQty[pIdx]) || 0;
                                    oldUsed = oldQtyPerUnit * (parseFloat(this.editItem.qty) || 0);
                                }
                            }
                            totalUsed -= oldUsed;

                            if ((totalUsed + totalQtyNeed) > originalStock) {
                                stockErrors.push(`- ${productName} (Sisa stok: ${Math.round((originalStock - totalUsed) * 100) / 100} [DEBUG: OS=${originalStock}, TU=${totalUsed}, QN=${qtyNeed}])`);
                            }
                        }
                    });

                    if (stockErrors.length > 0) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Stok bahan tidak mencukupi',
                            text: 'Beberapa bahan tidak cukup stoknya:\n' + stockErrors.join('\n'),
                        });
                        return;
                    }

                    // Gunakan native .text pada option untuk menghindari Select2 override
                    let receiptProductsText = $('select[name="receipt_edit_product_id[]"] option:selected')
                        .map(function() { return this.text; }).get();

                    let receiptProductsOriginalStock = [];
                    $('select[name="receipt_edit_product_id[]"]').each((index, el) => {
                        let select2Data = $(el).select2('data');
                        if (select2Data && select2Data.length > 0 && select2Data[0].original_stock !== undefined && select2Data[0].original_stock !== null) {
                            receiptProductsOriginalStock.push(parseFloat(select2Data[0].original_stock) || 0);
                        } else {
                            receiptProductsOriginalStock.push(parseFloat($(el).find('option:selected').attr('data-original-stock')) || parseFloat($(el).attr('data-original-stock')) || 0);
                        }
                    });

                    this.cart[idx].data = {
                        products: receiptProducts,
                        productsQty: receiptProductsQty,
                        productsText: receiptProductsText,
                        productsOriginalStock: receiptProductsOriginalStock
                    };

                    let jusIdx = this.jus.findIndex(i => i.id === this.editItem.id);
                    if (jusIdx !== -1) {
                        this.jus[jusIdx].qty = this.editQty;
                        this.jus[jusIdx].total_input = this.editTotal;
                        this.jus[jusIdx].discount = disc;
                        this.jus[jusIdx].discountPercent = this.editDiscountPercent;
                        this.jus[jusIdx].product_receipt_id = [...receiptProducts];
                        this.jus[jusIdx].product_receipt_qty = [...receiptProductsQty];
                    }
                }
                this.closeEditJusModal();
            },

            // Add Product Section
            openAddModal() {
                this.showAddModal = true;
                setTimeout(() => {
                    const modal = new bootstrap.Modal(document.getElementById('addModal'));
                    modal.show();

                    // Init select2
                    $('#select_product').select2({
                        placeholder: 'Pilih produk',
                        dropdownParent: $('#addModal'),
                        ajax: {
                            url: '/ajax/listProduct', // ganti sesuai route
                            dataType: 'json',
                            delay: 250,
                            data: function(params) {
                                return {
                                    term: params.term,
                                    branch: $('#branch_id').val(),
                                    status: 'aktif', // contoh nilai statis
                                    limit: 10 // contoh parameter tambahan
                                };
                            },
                            processResults: data => {
                                window.productStockMap = window.productStockMap || {};
                                data.forEach(item => {
                                    window.productStockMap[item.id] = item.parent_product ? item.parent_product.parent_id : item.id;
                                });
                                return {
                                    results: data.map(item => {
                                        let qtyInCart = 0;
                                        if (typeof this.calculateUsedStock === 'function') {
                                            qtyInCart = this.calculateUsedStock(item.id);
                                        } else {
                                            const mainApp = window.mainCartInstance || (document.querySelector('[x-data="posApp()"]') ? document.querySelector('[x-data="posApp()"]').__x.$data : null);
                                            if (mainApp && typeof mainApp.calculateUsedStock === 'function') {
                                                qtyInCart = mainApp.calculateUsedStock(item.id);
                                            }
                                        }
                                        let stock_available = (item.get_stock?.stock_available ?? 0) - qtyInCart;
                                        return {
                                            id: item.id,
                                            text: item.name,
                                            unit: item.unit,
                                            price: item.price,
                                            hpp: item.hpp,
                                            receipt: item.product_receipt,
                                            stock_available: stock_available,
                                            original_stock: item.get_stock?.stock_available ?? 0,
                                            parent_id: item.parent_product ? item.parent_product.parent_id : item.id
                                        };
                                    })
                                };
                            }
                        },
                        templateResult: data => {
                            if (data.loading) return data.text;
                            const stock = data.stock_available ?? 0;
                            const disabled = stock <= 0;
                            const $el = $(`<span class="${disabled ? 'text-muted' : ''}">${data.text} <span class="badge badge-light-${stock > 0 ? 'success' : 'danger'} ms-2">Stok: ${Math.round(stock * 100) / 100}</span></span>`);
                            if (disabled) {
                                $el.css('cursor', 'not-allowed');
                            }
                            return $el;
                        },
                        templateSelection: data => {
                            const stock = data.stock_available ?? 0;
                            if (stock <= 0) return $(`<span class="text-muted">${data.text} (Stok habis)</span>`);
                            return data.text;
                        }
                    }).on('select2:select', (e) => {
                        const data = e.params.data;
                        const stock = data.stock_available ?? 0;
                        if (stock <= 0) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Stok tidak mencukupi',
                                text: 'Produk ' + data.text + ' tidak memiliki stok yang cukup.',
                            });
                            $('#select_product').val(null).trigger('change');
                            return;
                        }
                        this.addProduct.id = data.id;
                        this.addProduct.name = data.text;
                        this.addProduct.unit = data.unit.abbreviation;
                        this.addProduct.price = data.price;
                        this.addProduct.hpp = data.hpp ?? 0;
                        this.addProduct.stock_available = data.stock_available ?? 0;
                        this.addProduct.original_stock = data.original_stock ?? 0;
                        this.addProduct.parent_id = data.parent_id ?? data.id;
                        subtotal = this.addProduct.qty * this.addProduct.price;
                        // skip formatRupiah undefined, let updateAddTotalFromQty handle it
                        this.updateAddTotalFromQty();
                    });
                }, 0);
            },
            closeAddModal() {
                this.showAddModal = false;
                const modal = bootstrap.Modal.getInstance(document.getElementById('addModal'));
                if (modal) modal.hide();
                
                this.addProduct = {
                    id: null,
                    name: '',
                    unit: '',
                    price: 0,
                    hpp: 0,
                    discount: 0,
                    discountNominal: 0,
                    discountPercent: 0,
                    qty: 1,
                    formattedAddTotalInput: this.formatRupiah(0)
                };
                $('#select_product').val(null).trigger('change');
            },
            updateAddTotalFromQty() {
                const qty = parseFloat(this.addProduct.qty) || 0;
                const price = parseFloat(this.addProduct.price) || 0;
                const input = parseFloat(this.addProduct.discountNominal || 0);
                let isPercent = (input <= 100 && input > 0);
                if (!isPercent) {
                    let maxDiscount = qty < 1 ? (qty * price) : price;
                    if (input > maxDiscount) {
                        input = maxDiscount;
                        this.addProduct.discountNominal = input;
                    }
                }
                
                let totalDiscount = isPercent ? (qty * price * (input / 100)) : (qty < 1 ? input : (qty * input));
                let totalAfterDiscount = (qty * price) - totalDiscount;
                
                this.addProduct.discount = totalDiscount;
                this.addProduct.total = totalAfterDiscount;
                this.addProduct.formattedAddTotalInput = this.formatRupiah(totalAfterDiscount);
            },
            updateQtyFromAddTotal(e) {
                let raw = e.target.value.replace(/\./g, '').replace(/[^0-9]/g, '');
                let inputTotal = parseFloat(raw || 0);
                const price = parseFloat(this.addProduct.price || 1);
                
                let input = parseFloat(this.addProduct.discountNominal || 0);
                let isPercent = (input <= 100 && input > 0);
                
                let qty;
                let totalDiscount;
                if (isPercent) {
                    let discountedPrice = price * (1 - input / 100);
                    if (discountedPrice === 0) {
                        qty = parseFloat(this.addProduct.qty || 1);
                        totalDiscount = qty * price;
                        inputTotal = 0;
                        e.target.value = this.formatRupiah(0);
                    } else {
                        qty = inputTotal / discountedPrice;
                        totalDiscount = qty * price * (input / 100);
                    }
                } else {
                    let qty_A = (inputTotal + input) / price;
                    let qty_B = (price - input > 0) ? (inputTotal / (price - input)) : (inputTotal === 0 ? 1 : -1);
                    if (qty_A < 1) {
                        qty = qty_A;
                        totalDiscount = input;
                    } else if (qty_B >= 1) {
                        qty = qty_B;
                        totalDiscount = qty * input;
                    } else {
                        qty = qty_A;
                        totalDiscount = input;
                    }
                }
                
                this.addProduct.qty = parseFloat(qty.toFixed(2));
                this.addProduct.total = inputTotal;
                this.addProduct.discount = totalDiscount;
                this.addProduct.formattedAddTotalInput = this.formatRupiah(inputTotal);
            },

            updateDiscountValue(e) {
                let raw = e.target.value.replace(/\./g, '').replace(/[^0-9]/g, '');
                let input = parseFloat(raw || 0);
                const qty = parseFloat(this.addProduct.qty || 0);
                const price = parseFloat(this.addProduct.price || 0);
                let isPercent = (input <= 100 && input > 0);
                if (!isPercent) {
                    let maxDiscount = qty < 1 ? (qty * price) : price;
                    if (input > maxDiscount) {
                        input = maxDiscount;
                    }
                }
                
                e.target.value = this.formatRupiah(input);
                this.addProduct.discountNominal = input;
                
                let totalDiscount = isPercent ? (qty * price * (input / 100)) : (qty < 1 ? input : (qty * input));
                let totalAfterDiscount = (qty * price) - totalDiscount;
                
                this.addProduct.discount = totalDiscount;
                this.addProduct.discountPercent = (input <= 100 && input > 0) ? input : 0;
                
                this.addProduct.total = totalAfterDiscount;
                this.addProduct.formattedAddTotalInput = this.formatRupiah(totalAfterDiscount);
            },
            formatRupiah(angka) {
                return angka.toLocaleString('id-ID');
            },
            get formattedAddPrice() {
                return this.formatRupiah(this.addProduct.price);
            },
            set formattedAddPrice(val) {
                const raw = val.replace(/\./g, '').replace(/[^0-9]/g, '');
                this.addProduct.price = Number(raw || 0);
                this.updateAddTotalFromQty();
            },
            get formattedAddTotal() {
                return this.formatRupiah(this.addProduct.qty * this.addProduct.price);
            },
            saveAddToCart() {
                if (!this.addProduct.id) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Produk belum dipilih',
                        text: 'Silakan pilih produk terlebih dahulu.',
                    });
                    return;
                }
                if (this.addProduct.price === null || this.addProduct.price === '') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Harga produk belum diisi',
                        text: 'Silakan isi harga produk terlebih dahulu.',
                    });
                    return;
                }
                if (this.addProduct.qty <= 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Kuantitas tidak valid',
                        text: 'Jumlah produk harus lebih dari 0.',
                    });
                    return;
                }
                
                let currentStock = parseFloat(this.addProduct.stock_available) || 0;
                if (parseFloat(this.addProduct.qty) > currentStock) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Stok tidak mencukupi',
                        text: 'Stok tersedia hanya ' + currentStock + ', sedangkan kuantitas yang diminta adalah ' + this.addProduct.qty + '.',
                    });
                    return;
                }

                // const isExist = this.cart.some(item => item.id === this.addProduct.id);
                // if (isExist) {
                //     Swal.fire({
                //         icon: 'warning',
                //         title: 'Produk sudah ditambahkan',
                //         text: 'Produk ini sudah ada di keranjang.',
                //     });
                //     return;
                // }

                const discount = Number(this.addProduct.discount || 0);
                const total_input = this.addProduct.total;
                const key = Date.now() + Math.floor(Math.random() * 1000);

                this.cart.push({
                    key: key,
                    id: this.addProduct.id,
                    name: this.addProduct.name,
                    price: this.addProduct.price,
                    hpp: this.addProduct.hpp,
                    qty: this.addProduct.qty,
                    unit: this.addProduct.unit,
                    discount: discount,
                    discountPercent: this.addProduct.discountPercent,
                    total_input: total_input,
                    typeProduct: 'product',
                    original_stock: this.addProduct.original_stock,
                    parent_id: this.addProduct.parent_id,
                });

                // console.log('cart', this.cart);
                this.resetAddForm();
            },

            resetAddForm() {
                this.addProduct = {
                    id: null,
                    name: '',
                    unit: '',
                    price: 0,
                    hpp: 0,
                    discount: 0,
                    qty: 1
                };
                // Reset Select2 input juga
                $('#select_product').val(null).trigger('change');
            },
            // End Add Product Section

            // Total
            totalProduk() {
                // return this.cart.reduce((sum, item) => sum + Number(item.qty), 0);
                return this.cart.length;
            },


            showCartModal: false, // di dalam return {...}
            openCartModal() {
                this.showCartModal = true;
                setTimeout(() => {
                    const modal = new bootstrap.Modal(document.getElementById('cartModal'));
                    modal.show();
                }, 0);
            },

            closeCartModal() {
                this.showCartModal = false;
                const modalEl = document.getElementById('cartModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            },

            // Update quantity item di cart
            updateCartItemQty(itemId, newQty) {
                if (newQty < 0.01) return; // Minimal qty 0.01

                const item = this.cart.find(i => i.id === itemId);
                if (item) {
                    const oldQty = item.qty;
                    let targetQty = parseFloat(newQty);

                    let usedOther = this.calculateUsedStock(item.id) - oldQty;
                    let maxAllowed = (parseFloat(item.original_stock) || 0) - usedOther;
                    
                    if (targetQty > maxAllowed) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Stok tidak mencukupi',
                            text: 'Sisa stok tersedia hanya ' + maxAllowed + '.',
                        });
                        targetQty = maxAllowed;
                        if (targetQty < 0.01) return; // Prevent setting to 0
                    }
                    item.qty = targetQty;

                    // Recalculate total_input proportionally
                    if (item.total_input && oldQty > 0) {
                        const originalTotal = item.total_input + item.discount;
                        const pricePerUnit = originalTotal / oldQty;
                        const discountPerUnit = item.discount / oldQty;
                        
                        item.discount = discountPerUnit * item.qty;
                        item.total_input = (pricePerUnit * item.qty) - item.discount;
                    } else {
                        item.total_input = item.price * item.qty;
                    }
                }
            },

            // Update discount item di cart
            updateCartItemDiscount(itemId, newDiscount) {
                const item = this.cart.find(i => i.id === itemId);
                if (item) {
                    item.discount = parseFloat(newDiscount) || 0;
                }
            },

            // Increment quantity
            incrementQty(itemId) {
                const item = this.cart.find(i => i.id === itemId);
                if (item) {
                    this.updateCartItemQty(itemId, item.qty + 1);
                }
            },

            // Decrement quantity
            decrementQty(itemId) {
                const item = this.cart.find(i => i.id === itemId);
                if (item && item.qty > 0.01) {
                    this.updateCartItemQty(itemId, Math.max(0.01, item.qty - 1));
                }
            },

            // Method untuk menghitung total quantity (jumlah items)
            getTotalQuantity() {
                return this.cart.reduce((total, item) => total + parseFloat(item.qty || 0), 0);
            },

            // Method untuk menghitung total price
            getTotalPrice() {
                return this.cart.reduce((sum, item) => {
                    const itemTotal = item.total_input !== undefined ? item.total_input : ((item.price * item.qty) - (item.discount || 0));
                    return sum + Math.max(0, itemTotal); // Pastikan tidak minus
                }, 0);
            },

            // Method untuk rincian total
            sanitizeNumber(value) {
                if (value == null) return null;

                if (typeof value === "string") {
                    return parseFloat(value.replace(/[^\d]/g, "")) || 0;
                }

                if (typeof value === "number") {
                    return value;
                }

                return 0; // fallback kalau tipenya aneh
            },

            get subtotal() {
                return this.cart.reduce((sum, item) => {
                    // Membersihkan total_input dari karakter non-angka
                    const cleanTotalInput = this.sanitizeNumber(item.total_input);

                    const total = (cleanTotalInput || (item.price * item.qty) - (item.discount || 0));
                    return sum + total;
                }, 0);
            },
            get totalHargaKeseluruhan() {
                const diskon = this.diskonGlobal;
                const ongkir = this.ongkirGlobal;
                const diskonOngkir = this.diskonOngkir;

                let totalSetelahDiskon = 0;
                let totalOngkir = 0;
                if (diskon > 0 && diskon <= 100) {
                    // Diskon persen
                    totalSetelahDiskon = this.subtotal - (this.subtotal * (diskon / 100));
                } else {
                    // Diskon nominal
                    totalSetelahDiskon = this.subtotal - diskon;
                }

                if (diskonOngkir > 0 && diskonOngkir <= 100) {
                    // Diskon persen
                    totalOngkir = ongkir - (ongkir * (diskonOngkir / 100));
                } else {
                    // Diskon nominal
                    totalOngkir = ongkir - diskonOngkir;
                }

                this.checkGiftButton(totalSetelahDiskon);
                // Ongkir harus selalu ditambahkan
                return Math.max(totalSetelahDiskon + totalOngkir, 0);
            },
            updateDiskonGlobal(e) {
                let cursor = e.target.selectionStart;
                let oldLength = e.target.value.length;
                let val = parseFloat(e.target.value.replace(/[^\d]/g, '')) || 0;
                
                let isPercent = (val <= 100 && val > 0);
                if (!isPercent) {
                    if (val > this.subtotal) {
                        val = this.subtotal;
                    }
                }
                
                e.target.value = this.formatRupiah(val);
                this.diskonGlobal = val;
                this.$nextTick(() => {
                    let newLength = e.target.value.length;
                    cursor = cursor + (newLength - oldLength);
                    e.target.setSelectionRange(cursor, cursor);
                });
            },
            updateOngkirGlobal(e) {
                let cursor = e.target.selectionStart;
                let oldLength = e.target.value.length;
                let val = parseFloat(e.target.value.replace(/[^\d]/g, '')) || 0;
                this.ongkirGlobal = val;
                
                // If ongkir changes, check if diskonOngkir > ongkirGlobal
                let isPercent = (this.diskonOngkir <= 100 && this.diskonOngkir > 0);
                if (!isPercent && this.diskonOngkir > this.ongkirGlobal) {
                    this.diskonOngkir = this.ongkirGlobal;
                }
                
                e.target.value = this.formatRupiah(val);
                this.$nextTick(() => {
                    let newLength = e.target.value.length;
                    cursor = cursor + (newLength - oldLength);
                    e.target.setSelectionRange(cursor, cursor);
                });
            },
            updateDiskonOngkir(e) {
                let cursor = e.target.selectionStart;
                let oldLength = e.target.value.length;
                let val = parseFloat(e.target.value.replace(/[^\d]/g, '')) || 0;
                
                let isPercent = (val <= 100 && val > 0);
                if (!isPercent) {
                    if (val > this.ongkirGlobal) {
                        val = this.ongkirGlobal;
                    }
                }
                
                e.target.value = this.formatRupiah(val);
                this.diskonOngkir = val;
                this.$nextTick(() => {
                    let newLength = e.target.value.length;
                    cursor = cursor + (newLength - oldLength);
                    e.target.setSelectionRange(cursor, cursor);
                });
            },
            formatRupiah(number) {
                number = this.sanitizeNumber(number) || 0;
                return number.toLocaleString("id-ID");
            },

            // Action save Transaction
            saveTransaction(doneCallback) {
                if (this.cart.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Keranjang kosong',
                        text: 'Silakan tambahkan produk terlebih dahulu!',
                    });
                    if (typeof doneCallback === 'function') doneCallback();
                    return;
                }

                if (this.autoSaveTimeout) {
                    clearTimeout(this.autoSaveTimeout);
                    this.autoSaveTimeout = null;
                }
                this.autoSaveQueued = false;

                const customerId = document.querySelector('select[name="customer_id"]').value;
                const transactionDate = document.querySelector('input[name="date"]').value;
                const invoiceNumber = this.currentInvoiceNumber || document.querySelector('input[name="invoice_number"]').value;
                const ongkirDate = document.querySelector('input[name="ongkir_date"]').value;
                const ongkirTime = document.querySelector('input[name="ongkir_time"]').value;
                const note = document.querySelector('textarea[name="note"]').value;
                const courierId = document.querySelector('select[name="courier_id"]').value;
                const branchId = document.querySelector('select[name="branch_id"]').value;
                const branchProcessId = document.querySelector('select[name="branch_process_id"]').value;
                // const ongkirAddress = document.querySelector('textarea[name="ongkir_address"]').value;
                const ongkirAddress = document.querySelector('select[name="ongkir_address"]').value;

                if (this.ongkirGlobal > 0 && (courierId == null || courierId == '')) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Kurir belum dipilih',
                        text: 'Anda memasukkan biaya ongkir, tetapi tidak memilih kurir!',
                    });
                    if (typeof doneCallback === 'function') doneCallback();
                    return;
                }


                const data = {
                    customer_id: customerId,
                    date: transactionDate,
                    invoice_number: invoiceNumber,
                    items: this.cart,
                    parcel: this.parcel,
                    jus: this.jus,
                    subtotal: this.subtotal,
                    discount: this.diskonGlobal,
                    ongkir: this.ongkirGlobal,
                    discount_ongkir: this.diskonOngkir,
                    ongkir_date: ongkirDate,
                    ongkir_time: ongkirTime,
                    total: this.totalHargaKeseluruhan,
                    status: 'draft',
                    note: note,
                    courier_id: courierId,
                    courier_type: document.querySelector('select[name="courier_type"]')?.value || null,
                    ongkir_address: ongkirAddress,
                    branch_id: branchId,
                    branch_process_id: branchProcessId,
                };

                // Simulasi kirim ke server
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                fetch('/pos/save-transaction', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(data, getCircularReplacer()),
                    })
                    .then(async (res) => {
                        const text = await res.text();
                        let json = {};
                        try {
                            json = text ? JSON.parse(text) : {};
                        } catch (e) {
                            json = {
                                message: 'Response server tidak valid. Periksa kemungkinan debug/dd di backend.'
                            };
                        }

                        if (!res.ok) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: json.message ?? 'Gagal menyimpan transaksi.',
                            });

                            if (typeof doneCallback === 'function') doneCallback();
                            return;
                        }

                        if (json.success === false) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: json.message ?? 'Gagal menyimpan transaksi.',
                            });

                            if (typeof doneCallback === 'function') doneCallback();
                            return;
                        }

                        if (typeof doneCallback === 'function') doneCallback();
                        redirectToHome();
                    })
                    .catch(err => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: 'Gagal menyimpan transaksi.',
                        });
                        console.error(err);
                        if (typeof doneCallback === 'function') doneCallback();
                    });
            },

            autoSaveDraft() {
                if (this.isEditing && this.originalStatus !== 'temp') {
                    return;
                }

                if (this.cart.length === 0 && this.parcel.length === 0 && this.jus.length === 0) {
                    return;
                }

                // Clear previous timeout
                if (this.autoSaveTimeout) {
                    clearTimeout(this.autoSaveTimeout);
                }

                // Debounce auto-save
                this.autoSaveTimeout = setTimeout(() => {
                    if (this.isAutoSaving) {
                        this.autoSaveQueued = true;
                        return;
                    }

                    this.isAutoSaving = true;
                    this.autoSaveQueued = false;
                    console.log('Auto-saving draft...');
                    const customerId = document.querySelector('select[name="customer_id"]').value;
                    const transactionDate = document.querySelector('input[name="date"]').value;
                    const invoiceInput = document.querySelector('input[name="invoice_number"]');
                    const invoiceNumber = this.currentInvoiceNumber || invoiceInput?.value || '';
                    const ongkirDate = document.querySelector('input[name="ongkir_date"]').value;
                    const ongkirTime = document.querySelector('input[name="ongkir_time"]').value;
                    const note = document.querySelector('textarea[name="note"]').value;
                    const courierId = document.querySelector('select[name="courier_id"]').value;
                    const branchId = document.querySelector('select[name="branch_id"]').value;
                    const branchProcessId = document.querySelector('select[name="branch_process_id"]').value;
                    const ongkirAddress = document.querySelector('select[name="ongkir_address"]').value;

                    const data = {
                        customer_id: customerId,
                        date: transactionDate,
                        invoice_number: invoiceNumber,
                        items: this.cart,
                        parcel: this.parcel,
                        jus: this.jus,
                        subtotal: this.subtotal,
                        discount: this.diskonGlobal,
                        ongkir: this.ongkirGlobal,
                        discount_ongkir: this.diskonOngkir,
                        ongkir_date: ongkirDate,
                        ongkir_time: ongkirTime,
                        total: this.totalHargaKeseluruhan,
                        status: this.isEditing && this.originalStatus !== 'temp' ? this.originalStatus : 'temp',
                        note: note,
                        courier_id: courierId,
                        courier_type: document.querySelector('input[name="courier_type"]')?.value || null,
                        ongkir_address: ongkirAddress,
                        branch_id: branchId,
                        branch_process_id: branchProcessId,
                    };
                        fetch('/pos/save-transaction', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify(data, getCircularReplacer()),
                        })
                        .then(async res => {
                            const json = await res.json().catch(() => ({}));

                            if (res.ok && json.success) {
                                if (json.invoice_number) {
                                    this.currentInvoiceNumber = json.invoice_number;
                                    if (invoiceInput) {
                                        invoiceInput.value = json.invoice_number;
                                    }
                                }
                                console.log('Draft saved successfully');
                                return;
                            }

                            throw new Error(json.message || 'Auto-save failed');
                        })
                        .catch(err => {
                            console.error('Auto-save failed:', err);
                        })
                        .finally(() => {
                            this.isAutoSaving = false;

                            if (this.autoSaveQueued) {
                                this.autoSaveQueued = false;
                                this.autoSaveDraft();
                            }
                        });
                }, 2000); // 2 second delay
            },
            // Save Order Book
            saveToOrderBook() {
                if (this.cart.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Keranjang kosong',
                        text: 'Silakan tambahkan produk terlebih dahulu!',
                    });
                    return;
                }

                if (this.autoSaveTimeout) {
                    clearTimeout(this.autoSaveTimeout);
                    this.autoSaveTimeout = null;
                }
                this.autoSaveQueued = false;

                const customerId = document.querySelector('select[name="customer_id"]').value;
                const transactionDate = document.querySelector('input[name="date"]').value;
                const invoiceNumber = this.currentInvoiceNumber || document.querySelector('input[name="invoice_number"]').value;
                const ongkirDate = document.querySelector('input[name="ongkir_date"]').value;
                const ongkirTime = document.querySelector('input[name="ongkir_time"]').value;
                const note = document.querySelector('textarea[name="note"]').value;
                const courierId = document.querySelector('select[name="courier_id"]').value;
                const branchId = document.querySelector('select[name="branch_id"]').value;
                const branchProsesId = document.querySelector('select[name="branch_process_id"]').value;
                // const ongkirAddress = document.querySelector('textarea[name="ongkir_address"]').value;
                const ongkirAddress = document.querySelector('select[name="ongkir_address"]').value;

                if (this.ongkirGlobal > 0 && (courierId == null || courierId == '')) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Kurir belum dipilih',
                        text: 'Anda memasukkan biaya ongkir, tetapi tidak memilih kurir!',
                    });
                    if (typeof doneCallback === 'function') doneCallback();
                    return;
                }


                const data = {
                    customer_id: customerId,
                    date: transactionDate,
                    invoice_number: invoiceNumber,
                    items: this.cart,
                    parcel: this.parcel,
                    jus: this.jus,
                    subtotal: this.subtotal,
                    discount: this.diskonGlobal,
                    ongkir: this.ongkirGlobal,
                    discount_ongkir: this.diskonOngkir,
                    ongkir_date: ongkirDate,
                    ongkir_time: ongkirTime,
                    total: this.totalHargaKeseluruhan,
                    process_status: 'pending',
                    note: note,
                    courier_id: courierId,
                    courier_type: document.querySelector('input[name="courier_type"]')?.value || null,
                    ongkir_address: ongkirAddress,
                    branch_id: branchId,
                    branch_process_id: branchProsesId,
                };

                // Simulasi kirim ke server
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                fetch('/pos/save-transaction', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(data, getCircularReplacer()),
                    })
                    .then(async (res) => {
                        const text = await res.text();
                        let json = {};
                        try {
                            json = text ? JSON.parse(text) : {};
                        } catch (e) {
                            json = {
                                message: 'Response server tidak valid. Periksa kemungkinan debug/dd di backend.'
                            };
                        }

                        // Jika server mengembalikan error (HTTP bukan 200)
                        if (!res.ok) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: json.message ?? 'Gagal menyimpan transaksi.',
                            });

                            if (typeof doneCallback === 'function') doneCallback();
                            return; // ⛔ STOP — jangan redirect
                        }

                        // Jika JSON success = false
                        if (json.success === false) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: json.message ?? 'Gagal menyimpan transaksi.',
                            });

                            if (typeof doneCallback === 'function') doneCallback();
                            return; // ⛔ STOP redirect
                        }

                        // Jika sukses → baru redirect
                        if (typeof doneCallback === 'function') doneCallback();
                        redirectToPayment(json.transaksi_id);
                    })

                    .catch(err => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: 'Gagal menyimpan transaksi.',
                        });
                        console.error(err);
                        if (typeof doneCallback === 'function') doneCallback();
                    });
            },
            // Pembayaran
            goToPayment(doneCallback) {
                if (this.cart.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Keranjang kosong',
                        text: 'Silakan tambahkan produk terlebih dahulu!',
                    });
                    if (typeof doneCallback === 'function') doneCallback();
                    return;
                }
                const customerId = document.querySelector('select[name="customer_id"]').value;
                const transactionDate = document.querySelector('input[name="date"]').value;
                const invoiceNumber = document.querySelector('input[name="invoice_number"]').value;
                const ongkirDate = document.querySelector('input[name="ongkir_date"]').value;
                const ongkirTime = document.querySelector('input[name="ongkir_time"]').value;
                const note = document.querySelector('textarea[name="note"]').value;
                const courierId = document.querySelector('select[name="courier_id"]').value;
                const branchId = document.querySelector('select[name="branch_id"]').value;
                const branchProsesId = document.querySelector('select[name="branch_process_id"]').value;
                // const ongkirAddress = document.querySelector('textarea[name="ongkir_address"]').value;
                const ongkirAddress = document.querySelector('select[name="ongkir_address"]').value;
                console.table(this.ongkirGlobal, courierId);

                if (this.ongkirGlobal > 0 && (courierId == null || courierId == '')) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Kurir belum dipilih',
                        text: 'Anda memasukkan biaya ongkir, tetapi tidak memilih kurir!',
                    });
                    if (typeof doneCallback === 'function') doneCallback();
                    return;
                }

                const data = {
                    customer_id: customerId,
                    date: transactionDate,
                    invoice_number: invoiceNumber,
                    items: this.cart,
                    parcel: this.parcel,
                    jus: this.jus,
                    subtotal: this.subtotal,
                    discount: this.diskonGlobal,
                    ongkir: this.ongkirGlobal,
                    discount_ongkir: this.diskonOngkir,
                    ongkir_date: ongkirDate,
                    ongkir_time: ongkirTime,
                    total: this.totalHargaKeseluruhan,
                    status: 'draft',
                    note: note,
                    courier_id: courierId,
                    ongkir_address: ongkirAddress,
                    branch_id: branchId,
                    branch_process_id: branchProsesId,
                };

                // Simulasi kirim ke server
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                fetch('/pos/save-transaction', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(data, getCircularReplacer()),
                    })
                    .then(async (res) => {
                        const text = await res.text();
                        let json = {};
                        try {
                            json = text ? JSON.parse(text) : {};
                        } catch (e) {
                            json = {
                                message: 'Response server tidak valid. Periksa kemungkinan debug/dd di backend.'
                            };
                        }

                        // Jika server mengembalikan error (HTTP bukan 200)
                        if (!res.ok) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: json.message ?? 'Gagal menyimpan transaksi.',
                            });

                            if (typeof doneCallback === 'function') doneCallback();
                            return; // ⛔ STOP — jangan redirect
                        }

                        // Jika JSON success = false
                        if (json.success === false) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: json.message ?? 'Gagal menyimpan transaksi.',
                            });

                            if (typeof doneCallback === 'function') doneCallback();
                            return; // ⛔ STOP redirect
                        }

                        // Jika sukses → baru redirect
                        if (typeof doneCallback === 'function') doneCallback();
                        redirectToPayment(json.transaksi_id);
                    })
                    .catch(err => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: 'Gagal menyimpan transaksi.',
                        });
                        console.error(err);
                        if (typeof doneCallback === 'function') doneCallback();
                    });
            },

            resetPOS() {
                this.cart = [];
                this.diskonGlobal = 0;
                this.ongkirGlobal = 0;
                this.subtotal = 0;
                this.totalHargaKeseluruhan = 0;
            },

            addCustomer() {
                const modal = new bootstrap.Modal(document.getElementById('customerModal'));
                modal.show();
            },

            saveCustomer() {
                const modal = bootstrap.Modal.getInstance(document.getElementById('customerModal'));
                const name = document.querySelector('[x-model="customerName"]').value;
                const phone = document.querySelector('[x-model="customerPhone"]').value;
                const address = document.querySelector('[x-model="customerAddress"]').value;

                if (!name || !phone) {
                    Swal.fire('Lengkapi data', 'Nama dan nomor telepon wajib diisi.', 'warning');
                    return;
                }

                fetch('/pos/customers', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({
                            name,
                            phone,
                            address
                        })
                    })
                    .then(res => res.json())
                    .then(res => {
                        if (res.success) {
                            modal.hide();
                            console.log(res);

                            const c = res.customer;

                            // Buat <option> baru dengan atribut tambahan
                            const option = new Option(c.name, c.id, true, true);
                            $(option).attr({
                                'data-name': c.name,
                                'data-address': c.address,
                                'data-whatsapp': c.phone,
                                'data-tier_id': c.tier_id || '',
                                'data-tier_name': c.tier_name || '-',
                                'data-tier_style': c.tier_style || 'badge-light-secondary'
                            });

                            // Tambahkan ke select2
                            $('#customer_id').append(option).trigger('change');
                            $('#ongkir_address').text(c.address);

                            document.querySelector('[x-model="customerName"]').value = '';
                            document.querySelector('[x-model="customerPhone"]').value = '';
                            document.querySelector('[x-model="customerAddress"]').value = '';

                            // Swal.fire('Berhasil', 'Customer berhasil ditambahkan.', 'success');
                        } else {
                            Swal.fire('Gagal', res.message ?? 'Gagal menyimpan customer.', 'error');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        Swal.fire('Error', 'Terjadi kesalahan saat menyimpan.', 'error');
                    });
            },

            // Gift Modal
            openGiftModal() {
                this.showGiftModal = true;
                setTimeout(() => {
                    const modal = new bootstrap.Modal(document.getElementById('giftModal'));
                    modal.show();

                    // Init select2
                    $('#select_gift').select2({
                        placeholder: 'Pilih produk',
                        language: {
                            errorLoading: function() {
                                return "Tidak ada Hadiah yang tersedia.";
                            }
                        },
                        dropdownParent: $('#giftModal'),
                        ajax: {
                            // url: '/tier/get-gift/' + $('#tier_id').val(), // ganti sesuai route
                            url: '/ajax/listProduct', // ganti sesuai route
                            dataType: 'json',
                            delay: 250,
                            processResults: data => {
                                window.productStockMap = window.productStockMap || {};
                                data.forEach(item => {
                                    window.productStockMap[item.id] = item.parent_product ? item.parent_product.parent_id : item.id;
                                });
                                return {
                                    results: data.map(item => {
                                        let qtyInCart = 0;
                                        if (typeof this.calculateUsedStock === 'function') {
                                            qtyInCart = this.calculateUsedStock(item.id);
                                        } else {
                                            const mainApp = window.mainCartInstance || (document.querySelector('[x-data="posApp()"]') ? document.querySelector('[x-data="posApp()"]').__x.$data : null);
                                            if (mainApp && typeof mainApp.calculateUsedStock === 'function') {
                                                qtyInCart = mainApp.calculateUsedStock(item.id);
                                            }
                                        }
                                        let stock_available = (item.get_stock?.stock_available ?? 0) - qtyInCart;
                                        return {
                                            id: item.id,
                                            text: item.name,
                                            unit: item.unit,
                                            price: item.price,
                                            hpp: item.hpp,
                                            receipt: item.product_receipt,
                                            stock_available: stock_available,
                                            original_stock: item.get_stock?.stock_available ?? 0,
                                            parent_id: item.parent_product ? item.parent_product.parent_id : item.id
                                        };
                                    })
                                };
                            }
                        }
                    }).on('select2:select', (e) => {
                        const data = e.params.data;
                        this.addProduct.id = data.id;
                        this.addProduct.name = data.text;
                        this.addProduct.unit = data.unit.abbreviation;
                        this.addProduct.price = data.price;
                        this.addProduct.hpp = data.hpp ?? 0;
                        subtotal = this.addProduct.qty * this.addProduct.price;
                        this.addProduct.formattedAddTotalInput = this.formatRupiah(this.addProduct
                            .total);
                        this.updateAddTotalFromQty();
                    });
                }, 0);
            },
            closeGiftModal() {
                this.showGiftModal = false;
                const modal = bootstrap.Modal.getInstance(document.getElementById('giftModal'));
                if (modal) modal.hide();
            },
            saveGiftToCart() {
                if (!this.addProduct.id) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Produk belum dipilih',
                        text: 'Silakan pilih produk terlebih dahulu.',
                    });
                    return;
                }

                const isExist = this.cart.some(item => item.id === this.addProduct.id);
                if (isExist) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Produk sudah ditambahkan',
                        text: 'Produk ini sudah ada di keranjang.',
                    });
                    return;
                }

                const discount = Number(this.addProduct.discount || 0);
                const total_input = this.addProduct.total;

                this.cart.push({
                    id: this.addProduct.id,
                    name: this.addProduct.name,
                    price: 0,
                    hpp: this.addProduct.hpp,
                    qty: this.addProduct.qty,
                    unit: this.addProduct.unit,
                    discount: discount,
                    discountPercent: this.addProduct.discountPercent,
                    total_input: 0,
                    typeProduct: 'gift', // Tambahkan tipe produk
                });

                // console.log('cart', this.cart);
                this.resetAddForm();
            },

            checkGiftButton(total) {
                // console.log('minimalPurchase', this.minimalPurchase, 'total', total);
                const customerId = document.getElementById('customer_id').value;
                if (total > this.minimalPurchase && customerId != 0) {
                    this.isShowGiftButton = true;
                } else {
                    this.isShowGiftButton = false;
                }
            },

            // Parcel Modal
            openParcelModal() {
                this.showParcelModal = true;
                setTimeout(() => {
                    const jasaEl = document.getElementById('parcel_edit_jasa');
                    if (jasaEl) jasaEl.value = 0;

                    const kemasanEl = document.getElementById('kemasan_edit_price');
                    if (kemasanEl) kemasanEl.value = 0;
                    const modal = new bootstrap.Modal(document.getElementById('parcelModal'));
                    modal.show();

                    // Init select2
                    $('#select_kemasan').select2({
                        placeholder: 'Pilih kemasan',
                        language: {
                            errorLoading: function() {
                                return "Belum ada kemasan yang dibuat.";
                            }
                        },
                        dropdownParent: $('#parcelModal'),
                        ajax: {
                            url: '/ajax/listProduct', // ganti sesuai route
                            dataType: 'json',
                            delay: 250,
                            data: function(params) {
                                return {
                                    term: params.term, // term dari select2 untuk pencarian
                                    branch: $('#branch_id').val(),
                                    type: 'kemasan', // contoh ambil dari input lain
                                    status: 'aktif', // contoh nilai statis
                                    limit: 10 // contoh parameter tambahan
                                };
                            },
                            processResults: data => {
                                window.productStockMap = window.productStockMap || {};
                                data.forEach(item => {
                                    window.productStockMap[item.id] = item.parent_product ? item.parent_product.parent_id : item.id;
                                });
                                return {
                                    results: data.map(item => {
                                        let qtyInCart = 0;
                                        if (typeof this.calculateUsedStock === 'function') {
                                            qtyInCart = this.calculateUsedStock(item.id);
                                        } else {
                                            const mainApp = window.mainCartInstance || (document.querySelector('[x-data="posApp()"]') ? document.querySelector('[x-data="posApp()"]').__x.$data : null);
                                            if (mainApp && typeof mainApp.calculateUsedStock === 'function') {
                                                qtyInCart = mainApp.calculateUsedStock(item.id);
                                            }
                                        }
                                        let stock_available = (item.get_stock?.stock_available ?? 0) - qtyInCart;
                                        return {
                                            id: item.id,
                                            text: item.name,
                                            unit: item.unit,
                                            price: item.price,
                                            hpp: item.hpp,
                                            receipt: item.product_receipt,
                                            stock_available: stock_available,
                                            original_stock: item.get_stock?.stock_available ?? 0,
                                            parent_id: item.parent_product ? item.parent_product.parent_id : item.id
                                        };
                                    })
                                };
                            }
                        },
                        templateResult: data => {
                            if (data.loading) return data.text;
                            const stock = data.stock_available ?? 0;
                            const disabled = stock <= 0;
                            const $el = $(`<span class="${disabled ? 'text-muted' : ''}">${data.text} <span class="badge badge-light-${stock > 0 ? 'success' : 'danger'} ms-2">Stok: ${Math.round(stock * 100) / 100}</span></span>`);
                            if (disabled) {
                                $el.css('cursor', 'not-allowed');
                            }
                            return $el;
                        },
                        templateSelection: data => {
                            const stock = data.stock_available ?? 0;
                            if (stock <= 0 && data.id) return $(`<span class="text-muted">${data.text} (Stok habis)</span>`);
                            return data.text;
                        }
                    }).on('select2:select', (e) => {
                        const data = e.params.data;
                        const stock = data.stock_available ?? 0;
                        if (stock <= 0) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Stok tidak mencukupi',
                                text: 'Kemasan ' + data.text + ' tidak memiliki stok yang cukup.',
                            });
                            $('#select_kemasan').val(null).trigger('change');
                            return;
                        }
                        $('#kemasan_price').val(data.price.toLocaleString());
                        this.updateAddTotalFromQty();
                    });
                }, 0);
            },
            closeParcelModal() {
                this.showParcelModal = false;
                const modal = bootstrap.Modal.getInstance(document.getElementById('parcelModal'));
                if (modal) modal.hide();
                
                let parcelFormNodes = document.querySelectorAll('[x-data="parcelForm()"]');
                parcelFormNodes.forEach(node => {
                    if (node.id === 'parcelModal' && node.__x) {
                        let instance = node.__x.$data;
                        instance.parcels = [];
                        instance.totalAll = 0;
                        instance.qtyParcel = 1;
                        instance.budgetParcel = '';
                    }
                });
                
                document.getElementById('parcel_budget').value = '';
                document.getElementById('parcel_qty').value = 1;
                document.getElementById('parcel_jasa').value = '';
                $('#select_kemasan').val(null).trigger('change');
            },

            closeParcelEditModal() {
                this.showParcelEditModal = false;
                const modal = bootstrap.Modal.getInstance(document.getElementById('parcelEditModal'));
                if (modal) modal.hide();
                
                let parcelFormNodes = document.querySelectorAll('[x-data="parcelForm()"]');
                parcelFormNodes.forEach(node => {
                    if (node.id === 'parcelEditModal' && node.__x) {
                        let instance = node.__x.$data;
                        instance.parcels = [];
                        instance.totalAll = 0;
                        instance.qtyParcel = 1;
                        instance.budgetParcel = '';
                    }
                });
                
                document.getElementById('parcel_edit_budget').value = '';
                document.getElementById('parcel_edit_qty').value = 1;
                document.getElementById('parcel_edit_jasa').value = '';
                $('#select_edit_kemasan').val(null).trigger('change');
            },

            saveParcelToCart(index) {
                let item = this.parcels[index];
                // console.log('Saving parcel to cart:', item);
                if (!item.product) {
                    alert('Pilih parcel terlebih dahulu');
                    return;
                }

                // Akses posApp dari luar
                let posAppInstance = document.querySelector('[x-ref=posApp]').__x.$data;

                posAppInstance.cart.push({
                    id: item.product,
                    name: item.name,
                    qty: item.qty,
                    budget: item.price
                });

                this.removeParcel(index);
            },

            openEditParcelModal(item) {
                console.log('Opening edit parcel for item:', item);
                this.editItem = {
                    ...item
                }; // salin data item
                const selectedParcel = this.parcel.find(i => i.id === this.editItem.id) || null;
                const parcelData = Array.isArray(selectedParcel?.data) ? selectedParcel.data : [];
                console.log('parcel Data', parcelData, 'item', item, 'selectedParcel', selectedParcel);
                let modalEl = document.getElementById('parcelEditModal');

                const jasaEl = document.getElementById('parcel_jasa');
                if (jasaEl) jasaEl.value = 0;
                const kemasanEl = document.getElementById('kemasan_price');
                if (kemasanEl) kemasanEl.value = 0;

                let parcelFormInstance = Alpine.$data(modalEl);

                Alpine.nextTick(() => {
                    // ambil instance alpine di modal
                    // let parcelFormInstance = Alpine.$data(modalEl);
                    parcelFormInstance.resetParcel();
                    parcelData.forEach((item, index) => {
                        const parcelItem = {
                            product: item.product,
                            name: item.name,
                            unit: item.unit?.abbreviation ?? item.unit?.name ?? item.unit ?? '',
                            displayName: item.displayName ?? '',
                            priceAwal: item.priceAwal ?? item.price,
                            qty: item.qty || 1,
                            price: item.price,
                            priceFormatted: this.formatRupiah(item.price),
                            hpp: item.hpp || 0,
                            original_stock: item.original_stock || 0,
                            stock_available: item.stock_available || 0
                        };

                        // push ke Alpine
                        parcelFormInstance.setParcel(parcelItem);
                    });
                    parcelFormInstance.setParcelId(item.id);
                    setTimeout(() => {
                        parcelFormInstance.initSelect2();
                    }, 50);
                });

                const parcelQty = selectedParcel?.qty ?? item.qty ?? 1;
                const parcelBudget = selectedParcel?.budget ?? item.price ?? 0;
                const parcelFee = selectedParcel?.fee ?? item.fee ?? 0;
                const kemasanPrice = selectedParcel?.kemasanPrice ?? item.kemasanPrice ?? 0;
                const kemasanId = selectedParcel?.kemasanId ?? item.kemasanId ?? null;
                const kemasanName = selectedParcel?.kemasan ?? selectedParcel?.kemasanName ?? item.kemasanName ?? null;

                $('#parcel_edit_qty').val(parcelQty);
                $('#parcel_edit_budget').val(this.formatRupiah(parcelBudget || 0));
                $('#parcel_edit_jasa').val(this.formatRupiah(parcelFee || 0));
                $('#kemasan_edit_price').val(kemasanPrice || 0);
                $('#select_edit_kemasan').select2({
                    placeholder: 'Pilih kemasan',
                    language: {
                        errorLoading: function() {
                            return "Belum ada kemasan yang dibuat.";
                        }
                    },
                    dropdownParent: $('#parcelEditModal'),
                    ajax: {
                        url: '/ajax/listProduct', // ganti sesuai route
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                term: params.term, // term dari select2 untuk pencarian
                                branch: $('#branch_id').val(),
                                type: 'kemasan', // contoh ambil dari input lain
                                status: 'aktif', // contoh nilai statis
                                limit: 10 // contoh parameter tambahan
                            };
                        },
                            processResults: data => {
                                window.productStockMap = window.productStockMap || {};
                                data.forEach(item => {
                                    window.productStockMap[item.id] = item.parent_product ? item.parent_product.parent_id : item.id;
                                });
                                return {
                                    results: data.map(item => {
                                        let qtyInCart = 0;
                                        if (typeof this.calculateUsedStock === 'function') {
                                            qtyInCart = this.calculateUsedStock(item.id);
                                        } else {
                                            const mainApp = window.mainCartInstance || (document.querySelector('[x-data="posApp()"]') ? document.querySelector('[x-data="posApp()"]').__x.$data : null);
                                            if (mainApp && typeof mainApp.calculateUsedStock === 'function') {
                                                qtyInCart = mainApp.calculateUsedStock(item.id);
                                            }
                                        }
                                        let stock_available = (item.get_stock?.stock_available ?? 0) - qtyInCart;
                                        return {
                                            id: item.id,
                                            text: item.name,
                                            unit: item.unit,
                                            price: item.price,
                                            hpp: item.hpp,
                                            receipt: item.product_receipt,
                                            stock_available: stock_available,
                                            original_stock: item.get_stock?.stock_available ?? 0,
                                            parent_id: item.parent_product ? item.parent_product.parent_id : item.id
                                        };
                                    })
                                };
                            }
                    },
                    templateResult: data => {
                        if (data.loading) return data.text;
                        const stock = data.stock_available ?? 0;
                        const disabled = stock <= 0;
                        const $el = $(`<span class="${disabled ? 'text-muted' : ''}"><strong>${data.text}</strong> <span class="badge bg-${stock > 0 ? 'success' : 'danger'}">Sisa: ${Math.round(stock * 100) / 100}</span></span>`);
                        if (disabled) {
                            $el.css('cursor', 'not-allowed');
                        }
                        return $el;
                    },
                    templateSelection: data => {
                        const stock = data.stock_available ?? 0;
                        if (stock <= 0 && data.id) return $(`<span class="text-muted">${data.text} (Stok habis)</span>`);
                        return data.text;
                    }
                }).on('select2:select', (e) => {
                    const data = e.params.data;
                    const stock = data.stock_available ?? 0;
                    if (stock <= 0) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Stok tidak mencukupi',
                            text: 'Kemasan ' + data.text + ' tidak memiliki stok yang cukup.',
                        });
                        $('#select_edit_kemasan').val(null).trigger('change');
                        return;
                    }
                    $('#kemasan_edit_price').val(data.price.toLocaleString());
                    this.updateAddTotalFromQty();
                });
                if (kemasanId) {
                    let option = new Option(kemasanName, kemasanId, true, true);
                    $('#select_edit_kemasan')
                        .append(option)
                        .trigger('change');
                }
                this.editModal = true;

                setTimeout(() => {
                    const modal = new bootstrap.Modal(document.getElementById('parcelEditModal'));
                    modal.show();
                }, 0);
            },

            // For Edit

            formatShortNumber(num) {
                num = parseInt(num.toString().replace(/\./g, '')) || 0; // hapus titik & jadi integer

                if (num >= 1_000_000_000) {
                    return (num / 1_000_000_000).toFixed(1).replace(/\.0$/, '') + 'B';
                } else if (num >= 1_000_000) {
                    return (num / 1_000_000).toFixed(1).replace(/\.0$/, '') + 'M';
                } else if (num >= 1_000) {
                    return (num / 1_000).toFixed(1).replace(/\.0$/, '') + 'K';
                }
                return num.toString();
            },

            loadExistingData(transactionData, detail) {
                this.cart = [];
                this.parcel = [];
                this.jus = [];

                // Load existing cart items
                detail.map(item => {
                    let id = item.product_id;
                    const key = Date.now() + Math.floor(Math.random() * 1000);
                    if (item.type == 'parcel') {
                        id = 'parcel' + item.product_id + this.formatShortNumber(item.price) + '_' + key;
                    } else if (item.type == 'jus') {
                        id = 'jus' + item.product_id + '_' + key;
                    }
                    let productName = item.type == 'parcel' ? item.product.description : item.product.name;
                    let total = item.type == 'parcel' ? this.sanitizeNumber(Number(item.subtotal || 0)) * item
                        .quantity : this.sanitizeNumber(Number(item.subtotal || 0));
                    const obj = {
                        key: key,
                        id: id,
                        name: productName,
                        price: this.sanitizeNumber(Number(item.price || 0)), // pastikan number dulu
                        hpp: parseFloat(item.hpp || 0),
                        qty: this.sanitizeNumber(Number(item.quantity)),
                        unit: item.product.unit.abbreviation,
                        discount: this.sanitizeNumber(Number(item.discount || 0)),
                        discountPercent: item.discountPercent || 0,
                        fee: item.product.fee || 0,
                        kemasanId: item.parcel ? item.parcel.id : null,
                        kemasanName: item.parcel ? item.parcel.name : null,
                        kemasanPrice: item.parcel ? item.parcel.price : null,
                        total_input: total,
                        typeProduct: item.type || 'product',
                    };
                    this.cart.push(obj);

                    if (item.type == 'parcel') {
                        let percelDatas = [];
                        const branchId = transactionData.branch_id ?? transactionData.branch?.id ?? $('#branch_id').val();
                        const parentParcelQty = this.sanitizeNumber(Number(item.quantity || 1)) || 1;
                        const parcelDetails = Array.isArray(item.product?.production_parcel_details) ? item.product.production_parcel_details : [];

                        parcelDetails.forEach(detailItem => {
                            const totalQty = this.sanitizeNumber(Number(detailItem.quantity || 0));
                            const qtyPerParcel = parentParcelQty > 0 ? totalQty / parentParcelQty : totalQty;
                            const branchPrice = detailItem.product?.product_branches?.find(branchProduct =>
                                String(branchProduct.branch_id) === String(branchId)
                            )?.price;
                            const fallbackBasePrice = this.sanitizeNumber(Number(branchPrice ?? detailItem.product?.price ?? 0));
                            const normalizedQty = qtyPerParcel > 0 ? qtyPerParcel : 1;
                            const storedBasePrice = this.sanitizeNumber(Number(detailItem.price_awal || 0));
                            const storedLinePrice = this.sanitizeNumber(Number(detailItem.price || 0));
                            const basePrice = storedBasePrice > 0
                                ? storedBasePrice
                                : (storedLinePrice > 0 && normalizedQty > 0
                                    ? storedLinePrice / normalizedQty
                                    : fallbackBasePrice);
                            const linePrice = storedLinePrice > 0 ? storedLinePrice : (basePrice * normalizedQty);

                            const parcelData = {
                                product: detailItem.product_id,
                                name: detailItem.product?.name ?? 'unknown',
                                unit: detailItem.product?.unit?.abbreviation ?? detailItem.product?.unit?.name ?? '',
                                displayName: detailItem.product?.name && (detailItem.product?.unit?.abbreviation ?? detailItem.product?.unit?.name)
                                    ? `${detailItem.product.name} (${detailItem.product.unit?.abbreviation ?? detailItem.product.unit?.name})`
                                    : (detailItem.product?.name ?? 'unknown'),
                                priceAwal: basePrice,
                                hpp: parseFloat(detailItem.product?.hpp ?? 0),
                                price: linePrice,
                                priceFormatted: this.formatRupiah(linePrice),
                                qty: qtyPerParcel,
                                original_stock: parseFloat(detailItem.product?.get_stock?.stock_available ?? 0),
                                stock_available: parseFloat(detailItem.product?.get_stock?.stock_available ?? 0),
                            };
                            percelDatas.push(parcelData);
                        });

                        const parcels = {
                            id: id,
                            budget: parseInt(item.price, 10),
                            qty: item.quantity,
                            kemasan: item.parcel.name,
                            kemasanId: item.parcel.id,
                            kemasanPrice: item.parcel.price,
                            hpp: parseFloat(item.hpp || 0),
                            fee: parseInt(item.product.fee, 10) || 0,
                            data: percelDatas,
                            type: 'parcel',
                        };

                        this.parcel.push(parcels);
                    }

                    if (item.type == 'jus') {
                        let prod = transactionData.productions ? transactionData.productions.find(p => p.product_id === item.product_id) : null;
                        let receiptProducts = [];
                        let receiptProductsQty = [];
                        let receiptProductsText = [];
                        let receiptProductsOriginalStock = [];

                        if (prod) {
                            // Ambil bahan dari production detail jika ada record produksi
                            let details = prod.production_details || prod.productionDetails;
                            if (details) {
                                details.forEach(d => {
                                    receiptProducts.push(d.product_id);
                                    let qtyPerUnit = d.quantity / (item.quantity > 0 ? item.quantity : 1);
                                    receiptProductsQty.push(qtyPerUnit);
                                    receiptProductsText.push(d.products ? d.products.name : ('Bahan ' + d.product_id));
                                    receiptProductsOriginalStock.push(d.products && d.products.get_stock ? (parseFloat(d.products.get_stock.stock_available) || 0) : 0);
                                });
                            }
                        }

                        // Fallback: jika tidak ada production (stok cukup, tidak perlu produksi),
                        // gunakan resep dari product_receipt yang sudah di-eager-load di controller.
                        if (receiptProducts.length === 0 && item.product && item.product.product_receipt && item.product.product_receipt.length > 0) {
                            item.product.product_receipt.forEach(r => {
                                if (r.ingredients) {
                                    receiptProducts.push(r.ingredients.id);
                                    receiptProductsQty.push(parseFloat(r.quantity) || 1);
                                    receiptProductsText.push(r.ingredients.name || ('Bahan ' + r.ingredients.id));
                                    receiptProductsOriginalStock.push(r.ingredients.get_stock ? (parseFloat(r.ingredients.get_stock.stock_available) || 0) : 0);
                                }
                            });
                        }

                        const cartIdx = this.cart.findIndex(c => c.key === key);
                        if (cartIdx !== -1) {
                            this.cart[cartIdx].data = {
                                products: receiptProducts,
                                productsQty: receiptProductsQty,
                                productsText: receiptProductsText,
                                productsOriginalStock: receiptProductsOriginalStock
                            };
                        }

                        this.jus.push({
                            id: id,
                            productId: item.product_id,
                            price: obj.price,
                            hpp: obj.hpp,
                            qty: obj.qty,
                            unit: obj.unit,
                            discount: obj.discount,
                            discountPercent: obj.discountPercent,
                            total_input: obj.total_input,
                            product_receipt_id: [...receiptProducts],
                            product_receipt_qty: [...receiptProductsQty],
                            type: 'jus'
                        });
                    }
                });

                this.diskonGlobal = parseFloat(transactionData.discount || 0);
                this.ongkirGlobal = parseFloat(transactionData.ongkir || 0);

                $('#note').val(transactionData.note);
                $('#ongkir_date').val(transactionData.ongkir_date);
                $('#ongkir_time').val(transactionData.ongkir_time);
                // $('#ongkir_address').val(data.ongkir_address);

                if (transactionData.courier) {
                    const courierTypeLabel = transactionData.courier_type === 'external' ? 'External' : 'Internal';
                    const courierText = `${transactionData.courier.name} (${courierTypeLabel})`;
                    let optionCourier = new Option(courierText, transactionData.courier.id, true, true);
                    $('#courier_id').append(optionCourier).val(transactionData.courier.id).trigger('change');
                }

                if (transactionData.courier_type) {
                    $('#courier_type').val(transactionData.courier_type).trigger('change');
                } else if (transactionData.courier) {
                    $('#courier_type').val('internal').trigger('change');
                }

                if (transactionData.invoice_number) {
                    this.currentInvoiceNumber = transactionData.invoice_number;
                    const invoiceInput = document.querySelector('input[name="invoice_number"]');
                    if (invoiceInput) {
                        invoiceInput.value = transactionData.invoice_number;
                    }
                }

                if (transactionData.ongkir_address) {
                    let ongkirAddress = new Option(transactionData.ongkir_address, transactionData.ongkir_address, true, true);
                    $('#address_id').append(ongkirAddress).val(transactionData.ongkir_address).trigger('change');
                }

                if (transactionData.branch_proses) {
                    let branchProcess = new Option(transactionData.branch_proses.name, transactionData.branch_proses.id, true, true);
                    $('#branch_process_id').append(branchProcess).val(transactionData.branch_proses.id).trigger('change');
                }

                if (transactionData.branch) {
                    let branch = new Option(transactionData.branch.name, transactionData.branch.id, true, true);
                    $('#branch_id').append(branch).val(transactionData.branch.id).trigger('change');
                }

                if (transactionData.customer) {
                    let c = {
                        id: transactionData.customer_id || 0,
                        name: transactionData.customer?.name || 'Pelanggan Umum',
                        address: transactionData.customer?.address || '-',
                        phone: transactionData.customer?.whatsapp || '-',
                        tier_id: transactionData.customer?.customer_tier?.tier_id || '',
                        tier_name: transactionData.customer?.customer_tier?.tier_name || '-',
                        tier_style: transactionData.customer?.customer_tier?.tier_style || 'badge-light-secondary'
                    };

                    let option = new Option(c.name, c.id, true, true);
                    $(option).attr({
                        'data-name': c.name,
                        'data-address': c.address,
                        'data-whatsapp': c.phone,
                        'data-tier_id': c.tier_id,
                        'data-tier_name': c.tier_name,
                        'data-tier_style': c.tier_style
                    });
                    $('#customer_id').append(option).val(c.id).trigger('change');
                }
            },

            loadExistingOrderBook(data, detail) {
                this.cart = [];
                this.parcel = [];
                this.jus = [];

                // Load existing cart items
                const key = Date.now() + Math.floor(Math.random() * 1000);
                detail.map(item => {
                    const obj = {
                        id: item.id,
                        name: item.product.name,
                        price: this.sanitizeNumber(Number(item.product.price || 0)), // pastikan number dulu
                        hpp: parseFloat(item.product.hpp || 0),
                        qty: this.sanitizeNumber(Number(item.quantity)),
                        unit: item.product.unit.abbreviation,
                        discount: this.sanitizeNumber(Number(item.discount || 0)),
                        discountPercent: item.discountPercent || 0,
                        fee: item.product.fee || 0,
                        total_input: this.sanitizeNumber(Number(item.subtotal || 0)),
                        typeProduct: item.type || 'product',
                    };
                    this.cart.push(obj);
                });

                if (data.customer) {
                    let c = {
                        id: data.customer_id || 0,
                        name: data.customer?.name || 'Pelanggan Umum',
                        address: data.customer?.address || '-',
                        phone: data.customer?.whatsapp || '-',
                        tier_id: data.customer?.customer_tier?.tier_id || '',
                        tier_name: data.customer?.customer_tier?.tier_name || '-',
                        tier_style: data.customer?.customer_tier?.tier_style || 'badge-light-secondary'
                    };

                    // Buat option baru
                    let option = new Option(c.name, c.id, true, true);

                    // Tambahkan atribut data-*
                    $(option).attr({
                        'data-name': c.name,
                        'data-address': c.address,
                        'data-whatsapp': c.phone,
                        'data-tier_id': c.tier_id,
                        'data-tier_name': c.tier_name,
                        'data-tier_style': c.tier_style
                    });

                    // Append ke select2 + set value
                    $('#customer_id').append(option).val(c.id).trigger('change');
                }

                if (data.branch) {
                    let optionBranch = new Option(data.branch.name, data.branch.id, true, true);
                    $('#branch_id').append(optionBranch).val(data.branch.id).trigger('change');
                }
            },

            // Open Jus
            openJusModal() {
                this.showJusModal = true;
                setTimeout(() => {
                    const modal = new bootstrap.Modal(document.getElementById('jusModal'));
                    modal.show();

                    // Init select2
                    $('#select_jus').select2({
                        placeholder: 'Pilih Jus',
                        language: {
                            errorLoading: function() {
                                return "Belum ada kemasan yang dibuat.";
                            }
                        },
                        dropdownParent: $('#jusModal'),
                        ajax: {
                            url: '/ajax/listProduct', // ganti sesuai route
                            dataType: 'json',
                            delay: 250,
                            data: function(params) {
                                return {
                                    term: params.term, // term dari select2 untuk pencarian
                                    jenis: 'receipt', // contoh ambil dari input lain
                                    branch: $('#branch_id').val(),
                                    limit: 10 // contoh parameter tambahan
                                };
                            },
                            processResults: data => {
                                window.productStockMap = window.productStockMap || {};
                                data.forEach(item => {
                                    window.productStockMap[item.id] = item.parent_product ? item.parent_product.parent_id : item.id;
                                });
                                return {
                                    results: data.map(item => {
                                        let qtyInCart = 0;
                                        if (typeof this.calculateUsedStock === 'function') {
                                            qtyInCart = this.calculateUsedStock(item.id);
                                        } else {
                                            const mainApp = window.mainCartInstance || (document.querySelector('[x-data="posApp()"]') ? document.querySelector('[x-data="posApp()"]').__x.$data : null);
                                            if (mainApp && typeof mainApp.calculateUsedStock === 'function') {
                                                qtyInCart = mainApp.calculateUsedStock(item.id);
                                            }
                                        }
                                        let stock_available = (item.get_stock?.stock_available ?? 0) - qtyInCart;
                                        return {
                                            id: item.id,
                                            text: item.name,
                                            unit: item.unit,
                                            price: item.price,
                                            hpp: item.hpp,
                                            receipt: item.product_receipt,
                                            stock_available: stock_available,
                                            original_stock: item.get_stock?.stock_available ?? 0,
                                            parent_id: item.parent_product ? item.parent_product.parent_id : item.id
                                        };
                                    })
                                };
                            }
                        },
                        templateResult: data => {
                            if (data.loading) return data.text;
                            const stock = data.stock_available ?? 0;
                            const isZero = stock <= 0;
                            const $el = $(`<span class="${isZero ? 'text-muted' : ''}">${data.text} <span class="badge badge-light-${stock > 0 ? 'success' : 'warning'} ms-2">Stok: ${Math.round(stock * 100) / 100}</span></span>`);
                            return $el;
                        },
                        templateSelection: data => {
                            const stock = data.stock_available ?? 0;
                            if (stock <= 0 && data.id) return $(`<span>${data.text} (Stok ${stock}, produksi otomatis)</span>`);
                            return data.text;
                        }
                    }).on('select2:select', (e) => {
                        const data = e.params.data;
                        const stock = data.stock_available ?? 0;
                        
                        this.addProduct.id = data.id;
                        this.addProduct.name = data.text;
                        this.addProduct.unit = data.unit.abbreviation;
                        this.addProduct.price = data.price;
                        this.addProduct.hpp = data.hpp ?? 0;
                        subtotal = this.addProduct.qty * this.addProduct.price;
                        this.addProduct.formattedAddTotalInput = this.formatRupiah(this.addProduct
                            .total);
                        this.addProduct.receipt = data.receipt;
                        this.updateAddTotalFromQty();
                        this.loadReceipt(data.receipt);
                    });
                }, 0);
            },

            loadReceipt(data) {
                console.log('receipt', data);

                const container = $('#receiptContainer');
                container.empty(); // bersihkan biar ga dobel

                if (data && data.length > 0) {
                    data.forEach(item => {
                        let originalStock = item.ingredients.get_stock ? item.ingredients.get_stock.stock_available : 0;
                        window.productStockMap = window.productStockMap || {};
                        window.productStockMap[item.ingredients.id] = item.ingredients.parent_product ? item.ingredients.parent_product.parent_id : item.ingredients.id;
                        
                        let row = `
                        <div class="row receipt-row mb-2">
                            <div class="col-9 mb-3">
                                <label class="form-label">Nama Produk</label>
                                <select name="receipt_product_id[]" class="form-select receipt-select" data-selected-id="${item.ingredients.id}" data-selected-text="${item.ingredients.name}" data-original-stock="${originalStock}">
                                </select>
                            </div>

                            <div class="col-3 mb-3">
                                <label class="form-label">Qty</label>
                                <input type="number" name="receipt_qty[]" class="form-control" value="${item.quantity ?? 1}">
                            </div>
                        </div>
                        <div class="row receipt-row mb-2">
                            <div class="col-12 mb-3 text-center text-muted">
                                <em>Quantity akan dihitung otomatis berdasarkan Jumlah yang akan dibeli</em>
                            </div>
                        </div>
                    `;
                        container.append(row);
                    });
                } else {
                    let row = `
                            <div class="row receipt-row mb-2">
                                <div class="col-12 mb-3 text-center text-muted">
                                    <em>Tidak ada bahan</em>
                                </div>
                            </div>
                        `;
                    container.append(row);
                }

                // aktifkan select2 di semua select yang baru dibuat
                container.find('.receipt-select').each(function() {
                    const selectedId = $(this).data('selected-id');
                    const selectedText = $(this).data('selected-text');

                    $(this).select2({
                        placeholder: 'Pilih Produk',
                        dropdownParent: $('#jusModal'),
                        ajax: {
                            url: '/ajax/listProduct',
                            dataType: 'json',
                            delay: 250,
                            data: function(params) {
                                return {
                                    term: params.term,
                                    branch: $('#branch_id').val(),
                                    limit: 10
                                };
                            },
                            processResults: data => {
                                window.productStockMap = window.productStockMap || {};
                                data.forEach(item => {
                                    window.productStockMap[item.id] = item.parent_product ? item.parent_product.parent_id : item.id;
                                });
                                return {
                                    results: data.map(item => {
                                        let qtyInCart = 0;
                                        if (typeof this.calculateUsedStock === 'function') {
                                            qtyInCart = this.calculateUsedStock(item.id);
                                        } else {
                                            const mainApp = window.mainCartInstance || (document.querySelector('[x-data="posApp()"]') ? document.querySelector('[x-data="posApp()"]').__x.$data : null);
                                            if (mainApp && typeof mainApp.calculateUsedStock === 'function') {
                                                qtyInCart = mainApp.calculateUsedStock(item.id);
                                            }
                                        }
                                        let stock_available = (item.get_stock?.stock_available ?? 0) - qtyInCart;
                                        return {
                                            id: item.id,
                                            text: item.name,
                                            unit: item.unit,
                                            price: item.price,
                                            hpp: item.hpp,
                                            receipt: item.product_receipt,
                                            stock_available: stock_available,
                                            original_stock: item.get_stock?.stock_available ?? 0,
                                            parent_id: item.parent_product ? item.parent_product.parent_id : item.id
                                        };
                                    })
                                };
                            }
                        },
                        templateResult: data => {
                            if (data.loading) return data.text;
                            const stock = data.stock_available ?? 0;
                            const disabled = stock <= 0;
                            const $el = $(`<span class="${disabled ? 'text-muted' : ''}">${data.text} <span class="badge badge-light-${stock > 0 ? 'success' : 'danger'} ms-2">Stok: ${Math.round(stock * 100) / 100}</span></span>`);
                            if (disabled) {
                                $el.css('cursor', 'not-allowed');
                            }
                            return $el;
                        },
                        templateSelection: data => {
                            const stock = data.stock_available ?? 0;
                            if (stock <= 0 && data.id) return $(`<span class="text-muted">${data.text} (Stok habis)</span>`);
                            return data.text;
                        }
                    }).on('select2:select', (e) => {
                        const data = e.params.data;
                        const stock = data.stock_available ?? 0;
                        if (stock <= 0) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Stok tidak mencukupi',
                                text: 'Produk ' + data.text + ' tidak memiliki stok yang cukup.',
                            });
                            $(e.target).val(null).trigger('change');
                            return;
                        }
                    });

                    // set value awal dari item receipt
                    if (selectedId) {
                        const originalStockVal = parseFloat($(this).attr('data-original-stock')) || 0;
                        let option = new Option(selectedText, selectedId, true, true);
                        $(option).attr('data-original-stock', originalStockVal);
                        $(this).append(option).trigger('change');
                    }
                });
            },

            saveJusToCart() {
                try {
                    if (!this.addProduct.id) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Produk belum dipilih',
                            text: 'Silakan pilih jus terlebih dahulu.',
                        });
                        return;
                    }
                    if (this.addProduct.price === null || this.addProduct.price === '') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Harga produk belum diisi',
                            text: 'Silakan isi harga jus terlebih dahulu.',
                        });
                        return;
                    }
                    if (this.addProduct.qty <= 0) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Kuantitas tidak valid',
                            text: 'Jumlah produk harus lebih dari 0.',
                        });
                        return;
                    }

                    // save
                    let uniqueId = 'jus' + this.addProduct.id + '_' + Date.now();
                    
                    let receiptProducts = $("select[name='receipt_product_id[]']").map(function() { return $(this).val(); }).get();
                    let receiptProductsQty = $("input[name='receipt_qty[]']").map(function() { return $(this).val(); }).get();
                    let receiptProductsOriginalStock = $("select[name='receipt_product_id[]']").map(function() { 
                        let select2Data = $(this).select2('data');
                        if(select2Data && select2Data.length > 0 && select2Data[0].original_stock !== undefined) {
                            return select2Data[0].original_stock;
                        }
                        return $(this).find('option:selected').attr('data-original-stock') ?? $(this).attr('data-original-stock') ?? 0; 
                    }).get();
                    let receiptProductsText = $("select[name='receipt_product_id[]'] option:selected")
                        .map(function() {
                            return $(this).text() || '';
                        })
                        .get();

                    let discount = parseFloat(this.addProduct.discount) || 0;
                    let rawTotal = this.addProduct.formattedAddTotalInput || '';
                    let total_input = parseFloat(rawTotal.toString().replace(/[^\d]/g, '')) || 0;
                    
                    // Cek stok bahan — baca langsung dari DOM, bukan select2 data
                    let stockErrors = [];
                    $("select[name='receipt_product_id[]']").each((index, el) => {
                        const productId = $(el).val();
                        if (!productId) return; // skip jika belum dipilih

                        const selectedOption = $(el).find('option:selected');
                        const productName = selectedOption.text().trim() || `Bahan #${index + 1}`;
                        
                        let select2Data = $(el).select2('data');
                        let originalStock = 0;
                        if (select2Data && select2Data.length > 0 && select2Data[0].original_stock !== undefined) {
                            originalStock = parseFloat(select2Data[0].original_stock) || 0;
                        } else {
                            originalStock = parseFloat(selectedOption.attr('data-original-stock')) || parseFloat($(el).attr('data-original-stock')) || 0;
                        }

                        const qtyNeed = parseFloat($("input[name='receipt_qty[]']").eq(index).val()) || 0;
                        const totalQtyNeed = qtyNeed * (parseFloat(this.addProduct.qty) || 1);
                        const totalUsed = this.calculateUsedStock(productId);

                        if ((totalUsed + totalQtyNeed) > originalStock) {
                            stockErrors.push(`- ${productName} (Dibutuhkan: ${totalQtyNeed}, Sisa stok: ${Math.round((originalStock - totalUsed) * 100) / 100} [DEBUG: OS=${originalStock}, TU=${totalUsed}, QN=${qtyNeed}])`);
                        }
                    });

                    if (stockErrors.length > 0) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Stok Bahan Tidak Mencukupi',
                            html: 'Beberapa bahan untuk jus ini tidak mencukupi:<br>' + stockErrors.join('<br>'),
                        });
                        return;
                    }

                    this.cart.push({
                        id: uniqueId,
                        name: this.addProduct.name || '',
                        price: this.addProduct.price || 0,
                        hpp: this.addProduct.hpp || 0,
                        qty: this.addProduct.qty || 1,
                        unit: this.addProduct.unit || '',
                        discount: discount,
                        discountPercent: this.addProduct.discountPercent || 0,
                        total_input: total_input,
                        data: {
                            products: receiptProducts,
                            productsQty: receiptProductsQty,
                            productsText: receiptProductsText,
                            productsOriginalStock: receiptProductsOriginalStock
                        },
                        typeProduct: 'jus',
                    });

                    this.jus.push({
                        id: uniqueId,
                        productId: this.addProduct.id,
                        price: this.addProduct.price || 0,
                        hpp: this.addProduct.hpp || 0,
                        qty: this.addProduct.qty || 1,
                        unit: this.addProduct.unit || '',
                        discount: discount,
                        discountPercent: this.addProduct.discountPercent || 0,
                        total_input: total_input,
                        product_receipt_id: [...receiptProducts],
                        product_receipt_qty: [...receiptProductsQty],
                        type: 'jus',
                    });

                    console.log('cart', this.cart);
                    const container = $('#receiptContainer');
                    container.empty(); // bersihkan biar ga dobel
                    $('#select_jus').val(null).trigger('change');
                    this.resetAddForm();
                } catch (e) {
                    console.error("SAVE JUS ERROR:", e);
                    Swal.fire({
                        icon: 'error',
                        title: 'Terjadi Kesalahan',
                        text: e.message || 'Silakan cek console untuk detailnya.'
                    });
                }
            },
            closeJusModal() {
                this.showJusModal = false;
                const modal = bootstrap.Modal.getInstance(document.getElementById('jusModal'));
                if (modal) modal.hide();
                
                this.addProduct = {
                    id: null,
                    name: '',
                    unit: '',
                    price: 0,
                    hpp: 0,
                    discount: 0,
                    discountNominal: 0,
                    discountPercent: 0,
                    qty: 1,
                    formattedAddTotalInput: this.formatRupiah(0)
                };
                $('#select_jus').val(null).trigger('change');
                const container = $('#receiptContainer');
                container.empty();
            },
        }
    }
</script>
