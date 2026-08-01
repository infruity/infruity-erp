<script>
    function parcelForm() {
        return {
            parcels: [],
            totalAll: 0,
            parcelId: 0,
            cart: [],
            qtyParcel: 1,
            budgetParcel: '',
            // get totalAll() {
            //     // kalau "Harga Jual" = budget per item, cukup jumlahkan price
            //     const total = this.parcels.reduce((sum, p) => sum + (Number(p.price) || 0), 0);
            //     const biayaJasa = parseInt(document.getElementById('parcel_jasa').value.replace(/\./g, ''), 10) ||
            //         0;
            //     const kemasanPrice = parseInt(document.getElementById('kemasan_price').value.replace(/\./g, ''),
            //             10) ||
            //         0;
            //     return total + biayaJasa + kemasanPrice;

            //     // kalau maunya total = qty * hargaAsli per item, pakai ini:
            //     // return this.parcels.reduce((sum, p) => sum + (Number(p.qty||0) * Number(p.priceAwal||0)), 0);
            // },

            refreshSelect2Values() {
                $('.parcel-select, .parcel-select-edit').each(function() {
                    if ($(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2('destroy');
                    }
                });
                this.initSelect2();
                
                $('.parcel-select, .parcel-select-edit').each((i, el) => {
                    let idx = $(el).data('index');
                    if (this.parcels[idx] && this.parcels[idx].product) {
                        if ($(el).find(`option[value='${this.parcels[idx].product}']`).length === 0) {
                            let newOption = new Option(this.parcels[idx].displayName, this.parcels[idx].product, true, true);
                            $(el).append(newOption);
                        }
                        $(el).val(this.parcels[idx].product).trigger('change.select2');
                    }
                });
            },
            setParcelId(id) {
                this.parcelId = id;
            },

            resetParcel() {
                this.parcels = [];
            },
            setParcel(item) {
                // this.parcels = []; // reset
                const normalizedUnit = this.getUnitLabel(item.unit);
                this.parcels.push({
                    ...item,
                    unit: normalizedUnit,
                    displayName: item.displayName || this.formatParcelProductName({
                        name: item.name,
                        text: item.text,
                        unit: normalizedUnit
                    })
                }); // masukkan data baru
                this.$nextTick(() => {
                    this.refreshSelect2Values();
                    this.updateTotal();
                });
            },

            addParcel() {
                this.parcels.push({
                    product: '',
                    name: '',
                    unit: '',
                    displayName: '',
                    priceAwal: 0,
                    qty: 1,
                    price: 0,
                    priceFormatted: '',
                    hpp: 0
                });
                this.$nextTick(() => {
                    this.refreshSelect2Values();
                    this.updateTotal();
                });
            },
            removeParcel(index) {
                this.parcels.splice(index, 1);
                this.$nextTick(() => {
                    this.refreshSelect2Values();
                    this.updateTotal();
                });
            },
            updatePrice(index) {
                let raw = (this.parcels[index].priceFormatted || '').replace(/\D/g, '');
                let hargaJualBaru = parseInt(raw);
                if (isNaN(hargaJualBaru)) hargaJualBaru = 0;

                this.parcels[index].price = hargaJualBaru;
                this.parcels[index].priceFormatted = this.formatRupiah(hargaJualBaru);

                let hargaAsli = parseInt(this.parcels[index].priceAwal || 0);
                if (hargaAsli > 0) {
                    let qtyBaru = hargaJualBaru / hargaAsli;
                    this.parcels[index].qty = parseFloat(qtyBaru.toFixed(2));
                }
                this.updateTotal();
            },
            updateFromQty(index) {
                let hargaAsli = parseInt(this.parcels[index].priceAwal || 0);
                if (hargaAsli > 0) {
                    let hargaBaru = this.parcels[index].qty * hargaAsli;
                    this.parcels[index].price = hargaBaru;
                    this.parcels[index].priceFormatted = this.formatRupiah(hargaBaru);
                }
                this.updateTotal();
            },

            updateQty(index) {
                console.log('updateQty', index);
                const item = this.parcels[index];

                // Pastikan harga asli tersimpan (misalnya item.priceAwal = harga satuan)
                const hargaAwal = Number(item.priceAwal || 0);
                let qty = Number(item.qty || 1);
                const stockAvailable = Number(item.stock_available || 0);
                
                if (qty > stockAvailable && stockAvailable > 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Stok tidak mencukupi',
                        text: 'Sisa stok tersedia hanya ' + stockAvailable + '.',
                    });
                    item.qty = stockAvailable;
                    qty = stockAvailable;
                }

                // Hitung ulang total price berdasarkan qty
                const total = hargaAwal * qty;

                item.price = total;
                item.priceFormatted = this.formatRupiah(total);

                // update total keseluruhan juga
                this.updateTotal();
            },

            parseNumber(value) {
                if (!value) return 0;

                // Coba pakai regex untuk hapus karakter non-digit
                const clean = value.toString().replace(/[^\d]/g, "");
                return parseInt(clean, 10) || 0;
            },

            updateTotal() {
                // total produk: per item price * qty
                const totalProduk = this.parcels.reduce((sum, p) => sum + (Number(p.price) || 0), 0);

                // baca biaya jasa / kemasan: coba DOM dulu; kalau tidak ada fallback ke state
                const jasaEl = document.getElementById('parcel_jasa');
                const jasaEditEl = document.getElementById('parcel_edit_jasa');
                const kemasanEl = document.getElementById('kemasan_price');
                const kemasanEditEl = document.getElementById('kemasan_edit_price');

                const biayaJasa = jasaEl ? this.parseNumber(jasaEl.value) : this.parseNumber(this.biayaJasa);
                const biayaJasaEdit = jasaEditEl ? this.parseNumber(jasaEditEl.value) : this.parseNumber(this.biayaJasa);
                const kemasanPrice = kemasanEl ? this.parseNumber(kemasanEl.value) : this.parseNumber(this.kemasanPrice);
                const kemasanPriceEdit = kemasanEditEl ? this.parseNumber(kemasanEditEl.value) : this.parseNumber(this.kemasanPrice);

                this.biayaJasa = biayaJasa > 0 ? biayaJasa : biayaJasaEdit;
                this.kemasanPrice = kemasanPrice > 0 ? kemasanPrice : kemasanPriceEdit;

                // set final total numeric
                this.totalAll = totalProduk + biayaJasa + kemasanPrice + biayaJasaEdit + kemasanPriceEdit;

                // (opsional) update kemasan input display
                if (kemasanEl) kemasanEl.value = kemasanPrice ? kemasanPrice.toLocaleString('id-ID') : '';
                if (kemasanEditEl) kemasanEditEl.value = kemasanPriceEdit ? kemasanPriceEdit.toLocaleString('id-ID') : '';
                // (opsional) jika ada elemen di UI yang menampilkan total, kamu bind ke totalAll dan format di template
            },

            formatRupiah(angka) {
                let num = this.parseNumber(angka);
                if (isNaN(num) || num < 0) num = 0;
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0
                }).format(num);
            },
            getUnitLabel(unit) {
                if (!unit) return '';
                if (typeof unit === 'string') return unit;
                return unit.abbreviation || unit.name || '';
            },
            formatParcelProductName(item) {
                if (!item) return '';

                const productName = item.displayName || item.text || item.name || '';
                const unitLabel = this.getUnitLabel(item.unit);

                if (!unitLabel) return productName;
                return productName.includes(`(${unitLabel})`) ? productName : `${productName} (${unitLabel})`;
            },
            initSelect2() {
                $('.parcel-select:not(.select2-hidden-accessible)').select2({
                    placeholder: 'Pilih Buah',
                    language: {
                        errorLoading: function() {
                            return "Belum ada Buah yang dibuat.";
                        }
                    },
                    dropdownParent: $('#parcelModal'),
                    templateResult: (data) => {
                        if (data.loading) return data.text;
                        const stock = data.stock_available ?? 0;
                        const disabled = stock <= 0;
                        const roundedStock = Math.round(stock * 100) / 100;
                        const $el = $(`<span class="${disabled ? 'text-muted' : ''}">${data.text} <span class="badge badge-light-${stock > 0 ? 'success' : 'danger'} ms-2">Stok: ${roundedStock}</span></span>`);
                        if (disabled) {
                            $el.css('cursor', 'not-allowed');
                        }
                        return $el;
                    },
                    templateSelection: (data) => {
                        if (!data.id) return data.text;
                        const stock = data.stock_available ?? 0;
                        if (stock <= 0) return $(`<span class="text-muted">${data.text} (Stok habis)</span>`);
                        return data.text;
                    },
                    ajax: {
                        url: '/ajax/listProduct',
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                term: params.term,
                                branch: $('#branch_id').val(),
                                status: 'aktif',
                                limit: 10
                            };
                        },
                        processResults: (data) => {
                            window.productStockMap = window.productStockMap || {};
                            data.forEach(item => {
                                window.productStockMap[item.id] = item.parent_product ? item.parent_product.parent_id : item.id;
                            });
                            return {
                                results: data.map(item => {
                                    let qtyInCart = 0;
                                    let debugStr = "";
                                    const mainApp = window.mainCartInstance;
                                    if (mainApp && typeof mainApp.calculateUsedStock === 'function') {
                                        const currentParcelQty = $('#parcel_qty').val() || 1;
                                        qtyInCart = mainApp.calculateUsedStock(item.id, this.parcels, currentParcelQty);
                                    }
                                    let stock_available = (item.get_stock?.stock_available ?? 0) - qtyInCart;
                                    return {
                                        id: item.id,
                                        text: item.name,
                                        unit: item.unit,
                                        price: item.price,
                                        hpp: item.hpp,
                                        stock_available: stock_available,
                                        original_stock: item.get_stock?.stock_available ?? 0,
                                    };
                                })
                            };
                        },
                        cache: true
                    }
                }).on('select2:select', (e) => {
                    let index = $(e.target).data('index');
                    let data = e.params.data;
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
                    this.parcels[index].product = data.id;
                    this.parcels[index].name = data.text;
                    this.parcels[index].unit = data.unit;
                    this.parcels[index].displayName = this.formatParcelProductName({
                        text: data.text,
                        unit: data.unit
                    });
                    this.parcels[index].priceAwal = data.price;
                    this.parcels[index].stock_available = data.stock_available ?? 0;
                    this.parcels[index].original_stock = data.original_stock ?? 0;
                    this.parcels[index].hpp = data.hpp;
                    this.parcels[index].price = data.price;
                    this.parcels[index].priceFormatted = this.formatRupiah(data.price);
                    this.parcels[index].qty = 1;
                    this.updateTotal();
                });

                $('.parcel-select-edit:not(.select2-hidden-accessible)').select2({
                    placeholder: 'Pilih Parcel',
                    dropdownParent: $('#parcelEditModal'),
                    templateResult: (data) => {
                        if (data.loading) return data.text;
                        const stock = data.stock_available ?? 0;
                        const disabled = stock <= 0;
                        const roundedStock = Math.round(stock * 100) / 100;
                        const $el = $(`<span class="${disabled ? 'text-muted' : ''}">${data.text} <span class="badge badge-light-${stock > 0 ? 'success' : 'danger'} ms-2">Stok: ${roundedStock}</span></span>`);
                        if (disabled) {
                            $el.css('cursor', 'not-allowed');
                        }
                        return $el;
                    },
                    templateSelection: (data) => {
                        if (!data.id) return data.text;
                        const stock = data.stock_available ?? 0;
                        if (stock <= 0) return $(`<span class="text-muted">${data.text} (Stok habis)</span>`);
                        return data.text;
                    },
                    ajax: {
                        url: '/ajax/listProduct',
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                term: params.term,
                                branch: $('#branch_id').val(),
                                status: 'aktif',
                                limit: 10
                            };
                        },
                        processResults: (data) => {
                            window.productStockMap = window.productStockMap || {};
                            data.forEach(item => {
                                window.productStockMap[item.id] = item.parent_product ? item.parent_product.parent_id : item.id;
                            });
                            return {
                                results: data.map(item => {
                                    let qtyInCart = 0;
                                    const mainApp = window.mainCartInstance;
                                    if (mainApp && typeof mainApp.calculateUsedStock === 'function') {
                                        const currentParcelQty = $('#parcel_edit_qty').val() || 1;
                                        qtyInCart = mainApp.calculateUsedStock(item.id, this.parcels, currentParcelQty);
                                    }
                                    let stock_available = (item.get_stock?.stock_available ?? 0) - qtyInCart;
                                    return {
                                        id: item.id,
                                        text: item.name,
                                        unit: item.unit,
                                        price: item.price,
                                        hpp: item.hpp,
                                        stock_available: stock_available,
                                        original_stock: item.get_stock?.stock_available ?? 0,
                                    };
                                })
                            };
                        },
                        cache: true
                    }
                }).on('select2:select', (e) => {
                    let index = $(e.target).data('index');
                    let data = e.params.data;
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
                    this.parcels[index].product = data.id;
                    this.parcels[index].name = data.text;
                    this.parcels[index].unit = data.unit;
                    this.parcels[index].displayName = this.formatParcelProductName({
                        text: data.text,
                        unit: data.unit
                    });
                    this.parcels[index].priceAwal = data.price;
                    this.parcels[index].stock_available = data.stock_available ?? 0;
                    this.parcels[index].original_stock = data.original_stock ?? 0;
                    this.parcels[index].hpp = data.hpp;
                    this.parcels[index].price = data.price;
                    this.parcels[index].priceFormatted = this.formatRupiah(data.price);
                    this.parcels[index].qty = 1;
                    this.updateTotal();
                });
            },
            saveParcelToCart() {
                if (this.parcels.some(item => parseFloat(item.qty) <= 0)) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Kuantitas tidak valid',
                        text: 'Quantity product (bahan) harus lebih dari 0.',
                    });
                    return;
                }

                const budget = document.getElementById('parcel_budget').value;
                const qty = document.getElementById('parcel_qty').value;
                if (parseFloat(qty) <= 0 || !qty) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Kuantitas tidak valid',
                        text: 'Jumlah parcel harus lebih dari 0.',
                    });
                    return;
                }
                const fee = document.getElementById('parcel_jasa').value;
                let kemasan = $('#select_kemasan option:selected').text();
                let kemasanId = $('#select_kemasan option:selected').val();
                if (!kemasanId) {
                    kemasanId = defaultKemasanId;
                    kemasan = defaultKemasanName;
                }
                console.log(kemasanId, kemasan);
                const kemasanPrice = $('#kemasan_price').val();
                const budgetValue = this.parseNumber(budget);
                const feeValue = this.parseNumber(fee);
                const kemasanPriceValue = this.parseNumber(kemasanPrice);

                const saveProcess = () => {
                    // --- Validasi stok bahan parcel ---
                    const posAppInstance = Alpine.$data(document.querySelector('[x-data="posApp()"]'));
                    const parcelQty = parseFloat(qty) || 1;
                    const stockErrors = [];

                    this.parcels.forEach(item => {
                        const productId = item.product;
                        if (!productId) return;

                        const originalStock = parseFloat(item.original_stock) || 0;
                        const qtyPerParcel  = parseFloat(item.qty) || 0;
                        const totalQtyNeed  = qtyPerParcel * parcelQty;

                        let totalUsed = 0;
                        if (posAppInstance && typeof posAppInstance.calculateUsedStock === 'function') {
                            totalUsed = posAppInstance.calculateUsedStock(productId);
                        }

                        if ((totalUsed + totalQtyNeed) > originalStock) {
                            const productName = item.displayName || item.name || `Produk #${productId}`;
                            const sisa = (originalStock - totalUsed).toFixed(2);
                            stockErrors.push(`- ${productName} (Dibutuhkan: ${totalQtyNeed}, Sisa stok: ${sisa})`);
                        }
                    });

                    if (stockErrors.length > 0) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Stok Bahan Tidak Mencukupi',
                            html: 'Beberapa bahan parcel tidak mencukupi:<br>' + stockErrors.join('<br>'),
                        });
                        return;
                    }
                    // --- End validasi stok ---

                    const normalizedParcels = this.parcels.map(item => ({
                        ...item,
                        unit: this.getUnitLabel(item.unit),
                        displayName: item.displayName || this.formatParcelProductName(item)
                    }));

                    const parcelId = 'parcel' + kemasanId + this.formatShortNumber(budget) + '_' + Date.now();
                    const parcel = {
                        id: parcelId,
                        name: 'Parcel ' + kemasan + '-' + this.formatShortNumber(budget),
                        price: budgetValue,
                        fee: feeValue,
                        hpp: 0,
                        qty: qty,
                        unit: 'Parcel',
                        discount: 0,
                        discountPercent: 0,
                        total_input: 0,
                        kemasanId: kemasanId,
                        kemasanName: kemasan,
                        kemasanPrice: kemasanPriceValue,
                        typeProduct: 'parcel',
                    };
                    const posParcel = {
                        id: parcelId,
                        budget: budgetValue,
                        qty: qty,
                        kemasan: kemasan,
                        kemasanId: kemasanId,
                        kemasanPrice: kemasanPriceValue,
                        hpp: this.totalAll,
                        fee: feeValue,
                        data: normalizedParcels,
                        type: 'parcel',
                    }

                    posAppInstance.cart.push(parcel);
                    posAppInstance.parcel.push(posParcel);
                    document.getElementById('parcel_budget').value = '';
                    document.getElementById('parcel_qty').value = 1;
                    document.getElementById('parcel_jasa').value = '';
                    $('#select_kemasan').val(null).trigger('change');
                    this.parcels = [];
                    
                    const modalEl = document.getElementById('parcelModal');
                    if(modalEl) {
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if(modal) modal.hide();
                    }
                };

                if (this.totalAll > budgetValue) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Perhatian',
                        text: 'Total keseluruhan parcel (' + this.formatRupiah(this.totalAll) + ') lebih besar dari budget (' + this.formatRupiah(budgetValue) + '). Tetap lanjutkan?',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Lanjutkan',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            saveProcess();
                        }
                    });
                } else {
                    saveProcess();
                }

                // console.log("Cart sekarang:", posAppInstance.cart, posAppInstance.parcel);
                // console.log('Parcel:', this.parcels, 'Product', parcel);
                // this.parcels.forEach(item => {
                //     console.log("Produk ID:", item.product);
                //     console.log("Nama:", item.name);
                //     console.log("Unit:", item.unit.name);
                //     console.log("Harga Awal:", item.priceAwal);
                //     console.log("Qty:", item.qty);
                // });
                // let budgetClean = parseInt(this.budgetParcel.replace(/[^0-9]/g, '')) || 0;

                // posApp.cart.push({
                //     id: Date.now(), // ID unik
                //     name: 'Parcel',
                //     qty: this.qtyParcel,
                //     budget: budgetClean
                // });

                // this.closeAddModal();
            },

            editParcelToCart(parcelId) {
                console.log(parcelId);
                
                if (this.parcels.some(item => parseFloat(item.qty) <= 0)) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Kuantitas tidak valid',
                        text: 'Quantity product (bahan) harus lebih dari 0.',
                    });
                    return;
                }

                const budget = document.getElementById('parcel_edit_budget').value;
                const qty = document.getElementById('parcel_edit_qty').value;
                if (parseFloat(qty) <= 0 || !qty) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Kuantitas tidak valid',
                        text: 'Jumlah parcel harus lebih dari 0.',
                    });
                    return;
                }
                const fee = document.getElementById('parcel_edit_jasa').value;
                const kemasan = $('#select_edit_kemasan option:selected').text();
                const kemasanId = $('#select_edit_kemasan option:selected').val();
                const kemasanPrice = $('#kemasan_edit_price').val();
                const budgetValue = this.parseNumber(budget);
                const feeValue = this.parseNumber(fee);
                const kemasanPriceValue = this.parseNumber(kemasanPrice);

                const saveEditProcess = () => {
                    // --- Validasi stok bahan parcel (edit-aware) ---
                    const posAppInstance = Alpine.$data(document.querySelector('[x-data="posApp()"]'));
                    const parcelQty = parseFloat(qty) || 1;
                    const stockErrors = [];

                    // Ambil data parcel lama untuk dikecualikan dari hitungan stok
                    const oldParcel = posAppInstance.parcel.find(p => p.id === parcelId);
                    const oldParcelQty = parseFloat(oldParcel?.qty) || 0;

                    this.parcels.forEach(item => {
                        const productId = item.product;
                        if (!productId) return;

                        const originalStock = parseFloat(item.original_stock) || 0;
                        const qtyPerParcel  = parseFloat(item.qty) || 0;
                        const totalQtyNeed  = qtyPerParcel * parcelQty;

                        let totalUsed = 0;
                        if (posAppInstance && typeof posAppInstance.calculateUsedStock === 'function') {
                            totalUsed = posAppInstance.calculateUsedStock(productId);
                        }

                        if (oldParcel && oldParcel.data && Array.isArray(oldParcel.data)) {
                            const oldIngredient = oldParcel.data.find(ing => {
                                const ingId = ing.id || ing.product;
                                const ingStockId = window.productStockMap?.[ingId] || ingId;
                                const targetId = window.productStockMap?.[productId] || productId;
                                return ingStockId == targetId;
                            });
                            if (oldIngredient) {
                                totalUsed -= (parseFloat(oldIngredient.qty) || 0) * oldParcelQty;
                            }
                        }

                        console.log(`[Validation Edit Parcel] Product: ${productId}`, {
                            originalStock: originalStock,
                            qtyPerParcel: qtyPerParcel,
                            parcelQty: parcelQty,
                            totalQtyNeed: totalQtyNeed,
                            totalUsedAfterSubtractingOld: totalUsed,
                            totalUsedBefore: totalUsed + ((oldParcel && oldParcel.data) ? ((parseFloat(oldParcel.data.find(ing => (ing.id || ing.product) == productId)?.qty) || 0) * oldParcelQty) : 0),
                            diff: (totalUsed + totalQtyNeed) - originalStock
                        });

                        if ((totalUsed + totalQtyNeed) > originalStock) {
                            const productName = item.displayName || item.name || `Produk #${productId}`;
                            const sisa = (originalStock - totalUsed).toFixed(2);
                            stockErrors.push(`- ${productName} (Dibutuhkan: ${totalQtyNeed}, Sisa stok: ${sisa})`);
                        }
                    });

                    if (stockErrors.length > 0) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Stok Bahan Tidak Mencukupi',
                            html: 'Beberapa bahan parcel tidak mencukupi:<br>' + stockErrors.join('<br>'),
                        });
                        return;
                    }
                    // --- End validasi stok ---

                    const normalizedParcels = this.parcels.map(item => ({
                        ...item,
                        unit: this.getUnitLabel(item.unit),
                        displayName: item.displayName || this.formatParcelProductName(item)
                    }));

                    const parcel = {
                        id: parcelId,
                        name: 'Parcel ' + kemasan + '-' + this.formatShortNumber(budget),
                        price: budgetValue,
                        fee: feeValue,
                        hpp: 0,
                        qty: qty,
                        unit: 'Parcel',
                        discount: 0,
                        discountPercent: 0,
                        total_input: 0,
                        typeProduct: 'parcel',
                        kemasanId: kemasanId,
                        kemasanName: kemasan,
                        kemasanPrice: kemasanPriceValue,
                    };

                    const posParcel = {
                        id: parcelId,
                        budget: budgetValue,
                        qty: qty,
                        kemasan: kemasan,
                        kemasanId: kemasanId,
                        kemasanName: kemasan,
                        kemasanPrice: kemasanPriceValue,
                        hpp: this.totalAll,
                        fee: feeValue,
                        data: normalizedParcels, // isi produk dalam parcel
                        type: 'parcel',
                    };

                    // cari index berdasarkan parcelId
                    let idxCart = posAppInstance.cart.findIndex(p => p.id === parcelId);
                    let idxParcel = posAppInstance.parcel.findIndex(p => p.id === parcelId);

                    if (idxCart !== -1) {
                        posAppInstance.cart.splice(idxCart, 1, parcel);
                    }
                    if (idxParcel !== -1) {
                        posAppInstance.parcel.splice(idxParcel, 1, posParcel);
                    }

                    // kalau mau reset form setelah edit
                    document.getElementById('parcel_budget').value = '';
                    document.getElementById('parcel_qty').value = 1;
                    document.getElementById('parcel_jasa').value = '';
                    $('#select_kemasan').val(null).trigger('change');
                    this.parcels = [];
                    
                    const modalEl = document.getElementById('parcelEditModal');
                    if(modalEl) {
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if(modal) modal.hide();
                    }
                };

                if (this.totalAll > budgetValue) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Perhatian',
                        text: 'Total keseluruhan parcel (' + this.formatRupiah(this.totalAll) + ') lebih besar dari budget (' + this.formatRupiah(budgetValue) + '). Tetap lanjutkan?',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Lanjutkan',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            saveEditProcess();
                        }
                    });
                } else {
                    saveEditProcess();
                }
            },

            deleteParcel(parcelId) {
                if (!parcelId) return;

                let posAppInstance = Alpine.$data(document.querySelector('[x-data="posApp()"]'));
                let idxCart = posAppInstance.cart.findIndex(p => p.id === parcelId);
                let idxParcel = posAppInstance.parcel.findIndex(p => p.id === parcelId);
                if (idxCart !== -1) {
                    posAppInstance.cart.splice(idxCart, 1);
                }
                if (idxParcel !== -1) {
                    posAppInstance.parcel.splice(idxParcel, 1);
                }
                this.showParcelEditModal = false;
                const modal = bootstrap.Modal.getInstance(document.getElementById('parcelEditModal'));
                if (modal) modal.hide();
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

        }
    }
</script>
