@php
    $reportMobileTypes = [
        'asset' => ['title' => 'Total Aset', 'endpoint' => route('report-total-aset.data'), 'history' => null, 'placeholder' => 'Cari transaksi...'],
        'sales' => ['title' => 'Pendapatan Penjualan', 'endpoint' => route('report-product-sales.data'), 'history' => route('report-product-sales.history'), 'placeholder' => 'Cari transaksi...'],
        'profit' => ['title' => 'Keuntungan Penjualan', 'endpoint' => route('report-profit-revenue.data'), 'history' => route('report-profit-revenue.history'), 'placeholder' => 'Cari transaksi...'],
        'adjusted' => ['title' => 'Keuntungan Setelah Penyesuaian', 'endpoint' => route('report-profit-adjusted.data'), 'history' => route('report-profit-adjusted.history'), 'placeholder' => 'Cari transaksi...'],
        'shipping' => ['title' => 'Ongkos Kirim', 'endpoint' => route('report-shipping-cost.data'), 'history' => route('report-shipping-cost.history'), 'placeholder' => 'Cari transaksi...'],
        'loss' => ['title' => 'Kerugian Barang Buang', 'endpoint' => route('report-product-buang.data'), 'history' => route('report-product-buang.history'), 'placeholder' => 'Cari transaksi...'],
    ];
    $reportMobileConfig = $reportMobileTypes[$reportType];
    $reportMobileConfig['type'] = $reportType;
    $reportMobileConfig['branches'] = collect($branches ?? [])->map(static fn ($branch) => ['id' => (string) $branch->id, 'name' => $branch->name])->values();
    $reportMobileConfig['couriers'] = collect($couriers ?? [])->map(static fn ($courier) => ['id' => (string) $courier->id, 'name' => $courier->name])->values();
    $reportMobileConfig['today'] = now()->toDateString();
    $reportMobileConfig['stockUrl'] = url('product-stock');
    $reportMobileConfig['dateEndpoint'] = route('reports.mobile.dates', ['type' => $reportType]);
    $reportMobileConfig['dateHistory'] = route('reports.mobile.history', ['type' => $reportType]);
@endphp

<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="{{ asset('assets/css/report-mobile.css') }}?v={{ filemtime(public_path('assets/css/report-mobile.css')) }}">
<script type="application/json" id="report-mobile-config">@json($reportMobileConfig)</script>
<script src="{{ asset('assets/js/report-mobile.js') }}?v={{ filemtime(public_path('assets/js/report-mobile.js')) }}"></script>

