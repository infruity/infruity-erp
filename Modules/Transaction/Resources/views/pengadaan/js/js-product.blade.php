<script>
    function posApp() {
        return {
            products: [],
            cart: [],
            parcel: [],
            jus: [],

            // edit product
            editModal: false,
            editItem: null,
            editProduct: null,
            editPrice: 0,
            editSell: 0,
            editQty: 1,
            editTotal: 0,
            editDiscount: 0,
            editDiscountPercent: 0,
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
            payment: 0,

            // Add Product
            showAddModal: false,
            addProducts: [],
            addProductSearch: '',
            addProductsLoading: false,
            addProductsError: false,
            addProductsRequest: 0,
            addValidationError: '',
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
                qty: 1,
                sell: 0
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
            autoSaveTimeout: null,
            autoSaveRequest: null,
            autoSaveEnabled: @json(request()->routeIs('wholesale.create')),
            currentInvoiceNumber: '',
            isSubmitting: false,

            init() {
                const self = this; // simpan konteks Alpine
                
                // Check if there's existing data from create() or edit() method
                const data = @json($data ?? null);
                const detail = @json($detail ?? null);
                
                console.log('Init - data:', data);
                console.log('Init - detail:', detail);
                
                // Load existing data if available
                if (data && !this._loaded) {
                    console.log('Loading existing data...');
                    this.loadExistingData(data, detail);
                    this._loaded = true;
                }

                // Auto-save using watchers - similar to PosController
                this.$watch('cart', () => {
                    if (!self.isSubmitting) self.autoSaveTemp();
                });
                this.$watch('diskonGlobal', () => {
                    if (!self.isSubmitting) self.autoSaveTemp();
                });
                
                // Listen to form changes
                $('input[name="date"]').on('change input', () => {
                    if (!self.isSubmitting) self.autoSaveTemp();
                });
                $('#branch_id').on('change', () => {
                    if (!self.isSubmitting) self.autoSaveTemp();
                });
            },

            // Manual trigger for testing
            manualSaveTemp() {
                if (this.cart.length > 0) {
                    this.autoSaveTemp();
                }
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

            submitTransaction() {
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
                    .then(data => {
                        console.log(data);
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: 'Data berhasil disimpan.'
                            });
                            this.cart = []; // Kosongkan keranjang
                            document.getElementById('customer_id').value = '';
                            $('#customer_id').val(null).trigger('change');
                            window.location.href = '/pos/show/' + data.id;
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: 'Terjadi kesalahan saat menyimpan data.'
                            });
                        }
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
                this.editProduct = {
                    ...item
                };
                this.editProductName = item.name;
                this.editProductUnit = item.unit;
                this.editTitle = item.name;
                this.editPrice = item.price;
                this.editSell = item.sell;
                this.editQty = item.qty;
                this.editTotal = item.total_input || (item.qty * item.price);
                this.editTotalFormatted = this.formatRupiah(this.editTotal);

                this.editDiscount = item.discount || 0;

                this.editModal = true;

                setTimeout(() => {
                    const modal = new bootstrap.Modal(document.getElementById('editModal'));
                    modal.show();
                }, 0);
            },

            get formattedEditPrice() {
                return this.formatRupiah(this.editProduct?.price || 0);
            },

            set formattedEditPrice(val) {
                const raw = val.replace(/\./g, '').replace(/[^0-9]/g, '');
                this.editProduct.price = Number(raw || 0);
                this.editPrice = this.editProduct.price;
                this.updateEditTotalFromQty();
            },

            get formattedEditSell() {
                return this.formatRupiah(this.editProduct?.sell || 0);
            },

