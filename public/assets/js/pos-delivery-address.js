function posDeliveryAddress(config) {
    return {
        pageOpen: false, formOpen: false, busy: false, fetching: false, error: '', formError: '',
        addresses: [], selectedId: null, snapshot: config.snapshot, savedAddress: config.address, editingId: null, pickup: config.pickup, canDelete: config.canDelete,
        form: {name: '', phone: '', address: '', is_default: false},
        init() {},
        phoneLabel(phone) {
            let n = String(phone || '').replace(/\D/g, '').replace(/^(62|0)/, '');
            return n ? '(+62) ' + [n.slice(0, 3), n.slice(3, 7), n.slice(7)].filter(Boolean).join('-') : '';
        },
        get recipient() { return this.snapshot?.name || config.name; },
        get phone() { return this.phoneLabel(this.snapshot?.phone || config.phone); },
        get addressText() { return this.snapshot?.address || this.savedAddress; },
        get canSave() {
            return !!this.form.name.trim() && /^[0-9]{1,15}$/.test(this.form.phone)
                && !!this.form.address.trim();
        },
        async request(path = '', method = 'GET', data) {
            const response = await fetch(config.url + path, {
                method, headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.csrf},
                body: data === undefined ? undefined : JSON.stringify(data),
            });
            const result = await response.json().catch(() => ({}));
            if (!response.ok || response.redirected) {
                throw new Error(Object.values(result.errors || {}).flat()[0] || result.message || 'Alamat gagal dimuat atau disimpan. Silakan coba lagi.');
            }
            this.addresses = result.addresses;
            this.selectedId = result.selected_id;
            this.snapshot = result.selected;
            this.savedAddress = result.current_address;
            // Keep legacy courier rendering in sync with the selected address.
            const trigger = document.getElementById('pay_customer');
            if (trigger) trigger.dataset.address = this.addressText;
            return result;
        },
        async open() {
            if (this.busy || this.fetching) return;
            this.pageOpen = true; this.error = ''; this.fetching = true;
            document.body.classList.add('pos-address-active');
            this.$nextTick(() => this.$refs.pageBack.focus());
            try { await this.request(); } catch (e) { this.error = e.message; }
            finally { this.fetching = false; }
        },
        close() {
            if (this.busy) return;
            this.formOpen = false; this.pageOpen = false;
            document.body.classList.remove('pos-address-active');
            this.$nextTick(() => document.getElementById('pay_customer')?.focus());
        },
        edit(address = null) {
            if (this.busy || this.fetching) return;
            this.editingId = address?.id || null;
            this.form = address ? {...address, phone: String(address.phone || '').replace(/\D/g, '').replace(/^(62|0)/, '')}
                : {name: '', phone: '', address: '', is_default: this.addresses.length === 0};
            this.formError = ''; this.formOpen = true;
        },
        dismissForm() { if (!this.busy) this.formOpen = false; },
        async save() {
            if (this.busy || !this.canSave) return;
            this.busy = true; this.formError = '';
            try {
                await this.request(this.editingId ? '/' + this.editingId : '', this.editingId ? 'PUT' : 'POST', this.form);
                this.formOpen = false;
            } catch (e) { this.formError = e.message; }
            finally { this.busy = false; }
        },
        async choose(address) {
            if (this.busy || this.fetching) return;
            this.busy = true; this.error = '';
            try { await this.request('/' + address.id + '/select', 'POST'); this.busy = false; this.close(); }
            catch (e) { this.error = e.message; }
            finally { this.busy = false; }
        },
        async remove() {
            if (this.busy || !this.editingId || !window.confirm('Apakah Anda yakin ingin menghapus alamat ini?')) return;
            this.busy = true; this.formError = '';
            const wasSelected = this.editingId === this.selectedId;
            try {
                await this.request('/' + this.editingId, 'DELETE'); this.formOpen = false;
                if (wasSelected && this.selectedId) { this.busy = false; this.close(); }
            } catch (e) { this.formError = e.message; }
            finally { this.busy = false; }
        },
        trap(event, element) {
            const nodes = [...element.querySelectorAll('button, input, textarea, [tabindex="0"]')].filter(e => !e.disabled && e.getClientRects().length);
            if (!nodes.length) { event.preventDefault(); return; }
            const first = nodes[0], last = nodes[nodes.length - 1];
            if (event.shiftKey && (document.activeElement === first || !element.contains(document.activeElement))) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && (document.activeElement === last || !element.contains(document.activeElement))) { event.preventDefault(); first.focus(); }
        },
    };
}
