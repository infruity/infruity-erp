(function (root) {
    function transferForm(config = {}) {
        const transaction = config.transaction || {};
        return {
            branches: config.branches || [], destinations: config.destinations || [],
            branchId: transaction.branch_id || config.branches?.[0]?.id || '',
            destinationId: transaction.branch_destination_id || '', date: String(transaction.date || config.today || '').slice(0, 10),
            transactionId: transaction.id || null, invoiceNumber: transaction.invoice_number || config.invoiceNumber || '',
            status: transaction.status || 'temp', receiving: !!config.receiving, readOnly: !!config.readOnly, canReceive: !!config.canReceive,
            branchOpen: false, destinationOpen: false, cart: [], products: [], productSearch: '', productsError: '', loadingProducts: false, productRequest: 0,
            editingId: null, modalItem: {}, qtyInput: '', modalError: '', saveError: '', saving: false, savingStatus: '', dirty: false, navigationAllowed: false,
            init() {
                this.cart = (config.details || []).map(detail => {
                    const history = [...(detail.corrections || [])].sort((a,b) => Number(b.id) - Number(a.id));
                    return {id: detail.product_id, detailId: detail.id, name: detail.product?.name || 'Produk dihapus', unit: detail.product?.unit?.abbreviation || '',
                        qty: Number(detail.quantity), expected: Number(detail.quantity), sent: Number(history.at(-1)?.old_quantity ?? detail.quantity),
                        price: Number(detail.price) || 0, discount: Number(detail.discount) || 0, note: history[0]?.note || '', stock: null};
                });
                this._beforeUnload = event => { if (this.dirty && !this.navigationAllowed) { event.preventDefault(); event.returnValue = ''; } };
                this._linkClick = event => {
                    const link = event.target.closest('a[href]');
                    if (!link || !this.dirty || this.navigationAllowed || link.target === '_blank' || event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
                    if (!window.confirm('Perubahan transfer belum disimpan. Tinggalkan halaman ini?')) { event.preventDefault(); event.stopImmediatePropagation(); }
                    else this.navigationAllowed = true;
                };
                window.addEventListener('beforeunload', this._beforeUnload);
                document.addEventListener('click', this._linkClick, true);
            },
            destroy() { window.removeEventListener('beforeunload', this._beforeUnload); document.removeEventListener('click', this._linkClick, true); this.productRequest++; },
            round(value) { return Math.round((Number(value) + Number.EPSILON) * 100) / 100; },
            number(value) { return Number(value || 0).toLocaleString('id-ID', {maximumFractionDigits: 2}); },
            get branchName() { return this.branches.find(b => String(b.id) === String(this.branchId))?.name || transaction.branch?.name || ''; },
            get destinationName() { return this.destinations.find(b => String(b.id) === String(this.destinationId))?.name || transaction.branch_destination?.name || ''; },
            get availableDestinations() { return this.destinations.filter(b => String(b.id) !== String(this.branchId)); },
            get total() { return this.round(this.cart.reduce((sum,item) => sum + this.round(item.qty * item.price - (item.discount || 0)), 0)); },
            get hasShortage() { return this.cart.some(item => item.qty < item.sent); },
            get statusLabel() { return ({temp:'Draf',draft:'Draf',pending:'Dalam Perjalanan',proses:'Sedang Diperiksa',selesai:'Sudah Diterima'})[this.status] || this.status; },
            markDirty() { this.dirty = true; this.saveError = ''; },
            changeBranch(id) {
                if (String(id) === String(this.branchId)) { this.branchOpen = false; return; }
                if (this.cart.length && !window.confirm('Mengganti cabang asal akan mengosongkan daftar produk. Lanjutkan?')) return;
                this.branchId = id; this.branchOpen = false; this.cart = []; this.products = []; this.productRequest++;
                if (String(id) === String(this.destinationId)) this.destinationId = '';
                this.markDirty();
            },
            selectDestination(id) { this.destinationId = id; this.destinationOpen = false; this.markDirty(); },
            showItemModal() { this.$nextTick(() => this.$refs.itemModal.showModal()); },
            openAddModal() {
                if (this.readOnly || this.receiving || this.saving || !this.branchId) return;
                this.editingId = null; this.modalItem = {id:null,name:'',unit:'',qty:0,price:0,stock:null,discount:0};
                this.qtyInput = ''; this.productSearch = ''; this.modalError = ''; this.showItemModal(); this.loadProducts();
            },
            openItem(item) {
                if (this.saving || (this.readOnly && !this.canReceive)) return;
                this.editingId = item.id; this.modalItem = {...item}; this.qtyInput = String(item.qty).replace('.', ',');
                this.productSearch = ''; this.modalError = ''; this.showItemModal();
                if (!this.receiving) this.loadProducts();
            },
            closeItemModal() { this.$refs.itemModal.close(); this.productRequest++; this.loadingProducts = false; this.modalError = ''; },
            async loadProducts() {
                const request = ++this.productRequest, branch = this.branchId;
                this.loadingProducts = true; this.productsError = '';
                try {
                    const params = new URLSearchParams({branch_id:branch,term:this.productSearch});
                    const response = await fetch(config.productsUrl + '?' + params, {headers:{Accept:'application/json'}});
                    const result = await response.json().catch(() => ({message:'Produk gagal dimuat. Masuk kembali jika sesi berakhir.'}));
                    if (!response.ok || !Array.isArray(result)) throw new Error(response.status >= 500 ? 'Produk gagal dimuat. Periksa koneksi dan coba lagi.' : result.message || 'Produk gagal dimuat.');
                    if (request !== this.productRequest || String(branch) !== String(this.branchId)) return;
                    this.products = result;
                    const selected = result.find(p => String(p.id) === String(this.modalItem.id));
                    if (selected) this.modalItem.stock = Number(selected.stock);
                } catch(error) { if (request === this.productRequest) { this.products = []; this.productsError = error.message || 'Periksa koneksi dan coba lagi.'; } }
                finally { if (request === this.productRequest) this.loadingProducts = false; }
            },
            selectProduct(product) {
                if (this.editingId !== null) return;
                this.modalItem = {...product,qty:this.modalItem.qty,discount:0}; this.modalError = '';
            },
            updateQuantity(event) {
                const parts = event.target.value.replace(/[^\d.,]/g,'').replace('.',',').split(',');
                const raw = parts[0] + (parts.length > 1 ? ',' + parts.slice(1).join('').slice(0,2) : '');
                this.qtyInput = raw; this.modalItem.qty = Number(raw.replace(',','.')) || 0; event.target.value = raw; this.modalError = '';
            },
            saveItem() {
                const item = {...this.modalItem};
                if (!item.id) { this.modalError = 'Pilih produk terlebih dahulu.'; return; }
                const minimum = this.receiving ? 0 : .01;
                if (!Number.isFinite(item.qty) || item.qty < minimum || item.qty > 99999999.99) { this.modalError = this.receiving ? 'Jumlah diterima harus 0 atau lebih.' : 'Isi kuantitas yang valid, minimal 0,01.'; return; }
                if (this.receiving && item.qty > item.sent) { this.modalError = 'Jumlah diterima tidak boleh melebihi jumlah dikirim.'; return; }
                if (!this.receiving && item.stock !== null && item.qty > Number(item.stock)) { this.modalError = 'Kuantitas melebihi stok tersedia (' + this.number(item.stock) + ' ' + item.unit + ').'; return; }
                if (this.cart.some(p => String(p.id) === String(item.id) && String(p.id) !== String(this.editingId))) { this.modalError = 'Produk sudah ada. Ubah kuantitas pada produk yang tersedia.'; return; }
                item.qty = this.round(item.qty);
                if (this.editingId !== null) {
                    const index = this.cart.findIndex(p => String(p.id) === String(this.editingId));
                    if (index < 0) { this.modalError = 'Produk tidak ditemukan. Buka ulang item.'; return; }
                    this.cart.splice(index,1,item);
                } else this.cart.push(item);
                this.markDirty(); this.closeItemModal();
            },
            deleteItem() { if (this.receiving || this.readOnly) return; this.cart = this.cart.filter(p => String(p.id) !== String(this.editingId)); this.markDirty(); this.closeItemModal(); },
            validate() {
                if (!this.branchId || !this.destinationId) return 'Pilih cabang asal dan tujuan pengiriman.';
                if (String(this.branchId) === String(this.destinationId)) return 'Cabang asal dan tujuan harus berbeda.';
                if (!this.cart.length) return 'Tambahkan produk terlebih dahulu.';
                if (!/^\d{4}-\d{2}-\d{2}$/.test(this.date)) return 'Isi tanggal transaksi yang valid.';
                if (this.cart.some(item => !Number.isFinite(item.qty) || item.qty < (this.receiving ? 0 : .01) || (this.receiving && item.qty > item.sent))) return 'Periksa kuantitas seluruh produk.';
                return '';
            },
            openConfirm() {
                if (this.saving || (this.receiving ? !this.canReceive : this.readOnly)) return;
                this.saveError = this.validate(); if (this.saveError) return;
                this.$refs.confirmModal.showModal();
            },
            async request(url, payload) {
                const response = await fetch(url, {method:'POST',headers:{'Content-Type':'application/json',Accept:'application/json','X-CSRF-TOKEN':config.csrf},body:JSON.stringify(payload)});
                const result = await response.json().catch(() => ({message: [401,419].includes(response.status) ? 'Sesi berakhir. Masuk kembali lalu muat ulang halaman.' : 'Transaksi gagal disimpan. Periksa koneksi dan coba lagi.'}));
                if (!response.ok || result.success !== true) throw new Error(result.errors ? Object.values(result.errors).flat().join(' ') : result.message || 'Transaksi gagal disimpan.');
                return result;
            },
            async save(status) {
                if (this.saving || this.receiving || this.readOnly) return;
                this.saveError = this.validate(); if (this.saveError) return;
                this.saving = true; this.savingStatus = status;
                try {
                    await this.request(config.saveUrl, {transfer_id:this.transactionId,invoice_number:this.invoiceNumber,date:this.date,branch_id:this.branchId,branch_destination_id:this.destinationId,
                        type:'transfer-pengirim',status,subtotal:this.total,total:this.total,discount:0,ongkir:0,discount_ongkir:0,
                        items:this.cart.map(item => ({id:item.id,qty:item.qty,price:item.price,discount:item.discount || 0,total_input:this.round(item.qty * item.price - (item.discount || 0))}))});
                    this.dirty = false; this.navigationAllowed = true; window.location.assign(config.indexUrl);
                } catch(error) { this.saveError = error.message || 'Transfer gagal disimpan.'; this.$refs.confirmModal?.close(); }
                finally { this.saving = false; this.savingStatus = ''; }
            },
            async receive() {
                if (this.saving || !this.canReceive) return;
                this.saveError = this.validate(); if (this.saveError) return;
                this.saving = true;
                try {
                    await this.request(config.receiveUrl, {items:this.cart.map(item => ({detail_id:item.detailId,quantity:item.qty,expected_quantity:item.expected,note:item.note || ''}))});
                    this.dirty = false; this.navigationAllowed = true; window.location.assign(config.indexUrl);
                } catch(error) { this.saveError = error.message || 'Penerimaan gagal disimpan.'; this.$refs.confirmModal?.close(); }
                finally { this.saving = false; }
            },
        };
    }
    root.transferForm = transferForm;
    if (typeof module !== 'undefined' && module.exports) module.exports = transferForm;
})(typeof window !== 'undefined' ? window : globalThis);