set formattedEditSell(val) {
                const raw = val.replace(/\./g, '').replace(/[^0-9]/g, '');
                this.editProduct.sell = Number(raw || 0);
                this.editSell = this.editProduct.sell;
                this.updateEditTotalFromQty();
            },
            get edit_HPPPercent() {
                const sell = parseFloat(this.editProduct?.sell || this.editSell || 0);
                const price = parseFloat(this.editProduct?.price || this.editPrice || 0);
                if (sell === 0) return '0';
                const percent = ((sell - price) / sell) * 100;
                return percent.toFixed(2);
            },

            updateEditTotalFormatted(e) {
                const raw = e.target.value.replace(/[^\d]/g, '');
                this.editTotal = Number(raw || 0);
                this.editTotalFormatted = this.formatRupiah(this.editTotal);
                this.updateQtyFromEditTotal(); // Sesuaikan qty berdasarkan harga
            },

                updateQtyFromEditTotal() {
                if (this.editProduct && this.editProduct.price > 0) {
                    this.editQty = parseFloat((this.editTotal / this.editProduct.price).toFixed(2));
                }
            },

            updateTotalFromEditQty() {
                if (this.editProduct) {
                    this.editTotal = parseFloat((this.editQty * this.editProduct.price).toFixed(2));
                    this.editTotalFormatted = this.formatRupiah(this.editTotal);
                }
            },
            calculateEditDiscountAmount() {
                const val = parseFloat(this.editDiscount || 0);
                // console.log('Discount:', val);
                if (val <= 100) {
                    let qty = parseFloat(this.editQty || 0);
                    let originalPrice = parseFloat(this.editProduct?.price || this.editPrice || 0);
                    let discount = parseFloat(this.editDiscount || 0);
                    let subtotal = qty * originalPrice;
                    return parseFloat(((subtotal || 0) * val / 100).toFixed(2)); // persen
                } else {
                    return val; // nominal
                }
            },
            // Update otomatis qty berdasarkan total
            updateEditQtyFromTotal() {
                if (this.editProduct && this.editProduct.price > 0) {
                    this.editQty = parseFloat((this.editTotal / this.editProduct.price).toFixed(2));
                }
            },

            // Update total otomatis berdasarkan qty
            updateEditTotalFromQty() {
                if (this.editProduct) {
                    this.editTotal = parseFloat((this.editQty * this.editProduct.price).toFixed(2));
                    this.editTotalFormatted = this.formatRupiah(this.editTotal);
                }
            },

            updateEditDiscount() {
                let qty = parseFloat(this.editQty || 0);
                let originalPrice = parseFloat(this.editProduct?.price || this.editPrice || 0);
                let discount = parseFloat(this.editDiscount || 0);

                // Hitung total sebelum diskon
                let subtotal = qty * originalPrice;

                // Jika discount > 100 → anggap sebagai nominal (Rp)
                // Jika ≤ 100 → anggap sebagai persen
                let discountValue = discount > 100 ? discount : subtotal * (discount / 100);
                let discountPercent = discount > 100 ? 0 : discount;

                let totalAfterDiscount = subtotal - discountValue;

                this.editTotalFormatted = this.formatRupiah(totalAfterDiscount);
                this.editTotal = totalAfterDiscount;
                this.editDiscountPercent = discountPercent;
            },

            saveEditToCart() {
                if (!this.editItem || !Number.isFinite(Number(this.editQty)) || Number(this.editQty) <= 0) {
                    Swal.fire({ icon: 'warning', title: 'Kuantitas tidak valid', text: 'Isi kuantitas lebih dari 0.' });
                    return;
                }
                const idx = this.cart.findIndex(i => (i.cart_id || i.id) === (this.editItem.cart_id || this.editItem.id));
                if (idx !== -1) {
                    const disc = this.calculateEditDiscountAmount();
                    this.cart[idx].price = this.editProduct?.price || 0;
                    this.cart[idx].sell = this.editProduct?.sell || 0;
                    this.cart[idx].qty = this.editQty;
                    this.cart[idx].total_input = this.editTotal;
                    this.cart[idx].discount = disc;
                    this.cart[idx].discountPercent = this.editDiscountPercent;
                }
                this.closeEditModal();
            },

            deleteFromCart() {
                if (!this.editItem) return;

                const index = this.cart.findIndex(item => (item.cart_id || item.id) === (this.editItem.cart_id || this.editItem.id));
                if (index !== -1) {
                    this.cart.splice(index, 1);
                    this.closeEditModal();
                }
            },

            // End edit modal section

            closeEditModal() {
                this.editModal = false;
                const modalEl = document.getElementById('editModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            },

            // Add Product Section
            loadAddProducts() {
                const requestId = ++this.addProductsRequest;
                this.addProductsLoading = true;
                this.addProductsError = false;
                $.ajax({
                    url: '/ajax/listProduct',
                    dataType: 'json',
                    data: { term: this.addProductSearch, status: 'aktif', variant: '0', limit: 10 },
                    success: (products) => {
                        if (requestId !== this.addProductsRequest) return;
                        this.addProducts = products;
                        this.addProductsLoading = false;
                    },
                    error: () => {
                        if (requestId !== this.addProductsRequest) return;
                        this.addProducts = [];
                        this.addProductsLoading = false;
                        this.addProductsError = true;
                    },
                });
            },
            selectAddProduct(product) {
                this.addProduct.id = product.id;
                this.addProduct.name = product.name;
                this.addProduct.unit = product.unit?.abbreviation || '';
                this.addProduct.price = product.last_price ?? product.price ?? 0;
                this.addProduct.sell = product.last_sell_price ?? product.last_price ?? product.price ?? 0;
                this.addProduct.hpp = product.hpp ?? 0;
                this.addValidationError = '';
                this.updateAddTotalFromQty();
            },
            openAddModal() {
                this.showAddModal = true;
                if (document.getElementById('wholesale-product-search')) {
                    this.resetAddForm();
                    this.addProductSearch = '';
                    this.addValidationError = '';
                    const modalEl = document.getElementById('addModal');
                    if (!modalEl.dataset.wholesaleBound) {
                        modalEl.addEventListener('hidden.bs.modal', () => { this.showAddModal = false; });
                        modalEl.dataset.wholesaleBound = 'true';
                    }
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                    this.loadAddProducts();
                    return;
                }
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
                                    status: 'aktif',
                                    variant: '0',
                                    limit: 10
                                };
                            },
                            processResults: data => ({
                                results: data.map(item => ({
                                    id: item.id,
                                    text: item.name,
                                    unit: item.unit,
                                    price: item.price || 0,
                                    hpp: item.hpp || 0,
                                    last_price: item.last_price || null,
                                    last_sell_price: item.last_sell_price || null,
                                }))
                            })
                        }
                    }).on('select2:select', (e) => {
                        const data = e.params.data;
                        this.addProduct.id = data.id;
                        this.addProduct.name = data.text;
                        this.addProduct.unit = data.unit.abbreviation;
                        // Gunakan last_price dari pengadaan sebelumnya jika ada, jika tidak gunakan price biasa
                        this.addProduct.price = data.last_price || data.price || 0;
                        // Gunakan last_sell_price untuk harga jual nanti
                        this.addProduct.sell = data.last_sell_price || data.last_price || data.price || 0;
                        this.addProduct.hpp = data.hpp ?? 0;
                        subtotal = this.addProduct.qty * this.addProduct.price;
                        this.addProduct.formattedAddTotalInput = this.formatRupiah(this.addProduct
                            .total);
                        this.updateAddTotalFromQty();
                    });
                }, 0);
            },
            closeAddModal() {
                this.showAddModal = false;
                const modal = bootstrap.Modal.getInstance(document.getElementById('addModal'));
                if (modal) modal.hide();
            },
            updateAddTotalFromQty() {
                const qty = parseFloat(this.addProduct.qty) || 0;
                const price = parseFloat(this.addProduct.price) || 0;
                const discount = parseFloat(this.addProduct.discount) || 0;
                this.addProduct.total = qty * (price - discount);
                this.addProduct.formattedAddTotalInput = this.formatRupiah(this.addProduct.total);
            },
            updateQtyFromAddTotal(e) {
                let raw = e.target.value.replace(/\./g, '').replace(/[^0-9]/g, '');
                const inputTotal = parseFloat(raw || 0);
                const price = parseFloat(this.addProduct.price || 1);
                const discount = parseFloat(this.addProduct.discount || 0);

                // Hitung qty dari total + diskon
                const qty = (inputTotal + discount) / price;

                this.addProduct.qty = parseFloat(qty.toFixed(2));
                this.addProduct.total = inputTotal;
                this.addProduct.formattedAddTotalInput = this.formatRupiah(inputTotal);
            },

            updateDiscountValue(e) {
                let raw = e.target.value.replace(/\./g, '').replace(/[^0-9]/g, '');
                let input = parseFloat(raw || 0);

                const qty = parseFloat(this.addProduct.qty || 0);
                const price = parseFloat(this.addProduct.price || 0);
                const subtotal = qty * price;

                // Cegah pembagian dengan 0
                if (subtotal === 0) {
                    this.addProduct.discount = 0;
                    this.addProduct.discountNominal = 0;
                    return;
                }

                if (input > 100) {
                    // Input dianggap nominal rupiah
                    this.addProduct.discount = input;
                    this.addProduct.discountNominal = input;
                    this.addProduct.discountPercent = 0;
                } else {
                    // Input dianggap persen
                    let diskonRupiah = (input / 100) * price;
                    this.addProduct.discount = diskonRupiah;
                    this.addProduct.discountNominal = input;
                    this.addProduct.discountPercent = input;
                }

                // Update total setelah diskon
                const totalAfterDiscount = subtotal - (this.addProduct.discount * qty);
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
            get formattedAddSell() {
                return this.formatRupiah(this.addProduct.sell);
            },
            set formattedAddSell(val) {
                const raw = val.replace(/\./g, '').replace(/[^0-9]/g, '');
                this.addProduct.sell = Number(raw || 0);
                this.updateAddTotalFromQty();
            },
            get addProduct_HPPPercent() {
                const sell = parseFloat(this.addProduct.sell) || 0;
                const price = parseFloat(this.addProduct.price) || 0;
                if (sell === 0) return '0';
                const percent = ((sell - price) / sell) * 100;
                return percent.toFixed(2);
            },
            get formattedAddTotal() {
                return this.formatRupiah(this.addProduct.qty * this.addProduct.price);
            },
            saveAddToCart() {
                const isWholesalePicker = !!document.getElementById('wholesale-product-search');
                if (isWholesalePicker) {
                    if (!this.addProduct.id) {
                        this.addValidationError = 'Pilih produk atau bahan terlebih dahulu.';
                        return;
                    }
                    if (!Number.isFinite(Number(this.addProduct.qty)) || Number(this.addProduct.qty) <= 0) {
                        this.addValidationError = 'Isi kuantitas lebih dari 0.';
                        return;
                    }
                    this.updateAddTotalFromQty();
                }
                if (!this.addProduct.id) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Produk belum dipilih',
                        text: 'Silakan pilih produk terlebih dahulu.',
                    });
                    return;
                }
                // if (!this.addProduct.price) {
                //     Swal.fire({
                //         icon: 'warning',
                //         title: 'Harga produk belum diisi',
                //         text: 'Silakan isi harga produk terlebih dahulu.',
                //     });
                //     return;
                // }

                const discount = Number(this.addProduct.discount || 0);
                const total_input = this.addProduct.total;

                this.cart.push({
                    cart_id: Date.now() + '-' + Math.random().toString(36).substr(2, 9),
                    id: this.addProduct.id,
                    name: this.addProduct.name,
                    price: this.addProduct.price,
                    hpp: this.addProduct.hpp,
                    qty: this.addProduct.qty,
                    unit: this.addProduct.unit,
                    sell: this.addProduct.sell,
                    discount: discount > 100 ? discount : (discount / 100) * total_input,
                    discountPercent: this.addProduct.discountPercent,
                    total_input: total_input,
                    typeProduct: 'product',
                });

                console.log('cart', this.cart);
                this.resetAddForm();
                if (isWholesalePicker) this.closeAddModal();
            },

            resetAddForm() {
                this.addProduct = {
                    id: null,
                    name: '',
                    unit: '',
                    price: 0,
                    hpp: 0,
                    discount: 0,
                    sell: 0,
                    total: 0,
                    qty: document.getElementById('wholesale-product-search') ? 0 : 1
                };
                // Reset Select2 input juga
                $('#select_product').val(null).trigger('change');
            },
            // End Add Product Section

            // Total
            totalProduk() {
                return this.cart.length;
            },
            totalHargaKeseluruhan() {
                return this.cart.reduce((sum, item) => {
                    const total = (item.total_input || (item.price * item.qty)) - (item.discount || 0);
                    return sum + total;
                }, 0).toLocaleString('id-ID');
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

                const item = this.cart.find(i => (i.cart_id || i.id) === itemId);
                if (item) {
                    const oldQty = item.qty;
                    item.qty = parseFloat(newQty);

                    // Recalculate total_input proportionally
                    if (item.total_input && oldQty > 0) {
                        const pricePerUnit = (item.total_input + item.discount) / oldQty;
                        item.total_input = (pricePerUnit * item.qty) - item.discount;
                    } else {
                        item.total_input = item.price * item.qty;
                    }
                }
            },

            // Update discount item di cart
            updateCartItemDiscount(itemId, newDiscount) {
                const item = this.cart.find(i => (i.cart_id || i.id) === itemId);
                if (item) {
                    item.discount = parseFloat(newDiscount) || 0;
                }
            },

            // Increment quantity
            incrementQty(itemId) {
                const item = this.cart.find(i => (i.cart_id || i.id) === itemId);
                if (item) {
                    this.updateCartItemQty(itemId, item.qty + 1);
                }
            },

            // Decrement quantity
            decrementQty(itemId) {
                const item = this.cart.find(i => (i.cart_id || i.id) === itemId);
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
                    const itemTotal = (item.total_input || (item.price * item.qty)) - (item.discount || 0);
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
                // Ongkir harus selalu ditambahkan
                return Math.max(totalSetelahDiskon + totalOngkir, 0);
            },
            updateDiskonGlobal(e) {
                const val = parseFloat(e.target.value.replace(/[^\d]/g, '')) || 0;
                this.diskonGlobal = val;
            },
            updatePayment(e) {
                const val = parseFloat(e.target.value.replace(/[^\d]/g, '')) || 0;
                this.payment = val;
            },
            updateOngkirGlobal(e) {
                const val = parseFloat(e.target.value.replace(/[^\d]/g, '')) || 0;
                this.ongkirGlobal = val;
            },
            updateDiskonOngkir(e) {
                const val = parseFloat(e.target.value.replace(/[^\d]/g, '')) || 0;
                this.diskonOngkir = val;
            },
            formatRupiah(number) {
                number = parseFloat(number || 0);
                return number.toLocaleString("id-ID");
            },

            // Both actions share validation and a single submission lock.
            saveTransaction(doneCallback) {
                return this.saveWholesale('draft', doneCallback);
            },
            saveToOrderBook(doneCallback) {
                return this.saveWholesale('posting', doneCallback);
            },
            async saveWholesale(status, doneCallback) {
                if (this.isSubmitting) {
                    if (typeof doneCallback === 'function') doneCallback();
                    return;
                }
                const branchId = document.querySelector('select[name="branch_id"]')?.value;
                if (!this.cart.length || !branchId) {
                    Swal.fire({
                        icon: 'warning',
                        title: !this.cart.length ? 'Keranjang kosong' : 'Cabang belum dipilih',
                        text: !this.cart.length ? 'Silakan tambahkan produk terlebih dahulu!' : 'Silakan pilih cabang terlebih dahulu.',
                    });
                    if (typeof doneCallback === 'function') doneCallback();
                    return;
                }
                clearTimeout(this.autoSaveTimeout);
                this.autoSaveTimeout = null;
                this.isSubmitting = true;
                try {
                    // Finish an earlier temporary save before changing transaction status.
                    await this.autoSaveRequest;
                    const data = {
                        date: document.querySelector('input[name="date"]').value,
                        invoice_number: this.currentInvoiceNumber || document.querySelector('input[name="invoice_number"]').value,
                        branch_id: branchId,
                        items: this.cart,
                        subtotal: this.subtotal,
                        total: this.totalHargaKeseluruhan,
                        status,
                    };
                    const response = await fetch('/wholesale/save-transaction', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(data),
                    });
                    const json = await response.json().catch(() => ({}));
                    if (!response.ok || !json.success) {
                        const messages = json.errors ? Object.values(json.errors).flat().join('\n') : json.message;
                        throw new Error(messages || 'Gagal menyimpan transaksi. Silakan coba lagi.');
                    }
                    redirectToHome();
                } catch (error) {
                    Swal.fire({ icon: 'error', title: 'Gagal menyimpan transaksi', text: error.message || 'Periksa koneksi dan coba lagi.' });
                } finally {
                    this.isSubmitting = false;
                    if (typeof doneCallback === 'function') doneCallback();
                }
            },

            resetPOS() {
                this.cart = [];
                this.diskonGlobal = 0;
                this.ongkirGlobal = 0;
                this.subtotal = 0;
                this.totalHargaKeseluruhan = 0;
            },


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

            loadExistingData(data, detail) {
                // Load existing cart items
                console.log('loadExistingData - data:', data);
                console.log('loadExistingData - detail:', detail);
                
                if (detail) {
                    detail.map(item => {
                        let id = item.type == 'parcel' ? 'parcel' + item.product_id + this.formatShortNumber(item
                            .price) : item.product_id;
                        let productName = item.type == 'parcel' ? item.product.description : item.product.name;
                        const obj = {
                            id: id,
                            name: productName,
                            price: this.sanitizeNumber(Number(item.price || 0)), // pastikan number dulu
                            hpp: parseFloat(item.hpp || 0),
                            sell: this.sanitizeNumber(Number(item.sell_price ?? item.product?.price ?? 0)),
                            qty: this.sanitizeNumber(Number(item.quantity)),
                            unit: item.product.unit.abbreviation,
                            discount: this.sanitizeNumber(Number(item.discount || 0)),
                            discountPercent: item.discountPercent || 0,
                            fee: item.product.fee || 0,
                            total_input: this.sanitizeNumber(Number(item.subtotal ?? item.total_price ?? 0)),
                            typeProduct: item.type || 'product',
                        };
                        this.cart.push(obj);
                    });
                }

                // Set invoice number
                if (data.order_number) {
                    this.currentInvoiceNumber = data.order_number;
                    const invoiceInput = document.querySelector('input[name="invoice_number"]');
                    if (invoiceInput) {
                        invoiceInput.value = data.order_number;
                    }
                }

                // Set global discount
                if (data.discount) {
                    this.diskonGlobal = parseFloat(data.discount || 0);
                }

                if (data.branch) {
                    const branchSelect = $('#branch_id');
                    if (!branchSelect.find('option[value="' + data.branch.id + '"]').length) {
                        branchSelect.append(new Option(data.branch.name, data.branch.id, true, true));
                    }
                    branchSelect.val(data.branch.id).trigger('change');
                }

                if (data.payment_method) {
                    let optionPayment = new Option(data.payment_method.name, data.payment_method.id, true, true);
                    $('#payment_id').append(optionPayment).val(data.payment_method.id).trigger('change');
                }

                this.payment = data.paid;
            },

            // Auto-save function for temp draft (debounced like PosController)
            autoSaveTemp() {
                // Skip if cart is empty or currently submitting
                if (!this.autoSaveEnabled || this.isSubmitting) {
                    console.log('Auto-save skipped: cart empty or submitting, isSubmitting:', this.isSubmitting);
                    return;
                }

                // Clear previous timeout
                if (this.autoSaveTimeout) {
                    clearTimeout(this.autoSaveTimeout);
                }

                // Debounce auto-save (5 seconds)
                this.autoSaveTimeout = setTimeout(async () => {
                    await this.autoSaveRequest;
                    // Check again before executing
                    if (this.isSubmitting) {
                        console.log('Auto-save skipped: isSubmitting became true');
                        return;
                    }
                    
                    const transactionDate = document.querySelector('input[name="date"]')?.value;
                    const invoiceNumber = document.querySelector('input[name="invoice_number"]')?.value;
                    const branchId = document.querySelector('select[name="branch_id"]')?.value;

                    if (!branchId) {
                        console.log('Auto-save skipped: no branch selected');
                        return;
                    }

                    const data = {
                        date: transactionDate,
                        invoice_number: this.currentInvoiceNumber || invoiceNumber || '',
                        branch_id: branchId,
                        items: this.cart,
                        subtotal: this.subtotal,
                        total: this.totalHargaKeseluruhan,
                        status: 'temp',
                    };

                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    if (!csrfToken) {
                        console.error('Auto-save: CSRF token not found');
                        return;
                    }

                    this.autoSaveRequest = fetch('{{ route("wholesale.save-transaction") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(data),
                    })
                    .then(async res => {
                        const json = await res.json().catch(() => ({}));
                        console.log('Auto-save response:', res.status, json);
                        if (res.ok && json.success && !this.isSubmitting) {
                            this.currentInvoiceNumber = json.invoice_number;
                            const invoiceInput = document.querySelector('input[name="invoice_number"]');
                            if (invoiceInput && json.invoice_number) {
                                invoiceInput.value = json.invoice_number;
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Auto-save error:', error);
                    });
                }, 5000); // 1 second delay
            },

            transactionInput() {
                return {
                    type: "{{ $data->type ?? 'pemasukan' }}",
                    amount: 0,
                    amountFormatted: '',

                    toggleType() {
                        this.type = this.type === 'pemasukan' ? 'pengeluaran' : 'pemasukan';
                    },
                }
            },
        }
    }
</script>
