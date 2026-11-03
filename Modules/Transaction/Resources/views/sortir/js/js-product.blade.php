<script>
    function sortirForm(config = {}) {
        return {
            branches: config.branches || [],
            transactionId: config.transaction?.id || null,
            invoiceNumber: config.transaction?.invoice_number || config.invoiceNumber || '',
            date: String(config.transaction?.date || config.today || '').slice(0, 10),
            branchId: config.transaction?.branch_id || config.branches?.[0]?.id || '',
            isFinal: ['paid', 'debt'].includes(config.transaction?.status),
            branchOpen: false,
            cart: [],
            modalItem: {id: null, name: '', unit: '', qty: 0, price: 0, stock: null},
            editingId: null,
            qtyInput: '',
            productSearch: '',
            products: [],
            productsError: '',
            loadingProducts: false,
            productRequest: 0,
            modalError: '',
            saveError: '',
            saving: false,
            savingStatus: '',
            dirty: false,
            navigationAllowed: false,

            init() {
                this.cart = (config.details || []).map(item => ({
                    id: item.product_id,
                    name: item.product?.name || 'Produk Dihapus',
                    unit: item.product?.unit?.abbreviation || '',
                    qty: Number(item.quantity) || 0,
                    price: this.round(Number(item.price) || 0),
                    stock: null,
                }));
                this._beforeUnload = event => {
                    if (this.dirty && !this.navigationAllowed) {
                        event.preventDefault();
                        event.returnValue = '';
                    }
                };
                this._linkClick = event => {
                    const link = event.target.closest('a[href]');
                    if (!link || !this.dirty || this.navigationAllowed || link.target === '_blank' || event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
                    if (!window.confirm('Perubahan sortir belum disimpan. Tinggalkan halaman ini?')) {
                        event.preventDefault();
                        event.stopImmediatePropagation();
                    } else {
                        this.navigationAllowed = true;
                    }
                };
                window.addEventListener('beforeunload', this._beforeUnload);
                document.addEventListener('click', this._linkClick, true);
            },
            destroy() {
                window.removeEventListener('beforeunload', this._beforeUnload);
                document.removeEventListener('click', this._linkClick, true);
                this.productRequest++;
            },
            round(value) { return Math.round((Number(value) + Number.EPSILON) * 100) / 100; },
            number(value) { return (Number(value) || 0).toLocaleString('id-ID', {maximumFractionDigits: 2}); },
            rupiah(value) { return 'Rp ' + this.number(this.round(Number(value) || 0)); },
            get selectedBranchName() { return this.branches.find(branch => String(branch.id) === String(this.branchId))?.name || ''; },
            get total() { return this.round(this.cart.reduce((sum, item) => sum + this.round(item.qty * item.price), 0)); },
            markDirty() { this.dirty = true; this.saveError = ''; },
            changeBranch(id) {
                if (String(id) === String(this.branchId)) { this.branchOpen = false; return; }
                if (this.cart.length && !window.confirm('Mengganti cabang akan mengosongkan daftar item sortir. Lanjutkan?')) return;
                this.branchId = id;
                this.branchOpen = false;
                this.cart = [];
                this.products = [];
                this.productRequest++;
                this.markDirty();
            },
            showModal() {
                this.$nextTick(() => this.$refs.itemModal.showModal());
            },
            openAddModal() {
                if (!this.branchId || this.saving) return;
                this.editingId = null;
                this.modalItem = {id: null, name: '', unit: '', qty: 0, price: 0, stock: null};
                this.qtyInput = '';
                this.productSearch = '';
                this.modalError = '';
                this.showModal();
                this.loadProducts();
            },
            openEditModal(item) {
                if (this.saving) return;
                this.editingId = item.id;
                this.modalItem = {...item};
                this.qtyInput = String(item.qty).replace('.', ',');
                this.productSearch = '';
                this.modalError = '';
                this.showModal();
                this.loadProducts();
            },
            closeModal() { this.$refs.itemModal.close(); this.onModalClosed(); },
            onModalClosed() {
                this.modalError = '';
                this.editingId = null;
                this.productRequest++;
                this.loadingProducts = false;
            },
            async loadProducts() {
                const request = ++this.productRequest;
                const branchId = this.branchId;
                this.loadingProducts = true;
                this.productsError = '';
                try {
                    const query = new URLSearchParams({branch_id: branchId, term: this.productSearch});
                    if (this.transactionId) query.set('sortir_id', this.transactionId);
                    const response = await fetch(config.productsUrl + '?' + query, {headers: {'Accept': 'application/json'}});
                    const result = await response.json().catch(() => ({message: response.status === 401 ? 'Sesi berakhir. Masuk kembali untuk memuat produk.' : 'Produk gagal dimuat. Periksa koneksi lalu coba lagi.'}));
                    if (!response.ok || !Array.isArray(result)) throw new Error(result.message || 'Produk gagal dimuat.');
                    if (request !== this.productRequest || String(branchId) !== String(this.branchId)) return;
                    this.products = result;
                    const selected = result.find(product => String(product.id) === String(this.modalItem.id));
                    if (selected) this.modalItem.stock = Number(selected.stock_available) || 0;
                } catch (error) {
                    if (request !== this.productRequest) return;
                    this.products = [];
                    this.productsError = error.message || 'Produk gagal dimuat. Periksa koneksi lalu coba lagi.';
                } finally {
                    if (request === this.productRequest) this.loadingProducts = false;
                }
            },
            selectProduct(product) {
                if (this.editingId !== null && String(product.id) === String(this.modalItem.id)) return;
                this.modalItem = {
                    id: product.id,
                    name: product.name,
                    unit: product.unit?.abbreviation || '',
                    qty: this.modalItem.qty,
                    price: this.round(Math.max(0, Number(product.hpp) || 0)),
                    stock: Number(product.stock_available) || 0,
                };
                this.modalError = '';
            },
            updateQuantity(event) {
                let raw = event.target.value.replace(/[^\d.,]/g, '').replace('.', ',');
                const parts = raw.split(',');
                raw = parts[0] + (parts.length > 1 ? ',' + parts.slice(1).join('').slice(0, 2) : '');
                this.qtyInput = raw;
                this.modalItem.qty = Number(raw.replace(',', '.')) || 0;
                event.target.value = raw;
                this.modalError = '';
            },
            updatePrice(event) {
                const parts = event.target.value.replace(/[^\d,]/g, '').split(',');
                const integer = Number(parts[0]) || 0;
                const fraction = parts.length > 1 ? parts.slice(1).join('').slice(0, 2) : null;
                this.modalItem.price = Number(String(integer) + (fraction !== null ? '.' + fraction : '')) || 0;
                event.target.value = integer.toLocaleString('id-ID') + (fraction !== null ? ',' + fraction : '');
                this.modalError = '';
            },
            saveModalItem() {
                const item = {...this.modalItem};
                if (!item.id) { this.modalError = 'Pilih produk terlebih dahulu.'; return; }
                if (!Number.isFinite(item.qty) || item.qty < .01 || item.qty > 99999999.99) { this.modalError = 'Isi kuantitas antara 0,01 dan 99.999.999,99.'; return; }
                if (!Number.isFinite(item.price) || item.price < 0 || item.price > 999999999999.99) { this.modalError = 'HPP harus berupa angka positif atau 0.'; return; }
                if (this.cart.some(product => String(product.id) === String(item.id) && String(product.id) !== String(this.editingId))) { this.modalError = 'Produk ini sudah ada di daftar sortir. Ubah kuantitas pada item yang tersedia.'; return; }
                item.qty = this.round(item.qty);
                item.price = this.round(item.price);
                if (this.editingId !== null) {
                    const index = this.cart.findIndex(product => String(product.id) === String(this.editingId));
                    if (index === -1) { this.modalError = 'Item tidak ditemukan. Tutup dan buka kembali item.'; return; }
                    this.cart.splice(index, 1, item);
                } else this.cart.push(item);
                this.markDirty();
                this.closeModal();
            },
            deleteModalItem() {
                this.cart = this.cart.filter(item => String(item.id) !== String(this.editingId));
                this.markDirty();
                this.closeModal();
            },
            validateTransaction() {
                if (!this.branchId) return 'Pilih cabang terlebih dahulu.';
                if (!this.date || !/^\d{4}-\d{2}-\d{2}$/.test(this.date)) return 'Isi tanggal transaksi yang valid.';
                if (!this.cart.length) return 'Daftar sortir masih kosong. Tambahkan produk terlebih dahulu.';
                if (this.cart.some(item => !Number.isFinite(item.qty) || item.qty < .01 || !Number.isFinite(item.price) || item.price < 0)) return 'Periksa kembali kuantitas dan HPP setiap produk.';
                return '';
            },
            async confirmComplete() {
                if (this.saving) return;
                this.saveError = this.validateTransaction();
                if (this.saveError) return;
                if (window.Swal) {
                    const result = await Swal.fire({icon: 'question', title: this.isFinal ? 'Simpan perubahan sortir?' : 'Selesaikan transaksi sortir?', text: 'Stok barang pada cabang akan berkurang sesuai kuantitas sortir.', showCancelButton: true, confirmButtonText: 'Ya, simpan', cancelButtonText: 'Batal', confirmButtonColor: '#0b595b'});
                    if (!result.isConfirmed) return;
                } else if (!window.confirm('Selesaikan transaksi sortir? Stok cabang akan berkurang sesuai kuantitas sortir.')) return;
                await this.saveTransaction('paid');
            },
            async saveTransaction(status) {
                if (this.saving) return;
                this.saveError = this.validateTransaction();
                if (this.saveError) return;
                this.saving = true;
                this.savingStatus = this.isFinal ? 'paid' : status;
                try {
                    const response = await fetch(config.saveUrl, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
                        body: JSON.stringify({sortir_id: this.transactionId, invoice_number: this.invoiceNumber, branch_id: this.branchId, date: this.date, status: this.savingStatus,
                            subtotal: this.total, total: this.total,
                            items: this.cart.map(item => ({id: item.id, qty: item.qty, price: item.price, hpp: item.price, discount: 0, total_input: this.round(item.qty * item.price)})),
                        }),
                    });
                    const result = await response.json().catch(() => ({message: [401, 419].includes(response.status) ? 'Sesi berakhir. Buka ulang halaman setelah masuk kembali.' : 'Transaksi gagal disimpan. Periksa koneksi lalu coba lagi.'}));
                    if (!response.ok || result.success !== true) {
                        const validation = result.errors ? Object.values(result.errors).flat().join(' ') : '';
                        throw new Error(validation || result.message || 'Transaksi gagal disimpan. Silakan coba lagi.');
                    }
                    this.invoiceNumber = result.invoice_number || this.invoiceNumber;
                    this.transactionId = result.transaksi_id || this.transactionId;
                    this.dirty = false;
                    this.navigationAllowed = true;
                    window.location.assign(config.indexUrl);
                } catch (error) {
                    this.saveError = error.message || 'Transaksi gagal disimpan. Periksa koneksi lalu coba lagi.';
                } finally {
                    this.saving = false;
                    this.savingStatus = '';
                }
            },
        };
    }
</script>
