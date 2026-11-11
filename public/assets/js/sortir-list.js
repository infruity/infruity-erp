(function (root) {
    function sortirList(config) {
        const onNextFrame = callback => {
            if (typeof root.requestAnimationFrame === 'function') root.requestAnimationFrame(callback);
            else callback();
        };
        return {
            rows: [], total: 0, filtered: 0, search: '', loading: false, error: '', requestId: 0,
            branches: Array.isArray(config.branches) ? config.branches : [], branchSearch: '',
            filters: {status: 'all', branch: 'all', start: '', end: ''},
            pending: {status: 'all', branch: 'all', start: '', end: ''},
            filterOpen: false, calendarOpen: false, calendarView: 'date', calendarMonth: new Date().getMonth(), calendarYear: new Date().getFullYear(),
            filterCalendarTrigger: null, filterCalendarMonths: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
            selected: null, deleting: false, returnFocus: null,
            init() { this.load(); },
            money(value) { return 'Rp ' + Number(value || 0).toLocaleString('id-ID', {maximumFractionDigits: 2}); },
            statusLabel(status) { return ['paid', 'debt'].includes(status) ? 'Selesai' : (status === 'canceled' ? 'Dibatalkan' : 'Draf'); },
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
            get visibleBranches() {
                const query = this.branchSearch.trim().toLocaleLowerCase();
                return this.branches.filter(branch => String(branch.name || '').toLocaleLowerCase().includes(query));
            },
            get showAllBranchOption() {
                const query = this.branchSearch.trim().toLocaleLowerCase();
                return !query || 'semua cabang'.includes(query);
            },
            get pendingDateError() { return this.pending.start && this.pending.end && this.pending.start > this.pending.end ? 'Tanggal akhir harus sama atau setelah tanggal mulai.' : ''; },
            get filterCalendarHeading() {
                if (this.calendarView === 'month') return String(this.calendarYear);
                if (this.calendarView === 'year') return `${this.calendarYear - 4} - ${this.calendarYear + 7}`;
                return `${this.filterCalendarMonths[this.calendarMonth]} ${this.calendarYear}`;
            },
            get filterCalendarBlankDays() {
                return Array.from({length:new Date(this.calendarYear, this.calendarMonth, 1).getDay()}, (_, index) => index);
            },
            get filterCalendarDays() {
                return Array.from({length:new Date(this.calendarYear, this.calendarMonth + 1, 0).getDate()}, (_, index) => index + 1);
            },
            get filterCalendarYears() {
                return Array.from({length:12}, (_, index) => this.calendarYear - 4 + index);
            },
            openFilter(event) {
                this.returnFocus = event?.currentTarget || null;
                this.pending = {...this.filters}; this.branchSearch = ''; this.calendarOpen = false; this.calendarView = 'date'; this.filterOpen = true;
                this.$nextTick(() => document.getElementById('sortir-filter-dialog')?.focus());
            },
            openFilterCalendar(trigger) {
                this.filterCalendarTrigger = trigger;
                const value = trigger?.id === 'sortir-date-end'
                    ? (this.pending.end || this.pending.start)
                    : (this.pending.start || this.pending.end);
                if (value) {
                    const [year, month] = value.split('-').map(Number);
                    this.calendarYear = year;
                    this.calendarMonth = month - 1;
                } else {
                    const today = new Date();
                    this.calendarYear = today.getFullYear();
                    this.calendarMonth = today.getMonth();
                }
                this.calendarView = 'date';
                this.calendarOpen = true;
                this.$nextTick(() => onNextFrame(() => {
                    const target = this.$refs.filterCalendarBack;
                    if (!target || !target.getClientRects().length) return;
                    target.focus({preventScroll:true});
                    target.scrollIntoView({block:'nearest'});
                }));
            },
            closeFilterCalendar() {
                if (!this.calendarOpen) return;
                this.calendarOpen = false;
                this.calendarView = 'date';
                this.$nextTick(() => onNextFrame(() => {
                    const target = this.filterCalendarTrigger;
                    if (!target || !target.getClientRects().length) return;
                    target.focus({preventScroll:true});
                    target.scrollIntoView({block:'nearest'});
                }));
            },
            moveFilterCalendar(step) {
                if (this.calendarView === 'date') {
                    const date = new Date(this.calendarYear, this.calendarMonth + step, 1);
                    this.calendarMonth = date.getMonth();
                    this.calendarYear = date.getFullYear();
                } else if (this.calendarView === 'month') {
                    this.calendarYear += step;
                } else {
                    this.calendarYear += step * 12;
                }
            },
            getFilterCalendarDate(day) {
                return `${this.calendarYear}-${String(this.calendarMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            },
            selectFilterCalendarDate(day) {
                if (!Number.isInteger(Number(day)) || Number(day) < 1 || Number(day) > this.filterCalendarDays.length) return;
                const date = this.getFilterCalendarDate(Number(day));
                if (!this.pending.start || (this.pending.start && this.pending.end)) {
                    this.pending.start = date;
                    this.pending.end = '';
                } else if (date < this.pending.start) {
                    this.pending.end = this.pending.start;
                    this.pending.start = date;
                } else {
                    this.pending.end = date;
                }
            },
            isFilterCalendarDateSelected(day) {
                const date = this.getFilterCalendarDate(day);
                return date === this.pending.start || date === this.pending.end;
            },
            isFilterCalendarDateInRange(day) {
                if (!this.pending.start || !this.pending.end) return false;
                const date = this.getFilterCalendarDate(day);
                return date > this.pending.start && date < this.pending.end;
            },
            chooseFilterCalendarMonth(month) {
                this.calendarMonth = month;
                this.calendarView = 'date';
            },
            chooseFilterCalendarYear(year) {
                this.calendarYear = year;
                this.calendarView = 'month';
            },
            closeFilter() { this.calendarOpen = false; this.calendarView = 'date'; this.filterOpen = false; this.returnFocus?.focus(); },
            openDetail(row, event) {
                this.returnFocus = event?.currentTarget || null; this.selected = row;
                this.$nextTick(() => document.getElementById('sortir-detail-dialog')?.focus());
            },
            closeDetail() { if (this.deleting) return; this.selected = null; this.returnFocus?.focus(); },
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
            resetPendingFilter(section) {
                if (section === 'branch') { this.pending.branch = 'all'; this.branchSearch = ''; }
                if (section === 'status') this.pending.status = 'all';
                if (section === 'date') { this.pending.start = ''; this.pending.end = ''; }
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
            formatFilterDate(value) {
                if (!value) return 'Pilih Tanggal';
                const [year, month, day] = value.split('-').map(Number);
                if (!year || !month || !day) return value;
                return new Intl.DateTimeFormat('id-ID', {day:'numeric', month:'short', year:'numeric'}).format(new Date(year, month - 1, day));
            },
            resetFilters() { this.filters = {status:'all',branch:'all',start:'',end:''}; this.pending = {...this.filters}; this.closeFilter(); this.load(); },
            clearSearch() { this.search = ''; this.load(); },
            async load(append = false) {
                if (append && this.loading) return;
                const requestId = ++this.requestId;
                this.loading = true; this.error = '';
                if (!append) this.rows = [];
                const params = new URLSearchParams({mobile:'1',start:String(append ? this.rows.length : 0),length:'20',search:this.search,
                    status_filter:this.filters.status,cabang_filter:this.filters.branch,start_date:this.filters.start,end_date:this.filters.end});
                try {
                    const response = await fetch(config.endpoint + '?' + params, {headers:{Accept:'application/json'}});
                    const json = await response.json();
                    if (!response.ok || !Array.isArray(json.data)) throw new Error(json.message || 'Daftar sortir gagal dimuat.');
                    if (requestId !== this.requestId) return;
                    this.rows = append ? [...this.rows,...json.data] : json.data;
                    this.total = Number(json.recordsTotal || 0); this.filtered = Number(json.recordsFiltered || 0);
                } catch(error) {
                    if (requestId === this.requestId) this.error = error.name === 'TypeError' ? 'Periksa koneksi lalu coba lagi.' : (error.message || 'Periksa koneksi lalu coba lagi.');
                } finally { if (requestId === this.requestId) this.loading = false; }
            },
            async deleteSelected() {
                const row = this.selected;
                if (!row?.can_delete || this.deleting) return;
                const decision = await Swal.fire({title:'Hapus transaksi sortir?',text:'Transaksi ' + row.invoice_number + ' akan dihapus. Perhitungan stok akan mengikuti perubahan ini.',icon:'warning',showCancelButton:true,confirmButtonText:'Hapus',cancelButtonText:'Batal',confirmButtonColor:'#dc2626'});
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
    root.sortirList = sortirList;
    if (typeof module !== 'undefined' && module.exports) module.exports = sortirList;
})(typeof window !== 'undefined' ? window : globalThis);
