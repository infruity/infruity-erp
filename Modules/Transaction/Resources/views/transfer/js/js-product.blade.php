<script>
    function posApp() {
        return {
            products: [],
            cart: [],
            parcel: [],
            jus: [],

            // edit product
            showEditModal: false,
            editItem: null,
            editPrice: 0,
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

            // Correction Section
            showCorrectionModal: false,
            correctionItem: null,
            correctionQty: 0,
            correctionNote: '',
            correctionLoading: false,

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
                let url = '{{ Request::segment(3) }}';
                let segment2 = '{{ Request::segment(2) }}';
                const data = @json($data ?? null);
                const detail = @json($detail ?? null);
                if ((segment2 == 'edit' || segment2 == 'show' || (data && detail && detail.length > 0)) && !this._loaded) {
                    this.loadExistingData(data, detail);
                    // console.log('loadExisting', url, segment2, data, detail);
                    this._loaded = true;

                    // console.log('cart =>', this.cart);
                    // console.log('parcel =>', this.parcel);
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
            handleItemClick(item) {
                @if($is_view && ($data->status ?? '') !== 'selesai')
                    @if(($type ?? '') == 'transfer-penerima')
                        this.openCorrectionModal(item);
                    @endif
                @else
                    if (item.typeProduct === 'parcel') {
                        this.openEditParcelModal(item);
                    } else {
                        this.openEditModal(item);
                    }
                @endif
            },

            openEditModal(item) {
                // console.log('Opening edit modal for item:', item);
                this.editItem = {
                    ...item
                }; // salin data item
                this.editProduct = item;
                this.editProductName = item.name;
                this.editProductUnit = item.unit;
                this.editTitle = item.name;
                this.editPrice = item.price;
                this.editQty = item.qty;
                this.editTotal = item.total_input || (item.qty * item.price);
                this.editTotalFormatted = this.formatRupiah(this.editTotal);

                this.editDiscount = item.discount || 0;


                this.showEditModal = true;

                setTimeout(() => {
                    const modalEl = document.getElementById('editModal');
                    if (modalEl) {
                        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                        modal.show();
                    }
                }, 0);
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
                if (this.editProduct && this.editProduct.price > 0) {
                    this.editTotal = parseFloat((this.editQty * this.editProduct.price).toFixed(2));
                    this.editTotalFormatted = this.formatRupiah(this.editTotal);
                }
            },
            calculateEditDiscountAmount() {
                const val = parseFloat(this.editDiscount || 0);
                // console.log('Discount:', val);
                if (val <= 100) {
                    let qty = parseFloat(this.editQty || 0);
                    let originalPrice = parseFloat(this.editPrice || 0);
                    let discount = parseFloat(this.editDiscount || 0);
                    let subtotal = qty * originalPrice;
                    return parseFloat(((subtotal || 0) * val / 100).toFixed(2)); // persen
                } else {
                    return val; // nominal
                }
            },
            // Update otomatis qty berdasarkan total
            updateEditQtyFromTotal() {
                if (this.editItem && this.editItem.price > 0) {
                    this.editQty = parseFloat((this.editTotal / this.editItem.price).toFixed(2));
                }
            },

            // Update total otomatis berdasarkan qty
            updateEditTotalFromQty() {
                if (this.editItem) {
                    this.editTotal = parseFloat((this.editQty * this.editItem.price).toFixed(2));
                }
            },

            updateEditDiscount() {
                let qty = parseFloat(this.editQty || 0);
                let originalPrice = parseFloat(this.editPrice || 0);
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
                const idx = this.cart.findIndex(i => i.id === this.editItem.id);
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
                    this.closeEditModal();
                }
            },

            removeFromCart(item) {
                Swal.fire({
                    title: 'Hapus Produk?',
                    text: "Apakah Anda yakin ingin menghapus " + item.name + " dari keranjang?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const index = this.cart.findIndex(i => i.id === item.id);
                        if (index !== -1) {
                            this.cart.splice(index, 1);
                        }
                    }
                });
            },

            openEditParcelModal(item) {
                // For now, parcels use the same edit modal logic
                this.openEditModal(item);
            },

            // End edit modal section

            closeEditModal() {
                this.showEditModal = false;
                const modalEl = document.getElementById('editModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            },

            // Add Product Section
            openAddModal() {
                const branchId = document.getElementById('branch_id').value;
                if (!branchId) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Cabang belum dipilih',
                        text: 'Silakan pilih cabang asal terlebih dahulu.',
                    });
                    return;
                }

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
                                    status: 'aktif',
                                    variant: '0',
                                    branch: branchId,
                                    has_stock: '{{ ($type ?? "") == "transfer-pengirim" ? 1 : 0 }}',
                                    limit: 10
                                };
                            },
                            processResults: data => ({
                                results: data.map(item => ({
                                    id: item.id,
                                    text: item.name,
                                    unit: item.unit,
                                    price: item.hpp || 0,
                                    hpp: item.hpp || 0,
                                    stock_available: item.get_stock?.stock_available ?? 0,
                                }))
                            })
                        },
                        templateResult: data => {
                            if (data.loading) return data.text;
                            const stock = data.stock_available ?? 0;
                            const disabled = stock <= 0;
                            const $el = $(`<span class="${disabled ? 'text-muted' : ''}">${data.text} <span class="badge badge-light-${stock > 0 ? 'success' : 'danger'} ms-2">Stok: ${stock}</span></span>`);
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
                // if (!this.addProduct.price) {
                //     Swal.fire({
                //         icon: 'warning',
                //         title: 'Harga produk belum diisi',
                //         text: 'Silakan isi harga produk terlebih dahulu.',
                //     });
                //     return;
                // }

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
                    price: this.addProduct.price,
                    hpp: this.addProduct.hpp,
                    qty: this.addProduct.qty,
                    unit: this.addProduct.unit,
                    discount: discount > 100 ? discount : (discount / 100) * total_input,
                    discountPercent: this.addProduct.discountPercent,
                    total_input: total_input,
                    typeProduct: 'product',
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

                const item = this.cart.find(i => i.id === itemId);
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
                const transactionDate = document.querySelector('input[name="date"]').value;
                const invoiceNumber = document.querySelector('input[name="invoice_number"]').value;
                const branchId = document.querySelector('select[name="branch_id"]').value;
                const branchDestinationId = document.querySelector('select[name="branch_destination_id"]').value;
                const transferId = document.querySelector('input[name="transfer_id"]')?.value || null;

                let type = window.location.pathname.split('/')[1];
                const data = {
                    transfer_id: transferId,
                    date: transactionDate,
                    invoice_number: invoiceNumber,
                    branch_id: branchId,
                    branch_destination_id: branchDestinationId,
                    items: this.cart,
                    subtotal: this.subtotal,
                    total: this.totalHargaKeseluruhan,
                    status: 'draft',
                    type: type,
                };
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                fetch('/transfer/save-transaction', {
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

                        if (!res.ok) {
                            if (json.errors) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Validasi gagal',
                                    html: Object.values(json.errors)
                                        .map(msg => `<div>${msg}</div>`)
                                        .join('')
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Gagal',
                                    text: json.message || 'Terjadi kesalahan.',
                                });
                            }

                            throw new Error("Request Failed");
                        }

                        if (json.redirect_url) {
                            window.location.href = json.redirect_url;
                        } else {
                            redirectToHome();
                        }
                        if (typeof doneCallback === 'function') doneCallback();

                    })
                    .catch(err => {
                        console.error(err);
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: 'Gagal menyimpan transaksi.',
                        });
                        if (typeof doneCallback === 'function') doneCallback();
                    });
            },
            // Save Order Book
            saveToOrderBook(doneCallback) {
                if (this.cart.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Keranjang kosong',
                        text: 'Silakan tambahkan produk terlebih dahulu!',
                    });
                    if (typeof doneCallback === 'function') doneCallback();
                    return;
                }
                const transactionDate = document.querySelector('input[name="date"]').value;
                const invoiceNumber = document.querySelector('input[name="invoice_number"]').value;
                const branchId = document.querySelector('select[name="branch_id"]').value;
                const branchDestinationId = document.querySelector('select[name="branch_destination_id"]').value;
                const transferId = document.querySelector('input[name="transfer_id"]')?.value || null;

                let type = window.location.pathname.split('/')[1];
                const data = {
                    transfer_id: transferId,
                    date: transactionDate,
                    invoice_number: invoiceNumber,
                    items: this.cart,
                    branch_id: branchId,
                    branch_destination_id: branchDestinationId,
                    subtotal: this.subtotal,
                    total: this.totalHargaKeseluruhan,
                    status: 'pending',
                    type: type,
                };

                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                fetch('/transfer/save-transaction', {
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

                        if (!res.ok) {
                            if (json.errors) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Validasi gagal',
                                    html: Object.values(json.errors)
                                        .map(msg => `<div>${msg}</div>`)
                                        .join('')
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Gagal',
                                    text: json.message || 'Terjadi kesalahan.',
                                });
                            }

                            throw new Error("Request Failed");
                        }

                        if (json.redirect_url) {
                            window.location.href = json.redirect_url;
                        } else {
                            redirectToHome();
                        }
                        if (typeof doneCallback === 'function') doneCallback();

                    })
                    .catch(err => {
                        console.error(err);
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: 'Gagal menyimpan transaksi.',
                        });
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
                const ongkirAddress = document.querySelector('textarea[name="ongkir_address"]').value;

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
                        body: JSON.stringify(data),
                    })
                    .then(res => res.json())
                    .then(res => {
                        // console.log(res);
                        // this.resetPOS(); // Reset cart dsb.
                        if (typeof doneCallback === 'function') doneCallback();
                        redirectToPayment(res.transaksi_id);
                    })
                    .catch(err => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: 'Gagal menyimpan transaksi.',
                        });
                        console.error(err);
                    });
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
                console.log('data', data);
                console.log('detail', detail);
                detail.map(item => {
                    let id = item.type == 'parcel' ? 'parcel' + item.product_id + this.formatShortNumber(item
                        .price) : item.product_id;
                    let productName = item.type == 'parcel' ? item.product.description : item.product.name;
                    const obj = {
                        id: id,
                        name: productName,
                        price: this.sanitizeNumber(Number(item.price || 0)), // pastikan number dulu
                        hpp: parseFloat(item.hpp || 0),
                        qty: this.sanitizeNumber(Number(item.quantity)),
                        unit: item.product.unit ? item.product.unit.abbreviation : '',
                        discount: this.sanitizeNumber(Number(item.discount || 0)),
                        discountPercent: item.discountPercent || 0,
                        fee: item.product.fee || 0,
                        total_input: this.sanitizeNumber(Number(item.subtotal || 0)),
                        typeProduct: item.type || 'product',
                        detail_id: item.id,
                        corrections: item.corrections || [],
                    };

                    // Identify original_qty
                    if (obj.corrections.length > 0) {
                        // The oldest correction's old_quantity is the very first qty
                        obj.original_qty = this.sanitizeNumber(Number(obj.corrections[0].old_quantity));
                    } else {
                        obj.original_qty = obj.qty;
                    }

                    this.cart.push(obj);
                });

                if (data.branch) {
                    let option = new Option(data.branch.name, data.branch.id, true, true);
                    $('#branch_id').append(option).val(data.branch.id).trigger('change');
                }

                if (data.branch_destination) {
                    let optionDestination = new Option(data.branch_destination.name, data.branch_destination.id, true, true);
                    $('#branch_destination_id').append(optionDestination).val(data.branch_destination.id).trigger('change');
                }

                if (data.date) {
                    $('input[name="date"]').val(data.date);
                }

                this.payment = data.paid;
            },

            setSelesai() {
                const id = '{{ $data->id ?? '' }}';
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const type = window.location.pathname.split('/')[1];

                fetch('/' + type + '/set-selesai/' + id, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                    })
                    .then(async res => {
                        const json = await res.json().catch(() => ({}));
                        if (res.ok) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: json.message || 'Status berhasil diubah',
                            }).then(() => {
                                window.location.href = '/' + type;
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: json.message || 'Gagal mengubah status',
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Terjadi kesalahan: ' + error.message,
                        });
                    });
            },

            openCorrectionModal(item) {
                this.correctionItem = item;
                this.correctionQty = item.qty;
                this.correctionNote = '';
                this.showCorrectionModal = true;
                
                setTimeout(() => {
                    const modalEl = document.getElementById('correctionModal');
                    if (modalEl) {
                        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                        modal.show();
                    }
                }, 0);
            },

            saveCorrection() {
                if (!this.correctionItem || !this.correctionItem.detail_id) {
                    Swal.fire('Error', 'ID detail tidak ditemukan', 'error');
                    return;
                }

                const payload = {
                    detail_id: this.correctionItem.detail_id,
                    quantity: this.correctionQty,
                    note: this.correctionNote
                };

                this.correctionLoading = true;
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                fetch('/transfer/save-correction', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                })
                .then(res => res.json())
                .then(data => {
                    this.correctionLoading = false;
                    if (data.success) {
                        Swal.fire('Berhasil', data.message, 'success').then(() => {
                            // Update local state instead of full reload for speed
                            this.correctionItem.qty = this.correctionQty;
                            this.correctionItem.total_input = data.new_subtotal;
                            
                            // Close modal
                            const modalEl = document.getElementById('correctionModal');
                            const modal = bootstrap.Modal.getInstance(modalEl);
                            if (modal) modal.hide();
                            
                            // Optional: still reload if you want to refresh history, 
                            // but doing it here might be what the user felt was slow.
                            // window.location.reload(); 
                        });
                    } else {
                        Swal.fire('Gagal', data.message, 'error');
                    }
                })
                .catch(err => {
                    this.correctionLoading = false;
                    console.error(err);
                    Swal.fire('Error', 'Terjadi kesalahan sistem', 'error');
                });
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
    function redirectToHome(transaksiId) {
        let type = window.location.pathname.split('/')[1];
        if (type === 'transfer-penerima') {
            window.location.href = '/transfer-penerima';
        } else if (type === 'transfer-pengirim') {
            window.location.href = '/transfer-pengirim';
        } else {
            window.location.href = '/transfer';
        }
    }

    function redirectToPayment(transaksiId) {
        window.location.href = '/transfer/payment/' + transaksiId;
    }
</script>
