(function () {
    'use strict';

    const formatMoney = value => 'Rp ' + Math.abs(Number(value) || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 });
    const numericMoney = value => Number(value) || 0;
    const parseMoney = value => {
        if (typeof value === 'number') return value;
        const text = String(value ?? '').replace(/[^\d.,-]/g, '').replace(/^\.+/, '');
        if (/^-?\d+\.\d{1,2}$/.test(text)) return Number(text);
        return Number(text.replace(/\./g, '').replace(',', '.')) || 0;
    };
    const plainText = value => String(value ?? '').replace(/<[^>]*>/g, '').trim();
    const fullDateLabel = value => value ? new Date(value.slice(0, 10) + 'T12:00:00').toLocaleDateString('id-ID', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' }) : '';
    const toDateValue = date => [date.getFullYear(), String(date.getMonth() + 1).padStart(2, '0'), String(date.getDate()).padStart(2, '0')].join('-');
    const nextDay = value => {
        const date = new Date(value.slice(0, 10) + 'T12:00:00');
        date.setDate(date.getDate() + 6);
        return [date.getFullYear(), String(date.getMonth() + 1).padStart(2, '0'), String(date.getDate()).padStart(2, '0')].join('-');
    };

    window.erpReportMobile = function (config) {
        const today = config.today;
        const todayDate = new Date(today + 'T12:00:00');

        return {
            config,
            rows: [],
            totals: {},
            search: '',
            branch: 'all',
            courier: 'all',
            startDate: '',
            endDate: '',
            draftBranch: 'all',
            draftCourier: 'all',
            draftStart: '',
            draftEnd: '',
            groupBy: 'product',
            timeGroup: 'daily',
            selectedYear: todayDate.getFullYear(),
            selectedWeekValue: '',
            draftGroupBy: 'product',
            draftTimeGroup: 'daily',
            draftYear: todayDate.getFullYear(),
            draftWeekValue: '',
            weekOptions: [],
            calendarOpen: false,
            calendarView: 'date',
            calendarMonth: todayDate.getMonth(),
            calendarYear: todayDate.getFullYear(),
            filterOpen: false,
            filterError: '',
            infoOpen: false,
            loading: false,
            error: '',
            hasMore: false,
            nextStart: 0,
            recordsFiltered: 0,
            requestSequence: 0,
            historyOpen: false,
            historyLoading: false,
            historyRows: [],
            historyError: '',
            historyRow: null,
            historySearch: '',
            historyDate: '',
            historyPayment: 'all',
            historyCourier: 'all',
            historyFilterOpen: false,
            historyFilterExpanded: false,
            historyCalendarOpen: false,
            historyCalendarMonth: todayDate.getMonth(),
            historyCalendarYear: todayDate.getFullYear(),
            historyTitle: '',
            historySequence: 0,

            init() {
                this.reload();
            },
            rupiah(value) { return (value < 0 ? '- ' : '') + formatMoney(value); },
            get hasFilter() {
                return this.branch !== 'all' || (config.type === 'shipping' && this.groupBy === 'product' && this.courier !== 'all') || Boolean(this.startDate || this.endDate);
            },
            get filteredHistory() {
                const query = this.historySearch.trim().toLocaleLowerCase('id-ID');
                return this.historyRows.filter(entry => {
                    if (query && (config.type !== 'adjusted' || entry.type === 'Penjualan') && !String(entry.invoice || '').toLocaleLowerCase('id-ID').includes(query)) return false;
                    const date = String(entry.pos_date || entry.sortir_date || entry.date_raw || entry.tx_date_raw || entry.tx_date || '').slice(0, 10);
                    if (this.historyDate && date !== this.historyDate) return false;
                    if (config.type !== 'adjusted' || entry.type === 'Penjualan') {
                        const methods = Array.isArray(entry.payment_methods)
                            ? entry.payment_methods
                            : String(entry.payment || '').split(',').map(value => value.trim()).filter(Boolean);
                        if (this.historyPayment === 'split' && methods.length < 2) return false;
                        if (this.historyPayment !== 'all' && this.historyPayment !== 'split' && !methods.some(method => String(method).toLocaleLowerCase('id-ID') === this.historyPayment.toLocaleLowerCase('id-ID'))) return false;
                    }
                    if (config.type === 'shipping' && this.groupBy === 'date' && this.historyCourier !== 'all' && String(entry.courier_id ?? 'null') !== String(this.historyCourier)) return false;
                    return true;
                });
            },
            get adjustedHistorySummary() {
                return this.filteredHistory.reduce((summary, entry) => {
                    if (entry.type === 'Penjualan') {
                        const revenue = Number(entry.revenue_raw ?? parseMoney(entry.pendapatan_bersih));
                        const hpp = Number(entry.hpp_raw ?? parseMoney(entry.total_hpp));
                        summary.revenue += revenue;
                        summary.hpp += hpp;
                        summary.final += Number(entry.amount_raw ?? (revenue - hpp));
                    } else {
                        const correction = Number(entry.correction_raw ?? parseMoney(entry.koreksi_stock_raw));
                        summary.correction -= correction;
                        summary.final += Number(entry.amount_raw ?? -correction);
                    }
                    summary.grossProfit = summary.revenue - summary.hpp;
                    return summary;
                }, { revenue: 0, hpp: 0, grossProfit: 0, correction: 0, final: 0 });
            },
            get adjustedDailyBreakdown() {
                const days = new Map();
                this.filteredHistory.forEach(entry => {
                    const date = String(entry.date_raw || entry.pos_date || entry.sortir_date || entry.tx_date_raw || entry.tx_date || '').slice(0, 10);
                    if (!date) return;
                    if (!days.has(date)) days.set(date, { date, revenue: 0, hpp: 0, correction: 0 });
                    const day = days.get(date);
                    if (entry.type === 'Penjualan') {
                        day.revenue += Number(entry.revenue_raw ?? parseMoney(entry.pendapatan_bersih));
                        day.hpp += Number(entry.hpp_raw ?? parseMoney(entry.total_hpp));
                    } else {
                        day.correction -= Number(entry.correction_raw ?? parseMoney(entry.koreksi_stock_raw));
                    }
                });
                return [...days.values()]
                    .map(day => ({ ...day, grossProfit: day.revenue - day.hpp, final: day.revenue - day.hpp + day.correction }))
                    .sort((first, second) => second.date.localeCompare(first.date));
            },
            get historyCourierOptions() {
                const options = new Map();
                this.historyRows.forEach(entry => {
                    if (config.type === 'shipping' && this.groupBy === 'date') options.set(String(entry.courier_id ?? 'null'), plainText(entry.courier_name) || 'Tanpa Kurir / Lainnya');
                });
                return [...options].map(([id, name]) => ({ id, name }));
            },
            get historyPaymentOptions() {
                return ['asset', 'loss'].includes(config.type)
                    ? ['Tunai', 'QRIS', 'Transfer']
                    : ['Bank Jatim', 'Bank Mandiri', 'BCA', 'BNI', 'BRI', 'GoPay', 'Qris', 'ShopeePay', 'Tunai'];
            },
            get calendarWeekdays() { return ['M', 'S', 'S', 'R', 'K', 'J', 'S']; },
            get calendarMonths() { return ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']; },
            get calendarHeading() {
                if (this.calendarView === 'month') return String(this.calendarYear);
                if (this.calendarView === 'year') return `${this.calendarYear - 4} - ${this.calendarYear + 7}`;
                return `${this.calendarMonths[this.calendarMonth]} ${this.calendarYear}`;
            },
            get calendarBlankDays() { return Array.from({ length: new Date(this.calendarYear, this.calendarMonth, 1).getDay() }, (_, index) => index); },
            get calendarDays() { return Array.from({ length: new Date(this.calendarYear, this.calendarMonth + 1, 0).getDate() }, (_, index) => index + 1); },
            get calendarYears() { return Array.from({ length: 12 }, (_, index) => this.calendarYear - 4 + index); },
            historyCalendarMonthLabel() { return `${this.calendarMonths[this.historyCalendarMonth]} ${this.historyCalendarYear}`; },
            historyCalendarBlankDays() { return Array.from({ length: new Date(this.historyCalendarYear, this.historyCalendarMonth, 1).getDay() }, (_, index) => index); },
            historyCalendarDays() { return Array.from({ length: new Date(this.historyCalendarYear, this.historyCalendarMonth + 1, 0).getDate() }, (_, index) => index + 1); },
            historyCalendarDate(day) { return toDateValue(new Date(this.historyCalendarYear, this.historyCalendarMonth, day)); },
            isHistoryCalendarDateDisabled(day) {
                const value = this.historyCalendarDate(day);
                if (value > today) return true;
                if (this.startDate && value < this.startDate) return true;
                if (this.endDate && value > this.endDate) return true;
                return !this.historyRows.some(entry => String(entry.pos_date || entry.sortir_date || entry.date_raw || entry.tx_date_raw || entry.tx_date || '').slice(0, 10) === value);
            },
            openHistoryCalendar() {
                const selected = this.historyDate || today;
                const date = new Date(selected + 'T12:00:00');
                this.historyCalendarMonth = date.getMonth();
                this.historyCalendarYear = date.getFullYear();
                this.historyCalendarOpen = !this.historyCalendarOpen;
            },
            navigateHistoryCalendar(step) {
                const date = new Date(this.historyCalendarYear, this.historyCalendarMonth + step, 1);
                this.historyCalendarMonth = date.getMonth();
                this.historyCalendarYear = date.getFullYear();
            },
            selectHistoryCalendarDate(day) {
                if (this.isHistoryCalendarDateDisabled(day)) return;
                this.historyDate = this.historyCalendarDate(day);
                this.historyCalendarOpen = false;
            },
            get reportYears() { return [todayDate.getFullYear(), todayDate.getFullYear() - 1, todayDate.getFullYear() - 2]; },
            fullDateLabel(value) { return fullDateLabel(value); },
            draftBranchName() { return config.branches.find(branch => String(branch.id) === String(this.draftBranch))?.name || 'Semua Cabang'; },
            draftCourierName() { return this.draftCourier === 'null' ? 'Tanpa Kurir / Lainnya' : (config.couriers?.find(courier => String(courier.id) === String(this.draftCourier))?.name || 'Semua Kurir'); },
            draftWeekLabel() { return this.weekOptions.find(week => week.value === this.draftWeekValue)?.label || 'Semua Periode'; },
            draftYearLabel() { return this.draftYear === 'all' ? 'Semua Tahun' : String(this.draftYear); },
            get summaryLines() {
                const total = key => parseMoney(this.totals[key]);
                if (config.type === 'asset') return [{ label: 'TOTAL', value: this.rupiah(total('grand_total')) }];
                if (config.type === 'profit') {
                    const revenue = total('grand_total_pendapatan');
                    const profit = total('grand_total_laba');
                    const margin = revenue ? ((profit / revenue) * 100).toFixed(1).replace('.', ',') : '0,0';
                    return [
                        { label: 'Total Pendapatan', value: this.rupiah(revenue), style: 'report-mobile-summary__line--profit-subtotal' },
                        { label: 'Total HPP', value: '- ' + formatMoney(total('grand_total_hpp')), style: 'report-mobile-summary__line--profit-hpp' },
                        { label: 'LABA KOTOR', value: this.rupiah(profit), style: 'report-mobile-summary__line--profit-total', tone: profit >= 0 ? 'positive' : 'negative', badge: margin + '%' },
                    ];
                }
                if (config.type === 'adjusted') {
                    const adjusted = total('grand_total_laba_disesuaikan');
                    const revenue = total('grand_total_pendapatan');
                    const margin = revenue ? (adjusted / revenue * 100).toFixed(1) : '0.0';
                    const correction = parseMoney(this.totals.grand_total_koreksi_stock);
                    return [
                        { label: 'Laba Kotor', value: this.rupiah(total('grand_total_laba_kotor')) },
                        { label: '± Koreksi Stok', value: this.totals.grand_total_koreksi_stock || 'Rp 0', tone: correction >= 0 ? 'positive' : 'negative' },
                        { label: 'LABA DISESUAIKAN', value: this.rupiah(adjusted), style: 'report-mobile-summary__line--profit-total', tone: adjusted >= 0 ? 'positive' : 'negative', badge: margin + '%' },
                    ];
                }
                return [{ label: 'TOTAL', value: this.rupiah(total('grand_total')) }];
            },
            rowTitle(row) {
                if (this.groupBy === 'date') return this.timeGroup === 'weekly' ? `${fullDateLabel(row.period_start)} - ${fullDateLabel(nextDay(row.period_start))}` : fullDateLabel(row.period_start);
                return plainText(config.type === 'shipping' ? row.courier_name : row.name) || 'Tanpa Nama';
            },
            rowAmount(row) {
                if (this.groupBy === 'date') return numericMoney(row.amount);
                if (config.type === 'asset' || config.type === 'loss') return parseMoney(row.total_hpp);
                if (config.type === 'sales') return parseMoney(row.total);
                if (config.type === 'profit') return numericMoney(row.laba_kotor);
                if (config.type === 'adjusted') return numericMoney(row.laba_disesuaikan);
                return numericMoney(row.total_ongkir);
            },
            rowDisplayAmount(row) {
                const amount = this.rowAmount(row);
                return this.rupiah(amount);
            },
            adjustedCorrection(row) {
                const correction = numericMoney(this.groupBy === 'date' ? row.correction : row.koreksi_stock);
                return (correction > 0 ? '- ' : correction < 0 ? '+ ' : '') + formatMoney(correction);
            },
            rowMeta(row) {
                if (this.groupBy === 'date') return plainText(row.branch_name) || 'Pusat';
                if (config.type === 'asset') return `${row.total_stock ?? 0} ${plainText(row.abbreviation)}  @  ${this.rupiah(parseMoney(row.hpp))}`;
                if (config.type === 'sales') return `${row.qty_formatted ?? row.quantity ?? 0} ${plainText(row.unit)}  @  ${row.price || this.rupiah(parseMoney(row.total) / Math.max(1, numericMoney(row.quantity)))}`;
                if (config.type === 'profit') return `${row.qty_formatted ?? row.quantity ?? 0} ${plainText(row.unit)} · Pendapatan ${this.rupiah(numericMoney(row.total_pendapatan))}`;
                if (config.type === 'adjusted') return `Laba kotor ${this.rupiah(numericMoney(row.laba_kotor))} · Koreksi ${this.rupiah(numericMoney(row.koreksi_stock))}`;
                if (config.type === 'shipping') return plainText(row.branch_name) || 'Pusat';
                return `${row.quantity ?? 0} ${plainText(row.satuan)}  @  ${this.rupiah(parseMoney(row.hpp))}`;
            },
            rowSecondary(row) {
                if (config.type === 'sales') return plainText(row.persentase_penjualan);
                return '';
            },
            async reload() {
                this.nextStart = 0;
                this.rows = [];
                this.totals = {};
                this.hasMore = false;
                this.recordsFiltered = 0;
                this.$refs.reportList?.scrollTo({ top: 0 });
                await this.fetchPage(false);
            },
            async loadMore() { if (!this.loading && this.hasMore) await this.fetchPage(true); },
            maybeLoadMore() {
                const list = this.$refs.reportList;
                if (!list || this.loading || !this.hasMore) return;
                if (list.scrollHeight - list.scrollTop - list.clientHeight < 280) this.loadMore();
            },
            async fetchPage(append) {
                const sequence = ++this.requestSequence;
                this.loading = true;
                this.error = '';
                const params = new URLSearchParams({
                    draw: String(sequence), start: String(append ? this.nextStart : 0), length: '50',
                    'search[value]': this.search.trim(), branch_id: this.branch,
                });
                if (config.type === 'adjusted' && this.groupBy === 'product') params.set('mobile_report', '1');
                if (config.type === 'shipping' && this.groupBy === 'product') params.set('courier_id', this.courier);
                if (config.type !== 'asset') {
                    params.set('start_date', this.startDate || '1900-01-01');
                    params.set('end_date', this.endDate || '2100-12-31');
                }
                try {
                    if (this.groupBy === 'date') {
                        params.set('group_by', 'date');
                        params.set('time_group', this.timeGroup);
                    }
                    const endpoint = this.groupBy === 'date' ? config.dateEndpoint : config.endpoint;
                    const response = await fetch(endpoint + '?' + params.toString(), { credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json' } });
                    if (!response.ok || response.redirected) throw new Error(response.status === 401 || response.redirected ? 'Sesi telah berakhir. Masuk kembali untuk melanjutkan.' : response.status === 403 ? 'Anda tidak memiliki akses ke laporan ini.' : 'Laporan belum dapat dimuat. Coba lagi.');
                    const result = await response.json();
                    if (sequence !== this.requestSequence) return;
                    const incoming = Array.isArray(result.data) ? result.data : [];
                    this.rows = append ? [...this.rows, ...incoming] : incoming;
                    this.recordsFiltered = Number(result.recordsFiltered ?? result.recordsTotal ?? this.rows.length);
                    this.nextStart = this.rows.length;
                    this.hasMore = this.rows.length < this.recordsFiltered;
                    if (!append) this.totals = result;
                } catch (error) {
                    if (sequence === this.requestSequence) this.error = error.message === 'Failed to fetch' ? 'Periksa koneksi lalu coba lagi.' : error.message;
                } finally {
                    if (sequence === this.requestSequence) {
                        this.loading = false;
                        if (this.hasMore) this.$nextTick(() => this.maybeLoadMore());
                    }
                }
            },
            openFilter() {
                this.draftBranch = this.branch;
                this.draftCourier = this.courier;
                this.draftStart = this.startDate;
                this.draftEnd = this.endDate;
                this.draftGroupBy = this.groupBy;
                this.draftTimeGroup = this.timeGroup;
                this.draftYear = this.selectedYear;
                this.generateWeekOptions();
                this.draftWeekValue = this.selectedWeekValue;
                this.calendarOpen = false;
                this.filterError = '';
                this.filterOpen = true;
            },
            openDateCalendar() {
                const selected = this.draftStart || this.draftEnd || today;
                const date = new Date(selected + 'T12:00:00');
                this.calendarMonth = date.getMonth();
                this.calendarYear = date.getFullYear();
                this.calendarView = 'date';
                this.calendarOpen = true;
            },
            navigateCalendar(step) {
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
            toggleCalendarView() {
                this.calendarView = this.calendarView === 'date' ? 'month' : (this.calendarView === 'month' ? 'year' : 'date');
            },
            chooseCalendarMonth(month) { this.calendarMonth = month; this.calendarView = 'date'; },
            chooseCalendarYear(year) { this.calendarYear = year; this.calendarView = 'month'; },
            calendarDate(day) { return toDateValue(new Date(this.calendarYear, this.calendarMonth, day)); },
            isCalendarDateSelected(day) {
                const value = this.calendarDate(day);
                return value === this.draftStart || value === this.draftEnd;
            },
            isCalendarDateInRange(day) {
                if (!this.draftStart || !this.draftEnd) return false;
                const value = this.calendarDate(day);
                return value > this.draftStart && value < this.draftEnd;
            },
            selectCalendarDate(day) {
                const value = this.calendarDate(day);
                if (!this.draftStart || (this.draftStart && this.draftEnd)) {
                    this.draftStart = value;
                    this.draftEnd = '';
                } else if (this.draftStart && !this.draftEnd) {
                    if (value < this.draftStart) {
                        this.draftEnd = this.draftStart;
                        this.draftStart = value;
                    } else {
                        this.draftEnd = value;
                    }
                }
                this.applyDraftLive();
            },
            setDraftTimeGroup(value) {
                this.draftTimeGroup = value;
                if (value === 'weekly') {
                    this.generateWeekOptions();
                    this.selectDraftWeek();
                } else {
                    this.draftStart = today;
                    this.draftEnd = today;
                    this.draftWeekValue = '';
                    this.applyDraftLive();
                }
            },
            generateWeekOptions() {
                if (this.draftYear === 'all') {
                    this.weekOptions = [{ value: '', label: 'Semua Periode', disabled: false }];
                    return;
                }
                const year = Number(this.draftYear);
                const endOfYear = new Date(year, 11, 31, 12);
                const cursor = new Date(year, 0, 1, 12);
                cursor.setDate(cursor.getDate() - (cursor.getDay() + 1) % 7);
                const options = [{ value: '', label: 'Semua Periode', disabled: false }];
                let week = 1;
                while (cursor <= endOfYear || cursor.getFullYear() === year) {
                    if (cursor.getFullYear() > year) break;
                    const start = new Date(cursor);
                    const end = new Date(cursor);
                    end.setDate(end.getDate() + 6);
                    const startValue = toDateValue(start);
                    const endValue = toDateValue(end);
                    const shortDate = date => date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
                    options.push({
                        value: `${startValue}|${endValue}`,
                        start: startValue,
                        end: endValue,
                        label: `Minggu ${week} : ${shortDate(start)} s.d ${shortDate(end)}`,
                        disabled: startValue > today,
                    });
                    cursor.setDate(cursor.getDate() + 7);
                    week += 1;
                }
                this.weekOptions = options;
            },
            changeDraftYear() {
                this.generateWeekOptions();
                this.draftWeekValue = '';
                if (this.draftYear === 'all') {
                    this.draftStart = '';
                    this.draftEnd = '';
                    this.applyDraftLive();
                    return;
                }
                this.draftStart = `${this.draftYear}-01-01`;
                this.draftEnd = `${this.draftYear}-12-31`;
                this.applyDraftLive();
            },
            selectDraftWeek() {
                if (!this.draftWeekValue) {
                    if (this.draftYear === 'all') {
                        this.draftStart = '';
                        this.draftEnd = '';
                    } else {
                        this.draftStart = `${this.draftYear}-01-01`;
                        this.draftEnd = `${this.draftYear}-12-31`;
                    }
                    this.applyDraftLive();
                    return;
                }
                const [start, end] = this.draftWeekValue.split('|');
                this.draftStart = start;
                this.draftEnd = end;
                this.applyDraftLive();
            },
            setDraftToday() {
                this.draftStart = today;
                this.draftEnd = today;
                this.applyDraftLive();
            },
            currentDraftWeekRange() {
                const start = new Date(today + 'T12:00:00');
                const offset = this.draftTimeGroup === 'weekly' ? (start.getDay() + 1) % 7 : ((start.getDay() || 7) - 1);
                start.setDate(start.getDate() - offset);
                const end = new Date(start);
                end.setDate(end.getDate() + 6);
                return [toDateValue(start), toDateValue(end)];
            },
            isDraftTodayActive() {
                return this.draftStart === today && this.draftEnd === today;
            },
            isDraftWeekActive() {
                const [start, end] = this.currentDraftWeekRange();
                return this.draftStart === start && this.draftEnd === end;
            },
            setDraftWeek() {
                [this.draftStart, this.draftEnd] = this.currentDraftWeekRange();
                if (this.draftTimeGroup === 'weekly') {
                    this.draftYear = todayDate.getFullYear();
                    this.generateWeekOptions();
                    this.draftWeekValue = `${this.draftStart}|${this.draftEnd}`;
                }
                this.applyDraftLive();
            },
            resetDraft() {
                this.search = '';
                this.draftBranch = 'all';
                this.draftCourier = 'all';
                this.draftStart = '';
                this.draftEnd = '';
                this.draftYear = 'all';
                this.draftWeekValue = '';
                this.calendarOpen = false;
                this.filterError = '';
            },
            resetFilter() {
                const nextGroupBy = config.type === 'shipping' ? 'product' : this.groupBy;
                const nextTimeGroup = config.type === 'shipping' ? 'daily' : this.timeGroup;
                this.resetDraft();
                this.draftGroupBy = nextGroupBy;
                this.draftTimeGroup = nextTimeGroup;
                this.branch = 'all';
                this.courier = 'all';
                this.startDate = '';
                this.endDate = '';
                this.selectedYear = 'all';
                this.selectedWeekValue = '';
                this.groupBy = nextGroupBy;
                this.timeGroup = nextTimeGroup;
                this.filterOpen = true;
                this.reload();
            },
            applyDraftLive() {
                if (config.type !== 'asset' && this.draftStart && this.draftEnd && this.draftStart > this.draftEnd) {
                    this.filterError = 'Pilih rentang tanggal yang valid.';
                    return;
                }
                this.branch = this.draftBranch;
                this.courier = config.type === 'shipping' && this.draftGroupBy === 'product' ? this.draftCourier : 'all';
                this.startDate = config.type === 'asset' ? '' : this.draftStart;
                this.endDate = config.type === 'asset' ? '' : this.draftEnd;
                this.selectedYear = this.draftYear;
                this.selectedWeekValue = this.draftWeekValue;
                this.groupBy = config.type === 'asset' ? 'product' : this.draftGroupBy;
                this.timeGroup = this.draftTimeGroup;
                this.filterError = '';
                this.reload();
            },
            applyFilter() {
                if (config.type !== 'asset' && this.draftStart && this.draftEnd && this.draftStart > this.draftEnd) {
                    this.filterError = 'Pilih rentang tanggal yang valid.';
                    return;
                }
                this.calendarOpen = false;
                this.filterOpen = false;
            },
            async openRow(row) {
                if (this.groupBy === 'date') {
                    await this.openDateHistory(row);
                    return;
                }
                if (config.type === 'asset') {
                    if (row.product_id) window.location.href = config.stockUrl + '/' + encodeURIComponent(row.product_id) + '/show';
                    return;
                }
                if (!config.history) return;
                const sequence = ++this.historySequence;
                this.historyRows = [];
                this.historyError = '';
                this.historyRow = row;
                this.historySearch = '';
                this.historyDate = '';
                this.historyPayment = 'all';
                this.historyCourier = 'all';
                this.historyFilterOpen = false;
                this.historyFilterExpanded = false;
                this.historyCalendarOpen = false;
                this.historyTitle = this.rowTitle(row);
                this.historyLoading = true;
                this.historyOpen = true;
                const params = new URLSearchParams({ branch: this.branch });
                if (config.type === 'shipping' && row.branch_id !== undefined) params.set('branch', row.branch_id == null ? 'null' : String(row.branch_id));
                if (this.startDate) params.set('start_date', this.startDate);
                if (this.endDate) params.set('end_date', this.endDate);
                params.set(config.type === 'shipping' ? 'courier_id' : 'product_id', row.courier_id ?? row.product_id ?? 'null');
                if (['sales', 'profit', 'adjusted'].includes(config.type) && row.price !== undefined && row.price !== null) {
                    params.set('price', String(parseMoney(row.price_value ?? row.price)));
                }
                try {
                    const response = await fetch(config.history + '?' + params.toString(), { credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json' } });
                    if (!response.ok || response.redirected) throw new Error(response.status === 401 || response.redirected ? 'Sesi telah berakhir. Masuk kembali untuk melanjutkan.' : response.status === 403 ? 'Anda tidak memiliki akses ke riwayat ini.' : 'Riwayat belum dapat dimuat. Coba lagi.');
                    const result = await response.json();
                    if (sequence !== this.historySequence) return;
                    this.historyRows = Array.isArray(result.data) ? result.data : [];
                    this.historyTitle = result.product_name || result.courier_name || this.historyTitle;
                } catch (error) {
                    if (sequence === this.historySequence) { this.historyRows = []; this.historyError = error.message === 'Failed to fetch' ? 'Periksa koneksi lalu coba lagi.' : error.message; }
                } finally {
                    if (sequence === this.historySequence) this.historyLoading = false;
                }
            },
            async openDateHistory(row) {
                const sequence = ++this.historySequence;
                this.historyRows = [];
                this.historyError = '';
                this.historyRow = row;
                this.historySearch = '';
                this.historyDate = '';
                this.historyPayment = 'all';
                this.historyCourier = 'all';
                this.historyFilterOpen = false;
                this.historyFilterExpanded = false;
                this.historyCalendarOpen = false;
                this.historyTitle = this.rowTitle(row);
                this.historyLoading = true;
                this.historyOpen = true;
                const periodStart = String(row.period_start).slice(0, 10);
                const periodEnd = this.timeGroup === 'weekly' ? nextDay(periodStart) : periodStart;
                const params = new URLSearchParams({
                    start_date: this.startDate && this.startDate > periodStart ? this.startDate : periodStart,
                    end_date: this.endDate && this.endDate < periodEnd ? this.endDate : periodEnd,
                    branch_id: row.branch_id == null ? 'null' : String(row.branch_id),
                    search_text: this.search.trim(),
                    group_by: 'date',
                });
                try {
                    const response = await fetch(config.dateHistory + '?' + params.toString(), { credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json' } });
                    if (!response.ok || response.redirected) throw new Error(response.status === 401 || response.redirected ? 'Sesi telah berakhir. Masuk kembali untuk melanjutkan.' : response.status === 403 ? 'Anda tidak memiliki akses ke riwayat ini.' : 'Riwayat belum dapat dimuat. Coba lagi.');
                    const result = await response.json();
                    if (sequence !== this.historySequence) return;
                    this.historyRows = Array.isArray(result.data) ? result.data : [];
                } catch (error) {
                    if (sequence === this.historySequence) { this.historyRows = []; this.historyError = error.message === 'Failed to fetch' ? 'Periksa koneksi lalu coba lagi.' : error.message; }
                } finally {
                    if (sequence === this.historySequence) this.historyLoading = false;
                }
            },
            historyAmount(entry) {
                if (config.type === 'shipping') return entry.total_bersih || entry.ongkir || 'Rp 0';
                if (this.groupBy === 'date') return entry.total || 'Rp 0';
                return entry.laba_disesuaikan || entry.laba_kotor || entry.total || entry.total_hpp || 'Rp 0';
            },
            historyTotalLabel(entry) {
                if (config.type === 'loss') return 'Total';
                if (this.groupBy === 'date' && entry?.total_label) return entry.total_label;
                if (config.type === 'profit') return 'Laba Kotor';
                if (config.type === 'adjusted') return entry.type === 'Penjualan' ? 'Laba Kotor' : 'Koreksi Stok';
                if (config.type === 'shipping') return 'Total Bersih';
                return 'Total';
            },
            historyDetails(entry) {
                if (this.groupBy === 'date') {
                    if (config.type === 'sales') return [
                        { label: 'Pembayaran', value: entry.payment },
                        { label: 'Subtotal', value: entry.subtotal },
                        { label: 'Diskon Produk', value: entry.product_discount },
                        { label: 'Diskon Prorata', value: entry.prorata_discount },
                    ].filter(line => line.value);
                    if (config.type === 'profit') return [
                        { label: 'Pembayaran', value: entry.payment },
                        { label: 'Harga Jual Satuan', value: entry.harga_satuan },
                        { label: 'Penjualan Kotor', value: entry.penjualan_kotor },
                        { label: 'Pendapatan Bersih', value: entry.pendapatan_bersih },
                        { label: 'HPP Satuan', value: entry.hpp_satuan },
                        { label: 'Total HPP', value: entry.total_hpp },
                    ].filter(line => line.value);
                    if (config.type === 'loss') return [
                        { label: 'Alasan', value: entry.reason },
                        { label: 'Jumlah Dibuang', value: entry.qty ?? entry.quantity },
                        { label: 'Kerugian (Nilai HPP)', value: entry.subtotal || entry.total_hpp },
                        { label: 'Diskon Produk', value: entry.product_discount },
                    ].filter(line => line.value);
                    if (config.type === 'adjusted') {
                        if (entry.type === 'Penjualan') return [
                            { label: 'Pembayaran', value: entry.payment },
                            { label: 'Laba Kotor', value: entry.laba_kotor },
                        ].filter(line => line.value);
                        return [
                            { label: 'Jenis', value: entry.type },
                            { label: 'Jumlah', value: entry.quantity && Number(entry.quantity) !== 0 ? Number(entry.quantity).toLocaleString('id-ID', { maximumFractionDigits: 2 }) : '' },
                            { label: 'Koreksi Stok', value: entry.koreksi_stock },
                        ].filter(line => line.value);
                    }
                    if (config.type === 'shipping') return [
                        { label: 'Kurir', value: entry.courier_name },
                        { label: 'Pelanggan', value: entry.customer_name },
                        { label: 'Alamat', value: entry.address },
                        { label: 'Pembayaran', value: entry.payment },
                        { label: 'Biaya Ongkir', value: entry.ongkir },
                        { label: 'Diskon Ongkir', value: entry.ongkir_discount },
                    ].filter(line => line.value && line.value !== '-');
                    return [
                        { label: 'Jumlah', value: entry.quantity && Number(entry.quantity) !== 0 ? Number(entry.quantity).toLocaleString('id-ID', { maximumFractionDigits: 2 }) : '' },
                        { label: 'Subtotal', value: entry.subtotal },
                    ].filter(line => line.value);
                }
                if (config.type === 'sales') return [
                    { label: 'Pembayaran', value: entry.payment },
                    { label: 'Jumlah Terjual', value: entry.qty },
                    { label: 'Subtotal', value: entry.subtotal },
                    { label: 'Diskon Produk', value: entry.product_discount },
                    { label: 'Diskon Prorata', value: entry.prorata_discount },
                ].filter(line => line.value);
                if (config.type === 'profit') return [
                    { label: 'Pembayaran', value: entry.payment },
                    { label: 'Jumlah Terjual', value: entry.qty },
                    { label: 'Harga Jual Satuan', value: entry.harga_satuan },
                    { label: 'Penjualan Kotor', value: entry.penjualan_kotor },
                    { label: 'Pendapatan Bersih', value: entry.pendapatan_bersih },
                    { label: 'HPP Satuan', value: entry.hpp_satuan },
                    { label: 'Total HPP', value: entry.total_hpp },
                ].filter(line => line.value);
                if (config.type === 'adjusted') return [
                    { label: 'Jenis', value: entry.type },
                    { label: 'Laba Kotor', value: entry.laba_kotor },
                    { label: 'Koreksi Stok', value: entry.koreksi_stock },
                ].filter(line => line.value);
                if (config.type === 'shipping') return [
                    { label: 'Pelanggan', value: entry.customer_name },
                    { label: 'Alamat', value: entry.address },
                    { label: 'Pembayaran', value: entry.payment },
                    { label: 'Biaya Ongkir', value: entry.ongkir },
                    { label: 'Diskon Ongkir', value: entry.ongkir_discount },
                ].filter(line => line.value && line.value !== '-');
                if (config.type === 'loss') return [
                    { label: 'Alasan', value: entry.reason },
                    { label: 'Jumlah Dibuang', value: entry.qty },
                    { label: 'Kerugian (Nilai HPP)', value: entry.subtotal },
                    { label: 'Diskon Produk', value: entry.product_discount },
                ].filter(line => line.value);
                return [
                    { label: 'Jumlah', value: entry.qty },
                    { label: 'Subtotal', value: entry.subtotal },
                ].filter(line => line.value);
            },
        };
    };
})();