<div class="report-mobile-page" :class="'report-mobile-page--' + config.type" x-data="erpReportMobile(JSON.parse(document.getElementById('report-mobile-config').textContent))" x-effect="document.body.style.overflow = (filterOpen || historyOpen) ? 'hidden' : ''" @mobile-search-toggle.window="$refs.reportSearch.focus()">
    <x-erp.mobile-search>
        <x-slot:input>
            <input x-ref="reportSearch" x-model="search" @input.debounce.350ms="reload()" type="search" :placeholder="config.placeholder" :aria-label="config.placeholder" class="erp-mobile-search__input">
        </x-slot:input>
        <x-slot:clear>
            <button x-show="search" x-cloak type="button" @click="search = ''; reload(); $refs.reportSearch.focus()" class="erp-mobile-search__clear" aria-label="Hapus pencarian"><i class="ph-bold ph-x text-xs" aria-hidden="true"></i></button>
        </x-slot:clear>
        <x-slot:filter>
            <button type="button" @click="openFilter()" class="erp-mobile-search__filter" :class="filterOpen || hasFilter ? 'is-active' : ''" aria-label="Filter laporan" title="Filter">
                <i class="ph ph-sliders-horizontal" aria-hidden="true"></i>
            </button>
        </x-slot:filter>
    </x-erp.mobile-search>

    <div class="report-mobile-hpp-notice" x-show="config.type === 'asset' || config.type === 'loss'"><i class="ph-fill ph-info" aria-hidden="true"></i><span x-show="config.type === 'asset'">Nilai rupiah pada laporan aset dihitung berdasarkan <strong>Nilai HPP</strong>, bukan harga jual.</span><span x-show="config.type === 'loss'">Nilai rupiah pada barang buang dihitung berdasarkan <strong>Nilai HPP</strong>, bukan harga jual.</span></div>

    <div class="report-mobile-list" x-ref="reportList" @scroll.passive="maybeLoadMore()">
        <template x-for="(row, index) in rows" :key="groupBy === 'date' ? `${row.period_start}-${row.branch_id}` : (config.type === 'shipping' ? `${row.courier_id}-${row.branch_id}` : `${row.product_id ?? row.courier_id ?? index}-${row.price ?? row.hpp ?? ''}`)">
            <div style="display:contents">
                <template x-if="config.type === 'asset'">
                    <div class="report-mobile-row report-mobile-row--static">
                        <span class="report-mobile-row__stacked"><span class="report-mobile-row__title" x-text="rowTitle(row)"></span><span class="report-mobile-row__meta"><span x-text="rowMeta(row)"></span><strong class="report-mobile-row__amount" x-text="rupiah(rowAmount(row))"></strong></span></span>
                    </div>
                </template>
                <template x-if="config.type !== 'asset'">
                    <button type="button" class="report-mobile-row" @click="openRow(row)">
                        <template x-if="config.type === 'profit' || config.type === 'adjusted'">
                            <span class="report-mobile-row__top">
                                <span class="report-mobile-row__heading"><span class="report-mobile-row__title" x-text="rowTitle(row)"></span><span x-show="groupBy === 'date'" class="report-mobile-row__branch" x-text="rowMeta(row)"></span></span>
                                <span class="report-mobile-row__amount report-mobile-row__amount--mobile" :class="rowAmount(row) < 0 ? 'is-negative' : 'is-positive'" x-text="rowDisplayAmount(row)"></span>
                                <span x-show="config.type === 'profit'" class="report-mobile-row__desktop-metrics"><span><small>Pendapatan</small><strong x-text="rupiah(groupBy === 'date' ? row.revenue : row.total_pendapatan)"></strong></span><span><small>HPP</small><strong class="is-negative" x-text="'- ' + rupiah(groupBy === 'date' ? row.hpp : row.total_hpp)"></strong></span><span><small>Laba Kotor</small><strong :class="rowAmount(row) < 0 ? 'is-negative' : 'is-positive'" x-text="rowDisplayAmount(row)"></strong></span></span>
                                <span x-show="config.type === 'adjusted'" class="report-mobile-row__desktop-metrics"><span><small>Laba Kotor</small><strong x-text="rupiah(groupBy === 'date' ? row.revenue - row.hpp : row.laba_kotor)"></strong></span><span><small>Koreksi Stok</small><strong :class="Number(groupBy === 'date' ? row.correction : row.koreksi_stock) <= 0 ? 'is-positive' : 'is-negative'" x-text="adjustedCorrection(row)"></strong></span><span><small>Laba Disesuaikan</small><strong :class="rowAmount(row) < 0 ? 'is-negative' : 'is-positive'" x-text="rowDisplayAmount(row)"></strong></span></span>
                            </span>
                        </template>
                        <template x-if="config.type !== 'profit' && config.type !== 'adjusted'">
                            <span class="report-mobile-row__stacked">
                                <span class="report-mobile-row__title" x-text="rowTitle(row)"></span>
                                <span class="report-mobile-row__meta"><span x-text="rowMeta(row)"></span><strong class="report-mobile-row__amount" :class="rowAmount(row) < 0 ? 'is-negative' : ''" x-text="rupiah(rowAmount(row))"></strong></span>
                            </span>
                        </template>
                    </button>
                </template>
            </div>
        </template>
        <div x-show="!loading && !error && rows.length === 0" x-cloak class="report-mobile-empty">
            <div class="report-mobile-empty__icon"><i class="ph ph-receipt" aria-hidden="true"></i></div>
            <h2>Tidak Ada Data</h2>
            <p>Belum ada transaksi pada kriteria ini.</p>
        </div>
        <div x-show="loading" x-cloak class="report-mobile-empty" role="status"><div class="report-mobile-empty__icon"><i class="ph ph-circle-notch ph-spin" aria-hidden="true"></i></div><p>Memuat laporan...</p></div>
        <div x-show="error" x-cloak class="report-mobile-empty" role="alert"><div class="report-mobile-empty__icon"><i class="ph ph-warning-circle" aria-hidden="true"></i></div><h2>Gagal memuat laporan</h2><p x-text="error"></p><button type="button" class="report-mobile-more" @click="reload()">Coba lagi</button></div>
    </div>

    <div class="report-mobile-summary" style="position:relative">
        <template x-for="line in summaryLines" :key="line.label">
            <div class="report-mobile-summary__line" :class="line.style || ''">
                <span class="report-mobile-summary__label"><span x-text="line.label"></span><button x-show="line.info" type="button" class="report-mobile-summary__info" @click="infoOpen = !infoOpen" aria-label="Informasi nilai HPP"><i class="ph-bold ph-warning-circle" aria-hidden="true"></i></button><span x-show="line.badge" x-cloak class="report-mobile-summary__badge" :class="line.tone === 'negative' ? 'is-negative' : 'is-positive'" x-text="line.badge"></span></span>
                <strong :class="line.tone === 'negative' ? 'is-negative' : (line.tone === 'positive' ? 'is-positive' : '')" x-text="line.value"></strong>
            </div>
        </template>
        <div x-show="infoOpen" x-cloak @click.outside="infoOpen = false" class="report-mobile-info"><span x-show="config.type === 'asset'">Nilai rupiah pada laporan aset dihitung berdasarkan <strong>Nilai HPP</strong>, bukan harga jual.</span><span x-show="config.type === 'loss'">Nilai rupiah pada barang buang dihitung berdasarkan <strong>Nilai HPP</strong>, bukan harga jual.</span></div>
    </div>

    <template x-teleport="body">
        <div x-show="filterOpen" x-cloak>
            <div class="report-mobile-backdrop" @click="calendarOpen = false; filterOpen = false"></div>
            <div class="report-mobile-sheet" role="dialog" aria-modal="true" aria-label="Filter Laporan" @keydown.escape.window="calendarOpen = false; filterOpen = false">
                <button type="button" class="report-mobile-sheet__handle" @click="calendarOpen = false; filterOpen = false" aria-label="Tutup filter"></button>
                <div class="report-mobile-sheet__head"><div><h2>Filter Laporan</h2><p>Sesuaikan parameter data laporan</p></div><button type="button" class="report-mobile-sheet__close" @click="calendarOpen = false; filterOpen = false" aria-label="Tutup filter"><i class="ph-bold ph-x" aria-hidden="true"></i></button></div>
                <div class="report-mobile-sheet__body">
                    <div class="report-mobile-filter-controls" x-show="!calendarOpen">
                        <div class="report-mobile-field" x-show="config.type !== 'asset'">
                            <span class="report-mobile-field__label">Grup Berdasarkan</span>
                            <div class="report-mobile-grouping">
                                <button type="button" :class="draftGroupBy === 'product' ? 'is-active' : ''" @click="draftGroupBy = 'product'; applyDraftLive()"><i class="ph-fill ph-package" aria-hidden="true"></i><span x-text="config.type === 'shipping' ? 'Kurir' : 'Produk'"></span></button>
                                <button type="button" :class="draftGroupBy === 'date' ? 'is-active' : ''" @click="draftGroupBy = 'date'; applyDraftLive()"><i class="ph ph-calendar-blank" aria-hidden="true"></i>Tanggal</button>
                            </div>
                            <div class="report-mobile-select" x-data="{open:false}" @click.outside="open = false">
                                <button type="button" class="report-mobile-select__trigger" @click="open = !open" :aria-expanded="open" aria-haspopup="listbox" aria-label="Kelompokkan tanggal"><span x-text="draftTimeGroup === 'daily' ? 'Harian' : 'Mingguan'"></span><i class="ph-bold ph-caret-down" :class="open ? 'is-open' : ''" aria-hidden="true"></i></button>
                                <div x-show="open" x-cloak class="report-mobile-select__menu" role="listbox" aria-label="Kelompokkan tanggal">
                                    <button type="button" role="option" :aria-selected="draftTimeGroup === 'daily'" :class="draftTimeGroup === 'daily' ? 'is-selected' : ''" @click="setDraftTimeGroup('daily'); open = false"><span>Harian</span><i x-show="draftTimeGroup === 'daily'" class="ph-fill ph-circle" aria-hidden="true"></i></button>
                                    <button type="button" role="option" :aria-selected="draftTimeGroup === 'weekly'" :class="draftTimeGroup === 'weekly' ? 'is-selected' : ''" @click="setDraftTimeGroup('weekly'); open = false"><span>Mingguan</span><i x-show="draftTimeGroup === 'weekly'" class="ph-fill ph-circle" aria-hidden="true"></i></button>
                                </div>
                            </div>
                        </div>
                        <div class="report-mobile-field">
                            <div class="report-mobile-field__heading"><span class="report-mobile-field__label">Cabang</span><button x-show="draftBranch !== 'all'" type="button" @click="draftBranch = 'all'; branchSearchQuery = ''; applyDraftLive()" class="report-mobile-field__reset">Reset</button></div>
                            <div class="report-mobile-branch-picker">
                                <label class="report-mobile-branch-search">
                                    <i class="ph ph-magnifying-glass" aria-hidden="true"></i>
                                    <input type="search" x-model="branchSearchQuery" placeholder="Cari cabang..." autocomplete="off" aria-label="Cari cabang">
                                </label>
                                <div class="report-mobile-branch-options" role="radiogroup" aria-label="Pilih cabang">
                                    <template x-for="branch in config.branches.filter(item => item.name.toLocaleLowerCase('id-ID').includes(branchSearchQuery.trim().toLocaleLowerCase('id-ID')))" :key="branch.id">
                                        <label class="report-mobile-branch-option">
                                            <input type="radio" name="report-mobile-branch" :value="String(branch.id)" x-model="draftBranch" @change="applyDraftLive()">
                                            <span x-text="branch.name"></span>
                                        </label>
                                    </template>
                                    <p x-show="config.branches.filter(item => item.name.toLocaleLowerCase('id-ID').includes(branchSearchQuery.trim().toLocaleLowerCase('id-ID'))).length === 0" class="report-mobile-branch-empty">Cabang tidak ditemukan</p>
                                </div>
                            </div>
                        </div>
                        <div class="report-mobile-field" x-show="config.type === 'shipping' && draftGroupBy === 'product'">
                            <div class="report-mobile-field__heading"><span class="report-mobile-field__label">Kurir</span><button x-show="draftCourier !== 'all'" type="button" @click="draftCourier = 'all'; applyDraftLive()" class="report-mobile-field__reset">Reset</button></div>
                            <div class="report-mobile-select" x-data="{open:false}" @click.outside="open = false">
                                <button type="button" class="report-mobile-select__trigger" @click="open = !open" :aria-expanded="open" aria-haspopup="listbox" aria-label="Kurir"><span x-text="draftCourierName()"></span><i class="ph-bold ph-caret-down" :class="open ? 'is-open' : ''" aria-hidden="true"></i></button>
                                <div x-show="open" x-cloak class="report-mobile-select__menu" role="listbox" aria-label="Pilih kurir">
                                    <button type="button" role="option" :aria-selected="draftCourier === 'all'" :class="draftCourier === 'all' ? 'is-selected' : ''" @click="draftCourier = 'all'; open = false; applyDraftLive()"><span>Semua Kurir</span><i x-show="draftCourier === 'all'" class="ph-fill ph-circle" aria-hidden="true"></i></button>
                                    <template x-for="courier in config.couriers" :key="courier.id"><button type="button" role="option" :aria-selected="String(draftCourier) === String(courier.id)" :class="String(draftCourier) === String(courier.id) ? 'is-selected' : ''" @click="draftCourier = String(courier.id); open = false; applyDraftLive()"><span x-text="courier.name"></span><i x-show="String(draftCourier) === String(courier.id)" class="ph-fill ph-circle" aria-hidden="true"></i></button></template>
                                    <button type="button" role="option" :aria-selected="draftCourier === 'null'" :class="draftCourier === 'null' ? 'is-selected' : ''" @click="draftCourier = 'null'; open = false; applyDraftLive()"><span>Tanpa Kurir / Lainnya</span><i x-show="draftCourier === 'null'" class="ph-fill ph-circle" aria-hidden="true"></i></button>
                                </div>
                            </div>
                        </div>
                        <div class="report-mobile-field" x-show="config.type !== 'asset' && draftTimeGroup === 'weekly'">
                            <div class="report-mobile-field__heading"><span class="report-mobile-field__label">Tahun</span><button x-show="draftYear !== 'all'" type="button" @click="draftYear = 'all'; changeDraftYear()" class="report-mobile-field__reset">Reset</button></div>
                            <div class="report-mobile-select" x-data="{open:false}" @click.outside="open = false">
                                <button type="button" class="report-mobile-select__trigger" @click="open = !open" :aria-expanded="open" aria-haspopup="listbox" aria-label="Tahun"><span x-text="draftYearLabel()"></span><i class="ph-bold ph-caret-down" :class="open ? 'is-open' : ''" aria-hidden="true"></i></button>
                                <div x-show="open" x-cloak class="report-mobile-select__menu" role="listbox" aria-label="Pilih tahun">
                                    <button type="button" role="option" :aria-selected="draftYear === 'all'" :class="draftYear === 'all' ? 'is-selected' : ''" @click="draftYear = 'all'; changeDraftYear(); open = false"><span>Semua Tahun</span><i x-show="draftYear === 'all'" class="ph-fill ph-circle" aria-hidden="true"></i></button>
                                    <template x-for="year in reportYears" :key="year"><button type="button" role="option" :aria-selected="Number(draftYear) === year" :class="Number(draftYear) === year ? 'is-selected' : ''" @click="draftYear = year; changeDraftYear(); open = false"><span x-text="year"></span><i x-show="Number(draftYear) === year" class="ph-fill ph-circle" aria-hidden="true"></i></button></template>
                                </div>
                            </div>
                        </div>
                        <div class="report-mobile-field" x-show="config.type !== 'asset' && draftTimeGroup === 'weekly'">
                            <div class="report-mobile-field__heading"><span class="report-mobile-field__label">Periode Mingguan</span><button x-show="draftWeekValue" type="button" @click="draftWeekValue = ''; selectDraftWeek()" class="report-mobile-field__reset">Reset</button></div>
                            <div class="report-mobile-select" x-data="{open:false}" @click.outside="open = false">
                                <button type="button" class="report-mobile-select__trigger" @click="open = !open; if(open) $nextTick(() => $refs.weekMenu?.querySelector('[aria-selected=true]')?.scrollIntoView({block:'center'}))" :aria-expanded="open" aria-haspopup="listbox" aria-label="Periode mingguan"><span x-text="draftWeekLabel()"></span><i class="ph-bold ph-caret-down" :class="open ? 'is-open' : ''" aria-hidden="true"></i></button>
                                <div x-ref="weekMenu" x-show="open" x-cloak class="report-mobile-select__menu report-mobile-select__menu--weeks" role="listbox" aria-label="Pilih periode mingguan">
                                    <template x-for="week in weekOptions" :key="week.value"><button type="button" role="option" :aria-selected="draftWeekValue === week.value" :disabled="week.disabled" :class="draftWeekValue === week.value ? 'is-selected' : ''" @click="draftWeekValue = week.value; selectDraftWeek(); open = false"><span x-text="week.label"></span><i x-show="draftWeekValue === week.value" class="ph-fill ph-circle" aria-hidden="true"></i></button></template>
                                </div>
                            </div>
                        </div>
                        <div class="report-mobile-field" x-show="config.type !== 'asset' && draftTimeGroup === 'daily'">
                            <div class="report-mobile-field__heading"><span class="report-mobile-field__label">Periode Tanggal</span><button x-show="draftStart || draftEnd" type="button" @click="draftStart = ''; draftEnd = ''; applyDraftLive()" class="report-mobile-field__reset">Reset</button></div>
                            <div class="report-mobile-date-grid">
                                <label class="report-mobile-date-input" :class="calendarOpen ? 'is-active' : ''" aria-label="Tanggal awal">
                                    <span>DARI</span>
                                    <input type="text" readonly :value="draftStart ? fullDateLabel(draftStart) : ''" placeholder="Pilih Tanggal" @click="openDateCalendar()" @keydown.enter.prevent="openDateCalendar()" @keydown.space.prevent="openDateCalendar()" aria-label="Pilih tanggal awal">
                                </label>
                                <label class="report-mobile-date-input" :class="calendarOpen ? 'is-active' : ''" aria-label="Tanggal akhir">
                                    <span>KE</span>
                                    <input type="text" readonly :value="draftEnd ? fullDateLabel(draftEnd) : ''" placeholder="Pilih Tanggal" @click="openDateCalendar()" @keydown.enter.prevent="openDateCalendar()" @keydown.space.prevent="openDateCalendar()" aria-label="Pilih tanggal akhir">
                                </label>
                            </div>
                        </div>
                        <div class="report-mobile-quick-wrap" x-show="config.type !== 'asset'">
                            <span class="report-mobile-field__label">Pilihan Cepat</span>
                            <div class="report-mobile-quick" x-show="draftTimeGroup === 'daily'"><button type="button" :class="isDraftWeekActive() ? 'is-active' : ''" @click="setDraftWeek()">Minggu Ini</button><button type="button" :class="isDraftTodayActive() ? 'is-active' : ''" @click="setDraftToday()">Hari Ini</button></div>
                            <div class="report-mobile-quick report-mobile-quick--single" x-show="draftTimeGroup === 'weekly'"><button type="button" :class="isDraftWeekActive() ? 'is-active' : ''" @click="setDraftWeek()">Minggu Ini</button></div>
                        </div>
                    </div>
                    <div x-show="calendarOpen" x-cloak class="report-mobile-calendar">
                        <div class="report-mobile-calendar__head">
                            <button type="button" class="report-mobile-calendar__title" @click="toggleCalendarView()"><span x-text="calendarHeading"></span><i class="ph-bold ph-caret-down" aria-hidden="true"></i></button>
                            <div><button type="button" @click="navigateCalendar(-1)" aria-label="Periode sebelumnya"><i class="ph-bold ph-caret-left" aria-hidden="true"></i></button><button type="button" @click="navigateCalendar(1)" aria-label="Periode berikutnya"><i class="ph-bold ph-caret-right" aria-hidden="true"></i></button></div>
                        </div>
                        <div x-show="calendarView === 'date'">
                            <div class="report-mobile-calendar__grid report-mobile-calendar__weekdays"><template x-for="(day, index) in calendarWeekdays" :key="index"><span x-text="day"></span></template></div>
                            <div class="report-mobile-calendar__grid report-mobile-calendar__days"><template x-for="blank in calendarBlankDays" :key="'blank-' + blank"><span></span></template><template x-for="day in calendarDays" :key="day"><span class="report-mobile-calendar__day" :class="{'is-selected': isCalendarDateSelected(day), 'is-in-range': isCalendarDateInRange(day), 'is-range-start': calendarDate(day) === draftStart && draftEnd && draftStart !== draftEnd, 'is-range-end': calendarDate(day) === draftEnd && draftStart !== draftEnd}"><button type="button" @click="selectCalendarDate(day)" x-text="day"></button></span></template></div>
                        </div>
                        <div x-show="calendarView === 'month'" x-cloak class="report-mobile-calendar__choices"><template x-for="(month, index) in calendarMonths" :key="month"><button type="button" :class="calendarMonth === index ? 'is-selected' : ''" @click="chooseCalendarMonth(index)" x-text="month"></button></template></div>
                        <div x-show="calendarView === 'year'" x-cloak class="report-mobile-calendar__choices"><template x-for="year in calendarYears" :key="year"><button type="button" :class="calendarYear === year ? 'is-selected' : ''" @click="chooseCalendarYear(year)" x-text="year"></button></template></div>
                        <button type="button" class="report-mobile-calendar__done" @click="calendarOpen = false"><i class="ph-bold ph-arrow-left" aria-hidden="true"></i>Selesai Memilih Tanggal</button>
                    </div>
                    <p x-show="filterError" x-cloak role="alert" style="margin:12px 0 0;color:#dc2626;font-size:12px" x-text="filterError"></p>
                </div>
                <div class="report-mobile-sheet__foot"><button type="button" class="report-mobile-sheet__cancel" @click="resetFilter()">Reset Filter</button><button type="button" class="report-mobile-sheet__apply" @click="applyFilter()">Terapkan Filter</button></div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <div x-show="historyOpen" x-cloak>
            <div class="report-mobile-backdrop" @click="historyOpen = false"></div>
            <div class="report-mobile-sheet report-mobile-sheet--history" :class="config.type === 'adjusted' ? 'report-mobile-sheet--history-adjusted' : ''" role="dialog" aria-modal="true" :aria-label="config.type === 'adjusted' ? 'Detail Rekonsiliasi' : (config.type === 'shipping' ? 'Riwayat Pengiriman' : 'Riwayat Transaksi')" @keydown.escape.window="historyOpen = false">
                <div class="report-mobile-sheet__handle"></div>
                <div class="report-mobile-sheet__head"><div><h2 x-text="config.type === 'adjusted' ? 'Detail Rekonsiliasi' : (config.type === 'shipping' ? 'Riwayat Pengiriman' : 'Riwayat Transaksi')"></h2><p x-text="historyTitle"></p></div><button type="button" class="report-mobile-sheet__close" @click="historyOpen = false" aria-label="Tutup riwayat"><i class="ph-bold ph-x" aria-hidden="true"></i></button></div>
                <div x-show="config.type !== 'adjusted'" class="report-mobile-history-tools">
                    <div class="report-mobile-history-search"><i class="ph ph-magnifying-glass" aria-hidden="true"></i><input type="search" x-model="historySearch" placeholder="Cari transaksi..." aria-label="Cari transaksi"></div>
                    <div class="report-mobile-history-filter-wrap" @mouseenter="historyFilterExpanded = true" @mouseleave="if (!historyFilterOpen) historyFilterExpanded = false" @click.outside="historyFilterOpen = false; historyCalendarOpen = false; historyFilterExpanded = false">
                        <button type="button" class="report-mobile-history-filter-button" :class="historyDate || historyPayment !== 'all' || historyCourier !== 'all' || historyFilterOpen ? 'is-active' : ''" @click="historyFilterOpen = !historyFilterOpen; historyFilterExpanded = true" :aria-expanded="historyFilterOpen" aria-label="Filter riwayat">
                            <span class="report-mobile-history-filter-icon"><i class="ph-bold ph-funnel" aria-hidden="true"></i><span x-show="historyDate || historyPayment !== 'all' || historyCourier !== 'all'" class="report-mobile-history-filter-dot"></span></span>
                            <span class="report-mobile-history-filter-period" :class="historyFilterExpanded || historyFilterOpen ? 'is-expanded' : ''" x-text="historyDate ? fullDateLabel(historyDate) : 'Semua Periode'"></span>
                            <i class="ph-bold ph-caret-down report-mobile-history-filter-caret" :class="historyFilterOpen ? 'is-open' : ''" aria-hidden="true"></i>
                        </button>
                        <div x-show="historyFilterOpen" x-cloak class="report-mobile-history-filter-popover">
                            <div class="report-mobile-history-filter-field">
                                <span class="report-mobile-field__label">Tanggal</span>
                                <button type="button" class="report-mobile-history-date-trigger" @click="openHistoryCalendar()" :aria-expanded="historyCalendarOpen"><i class="ph ph-calendar-blank" aria-hidden="true"></i><span x-text="historyDate ? fullDateLabel(historyDate) : 'Pilih Tanggal'"></span><i class="ph-bold ph-caret-down" aria-hidden="true"></i></button>
                                <div x-show="historyCalendarOpen" x-cloak class="report-mobile-history-calendar">
                                    <div class="report-mobile-history-calendar__head"><strong x-text="historyCalendarMonthLabel()"></strong><div><button type="button" @click="navigateHistoryCalendar(-1)" aria-label="Bulan sebelumnya"><i class="ph-bold ph-caret-left" aria-hidden="true"></i></button><button type="button" @click="navigateHistoryCalendar(1)" aria-label="Bulan berikutnya"><i class="ph-bold ph-caret-right" aria-hidden="true"></i></button></div></div>
                                    <div class="report-mobile-history-calendar__grid report-mobile-history-calendar__weekdays"><template x-for="(day, index) in calendarWeekdays" :key="index"><span x-text="day"></span></template></div>
                                    <div class="report-mobile-history-calendar__grid report-mobile-history-calendar__days"><template x-for="blank in historyCalendarBlankDays()" :key="'blank-' + blank"><span></span></template><template x-for="day in historyCalendarDays()" :key="day"><button type="button" :disabled="isHistoryCalendarDateDisabled(day)" :class="{'is-selected': historyCalendarDate(day) === historyDate}" @click="selectHistoryCalendarDate(day)" x-text="day"></button></template></div>
                                </div>
                            </div>
                            <div x-show="historyPaymentOptions.length" class="report-mobile-history-filter-field" x-data="{open:false}" @click.outside="open = false">
                                <span class="report-mobile-field__label" x-text="config.type === 'loss' ? 'Alasan' : 'Tipe Pembayaran'"></span>
                                <button type="button" class="report-mobile-history-date-trigger" @click="open = !open" :aria-expanded="open"><span x-text="historyPayment === 'all' ? 'Semua Metode' : (historyPayment === 'split' ? 'Multi/Split' : historyPayment)"></span><i class="ph-bold ph-caret-down" :class="open ? 'is-open' : ''" aria-hidden="true"></i></button>
                                <div x-show="open" x-cloak class="report-mobile-history-option-menu">
                                    <button type="button" :class="historyPayment === 'all' ? 'is-selected' : ''" @click="historyPayment = 'all'; open = false">Semua Metode<i x-show="historyPayment === 'all'" class="ph-fill ph-circle" aria-hidden="true"></i></button>
                                    <template x-for="method in historyPaymentOptions" :key="method"><button type="button" :class="historyPayment === method ? 'is-selected' : ''" @click="historyPayment = method; open = false"><span x-text="method"></span><i x-show="historyPayment === method" class="ph-fill ph-circle" aria-hidden="true"></i></button></template>
                                    <button x-show="!['asset', 'loss'].includes(config.type)" type="button" :class="historyPayment === 'split' ? 'is-selected' : ''" @click="historyPayment = 'split'; open = false">Multi/Split<i x-show="historyPayment === 'split'" class="ph-fill ph-circle" aria-hidden="true"></i></button>
                                </div>
                            </div>
                            <div x-show="config.type === 'shipping' && groupBy === 'date' && historyCourierOptions.length" class="report-mobile-history-filter-field" x-data="{open:false}" @click.outside="open = false">
                                <span class="report-mobile-field__label">Kurir</span>
                                <button type="button" class="report-mobile-history-date-trigger" @click="open = !open" :aria-expanded="open"><span x-text="historyCourier === 'all' ? 'Semua Kurir' : (historyCourier === 'null' ? 'Tanpa Kurir / Lainnya' : (historyCourierOptions.find(item => item.id === historyCourier)?.name || 'Semua Kurir'))"></span><i class="ph-bold ph-caret-down" :class="open ? 'is-open' : ''" aria-hidden="true"></i></button>
                                <div x-show="open" x-cloak class="report-mobile-history-option-menu"><button type="button" :class="historyCourier === 'all' ? 'is-selected' : ''" @click="historyCourier = 'all'; open = false">Semua Kurir<i x-show="historyCourier === 'all'" class="ph-fill ph-circle" aria-hidden="true"></i></button><template x-for="courier in historyCourierOptions" :key="courier.id"><button type="button" :class="historyCourier === courier.id ? 'is-selected' : ''" @click="historyCourier = courier.id; open = false"><span x-text="courier.name"></span><i x-show="historyCourier === courier.id" class="ph-fill ph-circle" aria-hidden="true"></i></button></template></div>
                            </div>
                            <div class="report-mobile-history-filter-actions"><button type="button" @click="historyDate = config.today; historyFilterOpen = false; historyCalendarOpen = false; historyFilterExpanded = false">Hari Ini</button><button type="button" @click="historyDate = ''; historyPayment = 'all'; historyCourier = 'all'; historyFilterOpen = false; historyCalendarOpen = false; historyFilterExpanded = false">Reset Semua</button></div>
                        </div>
                    </div>
                </div>
                <div class="report-mobile-sheet__body report-mobile-history-body">
                    <div x-show="historyLoading" role="status" style="padding:28px;text-align:center;color:#9ca3af;font-size:12px">Memuat riwayat...</div>
                    <div x-show="config.type === 'adjusted' && !historyLoading" class="report-adjusted-history">
                        <section class="report-adjusted-history__summary">
                            <span>Total Laba Disesuaikan</span>
                            <strong x-text="rupiah(adjustedHistorySummary.final)"></strong>
                            <div><span><small>Laba Kotor</small><b x-text="rupiah(adjustedHistorySummary.grossProfit)"></b></span><span><small>± Koreksi Stok</small><b :class="adjustedHistorySummary.correction >= 0 ? 'is-positive' : 'is-negative'" x-text="(adjustedHistorySummary.correction > 0 ? '+ ' : '') + rupiah(adjustedHistorySummary.correction)"></b></span></div>
                        </section>
                        <div class="report-adjusted-history__timeline">
                            <template x-for="day in adjustedDailyBreakdown" :key="day.date">
                                <section class="report-adjusted-history__day">
                                    <h3><i class="ph-fill ph-calendar-blank" aria-hidden="true"></i><span x-text="fullDateLabel(day.date)"></span></h3>
                                    <article x-show="day.grossProfit !== 0 || day.revenue !== 0" class="report-adjusted-history__entry report-adjusted-history__entry--profit">
                                        <header><span><i class="ph-bold ph-shopping-cart-simple" aria-hidden="true"></i>Laba Kotor</span><strong x-text="rupiah(day.grossProfit)"></strong></header>
                                        <div><span><small>Pendapatan</small><b x-text="rupiah(day.revenue)"></b></span><span><small>HPP</small><b x-text="rupiah(day.hpp)"></b></span></div>
                                    </article>
                                    <article x-show="day.correction !== 0" class="report-adjusted-history__entry" :class="day.correction > 0 ? 'report-adjusted-history__entry--gain' : 'report-adjusted-history__entry--loss'">
                                        <header><span><i class="ph-bold ph-package" aria-hidden="true"></i>Koreksi Stok</span><strong :class="day.correction > 0 ? 'is-positive' : 'is-negative'" x-text="(day.correction > 0 ? '+ ' : '') + rupiah(day.correction)"></strong></header>
                                        <p><i class="ph-fill ph-info" aria-hidden="true"></i><span x-text="day.correction > 0 ? 'Surplus (Keuntungan)' : 'Defisit (Kerugian)'"></span></p>
                                    </article>
                                </section>
                            </template>
                            <div x-show="adjustedDailyBreakdown.length === 0" class="report-adjusted-history__empty"><i class="ph ph-calendar-blank" aria-hidden="true"></i><p>Tidak ada riwayat transaksi</p></div>
                        </div>
                    </div>
                    <div x-show="config.type !== 'adjusted'" class="report-mobile-history-list">
                        <template x-for="(entry, index) in filteredHistory" :key="index">
                            <article class="report-mobile-receipt">
                                <div class="report-mobile-receipt__head"><div><span class="report-mobile-receipt__eyebrow" x-text="config.type === 'shipping' ? 'No. Resi' : 'No. Nota'"></span><strong x-text="entry.invoice || entry.type || 'Transaksi'"></strong></div><div class="report-mobile-receipt__date"><strong x-text="entry.date_formatted"></strong><span x-text="entry.time_formatted"></span></div></div>
                                <div class="report-mobile-receipt__content">
                                    <p x-show="['sales', 'profit'].includes(config.type) && groupBy !== 'date' && branch === 'all' && entry.branch_name" class="report-mobile-receipt__branch" x-text="entry.branch_name"></p>
                                    <template x-for="line in historyDetails(entry)" :key="line.label"><div class="report-mobile-receipt__line"><span x-text="line.label"></span><strong x-text="line.value"></strong></div></template>
                                    <div class="report-mobile-receipt__total"><span x-text="historyTotalLabel(entry)"></span><strong x-text="historyAmount(entry)"></strong></div>
                                </div>
                            </article>
                        </template>
                        <p x-show="!historyLoading && filteredHistory.length === 0" style="padding:28px;text-align:center;color:#9ca3af;font-size:12px">Belum ada riwayat pada periode ini.</p>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
