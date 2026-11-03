(function (root) {
    function transferList(config) {
        return {
            rows: [], total: 0, filtered: 0, search: '', loading: false, error: '', requestId: 0,
            filters: {status: 'all', branch: 'all', start: '', end: ''},
            pending: {status: 'all', branch: 'all', start: '', end: ''},
            branchSearchQuery: '', filterOpen: false, selected: null, documentOpen: false, deleting: false, returnFocus: null,
            init() { this.load(); },
            money(value) { return 'Rp ' + Number(value || 0).toLocaleString('id-ID', {maximumFractionDigits: 2}); },
            statusLabel(status) { return ({temp:'Draf',draft:'Draf',pending:'Dalam Perjalanan',proses:'Sedang Diperiksa',selesai:'Sudah Diterima'})[status] || status; },
            branchName(row) { return config.receiving ? row.origin : row.destination; },
            dateLabel(value) {
                if (!value) return '—';
                const date = new Date(String(value).replace(' ', 'T'));
                return Number.isNaN(date.getTime()) ? String(value) : date.toLocaleString('id-ID', {day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'});
            },
            initials(name) {
                const branch = String(name || '').toUpperCase();
                if (branch.includes('GRESIK')) return 'GR';
                if (branch.includes('RUNGKUT')) return 'SR';
                if (branch.includes('BARAT') || branch.includes('SURABAYA')) return 'SB';
                if (branch.includes('SIDOARJO') || branch.includes('WARU')) return 'SD';
                if (branch.includes('MALANG') || branch.includes('KLOJEN')) return 'ML';
                return branch.slice(0,2) || 'CB';
            },
            branchTheme(name) {
                const branch = String(name || '').toUpperCase();
                if (branch.includes('RUNGKUT')) return {background:'#eff6ff',color:'#1d4ed8',borderColor:'#bfdbfe'};
                if (branch.includes('BARAT')) return {background:'#faf5ff',color:'#7e22ce',borderColor:'#e9d5ff'};
                if (branch.includes('SIDOARJO') || branch.includes('WARU')) return {background:'#fffbeb',color:'#b45309',borderColor:'#fde68a'};
                if (branch.includes('MALANG') || branch.includes('KLOJEN')) return {background:'#f0fdfa',color:'#0f766e',borderColor:'#99f6e4'};
                return {background:'#ecfdf5',color:'#0b595b',borderColor:'#a7f3d0'};
            },
            get activeFilters() { return this.filters.status !== 'all' || this.filters.branch !== 'all' || !!this.filters.start || !!this.filters.end; },
            get hasMore() { return this.rows.length < this.filtered; },
            get pendingDateError() { return this.pending.start && this.pending.end && this.pending.start > this.pending.end ? 'Tanggal akhir harus sama atau setelah tanggal mulai.' : ''; },
            get filteredBranches() {
                const query = this.branchSearchQuery.trim().toLocaleLowerCase('id-ID');
                return [{id:'all', name:'Semua Cabang'}, ...(config.branches || [])]
                    .filter(branchOption => branchOption.name.toLocaleLowerCase('id-ID').includes(query));
            },
            openFilter(event) {
                this.returnFocus = event?.currentTarget || null;
                this.pending = {...this.filters}; this.branchSearchQuery = ''; this.filterOpen = true;
                this.$nextTick(() => document.getElementById('transfer-filter-dialog')?.focus());
            },
            closeFilter() { this.filterOpen = false; this.returnFocus?.focus(); },
            openDetail(row, event) {
                this.returnFocus = event?.currentTarget || null; this.selected = row; this.documentOpen = false;
                this.$nextTick(() => document.getElementById('transfer-detail-dialog')?.focus());
            },
            closeDetail() { if (this.deleting) return; this.selected = null; this.documentOpen = false; this.returnFocus?.focus(); },
            openDocument() {
                if (!this.selected?.can_show) return;
                this.documentOpen = true;
                this.$nextTick(() => document.getElementById('transfer-detail-dialog')?.focus());
            },
            closeDocument() {
                this.documentOpen = false;
                this.$nextTick(() => document.getElementById('transfer-document-trigger')?.focus());
            },
            printDocument() { if (this.documentOpen && this.selected?.can_show) window.print(); },
            quantity(value) { return Number(value || 0).toLocaleString('id-ID', {maximumFractionDigits:2}); },
            dayLabel(value) {
                if (!value) return '—';
                const date = new Date(String(value).slice(0,10) + 'T00:00:00');
                return Number.isNaN(date.getTime()) ? '—' : date.toLocaleDateString('id-ID', {day:'2-digit',month:'long',year:'numeric'});
            },
            trapFocus(event) {
                const controls = [...event.currentTarget.querySelectorAll('button:not(:disabled), a[href], input, select, [tabindex="0"]')].filter(el => el.getClientRects().length);
                if (!controls.length) { event.preventDefault(); return; }
                const first = controls[0], last = controls[controls.length - 1];
                if (event.shiftKey && (document.activeElement === first || document.activeElement === event.currentTarget.firstElementChild)) { event.preventDefault(); last.focus(); }
                else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
            },
            applyFilter() {
                if (this.pendingDateError) return;
                this.filters = {...this.pending}; this.closeFilter(); this.load();
            },
            setPendingDateRange(period, now = new Date()) {
                if (!['today', 'week'].includes(period)) return;
                const parts = new Intl.DateTimeFormat('en-US', {
                    timeZone: config.timezone || 'UTC',
                    year: 'numeric', month: '2-digit', day: '2-digit',
                }).formatToParts(now);
                const part = type => Number(parts.find(item => item.type === type)?.value);
                const start = new Date(Date.UTC(part('year'), part('month') - 1, part('day')));
                const end = new Date(start);
                if (period === 'week') {
                    start.setUTCDate(start.getUTCDate() - ((start.getUTCDay() + 6) % 7));
                    end.setTime(start.getTime());
                    end.setUTCDate(end.getUTCDate() + 6);
                }
                const asInputDate = date => [date.getUTCFullYear(), String(date.getUTCMonth() + 1).padStart(2, '0'), String(date.getUTCDate()).padStart(2, '0')].join('-');
                this.pending.start = asInputDate(start);
                this.pending.end = asInputDate(end);
            },
            resetPendingFilter(section) {
                if (section === 'branch') this.pending.branch = 'all';
                if (section === 'status') this.pending.status = 'all';
                if (section === 'date') { this.pending.start = ''; this.pending.end = ''; }
            },
            resetFilters() { this.search = ''; this.branchSearchQuery = ''; this.filters = {status:'all',branch:'all',start:'',end:''}; this.pending = {...this.filters}; this.closeFilter(); this.load(); },
            clearSearch() { this.search = ''; this.load(); },
            async load(append = false) {
                if (append && this.loading) return;
                const requestId = ++this.requestId;
                this.loading = true; this.error = '';
                if (!append) this.rows = [];
                const params = new URLSearchParams({mobile:'1',url:config.side,start:String(append ? this.rows.length : 0),length:'20',search:this.search,
                    status_filter:this.filters.status,cabang_filter:this.filters.branch,start_date:this.filters.start,end_date:this.filters.end});
                try {
                    const response = await fetch(config.endpoint + '?' + params, {headers:{Accept:'application/json'}});
                    const json = await response.json();
                    if (!response.ok || !Array.isArray(json.data)) throw new Error(json.message || 'Daftar transfer gagal dimuat.');
                    if (requestId !== this.requestId) return;
                    this.rows = append ? [...this.rows,...json.data] : json.data;
                    this.total = Number(json.recordsTotal || 0); this.filtered = Number(json.recordsFiltered || 0);
                } catch(error) {
                    if (requestId === this.requestId) this.error = error.message || 'Periksa koneksi lalu coba lagi.';
                } finally { if (requestId === this.requestId) this.loading = false; }
            },
            async deleteSelected() {
                const row = this.selected;
                if (!row?.can_delete || this.deleting) return;
                const decision = await Swal.fire({title:'Hapus draf pengiriman?',text:'Transaksi ' + row.invoice_number + ' akan dihapus.',icon:'warning',showCancelButton:true,confirmButtonText:'Hapus',cancelButtonText:'Batal',confirmButtonColor:'#dc2626'});
                if (!decision.isConfirmed) return;
                this.deleting = true;
                try {
                    const response = await fetch(row.delete_url,{method:'DELETE',headers:{'X-CSRF-TOKEN':config.csrf,Accept:'application/json'}});
                    const json = await response.json();
                    if (!response.ok || !json.success) throw new Error(json.message || 'Transaksi gagal dihapus.');
                    this.selected = null; await this.load();
                } catch(error) { await Swal.fire({icon:'error',title:'Gagal menghapus',text:error.message || 'Coba lagi.'}); }
                finally { this.deleting = false; }
            },
        };
    }
    root.transferList = transferList;
    if (typeof module !== 'undefined' && module.exports) module.exports = transferList;
})(typeof window !== 'undefined' ? window : globalThis);
